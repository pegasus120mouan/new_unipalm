@php
    $normalizedType = strtolower(trim((string) ($type ?? 'voiture')));
    $imageKey = in_array($normalizedType, ['moto', 'voiture', 'tricycle'], true)
        ? $normalizedType
        : 'voiture';
    $imagePath = 'assets/images/camions/'.$imageKey.'.png';
    $label = $label ?? ucfirst($imageKey);
@endphp

<span class="vehicule-type-icon d-inline-flex align-items-center" title="{{ $label }}" aria-label="{{ $label }}">
    <img src="{{ asset($imagePath) }}"
        alt="{{ $label }}"
        class="vehicule-type-img"
        width="40"
        height="40"
        style="object-fit: contain;">
</span>
