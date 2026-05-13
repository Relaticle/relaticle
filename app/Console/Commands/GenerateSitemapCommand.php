<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Relaticle\Ink\BlogSitemapGenerator;
use Spatie\Sitemap\SitemapGenerator;

final class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $sitemap = SitemapGenerator::create(config('app.url'))->getSitemap();

        BlogSitemapGenerator::addToSitemap($sitemap);

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
