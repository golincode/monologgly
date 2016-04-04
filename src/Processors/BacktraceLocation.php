<?php

namespace Golin\MonoLoggly\Processors;

use Monolog\Logger;

class BacktraceLocation
{
    /**
     * The class of the logger. This is used to figure out where the log was
     * called from.
     *
     * @var string
     */
    protected $loggerClass;

    public function __construct($loggerClass = Logger::class)
    {
        $this->loggerClass = $loggerClass;
    }

    /**
     * Handle invokations of this object
     *
     * @param  array  $record
     * @return array
     */
    public function __invoke(array $record)
    {
        return array_merge_recursive($record, ['context' => $this->context()]);
    }

    /**
     * Get the context array
     *
     * @return array
     */
    public function context()
    {
        return [
            'origin' => $this->backtraceInfo(),
        ];
    }

    /**
     * Get the backtrace information of whatever called the log
     *
     * @return array
     */
    protected function backtraceInfo()
    {
        // a flag to see if we have reached the logger in the backtrace
        $foundLogger = false;

        foreach (debug_backtrace() as $index => $trace) {
            // loop through until we reach the logger class
            if (!$foundLogger && isset($trace['class']) && $trace['class'] === $this->loggerClass) {
                $foundLogger = true;
            }

            // once we've found the logger, keep looping until we get out of the
            // logger class, then escape the loop
            if ($foundLogger && !(isset($trace['class']) && $trace['class'] === $this->loggerClass)) {
                break;
            }
        }

        return $this->parseBacktrace($trace);
    }

    /**
     * Parse the backtrace node into useful context information
     *
     * @param  array  $node
     * @return array
     */
    protected function parseBacktrace(array $node)
    {
        $context = [];

        // If there is file information, add it!
        $context['file'] = [
            'file' => isset($node['file']) ? $node['file'] : '',
            'line' => isset($node['line']) ? $node['line'] : '',
        ];

        $context['function'] = $function = isset($node['function']) ? $node['function'] : null;

        list($context['class'], $context['signature']) = $this->parseClassAndSignature(
            isset($node['class']) ? $node['class'] : null,
            $function
        );

        // If there is no signature, and the function name is "require", then it
        // is requiring the file, and the arguments are a lie, so ignore them
        $isRequiring = ($function === 'require' && !$context['signature']);

        // Log the types of the arguments
        if ($isRequiring) {
            $context['arge'] = [];
        } else {
            $context['args'] = $this->parseArguments(
                isset($node['args']) ? (array)$node['args'] : []
            );
        }

        return $context;
    }

    /**
     * If the function is a closure, it will be in the current namespace - this
     * strips that out
     *
     * @param  string $function
     * @return string
     */
    protected function parseFunction($function)
    {
        if (strpos($function, '{closure}') !== false) {
            return '{closure}';
        }

        return $function;
    }

    /**
     * Parse the class and function into a signature
     *
     * @param  string $class
     * @param  string $function
     * @return array
     */
    protected function parseClassAndSignature($class, $function)
    {
        $signature = null;
        $function = $this->parseFunction($function);

        // the signature key is a convenience key to describe where the log was
        // called in terms of classes and methods.
        if ($class) {
            // if there is a class, add a signature - class@method
            $class = $class;
            $signature = sprintf('%s@%s', $class, $function);
        } elseif($function === 'require') {
            // if there is no class, and the function is require, it is likely a
            // raw required file, so do nothing
        } elseif($function) {
            // if there is only a function, use that as a signature
            $signature = $function;
        }

        return [$class, $signature];
    }

    /**
     * Parse the arguments into their types
     *
     * @param  array  $arguments
     * @return array
     */
    protected function parseArguments(array $arguments)
    {
        return array_map(function($argument) {
            $type = gettype($argument);

            return $type === 'object' ? get_class($argument) : $type;
        }, (array) $arguments);
    }

}
