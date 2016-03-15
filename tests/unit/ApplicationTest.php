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
        $this->_logger = $this->getMock("\\Psr\\Log\LoggerInterface");

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
    }

    protected function tearDown()
    {
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
            ->setMethods(["_registerProviders"])
            ->getMock();

        $this->_logger->expects($this->once())
            ->method("info");

        $this->_hooks->expects($this->once())
            ->method("exec")
            ->with("application.init.after");

        $app->expects($this->once())
            ->method("_registerProviders");

        $app->init(
            $this->_config,
            $this->_router,
            $this->_hooks,
            $this->_logger
        );
    }

    /**
     * Test Provider Registration
     *
     * Ensure that the application is registering the providers that the config
     * dictates.
     *
     * @return void
     */
    public function testProviderRegistration()
    {
        $app = $this->getMockBuilder("\\SlaxWeb\\Bootstrap\\Application")
            ->setMethods(["register"])
            ->getMock();

        $this->_config->expects($this->exactly(3))
            ->method("offsetExists")
            ->will($this->onConsecutiveCalls(true, true, true));

        $this->_config->expects($this->exactly(4))
            ->method("offsetGet")
            ->withConsecutive(
                ["application.provider.register"],
                ["application.providerList"]
            )->will($this->onConsecutiveCalls(
                true,
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],
                ["\\SlaxWeb\\Bootstrap\\Tests\\Helper\\Provider"],
                false
            ));

        $app->expects($this->once())
            ->method("register")
            ->with($this->callback(function ($class) {
                return $class instanceof TestProvider;
            }));

        $app->init(
            $this->_config,
            $this->_router,
            $this->_hooks,
            $this->_logger
        );
        $app->init(
            $this->_config,
            $this->_router,
            $this->_hooks,
            $this->_logger
        );
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
            ->setMethods(["_registerProviders"])
            ->getMock();

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

        $app->init(
            $this->_config,
            $this->_router,
            $this->_hooks,
            $this->_logger
        );
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
            ->setMethods(["_registerProviders"])
            ->getMock();

        $deps = $this->_getRunDependencies();

        $this->_hooks->expects($this->exactly(3))
            ->method("exec")
            ->will(
                $this->onConsecutiveCalls(null, "exit", [0, "exit", 1])
            );

        $this->_router->expects($this->never())
            ->method("dispatch");

        $app->init(
            $this->_config,
            $this->_router,
            $this->_hooks,
            $this->_logger
        );
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
            ->setMethods(["_registerProviders"])
            ->getMock();

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

        $app->init(
            $this->_config,
            $this->_router,
            $this->_hooks,
            $this->_logger
        );
        $app->run($deps["request"], $deps["response"]);
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
            "\\Symfony\\Component\\HttpFoundation\\Response"
        )->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        return ["request" => $request, "response" => $response];
    }
}
