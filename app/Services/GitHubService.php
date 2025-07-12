<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

final readonly class GitHubService
{
    /**
     * Get the stargazers count for a GitHub repository
     *
     * @param  string  $owner  The repository owner
     * @param  string  $repo  The repository name
     * @param  int  $cacheMinutes  Minutes to cache the result (default: 60)
     */
    public function getStarsCount(string $owner = 'Relaticle', string $repo = 'relaticle', int $cacheMinutes = 60): ?int
    {
        $cacheKey = "github_stars_{$owner}_{$repo}";

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($owner, $repo) {
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                ])->get("https://api.github.com/repos/{$owner}/{$repo}");

                if ($response->successful()) {
                    return (int) $response->json('stargazers_count', 0);
                }

                Log::warning('Failed to fetch GitHub stars: '.$response->status());

                return 0;
            } catch (\Exception $e) {
                Log::error('Error fetching GitHub stars: '.$e->getMessage());

                return 0;
            }
        });
    }

    /**
     * Get the formatted stargazers count for a GitHub repository
     *
     * @param  string  $owner  The repository owner
     * @param  string  $repo  The repository name
     * @param  int  $cacheMinutes  Minutes to cache the result (default: 60)
     */
    public function getFormattedStarsCount(string $owner = 'Relaticle', string $repo = 'relaticle', int $cacheMinutes = 60): string
    {
        $starsCount = $this->getStarsCount($owner, $repo, $cacheMinutes);

        if ($starsCount >= 1000) {
            return Number::abbreviate($starsCount, 1);
        }

        return (string) $starsCount;
    }
}
