<script lang="ts">
	import { adminAuth } from '$lib/stores/adminAuth.svelte.js';
	import { goto } from '$app/navigation';

	let email = $state('superman@example.com');
	let password = $state('superman');
	let remember = $state(true);
	let error = $state<string | null>(null);
	let loading = $state(false);
	let showPassword = $state(false);

	async function handleSubmit(e: SubmitEvent) {
		e.preventDefault();
		error = null;
		loading = true;

		try {
			const res = await adminAuth.login(email, password, remember);
			if (res.success) {
				goto('/superman');
			} else {
				error = res.message ?? 'Login failed. Please verify your credentials.';
			}
		} catch (err: unknown) {
			error = 'An unexpected error occurred. Please try again.';
		} finally {
			loading = false;
		}
	}
</script>

<svelte:head>
	<title>Sign in — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50/80 px-4 py-12 transition-colors dark:bg-gray-950 sm:px-6 lg:px-8">
	<div class="w-full max-w-[420px] space-y-6">
		<!-- Card container matching Filament v5 style -->
		<div class="rounded-2xl border border-gray-200/90 bg-white p-6 shadow-sm transition-colors dark:border-gray-800 dark:bg-gray-900 sm:p-8">
			<!-- Logo -->
			<div class="flex flex-col items-center justify-center text-center">
				<img src="/logo.png" alt="SMS Whiteinfo" class="h-10 w-auto object-contain" />
				<h2 class="mt-4 text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
					Sign in
				</h2>
				<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
					SMS Whiteinfo Superman Admin Panel
				</p>
			</div>

			<!-- Error message banner -->
			{#if error}
				<div class="mt-4 rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
					{error}
				</div>
			{/if}

			<!-- Form -->
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
						class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-2xs outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-emerald-500"
					/>
				</div>

				<div>
					<div class="flex items-center justify-between">
						<label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
							Password <span class="text-rose-500">*</span>
						</label>
					</div>
					<div class="relative mt-1.5">
						<input
							id="password"
							type={showPassword ? 'text' : 'password'}
							bind:value={password}
							required
							autocomplete="current-password"
							class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-xs text-gray-900 shadow-2xs outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-emerald-500"
						/>
						<button
							type="button"
							onclick={() => (showPassword = !showPassword)}
							class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
						>
							{#if showPassword}
								<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
								</svg>
							{:else}
								<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
								</svg>
							{/if}
						</button>
					</div>
				</div>

				<div class="flex items-center">
					<input
						id="remember"
						type="checkbox"
						bind:checked={remember}
						class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-800"
					/>
					<label for="remember" class="ml-2 block text-xs text-gray-700 dark:text-gray-300">
						Remember me
					</label>
				</div>

				<button
					type="submit"
					disabled={loading}
					class="w-full flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 disabled:opacity-50 cursor-pointer"
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
