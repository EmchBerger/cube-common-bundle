<?php

namespace CubeTools\CubeCommonBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;

/**
 * VersatileLdapBindAuthenticationProvider uses the User instance returned by
 * the UserProvider for authentication (rather than the username string). This
 * allows to use alternative ways of fetching the User instance.
 *
 * @author paschke
 */
class VersatileLdapBindAuthenticationProvider extends LdapBindAuthenticationProvider
{
    private $ldap;
    private $queryString;

    /**
     * @param UserProviderInterface $userProvider               A UserProvider
     * @param UserCheckerInterface  $userChecker                A UserChecker
     * @param string                $providerKey                The provider key
     * @param LdapInterface         $ldap                       A Ldap client
     * @param string                $dnString                   A string used to create the bind DN
     * @param bool                  $hideUserNotFoundExceptions Whether to hide user not found exception or not
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, LdapInterface $ldap, $dnString = '{username}', $hideUserNotFoundExceptions = true)
    {
        parent::__construct($userProvider, $userChecker, $providerKey, $ldap, $dnString, $hideUserNotFoundExceptions);

        $this->ldap = $ldap;
    }

    /**
     * Set a query string to use in order to find a DN for the username.
     *
     * @param string $queryString
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;

        parent::setQueryString($queryString);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $token->setUser($user);

        // in order to use the query string, the LDAP connection must be bound
        if ($this->queryString) {
            try {
                $this->ldap->bind();
            } catch (ConnectionException $e) {
                throw new BadCredentialsException('could not query the server.');
            }
        }

        parent::checkAuthentication($user, $token);
    }
}
