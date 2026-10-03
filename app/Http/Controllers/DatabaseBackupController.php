<?php

namespace App\Http\Controllers;

use App\Models\DatabaseBackup;
use App\Services\DatabaseDumper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class DatabaseBackupController extends Controller
{
    public function index()
    {
        $backups = DatabaseBackup::with('creator')->orderBy('id', 'desc')->get();
        return view('admin_panel.database_backup.index', compact('backups'));
    }

    public function createBackup()
    {
        try {
            $databaseName = config('database.connections.mysql.database', 'database');
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$databaseName}_{$timestamp}.sql";

            $backupDirectory = storage_path('app/backups');
            if (!File::exists($backupDirectory)) {
                File::makeDirectory($backupDirectory, 0755, true);
            }

            $filePath = $backupDirectory . DIRECTORY_SEPARATOR . $filename;

            // Generate Dump
            $success = DatabaseDumper::dump($filePath);

            if ($success && File::exists($filePath)) {
                $fileSize = File::size($filePath);

                DatabaseBackup::create([
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'status' => 'completed',
                    'created_by' => Auth::id(),
                ]);

                return redirect()->back()->with('success', 'Database backup created successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to generate database backup dump file.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        $backup = DatabaseBackup::findOrFail($id);

        if (!File::exists($backup->file_path)) {
            return redirect()->back()->with('error', 'Backup file not found on disk.');
        }

        return response()->download($backup->file_path, $backup->filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function destroy($id)
    {
        $backup = DatabaseBackup::findOrFail($id);

        if (File::exists($backup->file_path)) {
            File::delete($backup->file_path);
        }

        $backup->delete();

        return redirect()->back()->with('success', 'Database backup deleted successfully!');
    }
}
