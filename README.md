<p align="center">
  <a href="https://relaticle.com">
    <img src="https://relaticle.com/relaticle-logo.svg" width="100px" alt="Relaticle logo" />
  </a>
</p>

<h1 align="center">Relaticle</h1>
<h3 align="center">The Next-Generation Open-Source CRM Platform</h3>

<p align="center">
  <a href="https://laravel.com/docs/12.x"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel" alt="Laravel 12"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php" alt="PHP 8.3"></a>
  <a href="https://github.com/Relaticle/relaticle/blob/main/LICENSE"><img src="https://img.shields.io/badge/License-GPL--3.0-blue.svg?style=flat-square" alt="License"></a>
  <a href="https://github.com/Relaticle/relaticle/actions"><img src="https://img.shields.io/github/actions/workflow/status/Relaticle/relaticle/tests.yml?branch=main&style=flat-square&label=tests" alt="Tests"></a>
</p>

<p align="center">
  <a href="https://relaticle.com">🌐 Website</a> ·
  <a href="https://relaticle.com/documentation">📚 Documentation</a> ·
  <a href="https://github.com/orgs/Relaticle/projects/1/views/1">🛣️ Roadmap</a> ·
  <a href="#contributing">👥 Contributing</a>
</p>

<hr>

## 🚀 Introduction

Relaticle is a powerful, flexible CRM platform built for modern businesses. Unlike traditional CRM solutions that force you to adapt your workflows to their systems, Relaticle adapts to how *your* team actually works.

**Key advantages:**
- 💪 **Fully Customizable Data Model** - Create custom fields, relationships, and workflows without coding
- 🔄 **Seamless Integration** - Connect with your existing tools through our comprehensive API
- 🚀 **Built for Performance** - Engineered to handle enterprise-scale data without sacrificing speed
- 🔒 **Privacy-Focused** - Self-host on your infrastructure with complete data ownership
- 🌱 **Open Source** - Transparent, community-driven development with GPL-3.0 license

<p align="center">
  <a href="https://www.relaticle.com">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://relaticle.com/images/app-preview.png?a=1">
      <source media="(prefers-color-scheme: light)" srcset="https://relaticle.com/images/app-preview.png?a=1">
      <img src="https://relaticle.com/images/app-preview.png?a=1" alt="Relaticle Dashboard" style="max-width: 100%; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);" />
    </picture>
  </a>
</p>

## ✨ Features

- **360° Customer View** - Unified profiles combining contact details, interaction history, and opportunities
- **Dynamic Pipelines** - Customizable sales funnels that adapt to your unique sales process
- **Task Management** - Track and prioritize work across your team with smart assignments
- **Rich Reporting** - Data-driven insights with customizable dashboards and exportable reports
- **User Permissions** - Fine-grained access control to protect sensitive customer data
- **White-Labeling** - Fully rebrand the platform for your organization or clients
- **Multi-Team Support** - Securely manage multiple business units with team isolation

## 🏁 Quick Start

### Prerequisites

- PHP 8.3+ with PostgreSQL, GD, and required extensions
- Node.js 16+
- Composer

### Installation in 5 Minutes

```bash
# Clone the repository
git clone https://github.com/Relaticle/relaticle.git
cd relaticle

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations and link storage
php artisan migrate
php artisan storage:link

# Build assets and start server
npm run dev
php artisan serve
```

Visit `http://localhost:8000` in your browser and create your admin account to get started!

For detailed setup instructions, including production deployment, see our [Technical Guide](resources/markdown/documentation/technical-guide.md).

## 📚 Documentation

Relaticle comes with comprehensive documentation to help you get the most out of the platform:

- [Business Guide](resources/markdown/documentation/business-guide.md) - Overview of Relaticle from a business perspective
- [Quick Start Guide](resources/markdown/documentation/quick-start-guide.md) - Step-by-step guide for new users
- [Technical Guide](resources/markdown/documentation/technical-guide.md) - Architecture and development information
- [API Documentation](resources/markdown/documentation/api-guide.md) - Integration with the Relaticle API

## 🧩 Premium Component

While Relaticle is fully open-source, we offer one premium component that significantly enhances its capabilities:

**Data Model (Custom Fields)** - This Filament plugin provides powerful dynamic data management throughout the application, enabling unlimited customization possibilities without coding.

- [Documentation](https://custom-fields.relaticle.com/introduction)
- [Marketplace](https://filamentphp.com/plugins/relaticle-custom-fields)

This is the only paid component in the Relaticle ecosystem and supports ongoing development of the open-source platform.

## 🤝 Contributing

We welcome contributions from developers of all skill levels! Here's how to get started:

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/amazing-feature`
3. Make your changes following our coding standards
4. Run tests to ensure quality: `composer test`
5. Commit your changes: `git commit -am 'Add some amazing feature'`
6. Push to your branch: `git push origin feat/amazing-feature`
7. Create a Pull Request

For more details, see our [Contributing Guide](CONTRIBUTING.md).

## 🌟 Community

- Join our [Discord community](https://discord.gg/relaticle)
- Follow us on [Twitter](https://twitter.com/relaticle)
- Subscribe to our [YouTube channel](https://youtube.com/relaticle) for tutorials

## 📝 License

Relaticle is open-sourced software licensed under the [GPL-3.0 license](LICENSE).
