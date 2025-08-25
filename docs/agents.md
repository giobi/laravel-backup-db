# Agents Documentation

This document provides detailed information about the internal workings and extensibility of the Laravel Backup Database Manager package.

## Architecture Overview

The package is built with a modular architecture that integrates seamlessly with Laravel's service container and follows Laravel best practices.

### Core Components

#### 1. Service Provider (`LaravelBackupDbServiceProvider`)

The service provider handles:
- Package registration and bootstrapping
- Configuration merging and publishing
- View registration and publishing
- Route registration (when enabled)

#### 2. Controller (`BackupController`)

Handles HTTP requests for:
- Listing available backups (`index`)
- Processing backup restoration requests (`restore`)

#### 3. Configuration System

The package uses a dedicated configuration file (`backup-manager.php`) that allows customization of:
- Route settings
- Middleware configuration
- Authorization gates
- UI preferences
- Logging settings

## Configuration Agents

### Route Agent

Controls how routes are registered and configured:

```php
// config/backup-manager.php
'enable_routes' => true,           // Enable/disable automatic route registration
'route_prefix' => 'backups',       // URL prefix for backup routes
'middleware' => ['web', 'auth'],   // Middleware stack for routes
```

**Customization**: You can disable automatic routes and define your own:

```php
// Disable automatic routes
'enable_routes' => false,

// Define custom routes in routes/web.php
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/database-backups', [BackupController::class, 'index'])
        ->name('admin.backups.index');
    Route::post('/admin/database-backups/restore', [BackupController::class, 'restore'])
        ->name('admin.backups.restore');
});
```

### Authorization Agent

Manages access control through Laravel's Gate system:

```php
// config/backup-manager.php
'auth_gate' => 'admin',
```

**Implementation Examples**:

```php
// Simple role check
Gate::define('admin', function ($user) {
    return $user->role === 'admin';
});

// Multiple roles
Gate::define('admin', function ($user) {
    return in_array($user->role, ['admin', 'backup_manager']);
});

// Permission-based (using Spatie Permission package)
Gate::define('admin', function ($user) {
    return $user->hasPermissionTo('manage backups');
});

// Custom logic with additional checks
Gate::define('admin', function ($user) {
    return $user->isAdmin() && $user->department === 'IT';
});
```

### Logging Agent

Handles operation logging and audit trails:

```php
// config/backup-manager.php
'log_channel' => 'daily',
```

**Custom Log Channel Example**:

```php
// config/logging.php
'channels' => [
    'backup_operations' => [
        'driver' => 'daily',
        'path' => storage_path('logs/backup-operations.log'),
        'level' => 'info',
        'days' => 30,
    ],
],

// Then in backup-manager.php
'log_channel' => 'backup_operations',
```

### UI Agent

Controls the appearance and behavior of the web interface:

```php
// config/backup-manager.php
'ui' => [
    'title' => 'Database Backup Manager',
    'theme' => 'dark',        // 'dark' or 'light'
    'per_page' => 10,
],
```

## Extension Points

### Custom Controllers

You can extend the base controller to add custom functionality:

```php
<?php

namespace App\Http\Controllers;

use Giobi\LaravelBackupDb\Http\Controllers\BackupController as BaseController;
use Illuminate\Http\Request;

class CustomBackupController extends BaseController
{
    public function index()
    {
        // Add custom logic before listing backups
        $this->logAccess();
        
        // Call parent method
        $response = parent::index();
        
        // Add custom data to view
        $response->with('custom_data', $this->getCustomData());
        
        return $response;
    }
    
    public function restore(Request $request)
    {
        // Add custom validation
        $this->validateCustomRules($request);
        
        // Call parent method
        return parent::restore($request);
    }
    
    private function logAccess()
    {
        // Custom access logging
        Log::info('Backup interface accessed', [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }
    
    private function validateCustomRules(Request $request)
    {
        // Add custom validation rules
        $request->validate([
            'confirmation_code' => 'required|string',
        ]);
    }
}
```

### Custom Views

Publish and customize the views:

```bash
php artisan vendor:publish --provider="Giobi\LaravelBackupDb\LaravelBackupDbServiceProvider" --tag="views"
```

The views will be published to `resources/views/vendor/laravel-backup-db/` where you can modify them.

### Middleware Integration

Add custom middleware to the route stack:

