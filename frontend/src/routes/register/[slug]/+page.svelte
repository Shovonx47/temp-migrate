<script lang="ts">
	import { page } from '$app/stores';
	import { api } from '$lib/api.js';
	import { onMount } from 'svelte';
	import RegisterPage from '../+page.svelte';

	let slug = $derived($page.params.slug);
	let planName = $state('');
	let loading = $state(true);

	onMount(async () => {
		try {
			const res = await api.get<{ data: { name: string; slug: string } }>(`/api/v1/customer/register/plan/${slug}`);
			planName = res.data.name;
		} catch {
			planName = 'Enterprise Plan';
		} finally {
			loading = false;
		}
	});
</script>

{#if loading}
	<div class="flex min-h-screen items-center justify-center bg-gray-950 text-gray-400">
		<div class="flex flex-col items-center gap-3">
			<div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-700 border-t-primary-500"></div>
			<p class="text-xs">Loading tariff plan...</p>
		</div>
	</div>
{:else}
	<RegisterPage planSlug={slug} {planName} />
{/if}
