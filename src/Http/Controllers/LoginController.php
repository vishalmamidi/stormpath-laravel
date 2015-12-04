<?php
/*
 * Copyright 2015 Stormpath, Inc.
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

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Factory as Validator;
use Stormpath\Laravel\Http\Traits\AuthenticatesUser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginController extends Controller
{

    use AuthenticatesUser;

    /**
     * @var Request
     */
    private $request;
    /**
     * @var Validator
     */
    private $validator;


    /**
     * LoginController constructor.
     * @param Request $request
     * @param Validator $validator
     */
    public function __construct(Request $request, Validator $validator)
    {
        $this->request = $request;
        $this->validator = $validator;
    }

    public function getLogin()
    {
        $status = $this->request->get('status');

        return view( config('stormpath.web.login.view'), compact('status') );
    }

    public function postLogin()
    {

        $validator = $this->loginValidator();

        if($validator->fails()) {
            return redirect()
                ->to(config('stormpath.web.login.uri'))
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->authenticate($this->request->get('login'), $this->request->get('password'));

            return redirect()
                ->intended(config('stormpath.web.login.nextUri'));
        } catch (\Stormpath\Resource\ResourceError $re) {
            return redirect()
                ->to(config('stormpath.web.login.uri'))
                ->withErrors(['errors'=>[$re->getMessage()]])
                ->withInput();
        }
    }

    public function getLogout()
    {
        session()->forget(config('stormpath.web.accessTokenCookie.name'));
        session()->forget(config('stormpath.web.refreshTokenCookie.name'));

        return Redirect()->to(config('stormpath.web.logout.nextUri'));
    }

    private function loginValidator()
    {
        $validator = $this->validator->make(
            $this->request->all(),
            [
                'login' => 'required',
                'password' => 'required'
            ],
            [
                'login.required' => 'Login is required.',
                'password.required' => 'Password is required.'
            ]
        );


        return $validator;
    }
}