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

}
