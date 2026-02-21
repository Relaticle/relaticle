<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\ValidatesCustomFields;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOpportunityRequest extends FormRequest
{
    use ValidatesCustomFields;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'string'],
            'contact_id' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ];

        /** @var ?User $user */
        $user = $this->user();

        if ($user?->currentTeam) {
            $teamId = $user->currentTeam->getKey();
            $rules['company_id'] = ['nullable', 'string', Rule::exists('companies', 'id')->where('team_id', $teamId)];
            $rules['contact_id'] = ['nullable', 'string', Rule::exists('people', 'id')->where('team_id', $teamId)];
        }

        return array_merge($rules, $this->customFieldRules());
    }

    public function customFieldEntityType(): string
    {
        return 'opportunity';
    }
}
