<?php

namespace CubeTools\CubeCommonBundle\Session;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handle events for keeping session data on success only.
 */
class KeepOnSuccessEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array(
                array('handleDeleteOnError', -88 /* higher than SessionSaveListener (and profiler) */),
            ),
        );
    }

    public function handleDeleteOnError(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        if ($response->isSuccessful()) {
            // fine, remove delete instruction
            $session = $event->getRequest()->getSession();
            $session->remove(KeepOnSuccess::STORAGE_KEY);
        } elseif (!$response->isRedirection() && !$response->isInformational()) {
            $session = $event->getRequest()->getSession();
            if (null === $session) {
                return;
            }
            // failed, remove keys
            foreach (array_keys($session->get(KeepOnSuccess::STORAGE_KEY, array())) as $key) {
                $session->remove($key);
            }
            $session->remove(KeepOnSuccess::STORAGE_KEY);
        }
        // else keep info until success or failure
    }
}
