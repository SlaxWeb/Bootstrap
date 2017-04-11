<?php
namespace SlaxWeb\Bootstrap\Service;

use SlaxWeb\Router\Route;
use Pimple\Container as App;

/**
 * Route Collection Helper
 *
 * The Route Collection Helper provides an easy way to add Routes to the
 * Container.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
abstract class RouteCollection implements \Pimple\ServiceProviderInterface
{
    /**
     * Application Container
     *
     * @var \Pimple\Container
     */
    protected $app = null;

    /**
     * Routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Before Route Dispatch Hook
     *
     * @var string
     */
    protected $beforeDispatch = "";

    /**
     * After Route Dispatch Hook
     *
     * @var string
     */
    protected $afterDispatch = "";

    /**
     * Register Service
     *
     * Method called by the Pimple\Container when registering this service.
     * From here the 'define' method is called, and then the protected property
     * 'routes' is iterated, and all found routes are added to the Route
     * Container. Also exposes the received DIC to the protected property.
     *
     * @param \Pimple\Container $app DIC
     * @return void
     */
    public function register(App $app)
    {
        $this->app = $app;
        $this->define();

        foreach ($this->routes as $route) {
            $newRoute = $app["router.newRoute"];
            $newRoute->set(
                ($route["uri"] ?? null),
                ($route["method"] ?? Route::METHOD_GET),
                ($route["action"] ?? null)
            );

            foreach (["beforeDispatch", "afterDispatch"] as $type) {
                if (isset($routeDefinition[$type])) {
                    $newRoute->setHook($routeDefinition[$type], $type === "afterDispatch");
                } elseif ($this->{$type} !== "") {
                    $newRoute->setHook($this->{$type}, $type === "afterDispatch");
                }
            }

            $app["routesContainer.service"]->add($newRoute);
        }
    }

    /**
     * Define Routes
     *
     * This method is called when the service is registered, and can be used to
     * add new route definitions to the internal container property.
     *
     * @return void
     */
    abstract public function define();
}
