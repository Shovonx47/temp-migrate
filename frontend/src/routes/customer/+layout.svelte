<script lang="ts">
	import PortalLayout from '$lib/components/PortalLayout.svelte';

	let { children } = $props();
</script>

<PortalLayout>
	{@render children()}
</PortalLayout>
