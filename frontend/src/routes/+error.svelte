<script lang="ts">
	import { page } from '$app/stores';
	import { goto } from '$app/navigation';
	import { auth } from '$lib/stores/auth.svelte.js';

	let status = $derived($page.status);
	let is404 = $derived(status === 404);

	function handleBack() {
		if (auth.isLoggedIn) {
			goto('/customer');
		} else {
			goto('/login');
		}
	}
</script>

<svelte:head>
	<title>{is404 ? 'Page Not Found' : 'Something Went Wrong'} – SMS Whiteinfo</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
	<!-- Error Card -->
	<div class="relative z-10 w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<!-- Icon / Badge -->
		<div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl {is404 ? 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400' : 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400'} mb-6 border border-gray-100 dark:border-gray-800">
			{#if is404}
				<svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
				</svg>
			{:else}
				<svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
				</svg>
			{/if}
		</div>

		<!-- Status Code -->
		<span class="inline-block rounded-full bg-gray-100 px-3 py-1 font-mono text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
			HTTP {status}
		</span>

		<!-- Title & Message -->
		<h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
			{is404 ? 'Page Not Found' : 'Something went wrong'}
		</h1>
		<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
			{is404
				? "The page you are looking for doesn't exist, was moved, or is temporarily unavailable."
				: 'An unexpected issue occurred while processing your request. Please try again or return to safety.'}
		</p>

		<!-- Action Buttons -->
		<div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
			<button
				type="button"
				onclick={handleBack}
				class="flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
				</svg>
				{auth.isLoggedIn ? 'Back to Dashboard' : 'Go to Sign In'}
			</button>

			<button
				type="button"
				onclick={() => window.location.reload()}
				class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
			>
				Refresh Page
			</button>
		</div>

		<!-- Footer note -->
		<div class="mt-8 border-t border-gray-100 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
			Need help? Reach out to <a href="mailto:support@whiteinfobd.com" class="text-teal-600 hover:underline dark:text-teal-400">SMS Whiteinfo Support</a>
		</div>
	</div>
</div>
