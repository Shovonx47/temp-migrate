<script lang="ts">
	import { spAuth } from '$lib/stores/spAuth.svelte.js';
	import { goto } from '$app/navigation';

	let email = $state('sp@example.com');
	let password = $state('password');
	let error = $state<string | null>(null);
	let loading = $state(false);

	async function handleSubmit(e: SubmitEvent) {
		e.preventDefault();
		error = null;
		loading = true;

		try {
			const res = await spAuth.login(email, password);
			if (res.success) {
				goto('/sp');
			} else {
				error = res.message ?? 'Login failed. Please check your credentials.';
			}
		} finally {
			loading = false;
		}
	}
</script>

<svelte:head>
	<title>Sign in — SMS Whiteinfo S P Reseller</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50/80 px-4 py-12 transition-colors dark:bg-gray-950 sm:px-6 lg:px-8">
	<div class="w-full max-w-[420px] space-y-6">
		<div class="rounded-2xl border border-gray-200/90 bg-white p-6 shadow-sm transition-colors dark:border-gray-800 dark:bg-gray-900 sm:p-8">
			<div class="flex flex-col items-center justify-center text-center">
				<img src="/logo.png" alt="SMS Whiteinfo" class="h-10 w-auto object-contain" />
				<h2 class="mt-4 text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
					Sign in
				</h2>
				<p class="mt-1 text-xs text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider">
					S P Partner Reseller Portal
				</p>
			</div>

			{#if error}
				<div class="mt-4 rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
					{error}
				</div>
			{/if}

			<form onsubmit={handleSubmit} class="mt-6 space-y-4">
				<div>
					<label for="email" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
						Email address <span class="text-rose-500">*</span>
					</label>
					<input
						id="email"
						type="email"
						bind:value={email}
						required
						autocomplete="email"
						class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-2xs outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-amber-500"
					/>
				</div>

				<div>
					<label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
						Password <span class="text-rose-500">*</span>
					</label>
					<input
						id="password"
						type="password"
						bind:value={password}
						required
						autocomplete="current-password"
						class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-2xs outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-amber-500"
					/>
				</div>

				<button
					type="submit"
					disabled={loading}
					class="w-full flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/50 disabled:opacity-50 cursor-pointer"
				>
					{#if loading}
						<div class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2"></div>
						Signing in…
					{:else}
						Sign in
					{/if}
				</button>
			</form>
		</div>
	</div>
</div>
