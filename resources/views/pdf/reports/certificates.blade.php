<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    h1 { font-size: 18px; color: #003366; margin: 0 0 4px; }
    h2 { font-size: 13px; color: #003366; margin: 16px 0 8px; }
    .meta { color: #555; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
    th { background: #003366; color: #fff; font-size: 10px; text-transform: uppercase; }
    .stats td { font-weight: bold; color: #003366; }
</style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ $office }}<br>
        Period: {{ $range }}<br>
        Generated: {{ $generatedAt }}
    </div>

    <h2>Summary</h2>
    <table class="stats">
        <tr>
            @foreach($report['stats'] as $stat)
                <th>{{ $stat['label'] }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($report['stats'] as $stat)
                <td>{{ $stat['value'] }}</td>
            @endforeach
        </tr>
    </table>

    @if(!empty($report['breakdown']))
    <h2>By Purpose</h2>
    <table>
        <tr><th>Purpose</th><th>Total</th></tr>
        @foreach($report['breakdown'] as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['value'] }}</td>
            </tr>
        @endforeach
    </table>
    @endif

    <h2>Issued Certificates</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Certificate No.</th>
            <th>Visitor</th>
            <th>Address</th>
            <th>Purpose</th>
            <th>Issued By</th>
        </tr>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['number'] }}</td>
                <td>{{ $row['visitor'] }}</td>
                <td>{{ $row['address'] }}</td>
                <td>{{ $row['purpose'] }}</td>
                <td>{{ $row['issuer'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No certificates issued in this period.</td></tr>
        @endforelse
    </table>
</body>
</html>
