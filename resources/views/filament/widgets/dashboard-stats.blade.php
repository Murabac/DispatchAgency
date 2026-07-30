<div wire:key="dashboard-stats-{{ md5(json_encode($this->filters)) }}" class="dl-dash-stats">
    @foreach ($cards as $card)
        <div class="dl-dash-stat dl-dash-stat--{{ $card['tone'] }}">
            <div class="dl-dash-stat__head">{{ $card['label'] }}</div>
            <div class="dl-dash-stat__body">
                <div class="dl-dash-stat__amount">{{ $card['amount'] }}</div>
            </div>
            <div class="dl-dash-stat__foot">
                <span>{{ $card['meta'] }}</span>
                @if ($card['show_trend'])
                    @php
                        $trend = (float) $card['trend'];
                        $up = $trend >= 0;
                    @endphp
                    <span @class([
                        'dl-dash-stat__trend',
                        'is-up' => $up,
                        'is-down' => ! $up,
                    ])>
                        {{ number_format(abs($trend), $trend == floor($trend) ? 0 : 2) }}%
                        @if ($up)
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.53.22l4.5 4.5a.75.75 0 11-1.06 1.06L10.75 5.56v10.69a.75.75 0 01-1.5 0V5.56L6.03 8.78a.75.75 0 01-1.06-1.06l4.5-4.5A.75.75 0 0110 3z" clip-rule="evenodd"/></svg>
                        @else
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 01-.53-.22l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3.75a.75.75 0 011.5 0v10.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5A.75.75 0 0110 17z" clip-rule="evenodd"/></svg>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    @endforeach
</div>
