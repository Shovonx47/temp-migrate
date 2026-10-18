<script lang="ts">
	import { page } from '$app/stores';
	import AdminLayout from '$lib/components/admin/AdminLayout.svelte';

	let { children } = $props();

	let isLoginPage = $derived($page.url.pathname === '/superman/login');
</script>

{#if isLoginPage}
	{@render children()}
{:else}
	<AdminLayout>
		{@render children()}
	</AdminLayout>
{/if}
