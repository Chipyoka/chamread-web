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
            border-bottom: 2px solid #198bce;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #198bce;
            font-size: 22px;
            margin-bottom: 5px;
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
            margin-top: 35px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            font-size: 11px;
            color: #777;
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
    <h1>CHAMREAD | Customer Account Report</h1>
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
        <div class="info-value">{{ $account->customer_name }}</div>
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

<h3>Readings History</h3>

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
    System Generated on {{ $date }} by {{ $user->name }}
</div>

</body>
</html>