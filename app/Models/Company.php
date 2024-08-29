<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ManukMinasyan\FilamentAttribute\Models\Concerns\UsesCustomAttributes;
use ManukMinasyan\FilamentAttribute\Models\Contracts\HasCustomAttributes;

class Company extends Model implements HasCustomAttributes
{
    use UsesCustomAttributes;

    protected $fillable = [
        'name', 'address', 'country', 'phone',
    ];
}
