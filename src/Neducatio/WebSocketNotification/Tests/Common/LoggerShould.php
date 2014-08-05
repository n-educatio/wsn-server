<?php
namespace Neducatio\WebSocketNotification\Tests\Common;

use Neducatio\WebSocketNotification\Common\Logger;
use Neducatio\WebSocketNotification\Common\Loggable;

/**
 * Unit tests fot Neducatio\WebSocketNotification\Common\Logger
 */
class LoggerShould extends \PHPUnit_Framework_TestCase
{
  const LEVEL   = 'DEBUG';
  const MESSAGE = 'Secret message is Foo';

  /**
   * @var Logger
   */
  private $logger;
  private $stream;

  /**
   * @test
   */
  public function beInstnaceOfLoggable()
  {
    $this->assertInstanceOf('Neducatio\WebSocketNotification\Common\Loggable', $this->logger);
  }

  /**
   * @test
   */
  public function printMessageToSTDOUT()
  {
    $this->logger->log(self::LEVEL, self::MESSAGE);
    $this->assertRegExp(sprintf('/%s]\s%s/', self::LEVEL, self::MESSAGE), file_get_contents(stream_get_meta_data($this->stream)['uri']));
  }

  /**
   * {@inheritdoc}
   */
  public function setUp()
  {
    $this->logger = new Logger($this->stream = tmpfile());
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown()
  {
    fclose($this->stream);
    parent::tearDown();
  }
}
