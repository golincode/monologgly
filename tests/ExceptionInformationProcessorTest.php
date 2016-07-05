<?php

use Golin\MonoLoggly\Processors\ExceptionInformation;

class ExceptionInformationProcessorTest extends PHPUnit_Framework_TestCase
{
    public function testBasicException()
    {
        $processor = new ExceptionInformation;

        $actual = $processor(
            ['context' => ['exception' => new \Exception('foo')]]
        )['context']['exception'];

        $expected = [
            'message' => 'foo',
            'exception' => 'Exception',
            'file' => __FILE__,
            'line' => 12,
        ];

        $this->assertEquals($expected['message'], $actual['message']);
        $this->assertEquals($expected['exception'], $actual['exception']);
        $this->assertEquals($expected['file'], $actual['file']);
        $this->assertEquals($expected['line'], $actual['line']);

        $this->assertTrue(is_string($actual['trace']));
    }

    public function testRecursiveException()
    {
        $processor = new ExceptionInformation;

        $actual = $processor(
            ['context' => ['exception' => new \Exception('', 0, new \RuntimeException)]]
        )['context']['exception'];

        // just check that the regular exception is normal, and there is a previous one
        $this->assertEquals('Exception', $actual['exception']);
        $this->assertEquals('RuntimeException', $actual['previous']['exception']);
    }
}
