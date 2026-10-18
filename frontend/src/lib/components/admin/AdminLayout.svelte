<script lang="ts">
	import { adminAuth } from '$lib/stores/adminAuth.svelte.js';
	import { goto } from '$app/navigation';
	import AdminSidebar from '$lib/components/admin/AdminSidebar.svelte';
	import AdminNavbar from '$lib/components/admin/AdminNavbar.svelte';
	import { onMount } from 'svelte';

	let { children } = $props();
	let sidebarCollapsed = $state(false);

	onMount(async () => {
		await adminAuth.init();
		if (!adminAuth.isLoggedIn) {
			goto('/superman/login');
		}
	});
</script>

{#if adminAuth.initialized && adminAuth.isLoggedIn}
	<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
		<AdminSidebar bind:collapsed={sidebarCollapsed} />

		<div class="flex min-w-0 flex-1 flex-col overflow-hidden">
			<AdminNavbar
				sidebarCollapsed={sidebarCollapsed}
				onToggleSidebar={() => (sidebarCollapsed = !sidebarCollapsed)}
			/>

			<main class="flex-1 overflow-y-auto p-4 lg:p-6">
				<div class="animate-fade-in">
					{@render children()}
				</div>
			</main>
		</div>
	</div>
{:else if !adminAuth.initialized}
	<div class="flex h-screen items-center justify-center bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
		<div class="flex flex-col items-center gap-4">
			<img src="/logo.png" alt="SMS Whiteinfo" class="h-10 w-auto object-contain animate-pulse" />
			<div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-emerald-600 dark:border-gray-700 dark:border-t-emerald-500"></div>
			<p class="text-sm text-gray-500">Loading Superman Administrator Panel…</p>
		</div>
	</div>
{/if}
