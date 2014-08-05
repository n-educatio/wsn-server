<?php
namespace Neducatio\WebSocketNotification\Tests\Common;

use Neducatio\WebSocketNotification\Common\JSON;

/**
 * JSONShould
 */
class JSONShould extends \PHPUnit_Framework_TestCase
{
  /**
   * Test for JSON::encode
   *
   * @test
   */
  public function serializeToJSON()
  {
    $this->assertEquals('"foo"', JSON::encode('foo'));
    $this->assertEquals('["foo"]', JSON::encode(['foo']));
  }

  /**
   * Test for JSON::decode
   *
   * @test
   */
  public function deserializeJSON()
  {
    $this->assertEquals('foo', JSON::decode('"foo"'));
    $this->assertEquals(['foo'], JSON::decode('["foo"]'));
  }

  /**
   * Test for JSON::decode
   *
   * @test
   * @expectedException RuntimeException
   */
  public function thrownRuntimeExceptionWhenDecodinMalformedJSON()
  {
    JSON::decode('"foo');
  }
}
