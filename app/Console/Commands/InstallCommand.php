<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

final class InstallCommand extends Command
{
    protected $signature = 'relaticle:install
                            {--force : Force installation even if already configured}
                            {--quick : Use default settings for rapid setup}
                            {--dev : Enable development mode with demo data}';

    protected $description = 'Install and configure Relaticle';

    /** @var array<string, callable(): bool> */
    private array $installationSteps = [];

    public function handle(): int
    {
        $this->displayWelcome();

        if (! $this->shouldProceed()) {
            return self::SUCCESS;
        }

        $config = $this->gatherConfiguration();

        return $this->runInstallation($config);
    }

    private function displayWelcome(): void
    {
        $this->output->write(PHP_EOL.'  <fg=cyan>
 ____      _       _   _      _
|  _ \ ___| | __ _| |_(_) ___| | ___
| |_) / _ \ |/ _` | __| |/ __| |/ _ \
|  _ <  __/ | (_| | |_| | (__| |  __/
|_| \_\___|_|\__,_|\__|_|\___|_|\___|</>'.PHP_EOL.PHP_EOL);
    }

    private function shouldProceed(): bool
    {
        if (! $this->option('force') && $this->isAlreadyInstalled()) {
            warning('Relaticle appears to be already installed.');

            return confirm(
                label: 'Do you want to continue anyway?',
                default: false,
                hint: 'This may overwrite existing configuration'
            );
        }

        return true;
    }

    private function isAlreadyInstalled(): bool
    {
        return File::exists(base_path('.env')) &&
               config('app.key') &&
               File::exists(public_path('storage'));
    }

    /** @return array<string, mixed> */
    private function gatherConfiguration(): array
    {
        $this->info('Let\'s configure your Relaticle installation...');

        if ($this->option('quick')) {
            return $this->getQuickConfiguration();
        }

        return $this->getInteractiveConfiguration();
    }

    /** @return array<string, mixed> */
    private function getQuickConfiguration(): array
    {
        $this->info('⚡ Using quick setup with smart defaults...');

        return [
            'database' => 'sqlite',
            'demo_data' => $this->option('dev'),
            'install_demo' => $this->option('dev'),
            'optimize' => ! $this->option('dev'),
        ];
    }

    /** @return array<string, mixed> */
    private function getInteractiveConfiguration(): array
    {
        $database = select(
            label: 'Which database would you like to use?',
            options: [
                'sqlite' => 'SQLite (Recommended for development)',
                'pgsql' => 'PostgreSQL (Recommended for production)',
                'mysql' => 'MySQL/MariaDB',
            ],
            default: 'sqlite',
            hint: 'SQLite requires no additional setup'
        );

        $features = multiselect(
            label: 'Select additional features to install:',
            options: [
                'demo_data' => 'Demo data (Sample companies, contacts, etc.)',
                'optimize' => 'Production optimizations (Caching, etc.)',
                'admin_user' => 'Create admin user account',
            ],
            default: $this->option('dev') ? ['demo_data', 'admin_user'] : ['optimize'],
            hint: 'You can change these later'
        );

        return [
            'database' => $database,
            'demo_data' => in_array('demo_data', $features),
            'optimize' => in_array('optimize', $features),
            'admin_user' => in_array('admin_user', $features),
        ];
    }

    /** @param array<string, mixed> $config */
    private function runInstallation(array $config): int
    {
        $this->installationSteps = [
            'System Requirements' => fn (): bool => $this->checkSystemRequirements(),
            'Environment Setup' => fn (): bool => $this->setupEnvironment($config),
            'Dependencies' => fn (): bool => $this->installDependencies(),
            'Database' => fn (): bool => $this->setupDatabase(),
            'Assets' => fn (): bool => $this->buildAssets(),
            'Storage' => fn (): bool => $this->setupStorage(),
            'Demo Data' => fn (): bool => $config['demo_data'] ? $this->seedDemoData() : true,
            'Optimization' => fn (): bool => $config['optimize'] ? $this->optimizeInstallation() : true,
            'Admin User' => fn (): bool => $config['admin_user'] ?? false ? $this->createAdminUser() : true,
        ];

        $this->info('🚀 Starting installation process...');

        foreach ($this->installationSteps as $stepName => $stepFunction) {
            $success = spin(
                callback: fn (): mixed => $stepFunction(),
                message: "Installing {$stepName}..."
            );

            if (! $success) {
                $this->newLine();
                $this->error("❌ Installation failed during: {$stepName}");
                $this->newLine();
                $this->line('<comment>Check the error messages above for more details.</comment>');
                $this->line('<comment>You can re-run the installer with --force to retry.</comment>');

                return self::FAILURE;
            }

            $this->line("   ✅ {$stepName} completed");
        }

        $this->newLine();
        $this->displaySuccessMessage($config);

        return self::SUCCESS;
    }

    private function checkSystemRequirements(): bool
    {
        $requirements = [
            'PHP 8.3+' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'Composer' => $this->commandExists('composer'),
            'Node.js' => $this->commandExists('node'),
            'NPM' => $this->commandExists('npm'),
        ];

        $extensions = [
            'pdo_sqlite', 'gd', 'bcmath', 'ctype', 'fileinfo',
            'json', 'mbstring', 'openssl', 'tokenizer', 'xml',
        ];

        foreach ($extensions as $extension) {
            $requirements["PHP {$extension}"] = extension_loaded($extension);
        }

        $failed = array_filter($requirements, fn (bool $passed): bool => ! $passed);

        if ($failed !== []) {
            $this->newLine();
            $this->error('✗ Missing system requirements:');
            foreach (array_keys($failed) as $requirement) {
                $this->line("  • {$requirement}");
            }
            $this->newLine();
            $this->line('<comment>Please install the missing requirements and try again.</comment>');

            return false;
        }

        return true;
    }

    private function commandExists(string $command): bool
    {
        return Process::run("which {$command} 2>/dev/null")->successful();
    }

    /** @param array<string, mixed> $config */
    private function setupEnvironment(array $config): bool
    {
        // Create .env file
        if (! File::exists(base_path('.env'))) {
            if (! File::exists(base_path('.env.example'))) {
                return false;
            }
            File::copy(base_path('.env.example'), base_path('.env'));
        }

        // Generate app key
        if (! config('app.key')) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // Configure database
        return $this->configureDatabaseConnection($config['database']);
    }

    private function configureDatabaseConnection(string $type): bool
    {
        $envContent = File::get(base_path('.env'));

        if ($type === 'sqlite') {
            if (! File::exists(database_path())) {
                File::makeDirectory(database_path(), 0755, true);
            }

            $dbPath = database_path('database.sqlite');
            if (! File::exists($dbPath)) {
                File::put($dbPath, '');
            }

            $config = [
                'DB_CONNECTION=sqlite',
                "DB_DATABASE={$dbPath}",
                'DB_HOST=',
                'DB_PORT=',
                'DB_USERNAME=',
                'DB_PASSWORD=',
            ];
        } else {
            // For non-SQLite, we'll use the existing .env values or prompt if needed
            return true;
        }

        foreach ($config as $line) {
            [$key, $value] = explode('=', $line, 2);
            $envContent = preg_replace("/^{$key}=.*/m", $line, (string) $envContent);
        }

        File::put(base_path('.env'), $envContent);

        return true;
    }

    private function installDependencies(): bool
    {
        $composerResult = Process::run('composer install --no-interaction --prefer-dist --optimize-autoloader');
        if (! $composerResult->successful()) {
            $this->error('Composer install failed:');
            $this->line($composerResult->errorOutput());

            return false;
        }

        $npmResult = Process::run('npm ci --silent');
        if (! $npmResult->successful()) {
            $this->error('NPM install failed:');
            $this->line($npmResult->errorOutput());

            return false;
        }

        return true;
    }

    private function setupDatabase(): bool
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return true;
        } catch (Throwable $e) {
            $this->error('Database migration failed:');
            $this->line($e->getMessage());

            return false;
        }
    }

    private function buildAssets(): bool
    {
        $result = Process::run('npm run build');

        if (! $result->successful()) {
            $this->error('Asset compilation failed:');
            $this->line($result->errorOutput());

            return false;
        }

        return true;
    }

    private function setupStorage(): bool
    {
        try {
            Artisan::call('storage:link', ['--force' => true]);

            return true;
        } catch (Throwable $e) {
            $this->error('Storage link failed:');
            $this->line($e->getMessage());

            return false;
        }
    }

    private function seedDemoData(): bool
    {
        try {
            Artisan::call('db:seed', ['--force' => true]);

            return true;
        } catch (Throwable $e) {
            $this->error('Demo data seeding failed:');
            $this->line($e->getMessage());

            return false;
        }
    }

    private function optimizeInstallation(): bool
    {
        try {
            // Clear any existing caches first
            Artisan::call('optimize:clear');

            // Only cache config and routes for production optimization
            // Skip view:cache as it can fail with missing components during installation
            Artisan::call('config:cache');
            Artisan::call('route:cache');

            // Only cache views if we're in production environment
            if (app()->environment('production')) {
                Artisan::call('view:cache');
            }

            return true;
        } catch (Throwable $e) {
            $this->error('Optimization failed:');
            $this->line($e->getMessage());

            return false;
        }
    }

    private function createAdminUser(): bool
    {
        try {
            $name = text(
                label: 'Admin user name',
                default: 'Admin User',
                required: true
            );

            $email = text(
                label: 'Admin email address',
                default: 'admin@relaticle.local',
                required: true,
                validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address'
            );

            $password = text(
                label: 'Admin password',
                default: 'password',
                required: true
            );

            Artisan::call('make:filament-user', [
                '--name' => $name,
                '--email' => $email,
                '--password' => $password,
            ]);

            return true;
        } catch (Throwable $e) {
            $this->error('Admin user creation failed:');
            $this->line($e->getMessage());

            return false;
        }
    }

    /** @param array<string, mixed> $config */
    private function displaySuccessMessage(array $config): void
    {
        $this->newLine();

        $this->info('🎉 Relaticle installed successfully!');

        $this->newLine();
        $this->line('  <options=bold>Start all development services:</>');
        $this->line('  composer run dev');
        $this->newLine();

        $this->line('  <options=bold>Your application:</>');
        $this->line('  http://localhost:8000');

        if ($config['admin_user'] ?? false) {
            $this->line('  <options=bold>Admin panel:</>');
            $this->line('  http://localhost:8000/admin');
        }

        if ($config['demo_data']) {
            $this->newLine();
            $this->line('  <options=bold>Demo data included:</>');
            $this->line('  • Sample companies and contacts');
            $this->line('  • Example opportunities and tasks');
            $this->line('  • Pre-configured custom fields');
        }

        $this->newLine();
        $this->line('  <options=bold>Development services:</>');
        $this->line('  • Laravel development server');
        $this->line('  • Vite asset watcher with HMR');
        $this->line('  • Queue worker (Horizon)');
        $this->line('  • Real-time logs (Pail)');

        $this->newLine();
        $this->line('  Documentation: https://relaticle.com/documentation');
        $this->newLine();

        $this->info('Happy CRM-ing! 🚀');
    }
}
