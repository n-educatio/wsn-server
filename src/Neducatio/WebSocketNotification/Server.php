<?php
namespace Neducatio\WebSocketNotification;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\Wamp\TopicManager as ChannelManager;

/**
 * Websocket Notification Server
 */
class Server implements WampServerInterface
{
  /**
   * @var ChannelManager
   */
  protected $channelManager;

  /**
   * @var \Ratchet\Wamp\Topic[]
   */
  protected $channels;

  /**
   * @var Sunscriber[]
   */
  protected $subscribers;

  /**
   * @var  \Neducatio\WebSocketNotification\Common\Loggable
   */
  protected $logger;

  /**
   *
   * @param type $logger
   */
  public function __construct($logger = null)
  {
    $this->channels = [];
    $this->subscribers = [];
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function onOpen(ConnectionInterface $connection)
  {
    $this->log('INFO', sprintf('connection #%d has been established', $connection->resourceId));

    if (isset($connection->Session)) {
      /**
       * @var Subscriber
       */
      $subscriber = $connection->Subscriber = $connection->Session->get('wsn_server_subscriber');
      if (is_null($subscriber)) {
        $this->log('ERROR', 'Subscriber not set in session or session empty! Have you run web socket server in the same environment as subscriber ?');
        die;
      }
      $subscriber->setConnection($connection);
      $this->subscribers[(string) $subscriber] = $subscriber;      
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onClose(ConnectionInterface $connection)
  {
    $this->log('INFO', sprintf('connection #%d has been closed', $connection->resourceId));

    foreach($connection->WAMP->subscriptions as $channel) {
      $channel->remove($connection);
      $this->onUnSubscribe($connection, $channel);
    }

    if (isset($connection->Subscriber)) {
      unset($this->subscribers[(string) $connection->Subscriber], $connection->Subscriber);
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
    if (isset($connection->Subscriber) && !$connection->Subscriber->isChannelGranted($channel)) {
      $connection->close();

      return;
    }

    $this->log('INFO', sprintf('connection #%s has subscribed %s channel', $connection->resourceId, $channel));

    if (!array_key_exists($channel->getId(), $this->channels)) {
      $this->channels[$channel->getId()] = $channel;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onUnSubscribe(ConnectionInterface $connection, $channel)
  {
    $this->log('INFO', sprintf('connection #%d has unsubscribed %s channel', $connection->resourceId, $channel));

    if (0 === $channel->count()) {
      $this->log('INFO', sprintf('channel %s is now unsubscribed', $channel));
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

      if (!$this->isValid($payload, ['channel', 'data'])) {
        return;
      }

      $this->log('INFO', sprintf('new entry has been published to %s channel', $payload['channel']));

      if (!array_key_exists($payload['channel'], $this->channels)) {
        $this->log('INFO', sprintf('there are no subscribers of %s channel', $payload['channel']));

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
   * Manage subscriber channel
   *
   * @param string $JSONPayload
   */
  public function channelManagement($JSONPayload)
  {

    try {
      $payload = Common\JSON::decode($JSONPayload);

      if (!$this->isValid($payload, ['channel', 'subscriber', 'action']) || !array_key_exists($subscriberId = $payload['subscriber'], $this->subscribers)) {
        return;
      }

      /**
       * @var Subscriber
       */
      $subscriber = $this->subscribers[$subscriberId];

      if (Subscriber::CHANNEL_REVOKE === ($action = $payload['action'])) {
        $subscriber->revokeChannel($payload['channel']);
        $this->logger->log('INFO', sprintf('channel %s has been revoked from connection #%s', $payload['channel'], $subscriber->getConnection()->resourceId));
        $this->channelManager->onUnsubscribe($subscriber->getConnection(), $payload['channel']);
        $this->channels[$subscriber->getManagementChannel()]->broadcast(Common\JSON::encode(['action' => 'unsubscribe', 'channel' => $payload['channel']]));
      }
      elseif (Subscriber::CHANNEL_GRANT) {
        $subscriber->grantChannel($payload['channel']);
        $this->logger->log('INFO', sprintf('channel %s has been granted to connect #%s', $payload['channel'], $subscriber->getConnection()->resourceId));
        $this->channels[$subscriber->getManagementChannel()]->broadcast(Common\JSON::encode(['action' => 'subscribe', 'channel' => $payload['channel']]));
      }

    } catch (\InvalidArgumentException $ex) {
      $this->log('WARN', $ex->getMessage());
    }
  }

  /**
   * @param  $channelManager
   */
  public function setChannelManager($channelManager)
  {
    $this->channelManager = $channelManager;
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
   * Answers whether payload is valid
   *
   * @param array $payload
   *
   * @return boolean
   */
  private function isValid(array $payload, $mandatoryKeys)
  {
    $isValid = true;

    foreach ($mandatoryKeys as $mandatoryKey) {
      if (!array_key_exists($mandatoryKey, $payload)) {
        $isValid = false;
        $this->log('WARN', sprintf('payload array lacks of %s key', $mandatoryKey));
      }
    }

    return $isValid;
  }
}
