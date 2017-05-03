<?php
namespace SlaxWeb\Bootstrap\Service;

use Pimple\Container as App;

/**
 * Component Commands Provider
 *
 * Load component commands into the 'slaxerCommands' application property with their
 * required dependencies. This provider also defines missing service providers for
 * the commands.
 *
 * @package   SlaxWeb\Config
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.7
 */
class ComponentCommandsProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register provider
     *
     * Register is called by the container, when the provider gets registered.
     *
     * @param \Pimple\Container $app Dependency Injection Container
     * @return void
     */
    public function register(App $app)
    {
        if (isset($app["guzzle.service"]) === false) {
            $app["guzzleClient.service"] = function() {
                return new \GuzzleHttp\Client;
            };
        }

        /*
         * This is only a temporary service definition to satisfy the install component
         * command and to load the service provider into it through the new params
         * as services method.
         *
         * @todo: remove when the component commands are refactored
         */
        $app["serviceProvider.service"] = function(App $app) {
            return $app;
        };

        $app["slaxerCommands"] = array_merge(
            $app["slaxerCommands"] ?? [],
            [
                \SlaxWeb\Bootstrap\Commands\Component\InstallCommand::class => [
                    "serviceProvider.service",
                    "guzzleClient.service"
                ]
            ]
        );
    }
}
