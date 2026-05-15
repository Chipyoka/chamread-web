<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 40px;
            background: #fff;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #198bce;
        }

        .header h3 {
            color: #198bce;
            font-size: 22px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .header h2 {
            color: #555;
            font-size: 18px;
            font-weight: normal;
            margin-top: 5px;
        }

        /* Info Grid */
        .info-section {
            margin: 25px 0;
            background: #f9f9f9;
            padding: 15px 20px;
            display:flex;
            justify-content: flex-start;
            align-items: center;
            gap: 10px;
        }

        .info-row {
            display: flex;
            flex-wrap: wrap;
            margin: 8px 0;
        }

        .info-label {
            width: 140px;
            font-weight: bold;
            color: #555;
        }

        .info-value {
            flex: 1;
            color: #333;
        }

        /* Table Styles */
        .table-wrapper {
            margin: 25px 0;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th {
            background: #198bce;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }

        tr {
            border-bottom: 1px solid #f0f0f0;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        /* Alternating row colors */
        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #777;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
        }

        /* Typography */
        h3 {
            color: #198bce;
            font-size: 16px;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Utility */
        .text-right {
            text-align: right;
        }

        .consumption {
            font-weight: bold;
            color: #198bce;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Customer Account Report</h2>
</div>

<div class="info-section">
    <div class="info-row">
        <div class="info-label">Account Number:</div>
        <div class="info-value">{{ $account->account_number }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Meter Number:</div>
        <div class="info-value">{{ $account->meter_number }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Customer Name:</div>
        <div class="info-value">{{ $account->name }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Customer Phone:</div>
        <div class="info-value">{{ $account->phone }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Assigned CSA:</div>
        <div class="info-value">{{ $assignedCsa?->name ?? 'Unassigned' }}</div>
    </div>
</div>

<h3>Readings Snapshot</h3>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Previous</th>
                <th>Current</th>
                <th>Consumption</th>
            </tr>
        </thead>
        <tbody>
            @foreach($readings as $reading)
                <tr>
                    <td>{{ $reading->created_at->format('M Y') }}</td>
                    <td>{{ number_format($reading->previous_reading, 2) }}</td>
                    <td>{{ number_format($reading->current_reading, 2) }}</td>
                    <td class="consumption">
                        {{ number_format($reading->current_reading - $reading->previous_reading, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    <div class="footer-left">
        <strong>Generated on:</strong> {{ $date }}
    </div>
    <div class="footer-right">
        <strong>Generated by:</strong> {{ $user->name }}
    </div>
</div>

</body>
</html>