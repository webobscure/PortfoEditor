<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PortfolioExport;
use App\Models\User;

final class PortfolioExportPolicy
{
    public function view(User $user, PortfolioExport $export): bool
    {
        return $user->id === $export->user_id;
    }
}
