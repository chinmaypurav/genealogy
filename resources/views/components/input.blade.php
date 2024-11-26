@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => $disabled
        ? 'block w-full rounded border-gray-300 bg-gray-100 text-gray-500 shadow-sm cursor-not-allowed'
        : 'block w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm',
]) !!}>
