<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Un Post Entries Report</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 10mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            text-align: center;
            color: #8e24aa;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 16px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 11px;
            color: #333;
        }
        .date-range {
            margin-top: 8px;
            font-size: 11px;
            font-weight: bold;
        }
        .date-range span { text-decoration: underline; }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        th {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: middle;
            font-size: 11px;
        }
        .center { text-align: center; }
        .left { text-align: left; }
        .view-btn {
            display: inline-block;
            padding: 4px 12px;
            background: #0d6efd;
            color: #fff !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .view-btn:hover { background: #0b5ed7; }
        @media print {
            .no-print-col { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ now()->format('l, F j, Y') }}</div>
        <h1 class="report-title">Un Post Entries Report</h1>
        <div class="date-range">Overall System — All Unposted Entries</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:8%;">S.No.</th>
                <th style="width:20%;">Definitions</th>
                <th style="width:12%;">ID on Form</th>
                <th style="width:12%;">Date</th>
                <th style="width:36%;">Record Add User Name</th>
                <th class="no-print-col center" style="width:12%;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="left">{{ $row['definition'] }}</td>
                <td class="center">{{ $row['record_id'] }}</td>
                <td class="center">{{ $row['date'] }}</td>
                <td class="left">{{ $row['user_name'] }}</td>
                <td class="no-print-col center">
                    @if(!empty($row['view_url']))
                    <a href="{{ $row['view_url'] }}" class="view-btn">View</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="center" style="padding:24px;">No unposted entries found in the system.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
