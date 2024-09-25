<?php

use App\Models\Company;
use Relaticle\CustomFields\Migrations\CustomFieldsMigration;

return new class extends CustomFieldsMigration {
    public function up(): void
    {
        $this->migrator
            ->find(
                model: Company::class,
                code: 'domain_name'
            )
            ->update([
                'name' => 'Company Domain Name',
            ]);
    }
};
