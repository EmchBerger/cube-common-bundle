<?php

namespace Tests\CubeTools\CubeCommonBundle\Session;

use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use CubeTools\CubeCommonBundle\Session\KeepOnSuccess;
use CubeTools\CubeCommonBundle\Session\KeepOnSuccessEventListener;

class KeepOnSuccessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests keeping.
     *
     * with: set -> redirect -> pass -> error
     */
    public function testKeep()
    {
        $listener = new KeepOnSuccessEventListener();
        $event = $this->getResponseEvent();
        $mSess = $event->getRequest()->getSession();

        $mSess->set('stays3', 3);
        $mSess->set('toKeepA', 'hey');
        $mSess->set('toKeepB', $this);
        $notChanged = function () use ($mSess) {
            $this->assertSame(3, $mSess->get('stays3'));
            $this->assertSame('hey', $mSess->get('toKeepA'));
            $this->assertSame($this, $mSess->get('toKeepB'));
        };

        KeepOnSuccess::markFor($mSess, array('toKeepA', 'toKeepB'));
        $notChanged();

        $event->getResponse()->setStatusCode(Response::HTTP_TEMPORARY_REDIRECT);
        $listener->handleDeleteOnError($event);
        $notChanged();

        $event->getResponse()->setStatusCode(Response::HTTP_OK);
        $listener->handleDeleteOnError($event);
        $notChanged();

        $event->getResponse()->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
        $listener->handleDeleteOnError($event);
        $notChanged();
    }

    /**
     * Tests removing on failure.
     *
     * with: (set -> keep -> ) set -> redirect -> redirect -> failure -> pass
     */
    public function testRemoveOnFail()
    {
        $listener = new KeepOnSuccessEventListener();
        $event = $this->getResponseEvent();
        $mSess = $event->getRequest()->getSession();

        $mSess->set('keepW', array(9, 'v' => 'tU'));
        $mSess->set('staysX', $this);
        $notChanged1 = function () use ($mSess) {
            $this->assertSame($this, $mSess->get('staysX'));
            $this->assertSame(array(9, 'v' => 'tU'), $mSess->get('keepW'));
        };

        KeepOnSuccess::markFor($mSess, array('keepW'));
        $notChanged1();

        $event->getResponse()->setStatusCode(Response::HTTP_ACCEPTED);
        $listener->handleDeleteOnError($event);
        $notChanged1();

        $mSess->set('toRemoveY', 92);
        $mSess->set('toRemoveZ', 'zzz');
        $notChanged = function () use ($mSess, $notChanged1) {
            $notChanged1();
            $this->assertSame(92, $mSess->get('toRemoveY'));
            $this->assertSame('zzz', $mSess->get('toRemoveZ'));
        };

        KeepOnSuccess::markFor($mSess, array('toRemoveY', 'toRemoveZ', 'nonExisting'));
        $notChanged();

        $event->getResponse()->setStatusCode(Response::HTTP_PERMANENTLY_REDIRECT);
        $listener->handleDeleteOnError($event);
        // and 2nd redirect
        $event->getResponse()->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);
        $listener->handleDeleteOnError($event);
        $notChanged();

        $event->getResponse()->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
        $listener->handleDeleteOnError($event);
        $notChanged1();
        $this->assertFalse($mSess->has('toRemoveY'), 'has(toRemoveY)');
        $this->assertFalse($mSess->has('toRemoveZ'), 'has(toRemoveZ)');
    }

    private function getResponseEvent()
    {
        if (!class_exists(ResponseEvent::class)) {
            $this->markTestSkipped(ResponseEvent::class.' is not installed');
        }
        $mKernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $response = new Response();

        return new ResponseEvent($mKernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
    }
}
