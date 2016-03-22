<?php

namespace Golin\MonoLoggly\Processors;

class ExceptionInformation
{
    /**
     * Handle invokations of this object
     *
     * @param  array  $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $result = null;

        try {
            $result = $this->parseExceptionMessage($record['message']);
        } catch (\Exception $e) {
            // if anything goes wrong, just pretend nothing ever happened. This
            // is handled by the upcoming is null check
        }

        if (is_null($result)) {
            return $record;
        }

        list($record['message'], $context) = $result;

        return array_merge_recursive($record, compact('context'));
    }

    /**
     * Parse the exception message into all of its parts
     *
     * @param  string $original
     * @return array
     */
    protected function parseExceptionMessage($original)
    {
        $results = [];

        // First, get the exception class from the message
        $pattern = '/exception \'([a-zA-Z0-9\\\\]*)\' /';
        if (preg_match($pattern, $original, $results) !== 1) {
            return null;
        }

        list(,$class) = $results;
        $escapedClass = preg_quote($class);

        // Then get the message and location
        $pattern = "/exception '$escapedClass' (with message '(.*)' )?in (\/.*)\:([0-9]*)\\n/";

        if (preg_match($pattern, $original, $results) !== 1) {
            return null;
        }

        list(,,$message, $file, $line) = $results;

        $message = $message ?: '(no message)';

        // Finally, get the stack trace
        $pattern = "/Stack trace:\n(.*)/s";
        $trace = preg_match($pattern, $original, $results) === 1 ?
            $results[1] :
            null;

        return [$this->composeMessage($class, $message, $file, $line), [
            'exception' => [
                'exception' => $class,
                'file'      => $file,
                'line'      => $line,
                'trace'     => $trace,
            ],
        ]];
    }

    /**
     * Compose the new message
     *
     * @param  string $exception
     * @param  string $message
     * @param  string $file
     * @param  string $line
     * @return string
     */
    protected function composeMessage($exception, $message, $file, $line)
    {
        return sprintf('%s: %s [%s:%s]',
            $exception,
            $message,
            $file,
            $line
        );
    }
}
