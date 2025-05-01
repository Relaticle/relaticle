# Documentation Module

The Documentation module provides a flexible and modular documentation system for Relaticle, allowing you to present markdown-based documentation with a clean, interactive UI.

## Overview

This module includes:

- A dedicated controller for serving documentation content
- A view system for rendering documentation with responsive layout
- Markdown storage and processing
- TOC (Table of Contents) generation with automatic scrollspy
- Mobile-friendly navigation

## Installation

The module is automatically loaded by the application through the service provider registration in `bootstrap/providers.php`.

## Configuration

No additional configuration is required. The module works out of the box.

## Usage

### Accessing Documentation

Documentation can be accessed at these URLs:

- `/documentation` - Main documentation index
- `/documentation/{type}` - Specific documentation types (e.g., technical, business, etc.)

### Adding New Documentation

To add a new documentation type:

1. Edit the `DocumentationController.php` file in the module's `src/Http/Controllers` directory
2. Add your new type to the `$validTypes` array:

```php
$validTypes = [
    // ... existing types
    'your-type' => [
        'title' => 'Your Type Title',
        'file' => 'your-type-guide.md',
    ],
];
```

3. Create a corresponding Markdown file in `resources/markdown` directory

## Extending the Module

### Publishing Resources

To customize the module's views or markdown files:

```bash
php artisan vendor:publish --tag=documentation-views
php artisan vendor:publish --tag=documentation-markdown
```

### Customizing the Look and Feel

The module uses a dedicated view at `resources/views/index.blade.php` that can be customized after publishing.

### Adding New Features

To extend the module with new features:

1. Create a new service provider that extends or decorates the DocumentationServiceProvider
2. Override the necessary methods or add new functionality
3. Register your provider in `bootstrap/providers.php` after the DocumentationServiceProvider

## Structure

```
app-modules/Documentation/
├── resources/
│   ├── markdown/          # Markdown documentation files
│   └── views/             # Blade templates
├── routes/
│   └── web.php            # Module routes
└── src/
    ├── DocumentationServiceProvider.php     # Service provider
    └── Http/
        └── Controllers/
            └── DocumentationController.php  # Controller
```

## Contributing

Contributions to the Documentation module are welcome. Please ensure your changes follow the project's coding standards and include tests where appropriate. 