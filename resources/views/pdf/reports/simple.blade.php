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

    @if(!empty($report['stats']))
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
    @endif

    <h2>Records</h2>
    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No records in this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
