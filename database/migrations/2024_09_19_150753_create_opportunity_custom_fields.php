<?php

use App\Models\Opportunity;
use ManukMinasyan\FilamentCustomField\Enums\CustomFieldType;
use ManukMinasyan\FilamentCustomField\Migrations\CustomFieldsMigration;

return new class extends CustomFieldsMigration
{
    public function up(): void
    {
        $this->migrator->add(
            model: Opportunity::class,
            type: CustomFieldType::TEXT,
            name: 'Name',
            code: 'name'
        );
    }
};
