@php
    use App\Support\Nps\NpsBuckets;

    /** @var NpsBuckets $buckets */
    $total = max(1, $buckets->promoters + $buckets->detractors + $buckets->neutrals + $buckets->excluded);
    $promoterPct = round(($buckets->promoters / $total) * 100, 1);
    $detractorPct = round(($buckets->detractors / $total) * 100, 1);
    $neutralPct = round(($buckets->neutrals / $total) * 100, 1);
    $excludedPct = round(($buckets->excluded / $total) * 100, 1);

    $barWidth = 280;
    $barHeight = 18;
    $promoterWidth = ($buckets->promoters / $total) * $barWidth;
    $detractorWidth = ($buckets->detractors / $total) * $barWidth;
    $neutralWidth = ($buckets->neutrals / $total) * $barWidth;
    $excludedWidth = ($buckets->excluded / $total) * $barWidth;
@endphp

<svg width="100%" height="{{ $barHeight + 52 }}" viewBox="0 0 320 {{ $barHeight + 52 }}" xmlns="http://www.w3.org/2000/svg" role="img">
    <rect x="0" y="0" width="{{ $promoterWidth }}" height="{{ $barHeight }}" fill="#059669" rx="4" />
    @if ($showNeutrals)
        <rect x="{{ $promoterWidth }}" y="0" width="{{ $neutralWidth }}" height="{{ $barHeight }}" fill="#64748b" />
    @endif
    <rect x="{{ $promoterWidth + ($showNeutrals ? $neutralWidth : 0) }}" y="0" width="{{ $detractorWidth }}" height="{{ $barHeight }}" fill="#dc2626" />
    @if ($buckets->excluded > 0)
        <rect x="{{ $promoterWidth + ($showNeutrals ? $neutralWidth : 0) + $detractorWidth }}" y="0" width="{{ $excludedWidth }}" height="{{ $barHeight }}" fill="#cbd5e1" />
    @endif

    <text x="0" y="{{ $barHeight + 14 }}" font-size="8" fill="#475569">{{ __('survey_batches.pdf.promoters') }}: {{ $buckets->promoters }} ({{ number_format($promoterPct, 1, ',', '.') }}%)</text>
    @if ($showNeutrals)
        <text x="0" y="{{ $barHeight + 26 }}" font-size="8" fill="#475569">{{ __('survey_batches.pdf.neutrals') }}: {{ $buckets->neutrals }} ({{ number_format($neutralPct, 1, ',', '.') }}%)</text>
    @endif
    <text x="0" y="{{ $barHeight + ($showNeutrals ? 38 : 26) }}" font-size="8" fill="#475569">{{ __('survey_batches.pdf.detractors') }}: {{ $buckets->detractors }} ({{ number_format($detractorPct, 1, ',', '.') }}%)</text>
    @if ($buckets->excluded > 0)
        <text x="0" y="{{ $barHeight + ($showNeutrals ? 50 : 38) }}" font-size="8" fill="#475569">{{ __('survey_batches.pdf.excluded') }}: {{ $buckets->excluded }} ({{ number_format($excludedPct, 1, ',', '.') }}%)</text>
    @endif
</svg>
