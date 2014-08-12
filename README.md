WebSocket Notification Server
==========

Built on top of [Rachet](http://socketo.me), [ZMQ](http://zeromq.org) library and [Symfony2](http://symfony.com) components. This is based on [push integration](http://socketo.me/docs/push) tutorial found in Ratchet docs.

#### Requirements
It requires ZMQ PHP extension to be installed (you can install it via PECL or compile on your own). Other dependencies are handled by composer.


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
run 
```
php bin/wsn-server.php help neducatio:wsn-server:run
```
to find out about available command options.

##### Subscribe a channel (topic)
Checkout [push integration](http://socketo.me/docs/push) tutoral.

##### Push from your web app
Checkout [push integration](http://socketo.me/docs/push) tutorial. For Symfony2 integration use [wsn-server-bundle](https://github.com/n-educatio/wsn-server-bundle).

### Configuration
TODO
