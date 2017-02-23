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
