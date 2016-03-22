# Loggly Helpers

### Setup with Laravel

##### Config

Add the following to `config/app.php`:

```
    'loggly-token' => env('LOGGLY_TOKEN'),
```

and add your loggly token to your .env file. Ommitting this will mean that the loggly monlog handler will not be loaded (and nothing will be sent to loggly) - basically, it's safe to not have this key when developing locally.

##### Provider

Add the following file, as `LogglyServiceProvider.php`, and put it in your application's service providers config in `config/app.php`.

Update the `$name` property with your application's name.

```
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
