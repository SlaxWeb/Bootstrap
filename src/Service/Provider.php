<?php
namespace SlaxWeb\Router\Service;

use Pimple\Container as Application;

/**
 * Bootstrap Service Provider
 *
 * Bootstrap Service Provider exposes the Controller Loader service to the dependency
 * injection container.
 *
 * @package   SlaxWeb\Bootstrap
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
class Provider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register provider
     *
     * Register the Controller Loader with the DIC.
     *
     * @param \Pimple\Container $app DIC
     * @return void
     */
    public function register(Application $app)
    {
        $app["loadController.service"] = $app->protect(
            function (string $name) use ($app) {
                $args = func_get_args();
                $cacheName = "loadController.service-{$name}";
                if (isset($app[$cacheName])) {
                    return $app[$cacheName];
                }

                $config = $app["config.service"];
                $class = rtrim($config["app.controllerNamespace"], "\\")
                    . "\\"
                    . str_replace("/", "\\", $name);
                $app[$cacheName] = new $class($app);

                if (method_exists($app[$cacheName], "init")) {
                    array_shift($args);
                    $app[$cacheName]->init(...$args);
                }

                return $app[$cacheName];
            }
        );
    }
}
