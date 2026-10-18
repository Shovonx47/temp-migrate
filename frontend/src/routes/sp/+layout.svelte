<script lang="ts">
	import { page } from '$app/stores';
	import SpLayout from '$lib/components/sp/SpLayout.svelte';

	let { children } = $props();

	let isLoginPage = $derived($page.url.pathname === '/sp/login');
</script>

{#if isLoginPage}
	{@render children()}
{:else}
	<SpLayout>
		{@render children()}
	</SpLayout>
{/if}
