<?php

namespace CubeTools\CubeCommonBundle\Security;

use FOS\UserBundle\Security\UserProvider;

/**
 * VersatileUserProvider extends the FOS UserProvider in order to allow
 * alternative ways of fetching User instances
 *
 * @author paschke
 */
class VersatileUserProvider extends UserProvider
{
    /**
     * {@inheritdoc}
     */
    protected function findUser($username)
    {
        if ($user = $this->userManager->findUserByUsername($username)) {
            return $user;
        } elseif ($user = $this->userManager->findUserByEmail($username)) {
            return $user;
        }
    }
}
