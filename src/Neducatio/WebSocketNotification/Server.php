<?php
namespace Neducatio\WebSocketNotification;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

/**
 * Websocket Notification Server
 */
class Server implements WampServerInterface
{
  protected $channels;

  protected $logger;

  /**
   *
   * @param type $logger
   */
  public function __construct($logger = null)
  {
    $this->channels = [];
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function onOpen(ConnectionInterface $connection)
  {
    $this->log('INFO', 'new connection has been established');
  }

  /**
   * {@inheritdoc}
   */
  public function onClose(ConnectionInterface $connection)
  {
    $this->log('INFO', 'one connection has been closed');

    foreach($connection->WAMP->subscriptions as $channel) {
      $channel->remove($connection);
      $this->onUnSubscribe($connection, $channel);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCall(ConnectionInterface $connection, $id, $channel, array $params)
  {
    // RPC via websocket is not allowed. Call error!
    $connection->callError($id, $channel, 'You are not allowed to make calls')->close();
  }

  /**
   * {@inheritdoc}
   */
  public function onPublish(ConnectionInterface $connection, $channel, $event, array $exclude, array $eligible)
  {
    // Publishing via websocket is not allowed. Close connection immediately!
    $connection->close();
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscribe(ConnectionInterface $connection, $channel)
  {
    if (!$this->isAllowedToSubscribeChannel($connection, $channel)) {
      $connection->close();

      return;
    }

    $this->log('DEBUG', sprintf('channel %s has been subscribed', $channel));

    if (!array_key_exists($channel->getId(), $this->channels)) {
      $this->channels[$channel->getId()] = $channel;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onUnSubscribe(ConnectionInterface $connection, $channel)
  {
    $this->log('DEBUG', sprintf('channel %s has been unsubscribed', $channel));

    if (0 === $channel->count()) {
      $this->log('DEBUG', sprintf('%s channel is now unsubscribed', $channel));
      unset($this->channels[$channel->getId()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onError(ConnectionInterface $conn, \Exception $e)
  {
  }

  /**
   * On Server Push Callback
   *
   * @param  string $JSONPayload jsonfied array with channel and data keys
   *
   * @return mixed
   */
  public function onServerPush($JSONPayload)
  {
    try {
      $payload = Common\JSON::decode($JSONPayload);

      if (!$this->isValid($payload)) {
        return;
      }

      $this->log('DEBUG', sprintf('new entry has been published to %s channel', $payload['channel']));

      if (!array_key_exists($payload['channel'], $this->channels)) {
        $this->log('DEBUG', sprintf('there are no subscribers of %s channel', $payload['channel']));

        return;
      }

      $channel = $this->channels[$payload['channel']];
      $channel->broadcast(Common\JSON::encode(['data' => $payload['data']]));

      $this->log('INFO', sprintf('new entry has been sent to %d subscriber%s', $count = $channel->count(), $count > 1 ? 's' : ''));

    } catch (\InvalidArgumentException $ex) {
      $this->log('WARN', $ex->getMessage());
    }
  }

  /**
   * Write message to logger (if available)
   *
   * @param string $level
   * @param string $message
   */
  protected function log($level, $message)
  {
    if (null !== $this->logger) {
      $this->logger->log($level, $message);
    }
  }

  /**
   * Answers whether connection is allowed to subscribe given channel
   *
   * @param string $channel
   *
   * @return boolean
   */
  protected function isAllowedToSubscribeChannel(ConnectionInterface $connection, $channel)
  {
    if (isset($connection->Session) && (!is_array($availableChannels = $connection->Session->get('wsn_server_channels')) || !in_array($channel->getId(), $availableChannels))) {
      return false;
    }

    return true;
  }

  /**
   * Answers whether payload is valid
   *
   * @param array $payload
   *
   * @return boolean
   */
  private function isValid(array $payload)
  {
    $isValid = true;

    if (!array_key_exists('channel', $payload)) {
      $isValid = false;
      $this->log('WARN', 'payload array lacks of channel key');
    }

    if (!array_key_exists('data', $payload)) {
      $isValid = false;
      $this->log('WARN', 'payload array lacks of data key');
    }

    return $isValid;
  }
}
