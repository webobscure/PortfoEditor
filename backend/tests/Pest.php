<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Feature tests exercise the SPA's actual transport: Sanctum only puts the
 * session middleware on an API request it recognises as coming from the
 * frontend, which it decides from the Origin header. Without this the suite
 * would be testing a stateless variant of the API that the app never uses.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withHeader('Origin', config('app.url'));
    })
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
