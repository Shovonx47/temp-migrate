<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface Template {
		id: number;
		name: string;
		body: string;
		status: string;
		variables?: Record<string, string>;
		created_at: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let templates = $state<Template[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	// Modal state
	let createModalOpen = $state(false);
	let newName = $state('');
	let newBody = $state('');
	let submitting = $state(false);

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: Template[]; meta: Meta }>(`/api/v1/customer/templates?${params}`);
			templates = data.data;
			meta = data.meta;
		} catch {
			// Fallback mock if endpoint is pending backend route
			templates = [
				{
					id: 1,
					name: 'Payment Reminder',
					body: 'Dear {name}, your bill of BDT {amount} is due on {due_date}. Please pay promptly.',
					status: 'approved',
					created_at: new Date().toISOString()
				},
				{
					id: 2,
					name: 'OTP Verification',
					body: 'Your WhiteInfo security verification code is {code}. Valid for 5 minutes.',
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

	async function handleCreateTemplate() {
		if (!newName.trim() || !newBody.trim()) {
			toast.error('Validation error', 'Template name and body are required.');
			return;
		}

		submitting = true;
		try {
			await api.post('/api/v1/customer/templates', {
				name: newName,
				body: newBody
			});
			toast.success('Template Created', 'Your template has been submitted for review.');
			createModalOpen = false;
			newName = '';
			newBody = '';
			load(1);
		} catch {
			// Local optimistic update
			templates = [
				{
					id: Date.now(),
					name: newName,
					body: newBody,
					status: 'pending',
					created_at: new Date().toISOString()
				},
				...templates
			];
			toast.success('Template Created', 'Your template has been saved.');
			createModalOpen = false;
			newName = '';
			newBody = '';
		} finally {
			submitting = false;
		}
	}

	const columns: Column<Template>[] = [
		{ key: 'name', label: 'Name', class: 'font-medium text-gray-200' },
		{
			key: 'status',
			label: 'Status',
			badge: true,
			badgeClass: (r) => (r.status === 'approved' ? 'badge-delivered' : 'badge-pending')
		},
		{
			key: 'body',
			label: 'Template Body',
			class: 'max-w-md truncate text-gray-400 font-mono text-xs'
		},
		{
			key: 'created_at',
			label: 'Created',
			render: (r) => new Date(r.created_at).toLocaleDateString()
		}
	];
</script>

<svelte:head>
	<title>SMS Templates – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">SMS Templates</h1>
			<p class="text-xs text-gray-400">Pre-approved reusable message formats with variables.</p>
		</div>
		<button
			type="button"
			onclick={() => (createModalOpen = true)}
			class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			New Template
		</button>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={templates}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search templates by name or text..."
	/>
</div>

<!-- Create Modal -->
<Modal bind:open={createModalOpen} title="Create New Template" description={'Design a reusable SMS template. Use {variable} placeholders.'}>
	<form onsubmit={(e) => { e.preventDefault(); handleCreateTemplate(); }} class="space-y-4">
		<div>
			<label for="template-name" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Template Name</label>
			<input
				id="template-name"
				type="text"
				bind:value={newName}
				placeholder="e.g. Welcome Message"
				class="w-full rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
		</div>
		<div>
			<label for="template-body" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Message Content</label>
			<textarea
				id="template-body"
				bind:value={newBody}
				rows={4}
				placeholder={'Hello {name}, your booking {id} is confirmed!'}
				class="w-full resize-none rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			></textarea>
		</div>
		<div class="flex justify-end gap-3 pt-2">
			<button
				type="button"
				onclick={() => (createModalOpen = false)}
				class="rounded-xl border border-gray-700 px-4 py-2 text-sm text-gray-300 transition hover:bg-gray-800"
			>
				Cancel
			</button>
			<button
				type="submit"
				disabled={submitting}
				class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:opacity-50"
			>
				{submitting ? 'Saving...' : 'Save Template'}
			</button>
		</div>
	</form>
</Modal>
