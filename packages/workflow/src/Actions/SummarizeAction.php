<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

use function Laravel\Ai\agent;

class SummarizeAction extends BaseAction
{
    /**
     * Execute the summarize action using AI to generate a concise summary from record fields.
     *
     * Resolves a record from the workflow context, builds field text from specified fields,
     * and calls the Laravel AI SDK to produce an actionable business summary.
     * Falls back to raw field concatenation if AI fails.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'record_source' (string), 'step_node_id' (string|null), 'fields' (array), 'provider' (string), 'model' (string)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $source = $config['record_source'] ?? 'trigger';
        $stepNodeId = $config['step_node_id'] ?? null;
        $fields = $config['fields'] ?? [];
        $provider = $config['provider'] ?? 'anthropic';
        $model = $config['model'] ?? 'claude-haiku-4-5-20251001';

        try {
            $record = $this->resolveRecord($source, $stepNodeId, $context);

            if ($record === null) {
                return [
                    'error' => 'Could not resolve record to summarize',
                    'summary' => null,
                    'field_count' => 0,
                ];
            }

            $recordData = $record instanceof Model ? $record->toArray() : (array) $record;

            if (empty($fields)) {
                $fields = collect($recordData)
                    ->filter(fn ($value) => is_scalar($value) || is_null($value))
                    ->keys()
                    ->toArray();
            }

            $fieldText = $this->buildFieldText($recordData, $fields);
            $includedCount = count(array_filter($fields, fn ($f) => data_get($recordData, $f) !== null));

            $response = agent(
                instructions: 'You are a CRM assistant. Summarize the following record data concisely in 2-3 sentences. Focus on actionable business insights. Do not use bullet points.',
            )->prompt(
                prompt: "Summarize this record:\n\n{$fieldText}",
                provider: $provider,
                model: $model,
                timeout: 30,
            );

            return [
                'summary' => $response->text,
                'field_count' => $includedCount,
            ];
        } catch (\Throwable $e) {
            // Fallback to field concatenation if AI fails
            if (isset($fieldText, $includedCount)) {
                return [
                    'summary' => $fieldText,
                    'field_count' => $includedCount,
                    'ai_error' => $e->getMessage(),
                ];
            }

            return [
                'error' => 'Summarization failed: ' . $e->getMessage(),
                'summary' => null,
                'field_count' => 0,
            ];
        }
    }

    /**
     * Build human-readable field text from record data for AI summarization.
     *
     * @param  array<string, mixed>  $recordData
     * @param  array<int, string>  $fields
     */
    private function buildFieldText(array $recordData, array $fields): string
    {
        $parts = [];

        foreach ($fields as $field) {
            $value = data_get($recordData, $field);

            if ($value === null) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            $label = str_replace('_', ' ', ucfirst((string) $field));
            $parts[] = "{$label}: {$value}";
        }

        return implode("\n", $parts);
    }

    /**
     * Resolve a record from the workflow context.
     */
    private function resolveRecord(string $source, ?string $stepNodeId, array $context): Model|array|null
    {
        if ($source === 'trigger') {
            return $context['trigger']['record'] ?? null;
        }

        if ($source === 'step' && $stepNodeId) {
            $stepOutput = $context['steps'][$stepNodeId]['output'] ?? null;

            if (!$stepOutput) {
                return null;
            }

            // If the step stored a record directly
            if (isset($stepOutput['_record']) && $stepOutput['_record'] instanceof Model) {
                return $stepOutput['_record'];
            }

            // If the step stored record data as an array
            if (isset($stepOutput['record']) && is_array($stepOutput['record'])) {
                return $stepOutput['record'];
            }

            return $stepOutput;
        }

        return null;
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Summarize record';
    }

    public static function category(): string
    {
        return 'AI';
    }

    public static function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'record_source' => ['type' => 'string', 'label' => 'Record Source', 'required' => true],
            'step_node_id' => ['type' => 'string', 'label' => 'Step Node ID', 'required' => false],
            'fields' => ['type' => 'array', 'label' => 'Fields to Include', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Select::make('record_source')
                ->label('Record Source')
                ->options([
                    'trigger' => 'Trigger Record',
                    'step' => 'From Previous Step',
                ])
                ->required()
                ->default('trigger')
                ->live(),
            TextInput::make('step_node_id')
                ->label('Step Node ID')
                ->placeholder('e.g. action-2')
                ->visible(fn ($get) => $get('record_source') === 'step'),
            TagsInput::make('fields')
                ->label('Fields to Include')
                ->placeholder('Add field name')
                ->helperText('Leave empty to include all scalar fields from the record'),
            Select::make('provider')
                ->label('AI Provider')
                ->options([
                    'anthropic' => 'Anthropic (Claude)',
                    'openai' => 'OpenAI (GPT)',
                    'gemini' => 'Google (Gemini)',
                ])
                ->default('anthropic'),
            Select::make('model')
                ->label('AI Model')
                ->options([
                    'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fast)',
                    'claude-sonnet-4-5-20250514' => 'Claude Sonnet 4.5 (Balanced)',
                    'gpt-4o-mini' => 'GPT-4o Mini (Fast)',
                    'gpt-4o' => 'GPT-4o (Balanced)',
                ])
                ->default('claude-haiku-4-5-20251001'),
        ];
    }

    /**
     * Get the output schema describing what variables this action produces.
     *
     * @return array<string, array{type: string, label: string}>
     */
    public static function outputSchema(): array
    {
        return [
            'summary' => ['type' => 'string', 'label' => 'Summary Text'],
            'field_count' => ['type' => 'number', 'label' => 'Fields Included'],
        ];
    }
}
