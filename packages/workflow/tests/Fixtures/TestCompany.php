<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class TestCompany extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'domain', 'status', 'tenant_id'];

    protected $table = 'test_companies';
}
