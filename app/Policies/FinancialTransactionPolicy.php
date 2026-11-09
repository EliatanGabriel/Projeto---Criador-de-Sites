<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.view');
    }

    public function view(User $user, FinancialTransaction $transaction): bool
    {
        if (! $user->can('finance.view')) {
            return false;
        }

        if ($user->hasRole('OWNER')) {
            return true;
        }

        if ($user->hasRole('COMMERCIAL')) {
            return $transaction->client_id !== null
                && (\App\Models\Opportunity::where('client_id', $transaction->client_id)->where('responsible_id', $user->id)->exists()
                    || \App\Models\Demand::where('client_id', $transaction->client_id)->where('commercial_owner_id', $user->id)->exists());
        }

        if ($user->hasRole('DEVELOPER')) {
            return false;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('finance.manage');
    }

    public function update(User $user, FinancialTransaction $transaction): bool
    {
        return $user->can('finance.manage');
    }

    public function delete(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }
}
