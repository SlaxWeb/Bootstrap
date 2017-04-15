<?php
namespace SlaxWeb\Bootstrap\Service;

use SlaxWeb\Router\Route;
use SlaxWeb\Router\Request;
use SlaxWeb\Router\Response;
use Pimple\Container as App;
use SlaxWeb\Router\Container as RoutesContainer;
use SlaxWeb\Router\Dispatcher as RouteDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Router Service Provider
 *
 * Router Service Provider exposes classes of the Router component to the
 * dependency injection container.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
class RouterProvider implements \Pimple\ServiceProviderInterface
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
        // new Route class instance
        $app["router.newRoute"] = $app->factory(function () {
            return new Route;
        });

        /*
         * Routes Container
         *
         * Requires the Logger Service Provider to be registered prior to its
         * own instantiation.
         */
        $app["routesContainer.service"] = function (App $app) {
            return new RoutesContainer($app["logger.service"]("System"));
        };

        /*
         * Route Dispatcher
         *
         * Requires the Routes Container, the Hooks Container, and the Logger.
         * This Service gathers all required services, and instantiates the
         * Dispatcher. Just make sure all required service providers are
         * registered prior to instantiating the Dispatcher
         */
        $app["routeDispatcher.service"] = function (App $app) {
            $dispatcher = new RouteDispatcher(
                $app["routesContainer.service"],
                $app["hooks.service"],
                $app["logger.service"]("System")
            );

            $config = $app["config.service"];
            if ($config["app.segmentBasedMatch"] === true) {
                $dispatcher->enableSegMatch(
                    $config["app.controllerNamespace"],
                    [$app],
                    $config["app.segmentBasedUriPrepend"],
                    $config["app.segmentBasedDefaultMethod"]
                );
            }
            return $dispatcher;
        };

        // new Request object from superglobals or pre set base url
        $app["request.service"] = function (App $app) {
            if (isset($app["requestParams"])) {
                $method = $app["requestParams"]["method"] ?? $_SERVER["REQUEST_METHOD"];
                $request = Request::create(
                    $app["requestParams"]["uri"],
                    $method,
                    array_merge($_GET, $_POST),
                    $_COOKIE,
                    $_FILES,
                    $_SERVER
                );

                /*
                 * prepare request parameters from request content, copy from
                 * Symfony Http Foundation Request method "createFromGlobals"
                 */
                if (strpos($request->headers->get("CONTENT_TYPE"), "application/x-www-form-urlencoded") === 0
                    && in_array(strtoupper($request->server->get("REQUEST_METHOD", "GET")),
                        ["PUT", "DELETE", "PATCH"])) {
                    parse_str($request->getContent(), $data);
                    $request->request = new ParameterBag($data ?? []);
                }
            } else {
                $request = Request::createFromGlobals();
            }

            return $request;
        };

        // new empty Response object
        $app["response.service"] = function () {
            return new Response;
        };
    }
}
