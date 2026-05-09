<?php

declare(strict_types=1);

namespace Tests\PHPStan\Rules;

use App\PHPStan\Rules\HardcodedUserFacingStringRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<HardcodedUserFacingStringRule>
 */
final class HardcodedUserFacingStringRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new HardcodedUserFacingStringRule(
            guardedMethods: [
                'label',
                'placeholder',
                'helperText',
                'heading',
                'description',
                'modalHeading',
                'subject',
                'line',
            ],
        );
    }

    public function test_flags_hardcoded_label(): void
    {
        $this->analyse([__DIR__.'/data/hardcoded-label.php'], [
            [
                'Hardcoded user-facing string in ->label() — wrap in __() and add a key under lang/en/.',
                7,
            ],
        ]);
    }

    public function test_allows_translated_label(): void
    {
        $this->analyse([__DIR__.'/data/translated-label.php'], []);
    }
}
