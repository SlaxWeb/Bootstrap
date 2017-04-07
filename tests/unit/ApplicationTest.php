<?php
/**
 * Application class Tests
 *
 * Tests for the Container class of the Router component. The Container needs to
 * store retrieved Route definitions in an internal protected property, and
 * provide a way to retrieve those definitions. This test ensures that this
 * functionality works as intentended.
 *
 * @package   SlaxWeb\Bootstrap
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Bootstrap\Tests\Unit;

use SlaxWeb\Bootstrap\Application;
use SlaxWeb\Bootstrap\Tests\Helper\Provider as TestProvider;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Logger mock
     *
     * @var mocked object
     */
    protected $_logger = null;

    /**
     * Hooks mock
     *
     * @var mocked object
     */
    protected $_hooks = null;

    /**
     * Router mock
     *
     * @var mocked object
     */
    protected $_router = null;

    /**
     * Config mock
     *
     * @var mocked object
     */

    protected $_config = null;

    /**
     * Prepare the test
     *
     * Prepare the required components, Config, Logger, Hooks, Router.
     *
     * @return void
     */
    protected function setUp()
    {
        // get logger mock
        $this->_logger = $this->createMock("\\Psr\\Log\LoggerInterface");

        // get hooks mock
        $this->_hooks = $this->getMockBuilder("\\SlaxWeb\\Hooks\Container")
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        // get router mock
        $this->_router = $this->getMockBuilder("\\SlaxWeb\\Router\\Dispatcher")
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        // get config mock
        $this->_config = $this->getMockBuilder("\\SlaxWeb\\Config\\Container")
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        // create temporary config directory and file for tests
        mkdir(__DIR__ . "/Config");
        file_put_contents(__DIR__ . "/Config/test.php", "content");
    }

    /**
     * Tear down test
     *
     * Unlink the configuration file and directory that were created in 'setUp'
     * method.
     *
     * @return void
     */
    protected function tearDown()
    {
        unlink(__DIR__ . "/Config/test.php");
        rmdir(__DIR__ . "/Config/");
    }

    /**
     * Test initialization
     *
     * Ensure that the initialization logs and executes appropriate hooks.
     *
     * @return void
     */
    public function testInit()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["loadConfig", "register"])
            ->getMock();
        $app->__construct(__DIR__, __DIR__);

        $this->_logger->expects($this->once())
            ->method("info");

        $this->_hooks->expects($this->once())
            ->method("exec")
            ->with("application.init.after");

        $app["output.service"] = null;
        $app["config.service"] = $this->_config;
        $app["routeDispatcher.service"] = $this->_router;
        $app["hooks.service"] = $this->_hooks;
        $app["logger.service"] = $app->protect(function () { return $this->_logger; });

        $app->init();

        $this->assertTrue(isset($app["pubDir"]));
        $this->assertTrue(isset($app["appDir"]));
        $this->assertTrue(isset($app["configHandler"]));
        $this->assertTrue(isset($app["configResourceLocation"]));
    }

    /**
     * Test Resource Loading
     *
     * Tests the resource loading and ensures the correct methods are called in
     * the config object to get correct data as well as that correct classes are
     * registered with the service provider.
     *
     * @return void
     */
    public function testResourceLoading()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["register", "prepRequestData", "loadConfig"])
            ->getMock();

        $this->_config->expects($this->any())
            ->method("offsetExists")
            ->willReturn(true);

        $this->_config->expects($this->exactly(9))
            ->method("offsetGet")
            ->withConsecutive(
                ["provider.hooks.load"],
                ["provider.hooksList"],
                ["provider.hooksList"],

                ["provider.routes.load"],
                ["provider.routesList"],
                ["provider.routesList"],

                ["provider.provider.register"],
                ["provider.providerList"],
                ["provider.providerList"]
            )->will($this->onConsecutiveCalls(
                true,
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],

                true,
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],

                true,
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"]
            ));

        $app->expects($this->exactly(4))
            ->method("register")
            ->withConsecutive(
                [new \SlaxWeb\Bootstrap\Service\ConfigProvider],
                [
                    $this->callback(function ($class) {
                        return $class instanceof TestProvider;
                    })
                ]
            );

        $app->__construct(__DIR__, __DIR__);

        $app["output.service"] = null;
        $app["config.service"] = $this->_config;
        $app["routeDispatcher.service"] = $this->_router;
        $app["hooks.service"] = $this->_hooks;
        $app["logger.service"] = $app->protect(function () { return $this->_logger; });

        $app->init();
    }

    /**
     * Test load config
     *
     * Ensure that the Application class loads all config files it finds in the
     * configuration resource location.
     *
     * @return void
     */
    public function testLoadConfig()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["register"])
            ->getMock();
        $expects = 0;
        foreach (scandir(__DIR__ . "/Config/") as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === "php") {
                $expects++;
            }
        }
        $this->_config->expects($this->exactly($expects))
            ->method("load");

        $app["config.service"] = $this->_config;

        $app->__construct(__DIR__, __DIR__);
    }

    /**
     * Test dispatch request
     *
     * Ensure that the application is properly executed, with 'run' method, by
     * sending the request to the route dispatcher.
     *
     * @return void
     */
    public function testDispatchRequest()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["register", "loadConfig"])
            ->getMock();
        $app->__construct(__DIR__, __DIR__);

        $deps = $this->_getRunDependencies();

        $this->_router->expects($this->once())
            ->method("dispatch")
            ->with($deps["request"], $deps["response"], $app);

        $this->_logger->expects($this->exactly(3))
            ->method("info");

        $this->_logger->expects($this->once())
            ->method("debug");

        $this->_hooks->expects($this->exactly(3))
            ->method("exec")
            ->withConsecutive(
                ["application.init.after"],
                [
                    "application.dispatch.before",
                    $deps["request"],
                    $deps["response"],
                    $app
                ], ["application.dispatch.after"]
            );

        $app["output.service"] = null;
        $app["config.service"] = $this->_config;
        $app["routeDispatcher.service"] = $this->_router;
        $app["hooks.service"] = $this->_hooks;
        $app["logger.service"] = $app->protect(function () { return $this->_logger; });

        $app->init();
        $app->run($deps["request"], $deps["response"]);
    }

    /**
     * Test run interuption
     *
     * Ensure that the 'application,dispatch.before' hook can terminate further
     * execution of the 'run' method.
     *
     * @return false
     */
    public function testRunInterupt()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["register", "loadConfig"])
            ->getMock();
        $app->__construct(__DIR__, __DIR__);

        $deps = $this->_getRunDependencies();

        $this->_hooks->expects($this->exactly(3))
            ->method("exec")
            ->will(
                $this->onConsecutiveCalls(null, "exit", [0, "exit", 1])
            );

        $this->_router->expects($this->never())
            ->method("dispatch");

        $app["output.service"] = null;
        $app["config.service"] = $this->_config;
        $app["routeDispatcher.service"] = $this->_router;
        $app["hooks.service"] = $this->_hooks;
        $app["logger.service"] = $app->protect(function () { return $this->_logger; });

        $app->init();
        $app->run($deps["request"], $deps["response"]);
        $app->run($deps["request"], $deps["response"]);
    }

    /**
     * Test 404 handling
     *
     * Ensure the 'run' method handles the thrown 404 exception by assigning a
     * default error message to the response body.
     *
     * @return void
     */
    public function test404Handling()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["register", "loadConfig"])
            ->getMock();
        $app->__construct(__DIR__, __DIR__);

        $deps = $this->_getRunDependencies();

        $this->_router->expects($this->once())
            ->method("dispatch")
            ->will(
                $this->throwException(
                    new \SlaxWeb\Router\Exception\RouteNotFoundException
                )
            );

        $deps["response"]->expects($this->once())
            ->method("setContent");

        $app["output.service"] = null;
        $app["config.service"] = $this->_config;
        $app["routeDispatcher.service"] = $this->_router;
        $app["hooks.service"] = $this->_hooks;
        $app["logger.service"] = $app->protect(function () { return $this->_logger; });

        $app->init();
        $exception = false;
        try {
            $app->run($deps["request"], $deps["response"]);
        } catch (\SlaxWeb\Router\Exception\RouteNotFoundException $e) {
            $exception = true;
        }
        $this->assertTrue($exception, "A route not found exception was expected but not thrown");
    }

    /**
     * Test Url Override
     *
     * Ensure that the Application will set a custom URL if the configuration
     * service provides a base url set in the configuration.
     *
     * @return void
     */
    public function testUrlOverride()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->disableOriginalConstructor()
            ->setMethods(["loadConfig", "loadResources"])
            ->getMock();

        $this->_config->expects($this->any())
            ->method("offsetExists")
            ->willReturn(true);

        $this->_config->expects($this->exactly(4))
            ->method("offsetGet")
            ->withConsecutive(
                ["app.baseUrl"]
            )->will($this->onConsecutiveCalls(
                true,
                "http://test.url:8000/?foo=bar",
                "http://test.url:8000/?foo=bar",
                false
            ));

        $app->__construct(__DIR__, __DIR__);

        $app["output.service"] = null;
        $app["config.service"] = $this->_config;
        $app["hooks.service"] = $this->_hooks;
        $app["logger.service"] = $app->protect(function () { return $this->_logger; });

        $app->init();
        $app->init();
    }

    /**
    * Get Run Dependensies
    *
    * Prepare all dependencies for the 'run' method testing, and return them
    * in an array.
    *
    * @return array
    */
    protected function _getRunDependencies(): array
    {
        $request = $this->getMockBuilder("\\SlaxWeb\\Router\\Request")
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $response = $this->getMockBuilder(
            "\\SlaxWeb\\Router\\Response"
        )->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        return ["request" => $request, "response" => $response];
    }
}
