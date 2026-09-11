<div class="fi-ta-ctn overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10" style="max-width: calc(100vw - 5rem);">
    @if($transactions->isEmpty())
        <p class="p-4 text-sm text-gray-500 dark:text-gray-400">No wallet activity yet.</p>
    @else
        <table class="fi-ta-table w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Date</th>
                    <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Type</th>
                    <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Reason</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Amount</th>
                    <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Balance after</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach($transactions as $transaction)
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-4 py-2">
                            <span class="{{ $transaction->type === 'credit' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ str_replace('_', ' ', ucfirst($transaction->reason)) }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap {{ $transaction->type === 'credit' ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $transaction->type === 'credit' ? '+' : '-' }}₹{{ number_format((float) $transaction->amount, 2) }}
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">₹{{ number_format((float) $transaction->balance_after, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
