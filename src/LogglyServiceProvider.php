<?php

namespace Golin\MonoLoggly;

use Golin\MonoLoggly\Processors\BacktraceLocation;
use Golin\MonoLoggly\Processors\ExceptionInformation;
use Golin\MonoLoggly\Processors\RequestInformation;
use Illuminate\Log\Writer;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\LogglyHandler;
use Monolog\Logger;

class LogglyServiceProvider extends ServiceProvider {

    /**
     * The log name. This should uniquely identify the log
     *
     * @var string
     */
    protected $name;

    /**
     * The minimum log level
     *
     * @var int
     */
    protected $level = Logger::DEBUG;

    /**
     * @{inheritdoc}
     */
    public function boot()
    {
        $this->addLoggly(
            $this->app['log']->getMonolog()
        );
    }

    /**
     * @{inheritdoc}
     */
    public function register() {}

    /**
     * Get the loggly token
     *
     * @return string
     */
    protected function token()
    {
        return config('app.loggly-token');
    }

    /**
     * Add loggly to the given logger
     *
     * @param Monolog $monolog
     * @param string  $tag
     */
    public function addLoggly(Logger $monolog)
    {
        if ($this->token()) {
            $name = str_slug($this->name) . '-' . $this->app['env'];

            $monolog->pushHandler(
                $this->createHandler($name)
            );
        }
    }

    /**
     * Create the loggly handler
     *
     * @return LogglyHandler
     */
    protected function createHandler($name)
    {
        $handler = new LogglyHandler($this->token() . '/tag/' . $name, $this->level);

        // add a tag for the environment
        $handler->addTag($this->app['env']);

        $handler->pushProcessor(new ExceptionInformation);

        $handler->pushProcessor(new BacktraceLocation(Writer::class));

        $handler->pushProcessor(new RequestInformation(
            $this->app['env'],
            $this->app['request']->method(),
            $this->app['request']->fullUrl()
        ));

        foreach ($this->processors() as $processor) {
            $handler->pushProcessor($processor);
        }

        return $handler;
    }

    /**
     * A place to construct any other processors to add to loggly
     *
     * @return array   An array of callables objects
     */
    protected function processors()
    {
        return [];
    }

}
