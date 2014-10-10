<?php
namespace Neducatio\WebSocketNotification\Tests;

use Neducatio\WebSocketNotification\Server;
use Neducatio\WebSocketNotification\Subscriber;
use Mockery as m;

/**
 * ServerShould
 */
class ServerShould extends \PHPUnit_Framework_TestCase
{
  const ID            = 192;
  const CHANNEL_ID    = 'test_channel';
  const CONNECTION_ID = 193;
  const SUBSCRIBER_ID = 194;

  /**
   * @var Server
   */
  private $server;

  /**
   * Mock object
   *
   * @var Ratchet\ConnectionInterface
   */
  private $connection;

  /**
   * Mock object
   *
   * @var Ratchet\Wamp\Topic
   */
  private $channel;

  /**
   * Mock object
   *
   * @var Neducatio\WebSocketNotification\Common\Loggable
   */
  private $logger;

  /**
   * Test for __construct
   *
   * @test
   */
  public function beInstanceOfServer()
  {
    $this->assertInstanceOf('Neducatio\WebSocketNotification\Server', $this->server);
  }

  /**
   * Test for Server::onCall
   *
   * @test
   */
  public function shouldCloseOnectionOnRPCCall()
  {
    $this->connection->shouldReceive('callError')->with(self::ID, $this->channel, 'You are not allowed to make calls')->once()->andReturn($this->connection);
    $this->connection->shouldReceive('close')->once();

    $this->server->onCall($this->connection, self::ID, $this->channel, []);
  }

  /**
   * Test for Server::onPublish
   *
   * @test
   */
  public function shouldCloseConnectionOnPublish()
  {
    $this->connection->shouldReceive('close')->once();

    $this->server->onPublish($this->connection, $this->channel, null, [], []);
  }

  /**
   * Test for Server::onSubscribe
   *
   * @test
   */
  public function allowConnectionToSubscribeChannelWhen_sessionIsNotAvaiable()
  {
    $this->channel->shouldReceive('getId')->andReturn(self::CHANNEL_ID);
    $this->channel->shouldReceive('__toString')->andReturn(self::CHANNEL_ID);

    $this->server->onSubscribe($this->connection, $this->channel);
    $this->assertChannelExists(self::CHANNEL_ID);
  }

  /**
   * Test for Server::onSubscribe
   *
   * @test
   */
  public function allowConnectionToSubscribeChannelWhen_channelIsInAvailableChannelsSet()
  {
    $this->channelIsInAvailableChannelsSet();
    $this->server->onSubscribe($this->connection, $this->channel);
    $this->assertChannelExists(self::CHANNEL_ID);
  }

  /**
   * Test for Server::onSubscribe
   *
   * @test
   */
  public function disallowConnectionToSubscribeChannelWhen_channelIsNotInAvailableChannelsSet()
  {
    $this->channelIsNotInAvailableChannelsSet();
    $this->server->onSubscribe($this->connection, $this->channel);
    $this->assertChannelCollectionIsEmpty();
  }

  /**
   * Test for Server::onUnSubscribe
   *
   * @test
   */
  public function unsubscribeChannel()
  {
    $this->channel->shouldReceive('count')->once()->andReturn(0);
    $this->channel->shouldReceive('getId')->times(3)->andReturn(self::CHANNEL_ID);
    $this->server->onSubscribe($this->connection, $this->channel);
    $this->assertChannelExists(self::CHANNEL_ID);
    $this->server->onUnSubscribe($this->connection, $this->channel);
    $this->assertChannelCollectionIsEmpty();
  }

  /**
   * Test for Server::onServerPush
   *
   * @test
   */
  public function notBrodcastMessageWhenPayloadIsInvalid()
  {
    $this->logger->shouldReceive('log')->with('WARN', 'payload array lacks of data key')->once();
    $this->server->onServerPush('{"channel" : "test_channel"}');
  }

  /**
   * Test for Server::onServerPush
   *
   * @test
   */
  public function notBrodcastMessageWhenChanelIsNotSubscribed()
  {
    $this->logger->shouldReceive('log')->with('INFO', sprintf('new entry has been published to %s channel', self::CHANNEL_ID))->once();
    $this->logger->shouldReceive('log')->with('INFO', sprintf('there are no subscribers of %s channel', self::CHANNEL_ID))->once();
    $this->server->onServerPush('{"channel" : "test_channel", "data" : []}');
  }

  /**
   * Test for Server::onServerPush
   *
   * @test
   */
  public function brodcastMessageWhen_chanelIsSubscribed()
  {
    $this->chanelIsSubscribed();
    $this->server->onServerPush('{"channel" : "test_channel", "data" : []}');
  }

  /**
   * {@inheritdoc}
   */
  public function setUp()
  {
    $this->connection = m::mock('Ratchet\ConnectionInterface');
    $this->connection->resourceId = self::CONNECTION_ID;
    $this->channel = m::mock('Ratchet\Wamp\Topic');
    $this->logger = m::mock('Neducatio\WebSocketNotification\Common\Loggable');
    $this->logger->shouldReceive('log')->byDefault();

    $this->server = new Server($this->logger);

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown()
  {
    m::close();
    parent::tearDown();
  }

  // <editor-fold defaultstate="collapsed" desc="assertions">
  private function assertChannelExists($channel)
  {
    $this->assertArrayHasKey($channel, $this->getChannels());
  }

  private function assertChannelCollectionIsEmpty()
  {
    $this->assertEmpty($this->getChannels());
  }
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc="helpers">
  private function getChannels()
  {
    $propertyReflection = new \ReflectionProperty('Neducatio\WebSocketNotification\Server', 'channels');
    $propertyReflection->setAccessible(true);

    return $propertyReflection->getValue($this->server);
  }

  private function channelIsNotInAvailableChannelsSet()
  {
    $this->connection->Subscriber = new Subscriber(self::SUBSCRIBER_ID, []);
    $this->channel->shouldReceive('getId')->andReturn(self::CHANNEL_ID);
    $this->connection->shouldReceive('close')->once();
  }

  private function channelIsInAvailableChannelsSet()
  {
    $this->connection->Subscriber = new Subscriber(self::SUBSCRIBER_ID, [self::CHANNEL_ID]);
    $this->channel->shouldReceive('getId')->andReturn(self::CHANNEL_ID);
    $this->channel->shouldReceive('__toString')->andReturn(self::CHANNEL_ID);
    $this->connection->shouldReceive('close')->never();
  }

  private function chanelIsSubscribed()
  {
    $propertyReflection = new \ReflectionProperty('Neducatio\WebSocketNotification\Server', 'channels');
    $propertyReflection->setAccessible(true);
    $propertyReflection->setValue($this->server, [self::CHANNEL_ID => $this->channel]);
    $this->channel->shouldReceive('broadcast')->with('{"data":[]}')->once();
    $this->channel->shouldReceive('count')->andReturn(1);
    $this->logger->shouldReceive('log')->with('INFO', sprintf('new entry has been published to %s channel', self::CHANNEL_ID))->once();
    $this->logger->shouldReceive('log')->with('INFO', 'new entry has been sent to 1 subscriber')->once();
  }

  // </editor-fold>
}
