# Loggly Helpers

This extends the basic loggly handler provided by monolog, adding the following information as context:
- the environment
- whether the log happened on a request, or CLI call
- information about the http request (url, method)
- information about the origin of the log call (file and line, class and method (if relevant))
- some simplified exception information (if the log comes from a "normal" exception log - ie. logging, or `__toString`-ing the exception)

Note: there is currently a bug with the class and method origin / backtrace information.

### Installation

```bash
composer require golin/monologgly
```

### Setup with Laravel

##### Config

Add the following to `config/app.php`:

```php
    'loggly-token' => env('LOGGLY_TOKEN'),
```

and add your loggly token to your .env file. Ommitting this will mean that the loggly monlog handler will not be loaded (and nothing will be sent to loggly) - basically, it's safe to not have this key when developing locally.

##### Provider

Add the following file, as `LogglyServiceProvider.php`, and put it in your application's service providers config in `config/app.php`.

Update the `$name` property with your application's name.

```php
<?php

namespace App\Providers;

use Golin\MonoLoggly\LogglyServiceProvider as BaseProvider;
use Monolog\Monolog;

class LogglyServiceProvider extends BaseProvider {

    /**
     * The log name. This should uniquely identify the log.
     *
     * @var string
     */
    protected $name;

    /**
     * The minimum log level.
     *
     * @var int
     */
    protected $level = Monolog::DEBUG;

    /**
     * A place to construct any other processors that will be added to 
     * the loggly handler.
     *
     * @return array   An array of callables objects
     */
    protected function processors()
    {
        return [];
    }

}

```
