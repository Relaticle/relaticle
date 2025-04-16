# Technical Guide

## Architecture Overview

Relaticle is built on the Laravel 12 framework with Filament 3 for the admin panel interface. The front-end uses Livewire and Tailwind CSS to create a responsive and interactive user experience.

### Tech Stack

- **Backend**: PHP 8.3, Laravel 12
- **Admin UI**: Filament 3
- **Frontend**: Livewire, Tailwind CSS
- **Database**: PostgreSQL
- **Testing**: Pest
- **Static Analysis**: PHPStan
- **Code Quality**: Laravel Pint, Rector

### Paid Dependencies

Relaticle includes one premium component in its technology stack:

**Data Model (Custom Fields)** - A powerful Filament plugin **developed by Relaticle** that serves as the backbone for dynamic data management throughout the application.

- **Documentation**: [custom-fields.relaticle.com/introduction](https://custom-fields.relaticle.com/introduction)
- **Marketplace**: [filamentphp.com/plugins/relaticle-custom-fields](https://filamentphp.com/plugins/relaticle-custom-fields)

As the creators of this plugin, we've engineered it specifically to address the limitations of existing solutions. The Custom Fields package is commercial for several reasons:

1. It represents thousands of development hours and specialized expertise
2. Ongoing maintenance and regular updates ensure compatibility with the Filament ecosystem
3. The commercial model supports dedicated customer service and technical support
4. Enterprise-grade performance optimizations for handling complex data structures
5. Regular feature additions based on real-world customer feedback

This is the **only** paid component in the Relaticle ecosystem. While we're committed to open source, this particular module represents a significant intellectual property investment that enables Relaticle to deliver unparalleled flexibility in data modeling without sacrificing performance or user experience.

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

## Development Guidelines

### Coding Standards

Relaticle follows Laravel's coding standards and conventions. We enforce these through:

- **Laravel Pint**: Ensures consistent code styling
- **Rector**: Refactors code to use modern PHP features
- **PHPStan**: Validates type correctness and prevents common issues
- **Pest**: Ensures code quality through comprehensive tests

### Git Workflow

1. Create feature branches from `main` (e.g., `feat/your-feature`)
2. Develop and test your changes locally
3. Run the full test suite with `composer test`
4. Push your branch and create a pull request
5. Wait for CI checks and code review before merging

### Testing Requirements

All code contributions should include:

- Unit tests for new functionality
- Feature tests for user interactions
- Passing static analysis

## Setup for Development

Follow these steps to set up your local development environment:

1. Clone the repository
2. Install PHP 8.3 with required extensions
3. Install Node.js 16+
4. Set up PostgreSQL database
5. Configure environment variables in `.env`
6. Run migrations and seed the database
7. Start development server and asset compilation

For detailed installation steps, refer to the README.md file in the project root.

## Customization and Extension

### Adding New Features

When adding new features:

1. Start by creating the necessary models and migrations
2. Implement business logic in dedicated Service classes
3. Create Filament resources for admin panel integration
4. Add Livewire components for frontend interactions
5. Write comprehensive tests
6. Document the feature in relevant documentation

## Performance Considerations

- Use eager loading to prevent N+1 query issues
- Keep frontend dependencies minimal

## Security Best Practices

- All user input must be validated
- Follow Laravel's authentication and authorization patterns
- Implement proper CSRF protection

## Deployment

The recommended deployment method is:

1. Environment-specific configuration
2. Regular database backups 
