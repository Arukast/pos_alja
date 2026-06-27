@props(['messages']) @if ($messages) @php $mergedAttributes =
$attributes->merge(['class' => 'text-xs font-medium text-red-600
dark:text-red-400 space-y-1']); @endphp
<ul {{ $mergedAttributes }}>
    @foreach ((array) $messages as $message)
    <li>{{ $message }}</li>
    @endforeach
</ul>
@endif
