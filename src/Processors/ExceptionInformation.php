<?php

namespace Golin\MonoLoggly\Processors;

use Exception;

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
        if (! isset($record['context']['exception']) || ! $record['context']['exception'] instanceof Exception) {
            return $record;
        }

        $record['context']['exception'] = $this->exceptionInformation($record['context']['exception']);

        return $record;
    }

    /**
     * Recursively format exception information
     *
     * @param  Exception $e
     * @return array
     */
    public function exceptionInformation(Exception $e)
    {
        $data = [
            'message'   => $e->getMessage(),
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => json_encode($this->trace($e), JSON_PRETTY_PRINT),
        ];

        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->exceptionInformation($previous);
        }

        return $data;
    }

    /**
     * Format the stack trace
     *
     * @param  Exception $e
     * @return array
     */
    protected function trace(Exception $e)
    {
        return array_map(function($item) {
            return [
                'file' => isset($item['file']) ? $item['file'] : null,
                'line' => isset($item['line']) ? $item['line'] : null,
                'class' => isset($item['class']) ? $item['class'] : null,
                'function' => isset($item['function']) ? $item['function'] : null,
            ];
        }, $e->getTrace());
    }
}
