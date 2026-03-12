<div {{ $attributes->merge(['class' => $getBadgeClasses()]) }}>
    @if($dot)
        <span class="{{ $getDotClasses() }}"></span>
    @endif
    
    <span>{{ $slot }}</span>
</div>