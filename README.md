Smesg - providing wrappers for danish sms gateways.
===================================================

**Smesg** is a library to help you send [sms](http://en.wikipedia.org/wiki/SMS) messages via different providers.

Currently supported providers:

- [unwire](http://unwire.dk)
- [inmobile](http://www.inmobile.dk/)

So far the library only supports sending sms messages, querying for status, canceling and other services will be added.

Installation
------------

There will be a composer package once I get the time to build one, so for now just clone this repository.

`git clone https://github.com/mrbase/Smesg.git Smesg`


Usage
-----

First off, you need a contract with one of the provides for you to be able to send anything.

You really sould use a [psr-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant autoloader, it will make your day.

A simple one can be found in `tests/autoloader.php`.

Then you need to set the configure variables for the provider (explained in details below) and provide the transport adapter.

Currently only one transport adapter exists, but you can add your own by implementing the `Smesg\Adapter\AdapterInterface`.

Unwire example:

``` php
<?php

require __DIR__ . '/tests/autoloader.php';

$config = array(
    'user' => 'test',
    'password' => 'test....',
    'appnr' => '1212',
    'mediacode' => 'test',
    'price' => '0.00DK',
);

$transport = new Smesg\Adapter\PhpStreamAdapter();
$provider = new Smesg\Provider\UnwireProvider($transport, $config);

$provider->addMessage('mobile_number', 'message');
$response = $provider->send();
```

Note, the `mobile_number` _must_ be a valid [MSISDN](http://en.wikipedia.org/wiki/MSISDN) compliant number or the service will fail!


In-depth docs for the providers
-------------------------------


### inmobile:

The provider for inmobile implements the following services:

- smspush
- smspushxml
- smsclookup (you need a seperate agreement to have access to this service)

_smspush:_ is used to send single messages.

_smspushxml:_ is used when sending batch messages, note that you can only send 1024 messages in one batch, the provider does _not_ handle the split into seperate batches.

_smsclookup:_ is used to loockup serviceproviders for mobile numbers. implementing a cache for this is on the todo list.


Default configuration values:

``` php
<?php

$config = array(
    'user' => '',
    'password' => '',
    'appnr' => '',
    'mediacode' => '',
    'price' => '',
    'get_smsc' => false,
);
```

Please note that the price format uses period (.) as decimal devider.

Example: `0.00DKK` is the correct value if you do not tax the messages in Denmark.


Possible default values when sending batch messages:

``` php
$defaults = array(
    'operator' => null,
    'shortcode' => null,
    'mediacode' => null,
    'price' => array(
        'amount' => null,
        'currency' => null,
        'vat' => null,
    ),
    'content' => null,
);
```

If left alone the price array will be calculated from the value set in the configuration.

If you want to send the same message to a group of people you have the posibility to setup defaults for the messages.

Here is a simple example extending the demo above.

``` php
<?php
// ....
$provider = new Smesg\Provider\UnwireProvider($transport, $config);

$provider->setDefaults(array(
    'shortcode' => '1212',
    'mediacode' => 'test',
    'content' => 'this is the message send',
));

$provider->addMessage('mobile_number_1', null);
$provider->addMessage('mobile_number_2', null);
$provider->addMessage('mobile_number_3', null);
$response = $provider->send();
```

[![Build Status](https://secure.travis-ci.org/mrbase/Smesg.png?branch=master)](http://travis-ci.org/mrbase/Smesg)