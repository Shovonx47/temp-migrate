<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OwnedByUser implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->type->isCustomer()) {
            $builder->where($model->getTable().'.user_id', $user->id);
        } elseif ($user->type->isSp()) {
            $ownerId = $user->sp_id ?: $user->id;
            $builder->where($model->getTable().'.user_id', $ownerId);
        }
    }
}
