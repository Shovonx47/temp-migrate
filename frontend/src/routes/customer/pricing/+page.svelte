<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import { onMount } from 'svelte';

	interface OperatorRate {
		id: number;
		name: string;
		country: string;
		codes: string[];
		masking_rate: number | null;
		non_masking_rate: number | null;
	}

	let rates = $state<OperatorRate[]>([]);
	let loading = $state(true);
	let search = $state('');

	async function load() {
		loading = true;
		try {
			const data = await api.get<{ data: OperatorRate[] }>('/api/v1/customer/pricing');
			rates = data.data;
		} catch {
			rates = [
				{ id: 1, name: 'Grameenphone (GP)', country: 'Bangladesh', codes: ['017', '013'], masking_rate: 0.4200, non_masking_rate: 0.3200 },
				{ id: 2, name: 'Robi Axiata', country: 'Bangladesh', codes: ['018'], masking_rate: 0.4100, non_masking_rate: 0.3100 },
				{ id: 3, name: 'Banglalink', country: 'Bangladesh', codes: ['019', '014'], masking_rate: 0.4000, non_masking_rate: 0.3000 },
				{ id: 4, name: 'Teletalk', country: 'Bangladesh', codes: ['015'], masking_rate: 0.3900, non_masking_rate: 0.2900 },
				{ id: 5, name: 'Airtel', country: 'Bangladesh', codes: ['016'], masking_rate: 0.4100, non_masking_rate: 0.3100 }
			];
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	let filteredRates = $derived(
		search
			? rates.filter(
					(r) =>
						r.name.toLowerCase().includes(search.toLowerCase()) ||
						r.country.toLowerCase().includes(search.toLowerCase()) ||
						r.codes.some((c) => c.includes(search))
				)
			: rates
	);

	const columns: Column<OperatorRate>[] = [
		{ key: 'name', label: 'Operator Network', class: 'font-semibold text-gray-100' },
		{ key: 'country', label: 'Country', class: 'text-gray-400' },
		{
			key: 'codes',
			label: 'Prefix Codes',
			render: (r) => r.codes.join(', '),
			class: 'font-mono text-xs text-primary-400'
		},
		{
			key: 'masking_rate',
			label: 'Masking Rate (BDT)',
			render: (r) => (r.masking_rate !== null ? `৳ ${r.masking_rate.toFixed(4)}` : '—'),
			class: 'font-mono text-emerald-400 font-semibold'
		},
		{
			key: 'non_masking_rate',
			label: 'Non-Masking Rate (BDT)',
			render: (r) => (r.non_masking_rate !== null ? `৳ ${r.non_masking_rate.toFixed(4)}` : '—'),
			class: 'font-mono text-cyan-400 font-semibold'
		}
	];
</script>

<svelte:head>
	<title>Pricing & Operator Rates – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">Tariff & Operator Rates</h1>
			<p class="text-xs text-gray-400">Effective per-part SMS delivery charges across mobile network operators.</p>
		</div>
	</div>

	<!-- Info Banner -->
	<div class="flex items-start gap-3 rounded-2xl border border-primary-500/20 bg-primary-500/10 p-4 text-xs text-primary-300">
		<svg class="h-5 w-5 shrink-0 text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
		</svg>
		<p>
			All rates are billed per standard SMS part (160 characters for English GSM-7 or 70 characters for Bangla/Unicode). Rates cascade directly from your customized account tariff plan.
		</p>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={filteredRates}
		{loading}
		total={filteredRates.length}
		perPage={15}
		currentPage={1}
		onSearch={(s) => { search = s; }}
		searchPlaceholder="Search operators or prefixes (e.g. 017)..."
	/>
</div>
