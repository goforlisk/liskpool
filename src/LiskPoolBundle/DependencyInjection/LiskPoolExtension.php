<?php
namespace LiskPoolBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class LiskPoolExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('lisk_pool.delegate_username', $config['delegate_username']);
        $container->setParameter('lisk_pool.memcached.host', $config['memcached']["host"]);
        $container->setParameter('lisk_pool.memcached.port', $config['memcached']["port"]);
        $container->setParameter('lisk_pool.forging.nodes', $config['forging']['nodes']);
        $container->setParameter('lisk_pool.forging.secret', $config['forging']['secret']);
        $container->setParameter('lisk_pool.forging.second_secret', $config['forging']['second_secret']);
        $container->setParameter('lisk_pool.forging.public_key', $config['forging']['public_key']);
        $container->setParameter('lisk_pool.forging.fee_in_percentage', $config['forging']['fee_in_percentage']);
        $container->setParameter('lisk_pool.forging.minimum_payout', $config['forging']['minimum_payout']);
    }
}