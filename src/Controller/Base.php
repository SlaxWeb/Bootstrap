<?php
namespace SlaxWeb\Bootstrap\Controller;

use SlaxWeb\Bootstrap\Application;

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
     * Class Constructor
     *
     * Copy the Application object instance to class properties, and extract Logger
     * and Config services from the Application object to class properties.
     *
     * @param \SlaxWeb\Bootstrap\Application $app Application object
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
}
