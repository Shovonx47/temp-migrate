<script lang="ts">
	let searchQuery = $state('');

	let logs = $state([
		{
			id: 8812,
			customer: 'Default Customer',
			recipient: '8801712345678',
			body: 'Your verification OTP is 492018. Valid for 5 minutes.',
			gateway: 'ReveSMS Primary',
			cost: 0.28,
			status: 'delivered',
			created_at: '2026-10-18 19:42:10'
		},
		{
			id: 8811,
			customer: 'Apex Retail Store',
			recipient: '8801823456789',
			body: 'Flash sale starts now! Enjoy 20% discount on all winter collections.',
			gateway: 'ReveSMS Primary',
			cost: 0.32,
			status: 'delivered',
			created_at: '2026-10-18 19:35:04'
		},
		{
			id: 8810,
			customer: 'Metro Pharmacy',
			recipient: '8801934567890',
			body: 'Your prescription medicine order #8271 has been dispatched.',
			gateway: 'QSMS Secondary',
			cost: 0.29,
			status: 'delivered',
			created_at: '2026-10-18 19:22:18'
		}
	]);

	let filteredLogs = $derived(
		logs.filter(l => {
			if (!searchQuery) return true;
			const q = searchQuery.toLowerCase();
			return l.recipient.includes(q) || l.body.toLowerCase().includes(q) || l.customer.toLowerCase().includes(q);
		})
	);
</script>

<svelte:head>
	<title>SMS Logs — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="space-y-4">
	<div class="flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">SMS Logs</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Complete historical record of all outbound SMS transmissions across gateways.</p>
		</div>
	</div>

	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<div class="flex items-center justify-between gap-3">
			<div class="relative w-full max-w-xs">
				<input
					type="text"
					bind:value={searchQuery}
					placeholder="Search recipient or body..."
					class="w-full rounded-lg border border-gray-200 bg-gray-50/70 py-1.5 pl-8 pr-3 text-xs outline-none focus:border-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
				/>
				<svg class="absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
			</div>
		</div>

		<div class="overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead>
					<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
						<th class="py-2.5">ID</th>
						<th class="py-2.5">Customer</th>
						<th class="py-2.5">Recipient</th>
						<th class="py-2.5">Message Body</th>
						<th class="py-2.5">Gateway</th>
						<th class="py-2.5">Cost</th>
						<th class="py-2.5">Status</th>
						<th class="py-2.5">Timestamp</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
					{#each filteredLogs as log}
						<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
							<td class="py-3 font-mono text-gray-500">#{log.id}</td>
							<td class="py-3 font-semibold text-gray-900 dark:text-white">{log.customer}</td>
							<td class="py-3 font-mono text-gray-700 dark:text-gray-300">{log.recipient}</td>
							<td class="py-3 text-gray-600 dark:text-gray-300 max-w-md truncate">{log.body}</td>
							<td class="py-3 text-gray-500 dark:text-gray-400">{log.gateway}</td>
							<td class="py-3 font-mono text-emerald-600 dark:text-emerald-400 font-semibold">৳{log.cost.toFixed(2)}</td>
							<td class="py-3">
								<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
									<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
									{log.status}
								</span>
							</td>
							<td class="py-3 text-[11px] font-mono text-gray-400">{log.created_at}</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</div>
</div>
