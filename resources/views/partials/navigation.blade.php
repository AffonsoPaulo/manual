@foreach ($items as $item)
@php $hasChildren = $item['children'] !== []; @endphp
@php $expanded = ($item['expanded'] || $item['active']) ? 'true' : 'false'; @endphp
<div class="manual-nav-group">
    @if ($hasChildren)
    <div class="manual-nav-row">
        @if ($item['url'])
        <a href="{{ $item['url'] }}" class="manual-nav-item" data-active="{{ $item['active'] ? 'true' : 'false' }}">
            {{ $item['label'] }}
        </a>
        @else
        <span class="manual-nav-item">{{ $item['label'] }}</span>
        @endif
        <button
            class="manual-nav-toggle"
            data-nav-toggle
            data-expanded="{{ $expanded }}"
            aria-expanded="{{ $expanded }}"
            aria-label="{{ $item['expanded'] || $item['active'] ? 'Recolher' : 'Expandir' }} {{ $item['label'] }}">
            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                <path d="M3 1.5l3.5 3.5L3 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
    </div>
    <div class="manual-nav-children" data-expanded="{{ $expanded }}">
        @include('manual::partials.navigation', ['items' => $item['children']])
    </div>
    @else
    @if ($item['url'])
    <a href="{{ $item['url'] }}" class="manual-nav-item" data-active="{{ $item['active'] ? 'true' : 'false' }}">
        {{ $item['label'] }}
    </a>
    @else
    <span class="manual-nav-item">{{ $item['label'] }}</span>
    @endif
    @endif
</div>
@endforeach
