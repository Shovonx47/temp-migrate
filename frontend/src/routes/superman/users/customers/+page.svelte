<script lang="ts">
	import DataTable from '$lib/components/DataTable.svelte';

	let searchQuery = $state('');

	let customers = $state([
		{
			id: 1,
			name: 'Default Customer',
			email: 'customer@example.com',
			phone: '+8801700000001',
			business_name: 'WhiteInfo Demo Ltd',
			balance: 1420.50,
			kyc_status: 'approved',
			status: 'active',
			created_at: '2026-01-15'
		},
		{
			id: 2,
			name: 'Rahim Telecom',
			email: 'rahim@telecom.bd',
			phone: '+8801812345678',
			business_name: 'Rahim Enterprise',
			balance: 450.00,
			kyc_status: 'under_review',
			status: 'active',
			created_at: '2026-02-10'
		},
		{
			id: 3,
			name: 'Apex Super Shop',
			email: 'info@apexshop.bd',
			phone: '+8801911223344',
			business_name: 'Apex Retail chain',
			balance: 85.00,
			kyc_status: 'approved',
			status: 'active',
			created_at: '2026-03-01'
		},
		{
			id: 4,
			name: 'Karim Electronics',
			email: 'karim@electronics.com',
			phone: '+8801619876543',
			business_name: 'Karim Trading',
			balance: 12.00,
			kyc_status: 'pending',
			status: 'pending',
			created_at: '2026-04-12'
		}
	]);

	let filteredCustomers = $derived(
		customers.filter((c) => {
			if (!searchQuery) return true;
			const q = searchQuery.toLowerCase();
			return (
				c.name.toLowerCase().includes(q) ||
				c.email.toLowerCase().includes(q) ||
				c.phone.includes(q) ||
				c.business_name.toLowerCase().includes(q)
			);
		})
	);
</script>

<svelte:head>
	<title>Customers — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="space-y-4">
	<!-- Page Header -->
	<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Customers</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Manage client accounts, balances, and verification tiers.</p>
		</div>
		<div class="flex items-center gap-2">
			<button
				type="button"
				class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
				New Customer
			</button>
		</div>
	</div>

	<!-- Table Container Card -->
	<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<!-- Search & Filter Bar -->
		<div class="flex items-center justify-between gap-3">
			<div class="relative w-full max-w-xs">
				<input
					type="text"
					bind:value={searchQuery}
					placeholder="Search customers..."
					class="w-full rounded-lg border border-gray-200 bg-gray-50/70 py-1.5 pl-8 pr-3 text-xs outline-none focus:border-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
				/>
				<svg class="absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
			</div>
		</div>

		<!-- Customers Data Table -->
		<div class="overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead>
					<tr class="border-b border-gray-100 dark:border-gray-800 text-gray-400 uppercase text-[10px] font-semibold">
						<th class="py-2.5">Customer</th>
						<th class="py-2.5">Business Name</th>
						<th class="py-2.5">Phone</th>
						<th class="py-2.5">Balance</th>
						<th class="py-2.5">KYC Status</th>
						<th class="py-2.5">Account Status</th>
						<th class="py-2.5 text-right">Actions</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 dark:divide-gray-800">
					{#each filteredCustomers as c}
						<tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
							<td class="py-3 font-medium text-gray-900 dark:text-white">
								<div>{c.name}</div>
								<div class="text-[11px] text-gray-400 font-normal">{c.email}</div>
							</td>
							<td class="py-3 text-gray-600 dark:text-gray-300">{c.business_name}</td>
							<td class="py-3 font-mono text-gray-700 dark:text-gray-300">{c.phone}</td>
							<td class="py-3 font-mono font-bold {c.balance < 100 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'}">
								৳{c.balance.toFixed(2)}
							</td>
							<td class="py-3">
								<span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {c.kyc_status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : c.kyc_status === 'under_review' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'}">
									{c.kyc_status}
								</span>
							</td>
							<td class="py-3">
								<span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {c.status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'}">
									{c.status}
								</span>
							</td>
							<td class="py-3 text-right">
								<div class="inline-flex items-center gap-2">
									<button type="button" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">View</button>
									<button type="button" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400">Edit</button>
								</div>
							</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</div>
</div>
