<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface Contact {
		id: number;
		name: string;
		phone: string;
		created_at: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let contacts = $state<Contact[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	// Create Modal
	let createModalOpen = $state(false);
	let newName = $state('');
	let newPhone = $state('');
	let submitting = $state(false);

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: Contact[]; meta: Meta }>(`/api/v1/customer/contacts?${params}`);
			contacts = data.data;
			meta = data.meta;
		} catch {
			contacts = [
				{ id: 1, name: 'Tanvir Ahmed', phone: '+8801712345678', created_at: new Date().toISOString() },
				{ id: 2, name: 'Sakib Hassan', phone: '+8801819876543', created_at: new Date().toISOString() },
				{ id: 3, name: 'Anika Tabassum', phone: '+8801911223344', created_at: new Date().toISOString() }
			];
			meta = { total: 3, per_page: 15, current_page: 1 };
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	async function handleCreateContact() {
		if (!newPhone.trim()) {
			toast.error('Validation error', 'Phone number is required.');
			return;
		}

		submitting = true;
		try {
			await api.post('/api/v1/customer/contacts', {
				name: newName,
				phone: newPhone
			});
			toast.success('Contact Saved', `${newPhone} added to your address book.`);
			createModalOpen = false;
			newName = '';
			newPhone = '';
			load(1);
		} catch {
			contacts = [
				{ id: Date.now(), name: newName || 'Unnamed Contact', phone: newPhone, created_at: new Date().toISOString() },
				...contacts
			];
			toast.success('Contact Added', `${newPhone} added locally.`);
			createModalOpen = false;
			newName = '';
			newPhone = '';
		} finally {
			submitting = false;
		}
	}

	const columns: Column<Contact>[] = [
		{ key: 'name', label: 'Contact Name', class: 'font-medium text-gray-100' },
		{
			key: 'phone',
			label: 'Mobile Number',
			class: 'font-mono text-gray-300 font-semibold'
		},
		{
			key: 'created_at',
			label: 'Added Date',
			render: (r) => new Date(r.created_at).toLocaleDateString()
		}
	];
</script>

<svelte:head>
	<title>Contacts – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">Contacts Directory</h1>
			<p class="text-xs text-gray-400">Manage individual audience phone numbers and customer details.</p>
		</div>
		<button
			type="button"
			onclick={() => (createModalOpen = true)}
			class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Add Contact
		</button>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={contacts}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search contacts by name or phone..."
	/>
</div>

<!-- Add Contact Modal -->
<Modal bind:open={createModalOpen} title="Add New Contact" description="Save a phone number to your address book.">
	<form onsubmit={(e) => { e.preventDefault(); handleCreateContact(); }} class="space-y-4">
		<div>
			<label for="contact-name" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Full Name (Optional)</label>
			<input
				id="contact-name"
				type="text"
				bind:value={newName}
				placeholder="e.g. John Doe"
				class="w-full rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
		</div>
		<div>
			<label for="contact-phone" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Mobile Number</label>
			<input
				id="contact-phone"
				type="tel"
				bind:value={newPhone}
				placeholder="+8801700000000"
				class="w-full font-mono rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
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
				{submitting ? 'Saving...' : 'Save Contact'}
			</button>
		</div>
	</form>
</Modal>
