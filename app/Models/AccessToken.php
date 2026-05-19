<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Laravel\Sanctum\PersonalAccessToken as Model;

#[Table('personal_access_tokens')]
class AccessToken extends Model {}
