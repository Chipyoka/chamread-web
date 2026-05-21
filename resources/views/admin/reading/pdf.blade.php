<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chamread | Reading Report</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 35px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 2px solid #198bce;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #198bce;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 13px;
            text-transform: uppercase;
            color: #777;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .grid {
            width: 100%;
        }

        .row {
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f8f8;
        }

        .label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .value {
            font-size: 13px;
            color: #333;
            font-weight: bold;
        }

        .status-read {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            text-align: center;
            font-weight: bold;
        }

        .status-not-read {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            text-align: center;
            font-weight: bold;
        }

        .photo {
            margin-top: 15px;
            text-align: center;
        }

        .photo img {
            max-width: 350px;
            max-height: 300px;
            object-fit: cover;
        }

        .footer {
            margin-top: 35px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            font-size: 11px;
            color: #777;
        }

        .consumption {
            color: #198bce;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>CHAMREAD | Reading Report</h1>
</div>

<!-- Reading Status -->
<div class="section">
    <div class="section-title">Reading Status</div>

    <div class="{{ $reading->status === 'read' ? 'status-read' : 'status-not-read' }}">
        {{ strtoupper($reading->status === 'read' ? 'Read' : 'Not Read') }}
    </div>
</div>

<!-- Reading Information -->
<div class="section">
    <div class="section-title">Reading Information</div>

    <div class="row">
        <div class="label">Recorded By</div>
        <div class="value">{{ $reading->csa->name ?? 'Unknown' }} | {{ $reading->reading_time?->format('Y-m-d H:i:s') ?? '-' }} </div>
    </div>

    <div class="row">
        <div class="label">ReadingS</div>
        <div class="value">
            Prev: {{ number_format((float)($reading->previous_reading ?? 0), 3) }} | Current: {{ number_format((float)($reading->current_reading ?? 0), 3) }} | Consumption: 
            {{ number_format($consumption, 3, '.', '') }}
        </div>
    </div>

    <div class="row">
        <div class="label">
            {{ $reading->status === 'read' ? 'Comment' : 'Reason' }}
        </div>

        <div class="value">
            {{
                $reading->status === 'read'
                    ? ($reading->comment ?? 'Not provided')
                    : ($reading->reason->name ?? 'Not provided')
            }}
        </div>
    </div>


    @if($reading->photo_path)
        <div class="photo">
            <img
                src="{{ public_path('storage/' . $reading->photo_path) }}"
                alt="Reading Photo"
            >
        </div>
    @endif
</div>

<!-- Customer Information -->
<div class="section">
    <div class="section-title">Customer Account Information</div>

    <div class="row">
        <div class="label">Account</div>
        <div class="value">{{ $reading->account->name ?? '-' }} - {{ $reading->account->account_number ?? '-' }}</div>
    </div>

    <div class="row">
        <div class="label">Contact</div>
        <div class="value">Phone: {{ $reading->account->phone ?? '-' }} | Address: {{ $reading->account->address ?? '-' }}</div>
    </div>
</div>

<div class="footer">
    System Generated on {{ $date }} by {{ $user->name }}
</div>

</body>
</html>