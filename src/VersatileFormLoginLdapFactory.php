<?php

namespace CubeTools\CubeCommonBundle\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Description of VersatileFormLoginLdapFactory
 *
 * @author paschke
 */
class VersatileFormLoginLdapFactory extends FormLoginFactory
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

    public function addConfiguration(NodeDefinition $node)
    {
        parent::addConfiguration($node);

        $node
            ->children()
                ->scalarNode('service')->defaultValue('ldap')->end()
                ->scalarNode('dn_string')->defaultValue('{username}')->end()
                ->scalarNode('query_string')->end()
            ->end()
        ;
    }

    public function getKey()
    {
        return 'versatile-form-login-ldap';
    }
}
