<?php

namespace CubeTools\CubeCommonBundle\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginLdapFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Factory that creates services for the 'versatile' flavor of the ldap authentication
 */
class VersatileFormLoginLdapFactory extends FormLoginLdapFactory
{
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.versatile_ldap_bind.'.$id;
        $definition = $container
            ->setDefinition($providerId, new ChildDefinition(VersatileLdapBindAuthenticationProvider::class))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->replaceArgument(2, $id)
            ->replaceArgument(3, new Reference($config['service']))
            ->replaceArgument(4, $config['dn_string'])
        ;

        if (!empty($config['query_string'])) {
            $definition->addMethodCall('setQueryString', array($config['query_string']));
        }

        return $providerId;
    }

    public function getKey()
    {
        return 'versatile-form-login-ldap';
    }
}
