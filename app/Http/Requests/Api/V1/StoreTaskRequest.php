<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Rules\ValidCustomFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTaskRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $teamId = $user->currentTeam->getKey();
        $teamMemberIds = $user->currentTeam->allUsers()->pluck('id')->all();

        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['string', Rule::exists('companies', 'id')->where('team_id', $teamId)],
            'people_ids' => ['nullable', 'array'],
            'people_ids.*' => ['string', Rule::exists('people', 'id')->where('team_id', $teamId)],
            'opportunity_ids' => ['nullable', 'array'],
            'opportunity_ids.*' => ['string', Rule::exists('opportunities', 'id')->where('team_id', $teamId)],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['string', Rule::in($teamMemberIds)],
        ], (new ValidCustomFields($teamId, 'task'))->toRules($this->input('custom_fields')));
    }
}
