<?php
namespace Neducatio\WebSocketNotification\Common;

/**
 * Simple logger that prints everything to given stream (STDOUT by default)
 */
class Logger implements Loggable
{
  private $stream;

  public function __construct($stream = STDOUT)
  {
    if (!is_resource($stream) || 'stream' !== get_resource_type($stream)) {
      throw new \InvalidArgumentException('You should provide resource of type stream');
    }
    
    $this->stream = $stream;
  }

  /**
   * Prints everything to given stream
   *
   * @param string $level
   * @param string $message
   */
  public function log($level, $message)
  {
    fprintf($this->stream, "[%s][%s]\t%s\n", date('Y-d-m H:i:s'), $level, $message);
  }
}
