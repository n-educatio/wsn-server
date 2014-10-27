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
    //Ratchet\Wamp\WampServer,
    Ratchet\WebSocket\WsServer,
    Ratchet\Session\SessionProvider;
use React\EventLoop\Factory,
    React\Socket\Server as SocketServer,
    React\ZMQ\Context;
use ZMQ;
use Neducatio\WebSocketNotification\Wamp\WampServer;
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
        'websocket_port',
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

    $notificationSock = $context->getSocket(ZMQ::SOCKET_PULL);
    $notificationSock->bind($notificationSockAddr = sprintf('tcp://%s:%d', $configuration['host'], (int) $configuration['port']));
    $notificationSock->on('message', [$webSocketNotificationServer, 'onServerPush']);

    $managementSock = $context->getSocket(ZMQ::SOCKET_PULL);
    $managementSock->bind($managementSockAddr = sprintf('tcp://%s:%d', $configuration['host'], (int) $configuration['port'] + 1));
    $managementSock->on('message', [$webSocketNotificationServer, 'channelManagement']);

    $socketServer = new SocketServer($loop);
    $socketServer->listen((int) $configuration['websocket_port'], '0.0.0.0');

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

    $output->writeln(sprintf("[%s][INFO]\tmessage socket <info>%s</info>", date('Y-d-m H:i:s'), $notificationSockAddr));
    $output->writeln(sprintf("[%s][INFO]\tmanagement socket <info>%s</info>", date('Y-d-m H:i:s'), $managementSockAddr));
    $output->writeln(sprintf("[%s][INFO]\tweb socket server is listening on <info>%s</info>", date('Y-d-m H:i:s'), $configuration['websocket_port']));
    $loop->run();
  }

  protected function processConfiguration($commandLineInput)
  {
    if (null === $this->container) {
      throw new \RuntimeException('No container was set, provide one.');
    }

    $this->logger = $this->container->get('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $this->sessionHandler = $this->container->get('session_handler', ContainerInterface::NULL_ON_INVALID_REFERENCE);

    $config = [];

    foreach(['host', 'port', 'websocket-port'] as $paramaterName) {
      $config[$paramaterName] = (null !== ($parameter = $commandLineInput->getOption($paramaterName)))
        ? $parameter
        : $this->container->getParameter($paramaterName);
    }

    return $config;
  }
}