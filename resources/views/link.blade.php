@if ($url == url()->current())
    <span {{ $attributes->merge(['class' => $currentClass]) }}>{{ $slot->isNotEmpty() ? $slot : $title }}</span>
@else
    <a href="{{ $url }}" target="{{ $target }}" title="{{ $title }}"
        {{ $attributes }}>{{ $slot->isNotEmpty() ? $slot : $title }}</a>
@endif
