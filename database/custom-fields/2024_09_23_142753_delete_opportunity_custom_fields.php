<?php

use App\Models\Opportunity;
use Relaticle\CustomFields\Migrations\CustomFieldsMigration;

return new class extends CustomFieldsMigration
{
    public function up(): void
    {
        $this->migrator->find(Opportunity::class, 'stage')->delete();
    }
};
