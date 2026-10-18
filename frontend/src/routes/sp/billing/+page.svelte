<script lang="ts">
	import { spAuth } from '$lib/stores/spAuth.svelte.js';

	let ledger = $state([
		{
			id: 1081,
			type: 'Credit Allocation',
			amount: 50000.00,
			description: 'Wallet top-up approved by Administrator',
			balance_after: 50000.00,
			date: '2026-10-14 10:00'
		},
		{
			id: 1082,
			type: 'Client Distribution',
			amount: -1500.00,
			description: 'Fund transfer to Rahim Telecom',
			balance_after: 48500.00,
			date: '2026-10-18 15:30'
		}
	]);
</script>

<svelte:head>
	<title>Billing & Ledger — SMS Whiteinfo S P Reseller</title>
</svelte:head>

<div class="space-y-4">
	<div>
		<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Billing & Ledger</h1>
		<p class="text-xs text-gray-500 dark:text-gray-400">Statement of credit additions, deductions, and customer transfers.</p>
	</div>

	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<div class="overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead>
					<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
						<th class="py-2.5">Transaction ID</th>
						<th class="py-2.5">Type</th>
						<th class="py-2.5">Amount</th>
						<th class="py-2.5">Description</th>
						<th class="py-2.5">Running Balance</th>
						<th class="py-2.5">Date</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
					{#each ledger as entry}
						<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
							<td class="py-3 font-mono text-gray-500">#{entry.id}</td>
							<td class="py-3 font-semibold text-gray-900 dark:text-white">{entry.type}</td>
							<td class="py-3 font-mono font-bold {entry.amount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}">
								{entry.amount > 0 ? '+' : ''}৳{entry.amount.toFixed(2)}
							</td>
							<td class="py-3 text-gray-600 dark:text-gray-300">{entry.description}</td>
							<td class="py-3 font-mono text-gray-800 dark:text-gray-200 font-semibold">৳{entry.balance_after.toFixed(2)}</td>
							<td class="py-3 text-gray-400 font-mono text-[11px]">{entry.date}</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</div>
</div>
