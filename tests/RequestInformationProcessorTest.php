<?php

use Golin\MonoLoggly\Processors\RequestInformation;

class RequestInformationProcessorTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleData()
    {
        $processor = new RequestInformation('foo', 'bar', 'baz');

        $actual = $processor([])['context'];

        $expected = [
            'environment' => 'foo',
            'is-cli' => true,
            'http' => [
                'method' => 'bar',
                'url'    => 'baz',
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testNoMethod()
    {
        $processor = new RequestInformation('foo', null, 'baz');

        $actual = $processor([])['context'];

        $expected = [
            'environment' => 'foo',
            'is-cli' => true,
            'http' => [
                'method' => null,
                'url'    => null,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
}
