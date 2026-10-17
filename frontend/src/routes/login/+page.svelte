<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { goto } from '$app/navigation';
	import { toast } from '$lib/components/Toast.svelte';

	let email = $state('');
	let password = $state('');
	let submitting = $state(false);
	let errorMsg = $state('');

	async function handleSubmit(e: SubmitEvent) {
		e.preventDefault();
		if (submitting) return;

		submitting = true;
		errorMsg = '';

		const result = await auth.login(email, password);
		submitting = false;

		if (result.success) {
			toast.success('Welcome back!', `Signed in as ${auth.user?.name}`);
			goto('/customer');
		} else {
			errorMsg = result.message ?? 'Login failed';
		}
	}
</script>

<svelte:head>
	<title>Sign In – SMS Portal</title>
	<meta name="description" content="Sign in to your SMS Portal customer account." />
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 transition-colors dark:bg-gray-950">
	<div class="relative w-full max-w-md">
		<!-- Brand Logo / Header -->
		<div class="mb-8 text-center">
			<div
				class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-md shadow-teal-600/20"
			>
				<svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
					<path
						d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"
					/>
					<path
						d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"
					/>
				</svg>
			</div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Sign in</h1>
			<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
				Please enter your account credentials to continue
			</p>
		</div>

		<!-- White Card -->
		<div class="rounded-2xl border border-gray-200/90 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			{#if errorMsg}
				<div
					class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
				>
					<svg
						class="mt-0.5 h-4 w-4 shrink-0 text-red-500"
						fill="none"
						viewBox="0 0 24 24"
						stroke-width="1.5"
						stroke="currentColor"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
						/>
					</svg>
					<p>{errorMsg}</p>
				</div>
			{/if}

			<form onsubmit={handleSubmit} class="space-y-5">
				<!-- Email -->
				<div>
					<label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300" for="login-email">
						Email address <span class="text-red-500">*</span>
					</label>
					<input
						id="login-email"
						type="email"
						bind:value={email}
						required
						autocomplete="email"
						placeholder="you@example.com"
						class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					/>
				</div>

				<!-- Password -->
				<div>
					<div class="mb-1.5 flex items-center justify-between">
						<label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300" for="login-password">
							Password <span class="text-red-500">*</span>
						</label>
						<a href="/forgot-password" class="text-xs font-semibold text-teal-600 transition hover:text-teal-700 dark:text-teal-400">
							Forgot password?
						</a>
					</div>
					<input
						id="login-password"
						type="password"
						bind:value={password}
						required
						autocomplete="current-password"
						placeholder="••••••••"
						class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					/>
				</div>

				<!-- Remember Me -->
				<div class="flex items-center gap-2">
					<input
						id="remember-me"
						type="checkbox"
						checked
						class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-700"
					/>
					<label for="remember-me" class="text-xs font-medium text-gray-600 dark:text-gray-400">
						Remember me on this browser
					</label>
				</div>

				<!-- Submit Button -->
				<button
					type="submit"
					disabled={submitting}
					id="btn-login"
					class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
				>
					{#if submitting}
						<svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path
								class="opacity-75"
								fill="currentColor"
								d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
							></path>
						</svg>
						Signing in…
					{:else}
						Sign In
					{/if}
				</button>
			</form>
		</div>

		<!-- Register link -->
		<p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
			Don't have an account?
			<a href="/register" class="font-semibold text-teal-600 transition hover:text-teal-700 hover:underline dark:text-teal-400">
				Sign up here
			</a>
		</p>
	</div>
</div>
