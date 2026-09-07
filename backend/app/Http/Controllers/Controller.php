<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Every portfolio route authorizes through a policy, so the trait belongs
    // on the base controller rather than being imported case by case.
    use AuthorizesRequests;
}
