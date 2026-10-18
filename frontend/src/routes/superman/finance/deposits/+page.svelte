<script lang="ts">
	let deposits = $state([
		{
			id: 201,
			customer: 'Default Customer',
			amount: 500.00,
			gateway: 'Piprapay (bKash)',
			transaction_id: 'PP_991827461',
			status: 'completed',
			created_at: '2026-10-18 14:20'
		},
		{
			id: 200,
			customer: 'Apex Retail Store',
			amount: 2500.00,
			gateway: 'Piprapay (Nagad)',
			transaction_id: 'PP_991811234',
			status: 'completed',
			created_at: '2026-10-17 11:05'
		}
	]);
</script>

<svelte:head>
	<title>Deposits — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="space-y-4">
	<div class="flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Customer Deposits</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">All inbound payment gateway top-ups and manual balance credits.</p>
		</div>
	</div>

	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<div class="overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead>
					<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
						<th class="py-2.5">Deposit ID</th>
						<th class="py-2.5">Customer</th>
						<th class="py-2.5">Amount</th>
						<th class="py-2.5">Gateway & Channel</th>
						<th class="py-2.5">Transaction ID</th>
						<th class="py-2.5">Status</th>
						<th class="py-2.5">Date & Time</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
					{#each deposits as d}
						<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
							<td class="py-3 font-mono text-gray-500 font-medium">#{d.id}</td>
							<td class="py-3 font-semibold text-gray-900 dark:text-white">{d.customer}</td>
							<td class="py-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">৳{d.amount.toFixed(2)}</td>
							<td class="py-3 text-gray-600 dark:text-gray-300">{d.gateway}</td>
							<td class="py-3 font-mono text-[11px] text-gray-500">{d.transaction_id}</td>
							<td class="py-3">
								<span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {d.status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'}">
									{d.status}
								</span>
							</td>
							<td class="py-3 text-[11px] font-mono text-gray-400">{d.created_at}</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</div>
</div>
