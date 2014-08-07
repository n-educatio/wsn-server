<?php
namespace Neducatio\WebSocketNotification\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\DependencyInjection\Container,
    Symfony\Component\DependencyInjection\ContainerInterface;
use Ratchet\Http\HttpServer,
    Ratchet\Server\IoServer,
    Ratchet\Wamp\WampServer,
    Ratchet\WebSocket\WsServer,
    Ratchet\Session\SessionProvider;
use React\EventLoop\Factory,
    React\Socket\Server as SocketServer,
    React\ZMQ\Context;
use ZMQ;
use Neducatio\WebSocketNotification\Server as WSNServer;

/**
 * Server
 */
class Server extends Command
{
  /**
   * @var Container
   */
  protected $container;

  /**
   * @var logger
   */
  protected $logger;

  /**
   * @var \SessionHandlerInterface
   */
  protected $sessionHandler;

  /**
   * Set container
   *
   * @param ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container)
  {
    $this->container = $container;
  }

  protected function configure()
  {
    $this
      ->setName('neducatio:wsn-server:run')
      ->setDescription('Start websocket notification server')
      ->addOption(
        'port',
        'p',
        InputOption::VALUE_OPTIONAL
      )
      ->addOption(
        'host',
        'H',
        InputOption::VALUE_OPTIONAL
      )
      ->addOption(
        'websocket-port',
        'w',
        InputOption::VALUE_OPTIONAL
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $configuration = $this->processConfiguration($input);

    $loop = Factory::create();
    $webSocketNotificationServer = new WSNServer($this->logger);
    $context = new Context($loop);
    $pullServer = $context->getSocket(ZMQ::SOCKET_PULL);
    $pullServer->bind($pullAddress = sprintf('tcp://%s:%d', $configuration['host'], (int) $configuration['port']));
    $pullServer->on('message', [$webSocketNotificationServer, 'onServerPush']);

    $socketServer = new SocketServer($loop);
    $socketServer->listen((int) $configuration['websocket-port'], '0.0.0.0');

    $wampServer = new WampServer($webSocketNotificationServer);

    new IoServer(
      new HttpServer(
        new WsServer(
          is_null($this->sessionHandler)
            ? $wampServer
            : new SessionProvider($wampServer, $this->sessionHandler)
        )
      ),
      $socketServer
    );

    $output->writeln("Pull server is running on <info>$pullAddress</info>");
    $output->writeln("WebSocket server is listening on port <info>${configuration['websocket-port']}</info>");
    $loop->run();
  }


  protected function processConfiguration($commandLineInput)
  {
    if (null === $this->container) {
      throw new \RuntimeException('No container was set, provide one.');
    }

    $this->logger = $this->container->get('logger');
    $this->sessionHandler = $this->container->get('session_handler');

    $config = [];

    foreach(['host', 'port', 'websocket-port'] as $paramaterName) {
      $config[$paramaterName] = (null !== ($parameter = $commandLineInput->getOption($paramaterName)))
        ? $parameter
        : $this->container->getParameter($paramaterName);
    }

    return $config;
  }
}