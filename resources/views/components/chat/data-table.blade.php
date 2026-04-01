@props([
    'columns' => [],
    'rows' => [],
    'total' => 0,
    'showAllUrl' => null,
])

<div class="my-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        @if(count($columns) > 0)
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach($columns as $column)
                        <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
            @foreach($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    @foreach($row as $cell)
                        <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $cell }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($total > count($rows))
        <div class="border-t border-gray-200 bg-gray-50 px-3 py-2 text-center dark:border-gray-700 dark:bg-gray-800">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Showing {{ count($rows) }} of {{ $total }} results
                @if($showAllUrl)
                    &middot; <a href="{{ $showAllUrl }}" class="text-primary-600 hover:underline dark:text-primary-400">View all</a>
                @endif
            </span>
        </div>
    @endif
</div>
