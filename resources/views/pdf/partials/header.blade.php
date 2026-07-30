@php
    $settings = $settings ?? \App\Models\Setting::query()->first();

    $logoSrc = null;
    $candidates = array_filter([
        public_path('images/logo.png'),
        $settings?->logo_path ? public_path($settings->logo_path) : null,
        $settings?->logo_path ? storage_path('app/public/' . ltrim($settings->logo_path, '/')) : null,
    ]);

    foreach ($candidates as $logoPath) {
        if ($logoPath && is_file($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            break;
        }
    }

    $addressLines = array_values(array_filter(array_map(
        'trim',
        preg_split('/\r\n|\r|\n/', (string) ($settings?->address ?? '')) ?: []
    )));

    // Keep long single-line addresses readable in the header.
    if (count($addressLines) === 1 && strlen($addressLines[0]) > 55) {
        $parts = array_map('trim', explode(',', $addressLines[0]));
        if (count($parts) >= 2) {
            $mid = (int) ceil(count($parts) / 2);
            $addressLines = [
                implode(', ', array_slice($parts, 0, $mid)),
                implode(', ', array_slice($parts, $mid)),
            ];
        }
    }
@endphp
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" class="logo" alt="Dispatch Logistics">
                @else
                    <div class="header-fallback-name">{{ $settings?->business_name ?? 'Dispatch Logistics' }}</div>
                @endif
            </td>
            <td class="header-meta-cell">
                <div class="biz-meta">
                    @foreach ($addressLines as $line)
                        <div class="line">{{ $line }}</div>
                    @endforeach
                    @if ($settings?->phone)
                        <div class="line">Tel: {{ $settings->phone }}</div>
                    @endif
                    @if ($settings?->email)
                        <div class="line">{{ $settings->email }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>
