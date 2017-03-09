<?php
namespace SlaxWeb\Bootstrap\Service;

use Pimple\Container as App;

/**
 * Hooks Service Provider
 *
 * Service Provider for the Pimple\Container for convenient creation of the
 * Hooks container service, and creation of new, empty Hook objects for
 * injection into the container.
 *
 * @package   SlaxWeb\Hooks
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.5
 */
class HooksProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register provider
     *
     * Register the Hooks Service Provider to the DIC.
     *
     * @param \Pimple\Container $app DIC
     * @return void
     */
    public function register(App $app)
    {
        $app["hooks.service"] = function (App $app) {
            return new \SlaxWeb\Hooks\Container($app["logger.service"]("System"));
        };

        $app["newHook.factory"] = $app->factory(
            function (App $app) {
                return new \SlaxWeb\Hooks\Hook;
            }
        );
    }
}
