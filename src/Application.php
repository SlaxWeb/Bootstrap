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
 * @version   0.4
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
        $this["pubDir"] = realpath($pubDir) . DIRECTORY_SEPARATOR;
        $this["appDir"] = realpath($appDir) . DIRECTORY_SEPARATOR;
        $this["configHandler"] = ConfigContainer::PHP_CONFIG_HANDLER;
        $this["configResourceLocation"] = "{$this["appDir"]}Config"
            . DIRECTORY_SEPARATOR;

        parent::__construct();

        // register config provider and load config
        $this->register(new \SlaxWeb\Bootstrap\Service\ConfigProvider);
        $this->loadConfig($this["configResourceLocation"]);
    }

    /**
     * Application Initialization
     *
     * Initialize the Application class by loading providers and routesfrom their
     * respective locations.
     *
     * @return void
     */
    public function init()
    {
        $this->loadResources();
        $this->prepRequestData();

        // initialize the output component by loading it, the component will register
        // handlers on construction
        $this["output.service"];

        $this["logger.service"]("System")->info("Application initialized");

        $this["hooks.service"]->setParams([$this]);
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

        $this->setRequestProperties($request);

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

            $response->setStatusCode(404);
            $response->setContent($this->load404Page());
            throw $routeNotFound;
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
                "elapsed"   =>  $end - $start,
                "uri"       =>  $request->getRequestUri()
            ]
        );
    }

    /**
     * Load resources
     *
     * Load the resource class names from the configuration, and register them with
     * the service provider.
     *
     * @return void
     */
    protected function loadResources()
    {
        $config = $this["config.service"];
        foreach (["hooks", "routes", "provider"] as $type) {
            // remnant of an old bad decission
            $confName = $type === "provider" ? "register" : "load";
            if (($config["provider.{$type}.{$confName}"] ?? false) === false) {
                continue;
            }
            if (isset($config["provider.{$type}List"]) === false
                || is_array($config["provider.{$type}List"]) === false
            ) {
                continue;
            }

            foreach ($config["provider.{$type}List"] as $class) {
                $this->register(new $class);
            }
        }
    }

    /**
     * Load configuration files
     *
     * Scan the configuration resource location directory and load all found
     * PHP files with the Config component, recursively.
     *
     * @param string $dir Directory from which the configuration files are loaded
     * @param bool $prepend Should configuration item names be prependedi with the
     *                      names of configuration file names
     * @return void
     */
    protected function loadConfig(string $dir, bool $prepend = true)
    {
        foreach (scandir($dir) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === "php") {
                $this["config.service"]->load($file, $prepend);
            } elseif (is_dir("{$dir}/{$file}") && ($file !== "." && $file !== "..")) {
                $this["config.service"]->addResDir("{$dir}/{$file}");
                $this->loadConfig("{$dir}/{$file}", false);
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
    protected function prepRequestData()
    {
        if (empty($this["config.service"]["app.baseUrl"])) {
            return;
        }

        $uri = parse_url($_SERVER["REQUEST_URI"] ?? "");
        $uri = $uri["path"] === "/" ? "" : $uri["path"];
        $query = isset($_SERVER["QUERY_STRING"]) ? "?{$_SERVER["QUERY_STRING"]}" : "";
        $baseUrl = rtrim("/", $this["config.service"]["app.baseUrl"]) . "/";

        $this["requestParams"] = [
            "uri"       =>  $baseUrl . $uri . $query,
            "method"    =>  $_SERVER["REQUEST_METHOD"] ?? "GET"
        ];
    }

    /**
     * Set application properties
     *
     * Sets some basic request properties for the application.
     *
     * @param \SlaxWeb\Route\Request $request Received Request
     * @return void
     */
    protected function setRequestProperties(Request $request)
    {
        $this["basePath"] = $request->getBasePath();
        $this["baseUrl"] = $request->getSchemeAndHttpHost() . $this["basePath"];
    }

    /**
     * Load Route Not Found Page
     *
     * Loads the 404 page and returns its contents.
     *
     * @return string
     */
    protected function load404Page(): string
    {
        ob_start();
        require __DIR__ . "/../resources/404.html";
        $errorHtml = ob_get_contents();
        ob_end_clean();

        return $errorHtml;
    }
}
