# Technical Guide

## Architecture Overview

Relaticle is built on the Laravel 12 framework with Filament 3 for the admin panel interface. The front-end uses
Livewire and Tailwind CSS to create a responsive and interactive user experience.

### Tech Stack

- **Backend**: PHP 8.3, Laravel 12
- **Admin UI**: Filament 3
- **Frontend**: Livewire, Tailwind CSS
- **Database**: PostgreSQL
- **Testing**: Pest
- **Static Analysis**: PHPStan
- **Code Quality**: Laravel Pint, Rector
- **Task Queue**: Laravel Horizon
- **Authentication**: Laravel Jetstream

### Paid Dependencies

Relaticle includes one premium component in its technology stack:

**Data Model (Custom Fields)** - A powerful Filament plugin **developed by Relaticle** that serves as the backbone for
dynamic data management throughout the application.

- **Documentation**: [custom-fields.relaticle.com/introduction](https://custom-fields.relaticle.com/introduction)
- **Marketplace**: [filamentphp.com/plugins/relaticle-custom-fields](https://filamentphp.com/plugins/relaticle-custom-fields)

As the creators of this plugin, we've engineered it specifically to address the limitations of existing solutions. The
Custom Fields package is commercial for several reasons:

1. It represents thousands of development hours and specialized expertise
2. Ongoing maintenance and regular updates ensure compatibility with the Filament ecosystem
3. The commercial model supports dedicated customer service and technical support
4. Enterprise-grade performance optimizations for handling complex data structures
5. Regular feature additions based on real-world customer feedback

This is the **only** paid component in the Relaticle ecosystem. While we're committed to open source, this particular
module represents a significant intellectual property investment that enables Relaticle to deliver unparalleled
flexibility in data modeling without sacrificing performance or user experience.

## Core Components

### Models

Relaticle's data structure revolves around these key models:

- **Team**: The organizational unit that groups users together
- **User**: System users with authentication and permissions
- **Company**: Organizations your business interacts with
- **People**: Individual contacts at companies
- **Opportunity**: Potential deals in the sales pipeline
- **Task**: Actionable items assigned to users
- **Note**: Documentation of interactions and important information

### Relationships

- Teams have many Users (through Memberships)
- Companies belong to Teams
- Companies have many People
- Companies have many Opportunities
- People belong to Companies
- Opportunities belong to Companies
- Tasks can be associated with various models (polymorphic)
- Notes can be associated with various models (polymorphic)

## Development Environment

### System Requirements

To develop Relaticle locally, you'll need:

- **PHP 8.3+** with the following extensions:
    - pdo_pgsql
    - gd
    - bcmath
    - ctype
    - fileinfo
    - json
    - mbstring
    - openssl
    - tokenizer
    - xml
- **PostgreSQL 13+**
- **Node.js 16+** with npm
- **Composer 2+**

### Detailed Installation Steps

1. **Clone the repository**

```bash
git clone https://github.com/Relaticle/relaticle.git
cd relaticle
```

2. **Create a feature branch**

```bash
git checkout -b feat/your-feature # or fix/your-fix
```

> **Important:** Don't push directly to the `main` branch. Instead, create a new branch and open a pull request.

3. **Install dependencies**

```bash
composer install
npm install
```

4. **Configure environment**

```bash
cp .env.example .env
php artisan key:generate
```

Open the `.env` file and configure your database connection:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=relaticle
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

5. **Run migrations**

```bash
php artisan migrate
```

6. **Link storage**

```bash
php artisan storage:link
```

7. **Start development services**

In separate terminal windows, run:

```bash
# Terminal 1: Asset compilation with hot reload
npm run dev

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Development server
php artisan serve
```

   Visit `http://localhost:8000` in your browser to access the application.

   > **Note:** By default, emails are sent to the `log` driver. You can change this in the `.env` file to something like
   `mailtrap` for development.

## Development Guidelines

### Coding Standards

Relaticle follows Laravel's coding standards and conventions. We enforce these through:

- **Laravel Pint**: Ensures consistent code styling
- **Rector**: Refactors code to use modern PHP features
- **PHPStan**: Validates type correctness and prevents common issues
- **Pest**: Ensures code quality through comprehensive tests

### Quality Assurance Tools

Relaticle uses several tools to maintain code quality:

```bash
# Lint the code using Pint
composer lint
composer test:lint

# Refactor the code using Rector
composer refactor
composer test:refactor

# Run PHPStan
composer test:types

# Run the test suite
composer test:unit

# Run test architecture checks
composer test:arch

# Run type coverage checks (must be 99.6%+)
composer test:type-coverage

# Run all quality checks
composer test
```

> Pull requests that don't pass the test suite will not be merged. Always run `composer test` before submitting your
> changes.

### Git Hooks

Relaticle uses Git Hooks to automate quality checks during the development process. The hooks are located in the
`.githooks` directory, and you can enable them by running:

```bash
git config core.hooksPath .githooks
```

This will automatically run the appropriate checks when you commit or push code.

### Git Workflow

1. Create feature branches from `main` (e.g., `feat/your-feature` or `fix/your-fix`)
2. Develop and test your changes locally
3. Run the full test suite with `composer test`
4. Commit your changes with a descriptive message
5. Push your branch and create a pull request
6. Wait for CI checks and code review before merging

### Testing Requirements

All code contributions should include:

- Unit tests for new functionality
- Feature tests for user interactions
- Passing static analysis checks
- Minimum 99.6% type coverage

## Customization and Extension

### Adding New Features

When adding new features:

1. Start by creating the necessary models and migrations
2. Implement business logic in dedicated Service classes
3. Create Filament resources for admin panel integration
4. Add Livewire components for frontend interactions
5. Write comprehensive tests
6. Document the feature in relevant documentation

### Creating Custom Components

The Filament ecosystem makes it easy to create custom components:

```php
namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    
    // Resource configuration...
}
```

## Performance Considerations

- Use eager loading to prevent N+1 query issues
- Keep frontend dependencies minimal
- Optimize database indexes for common queries
- Use Laravel queues for processing background tasks
- Implement caching for expensive operations

## Security Best Practices

- All user input must be validated
- Follow Laravel's authentication and authorization patterns
- Implement proper CSRF protection
- Use Laravel Sanctum for API authentication
- Regularly update dependencies for security patches

## Deployment

### Production Deployment Checklist

1. Configure environment-specific variables
2. Set up a production database with proper credentials
3. Configure a robust caching system (Redis recommended)
4. Set up a queue worker using Supervisor
5. Configure a web server (Nginx recommended)
6. Set up SSL certificates
7. Configure proper backups
8. Set up monitoring tools

### Deployment Process

1. Pull latest code from the repository
2. Install production dependencies: `composer install --no-dev --optimize-autoloader`
3. Build frontend assets: `npm ci && npm run build`
4. Run migrations: `php artisan migrate --force`
5. Clear and rebuild caches: `php artisan optimize`
6. Restart queue workers: `php artisan queue:restart`

### Server Requirements

- PHP 8.3+ with required extensions
- PostgreSQL 13+
- Redis (recommended for caching and queues)
- Nginx or Apache
- Supervisor (for managing queue workers)

## Troubleshooting

### Common Issues

#### Queue Worker Not Processing Jobs

```bash
# Check queue status
php artisan queue:status

# Restart queue worker
php artisan queue:restart
```

#### File Upload Issues

Ensure proper permissions on the storage directory:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Slow Database Queries

Use Laravel Telescope or Clockwork to identify slow queries, then add appropriate indexes.

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Filament Documentation](https://filamentphp.com/docs)
- [Livewire Documentation](https://livewire.laravel.com/)
- [Pest Testing Framework](https://pestphp.com/) 
