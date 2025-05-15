<?php

declare(strict_types=1);

use App\Services\AvatarService;
use Illuminate\Contracts\Cache\Repository as Cache;

beforeEach(function () {
    $this->cache = Mockery::mock(Cache::class);
    $this->cache->shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
        return $callback();
    });

    $this->avatarService = new AvatarService(
        cache: $this->cache,
        cacheTtl: 3600,
        defaultTextColor: '#FFFFFF',
        defaultBgColor: '#3182CE',
        backgroundColors: ['#FF5733', '#33FF57', '#3357FF']
    );
});

it('handles edge cases in name analysis', function () {
    $reflection = new ReflectionClass($this->avatarService);
    $method = $reflection->getMethod('analyzeNameCharacteristics');

    // Test empty name
    $result = $method->invoke($this->avatarService, '');
    expect($result)->toBeArray()
        ->and($result['length'])->toBe(0)
        ->and($result['adjustment'])->toBe(0);

    // Test single character name
    $result = $method->invoke($this->avatarService, 'A');
    expect($result)->toBeArray()
        ->and($result['length'])->toBe(1)
        ->and($result['uniqueness'])->toBeGreaterThanOrEqual(0)
        ->and($result['uniqueness'])->toBeLessThanOrEqual(10);
});

it('calculates appropriate characteristics for different name types', function () {
    $reflection = new ReflectionClass($this->avatarService);
    $method = $reflection->getMethod('analyzeNameCharacteristics');

    // Test vowel-heavy name
    $vowelHeavy = $method->invoke($this->avatarService, 'Aoi Ueo');

    // Test consonant-heavy name
    $consonantHeavy = $method->invoke($this->avatarService, 'Brynhildr Skjeggestad');

    // Vowel-heavy names should have higher vowel ratio
    expect($vowelHeavy['vowelRatio'])->toBeGreaterThan($consonantHeavy['vowelRatio']);

    // Names with rare letters should have higher uniqueness
    $withRareLetters = $method->invoke($this->avatarService, 'Xavi Quintanilla Jimenez');
    $ordinary = $method->invoke($this->avatarService, 'Daniel Anderson');

    expect($withRareLetters['uniqueness'])->toBeGreaterThan($ordinary['uniqueness']);
});

it('generates different background colors for different names', function () {
    // Test that different names get consistently different colors
    $avatar1 = $this->avatarService->generateAuto('John Smith');
    $avatar2 = $this->avatarService->generateAuto('Jane Doe');

    expect($avatar1)->not->toBe($avatar2);

    // Test color consistency for the same name
    $avatar3 = $this->avatarService->generateAuto('John Smith');
    expect($avatar1)->toBe($avatar3);
});

it('correctly extracts initials from names', function () {
    $reflection = new ReflectionClass($this->avatarService);
    $method = $reflection->getMethod('getInitials');

    expect($method->invoke($this->avatarService, 'John Smith', 2))->toBe('JS');
    expect($method->invoke($this->avatarService, 'John Smith', 1))->toBe('J');
    expect($method->invoke($this->avatarService, 'John', 2))->toBe('JO');
    expect($method->invoke($this->avatarService, 'O\'Connor', 2))->toBe('OC');
    expect($method->invoke($this->avatarService, 'Jean-Claude', 2))->toBe('JC');
});

it('correctly handles names with special characters and symbols', function () {
    $reflection = new ReflectionClass($this->avatarService);
    $method = $reflection->getMethod('getInitials');

    // Test names with periods
    expect($method->invoke($this->avatarService, 'Jo.hn Smith', 2))->toBe('JS');
    expect($method->invoke($this->avatarService, 'Dr. John Smith', 2))->toBe('DS'); // Dr is treated as the first name part

    // Test names with underscores
    expect($method->invoke($this->avatarService, 'John_Smith', 2))->toBe('JS');
    expect($method->invoke($this->avatarService, 'John_Do_e', 2))->toBe('JE'); // First part "John", last part "e"

    // Test names with mixed symbols
    expect($method->invoke($this->avatarService, 'Jo.hn d_oe', 2))->toBe('JO'); // Split into ["Jo", "hn", "d", "oe"]
    expect($method->invoke($this->avatarService, 'Jo.hn.d_oe', 2))->toBe('JO'); // Similar splitting behavior

    // Test names with other common symbols (not split by default)
    expect($method->invoke($this->avatarService, 'John+Smith', 2))->toBe('JO'); // Only splits by space, hyphen, underscore, period
    expect($method->invoke($this->avatarService, 'John@Smith', 2))->toBe('JO'); // Only splits by space, hyphen, underscore, period

    // Test more complex cases
    expect($method->invoke($this->avatarService, 'J.o-h_n S.m-i_t.h', 2))->toBe('JI'); // Split into many parts
    expect($method->invoke($this->avatarService, '...John...Smith...', 2))->toBe('JS'); // Multiple periods treated as delimiters
});

it('validates colors properly', function () {
    expect($this->avatarService->validateColor('#123456'))->toBeTrue();
    expect($this->avatarService->validateColor('#abc'))->toBeTrue();
    expect($this->avatarService->validateColor('rgb(10, 20, 30)'))->toBeTrue();
    expect($this->avatarService->validateColor('rgba(10, 20, 30, 0.5)'))->toBeTrue();
    expect($this->avatarService->validateColor('invalid'))->toBeFalse();
    expect($this->avatarService->validateColor(null))->toBeFalse();
});
