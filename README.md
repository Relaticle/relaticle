<p align="center">
  <a href="https://relaticle.com">
    <img src="https://relaticle.com/relaticle-logo.svg" width="100px" alt="Relaticle logo" />
  </a>
</p>

<h2 align="center" >The Next-Generation Open-Source CRM Platform</h3>

<p align="center"><a href="https://relaticle.com">🌐 Website</a> · <a href="https://relaticle.com/documentation">📚 Documentation</a> · <a href="https://github.com/orgs/Relaticle/projects/1/views/1">🛣️ Roadmap </a>
<p>
<br />


<p align="center">
  <a href="https://www.relaticle.com">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://relaticle.com/images/app-preview.png?a=1">
      <source media="(prefers-color-scheme: light)" srcset="https://relaticle.com/images/app-preview.png?a=1">
      <img src="https://relaticle.com/images/app-preview.png?a=1" alt="Companies view" />
    </picture>
  </a>
</p>

## Documentation

Relaticle comes with comprehensive documentation:

- [Business Guide](resources/markdown/business-guide.md) - Overview of Relaticle from a business perspective
- [Quick Start Guide](resources/markdown/quick-start-guide.md) - Step-by-step guide for new users
- [Technical Guide](resources/markdown/technical-guide.md) - Architecture and development information
- [API Documentation](resources/markdown/api-guide.md) - Integration with the Relaticle API

## Premium Component

Relaticle includes one premium component in its ecosystem - the **Data Model (Custom Fields)** plugin. This Filament plugin is developed by Relaticle and provides powerful dynamic data management capabilities throughout the application.

For detailed information about this component, see:
- **Documentation**: [custom-fields.relaticle.com/introduction](https://custom-fields.relaticle.com/introduction)
- **Marketplace**: [filamentphp.com/plugins/relaticle-custom-fields](https://filamentphp.com/plugins/relaticle-custom-fields)

This is the only paid component in the Relaticle ecosystem and represents a significant intellectual property investment that enables the platform's flexible data modeling capabilities.

## Installation

Relaticle is a regular Laravel application; it's build on top of Laravel 12 / Filament 3 and uses Livewire / Tailwind
CSS for the frontend. If you are familiar with Laravel, you should feel right at home.

In terms of local development, you can use the following requirements:

- PHP 8.3 - with Postgres, GD, and other common extensions.
- Node.js 16 or more recent.

If you have these requirements, you can start by cloning the repository and installing the dependencies:

```bash
git clone https://github.com/Relaticle/relaticle.git

cd relaticle

git checkout -b feat/your-feature # or fix/your-fix
```

> **Don't push directly to the `main` branch**. Instead, create a new branch and push it to your branch.

Next, install the dependencies using [Composer](https://getcomposer.org) and [NPM](https://www.npmjs.com):

```bash
composer install

npm install
```

After that, set up your `.env` file:

```bash
cp .env.example .env

php artisan key:generate
```

Run the migrations:

```bash
php artisan migrate
```

Link the storage to the public folder:

```bash
php artisan storage:link
```

In a **separate terminal**, build the assets in watch mode:

```bash
npm run dev
```

Also in a **separate terminal**, run the queue worker:

```bash
php artisan queue:work
```

Finally, start the development server:

```bash
php artisan serve
```

> Note: By default, emails are sent to the `log` driver. You can change this in the `.env` file to something like
`mailtrap`.

Once you are done with the code changes, be sure to run the test suite to ensure everything is still working:

```bash
composer test
```

If everything is green, push your branch and create a pull request:

```bash
git commit -am "Your commit message"

git push
```

Visit [github.com/Relaticle/relaticle/pulls](https://github.com/Relaticle/relaticle/pulls) and create a pull request.

## Tooling

Relaticle uses a few tools to ensure the code quality and consistency. Of course, [Pest](https://pestphp.com) is the
testing framework of choice, and we also use [PHPStan](https://phpstan.org) for static analysis. Pest's type coverage is
at 100%, and the test suite is also at 100% coverage.

In terms of code style, we use [Laravel Pint](https://laravel.com/docs/12.x/pint) to ensure the code is consistent and
follows the Laravel conventions. We also use [Rector](https://getrector.org) to ensure the code is up to date with the
latest PHP version.

You run these tools individually using the following commands:

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

# Run all the tools
composer test
```

Pull requests that don't pass the test suite will not be merged. So, as suggested on the [Installation](#installation)
section, be sure to run the test suite before pushing your branch.

## Git Hooks

Relaticle uses [Git Hooks](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks) to ensure the code quality and
consistency. The hooks are located in the `.githooks` directory, and you can enable them by running the following
command:

```bash
git config core.hooksPath .githooks.
```
