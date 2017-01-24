# ChangeLog

Changes between version.

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
