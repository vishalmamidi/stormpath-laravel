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
use Illuminate\Http\Request;
use Stormpath\Laravel\Http\Traits\AuthenticatesUser;
use Illuminate\Validation\Factory as Validator;

class ChangePasswordController extends Controller
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

    public function getChangePassword()
    {
        if(!$this->request->has('spToken')) {
            return redirect(config('stormpath.web.changePassword.errorUri'));
        }

        $token = $this->request->get('spToken');
        if(!$this->isValidToken($token)) {
            return redirect(config('stormpath.web.changePassword.errorUri'));
        }

        return view(config('stormpath.web.changePassword.view'));

    }

    public function postChangePassword()
    {
        $newPassword = $this->request->get('password');
        $token = $this->request->get('spToken');

        $validator = $this->loginValidator();

        if($validator->fails()) {
            return redirect()
                ->to(config('stormpath.web.changePassword.uri').'?spToken='.$token)
                ->withErrors($validator);
        }

        $application = app('stormpath.application');

        try {
            $application->resetPassword($token, $newPassword);

            return redirect()
                ->to(config('stormpath.web.changePassword.nextUri'));

        } catch (\Stormpath\Resource\ResourceError $re) {
            return redirect()
                ->to(config('stormpath.web.changePassword.errorUri'))
                ->withErrors(['errors'=>[$re->getMessage()]]);
        }
    }

    private function isValidToken($token)
    {
        $application = app('stormpath.application');
        try {
            $application->verifyPasswordResetToken($token);
            return true;
        } catch (\Stormpath\Resource\ResourceError $re) {
            return false;
        }
    }

    private function loginValidator()
    {
        $validator = $this->validator->make(
            $this->request->all(),
            [
                'password' => 'required|confirmed'
            ],
            [
                'password.required' => 'Password is required.',
                'password.confirmed' => 'Passwords do not match.'
            ]
        );


        return $validator;
    }

}