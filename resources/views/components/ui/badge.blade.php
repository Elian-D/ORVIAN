<div {{ $attributes->merge(['class' => $getBadgeClasses(), 'style' => $getCustomStyles()]) }}>
    @if($dot)
        <span class="{{ $getDotClasses() }}" style="{{ $getDotStyles() }}"></span>
    @endif
    
    <span>{{ $slot }}</span>
</div>