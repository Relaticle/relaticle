<?php

use App\Models\Opportunity;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Migrations\CustomFieldsMigration;

return new class extends CustomFieldsMigration
{
    public function up(): void
    {
        // Name - Indicates the name of the opportunity
        $this->migrator->new(
            model: Opportunity::class,
            type: CustomFieldType::TEXT,
            name: 'Name',
            code: 'name'
        )->create();

        // Stage - Indicates the stage of the opportunity
        $this->migrator
            ->new(
                model: Opportunity::class,
                type: CustomFieldType::SELECT,
                name: 'Stage',
                code: 'stage'
            )
            ->options([
                'New',
                'Screening',
                'Meeting',
                'Proposal',
                'Customer',
            ])
            ->create();
    }
};
