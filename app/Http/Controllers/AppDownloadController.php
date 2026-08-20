<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class AppDownloadController extends Controller
{
    public function index()
    {
        $files = collect(Storage::files('apks'))
            ->filter(fn ($path) => str_ends_with(strtolower($path), '.apk'))
            ->map(function ($path) {
                $filename = basename($path);

                // Handles:
                // chamread-v2.0.0.apk
                // chamread-v2.0.1.apk
                // chamread-v2.0.1-current.apk
                if (preg_match(
                    '/^chamread-v?(\d+\.\d+\.\d+)(?:-current)?\.apk$/i',
                    $filename,
                    $matches
                )) {
                    $version = $matches[1];

                    // Explicitly mark the APK with "-current" as current
                    $isCurrent = (bool) preg_match(
                        '/-current\.apk$/i',
                        $filename
                    );
                } else {
                    // Ignore files that do not follow the expected naming convention
                    return null;
                }

                return [
                    'filename' => $filename,
                    'version' => $version,
                    'is_current' => $isCurrent,
                    'size_mb' => round(
                        Storage::size($path) / 1024 / 1024,
                        2
                    ),
                    'mtime' => Storage::lastModified($path),
                ];
            })
            ->filter()
            ->sort(function ($a, $b) {
                return version_compare(
                    $b['version'],
                    $a['version']
                );
            })
            ->values();

        // Explicitly selected current APK
        $current = $files->firstWhere('is_current', true);

        // Everything else, sorted highest version first
        $previous = $files
            ->reject(fn ($file) => $file['is_current'])
            ->values();

        return view('downloads.index', [
            'appName' => 'Chamread',
            'current' => $current,
            'previous' => $previous,
        ]);
    }

    public function download(string $filename)
    {
        $path = 'apks/' . basename($filename);

        if (!Storage::exists($path)) {
            abort(404);
        }

        set_time_limit(0); // don't let PHP's own execution timer kill a slow external download

        return Storage::download(
            $path,
            basename($filename),
            [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]
        );
    }
}