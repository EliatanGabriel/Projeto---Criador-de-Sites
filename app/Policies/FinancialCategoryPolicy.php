<?php

namespace App\Policies;

use App\Models\FinancialCategory;
use App\Models\User;

class FinancialCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.view');
    }
}
