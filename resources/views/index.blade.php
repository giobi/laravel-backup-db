<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('backup-manager.ui.title', 'Database Backup Manager') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    
    @if(config('backup-manager.ui.theme') === 'dark')
    <style>
        body {
            background-color: #212529;
            color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            background-color: #343a40;
            border-color: #495057;
        }
        .card-header {
            background-color: #495057;
            border-bottom-color: #6c757d;
        }
        .list-group-item {
            background-color: #495057;
            border-color: #6c757d;
            color: #f8f9fa;
        }
        .btn-warning {
            color: #212529;
            background-color: #ffc107;
            border-color: #ffc107;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            color: #212529;
        }
        .alert-success {
            background-color: #155724;
            border-color: #1e7e34;
            color: #d4edda;
        }
        .alert-danger {
            background-color: #721c24;
            border-color: #a94442;
            color: #f8d7da;
        }
    </style>
    @endif
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="mb-4 text-center">
                    <i class="bi bi-database"></i>
                    {{ config('backup-manager.ui.title', 'Database Backup Manager') }}
                </h1>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-archive"></i>
                            Available Backups
                        </h5>
                        <small class="text-muted">
                            {{ count($backups) }} backup(s) found
                        </small>
                    </div>
                    
                    @if(count($backups) > 0)
                        <ul class="list-group list-group-flush">
                            @foreach ($backups as $backup)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="file-name">{{ basename($backup) }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i>
                                            {{ date('Y-m-d H:i:s', Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local')->lastModified($backup)) }}
                                            •
                                            <i class="bi bi-file-earmark-zip"></i>
                                            {{ number_format(Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local')->size($backup) / 1024 / 1024, 2) }} MB
                                        </small>
                                    </div>
                                    
                                    <form action="{{ route('laravel-backup-db.restore') }}" method="POST" 
                                          onsubmit="return confirm('⚠️ WARNING: You are about to restore the database. Current data will be overwritten. Are you sure?');">
                                        @csrf
                                        <input type="hidden" name="path" value="{{ $backup }}">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="bi bi-arrow-clockwise"></i>
                                            Restore
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="card-body text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                            <p class="mt-3 mb-0 text-muted">No backups found.</p>
                            <p class="text-muted">
                                <small>Make sure you have configured Spatie's backup package and created some backups.</small>
                            </p>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <div class="alert alert-warning" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Important Warnings</h6>
                        <ul class="mb-0">
                            <li><strong>Data Overwrite:</strong> Restoration will replace all current database data with backup data. There's no "undo" option.</li>
                            <li><strong>Database Only:</strong> This tool only restores database content. File restores must be handled separately.</li>
                            <li><strong>Access Control:</strong> Ensure only authorized users have access to this interface.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>