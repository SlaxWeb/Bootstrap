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
class OutputProvider extends \Pimple\ServiceProviderInterface
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
        $app["output.service"] = function(Application $app) {
            $config = $app["config.service"];
            return new \SlaxWeb\Output\Manager(
                $app["logger.service"]("System"),
                $app["response.service"],
                [
                    "enabled"           =>  $config["output.enable"] ?? false,
                    "allowOutput"       =>  $config["output.permitDirectOutput"] ?? true,
                    "mode"              =>  $config["output.defaultOutputMode"] ?? \SlaxWeb\Output\Manager::MODE_JSON,
                    "allowModeChange"   =>  $config["output.permitModeChange"] ?? true,
                    "environment"       =>  $config["app.environment"] ?? "development"
                ], [
                    "style"     =>  realpath(__DIR__ . "/../../resources/errorstyles.html"),
                    "template"  =>  realpath(__DIR__ . "/../../resources/error.php")
                ]
            );
        };
    }
}
