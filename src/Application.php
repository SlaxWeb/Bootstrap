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
        // are we running on windows?
        $dirSep = strtoupper(substr(PHP_OS, 0, 3)) === "WIN" ? "\\" : "/";

        $this["pubDir"] = rtrim($pubDir, $dirSep) . $dirSep;
        $this["appDir"] = rtrim($appnDir, $dirSep) . $dirSep;
        $this["configHandler"] = ConfigContainer::PHP_CONFIG_HANDLER;
        $this["configResourceLocation"] = "{$this["appDir"]}Config{$dirSep}";

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
        $this->_loadConfig();
        $this->_registerProviders();

        $this["logger.service"]->info("Application initialized");

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
        $this["logger.service"]->info("Beginning process for request.", [$request]);

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
            $this["logger.service"]->error("No Route found for Request");
            $this["logger.service"]->debug(
                "No Route Found Debug Information",
                ["exception" => $routeNotFound]
            );

            $response->setContent($this->_load404Page());
            return;
        }

        $this["logger.service"]->info(
            "Request has finished processing, Response is ready to be sent to "
            . "caller."
        );

        $this["hooks.service"]->exec("application.dispatch.after");

        // record the time after execution
        $end = microtime(true);
        $this["logger.service"]->debug(
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
     * Load configuration files
     *
     * Scan the configuration resource location directory and load all found
     * PHP files with the Config component.
     *
     * @return void
     */
    protected function _loadConfig()
    {
        foreach (scandir($this["configResourceLocation"]) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === "php") {
                $this["config.service"]->load($file);
            }
        }
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
        require_once __DIR__ . "/../resources/404.html";
        $errorHtml = ob_get_contents();
        ob_end_clean();

        return $errorHtml;
    }
}
