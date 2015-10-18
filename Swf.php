<?php
namespace SlaxWeb\Bootstrap;

use SlaxWeb\Hooks\Hooks;
use SlaxWeb\Registry\Container as Registry;
use SlaxWeb\Router\Exceptions\RouteNotFoundException;

class Swf
{
    /**
     * Composer Autoloader
     *
     * @var object
     */
    protected $_loader = null;
    /**
     * Router
     *
     * @var \SlaxWeb\Router\Router
     */
    protected $_router = null;

    public function __construct(\Composer\Autoload\ClassLoader $loader, \SlaxWeb\Router\Router $router)
    {
        $this->_loader = $loader;
        $this->_router = $router;

        Hooks::call("bootstrap.after.construct");
    }

    public function configureAutoload()
    {
        $this->_loader->add("Controller\\", APPPATH);
        $this->_loader->add("Model\\", APPPATH);
        $this->_loader->add("View\\", APPPATH);
        $this->_loader->add("Hooks\\", APPPATH);
        $this->_loader->add("Library\\", APPPATH);

        Hooks::call("bootstrap.after.autoload");
    }

    public function routeRequest()
    {
        Hooks::call("bootstrap.before.route");
        require_once APPPATH . "config/routes.php";

        try {
            $route = $this->_router->process();
        } catch (RouteNotFoundException $e) {
            if (Hooks::call("bootstrap.before.noroute") === true) {
                return;
            }
            if (function_exists("show404")) {
                call_user_func_array("show404", [$e->getRequest()]);
            } else {
                echo "No route found for following request: {$e->getRequest()}";
            }
            return;
        }

        if (Hooks::call("bootstrap.before.controller", $route["action"]) === true) {
            return;
        }

        if (is_object($route["action"]) && $route["action"] instanceof \Closure) {
            call_user_func_array($route["action"], $route["params"]);
        } else {
            $controller = Registry::setAlias("controller", "{$route["action"][0]}");
            $controller->{$route["action"][1]}(...$route["params"]);
        }

        Hooks::call("bootstrap.after.route");
    }
}
