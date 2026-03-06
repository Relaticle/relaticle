<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Livewire\Component;

use function Laravel\Ai\agent;

class ClassifyAction extends BaseAction
{
    /**
     * Execute the classify action using AI structured output with keyword fallback.
     *
     * Uses the Laravel AI SDK to classify input text into one of the configured
     * categories via structured output. Falls back to keyword-based classification
     * if the AI call fails.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'input_path' (string), 'categories' (array), 'provider' (string), 'model' (string)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $inputPath = $config['input_path'] ?? '';
        $categories = $config['categories'] ?? [];
        $provider = $config['provider'] ?? 'anthropic';
        $model = $config['model'] ?? 'claude-haiku-4-5-20251001';

        if (empty($inputPath)) {
            return ['error' => 'input_path is required', 'category' => null, 'confidence' => 0.0, 'input_text' => ''];
        }

        if (empty($categories)) {
            return ['error' => 'At least one category is required', 'category' => null, 'confidence' => 0.0, 'input_text' => ''];
        }

        try {
            $inputText = data_get($context, $inputPath, '');

            if (!is_string($inputText)) {
                $inputText = (string) $inputText;
            }

            if (empty($inputText)) {
                return [
                    'category' => $categories[0] ?? 'unknown',
                    'confidence' => 0.0,
                    'input_text' => '',
                    'model' => $model,
                ];
            }

            $categoriesList = implode(', ', $categories);

            $response = agent(
                instructions: "You are a text classification assistant. Classify the given text into exactly one of these categories: {$categoriesList}. Return the category name exactly as provided and a confidence score between 0 and 1.",
                schema: fn (JsonSchema $schema) => [
                    'category' => $schema->string()
                        ->description('The matched category, must be one of: ' . $categoriesList)
                        ->required(),
                    'confidence' => $schema->number()
                        ->min(0)
                        ->max(1)
                        ->description('Confidence score between 0 and 1')
                        ->required(),
                ],
            )->prompt(
                prompt: "Classify this text:\n\n{$inputText}",
                provider: $provider,
                model: $model,
                timeout: 30,
            );

            return [
                'category' => $response['category'],
                'confidence' => (float) $response['confidence'],
                'input_text' => mb_substr($inputText, 0, 500),
                'model' => $response->meta->model ?? $model,
            ];
        } catch (\Throwable $e) {
            // Fall back to keyword-based classification
            if (isset($inputText) && !empty($inputText)) {
                $fallback = $this->classifyByKeyword($inputText, $categories);

                return [
                    'category' => $fallback['category'],
                    'confidence' => $fallback['confidence'],
                    'input_text' => mb_substr($inputText, 0, 500),
                    'model' => 'keyword_fallback',
                    'ai_error' => $e->getMessage(),
                ];
            }

            return [
                'error' => 'Classification failed: ' . $e->getMessage(),
                'category' => null,
                'confidence' => 0.0,
                'input_text' => '',
            ];
        }
    }

    /**
     * Classify text by matching against category keywords.
     *
     * Splits each category name into keywords and counts occurrences in the input text.
     *
     * @return array{category: string, confidence: float}
     */
    private function classifyByKeyword(string $text, array $categories): array
    {
        $textLower = mb_strtolower($text);
        $bestCategory = $categories[0];
        $bestScore = 0;
        $totalScore = 0;

        foreach ($categories as $category) {
            $keywords = preg_split('/[\s_\-\/]+/', mb_strtolower($category));
            $score = 0;

            foreach ($keywords as $keyword) {
                if (strlen($keyword) < 2) {
                    continue;
                }
                $score += mb_substr_count($textLower, $keyword);
            }

            $totalScore += $score;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $category;
            }
        }

        // Calculate confidence as proportion of total score
        $confidence = $totalScore > 0 ? round($bestScore / $totalScore, 4) : 0.0;

        // If no keywords matched at all, assign a low default confidence to the first category
        if ($totalScore === 0) {
            $confidence = round(1 / count($categories), 4);
        }

        return [
            'category' => $bestCategory,
            'confidence' => $confidence,
        ];
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Classify record';
    }

    public static function hasSideEffects(): bool
    {
        return true;
    }

    public static function category(): string
    {
        return 'AI';
    }

    public static function icon(): string
    {
        return 'heroicon-o-tag';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'input_path' => ['type' => 'string', 'label' => 'Input Path', 'required' => true],
            'categories' => ['type' => 'array', 'label' => 'Categories', 'required' => true],
            'model' => ['type' => 'string', 'label' => 'AI Model', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            Select::make('input_path')
                ->label('Input Text')
                ->required()
                ->searchable()
                ->placeholder('Select a text field...')
                ->helperText('Select the text field to classify')
                ->options(fn (?Component $livewire) => static::getFieldOptions($livewire, ['string', 'text'])),
            TagsInput::make('categories')
                ->label('Categories')
                ->required()
                ->placeholder('Add a category')
                ->helperText('Enter the possible classification categories'),
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
            'category' => ['type' => 'string', 'label' => 'Matched Category'],
            'confidence' => ['type' => 'number', 'label' => 'Confidence Score'],
            'input_text' => ['type' => 'string', 'label' => 'Input Text'],
            'model' => ['type' => 'string', 'label' => 'Model Used'],
        ];
    }
}
