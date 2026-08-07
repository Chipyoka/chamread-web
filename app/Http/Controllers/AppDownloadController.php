<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class AppDownloadController extends Controller
{
    public function index()
    {
        $files = collect(Storage::files('apks'))
            ->filter(fn ($path) => str_ends_with($path, '.apk'))
            ->map(function ($path) {
               $filename = basename($path);

                // Handles: chamread-v2.0.0.apk, chamread-v2.0.0-current.apk, etc.
                if (preg_match('/chamread-v?([\d]+(?:\.[\d]+)*)(?:-\w+)?\.apk$/i', $filename, $m)) {
                    $version = $m[1];
                } elseif (preg_match('/([\d]+(?:\.[\d]+)+)/', $filename, $m)) {
                    // Fallback: grab a dotted version-looking number, don't strip the dots
                    $version = $m[1];
                } else {
                    $version = '0.0.' . $disk->lastModified($path);
                }

                return [
                    'filename' => $filename,
                    'version' => $version,
                    'size_mb' => round(Storage::size($path) / 1024 / 1024, 2),
                    'mtime' => Storage::lastModified($path),
                ];
            })
            ->sortByDesc(fn ($f) => version_compare($f['version'], '0'))
            ->values();

        return view('downloads.index', [
            'appName' => 'Chamread - Meter Reading App',
            'current' => $files->first(),
            'previous' => $files->skip(1),
        ]);
    }

    public function download(string $filename)
{
    $path = 'apks/' . basename($filename);

    if (! Storage::exists($path)) {
        abort(404);
    }

    ini_set('memory_limit', '512M'); // temporary safety net

    return Storage::download($path, basename($filename), [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
}
}