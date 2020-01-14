<?php

namespace CubeTools\CubeCommonBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;

/**
 * VersatileLdapBindAuthenticationProvider uses the User instance returned by
 * the UserProvider for authentication (rather than the username string). This
 * allows to use alternative ways of fetching the User instance.
 * In order to use the queryString, the ldap service passed as a constructor
 * argument must be bound anonymously.
 */
class VersatileLdapBindAuthenticationProvider extends LdapBindAuthenticationProvider
{
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, string $providerKey, LdapInterface $ldap, string $dnString = '{username}', bool $hideUserNotFoundExceptions = true, string $searchDn = '', string $searchPassword = '')
    {
        try {
            // ldap must be bound to allow anonymous search queries
            $ldap->bind();
        } catch (ConnectionException $e) {
            // hide exception
        }

        parent::__construct($userProvider, $userChecker, $providerKey, $ldap, $dnString, $hideUserNotFoundExceptions, $searchDn, $searchPassword);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $token->setUser($user);

        parent::checkAuthentication($user, $token);
    }
}
