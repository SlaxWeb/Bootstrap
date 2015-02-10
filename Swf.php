<?php
namespace \SlaxWeb\Bootstrap;

use \SlaxWeb\Hooks\Hooks;
use \SlaxWeb\Registry\Container as Registry;

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

        Hooks::call("bootstrap.after.autoload");
    }

    public function routeRequest()
    {
        Hooks::call("bootstrap.before.route");
        require_once APPPATH . "config/routes.php";

        $route = $this->_router->process();

        Hooks::call("bootstrap.before.controller");
        if (is_object($route["action"]) && $route["action"] instanceof \Closure) {
            call_user_func_array($route["action"], $route["params"]);
        } else {
            $controller = Registry::setAlias("controller", "\\Controller\\{$route["action"][0]}");
            $controller->{$route["action"][1]}(...$route["params"]);
        }

        Hooks::call("bootstrap.after.route");
    }
}