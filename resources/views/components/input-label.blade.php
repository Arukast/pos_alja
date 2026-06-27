@props(['value']) @php $mergedAttributes = $attributes->merge(['class' => 'block
font-medium text-sm text-light-text dark:text-gray-300']); @endphp

<label {{ $mergedAttributes }}> {{ $value ?? $slot }} </label>
