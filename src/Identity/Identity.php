<?php

/*
 * This file is part of the nilsir/laravel-esign.
 *
 * (c) nilsir <nilsir@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Nilsir\LaravelEsign\Identity;

use Nilsir\LaravelEsign\Core\AbstractAPI;
use Nilsir\LaravelEsign\Exceptions\HttpException;
use Nilsir\LaravelEsign\Support\Collection;

class Identity extends AbstractAPI
{
    /**
     * @param string $orgId 机构 id
     * @param string $agentAccountId 办理人账号Id
     * @param string $notifyUrl 发起方接收实名认证状态变更通知的地址
     * @param string $redirectUrl 实名结束后页面跳转地址
     * @param string $contextId 发起方业务上下文标识
     * @param string $authType 指定默认认证类型
     * @param bool $repeatIdentity 是否允许重复实名，默认允许
     * @param bool $showResultPage 实名完成是否显示结果页,默认显示
     *
     * @return Collection|null
     *
     * @throws HttpException
     */
    public function getOrgIdentityUrl(
        $orgId,
        $agentAccountId,
        $notifyUrl = '',
        $redirectUrl = '',
        $contextId = '',
        $authType = '',
        $repeatIdentity = true,
        $showResultPage = true
    ) {
        $url = sprintf('/v2/identity/auth/web/%s/orgIdentityUrl', $orgId);
        $params = [
            'authType' => $authType,
            'repeatIdentity' => $repeatIdentity,
            'agentAccountId' => $agentAccountId,
            'contextInfo' => [
                'contextId' => $contextId,
                'notifyUrl' => $notifyUrl,
                'redirectUrl' => $redirectUrl,
                'showResultPage' => $showResultPage,
            ],
        ];

        return $this->parseJSON('json', [$url, $params]);
    }


    /**
     * @param $name
     * @param $idNo
     * @param $mobileNo
     * @return array
     * @throws HttpException
     * 发起运营商3要素核身认证
     */
    public function telecom3Factors($name, $idNo, $mobileNo)
    {
        $url = "/v2/identity/verify/individual/telecom3Factors/detail";
        $params = [
            'name' => $name,
            'idNo' => $idNo,
            'mobileNo' => $mobileNo,
        ];

        return $this->parseJSON('json', [$url, $params]);
    }


    /**
     * @param $name
     * @param $idNo
     * @param $mobileNo
     * @param $bankCardNo
     * @return array
     * @throws HttpException
     * 发起银行4要素核身认证
     */
    public function bankCard4Factors(
        $name,
        $idNo,
        $mobileNo,
        $bankCardNo
    ) {
        $url = "/v2/identity/verify/individual/bank4Factors/detail";
        $params = [
            'name' => $name,
            'idNo' => $idNo,
            'mobileNo' => $mobileNo,
            'cardNo' => $bankCardNo,
        ];
        return $this->parseJSON('json', [$url, $params]);
    }
}
