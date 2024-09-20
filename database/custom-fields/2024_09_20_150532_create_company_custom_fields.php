<?php

use App\Models\Company;
use ManukMinasyan\FilamentCustomField\Enums\CustomFieldType;
use ManukMinasyan\FilamentCustomField\Migrations\CustomFieldsMigration;

return new class extends CustomFieldsMigration {
    public function up(): void
    {
        // ICP - Ideal Customer Profile: Indicates whether the company is the most suitable and valuable customer for you
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::TOGGLE,
                name: 'ICP',
                code: 'icp',
            )
            ->create();

        // Domain Name - Indicates the domain name of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::LINK,
                name: 'Domain Name',
                code: 'domain_name'
            )
            ->create();

        // Linkedin - Indicates the LinkedIn profile of the company
        $this->migrator
            ->new(
                model: Company::class,
                type: CustomFieldType::LINK,
                name: 'Linkedin',
                code: 'linkedin'
            )
            ->create();
    }
};
