<?php
/*
 * Copyright 2016 Stormpath, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Stormpath\Laravel\Http\Controllers;

use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stormpath\Laravel\Http\Helpers\FacebookProviderAccountRequest;
use Stormpath\Laravel\Http\Helpers\IdSiteSessionHelper;
use Stormpath\Laravel\Http\Traits\Cookies;
use Stormpath\Provider\ProviderAccountRequest;
use Stormpath\Resource\AccessToken;
use Stormpath\Resource\Account;

class SocialCallbackController extends Controller
{
    use Cookies;

    private $application;

    public function __construct($application = null)
    {
        $this->application = $application;

        if(null === $this->application)
            $this->application = app('stormpath.application');

    }

    public function facebook(Request $request)
    {
        try {
            $providerAccountRequest = new FacebookProviderAccountRequest(array(
                "accessToken" => $request->get('access_token'),
                "code" => $request->get('code')
            ));

            $account = $this->sendProviderAccountRequest($providerAccountRequest);

            $this->setCookies($account);

            if(app('request')->wantsJson()) {
                return $this->respondWithAccount($account);
            }


            return redirect()->to(config('stormpath.web.login.nextUri'));
        } catch (\Stormpath\Resource\ResourceError $re) {
            dd($re);
            return redirect()->to(config('stormpath.web.login.uri'));
        }

    }

    public function google(Request $request)
    {
        try {
            $providerAccountRequest = new \Stormpath\Provider\GoogleProviderAccountRequest(array(
                "code" => $request->get('code')
            ));

            $account = $this->sendProviderAccountRequest($providerAccountRequest);

            $this->setCookies($account);

            if(app('request')->wantsJson()) {
                return $this->respondWithAccount($account);
            }


            return redirect()->to(config('stormpath.web.login.nextUri'));
        } catch (\Stormpath\Resource\ResourceError $re) {
            return redirect()->to(config('stormpath.web.login.uri'));
        }

    }


    protected function sendProviderAccountRequest(ProviderAccountRequest $providerAccountRequest)
    {
        $result = $this->application->getAccount($providerAccountRequest);
        return $result->account;
    }

    protected function setCookies(Account $account)
    {
        $idSiteSession = new IdSiteSessionHelper();
        $accessTokens = $idSiteSession->create($account);

        $this->queueAccessToken($accessTokens->access_token);
        $this->queueRefreshToken($accessTokens->refresh_token);
    }

    private function respondWithAccount(Account $account)
    {
        $properties = ['account'=>[]];
        $blacklistProperties = [
            'httpStatus',
            'account',
            'applications',
            'apiKeys',
            'emailVerificationToken',
            'providerData'
        ];

        $propNames = $account->getPropertyNames();
        foreach($propNames as $prop) {
            if(in_array($prop, $blacklistProperties)) continue;
            if(is_object($account->{$prop})) continue;

            $properties['account'][$prop] = $this->getPropertyValue($account, $prop);
        }

        return response()->json($properties);
    }

    private function getPropertyValue($account, $prop)
    {
        $value = null;

        $value = $account->getProperty($prop);

        return $value;

    }


}
