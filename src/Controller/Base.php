<?php
namespace SlaxWeb\Bootstrap\Controller;

/**
 * Base Controller
 *
 * The SlaxWeb Framework Base Controller helps with simplifying controller loading
 * by providing a constructor that will copy the Application object instance to
 * the protected properties, and set common services to them as well, like the Logger
 * and the Config container objects.
 *
 * @package   SlaxWeb\Bootstrap
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
abstract class Base
{
    /**
     * Application object
     *
     * @var \SlaxWeb\Bootstrap\Application
     */
    protected $app = null;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * Config container object
     *
     * @var \SlaxWeb\Config\Container
     */
    protected $config = null;

    /**
     * Class Constructor
     *
     * Copy the Application object instance to class properties, and extract Logger
     * and Config services from the Application object to class properties.
     *
     * @param \SlaxWeb\Bootstrap\Application $app Application object
     * @param \Psr\Log\LoggerInterface $logger Logger instance
     * @param \SlaxWeb\Config\Container $config Config container object
     */
    public function __construct()
    {
        $this->app = $app;
        $this->logger = $app["logger.service"]();
        $this->config = $app["config.service"];
    }
}
