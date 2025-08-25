# Setup Guide

This guide will walk you through setting up the Laravel Backup Database Manager package in your Laravel application.

## Prerequisites

Before installing this package, ensure you have:

- PHP 8.1 or higher
- Laravel 10.0 or higher
- MySQL/MariaDB database
- `mysqldump` utility available in your system PATH
- Composer installed

## Step 1: Install Spatie Laravel Backup

First, install the base backup package that this manager extends:

```bash
composer require spatie/laravel-backup
```

Publish its configuration:

```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

## Step 2: Configure Spatie Backup

Edit the published configuration file `config/backup.php` to suit your needs. At minimum, ensure the database connection is properly configured:

```php
// config/backup.php

'backup' => [
    'name' => env('APP_NAME', 'laravel_backup'),
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
            ],
        ],
        'databases' => [
            'mysql', // or your database connection name
        ],
    ],
    'destination' => [
        'filename_prefix' => '',
        'disks' => [
            'local', // or your preferred storage disk
        ],
    ],
],
```

## Step 3: Install Laravel Backup DB Manager

Install this package via Composer:

```bash
composer require giobi/laravel-backup-db
```

The service provider will be automatically registered via Laravel's package discovery.

## Step 4: Publish Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --provider="Giobi\LaravelBackupDb\LaravelBackupDbServiceProvider" --tag="config"
```

This creates `config/backup-manager.php` where you can customize the package behavior.

## Step 5: Configure Authorization

The package uses Laravel Gates for authorization. Define the authorization logic in your `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php

use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    $this->registerPolicies();

    Gate::define('admin', function ($user) {
        // Implement your authorization logic here
        // Examples:
        
        // Role-based authorization
        return $user->hasRole('admin');
        
        // Permission-based authorization
        return $user->can('manage-backups');
        
        // Simple email check
        return in_array($user->email, [
            'admin@example.com',
            'backup-manager@example.com'
        ]);
        
        // Custom method on User model
        return $user->isBackupManager();
    });
}
```

## Step 6: Configure Storage (Optional)

If you want to store backups on a different disk (like S3), configure it in your `config/filesystems.php` and update the backup configuration accordingly.

## Step 7: Create Your First Backup

Create a backup to test the system:

```bash
php artisan backup:run
```

Or schedule regular backups in your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run')->daily()->at('02:00');
}
```

## Step 8: Access the Interface

Visit `/backups` (or your configured route prefix) in your browser to access the backup management interface.

## Troubleshooting

### Common Issues

**1. "Permission denied" errors**
- Ensure your web server has write permissions to the storage directory
- Check that `mysqldump` is available and executable

**2. "Backup files not found"**
- Verify that backups are being created successfully with `php artisan backup:run`
- Check the storage disk configuration in `config/backup.php`

**3. "Unauthorized access"**
- Ensure you've defined the authorization gate correctly
- Check that the authenticated user passes the gate logic

**4. "Database restoration failed"**
- Verify database credentials in your `.env` file
- Ensure the MySQL user has sufficient privileges for database operations
- Check that the backup file contains a valid SQL dump

### Logging

All backup restoration operations are logged. Check your configured log channel (default: `daily`) for detailed error information:

```bash
tail -f storage/logs/laravel-*.log
```

## Security Best Practices

1. **Limit Access**: Ensure only trusted administrators can access the backup interface
2. **Use HTTPS**: Always use HTTPS in production to protect data transmission
3. **Regular Backups**: Set up automated daily backups
4. **Test Restores**: Regularly test backup restoration on a staging environment
5. **Monitor Logs**: Keep an eye on backup and restoration logs for any issues

## Next Steps

- Review the [Agents Documentation](agents.md) for advanced configuration
- Customize the interface by publishing and modifying the views
- Set up automated backup monitoring and alerting