# Simple PHP MVC Framework

A lightweight PHP MVC framework built from scratch.

## Directory Structure

```
├── app/
│   ├── Controllers/     # Controller classes
│   ├── Models/         # Model classes
│   └── Views/          # View templates
├── config/             # Configuration files
├── core/              # Framework core classes
├── public/            # Public directory (web root)
└── routes/            # Route definitions
```

## Requirements

- PHP 8.0 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation

1. Clone the repository
2. Configure your web server to point to the `public` directory
3. Copy `.env.example` to `.env` and update the configuration
4. Run `composer install`

## Usage

1. Define your routes in `routes/web.php`
2. Create controllers in `app/Controllers`
3. Create views in `app/Views`
4. Access your application through the configured domain
