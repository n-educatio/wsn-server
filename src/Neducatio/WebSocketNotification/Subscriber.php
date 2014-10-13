<?php
namespace Neducatio\WebSocketNotification;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic as Channel;

/**
 * Identity
 */
class Subscriber
{
  const CHANNEL_REVOKE = 0;
  const CHANNEL_GRANT  = 1;

  /**
   * @var mixed
   */
  protected $id;

  /**
   * @var ConnectionInterface
   */
  protected $connection;

  /**
   * @var string[]
   */
  protected $grantedChannels;

  /**
   * Constructor :]
   *
   * @param mixed $id              unique subscriber's id
   * @param array $grantedChannels array of channels user is allowed to subscribe
   */
  public function __construct($id, array $grantedChannels = null)
  {
    $this->id = $id;
    $this->grantedChannels = $grantedChannels;

    if (is_null($grantedChannels)) {
      $this->grantedChannels = [];
    }
  }

  /**
   * Get subscriber id
   *
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set subscriber's connection
   *
   * @param \Ratchet\ConnectionInterface $connection
   */
  public function setConnection(ConnectionInterface $connection)
  {
    $this->connection = $connection;
  }

  /**
   * Get subscriber's connection
   *
   * @return \Ratchet\ConnectionInterface $connection
   */
  public function getConnection()
  {
    return $this->connection;
  }

  /**
   * Set subscriber's granted channels
   *
   * @param array $grantedChannels
   */
  public function setGrantedChannels(array $grantedChannels)
  {
    $this->grantedChannels = $grantedChannels;
  }

  /**
   * Get subscriber's granted channels
   *
   * @return array
   */
  public function getGrantedChannels()
  {
    return $this->grantedChannels;
  }

  /**
   * Revoke subscribe privilage to $channelName
   *
   * @param string $channelName
   */
  public function revokeChannel($channelName)
  {
    if (false !== ($key = array_search($channelName, $this->grantedChannels))) {
      unset($this->grantedChannels[$key]);
      // reset indexes
      $this->grantedChannels = array_values($this->grantedChannels);
    }
  }

  /**
   * Grant subscribe privilage to $channelName
   *
   * @param string $channelName
   */
  public function grantChannel($channelName)
  {
    if (!in_array($channelName, $this->grantedChannels)) {
      $this->grantedChannels[] = $channelName;
    }
  }

  /**
   * Clear all granted channels
   */
  public function clearChannels()
  {
    $this->grantedChannels = [];
  }

  /**
   * Answers whether $channelName is granted
   *
   * @param string $channelName
   *
   * @return boolean
   */
  public function isChannelGranted($channelName)
  {
    return in_array($channelName, $this->grantedChannels);
  }

  /**
   * Get string representation of object
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this->id;
  }
}