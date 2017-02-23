<?php
namespace SlaxWeb\Bootstrap\Service;

use Pimple\Container;

/**
 * Config Provider
 *
 * Register the correct config handler based on the container property
 * 'configHandler', and the Config service itself.
 *
 * @package   SlaxWeb\Config
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.2
 */
class Provider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register provider
     *
     * Register is called by the container, when the provider gets registered.
     *
     * @param \Pimple\Container $container Dependency Injection Container
     * @return void
     */
    public function register(Container $container)
    {
        $container["config.service"] = function(Container $cont) {
            return new \SlaxWeb\Config\Container(
                $cont["configHandler.service"]
            );
        };

        $container["configHandler.service"] = function(Container $cont) {
            switch ($cont["configHandler"]) {
                case \SlaxWeb\Config\Container::PHP_CONFIG_HANDLER:
                    return new \SlaxWeb\Config\PhpHandler(
                        [$cont["configResourceLocation"]]
                    );
                case \SlaxWeb\Config\Container::XML_CONFIG_HANDLER:
                    return new \SlaxWeb\Config\XmlHandler(
                        [$cont["configResourceLocation"]],
                        new \Desperado\XmlBundle\Model\XmlReader
                    );
                case \SlaxWeb\Config\Container::YAML_CONFIG_HANDLER:
                    return new \SlaxWeb\Config\YamlHandler(
                        [$cont["configResourceLocation"]]
                    );
                default:
                    $availOpts = [
                        \SlaxWeb\Config\Container::PHP_CONFIG_HANDLER,
                        \SlaxWeb\Config\Container::XML_CONFIG_HANDLER,
                        \SlaxWeb\Config\Container::YAML_CONFIG_HANDLER
                    ];
                    throw new \SlaxWeb\Bootstrap\Exception\InvalidConfigHandlerException(
                        "Handler type property 'configHandler' must be one of "
                        . json_encode($availOpts)
                    );
            }
        };
    }
}
