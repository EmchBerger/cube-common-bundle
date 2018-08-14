<?php

namespace CubeTools\CubeCommonBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * VersatileLdapBindAuthenticationProvider uses the User instance returned by
 * the UserProvider for authentication (rather than the username string). This
 * allows to use alternative ways of fetching the User instance.
 * In order to use the queryString, the ldap service passed as a constructor
 * argument must be bound anonymously.
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
