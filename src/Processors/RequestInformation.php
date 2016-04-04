<?php

namespace Golin\MonoLoggly\Processors;

class RequestInformation
{
    /**
     * The environment
     *
     * @var string
     */
    protected $environment;
    /**
     * The method
     *
     * @var string
     */
    protected $method;
    /**
     * The url
     *
     * @var string
     */
    protected $url;


    public function __construct($environment, $method, $url)
    {
        $this->environment = $environment;

        $this->method = $method;
        $this->url = $url;
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
     * Get the useful context items
     *
     * @return array
     */
    public function context()
    {
        $context['environment'] = $this->environment;

        $context['http'] = [
            'method' => $this->method ? strtolower($this->method) : null,
            'url'    => $this->url,
        ];

        // nicked from laravel's Application@runningInConsole method
        $context['is-cli'] = php_sapi_name() == 'cli';

        return $context;
    }
}
