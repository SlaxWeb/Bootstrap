<?php
namespace SlaxWeb\Bootstrap\Service;

use SlaxWeb\Logger\Helper;
use Pimple\Container as App;
use Monolog\Logger as MLogger;
use SlaxWeb\Config\Container as Config;

/**
 * Logger Service Provider
 *
 * Register the logger and its handler as services.
 *
 * @package   SlaxWeb\Logger
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
class LoggerProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register Logger Service Provider
     *
     * Method used by the DIC to register a new service provider. This Service
     * Provider defines only the Logger service.
     *
     * @param \Pimple\Container $app Pimple Dependency Injection Container
     * @return void
     */
    public function register(App $app)
    {
        $app["logger.service"] = $app->protect(
            function (string $loggerName = "") use ($app) {
                $cacheName = "logger.service-{$loggerName}";
                if (isset($app[$cacheName])) {
                    return $app[$cacheName];
                }
                /*
                 * Check the config service has been defined and provides correct
                 * object
                 */
                if (isset($app["config.service"]) === false
                    || get_class($app["config.service"]) !== "SlaxWeb\\Config\\Container") {
                    throw new \SlaxWeb\Bootstrap\Exception\LoggerConfigException(
                        "Config component provider must be registered before you can use the Logger component."
                    );
                }

                $config = $app["config.service"];
                if ($loggerName === "") {
                    $loggerName = $config["logger.defaultLogger"];
                }

                $logger = new MLogger($loggerName);
                foreach ($config["logger.loggerSettings"][$loggerName] as $type => $settings) {
                    // load propper handler and instantiate the Monolog\Logger
                    $handler = null;
                    switch ($type) {
                        case Helper::L_TYPE_FILE:
                            $app["temp.logger.settings"] = $settings;
                            $handler = $app["logger.{$type}.service"];
                            unset($app["temp.logger.settings"]);
                            break;
                        default:
                            throw new \SlaxWeb\Bootstrap\Exception\UnknownLoggerHandlerException(
                                "The handler you are tring to use is not known or not supported."
                            );
                    }
                    $logger->pushHandler($handler);
                }

                return $app[$cacheName] = $logger;
            }
        );

        $app["logger.StreamHandler.service"] = $app->factory(
            function (App $cont) {
                $settings = $cont["temp.logger.settings"];

                // if the log file name does not begin with a dir separator, treat
                // it as relative path and prepend 'logFilePath'
                if ($settings[0][0] !== DIRECTORY_SEPARATOR) {
                    $settings[0] = ($cont["config.service"]["logger.logFilePath"] ?? "")
                        . $settings[0];
                }
                return new \Monolog\Handler\StreamHandler(...$settings);
            }
        );
    }
}
