<?php

/*
 * This file is part of the nilsir/laravel-esign.
 *
 * (c) nilsir <nilsir@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Nilsir\LaravelEsign\Core;

use Illuminate\Support\Facades\Cache;
use Nilsir\LaravelEsign\Exceptions\HttpException;

class AccessToken
{
    protected $appId;

    protected $secret;

    protected $cache;

    protected $cacheKey;

    protected $http;

    protected $tokenJsonKey = 'token';

    protected $prefix = 'esign.common.access_token.';

    const API_TOKEN_GET = '/v1/oauth2/access_token';

    public function __construct($appId, $secret)
    {
        $this->appId = $appId;
        $this->secret = $secret;
        $this->cache = $this->getCache();
    }

    /**
     * @param bool $forceRefresh
     *
     * @return bool|mixed
     *
     * @throws HttpException
     */
    public function getToken($forceRefresh = false)
    {
        $cacheKey = $this->getCacheKey();
        $cached = $this->getCache()->get($cacheKey);

        if ($forceRefresh || empty($cached)) {
            $token = $this->getTokenFromServer();
            $this->getCache()->put($cacheKey, $token['data'][$this->tokenJsonKey], 60 * 100);

            return $token['data'][$this->tokenJsonKey];
        }

        return $cached;
    }

    /**
     * @return mixed
     *
     * @throws HttpException
     */
    public function getTokenFromServer()
    {
        $params = [
            'appId' => $this->appId,
            'secret' => $this->secret,
            'grantType' => 'client_credentials',
        ];

        $http = $this->getHttp();

        $token = $http->parseJSON($http->get(self::API_TOKEN_GET, $params));

        if (empty($token['data'][$this->tokenJsonKey])) {
            throw new HttpException('Request AccessToken fail. response: '.json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        return $token;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    protected function getCache()
    {
        return Cache::getStore();
    }

    public function getHttp()
    {
        return $this->http ?: $this->http = new Http();
    }

    public function setHttp($http)
    {
        $this->http = $http;

        return $this;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    protected function getCacheKey()
    {
        if (is_null($this->cacheKey)) {
            return $this->prefix.$this->appId;
        }

        return $this->cacheKey;
    }
}
