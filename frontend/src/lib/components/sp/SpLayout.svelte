<script lang="ts">
	import { spAuth } from '$lib/stores/spAuth.svelte.js';
	import { goto } from '$app/navigation';
	import SpSidebar from '$lib/components/sp/SpSidebar.svelte';
	import SpNavbar from '$lib/components/sp/SpNavbar.svelte';
	import { onMount } from 'svelte';

	let { children } = $props();
	let sidebarCollapsed = $state(false);

	onMount(async () => {
		await spAuth.init();
		if (!spAuth.isLoggedIn) {
			goto('/sp/login');
		}
	});
</script>

{#if spAuth.initialized && spAuth.isLoggedIn}
	<div class="flex h-screen overflow-hidden bg-gray-50 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
		<SpSidebar bind:collapsed={sidebarCollapsed} />

		<div class="flex min-w-0 flex-1 flex-col overflow-hidden">
			<SpNavbar
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
{:else if !spAuth.initialized}
	<div class="flex h-screen items-center justify-center bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
		<div class="flex flex-col items-center gap-4">
			<img src="/logo.png" alt="SMS Whiteinfo" class="h-10 w-auto object-contain animate-pulse" />
			<div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-amber-500"></div>
			<p class="text-sm text-gray-500">Loading S P Reseller Portal…</p>
		</div>
	</div>
{/if}
