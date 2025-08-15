<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

final class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relaticle:install
                            {--force : Force installation even if already configured}
                            {--no-demo : Skip demo data seeding}
                            {--quick : Use default SQLite settings for quick setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Relaticle with a single command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Welcome to Relaticle Installation');
        $this->newLine();

        // Check if already installed
        if (! $this->option('force') && $this->isAlreadyInstalled()) {
            $this->warn('Relaticle appears to be already installed.');

            if (! $this->confirm('Do you want to continue anyway?')) {
                $this->info('Installation cancelled.');

                return self::SUCCESS;
            }
        }

        // System requirements check
        if (! $this->checkSystemRequirements()) {
            return self::FAILURE;
        }

        $this->info('✅ System requirements check passed');
        $this->newLine();

        // Environment setup
        if (! $this->setupEnvironment()) {
            return self::FAILURE;
        }

        // Install dependencies
        if (! $this->installDependencies()) {
            return self::FAILURE;
        }

        // Database setup
        if (! $this->setupDatabase()) {
            return self::FAILURE;
        }

        // Asset compilation
        if (! $this->buildAssets()) {
            return self::FAILURE;
        }

        // Storage setup
        if (! $this->setupStorage()) {
            return self::FAILURE;
        }

        // Optional demo data
        if (! $this->option('no-demo') && $this->confirm('Would you like to install demo data?', true)) {
            $this->seedDemoData();
        }

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    private function isAlreadyInstalled(): bool
    {
        return File::exists(base_path('.env')) &&
               config('app.key') &&
               File::exists(public_path('storage'));
    }

    private function checkSystemRequirements(): bool
    {
        $this->info('🔍 Checking system requirements...');

        $requirements = [
            'PHP 8.3+' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'Composer' => $this->commandExists('composer'),
            'Node.js' => $this->commandExists('node'),
            'NPM' => $this->commandExists('npm'),
        ];

        $requiredExtensions = [
            'pdo_sqlite', 'gd', 'bcmath', 'ctype', 'fileinfo',
            'json', 'mbstring', 'openssl', 'tokenizer', 'xml',
        ];

        foreach ($requiredExtensions as $extension) {
            $requirements["PHP {$extension} extension"] = extension_loaded($extension);
        }

        $allPassed = true;
        foreach ($requirements as $requirement => $passed) {
            if ($passed) {
                $this->line("  ✅ {$requirement}");
            } else {
                $this->line("  ❌ {$requirement}");
                $allPassed = false;
            }
        }

        if (! $allPassed) {
            $this->error('Some system requirements are not met. Please install the missing components.');

            return false;
        }

        return true;
    }

    private function commandExists(string $command): bool
    {
        $result = Process::run("which {$command}");

        return $result->successful();
    }

    private function setupEnvironment(): bool
    {
        $this->info('⚙️  Setting up environment...');

        // Copy .env.example if .env doesn't exist
        if (! File::exists(base_path('.env'))) {
            if (! File::exists(base_path('.env.example'))) {
                $this->error('.env.example file not found!');

                return false;
            }

            File::copy(base_path('.env.example'), base_path('.env'));
            $this->line('  ✅ Created .env file from .env.example');
        }

        // Generate application key if not set
        if (! config('app.key')) {
            Artisan::call('key:generate', ['--force' => true]);
            $this->line('  ✅ Generated application key');
        }

        // Configure database if using quick setup
        if ($this->option('quick')) {
            $this->configureQuickDatabase();
        } else {
            $this->configureDatabaseInteractively();
        }

        return true;
    }

    private function configureQuickDatabase(): void
    {
        $this->info('🗄️  Using quick SQLite setup...');

        $envContent = File::get(base_path('.env'));

        // Ensure database directory exists
        if (! File::exists(database_path())) {
            File::makeDirectory(database_path(), 0755, true);
        }

        // Create SQLite database file if it doesn't exist
        $dbPath = database_path('database.sqlite');
        if (! File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        $dbConfig = [
            'DB_CONNECTION=sqlite',
            'DB_HOST=127.0.0.1',
            'DB_PORT=5432',
            "DB_DATABASE={$dbPath}",
            'DB_USERNAME=root',
            'DB_PASSWORD=',
        ];

        foreach ($dbConfig as $config) {
            [$key, $value] = explode('=', $config, 2);
            $envContent = preg_replace("/^{$key}=.*/m", $config, $envContent);
        }

        File::put(base_path('.env'), $envContent);
        $this->line('  ✅ SQLite database configuration updated');
    }

    private function configureDatabaseInteractively(): void
    {
        $this->info('🗄️  Database Configuration');
        $this->line('Configure your database connection:');
        $this->newLine();

        $connection = $this->choice('Database type', ['sqlite', 'pgsql', 'mysql'], 'sqlite');

        if ($connection === 'sqlite') {
            // Ensure database directory exists
            if (! File::exists(database_path())) {
                File::makeDirectory(database_path(), 0755, true);
            }

            // Create SQLite database file if it doesn't exist
            $dbPath = database_path('database.sqlite');
            if (! File::exists($dbPath)) {
                File::put($dbPath, '');
            }

            $host = '127.0.0.1';
            $port = '5432';
            $database = $dbPath;
            $username = 'root';
            $password = '';
        } else {
            $host = $this->ask('Database host', '127.0.0.1');
            $port = $this->ask('Database port', $connection === 'pgsql' ? '5432' : '3306');
            $database = $this->ask('Database name', 'relaticle');
            $username = $this->ask('Database username', $connection === 'pgsql' ? 'postgres' : 'root');
            $password = $this->secret('Database password (leave empty if none)');
        }

        // Update .env file
        $envContent = File::get(base_path('.env'));

        $dbConfig = [
            "DB_CONNECTION={$connection}",
            "DB_HOST={$host}",
            "DB_PORT={$port}",
            "DB_DATABASE={$database}",
            "DB_USERNAME={$username}",
            "DB_PASSWORD={$password}",
        ];

        foreach ($dbConfig as $config) {
            [$key, $value] = explode('=', $config, 2);
            $envContent = preg_replace("/^{$key}=.*/m", $config, $envContent);
        }

        File::put(base_path('.env'), $envContent);

        // Test database connection
        if (! $this->testDatabaseConnection()) {
            $this->warn('Database connection test failed. Please check your configuration.');
            if (! $this->confirm('Continue anyway?')) {
                exit(1);
            }
        } else {
            $this->line('  ✅ Database connection successful');
        }
    }

    private function testDatabaseConnection(): bool
    {
        try {
            // Reload config to pick up new database settings
            config(['database.default' => config('database.default')]);
            DB::purge();
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function installDependencies(): bool
    {
        $this->info('📦 Installing dependencies...');

        // Composer install
        $this->line('  Installing PHP dependencies...');
        $result = Process::run('composer install --no-interaction --prefer-dist --optimize-autoloader');

        if (! $result->successful()) {
            $this->error('Composer install failed:');
            $this->line($result->errorOutput());

            return false;
        }

        // NPM install
        $this->line('  Installing Node.js dependencies...');
        $result = Process::run('npm ci');

        if (! $result->successful()) {
            $this->error('NPM install failed:');
            $this->line($result->errorOutput());

            return false;
        }

        $this->line('  ✅ Dependencies installed successfully');

        return true;
    }

    private function setupDatabase(): bool
    {
        $this->info('🗄️  Setting up database...');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->line('  ✅ Database migrations completed');

            return true;
        } catch (Throwable $e) {
            $this->error('Database setup failed: '.$e->getMessage());

            return false;
        }
    }

    private function buildAssets(): bool
    {
        $this->info('🎨 Building frontend assets...');

        $result = Process::run('npm run build');

        if (! $result->successful()) {
            $this->error('Asset compilation failed:');
            $this->line($result->errorOutput());

            return false;
        }

        $this->line('  ✅ Assets compiled successfully');

        return true;
    }

    private function setupStorage(): bool
    {
        $this->info('💾 Setting up storage...');

        try {
            Artisan::call('storage:link');
            $this->line('  ✅ Storage symlink created');

            return true;
        } catch (Throwable $e) {
            $this->error('Storage setup failed: '.$e->getMessage());

            return false;
        }
    }

    private function seedDemoData(): void
    {
        $this->info('🌱 Seeding demo data...');

        try {
            Artisan::call('db:seed');
            $this->line('  ✅ Demo data seeded successfully');
        } catch (Throwable $e) {
            $this->warn('Demo data seeding failed: '.$e->getMessage());
        }
    }

    private function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('🎉 Relaticle installation completed successfully!');
        $this->newLine();

        $this->line('Next steps:');
        $this->line('  1. Start all development services: composer run dev');
        $this->line('  2. Visit http://localhost:8000 to access Relaticle');
        $this->newLine();

        $this->info('Happy CRM-ing! 🚀');
    }
}
