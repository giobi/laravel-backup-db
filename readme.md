# Laravel Backup Database Manager

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E10.0|%5E11.0-red.svg)](https://laravel.com/)
[![Status: Alpha](https://img.shields.io/badge/Status-Alpha-orange.svg)](https://github.com/giobi/laravel-backup-db)
[![Packagist Version](https://img.shields.io/packagist/v/giobi/laravel-backup-db)](https://packagist.org/packages/giobi/laravel-backup-db)
[![Packagist Downloads](https://img.shields.io/packagist/dt/giobi/laravel-backup-db)](https://packagist.org/packages/giobi/laravel-backup-db)

> **⚠️ ALPHA STATUS**: This package is currently in alpha development and has not been thoroughly tested in production environments. Use at your own risk and please report any issues you encounter.

A Laravel package that extends [Spatie's Laravel Backup](https://github.com/spatie/laravel-backup) functionality with a clean web interface for database backup management and restoration.

## ✨ Features

- **Simple Web Interface**: Clean, responsive UI built with Bootstrap 5
- **Secure Database Restoration**: One-click database restore with CSRF protection
- **Authorization Control**: Gate-based access control (customizable)
- **Comprehensive Logging**: All restoration operations are logged for audit trails
- **Dark/Light Theme Support**: Configurable UI themes
- **File Size & Date Display**: Shows backup file information including size and creation date

## 🛠️ Requirements

- PHP >= 8.1
- Laravel >= 10.0
- MySQL/MariaDB with `mysqldump` available
- [Spatie Laravel Backup](https://github.com/spatie/laravel-backup) package

## 📦 Installation

> **⚠️ Alpha Warning**: This package is in alpha development. It is recommended to test thoroughly in a development environment before considering any production use.

1. Install the package via Composer:

```bash
composer require giobi/laravel-backup-db
```

2. The service provider will be automatically registered via Laravel's package discovery.

3. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Giobi\LaravelBackupDb\LaravelBackupDbServiceProvider" --tag="config"
```

4. Optionally, publish the views for customization:

```bash
php artisan vendor:publish --provider="Giobi\LaravelBackupDb\LaravelBackupDbServiceProvider" --tag="views"
```

## ⚙️ Configuration

### Basic Setup

The package requires [Spatie's Laravel Backup](https://github.com/spatie/laravel-backup) to be installed and configured:

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Configure your `config/backup.php` file according to your needs.

### Package Configuration

Edit `config/backup-manager.php` to customize the package behavior:

```php
return [
    // Enable/disable the web routes
    'enable_routes' => true,
    
    // Route configuration
    'route_prefix' => 'backups',
    'middleware' => ['web', 'auth'],
    
    // Authorization gate
    'auth_gate' => 'admin',
    
    // Logging
    'log_channel' => 'daily',
    
    // UI customization
    'ui' => [
        'title' => 'Database Backup Manager',
        'theme' => 'dark', // 'dark' or 'light'
        'per_page' => 10,
    ],
];
```

### Authorization Gate

Define an authorization gate in your `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php

use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('admin', function ($user) {
        // Implement your authorization logic
        return $user->hasRole('admin'); // Example
    });
}
```

## 🚀 Usage

Once installed and configured, navigate to `/backups` (or your configured route prefix) to access the backup management interface.

### Features Available:

- **View Backups**: List all available backup files with metadata
- **Restore Database**: One-click database restoration with confirmation
- **File Information**: Display file size, creation date, and other metadata
- **Operation Logging**: All actions are logged for audit purposes

## 🎨 Customization

### Custom Views

Publish the views and modify them according to your needs:

```bash
php artisan vendor:publish --provider="Giobi\LaravelBackupDb\LaravelBackupDbServiceProvider" --tag="views"
```

Views will be published to `resources/views/vendor/laravel-backup-db/`.

### Custom Routes

If you prefer to define your own routes, set `enable_routes` to `false` in the config and define your routes manually:

```php
use Giobi\LaravelBackupDb\Http\Controllers\BackupController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/my-backups', [BackupController::class, 'index'])->name('my.backups');
    Route::post('/my-backups/restore', [BackupController::class, 'restore'])->name('my.backups.restore');
});
```

## ⚠️ Security Considerations

- **Alpha Status**: This package is currently in alpha development and has not been extensively tested. Use with caution in production environments.
- **Data Overwrite**: Database restoration overwrites all current data. There's no undo functionality.
- **Access Control**: Ensure only authorized users can access the backup interface.
- **File Validation**: The package validates backup files before processing.
- **CSRF Protection**: All forms include CSRF tokens for security.

## 📚 Documentation

For detailed setup instructions and advanced configuration, see:
- [Setup Guide](docs/setup.md)
- [Agents Documentation](docs/agents.md)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
