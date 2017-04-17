# ChangeLog

Changes between version.

## Current changes

* add service provider for config component
* add service provider for hooks component
* add service provider for logger component
* add service provider for router component
* add exception for invalid config handler
* add exception for unknown logger handler
* add exception for logger config
* move route collection provider from router component
* add possibility to define route and route collection specific before and/or after
dispatch hooks
* set 404 status code on route not found exception
* re-throw the route not found exception to allow the output component to handle
it
* resources like hooks, routes, and providers are now loaded from a single method
to help improve performance
* add component command
* download and install composer if not found in PATH

## v0.5

### v0.5.0

* set base URL and base path to application properties
* add base controller abstract class for simplified instantiation of controller
classes
* add controller loader service to application services

## v0.4

### v0.4.1

* use the prepared base url(with guaranteed trailing slash) while preparing the
request

### v0.4.0

* logger no longer instantiated through container service, but rather through
the container protected function
* add configuration directory sub-directories to configuration resource
locations, and load all configuration files from those sub-directories
* set request server name to the same value as in configuration, if set

## v0.3

### v0.3.1

* request with resource name prepended configuration items

### v0.3.0

* initial version
