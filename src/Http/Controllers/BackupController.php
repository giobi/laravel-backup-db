<?php

namespace Giobi\LaravelBackupDb\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\DbDumper\Databases\MySql;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $authGate = config('backup-manager.auth_gate');
        if ($authGate) {
            $this->middleware('can:' . $authGate);
        }
    }

    public function index()
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupPath = config('backup.backup.name', config('app.name'));
        
        try {
            $files = Storage::disk($disk)->files($backupPath);
            $backups = array_filter($files, function ($file) {
                return str_ends_with($file, '.zip');
            });

            // Sort by modification time (newest first)
            usort($backups, function ($a, $b) use ($disk) {
                return Storage::disk($disk)->lastModified($b) - Storage::disk($disk)->lastModified($a);
            });
        } catch (\Exception $e) {
            $backups = [];
            Log::channel(config('backup-manager.log_channel'))->warning('Failed to list backups: ' . $e->getMessage());
        }

        return view('laravel-backup-db::index', compact('backups'));
    }

    public function restore(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $path = $request->input('path');
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $dbConnection = config('database.connections.' . config('database.default'));

        if (!Storage::disk($disk)->exists($path)) {
            Log::channel(config('backup-manager.log_channel'))->warning('Restore attempt failed: backup file not found (' . $path . ')');
            return back()->with('error', 'Backup file not found!');
        }

        try {
            // Create temporary directory for extraction
            $tempDir = storage_path('app/temp_restore_' . uniqid());
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Download and extract the backup
            $zip = new ZipArchive;
            $zipPath = $tempDir . '/backup.zip';
            file_put_contents($zipPath, Storage::disk($disk)->get($path));

            if ($zip->open($zipPath) === true) {
                $zip->extractTo($tempDir);
                $zip->close();
            } else {
                throw new \Exception('Unable to open backup file.');
            }

            // Find the SQL dump file
            $dumpPath = $tempDir . '/db-dumps/' . $dbConnection['database'] . '.sql';
            
            if (!file_exists($dumpPath)) {
                // Try alternative paths
                $possiblePaths = [
                    $tempDir . '/db-dumps/mysql-' . $dbConnection['database'] . '.sql',
                    $tempDir . '/' . $dbConnection['database'] . '.sql',
                ];
                
                foreach ($possiblePaths as $possiblePath) {
                    if (file_exists($possiblePath)) {
                        $dumpPath = $possiblePath;
                        break;
                    }
                }
                
                if (!file_exists($dumpPath)) {
                    throw new \Exception('Database dump file not found in backup!');
                }
            }

            // Restore the database
            $importer = MySql::create()
                ->setDbName($dbConnection['database'])
                ->setUserName($dbConnection['username'])
                ->setPassword($dbConnection['password'])
                ->setHost($dbConnection['host'])
                ->setPort($dbConnection['port'] ?? 3306);

            $importer->import($dumpPath);

            // Clean up temporary files
            $this->cleanupTempDirectory($tempDir);

            Log::channel(config('backup-manager.log_channel'))->info('Database restored successfully from file: ' . $path);

            return back()->with('success', 'Database restored successfully!');
        } catch (\Exception $e) {
            // Clean up on error
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->cleanupTempDirectory($tempDir);
            }
            
            Log::channel(config('backup-manager.log_channel'))->error('Error during restoration: ' . $e->getMessage());
            return back()->with('error', 'Error during restoration: ' . $e->getMessage());
        }
    }

    private function cleanupTempDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->cleanupTempDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}