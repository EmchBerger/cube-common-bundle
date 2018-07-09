<?php

namespace CubeTools\CubeCommonBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * VersatileLdapBindAuthenticationProvider uses the User instance returned by
 * the UserProvider for authentication (rather than the username string). This
 * allows to use alternative ways of fetching the User instance.
 *
 * @author paschke
 */
class VersatileLdapBindAuthenticationProvider extends LdapBindAuthenticationProvider
{
    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $token->setUser($user);

        parent::checkAuthentication($user, $token);
    }
}
