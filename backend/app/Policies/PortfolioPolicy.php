<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

/**
 * Ownership is checked here and nowhere else.
 *
 * Every portfolio route authorizes through this policy, so the frontend's
 * routing is a convenience, never a control: a request for someone else's
 * portfolio is a 403 regardless of what the client believes.
 */
final class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        return $this->owns($user, $portfolio);
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $this->owns($user, $portfolio);
    }

    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $this->owns($user, $portfolio);
    }

    public function export(User $user, Portfolio $portfolio): bool
    {
        return $this->owns($user, $portfolio);
    }

    private function owns(User $user, Portfolio $portfolio): bool
    {
        return $user->id === $portfolio->user_id;
    }
}
