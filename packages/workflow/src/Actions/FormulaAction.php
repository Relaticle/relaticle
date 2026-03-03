<?php

declare(strict_types=1);

namespace Relaticle\Workflow\Actions;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;

class FormulaAction extends BaseAction
{
    /**
     * Execute the formula action, evaluating a mathematical expression.
     *
     * Supports +, -, *, /, parentheses and variable placeholders resolved from context.
     *
     * @param  array<string, mixed>  $config  Expected keys: 'formula' (string), 'variables' (array)
     * @param  array<string, mixed>  $context  The workflow execution context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $formula = $config['formula'] ?? '';
        $variables = $config['variables'] ?? [];

        if (empty($formula)) {
            return ['error' => 'Formula is required', 'result' => null];
        }

        try {
            // Replace variable placeholders with values from context
            $expression = $formula;
            foreach ($variables as $placeholder => $contextPath) {
                $value = data_get($context, $contextPath, 0);
                if (!is_numeric($value)) {
                    return [
                        'error' => "Variable '{$placeholder}' resolved to a non-numeric value",
                        'result' => null,
                    ];
                }
                $expression = str_replace('{' . $placeholder . '}', (string) $value, $expression);
            }

            $result = $this->evaluateExpression($expression);

            return [
                'result' => $result,
                'formula' => $formula,
                'resolved_expression' => $expression,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => 'Formula evaluation failed: ' . $e->getMessage(),
                'result' => null,
            ];
        }
    }

    /**
     * Evaluate a simple mathematical expression supporting +, -, *, /, and parentheses.
     *
     * Uses a recursive descent parser to safely evaluate without eval().
     */
    private function evaluateExpression(string $expression): float|int
    {
        // Remove whitespace
        $expression = preg_replace('/\s+/', '', $expression);

        // Validate that the expression only contains allowed characters
        if (!preg_match('/^[0-9+\-*\/().]+$/', $expression)) {
            throw new \InvalidArgumentException('Expression contains invalid characters');
        }

        $pos = 0;
        $result = $this->parseAddition($expression, $pos);

        if ($pos !== strlen($expression)) {
            throw new \InvalidArgumentException('Unexpected characters in expression');
        }

        return $result;
    }

    private function parseAddition(string $expr, int &$pos): float|int
    {
        $result = $this->parseMultiplication($expr, $pos);

        while ($pos < strlen($expr) && in_array($expr[$pos], ['+', '-'], true)) {
            $operator = $expr[$pos];
            $pos++;
            $right = $this->parseMultiplication($expr, $pos);
            $result = $operator === '+' ? $result + $right : $result - $right;
        }

        return $result;
    }

    private function parseMultiplication(string $expr, int &$pos): float|int
    {
        $result = $this->parseUnary($expr, $pos);

        while ($pos < strlen($expr) && in_array($expr[$pos], ['*', '/'], true)) {
            $operator = $expr[$pos];
            $pos++;
            $right = $this->parseUnary($expr, $pos);

            if ($operator === '/') {
                if ($right == 0) {
                    throw new \InvalidArgumentException('Division by zero');
                }
                $result = $result / $right;
            } else {
                $result = $result * $right;
            }
        }

        return $result;
    }

    private function parseUnary(string $expr, int &$pos): float|int
    {
        if ($pos < strlen($expr) && $expr[$pos] === '-') {
            $pos++;

            return -$this->parsePrimary($expr, $pos);
        }

        if ($pos < strlen($expr) && $expr[$pos] === '+') {
            $pos++;
        }

        return $this->parsePrimary($expr, $pos);
    }

    private function parsePrimary(string $expr, int &$pos): float|int
    {
        if ($pos < strlen($expr) && $expr[$pos] === '(') {
            $pos++; // skip '('
            $result = $this->parseAddition($expr, $pos);

            if ($pos >= strlen($expr) || $expr[$pos] !== ')') {
                throw new \InvalidArgumentException('Missing closing parenthesis');
            }
            $pos++; // skip ')'

            return $result;
        }

        return $this->parseNumber($expr, $pos);
    }

    private function parseNumber(string $expr, int &$pos): float|int
    {
        $start = $pos;

        while ($pos < strlen($expr) && (ctype_digit($expr[$pos]) || $expr[$pos] === '.')) {
            $pos++;
        }

        if ($start === $pos) {
            throw new \InvalidArgumentException('Expected a number at position ' . $pos);
        }

        $number = substr($expr, $start, $pos - $start);

        return str_contains($number, '.') ? (float) $number : (int) $number;
    }

    /**
     * Get a human-readable label for this action.
     */
    public static function label(): string
    {
        return 'Formula';
    }

    public static function category(): string
    {
        return 'Calculations';
    }

    public static function icon(): string
    {
        return 'heroicon-o-calculator';
    }

    /**
     * Get the configuration schema for this action.
     *
     * @return array<string, mixed>
     */
    public static function configSchema(): array
    {
        return [
            'formula' => ['type' => 'string', 'label' => 'Formula', 'required' => true],
            'variables' => ['type' => 'object', 'label' => 'Variable Mappings', 'required' => false],
        ];
    }

    public static function filamentForm(): array
    {
        return [
            TextInput::make('formula')
                ->label('Formula')
                ->required()
                ->placeholder('{price} * {quantity} - {discount}')
                ->helperText('Use {variable_name} placeholders. Supports +, -, *, /, and parentheses.'),
            KeyValue::make('variables')
                ->label('Variable Mappings')
                ->keyLabel('Placeholder')
                ->valueLabel('Context Path')
                ->addActionLabel('Add variable')
                ->helperText('Map placeholder names to context paths (e.g. price -> trigger.record.price)'),
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
            'result' => ['type' => 'number', 'label' => 'Result'],
            'formula' => ['type' => 'string', 'label' => 'Original Formula'],
            'resolved_expression' => ['type' => 'string', 'label' => 'Resolved Expression'],
        ];
    }
}
