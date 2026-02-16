<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;

final class QueryCounter
{
    /** @var array<int, array<string, mixed>> */
    private array $queries = [];

    public function start(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
    }

    public function stop(): void
    {
        $this->queries = DB::getQueryLog();
        DB::disableQueryLog();
    }

    public function count(): int
    {
        return count($this->queries);
    }

    /**
     * Find queries matching a pattern and return count + examples.
     *
     * @return array{count: int, examples: array<int, string>}
     */
    public function findRepeated(string $pattern): array
    {
        $matches = [];

        foreach ($this->queries as $query) {
            if (str_contains($query['query'], $pattern)) {
                $matches[] = $query['query'];
            }
        }

        return [
            'count' => count($matches),
            'examples' => array_slice($matches, 0, 3),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->queries;
    }

    public function dump(): void
    {
        dump("Total queries: " . $this->count());

        $grouped = [];
        foreach ($this->queries as $q) {
            $normalized = preg_replace('/\?/', '?', $q['query']);
            $grouped[$normalized] = ($grouped[$normalized] ?? 0) + 1;
        }

        arsort($grouped);
        foreach ($grouped as $sql => $count) {
            if ($count > 1) {
                dump("[{$count}x] {$sql}");
            }
        }
    }
}
