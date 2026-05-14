@props(['user' => null, 'size' => 16])

@php
    $user = $user ?? auth()->user();
    $sizeClass = "w-{$size} h-{$size}";
    $firstLetter = strtoupper(substr($user->username ?? $user->name, 0, 1));
    $defaultPlaceholder = "https://placehold.co/600x600/transparent/198bce?text=" . urlencode($firstLetter);
    $photoUrl = $user->photo_url ?? $defaultPlaceholder;
    $uniqueId = 'profile-photo-' . uniqid();
@endphp

<div class="relative {{ $sizeClass }}" id="{{ $uniqueId }}">
    {{-- Micro Loader / Pulse Effect --}}
    <div class="absolute inset-0 rounded-full bg-gray-50 flex items-center justify-center z-10" id="loader-{{ $uniqueId }}">
        <div class="w-3 h-3 bg-gray-300 rounded-full animate-pulse mx-1" style="animation-delay: 150ms"></div>
    </div>
    
    {{-- Actual Image --}}
    <img 
        src="{{ $photoUrl }}"
        alt="{{ $user->name ?? $user->username }}"
        class="{{ $sizeClass }} rounded-full object-cover border-2 border-primary opacity-0 transition-opacity duration-300"
        onerror="this.onerror=null; this.src='https://placehold.co/600x600/transparent/198bce?text=' + encodeURIComponent(this.alt.charAt(0).toUpperCase())"
        onload="handleImageLoad('{{ $uniqueId }}', this)"
        loading="lazy"
        style="position: relative; z-index: 5;"
    >
</div>

<script>
function handleImageLoad(uniqueId, imgElement) {
    const loader = document.getElementById(`loader-${uniqueId}`);
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 300);
    }
    imgElement.style.opacity = '1';
}
</script>