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

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Application instance
     *
     * @var \SlaxWeb\Bootstrap\Application
     */
    protected $_app = null;

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
     * Prepare the test
     *
     * Initialize the Application class and set it to the protected property
     * '_app'. Also prepare the required components, Logger, Hooks, Router.
     *
     * @return void
     */
    protected function setUp()
    {
        // instantiate the Application class
        $this->_app = new Application;

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
        $this->_logger->expects($this->once())
            ->method("info");

        $this->_hooks->expects($this->once())
            ->method("exec")
            ->with("application.init.after");

        $this->_app->init($this->_router, $this->_hooks, $this->_logger);
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
        $deps = $this->_getRunDependencies();

        $this->_router->expects($this->once())
            ->method("dispatch")
            ->with($deps["request"], $deps["response"], $this->_app);

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
                    $this->_app
                ], ["application.dispatch.after"]
            );

        $this->_app->init($this->_router, $this->_hooks, $this->_logger);
        $this->_app->run($deps["request"], $deps["response"]);
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
        $deps = $this->_getRunDependencies();

        $this->_hooks->expects($this->exactly(3))
            ->method("exec")
            ->will(
                $this->onConsecutiveCalls(null, "exit", [0, "exit", 1])
            );

        $this->_router->expects($this->never())
            ->method("dispatch");

        $this->_app->init($this->_router, $this->_hooks, $this->_logger);
        $this->_app->run($deps["request"], $deps["response"]);
        $this->_app->run($deps["request"], $deps["response"]);
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
