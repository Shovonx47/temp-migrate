<script lang="ts">
	import { spAuth } from '$lib/stores/spAuth.svelte.js';

	let distributions = $state([
		{
			id: 501,
			customer: 'Rahim Telecom',
			amount: 1500.00,
			notes: 'Cash payment received at office',
			date: '2026-10-18 15:30'
		},
		{
			id: 500,
			customer: 'Apex Super Shop',
			amount: 5000.00,
			notes: 'Monthly corporate SMS quota',
			date: '2026-10-15 11:20'
		}
	]);
</script>

<svelte:head>
	<title>Wallet & Distribution — SMS Whiteinfo S P Reseller</title>
</svelte:head>

<div class="space-y-6">
	<div>
		<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Reseller Wallet & Ledger</h1>
		<p class="text-xs text-gray-500 dark:text-gray-400">View available credit balance, wallet history, and distributions to your clients.</p>
	</div>

	<!-- Balance Banner Card -->
	<div class="rounded-xl border border-amber-200 bg-amber-50/50 p-6 dark:border-amber-900/40 dark:bg-amber-950/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
		<div>
			<span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Available Reseller Balance</span>
			<div class="text-3xl font-extrabold text-amber-950 dark:text-amber-200 font-mono mt-1">
				৳{spAuth.balance.toLocaleString('en-US', { minimumFractionDigits: 2 })} <span class="text-sm font-normal text-amber-600">BDT</span>
			</div>
			<p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Credits allocated from WhiteInfo Administrator.</p>
		</div>
		<div>
			<a
				href="/sp/customers"
				class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-amber-600 transition"
			>
				Distribute to Client
			</a>
		</div>
	</div>

	<!-- Distribution History Table -->
	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Distribution Ledger</h2>
		<div class="overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead>
					<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
						<th class="py-2.5">ID</th>
						<th class="py-2.5">Recipient Customer</th>
						<th class="py-2.5">Amount</th>
						<th class="py-2.5">Notes</th>
						<th class="py-2.5">Date</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
					{#each distributions as d}
						<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
							<td class="py-3 font-mono text-gray-500">#{d.id}</td>
							<td class="py-3 font-semibold text-gray-900 dark:text-white">{d.customer}</td>
							<td class="py-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">৳{d.amount.toFixed(2)}</td>
							<td class="py-3 text-gray-500">{d.notes}</td>
							<td class="py-3 text-gray-400 font-mono text-[11px]">{d.date}</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</div>
</div>
