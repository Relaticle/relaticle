@props([
    'role' => 'user',
    'content' => '',
])

@if($role === 'user')
    <div class="flex justify-end">
        <div class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-3 text-sm text-white">
            {{ $content }}
        </div>
    </div>
@else
    <div class="flex justify-start">
        <div class="max-w-[80%] rounded-2xl rounded-bl-md bg-gray-100 px-4 py-3 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100">
            {!! $content !!}
        </div>
    </div>
@endif
