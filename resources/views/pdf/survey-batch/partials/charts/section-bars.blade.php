@php
    $maxBarWidth = 220;
    $barHeight = 12;
    $gap = 22;
    $rows = collect($sections)->filter(fn ($row) => $row['nps_15'] !== null)->values();
    $maxAbs = max(1, $rows->max(fn ($row) => abs($row['nps_15'])) ?? 1);
    $chartHeight = max(1, $rows->count()) * $gap + 8;
@endphp

@if ($rows->isEmpty())
    <p class="empty-state">—</p>
@else
    <svg width="100%" height="{{ $chartHeight }}" viewBox="0 0 320 {{ $chartHeight }}" xmlns="http://www.w3.org/2000/svg" role="img">
        @foreach ($rows as $index => $row)
            @php
                $y = 8 + ($index * $gap);
                $nps = $row['nps_15'];
                $barWidth = (abs($nps) / $maxAbs) * ($maxBarWidth / 2);
                $centerX = 160;
                $color = $nps >= 0 ? '#059669' : '#dc2626';
                $x = $nps >= 0 ? $centerX : $centerX - $barWidth;
            @endphp
            <text x="0" y="{{ $y + 9 }}" font-size="8" fill="#334155">{{ \Illuminate\Support\Str::limit($row['label'], 28) }}</text>
            <line x1="{{ $centerX }}" y1="{{ $y }}" x2="{{ $centerX }}" y2="{{ $y + $barHeight }}" stroke="#cbd5e1" stroke-width="1" />
            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" fill="{{ $color }}" rx="3" />
            <text x="300" y="{{ $y + 9 }}" font-size="8" fill="#0f172a" text-anchor="end">{{ sprintf('%+.1f', $nps) }}</text>
        @endforeach
    </svg>
@endif
