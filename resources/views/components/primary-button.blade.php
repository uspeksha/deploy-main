@props(['type' => 'submit'])

<button {{ $attributes->merge([
    'type' => $type,
    'class' => 'ui-button-primary'
]) }}>
    {{ $slot }}
</button>
