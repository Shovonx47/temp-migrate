<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { goto } from '$app/navigation';
	import Sidebar from '$lib/components/Sidebar.svelte';
	import Navbar from '$lib/components/Navbar.svelte';
	import { page } from '$app/stores';
	import { onMount } from 'svelte';

	let { children } = $props();
	let sidebarCollapsed = $state(false);
	let depositModalOpen = $state(false);

	// Page title derived from route
	const pageTitles: Record<string, string> = {
		'/customer': 'Dashboard',
		'/customer/messages/quick': 'Quick Message',
		'/customer/messages/bulk': 'Bulk Message',
		'/customer/messages': 'SMS Logs',
		'/customer/batches': 'Batches',
		'/customer/templates': 'Templates',
		'/customer/senders': 'Sender IDs',
		'/customer/contacts': 'Contacts',
		'/customer/groups': 'Contact Groups',
		'/customer/deposits': 'Deposits',
		'/customer/transactions': 'Transactions',
		'/customer/pricing': 'Pricing',
		'/customer/developer': 'Developer API',
		'/customer/ip-whitelist': 'IP Whitelist',
		'/customer/live-queue': 'Live Message Queue',
		'/customer/analytics': 'Analytics & Reports',
		'/customer/kyc': 'KYC Verification',
		'/customer/profile': 'My Profile'
	};

	let currentTitle = $derived(pageTitles[$page.url.pathname] ?? '');

	onMount(async () => {
		await auth.init();
		if (!auth.isLoggedIn) {
			goto('/login');
		}
	});
</script>

{#if auth.initialized && auth.isLoggedIn}
	<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
		<Sidebar bind:collapsed={sidebarCollapsed} />

		<div class="flex min-w-0 flex-1 flex-col overflow-hidden">
			<Navbar title={currentTitle} onDepositClick={() => goto('/customer/deposits')} />

			<main class="flex-1 overflow-y-auto p-4 sm:p-6">
				<div class="animate-fade-in">
					{@render children()}
				</div>
			</main>
		</div>
	</div>
{:else if !auth.initialized}
	<!-- Loading skeleton -->
	<div class="flex h-screen items-center justify-center bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
		<div class="flex flex-col items-center gap-3">
			<div
				class="h-10 w-10 animate-spin rounded-full border-2 border-gray-300 border-t-teal-600 dark:border-gray-700 dark:border-t-primary-500"
			></div>
			<p class="text-sm text-gray-500">Loading portal…</p>
		</div>
	</div>
{/if}
