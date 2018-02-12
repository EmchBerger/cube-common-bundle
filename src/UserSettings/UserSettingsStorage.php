<?php

namespace CubeTools\CubeCommonBundle\UserSettings;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use CubeTools\CubeCommonBundle\Entity\UserSettings;

/**
 * UserSettings service.
 */
class UserSettingsStorage
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var \Symfony\Component\Security\Core\User\UserInterface
     */
    private $user;

    /**
     * Constructor for this service.
     *
     * @param ManagerRegistry       $doctrine
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $doctrine, TokenStorageInterface $tokenStorage, EventDispatcherInterface $dispatcher)
    {
        $this->em = $doctrine->getManager();
        $this->dispatcher = $dispatcher;
        $token = $tokenStorage->getToken();
        if (null === $token) {
            return;
        }
        $user = $token->getUser();
        if (!is_object($user)) {
            return;
        }
        $this->user = $user;
    }

    /**
     * Get a User Setting.
     *
     * @param string $type
     * @param string $settingId
     *
     * @return mixed
     */
    public function getUserSetting($type, $settingId)
    {
        $ent = $this->getEntity($type, $settingId);
        $eventName = 'cube_common.userSettingNotFound';
        if (!is_null($ent)) {
            $ret = $ent->getValue();
        } elseif ($this->dispatcher->hasListeners($eventName)) {
            // get default value from listener
            $event = new ValueEvent($type, $settingId);
            $this->dispatcher->dispatch($eventName, $event);
            $ret = $event->getValue();
        } else {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Set a User Setting.
     *
     * @param string $type
     * @param string $settingId
     * @param mixed  $settings
     *
     * @return self $this
     */
    public function setUserSetting($type, $settingId, $settings)
    {
        $ent = $this->getEntity($type, $settingId);
        if (null === $ent) {
            $ent = new UserSettings();
            $ent->setRelatedUser($this->user);
            $ent->setType($type);
            $ent->setSettingId($settingId);
            $this->em->persist($ent);
        }
        $ent->setValue($settings);
        $this->em->flush();

        return $this;
    }

    /**
     * Get one user settings entity.
     *
     * @param string $type
     * @param string $settingId
     *
     * @return mixed
     */
    private function getEntity($type, $settingId)
    {
        return $this->em->getRepository(UserSettings::class)
            ->findOneBy(array('relatedUser' => $this->user, 'type' => $type, 'settingId' => $settingId));
    }
}
