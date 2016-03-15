<?php
/**
 * Application class
 *
 * The Application class takes care of Application execution, and acts as an
 * dependency injection container, with the help of Pimple\Contaier.
 *
 * @package   SlaxWeb\Bootstrap
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Bootstrap;

use SlaxWeb\Router\Request;
use Psr\Log\LoggerInterface;
use SlaxWeb\Hooks\Container as HooksContainer;
use Symfony\Component\HttpFoundation\Response;
use SlaxWeb\Router\Dispatcher as RouteDispatcher;
use SlaxWeb\Router\Exception\RouteNotFoundException;

class Application extends \Pimple\Container
{
    /**
     * Route Dispatcher
     *
     * @var \SlaxWeb\Router\Dispatcher
     */
    protected $_router = null;

    /**
     * Hooks Container
     *
     * @var \SlaxWeb\Hooks\Container
     */
    protected $_hooks = null;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger = null;

    /**
     * Application Initialization
     *
     * Initializes the application class by setting the Router, Hooks, and
     * Logger objects to internal properties for later use.
     *
     * @param \SlaxWeb\Router\Dispatcher $router Rotue Dispathcer
     * @param \SlaxWeb\Hooks\Container $hooks Hooks Container
     * @param \Psr\Log\LoggerInterface $logger Logger implementing PSR4
     *                                         interface
     * @return void
     */
    public function init(
        RouteDispatcher $router,
        HooksContainer $hooks,
        LoggerInterface $logger
    ) {
        $this->_router = $router;
        $this->_hooks = $hooks;
        $this->_logger = $logger;

        $this->_logger->info("Application initialized");

        $this->_hooks->exec("application.init.after");
    }

    /**
     * Execute Application
     *
     * Take a Request and Resonse, and dispatch them with the help of the Route
     * Dispatcher.
     *
     * @param \SlaxWeb\Route\Request $request Received Request
     * @param \Symfony\Component\HttpFoundation\Response Prepared Response
     * @return void
     */
    public function run(Request $request, Response $response)
    {
        $this->_logger->info("Beginning process for request.", [$request]);

        $result = $this->_hooks->exec(
            "application.dispatch.before",
            $request,
            $response,
            $this
        );
        if ($result === "exit"
            || (is_array($result) && in_array("exit", $result))) {
            return;
        }

        // record the time before execution
        $start = microtime(true);

        // dispatch request
        try {
            $this->_router->dispatch($request, $response, $this);
        } catch (RouteNotFoundException $routeNotFound) {
            $this->_logger->error("No Route found for Request");
            $this->_logger->debug(
                "No Route Found Debug Information",
                ["exception" => $routeNotFound]
            );

            $response->setContent(
                "<h1>404 Not Found</h1>Requested page was not found.<h6>Default"
                . " SlaxWeb/Framework Message</h6>"
            );
            return;
        }

        $this->_logger->info(
            "Request has finished processing, Response is ready to be sent to "
            . "caller."
        );

        $this->_hooks->exec("application.dispatch.after");

        // record the time after execution
        $end = microtime(true);
        $this->_logger->debug(
            "Time taken to finish Request processing",
            [
                "start"     =>  $start,
                "end"       =>  $end,
                "elapsed"   =>  $end - $start
            ]
        );
    }
}
