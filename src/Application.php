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
use SlaxWeb\Config\Container as ConfigContainer;
use SlaxWeb\Router\Dispatcher as RouteDispatcher;
use SlaxWeb\Router\Exception\RouteNotFoundException;

class Application extends \Pimple\Container
{
    /**
     * Constructor
     *
     * Sets application properties. Retrieves the public directory and
     * application directiories as input.
     *
     * @param string $pubDir Public directory path
     * @param string $appDir Application directory path
     */
    public function __construct(string $pubDir, string $appDir)
    {
        $this["pubDir"] = rtrim($pubDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR;
        $this["appDir"] = rtrim($appDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR;
        $this["configHandler"] = ConfigContainer::PHP_CONFIG_HANDLER;
        $this["configResourceLocation"] = "{$this["appDir"]}Config"
            . DIRECTORY_SEPARATOR;

        parent::__construct();
    }

    /**
     * Application Initialization
     *
     * Initialize the Application class by loading providers and configuration
     * files from their respective locations.
     *
     * @return void
     */
    public function init()
    {
        $this->_loadConfig($this["configResourceLocation"]);
        $this->_loadHooks();
        $this->_loadRoutes();
        $this->_registerProviders();
        $this->_prepRequestData();

        $this["logger.service"]("System")->info("Application initialized");

        $this["hooks.service"]->exec("application.init.after");
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
        $this["logger.service"]("System")->info("Beginning process for request.", [$request]);

        $result = $this["hooks.service"]->exec(
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
            $this["routeDispatcher.service"]->dispatch($request, $response, $this);
        } catch (RouteNotFoundException $routeNotFound) {
            $this["logger.service"]("System")->error("No Route found for Request");
            $this["logger.service"]("System")->debug(
                "No Route Found Debug Information",
                ["exception" => $routeNotFound]
            );

            $response->setContent($this->_load404Page());
            return;
        }

        $this["logger.service"]("System")->info(
            "Request has finished processing, Response is ready to be sent to "
            . "caller."
        );

        $this["hooks.service"]->exec("application.dispatch.after");

        // record the time after execution
        $end = microtime(true);
        $this["logger.service"]("System")->debug(
            "Time taken to finish Request processing",
            [
                "start"     =>  $start,
                "end"       =>  $end,
                "elapsed"   =>  $end - $start
            ]
        );
    }

    /**
     * Register providers
     *
     * Check with the Configuration if the Application should register
     * additional providers. If so register them with the DIC.
     *
     * @return void
     */
    protected function _registerProviders()
    {
        // check config exists
        if (($this["config.service"]["app.provider.register"] ?? false)
            === false) {
            return;
        }
        if (isset($this["config.service"]["app.providerList"]) === false
            || is_array($this["config.service"]["app.providerList"])
            === false) {
            return;
        }

        foreach ($this["config.service"]["app.providerList"] as $providerClass) {
            $this->register(new $providerClass);
        }
    }

    /**
     * Load Hook Definitions
     *
     * Check with the Configuration if the Application should load hook
     * definitions. If so register their providers with the DIC.
     *
     * @return void
     */
    protected function _loadHooks()
    {
        // check config exists
        if (($this["config.service"]["app.hooks.load"] ?? false)
            === false) {
            return;
        }
        if (isset($this["config.service"]["app.hooksList"]) === false
            || is_array($this["config.service"]["app.hooksList"])
            === false) {
            return;
        }

        foreach ($this["config.service"]["app.hooksList"] as $hookClass) {
            $this->register(new $hookClass);
        }
    }

    /**
     * Load Routes
     *
     * Check configuration if the Application should load routes with the help
     * of Route Collections. If so register all their providers that are defined
     * in the configuration with the DIC.
     *
     * @return void
     */
    protected function _loadRoutes()
    {
        // check config exists
        if (($this["config.service"]["app.routes.load"] ?? false)
            === false) {
            return;
        }
        if (isset($this["config.service"]["app.routesList"]) === false
            || is_array($this["config.service"]["app.routesList"])
            === false) {
            return;
        }

        foreach ($this["config.service"]["app.routesList"] as $routeClass) {
            $this->register(new $routeClass);
        }
    }

    /**
     * Load configuration files
     *
     * Scan the configuration resource location directory and load all found
     * PHP files with the Config component, recursively.
     *
     * @param string $dir Directory from which the configuration files are loaded
     * @param bool $prependNames Should configuration item names be prepended
     *                           with the names of configuration file names
     * @return void
     */
    protected function _loadConfig(string $dir, bool $prepend = true)
    {
        foreach (scandir($dir) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === "php") {
                $this["config.service"]->load($file, $prepend);
            } elseif (is_dir("{$dir}/{$file}") && ($file !== "." && $file !== "..")) {
                $this["config.service"]->addResDir("{$dir}/{$file}");
                $this->_loadConfig("{$dir}/{$file}", false);
            }
        }
    }

    /**
     * Prepare request data
     *
     * Prepares the request URL if the config has a predefined base url set. If
     * this is not set, then the request data is not set, and the Request class
     * will do its best to guess its data. If the base url is set, then the HTTP
     * method and the request URI have to be read from the $_SERVER superglobal.
     *
     * @return void
     */
    protected function _prepRequestData()
    {
        if (empty($this["config.service"]["app.baseUrl"])) {
            return;
        }

        $uri = parse_url($_SERVER["REQUEST_URI"]);
        $uri = $uri["path"] === "/" ? "" : $uri["path"];
        $query = isset($_SERVER["QUERY_STRING"]) ? "?{$_SERVER["QUERY_STRING"]}" : "";
        $baseUrl = rtrim("/", $this["config.service"]["app.baseUrl"]) . "/";

        $this["requestParams"] = [
            "uri"       =>  "{$this["config.service"]["app.baseUrl"]}{$uri}{$query}",
            "method"    =>  $_SERVER["REQUEST_METHOD"]
        ];
    }

    /**
     * Load Route Not Found Page
     *
     * Loads the 404 page and returns its contents.
     *
     * @return string
     */
    protected function _load404Page(): string
    {
        ob_start();
        require __DIR__ . "/../resources/404.html";
        $errorHtml = ob_get_contents();
        ob_end_clean();

        return $errorHtml;
    }
}
