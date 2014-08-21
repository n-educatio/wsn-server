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

