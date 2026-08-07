<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - Downloads</title>
    <style>
        :root {
            --color-primary: #198bce;
            --color-primary-hover: #1a98e1;
            --color-gray-50: #F9FAFB;
            --color-gray-100: #F3F4F6;
            --color-gray-200: #E5E7EB;
            --color-gray-300: #D1D5DB;
            --color-gray-400: #9CA3AF;
            --color-gray-500: #6B7280;
            --color-gray-600: #4B5563;
            --color-gray-700: #374151;
            --color-gray-800: #1F2937;
            --color-gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-gray-50);
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            padding: 48px 40px 32px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--color-gray-200);
        }

        .app-icon {
            width: auto;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 32px;
            font-weight: 700;
            color: white;
        }

        .app-name {
            font-size: 26px;
            font-weight: 700;
            color: var(--color-gray-900);
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .version-label {
            text-align: center;
            color: var(--color-gray-500);
            font-size: 15px;
            margin-bottom: 28px;
        }

        .version-label span {
            background: var(--color-gray-100);
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 600;
            color: var(--color-gray-700);
            font-size: 14px;
        }

        .download-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .download-btn {
            display: inline-block;
            background: var(--color-primary);
            color: white;
            padding: 14px 48px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 17px;
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
            border: none;
            cursor: pointer;
            min-width: 200px;
            letter-spacing: 0.3px;
        }

        .download-btn:hover:not(.disabled) {
            background: var(--color-primary-hover);
            transform: translateY(-1px);
        }

        .download-btn:active:not(.disabled) {
            transform: translateY(0);
        }

        .download-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: var(--color-gray-400);
        }

        .file-info {
            font-size: 13px;
            color: var(--color-gray-500);
            margin-top: 10px;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--color-gray-200);
            margin: 24px 0 20px;
        }

        .versions-section {
            margin-top: 4px;
        }

        .versions-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .version-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 8px;
            transition: background 0.15s;
        }

        .version-item:hover {
            background: var(--color-gray-50);
        }

        .version-item .version-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .version-item .version-number {
            font-size: 14px;
            font-weight: 500;
            color: var(--color-gray-800);
        }

        .version-item .version-badge {
            font-size: 10px;
            font-weight: 600;
            background: var(--color-primary);
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            letter-spacing: 0.3px;
        }

        .version-item .version-size {
            font-size: 12px;
            color: var(--color-gray-400);
        }

        .version-item .download-small {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--color-gray-100);
            color: var(--color-gray-700);
            padding: 4px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            border: none;
            cursor: pointer;
            min-width: 60px;
        }

        .version-item .download-small:hover {
            background: var(--color-primary);
            color: white;
        }

        .version-item .download-small:active {
            transform: scale(0.96);
        }

        .version-item + .version-item {
            border-top: 1px solid var(--color-gray-100);
        }

        .no-versions {
            text-align: center;
            color: var(--color-gray-400);
            font-size: 14px;
            padding: 16px 0;
        }

        @media (max-width: 480px) {
            .container {
                padding: 32px 20px 24px;
            }
            .app-name {
                font-size: 22px;
            }
            .download-btn {
                padding: 12px 32px;
                font-size: 16px;
                min-width: 160px;
            }
            .version-item {
                padding: 8px 8px;
                flex-wrap: wrap;
                gap: 6px;
            }
            .version-item .version-info {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('images/logo.png') }}" alt="Chambeshi Logo" class="app-icon">
        <h1 class="app-name">{{ $appName }}</h1>

        @if ($current)
            <p class="version-label">
                <span>v{{ $current['version'] }}</span>
            </p>
        @endif

        <div class="download-section">
            @if ($current)
                <a href="{{ route('downloads.file', $current['filename']) }}"
                   class="download-btn"
                   download="{{ $current['filename'] }}">
                    Download App
                </a>
                <div class="file-info">
                    {{ number_format($current['size_mb'], 2) }} MB
                </div>
            @else
                <div class="download-btn disabled">
                    No version available
                </div>
                <div class="file-info">
                    Please check back later
                </div>
            @endif
        </div>

        <hr class="divider">

        <div class="versions-section">
            <div class="versions-title">Previous Versions</div>

            @if ($current && $previous->isNotEmpty())
                @foreach ($previous as $file)
                    <div class="version-item">
                        <div class="version-info">
                            <span class="version-number">v{{ $file['version'] }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="version-size">{{ number_format($file['size_mb'], 1) }} MB</span>
                            <a href="{{ route('downloads.file', $file['filename']) }}"
                               class="download-small"
                               download="{{ $file['filename'] }}">
                                Download
                            </a>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="no-versions">No previous versions available</div>
            @endif
        </div>
    </div>
</body>
</html>