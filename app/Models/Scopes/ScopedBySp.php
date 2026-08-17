<?php

namespace App\Models\Scopes;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts Sp-owned rows when an S P is the acting user.
 *
 * Must not call Auth in a way that re-enters this scope while the User
 * provider is loading the authenticatable — that causes infinite recursion
 * and memory exhaustion (User is itself scoped by this class).
 */
class ScopedBySp implements Scope
{
    /**
     * Re-entrancy depth for the current request/worker tick.
     * Auth::user() loads User through Eloquent, which re-applies this scope.
     */
    private static int $resolvingDepth = 0;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolvingDepth > 0) {
            return;
        }

        $user = $this->resolveActingUser();

        if (! $user || $user->type !== UserType::Sp) {
            return;
        }

        // An S P employee is an S P-type user whose sp_id points at the
        // owning reseller. For scoping purposes, treat employees under
        // the owning S P's ID without re-querying the User model.
        $scopingId = ($user->type === UserType::Sp && $user->sp_id)
            ? $user->sp_id
            : $user->id;

        $table = $model->getTable();

        if ($model instanceof User) {
            // An S P may see themselves, their own customers, and their own employees.
            // An employee may only see themselves plus the owning S P's customers.
            $builder->where(function (Builder $query) use ($table, $user, $scopingId): void {
                $query->where($table.'.id', $user->id);

                if (! $user->sp_id) {
                    // Parent S P: see all users owned by this S P (customers and employees)
                    $query->orWhere($table.'.sp_id', $user->id);
                } else {
                    // S P Employee: see only the parent S P's customers
                    $query->orWhere(function (Builder $inner) use ($table, $scopingId): void {
                        $inner->where($table.'.type', UserType::Customer->value)
                            ->where($table.'.sp_id', $scopingId);
                    });
                }
            });

            return;
        }

        $builder->where($table.'.sp_id', $scopingId);
    }

    private function resolveActingUser(): ?User
    {
        self::$resolvingDepth++;

        try {
            foreach (['sp', 'customer', 'superman', 'web'] as $guard) {
                $user = Auth::guard($guard)->user();

                if ($user instanceof User) {
                    return $user;
                }
            }

            return null;
        } finally {
            self::$resolvingDepth--;
        }
    }
}
