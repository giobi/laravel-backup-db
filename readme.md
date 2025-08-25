💾 Laravel DB Backup ManagerQuesto progetto estende il pacchetto di backup di Spatie per fornire un'interfaccia utente semplice e sicura, che ti permette di visualizzare e ripristinare i backup del database con un solo click.✨ Caratteristiche PrincipaliInterfaccia Utente Semplice: Un'interfaccia chiara e minimale per listare i backup esistenti, basata su Bootstrap 5 (Dark Theme).Ripristino Sicuro del Database: Ripristina il database da un backup a scelta con un singolo click. L'operazione è protetta da CSRF e accessibile solo a utenti autorizzati.Gestione Log: Ogni operazione di ripristino viene registrata nel file di log per un tracciamento completo delle attività.Autorizzazione Granulare: L'accesso all'interfaccia è protetto da un gate di Laravel (can('admin')), che puoi personalizzare.🛠️ RequisitiPHP >= 8.1Laravel >= 10mysqldump (o lo strumento di dump corrispondente al tuo database) installato e disponibile nel PATH del server.📦 InstallazioneInstalla il pacchetto Spatie:composer require spatie/laravel-backup
Pubblica la configurazione di Spatie:php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
Modifica il file config/backup.php per configurare le tue preferenze di backup. Per questo progetto, è sufficiente configurare il database.Configurazione del Manager:Crea il file config/backup-manager.php per definire le opzioni di accesso e log.<?php

return [
    'route_prefix' => 'backups',
    'middleware' => ['web', 'auth'],
    'auth_gate' => 'admin',
    'log_channel' => 'daily', // Canale di log per le operazioni di restore
];
⚙️ Utilizzo1. RotteAggiungi le seguenti rotte nel file routes/web.php per esporre l'interfaccia e la logica di ripristino.// routes/web.php

use App\Http\Controllers\BackupController;

Route::group(['middleware' => config('backup-manager.middleware')], function () {
    Route::get(config('backup-manager.route_prefix'), [BackupController::class, 'index'])->name('backups.index');
    Route::post(config('backup-manager.route_prefix') . '/restore', [BackupController::class, 'restore'])->name('backups.restore');
});
2. ControllerCrea il file app/Http/Controllers/BackupController.php con la logica per listare i backup e gestirne il ripristino.// app/Http/Controllers/BackupController.php

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\DbDumper\Databases\MySql;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:' . config('backup-manager.auth_gate'));
    }

    public function index()
    {
        $disk = config('backup.backup.destination.disks')[0];
        $backups = Storage::disk($disk)->files(config('backup.backup.destination.filename'));

        $backups = array_filter($backups, function ($file) {
            return str_ends_with($file, '.zip');
        });

        return view('backups.index', compact('backups'));
    }

    public function restore(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $path = $request->input('path');
        $disk = config('backup.backup.destination.disks')[0];
        $dbConnection = config('database.connections.' . config('database.default'));

        if (!Storage::disk($disk)->exists($path)) {
            Log::channel(config('backup-manager.log_channel'))->warning('Tentativo di ripristino fallito: file di backup non trovato (' . $path . ')');
            return back()->with('error', 'File di backup non trovato!');
        }

        try {
            // Percorso temporaneo per l'estrazione
            $tempDir = storage_path('app/temp_restore');
            if (!is_dir($tempDir)) {
                mkdir($tempDir);
            }

            // Scarica e decomprimi il backup
            $zip = new ZipArchive;
            $zipPath = $tempDir . '/backup.zip';
            file_put_contents($zipPath, Storage::disk($disk)->get($path));

            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($tempDir);
                $zip->close();
            } else {
                throw new \Exception('Impossibile aprire il file di backup.');
            }

            $dumpPath = $tempDir . '/db-dumps/' . $dbConnection['database'] . '.sql';

            if (!file_exists($dumpPath)) {
                throw new \Exception('File del database non trovato nel backup!');
            }

            // Ripristina il database
            MySql::create()
                ->setDbName($dbConnection['database'])
                ->setUserName($dbConnection['username'])
                ->setPassword($dbConnection['password'])
                ->setHost($dbConnection['host'])
                ->setPort($dbConnection['port'])
                ->import($dumpPath);

            // Pulisci i file temporanei
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir . '/db-dumps');
            rmdir($tempDir);

            Log::channel(config('backup-manager.log_channel'))->info('Database ripristinato con successo dal file: ' . $path);

            return back()->with('success', 'Database ripristinato con successo!');
        } catch (\Exception $e) {
            Log::channel(config('backup-manager.log_channel'))->error('Errore durante il ripristino: ' . $e->getMessage());
            return back()->with('error', 'Errore durante il ripristino: ' . $e->getMessage());
        }
    }
}
3. Vista (Blade)Crea il file resources/views/backups/index.blade.php.<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Backup DB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
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
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">Gestione Backup Database</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                Elenco Backup
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($backups as $backup)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="file-name">{{ basename($backup) }}</span>
                        <form action="{{ route('backups.restore') }}" method="POST" onsubmit="return confirm('ATTENZIONE: Stai per ripristinare il database. I dati attuali verranno sovrascritti. Sei sicuro?');">
                            @csrf
                            <input type="hidden" name="path" value="{{ $backup }}">
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="bi bi-arrow-repeat"></i> Ripristina
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-center">
                        Nessun backup trovato.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</body>
</html>
4. Policy di Autorizzazione (Gate)L'accesso a questa interfaccia è controllato da un gate di Laravel. Aggiungi il gate admin nel file app/Providers/AuthServiceProvider.php.// app/Providers/AuthServiceProvider.php

use Illuminate\Support\Facades\Gate;

// ...
public function boot(): void
{
    // ...
    Gate::define('admin', function ($user) {
        // Implementa la tua logica di autorizzazione qui
        return $user->isAdmin(); // Esempio: suppone che tu abbia un metodo `isAdmin()` sul tuo modello User
    });
}
⚠️ AvvertenzeSovrascrittura Dati: L'operazione di ripristino cancella tutti i dati attuali nel database e li sostituisce con quelli presenti nel backup. Non c'è un'opzione di "undo".Gestione Filesystem: Questa implementazione gestisce solo il database. Il ripristino di file e cartelle (ad es. storage/app/public) deve essere gestito separatamente.Sicurezza: Sebbene siano state implementate misure di sicurezza (Gate, CSRF), l'accesso a questa interfaccia dovrebbe essere limitato a un numero ristretto di utenti.
