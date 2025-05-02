# Documentation Module

A dedicated documentation module for the Relaticle application with a modern, customized frontend architecture.

## Features

- Modern frontend architecture with TailwindCSS 3 and Alpine.js 3
- Responsive design with mobile-first approach
- Dark mode support with system preference detection
- Interactive table of contents with active state highlighting
- Code block syntax highlighting with copy functionality
- Search functionality with highlighted results
- Custom callout components for warnings, tips, notes
- Keyboard navigation and accessibility improvements
- Print styles for documentation pages

## Installation

The Documentation module is included in the Relaticle application by default. If you need to install it manually:

```bash
# Publish the module's resources
php artisan vendor:publish --tag=documentation-views
php artisan vendor:publish --tag=documentation-markdown
php artisan vendor:publish --tag=documentation-config

# Install dependencies
cd app-modules/Documentation
npm install

# Build assets
npm run build
```

## Development

To work on the Documentation module's frontend:

```bash
# Start the Vite development server
cd app-modules/Documentation
npm run dev
```

## Asset Architecture

The Documentation module uses a dedicated asset pipeline with separate CSS and JS files:

- `resources/css/documentation.css`: Main CSS file with Tailwind directives
- `resources/js/documentation.js`: Main JS file with Alpine.js components
- `tailwind.config.js`: TailwindCSS configuration for the Documentation module
- `vite.config.js`: Vite configuration for the Documentation module

## Components

### Layout Components
- `<x-documentation::layout>`: The main layout component for documentation pages
- `<x-documentation::content>`: The content component for documentation pages

### UI Components
- `<x-documentation::callout>`: Callout component for warnings, tips, notes

## Usage

To render a documentation page:

```php
<x-documentation::layout :title="$documentTitle">
    <x-documentation::content :content="$documentContent" />
</x-documentation::layout>
```

To create a callout:

```php
<x-documentation::callout type="tip" title="Quick Tip">
    This is a helpful tip for the documentation.
</x-documentation::callout>
```

Available callout types:
- `info` (default)
- `warning`
- `danger`
- `tip`

## Customization

### Styling
The Documentation module uses TailwindCSS 3 with a custom configuration. You can customize the styles by editing:

- `tailwind.config.js`: Colors, typography, and other Tailwind settings
- `resources/css/documentation.css`: Custom components and utilities

### Templates
The Documentation module's templates are located in:

- `resources/views/components/`: Blade components
- `resources/views/`: Blade templates

## Accessibility

The Documentation module is designed with accessibility in mind:

- Proper ARIA attributes on interactive elements
- Keyboard navigation for all features
- Focus management for interactive components
- Color contrast meeting WCAG AA standards
- Dark mode support for reduced eye strain

## Browser Support

The Documentation module supports all modern browsers:

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

The Documentation module is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT). 