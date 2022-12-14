<?php

/*
 * This file is part of the nilsir/laravel-esign.
 *
 * (c) nilsir <nilsir@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Nilsir\LaravelEsign;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nilsir\LaravelEsign\Core\AbstractAPI;
use Nilsir\LaravelEsign\Core\AccessToken;
use Nilsir\LaravelEsign\Core\Http;
use Nilsir\LaravelEsign\Support\Log;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Esign.
 *
 * @property \Nilsir\LaravelEsign\Core\AccessToken $access_token
 * @property \Nilsir\LaravelEsign\Account\Account $account
 * @property \Nilsir\LaravelEsign\File\File $file
 * @property \Nilsir\LaravelEsign\SignFlow\SignFlow $signflow
 * @property \Nilsir\LaravelEsign\Template\Template $template
 * @property \Nilsir\LaravelEsign\Identity\Identity $identity
 */
class Esign extends Container
{
    protected $providers = [
        Foundation\ServiceProviders\AccountProvider::class,
        Foundation\ServiceProviders\FileProvider::class,
        Foundation\ServiceProviders\SignFlowProvider::class,
        Foundation\ServiceProviders\TemplateProvider::class,
        Foundation\ServiceProviders\IdentityProvider::class,
    ];

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this['config'] = function () use ($config) {
            return new Foundation\Config($config);
        };

        $this->registerBase();
        $this->registerProviders();
        $this->initializeLogger();

        $production = $this['config']->get('production', true);
        if ($production) {
            $baseUri = 'https://openapi.esign.cn';
        } else {
            $baseUri = 'https://smlopenapi.esign.cn';
        }

        Http::setDefaultOptions($this['config']->get('guzzle', ['timeout' => 5.0, 'base_uri' => $baseUri]));

        AbstractAPI::maxRetries($this['config']->get('max_retries', 2));

        $this->logConfiguration($config);
    }

    public function logConfiguration($config)
    {
        $config = new Foundation\Config($config);

        $keys = [
            'app_id',
            'secret',
            'open_platform.app_id',
            'open_platform.secret',
            'mini_program.app_id',
            'mini_program.secret'
        ];
        foreach ($keys as $key) {
            !$config->has($key) || $config[$key] = '***' . substr($config[$key], -5);
        }

        Log::debug('Current config:', $config->toArray());
    }

    public function addProvider($provider)
    {
        array_push($this->providers, $provider);

        return $this;
    }

    public function setProviders(array $providers)
    {
        $this->providers = [];

        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function getProviders()
    {
        return $this->providers;
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    private function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register(new $provider());
        }
    }

    private function registerBase()
    {
        $this['request'] = function () {
            return Request::createFromGlobals();
        };


        $this['access_token'] = function () {
            return new AccessToken(
                $this['config']['app_id'],
                $this['config']['secret']
            );
        };
    }

    private function initializeLogger()
    {
        if (Log::hasLogger()) {
            return;
        }

        $logger = new Logger('esign');

        if (!$this['config']['debug'] || defined('PHPUNIT_RUNNING')) {
            $logger->pushHandler(new NullHandler());
        } elseif ($this['config']['log.handler'] instanceof HandlerInterface) {
            $logger->pushHandler($this['config']['log.handler']);
        } elseif ($logFile = $this['config']['log.file']) {
            try {
                $logger->pushHandler(
                    new StreamHandler(
                        $logFile,
                        $this['config']->get('log.level', Logger::WARNING),
                        true,
                        $this['config']->get('log.permission', null)
                    )
                );
            } catch (\Exception $e) {
            }
        }

        Log::setLogger($logger);
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        if (is_callable([$this['fundamental.api'], $method])) {
            return call_user_func_array([$this['fundamental.api'], $method], $args);
        }

        throw new \Exception("Call to undefined method {$method}()");
    }
}
