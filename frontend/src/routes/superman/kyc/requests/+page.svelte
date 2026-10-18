<script lang="ts">
	let requests = $state([
		{
			id: 2,
			customer_name: 'Rahim Telecom',
			email: 'rahim@telecom.bd',
			phone: '+8801812345678',
			nid_number: '1987564123987',
			business_name: 'Rahim Enterprise',
			trade_license: 'TRAD/DSCC/019283/2024',
			status: 'under_review',
			submitted_at: '2026-10-18 16:45'
		}
	]);

	function handleApprove(id: number, name: string) {
		requests = requests.filter(r => r.id !== id);
		alert(`KYC for ${name} has been approved.`);
	}

	function handleReject(id: number, name: string) {
		const reason = prompt('Please provide reason for KYC rejection:');
		if (reason) {
			requests = requests.filter(r => r.id !== id);
			alert(`KYC for ${name} rejected: ${reason}`);
		}
	}
</script>

<svelte:head>
	<title>KYC Requests — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="space-y-4">
	<div class="flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">KYC Verification Requests</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Review National ID, trade license, and live facial verification files.</p>
		</div>
		<span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-800 dark:bg-purple-950 dark:text-purple-300">
			{requests.length} Pending Review
		</span>
	</div>

	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		{#if requests.length === 0}
			<div class="py-12 text-center text-gray-400 text-xs">
				<p>All KYC requests have been processed. Queue is clear!</p>
			</div>
		{:else}
			<div class="overflow-x-auto">
				<table class="w-full text-left text-xs">
					<thead>
						<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
							<th class="py-2.5">Customer</th>
							<th class="py-2.5">Business & Trade License</th>
							<th class="py-2.5">NID Number</th>
							<th class="py-2.5">Documents</th>
							<th class="py-2.5">Submitted</th>
							<th class="py-2.5 text-right">Decision</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
						{#each requests as r}
							<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
								<td class="py-3">
									<div class="font-semibold text-gray-900 dark:text-white">{r.customer_name}</div>
									<div class="text-[11px] text-gray-400">{r.email} • {r.phone}</div>
								</td>
								<td class="py-3">
									<div class="font-medium text-gray-800 dark:text-gray-200">{r.business_name}</div>
									<div class="text-[11px] font-mono text-gray-400">{r.trade_license}</div>
								</td>
								<td class="py-3 font-mono font-medium text-gray-700 dark:text-gray-300">{r.nid_number}</td>
								<td class="py-3">
									<div class="flex items-center gap-1.5 text-[11px]">
										<span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Front NID</span>
										<span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Back NID</span>
										<span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Live Photo</span>
									</div>
								</td>
								<td class="py-3 text-[11px] text-gray-400 font-mono">{r.submitted_at}</td>
								<td class="py-3 text-right">
									<div class="inline-flex items-center gap-2">
										<button
											type="button"
											onclick={() => handleApprove(r.id, r.customer_name)}
											class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition"
										>
											Approve
										</button>
										<button
											type="button"
											onclick={() => handleReject(r.id, r.customer_name)}
											class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 transition"
										>
											Reject
										</button>
									</div>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		{/if}
	</div>
</div>
