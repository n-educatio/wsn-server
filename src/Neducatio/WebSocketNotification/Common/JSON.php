<?php
namespace Neducatio\WebSocketNotification\Common;

/**
 * JSON
 */
class JSON
{
  /**
   * Serialize to JSON
   *
   * @param type $data
   *
   * @return string
   */
  public static function encode($data)
  {
    $encoded = json_encode($data);
    self::checkErrors();

    return $encoded;
  }

  /**
   * Deserialize JSON
   *
   * @param string  $json
   * @param boolean $assoc
   *
   * @return mixed
   */
  public static function decode($json, $assoc = true)
  {
    $decoded = json_decode($json, $assoc);
    self::checkErrors();

    return $decoded;
  }

  protected static function checkErrors()
  {
    if (JSON_ERROR_NONE !== ($errorCode = json_last_error())) {
        throw new \RuntimeException(json_last_error_msg(), $errorCode);
    }
  }
}