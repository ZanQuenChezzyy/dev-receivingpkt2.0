<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\Concerns\InteractsWithCookies;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @mixin MakesHttpRequests
 * @mixin InteractsWithSession
 * @mixin InteractsWithCookies
 * @mixin InteractsWithAuthentication
 * @mixin InteractsWithDatabase
 * @mixin InteractsWithConsole
 */
abstract class TestCase extends BaseTestCase
{
    //
}
