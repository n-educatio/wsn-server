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
      throw new \RuntimeException(self::getErrorMessage($errorCode), $errorCode);
    }
  }

  private static function getErrorMessage($errorCode)
  {
      if (!function_exists('json_last_error_msg')) {
        switch ($errorCode) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return '';
        }
      } else {
        return json_last_error_msg();
      }
  }
}
