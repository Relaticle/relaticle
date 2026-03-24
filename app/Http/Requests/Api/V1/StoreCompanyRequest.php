<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesCustomFields;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCompanyRequest extends FormRequest
{
    use ValidatesCustomFields;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
        ], $this->customFieldRules());
    }

    public function customFieldEntityType(): string
    {
        return 'company';
    }
}
