<?php
namespace SlaxWeb\Bootstrap\Service;

use Pimple\Container as Application;

/**
 * Output component service provider
 *
 * The Output component service provider exposes the Output manager and its helpers
 * to the dependency injection container as services.
 *
 * @package   SlaxWeb\Bootstrap
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
class OutputProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register provider
     *
     * Register the Hooks Service Provider to the DIC.
     *
     * @param \Pimple\Container $app DIC
     * @return void
     */
    public function register(Application $app)
    {
        $app["output.service"] = function (Application $app) {
            $config = $app["config.service"];
            $manager = new \SlaxWeb\Output\Manager(
                $app["logger.service"]("System"),
                $app["response.service"],
                [
                    "enabled"           =>  $config["output.enable"] ?? false,
                    "allowOutput"       =>  $config["output.permitDirectOutput"] ?? true,
                    "environment"       =>  $config["app.environment"] ?? "development"
                ], [
                    "style"     =>  realpath(__DIR__ . "/../../resources/errorstyles.html"),
                    "template"  =>  realpath(__DIR__ . "/../../resources/error.php")
                ]
            );
            $manager->setHandlerGetter($app["outputHandler.service"]);
            return $manager;
        };

        $app["outputHandler.service"] = $app->protect(function () use ($app) {
            $handler = $app["outputHandler"] ?? $app["config.service"]["output.defaultHandler"];

            switch ($handler) {
                case "view":
                    $handler = $app["outputViewHandler.service"];
                    break;

                case "json":
                    $handler = $app["outputJsonHandler.service"];
                    break;

                default:
                    if (isset($app[$handler])) {
                        $handler = $app[$handler];
                    } elseif (class_exists($handler)) {
                        $handler = new $handler;
                    } else {
                        throw new Exception(
                            "Output Handler class {$handler} does not exist."
                        );
                    }
            }

            return $handler;
        });

        $app["outputViewHandler.service"] = function () {
            return new \SlaxWeb\Output\Handler\View;
        };

        $app["outputJsonHandler.service"] = function () {
            return new \SlaxWeb\Output\Handler\Json;
        };
    }
}
