<?php
/**
 * Configuration Exception
 *
 * Thrown when the Factory or the Service Provider detect that the Config
 * component has not been registered, or the configuration of the logger is not
 * complete.
 *
 * @package   SlaxWeb\Logger
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Bootstrap\Exception;

class LoggerConfigException extends \Exception
{
}
