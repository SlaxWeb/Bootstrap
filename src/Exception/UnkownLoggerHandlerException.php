<?php
/**
 * Unknown Handler Exception
 *
 * Thrown when the Factory or the Service Provider receive an unknown logger
 * type that they should instantiate. Also thrown when that provider exists, but
 * is not yet supported by the Logger component.
 *
 * @package   SlaxWeb\Logger
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Bootstrap\Exception;

class UnknownLoggerHandlerException extends \Exception
{
}
