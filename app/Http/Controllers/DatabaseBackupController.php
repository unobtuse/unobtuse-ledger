<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    /**
     * Download a full backup of the Supabase database.
     *
     * @param Request $request
     * @return StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Request $request)
    {
        // Check if user is admin
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        try {
            $config = config('database.connections.' . config('database.default'));
            
            // Only allow PostgreSQL backups
            if (($config['driver'] ?? '') !== 'pgsql') {
                return redirect()->back()->with('error', 'Database backup is only available for PostgreSQL databases.');
            }
            
            // Generate filename with timestamp
            $filename = 'supabase-backup-' . now()->format('Y-m-d_H-i-s') . '.sql';
            $backupPath = storage_path('app/backups');
            
            // Ensure backups directory exists and has correct permissions
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0775, true);
            }
            // Ensure www-data can write to backups directory
            chmod($backupPath, 0775);
            if (fileowner($backupPath) !== fileowner(storage_path('app'))) {
                // Try to fix ownership if possible (may require sudo)
                @chown($backupPath, fileowner(storage_path('app')));
            }
            
            $fullPath = $backupPath . '/' . $filename;
            
            // Get database connection details
            $host = $config['host'] ?? 'localhost';
            $port = $config['port'] ?? 5432;
            $database = $config['database'] ?? 'postgres';
            $username = $config['username'] ?? 'postgres';
            $password = $config['password'] ?? '';
            $sslmode = $config['sslmode'] ?? 'prefer';
            
            // Check for direct connection host override (for Supabase)
            // Supabase provides separate connection strings for pooler vs direct
            // Set DB_DIRECT_HOST in .env if you have a direct connection hostname
            $directHost = env('DB_DIRECT_HOST');
            if ($directHost) {
                $host = $directHost;
                Log::info('Using direct connection host from DB_DIRECT_HOST', ['host' => $host]);
            }
            
            // Supabase connection pooler detection
            // Supabase pooler hostnames can work on port 5432 for direct connections
            // pg_dump works with pooler hostname on port 5432, no conversion needed
            $isPooler = str_contains($host, 'pooler');
            if ($isPooler && !$directHost) {
                // Ensure we're using port 5432 (direct connection port)
                // The pooler hostname works fine on port 5432 for pg_dump
                if ($port != 5432) {
                    $port = 5432;
                    Log::info('Switching to port 5432 for direct connection', [
                        'host' => $host,
                        'port' => $port,
                    ]);
                }
                // Keep the pooler hostname - it works on port 5432
            }
            
            // For Supabase, require SSL
            if (str_contains($host, 'supabase') && $sslmode === 'prefer') {
                $sslmode = 'require';
            }

            // Check if pg_dump is available
            $pgDumpPath = $this->findPgDump();
            
            if (!$pgDumpPath) {
                return redirect()->back()->with('error', 'pg_dump utility not found. Please install PostgreSQL client tools (postgresql-client).');
            }

            // Set PGPASSWORD environment variable for pg_dump
            putenv("PGPASSWORD={$password}");
            
            // Set SSL mode if specified (Supabase requires SSL)
            if ($sslmode && $sslmode !== 'disable') {
                putenv("PGSSLMODE={$sslmode}");
            }

            // Build pg_dump command with better error handling
            // --no-owner: Don't output commands to set ownership of objects
            // --no-acl: Don't output access privileges (grants/revokes)
            // -F p: Plain text format
            // Capture stderr separately for error messages
            $errorFile = $backupPath . '/backup-error-' . time() . '.txt';
            
            // Ensure error file can be written
            if (!is_writable($backupPath)) {
                throw new \Exception("Backups directory is not writable. Please check permissions.");
            }
            
            // Check pg_dump version compatibility
            $versionOutput = [];
            $versionExitCode = 0;
            exec($pgDumpPath . ' --version 2>&1', $versionOutput, $versionExitCode);
            $pgDumpVersion = !empty($versionOutput) ? $versionOutput[0] : 'unknown';
            
            // Note: pg_dump version must match or be newer than server version
            // If you get version mismatch errors, upgrade pg_dump:
            // sudo apt-get install postgresql-client-17
            $command = sprintf(
                '%s -h %s -p %d -U %s -d %s --no-owner --no-acl -F p > %s 2> %s',
                escapeshellarg($pgDumpPath),
                escapeshellarg($host),
                $port,
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($fullPath),
                escapeshellarg($errorFile)
            );

            // Execute pg_dump and save to file
            $exitCode = 0;
            $output = [];
            exec($command, $output, $exitCode);
            
            putenv('PGPASSWORD'); // Clear password from environment
            putenv('PGSSLMODE'); // Clear SSL mode from environment
            
            // Read error file if it exists
            $errorMessage = '';
            if (file_exists($errorFile)) {
                $errorMessage = file_get_contents($errorFile);
                @unlink($errorFile); // Clean up error file
            }
            
            // If no error file but exit code is non-zero, try to get error from command output
            if ($exitCode !== 0 && empty($errorMessage) && !empty($output)) {
                $errorMessage = implode("\n", $output);
            }
            
            if ($exitCode !== 0) {
                $errorDetails = !empty($errorMessage) ? trim($errorMessage) : "pg_dump failed with exit code {$exitCode}. Check database connection and credentials.";
                
                // Check for version mismatch error
                if (str_contains($errorMessage, 'server version mismatch') || str_contains($errorMessage, 'version mismatch')) {
                    $errorDetails = "PostgreSQL version mismatch detected. Your Supabase database is PostgreSQL 17, but pg_dump is version 16. " .
                                   "Please upgrade pg_dump by running: sudo apt-get install postgresql-client-17";
                }
                
                Log::error('pg_dump failed', [
                    'exit_code' => $exitCode,
                    'error_output' => $errorMessage,
                    'command_output' => $output,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'pg_dump_version' => $pgDumpVersion ?? 'unknown',
                    'command' => $command,
                ]);
                
                // Clean up partial backup file if it exists
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                
                throw new \Exception("Backup failed (exit code {$exitCode}): " . $errorDetails);
            }
            
            if (!file_exists($fullPath)) {
                throw new \Exception('Backup file was not created. ' . (!empty($errorMessage) ? trim($errorMessage) : 'Unknown error'));
            }

            // Check file size
            $fileSize = filesize($fullPath);
            if ($fileSize === false || $fileSize === 0) {
                @unlink($fullPath);
                throw new \Exception('Backup file is empty or could not be read.');
            }

            Log::info('Database backup created successfully', [
                'filename' => $filename,
                'size' => $fileSize,
                'path' => $fullPath,
            ]);

            // Stream the file for download
            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/sql',
            ])->deleteFileAfterSend(false); // Keep the file in backups folder

        } catch (\Exception $e) {
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            putenv('PGPASSWORD'); // Clear password from environment
            putenv('PGSSLMODE'); // Clear SSL mode from environment
            
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Find pg_dump executable path.
     *
     * @return string|null
     */
    private function findPgDump(): ?string
    {
        // Common paths for pg_dump
        // Prioritize PostgreSQL 17+ for compatibility with newer servers
        $paths = [
            '/usr/lib/postgresql/17/bin/pg_dump', // PostgreSQL 17 (for Supabase PostgreSQL 17)
            '/usr/lib/postgresql/16/bin/pg_dump', // PostgreSQL 16
            '/usr/bin/pg_dump',
            '/usr/local/bin/pg_dump',
            '/opt/homebrew/bin/pg_dump',
            'pg_dump', // Try in PATH
        ];

        foreach ($paths as $path) {
            if ($path === 'pg_dump') {
                // Check if it's in PATH
                $which = shell_exec('which pg_dump 2>/dev/null');
                if ($which) {
                    return trim($which);
                }
            } else {
                if (file_exists($path) && is_executable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }
}

