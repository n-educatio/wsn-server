WebSocket Notification Server
==========
![alt travis](https://travis-ci.org/n-educatio/wsn-server.svg?branch=master)

Built on top of [Rachet](http://socketo.me), [ZMQ](http://zeromq.org) library and [Symfony2](http://symfony.com) components, based on [push integration](http://socketo.me/docs/push) tutorial found in Ratchet [docs](http://socketo.me/docs)

#### Requirements
It requires ZMQ PHP extension to be [installed](http://zeromq.org/bindings:php) (you can install it via [PECL](http://pecl.php.net/package/zmq) or clone [sources](https://github.com/mkoppanen/php-zmq) and compile on your own). Other dependencies are handled by composer.


##### Install it via composer
 ```
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/n-educatio/wsn-server.git"
    }
  ],
  "require": {
    "neducatio/wsn-server": "dev-master"
  }
}
```

### Usage
##### Run server
```
php bin/wsn-server.php help neducatio:wsn-server:run
```
##### Subscribe a channel (topic)
Check out [push integration](http://socketo.me/docs/push#client) tutoral.

##### Push from your web app
Check out [push integration](http://socketo.me/docs/push#editblogsubmission) tutorial. For Symfony2 integration use [wsn-server-bundle](https://github.com/n-educatio/wsn-server-bundle).

### Configuration
It uses [Symfony2 DependencyInjection](http://symfony.com/doc/current/components/dependency_injection/introduction.html) component as DI container. Configuration is read from **config.yml** which can be found in project's root directory.
```
parameters:
    host: 127.0.0.1
    port: 5556
    websocket-port: 8080
    
services:
    logger:
        class: Neducatio\WebSocketNotification\Common\Logger
```

##### Params
**host** and **port** are bind to socket that is used for communication with your web app. Setting host as 127.0.0.1 means that only local web apps can push (that's desired when you keep your web app and wsn-server on the same machine). Setting it as 0.0.0.0 means that everyone can push to your wsn-server.

**websocket-port** port is bind to websocket. 


It is possible to provide those parameters in command line (that takes precedence over configuration file):
```
php bin/wsn-server.php help neducatio:wsn-server:run --port 5556 --host 127.0.0.1 --websocket-port 8080

```

##### Services
wsn-server comes with default **logger** that simply prints EVERYTHING to STDOUT. You can provide your own logger unless it implements [Neducatio\WebSocketNotification\Common\Loggable](https://github.com/n-educatio/wsn-server/blob/master/src/Neducatio/WebSocketNotification/Common/Loggable.php).
