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

use Psr\Log\LoggerInterface;
use SlaxWeb\Hooks\Container as HooksContainer;
use SlaxWeb\Router\Dispatcher as RouteDispatcher;

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

        $this->_hooks->exec("application.after.init");
    }
}
