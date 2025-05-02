<?php

declare(strict_types=1);

namespace Relaticle\Documentation\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class DocumentSearchRequest extends Data
{
    public function __construct(
        #[StringType]
        #[Min(3)]
        public string $query,

        #[StringType]
        public ?string $type = null,
    ) {}

    public static function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:'.config('documentation.search.min_length', 3)],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('documentation.documents', [])))],
        ];
    }
}
