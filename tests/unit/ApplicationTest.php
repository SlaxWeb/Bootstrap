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
namespace SlaxWeb\Router\Tests\Unit;

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
        $this->_hooks = $this->getMock("\\SlaxWeb\\Hooks\Container");

        // get router mock
        $this->_router = $this->getMock("\\SlaxxWeb\\Router\\Dispatcher");
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
            ->with("application.after.init");

        $this->_app->init($this->_router, $this->_hooks, $this->_logger);
    }
}
