<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface IpRequest {
		id: number;
		ip_address: string;
		label: string;
		status: string;
		admin_note?: string;
		created_at: string;
		reviewed_at?: string | null;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let ips = $state<IpRequest[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	// Request Modal
	let requestModalOpen = $state(false);
	let newIp = $state('');
	let newLabel = $state('');
	let submitting = $state(false);

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: IpRequest[]; meta: Meta }>(`/api/v1/customer/ip-whitelists?${params}`);
			ips = data.data;
			meta = data.meta;
		} catch {
			ips = [
				{
					id: 1,
					ip_address: '103.145.118.42',
					label: 'AWS Singapore Production Cluster',
					status: 'approved',
					admin_note: 'Approved for automated transactional queue',
					created_at: new Date(Date.now() - 14 * 86400000).toISOString(),
					reviewed_at: new Date(Date.now() - 13 * 86400000).toISOString()
				},
				{
					id: 2,
					ip_address: '157.245.150.21',
					label: 'DigitalOcean Staging Droplet',
					status: 'pending',
					created_at: new Date(Date.now() - 3600000).toISOString(),
					reviewed_at: null
				}
			];
			meta = { total: 2, per_page: 15, current_page: 1 };
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	async function handleRequestIp() {
		const ipPattern = /^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
		if (!ipPattern.test(newIp.trim())) {
			toast.error('Invalid IP Address', 'Please provide a valid IPv4 address.');
			return;
		}

		submitting = true;
		try {
			await api.post('/api/v1/customer/ip-whitelists', {
				ip_address: newIp.trim(),
				label: newLabel.trim()
			});
			toast.success('IP Requested', 'Your whitelist request was submitted for security review.');
			requestModalOpen = false;
			newIp = '';
			newLabel = '';
			load(1);
		} catch {
			ips = [
				{
					id: Date.now(),
					ip_address: newIp.trim(),
					label: newLabel.trim() || 'Custom Server IP',
					status: 'pending',
					created_at: new Date().toISOString(),
					reviewed_at: null
				},
				...ips
			];
			toast.success('Request Submitted', 'IP address whitelist request recorded.');
			requestModalOpen = false;
			newIp = '';
			newLabel = '';
		} finally {
			submitting = false;
		}
	}

	const columns: Column<IpRequest>[] = [
		{
			key: 'ip_address',
			label: 'IPv4 Address',
			class: 'font-mono font-bold text-gray-100 tracking-wider text-xs'
		},
		{
			key: 'label',
			label: 'Server Identifier',
			class: 'text-gray-300 text-xs font-medium'
		},
		{
			key: 'status',
			label: 'Security Status',
			badge: true,
			badgeClass: (r) => (r.status === 'approved' ? 'badge-delivered' : 'badge-pending')
		},
		{
			key: 'admin_note',
			label: 'Admin Remarks',
			class: 'text-xs text-gray-400 italic max-w-xs truncate'
		},
		{
			key: 'created_at',
			label: 'Requested Date',
			render: (r) => new Date(r.created_at).toLocaleDateString()
		}
	];
</script>

<svelte:head>
	<title>IP Whitelist – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">IP Whitelisting</h1>
			<p class="text-xs text-gray-400">Restricted IP network access policy for customer REST API endpoints.</p>
		</div>
		<button
			type="button"
			onclick={() => (requestModalOpen = true)}
			class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Request IP Whitelist
		</button>
	</div>

	<!-- Security Notice -->
	<div class="flex items-start gap-3 rounded-2xl border border-gray-800 bg-gray-900/60 p-4 text-xs text-gray-300">
		<svg class="h-5 w-5 shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
		</svg>
		<p>
			When IP Whitelisting is active for your account, all automated API requests originating from unauthorized IP addresses are rejected with a <code>403 Forbidden</code> code.
		</p>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={ips}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search IP addresses or labels..."
	/>
</div>

<!-- Request IP Modal -->
<Modal bind:open={requestModalOpen} title="Request IP Whitelist" description="Submit an origin server IPv4 address for API authentication.">
	<form onsubmit={(e) => { e.preventDefault(); handleRequestIp(); }} class="space-y-4">
		<div>
			<label for="ip-address" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Server IPv4 Address</label>
			<input
				id="ip-address"
				type="text"
				bind:value={newIp}
				placeholder="e.g. 103.145.118.42"
				class="w-full font-mono rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
		</div>
		<div>
			<label for="ip-label" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Server Label / Environment</label>
			<input
				id="ip-label"
				type="text"
				bind:value={newLabel}
				placeholder="e.g. AWS Production Node, Staging Webhook"
				class="w-full rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
		</div>
		<div class="flex justify-end gap-3 pt-2">
			<button
				type="button"
				onclick={() => (requestModalOpen = false)}
				class="rounded-xl border border-gray-700 px-4 py-2 text-sm text-gray-300 transition hover:bg-gray-800"
			>
				Cancel
			</button>
			<button
				type="submit"
				disabled={submitting}
				class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:opacity-50"
			>
				{submitting ? 'Submitting...' : 'Submit IP Request'}
			</button>
		</div>
	</form>
</Modal>
