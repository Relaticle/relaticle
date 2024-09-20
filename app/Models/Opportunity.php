<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ManukMinasyan\FilamentCustomField\Models\Concerns\UsesCustomFields;
use ManukMinasyan\FilamentCustomField\Models\Contracts\HasCustomFields;

class Opportunity extends Model implements HasCustomFields
{
    use HasFactory;
    use UsesCustomFields;
}
