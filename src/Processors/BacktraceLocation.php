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
    protected $classesAndFunctionsToIgnore;

    public function __construct(array $classesAndFunctionsToIgnore = [Logger::class])
    {
        $this->classesAndFunctionsToIgnore = $classesAndFunctionsToIgnore;
        $this->classesAndFunctionsToIgnore[] = static::class;
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
        $backtrace = debug_backtrace();

        $traceNodes = array_filter($backtrace, [$this, 'isNodeANodeWeShouldIgnore']);

        $nodeIndex = array_search(array_pop($traceNodes), $backtrace);

        if ($nodeIndex === false) {
            // Logically, should never happen, as we're searching from something that we
            // just got from that array
            return [
                'error' => 'Impossible error 1. Cannot find something that is literally there!',
            ];
        }

        if (!isset($backtrace[$nodeIndex + 1])) {
            // Again, logically shouldn't happen
            return [
                'error' => 'Impossible error 2. The found node is the absolute root of the stack.',
            ];
        }

        return $this->parseBacktrace(
            $backtrace[$nodeIndex + 1],
            $backtrace[$nodeIndex]
        );
    }


    /**
     * Check if the given node matches any of the classesAndFunctionsToIgnore. It counts as a
     * match if it has a class that matches one of them, or doesn't have a
     * class, and its function matches one of them
     *
     * @param  array  $node
     * @return bool
     */
    protected function isNodeANodeWeShouldIgnore(array $node)
    {
        $found = false;

        foreach ($this->classesAndFunctionsToIgnore as $needle) {
            // if needle is a class name
            if (class_exists($needle)) {
                // and the node's class is that class
                if (isset($node['class']) && $node['class'] === $needle) {
                    // then it exists
                    $found = true;
                }
            }

            // if needle is a function
            if (function_exists($needle)) {
                // and the node has no class (ie. is a function), and its function is the needle
                if (!isset($node['class']) && isset($node['function']) && $node['function'] === $needle) {
                    // then it exists
                    $found = true;
                }
            }
        }

        return $found;
    }

    /**
     * Parse the backtrace node into useful context information
     *
     * @param  array  $node
     * @return array
     */
    protected function parseBacktrace(array $node, array $previous = [])
    {
        $context = [];

        // If there is file information, add it!
        $context['file'] = [
            'file' => isset($previous['file']) ? $previous['file'] : '',
            'line' => isset($previous['line']) ? $previous['line'] : '',
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
            $context['args'] = [];
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
