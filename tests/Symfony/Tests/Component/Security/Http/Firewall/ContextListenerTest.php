<?php

namespace Symfony\Test\Component\Security\Http\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\SessionStorage\ArraySessionStorage;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Http\Firewall\ContextListener;

class ContextListenerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->securityContext = new SecurityContext(
            $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface'),
            $this->getMock('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface')
        );
    }

    protected function tearDown()
    {
        unset($this->securityContext);
    }

    public function testOnKernelResponseWillAddSession()
    {
        $session = $this->runSessionOnKernelResponse(
            new UsernamePasswordToken('test1', 'pass1', 'phpunit'),
            null
        );

        $token = unserialize($session->get('_security_session'));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $token);
        $this->assertEquals('test1', $token->getUsername());
    }

    public function testOnKernelResponseWillReplaceSession()
    {
        $session = $this->runSessionOnKernelResponse(
            new UsernamePasswordToken('test1', 'pass1', 'phpunit'),
            'C:10:"serialized"'
        );

        $token = unserialize($session->get('_security_session'));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $token);
        $this->assertEquals('test1', $token->getUsername());
    }

    public function testOnKernelResponseWillRemoveSession()
    {
        $session = $this->runSessionOnKernelResponse(
            null,
            'C:10:"serialized"'
        );

        $this->assertFalse($session->has('_security_session'));
    }

    protected function runSessionOnKernelResponse($newToken, $original = null)
    {
        $session = new Session(new ArraySessionStorage());

        if ($original !== null) {
            $session->set('_security_session', $original);
        }

        $this->securityContext->setToken($newToken);

        $request = new Request();
        $request->setSession($session);

        $event = new FilterResponseEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $listener = new ContextListener($this->securityContext, array(), 'session');
        $listener->onKernelResponse($event);

        return $session;
    }

    public function testOnKernelResponseWithoutSession()
    {
        $this->securityContext->setToken(new UsernamePasswordToken('test1', 'pass1', 'phpunit'));
        $request = new Request();

        $event = new FilterResponseEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $listener = new ContextListener($this->securityContext, array(), 'session');
        $listener->onKernelResponse($event);

        $this->assertFalse($request->hasSession());
    }
}
