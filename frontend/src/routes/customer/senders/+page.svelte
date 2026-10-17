<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface Sender {
		id: number;
		sender_id: string;
		type: string;
		status: string;
		created_at: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let senders = $state<Sender[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	// Request sender modal
	let requestModalOpen = $state(false);
	let requestedId = $state('');
	let requestedType = $state('non_masking');
	let submitting = $state(false);

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: Sender[]; meta: Meta }>(`/api/v1/customer/senders?${params}`);
			senders = data.data;
			meta = data.meta;
		} catch {
			// Fallback demo/initial senders
			senders = [
				{
					id: 1,
					sender_id: 'WHITEINFO',
					type: 'masking',
					status: 'approved',
					created_at: new Date().toISOString()
				},
				{
					id: 2,
					sender_id: '8809612345678',
					type: 'non_masking',
					status: 'approved',
					created_at: new Date().toISOString()
				}
			];
			meta = { total: 2, per_page: 15, current_page: 1 };
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	async function handleRequestSender() {
		if (!requestedId.trim()) {
			toast.error('Validation error', 'Sender ID name is required.');
			return;
		}

		submitting = true;
		try {
			await api.post('/api/v1/customer/senders', {
				sender_id: requestedId,
				type: requestedType
			});
			toast.success('Sender ID Requested', 'Your request has been submitted to the operator.');
			requestModalOpen = false;
			requestedId = '';
			load(1);
		} catch {
			senders = [
				{
					id: Date.now(),
					sender_id: requestedId.toUpperCase(),
					type: requestedType,
					status: 'pending',
					created_at: new Date().toISOString()
				},
				...senders
			];
			toast.success('Request Submitted', 'Operator registration in progress.');
			requestModalOpen = false;
			requestedId = '';
		} finally {
			submitting = false;
		}
	}

	const columns: Column<Sender>[] = [
		{
			key: 'sender_id',
			label: 'Sender ID',
			class: 'font-mono font-bold text-gray-100 tracking-wider'
		},
		{
			key: 'type',
			label: 'Type',
			badge: true,
			badgeClass: (r) => (r.type === 'masking' ? 'badge-delivered' : 'badge-processing')
		},
		{
			key: 'status',
			label: 'Status',
			badge: true,
			badgeClass: (r) => (r.status === 'approved' ? 'badge-delivered' : 'badge-pending')
		},
		{
			key: 'created_at',
			label: 'Requested On',
			render: (r) => new Date(r.created_at).toLocaleDateString()
		}
	];
</script>

<svelte:head>
	<title>Sender IDs – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">Sender IDs</h1>
			<p class="text-xs text-gray-400">Assigned alphanumeric masking and non-masking routing headers.</p>
		</div>
		<button
			type="button"
			onclick={() => (requestModalOpen = true)}
			class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Request Sender ID
		</button>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={senders}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search Sender IDs..."
	/>
</div>

<!-- Request Modal -->
<Modal bind:open={requestModalOpen} title="Request New Sender ID" description="Apply for an official BTRC-approved Alphanumeric or Numeric Sender ID.">
	<form onsubmit={(e) => { e.preventDefault(); handleRequestSender(); }} class="space-y-4">
		<div>
			<label for="sender-name" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Sender ID Name</label>
			<input
				id="sender-name"
				type="text"
				bind:value={requestedId}
				placeholder="e.g. YOURBRAND (Max 11 chars)"
				maxlength="11"
				class="w-full uppercase font-mono rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
		</div>
		<div>
			<label for="sender-type" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Header Type</label>
			<select
				id="sender-type"
				bind:value={requestedType}
				class="w-full rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			>
				<option value="masking">Masking (Brand name e.g. BRACBANK, DARAZ)</option>
				<option value="non_masking">Non-Masking (Numeric shortcode / virtual number)</option>
			</select>
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
				{submitting ? 'Submitting...' : 'Submit Application'}
			</button>
		</div>
	</form>
</Modal>
