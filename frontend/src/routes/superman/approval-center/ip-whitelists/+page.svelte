<script lang="ts">
	let whitelists = $state([
		{
			id: 1,
			customer: 'TechSolutions Ltd',
			ip_address: '103.145.120.45',
			notes: 'Production SMS gateway server',
			status: 'approved',
			created_at: '2026-10-15'
		},
		{
			id: 2,
			customer: 'Apex Retail Store',
			ip_address: '182.160.110.22',
			notes: 'ERP automated OTP dispatch server',
			status: 'pending',
			created_at: '2026-10-18'
		}
	]);

	function handleApprove(id: number) {
		const item = whitelists.find(w => w.id === id);
		if (item) item.status = 'approved';
	}

	function handleReject(id: number) {
		const item = whitelists.find(w => w.id === id);
		if (item) item.status = 'rejected';
	}
</script>

<svelte:head>
	<title>IP Whitelists — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="space-y-4">
	<div class="flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">IP Whitelist Requests</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Review server IP addresses authorized to send SMS via Developer API.</p>
		</div>
	</div>

	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<div class="overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead>
					<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
						<th class="py-2.5">Customer</th>
						<th class="py-2.5">IP Address</th>
						<th class="py-2.5">Server Purpose / Notes</th>
						<th class="py-2.5">Status</th>
						<th class="py-2.5 text-right">Actions</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
					{#each whitelists as w}
						<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
							<td class="py-3 font-semibold text-gray-900 dark:text-white">{w.customer}</td>
							<td class="py-3 font-mono font-bold text-gray-800 dark:text-gray-200">{w.ip_address}</td>
							<td class="py-3 text-gray-500 dark:text-gray-400">{w.notes}</td>
							<td class="py-3">
								<span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {w.status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : w.status === 'pending' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'}">
									{w.status}
								</span>
							</td>
							<td class="py-3 text-right">
								{#if w.status === 'pending'}
									<div class="inline-flex items-center gap-1.5">
										<button
											type="button"
											onclick={() => handleApprove(w.id)}
											class="rounded-lg bg-emerald-600 px-2 py-1 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700"
										>
											Approve
										</button>
										<button
											type="button"
											onclick={() => handleReject(w.id)}
											class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400"
										>
											Reject
										</button>
									</div>
								{:else}
									<span class="text-[11px] text-gray-400 italic">No action needed</span>
								{/if}
							</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</div>
</div>
