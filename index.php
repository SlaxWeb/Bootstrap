<?php
/**
 * Set up some global constants
 */
// Application directory
$appDir = "app";
// Public directory
$pubDir = "public";
// Vendor directory
$venDir = "vendor";

// Define FCPATH constant pointing to Front Controller (this file)
define("FCPATH", dirname(__FILE__) . "/");
// Define APPPATH constant pointing to Application directory
define("APPPATH", FCPATH . "../{$appDir}/");
// define PUBPATH constant pointing to Public directory
define("PUBPATH", FCPATH . "../{$pubDir}/");
// define VENPATH constant pointing to the Vendor directory
define("VENPATH", FCPATH . "../{$venDir}/");
// define TEMPLATEPATH constant pointing to the Templates directory
define("TEMPLATEPATH", APPPATH . "/Template/View");
// define TWIGCACHE constant pointing to the Twig Cache directory
define("TWIGCACHE", APPPATH . "/Template/Cache");

/**
 * Load the config
 */
require_once APPPATH . "config/config.php";

/**
 * Load the system Composer Autoloader
 */
$loader = require_once FCPATH . "../vendor/autoload.php";

/**
 * Parse the request
 */
$uri = "";
$method = "";
// check if request is from CLI
if (php_sapi_name() === "cli") {
    array_shift($argv);
    $uri = implode("/", $argv);
    $method = "CLI";
} else {
    // normal WEB request
    $uri = $_SERVER["REQUEST_URI"];
    $method = $_SERVER["REQUEST_METHOD"];
}
$routerParams = [
    "uri"       =>  $uri,
    "method"    =>  $method
];

// add autoloader and router to Registry
\SlaxWeb\Registry\Container::addAlias("loader", $loader);
\SlaxWeb\Registry\Container::setAlias("router", "\\SlaxWeb\\Router\\Router", $routerParams);

// load the hooks
require_once APPPATH . "config/hooks.php";

/**
 * Lets begin. Load and start the bootstraper
 */
$swf = new \SlaxWeb\Bootstrap\Swf($loader, \SlaxWeb\Registry\Container::get("router"));
// Add some stuff to the Autoloader
$swf->configureAutoload();

// Route the request
$swf->routeRequest();
