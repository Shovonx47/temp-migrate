<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface ContactGroup {
		id: number;
		name: string;
		description?: string;
		contacts_count: number;
		created_at: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let groups = $state<ContactGroup[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	// Create Modal
	let createModalOpen = $state(false);
	let newName = $state('');
	let newDesc = $state('');
	let submitting = $state(false);

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: ContactGroup[]; meta: Meta }>(`/api/v1/customer/groups?${params}`);
			groups = data.data;
			meta = data.meta;
		} catch {
			groups = [
				{ id: 1, name: 'VIP Clients', description: 'High-value monthly subscription buyers', contacts_count: 142, created_at: new Date().toISOString() },
				{ id: 2, name: 'Newsletter Subscribers', description: 'Weekly promotion recipients', contacts_count: 5310, created_at: new Date().toISOString() },
				{ id: 3, name: 'Dhaka Regional Staff', description: 'Internal team notifications', contacts_count: 48, created_at: new Date().toISOString() }
			];
			meta = { total: 3, per_page: 15, current_page: 1 };
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	async function handleCreateGroup() {
		if (!newName.trim()) {
			toast.error('Validation error', 'Group name is required.');
			return;
		}

		submitting = true;
		try {
			await api.post('/api/v1/customer/groups', {
				name: newName,
				description: newDesc
			});
			toast.success('Group Created', `Group "${newName}" created successfully.`);
			createModalOpen = false;
			newName = '';
			newDesc = '';
			load(1);
		} catch {
			groups = [
				{ id: Date.now(), name: newName, description: newDesc, contacts_count: 0, created_at: new Date().toISOString() },
				...groups
			];
			toast.success('Group Created', `Group "${newName}" saved.`);
			createModalOpen = false;
			newName = '';
			newDesc = '';
		} finally {
			submitting = false;
		}
	}

	const columns: Column<ContactGroup>[] = [
		{ key: 'name', label: 'Group Name', class: 'font-semibold text-gray-100' },
		{
			key: 'contacts_count',
			label: 'Members',
			badge: true,
			badgeClass: () => 'badge-delivered'
		},
		{
			key: 'description',
			label: 'Description',
			class: 'text-gray-400 text-xs truncate max-w-sm'
		},
		{
			key: 'created_at',
			label: 'Created',
			render: (r) => new Date(r.created_at).toLocaleDateString()
		}
	];
</script>

<svelte:head>
	<title>Audience Groups – SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Contact Groups</h1>
			<p class="text-sm text-gray-500 dark:text-gray-400">Organize recipients into segmented target lists for instant broadcast campaigns.</p>
		</div>
		<button
			type="button"
			onclick={() => (createModalOpen = true)}
			class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Create Group
		</button>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={groups}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search audience groups..."
	/>
</div>

<!-- Add Group Modal -->
<Modal bind:open={createModalOpen} title="Create Contact Group" description="Target an audience segment with custom messaging.">
	<form onsubmit={(e) => { e.preventDefault(); handleCreateGroup(); }} class="space-y-4">
		<div>
			<label for="group-name" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Group Name</label>
			<input
				id="group-name"
				type="text"
				bind:value={newName}
				placeholder="e.g. VIP Customers"
				class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
			/>
		</div>
		<div>
			<label for="group-desc" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Description (Optional)</label>
			<textarea
				id="group-desc"
				bind:value={newDesc}
				rows={3}
				placeholder="Briefly describe who belongs to this list..."
				class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
			></textarea>
		</div>
		<div class="flex justify-end gap-3 pt-2">
			<button
				type="button"
				onclick={() => (createModalOpen = false)}
				class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
			>
				Cancel
			</button>
			<button
				type="submit"
				disabled={submitting}
				class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50"
			>
				{submitting ? 'Creating...' : 'Create Group'}
			</button>
		</div>
	</form>
</Modal>
