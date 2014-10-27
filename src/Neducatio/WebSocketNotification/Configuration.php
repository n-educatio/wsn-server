<?php
namespace Neducatio\WebSocketNotification;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * WebSocket Notification Server Configuration
 */
class Configuration implements ConfigurationInterface
{
  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('biera_rtnserver');

    $rootNode
      ->children()
        ->scalarNode('host')
          ->defaultValue('127.0.0.1')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('port')
          ->defaultValue(5555)
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('websocket_port')
          ->defaultValue(8080)
          ->cannotBeEmpty()
        ->end()
      ->end();

    return $treeBuilder;
  }
}
