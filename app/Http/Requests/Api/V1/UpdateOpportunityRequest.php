<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', 'exists:companies,id'],
            'contact_id' => ['nullable', 'string', 'exists:people,id'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
