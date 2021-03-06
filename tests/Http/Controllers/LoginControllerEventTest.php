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

namespace Stormpath\Tests\Http\Controllers;

use Stormpath\Laravel\Tests\TestCase;
use Stormpath\Resource\Account;

class LoginControllerEventTest extends TestCase
{

    /** @test */
    public function it_fires_the_UserIsLoggingIn_event_before_authentication()
    {
        $this->expectsEvents(\Stormpath\Laravel\Events\UserIsLoggingIn::class);

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->assertTrue(cookie()->hasQueued(config('stormpath.web.accessTokenCookie.name')));
        $this->assertTrue(cookie()->hasQueued(config('stormpath.web.refreshTokenCookie.name')));
        $account->delete();
    }

    /** @test */
    public function UserIsLoggingIn_event_passes_user_and_pass()
    {
        \Event::listen(\Stormpath\Laravel\Events\UserIsLoggingIn::class, function ($event) {
            $this->assertEquals('test@test.com', $event->getData()['login']);
            $this->assertEquals('superP4ss!', $event->getData()['password']);
        });

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);


        $account->delete();
    }

    /**
     * @test
     * @expectedException \Stormpath\Laravel\Exceptions\ActionAbortedException
    */
    public function it_aborts_the_login_if_the_UserIsLoggingIn_event_listener_returns_false()
    {
        \Event::listen(\Stormpath\Laravel\Events\UserIsLoggingIn::class, function ($event) {
            return false;
        });

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);

        $this->assertTrue(cookie()->hasQueued(config('stormpath.web.accessTokenCookie.name')));
        $this->assertTrue(cookie()->hasQueued(config('stormpath.web.refreshTokenCookie.name')));
        $account->delete();
    }

    /** @test */
    public function it_fires_the_UserHasLoggedIn_event_after_authentication()
    {
        $this->expectsEvents(\Stormpath\Laravel\Events\UserHasLoggedIn::class);

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);

        $this->assertTrue(cookie()->hasQueued(config('stormpath.web.accessTokenCookie.name')));
        $this->assertTrue(cookie()->hasQueued(config('stormpath.web.refreshTokenCookie.name')));
        $account->delete();
    }

    /** @test */
    public function UserHasLoggedIn_event_sends_account_object()
    {
        \Event::listen(\Stormpath\Laravel\Events\UserHasLoggedIn::class, function ($event) {
            $this->assertInstanceOf(Account::class, $event->getAccount());
        });

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);


        $account->delete();
    }


    /** @test */
    public function it_fires_the_UserIsLoggingOut_event_before_logging_out_the_user()
    {
        $this->expectsEvents(\Stormpath\Laravel\Events\UserIsLoggingOut::class);

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);

        $this->call('POST', config('stormpath.web.logout.uri'));

        $headers = $this->response->headers;
        $cookies = $headers->getCookies();
        foreach($cookies as $cookie) {
            if($cookie->getName() == config('stormpath.web.accessTokenCookie.name') || $cookie->getName() == config('stormpath.web.refreshTokenCookie.name')) {
                $this->assertLessThan(time(), $cookie->getExpiresTime());
            }
        }

        $this->assertRedirectedTo(config('stormpath.web.logout.nextUri'));
        $account->delete();
    }

    /**
     * @test
     * @expectedException \Stormpath\Laravel\Exceptions\ActionAbortedException
    */
    public function it_aborts_the_logout_if_the_UserIsLoggingOut_event_listener_returns_false()
    {
        \Event::listen(\Stormpath\Laravel\Events\UserIsLoggingOut::class, function ($event) {
            return false;
        });

        $this->setupStormpathApplication();
        $account = $this->createAccount(['login' => 'test@test.com', 'password' => 'superP4ss!']);
        $this->post('login', ['login' => 'test@test.com', 'password' => 'superP4ss!']);

        $this->call('POST', config('stormpath.web.logout.uri'));

        // assert that the cookies still exist (ie. the user has not been logged
        // out)
        $this->seeCookie(config('stormpath.web.accessTokenCookie.name'));
        $this->seeCookie(config('stormpath.web.refreshTokenCookie.name'));
        $account->delete();
    }
}