```php
// config/backup-manager.php
'middleware' => [
    'web',
    'auth',
    'verified',           // Email verification
    'throttle:10,1',      // Rate limiting
    'custom.audit',       // Custom audit middleware
],
```

### Event Hooks

While the package doesn't currently emit events, you can add them by extending the controller:

```php
use Illuminate\Support\Facades\Event;

class EventfulBackupController extends BackupController
{
    public function restore(Request $request)
    {
        Event::dispatch('backup.restore.started', [
            'user' => auth()->user(),
            'backup_path' => $request->input('path'),
        ]);
        
        $result = parent::restore($request);
        
        if ($result->getSession()->has('success')) {
            Event::dispatch('backup.restore.completed', [
                'user' => auth()->user(),
                'backup_path' => $request->input('path'),
            ]);
        } else {
            Event::dispatch('backup.restore.failed', [
                'user' => auth()->user(),
                'backup_path' => $request->input('path'),
                'error' => $result->getSession()->get('error'),
            ]);
        }
        
        return $result;
    }
}
```

## Advanced Configurations

### Multi-Database Support

For applications with multiple databases:

```php
// Custom controller method
public function restore(Request $request)
{
    $request->validate([
        'path' => 'required|string',
        'database' => 'required|string|in:mysql,pgsql,secondary',
    ]);
    
    $database = $request->input('database');
    $dbConnection = config("database.connections.{$database}");
    
    // Continue with restoration using the specified database
    // ... (rest of restoration logic)
}
```

### Backup Filtering

Add filtering capabilities to the backup list:

```php
public function index(Request $request)
{
    $disk = config('backup.backup.destination.disks')[0] ?? 'local';
    $backupPath = config('backup.backup.name', config('app.name'));
    
    $files = Storage::disk($disk)->files($backupPath);
    $backups = array_filter($files, function ($file) {
        return str_ends_with($file, '.zip');
    });
    
    // Apply filters
    if ($request->has('date_from')) {
        $dateFrom = Carbon::parse($request->date_from)->timestamp;
        $backups = array_filter($backups, function ($backup) use ($disk, $dateFrom) {
            return Storage::disk($disk)->lastModified($backup) >= $dateFrom;
        });
    }
    
    if ($request->has('size_min')) {
        $sizeMin = (int) $request->size_min * 1024 * 1024; // Convert MB to bytes
        $backups = array_filter($backups, function ($backup) use ($disk, $sizeMin) {
            return Storage::disk($disk)->size($backup) >= $sizeMin;
        });
    }
    
    return view('laravel-backup-db::index', compact('backups'));
}
```

### Custom Backup Storage

For custom backup storage locations:

```php
// config/backup-manager.php
'storage' => [
    'disk' => 's3',
    'path' => 'database-backups',
    'retention_days' => 30,
],
```

Then modify the controller to use these settings.

## Performance Considerations

### Large Backup Files

For large backup files, consider:

1. **Streaming Downloads**: Implement streaming for large file downloads
2. **Background Processing**: Use queues for restoration operations
3. **Progress Indicators**: Add progress tracking for long-running operations

### Caching

Cache backup file listings for better performance:

```php
use Illuminate\Support\Facades\Cache;

public function index()
{
    $backups = Cache::remember('backup_files_list', 300, function () {
        // Expensive operation to list backups
        return $this->getBackupFiles();
    });
    
    return view('laravel-backup-db::index', compact('backups'));
}
```

## Security Considerations

### File Path Validation

The package includes basic path validation, but for additional security:

```php
private function validateBackupPath($path)
{
    // Ensure path doesn't contain directory traversal
    if (str_contains($path, '..') || str_contains($path, '/')) {
        throw new InvalidArgumentException('Invalid backup path');
    }
    
    // Ensure file exists in backup directory
    $disk = config('backup.backup.destination.disks')[0] ?? 'local';
    if (!Storage::disk($disk)->exists($path)) {
        throw new FileNotFoundException('Backup file not found');
    }
    
    return true;
}
```

### Rate Limiting

Implement rate limiting for restore operations:

```php
// In RouteServiceProvider or custom middleware
Route::middleware(['throttle:restore'])->group(function () {
    // Restore routes
});

// config/app.php or custom service provider
'throttle:restore' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':1,60',
```

This documentation provides the foundation for extending and customizing the Laravel Backup Database Manager package to fit your specific needs.