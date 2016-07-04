<?php

use Golin\MonoLoggly\Processors\BacktraceLocation;

class BacktraceCaller {
    public function __construct($processor, $logWrapper = null) {
        $this->processor = $processor;
        $this->logWrapper = $logWrapper;
    }

    public function foo() {
        return call_user_func($this->processor, []);
    }

    public function bar() {
        return $this->logWrapper->debug();
    }

    public function baz() {
        return mockLogWrapperFunction($this->processor);
    }
}

function mockLogLocationCallerFunction() {
    $processor = new BacktraceLocation;

    return call_user_func($processor, []);
}

/**
 * This is part of a fake log stack. It is the equivalent of Monolog\Logger
 */
class MockLogger {
    public function __construct($processor) {
        $this->processor = $processor;
    }

    public function debug() {
        return $this->log();
    }

    public function log() {
        return call_user_func($this->processor, []);
    }
}

/**
 * This is part of a fake log stack. It is the equivalent of
 * Illuminate\Log\Writer
 */
class MockLogWrapper {
    public function __construct($logger) {
        return $this->logger = $logger;
    }

    public function debug() {
        return $this->logger->debug();
    }
}

/**
 * This is part of a fake log stack. It is the equivalent of laravel's logger()
 * method
 */
function mockLogWrapperFunction($processor) {
    return call_user_func($processor, []);
}

class BacktraceLocationProcessorTest extends PHPUnit_Framework_TestCase
{
    public function testBasicBacktraceLocation()
    {
        $caller = new BacktraceCaller(new BacktraceLocation);

        $actual = $caller->foo(14)['context']['origin'];

        $expected = [
            'file' => [
                'file' => __FILE__,
                'line' => 12, // This should be the calling line inside BacktraceCaller@foo
            ],
            'class' => BacktraceCaller::class,
            'function' => 'foo',
            'signature' => 'BacktraceCaller@foo',
            'args' => ['integer'],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testBasicBacktraceLocationFromInsideFunction()
    {
        $actual = mockLogLocationCallerFunction()['context']['origin'];

        $expected = [
            'file' => [
                'file' => __FILE__,
                'line' => 27, // This should be the calling line inside mockLogLocationCallerFunction
            ],
            'class' => null,
            'function' => 'mockLogLocationCallerFunction',
            'signature' => 'mockLogLocationCallerFunction',
            'args' => [],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testNestedWrappedLoggerLocationInsideAFunction()
    {
        $caller = new BacktraceCaller(new BacktraceLocation(['mockLogWrapperFunction']));

        $actual = $caller->baz()['context']['origin'];

        $expected = [
            'file' => [
                'file' => __FILE__,
                'line' => 20, // This should be the calling line inside BacktraceCaller@baz
            ],
            'class' => BacktraceCaller::class,
            'function' => 'baz',
            'signature' => 'BacktraceCaller@baz',
            'args' => [],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testNestedWrappedLoggerLocation()
    {
        $processor = new BacktraceLocation([MockLogger::class, MockLogWrapper::class]);
        $logger = new MockLogger($processor);
        $wrapper = new MockLogWrapper($logger);
        $caller = new BacktraceCaller($processor, $wrapper);

        $actual = $caller->bar()['context']['origin'];

        $expected = [
            'file' => [
                'file' => __FILE__,
                'line' => 16, // This should be the calling line inside BacktraceCaller@bar
            ],
            'class' => BacktraceCaller::class,
            'function' => 'bar',
            'signature' => 'BacktraceCaller@bar',
            'args' => [],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testSkippingNestedWrappedLoggerLocation()
    {
        $processor = new BacktraceLocation([MockLogWrapper::class]);
        $logger = new MockLogger($processor);
        $wrapper = new MockLogWrapper($logger);
        $caller = new BacktraceCaller($processor, $wrapper);

        $actual = $caller->bar()['context']['origin'];

        $expected = [
            'file' => [
                'file' => __FILE__,
                'line' => 16, // This should be the calling line inside BacktraceCaller@bar
            ],
            'class' => BacktraceCaller::class,
            'function' => 'bar',
            'signature' => 'BacktraceCaller@bar',
            'args' => [],
        ];

        $this->assertEquals($expected, $actual);
    }

}
