<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { goto } from '$app/navigation';
	import { toast } from '$lib/components/Toast.svelte';

	let email = $state('');
	let password = $state('');
	let remember = $state(true);
	let showPassword = $state(false);
	let submitting = $state(false);
	let errorMsg = $state('');

	async function handleSubmit(e: SubmitEvent) {
		e.preventDefault();
		if (submitting) return;

		submitting = true;
		errorMsg = '';

		const result = await auth.login(email, password, remember);
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
	<title>Sign in – SMS Whiteinfo</title>
	<meta name="description" content="Sign in to your customer account." />
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 transition-colors dark:bg-gray-950">
	<div class="w-full max-w-lg">
		<!-- Main Filament Card -->
		<div class="rounded-2xl border border-gray-200/80 bg-white p-8 sm:p-10 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<!-- Header inside Card -->
			<div class="mb-8 text-center">
				<div class="mx-auto mb-4 flex justify-center">
					<img
						src="/logo.png"
						alt="SMS Whiteinfo"
						class="h-[50px] w-auto object-contain"
						onerror={(e) => {
							// Fallback text if logo image is missing
							(e.currentTarget as HTMLElement).style.display = 'none';
							const fallback = document.getElementById('logo-fallback');
							if (fallback) fallback.style.display = 'block';
						}}
					/>
					<span id="logo-fallback" class="hidden text-xl font-bold tracking-tight text-gray-900 dark:text-white">
						SMS Whiteinfo
					</span>
				</div>
				<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Sign in</h1>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					or <a href="/register" class="font-medium text-teal-600 hover:text-teal-500 hover:underline dark:text-teal-400">sign up for an account</a>
				</p>
			</div>

			{#if errorMsg}
				<div
					class="mb-6 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
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

			<form onsubmit={handleSubmit} class="space-y-6">
				<!-- Email -->
				<div>
					<label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200" for="login-email">
						Email address <span class="text-red-500">*</span>
					</label>
					<input
						id="login-email"
						type="email"
						bind:value={email}
						required
						autocomplete="email"
						placeholder=""
						class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
					/>
				</div>

				<!-- Password -->
				<div>
					<div class="mb-2 flex items-center justify-between">
						<label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="login-password">
							Password <span class="text-red-500">*</span>
						</label>
						<a href="/forgot-password" class="text-xs font-medium text-teal-600 transition hover:text-teal-500 hover:underline dark:text-teal-400">
							Forgot password?
						</a>
					</div>
					<div class="relative">
						<input
							id="login-password"
							type={showPassword ? 'text' : 'password'}
							bind:value={password}
							required
							autocomplete="current-password"
							placeholder=""
							class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 pr-10 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
						/>
						<button
							type="button"
							onclick={() => (showPassword = !showPassword)}
							class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
							aria-label={showPassword ? 'Hide password' : 'Show password'}
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

				<!-- Remember Me -->
				<div class="flex items-center gap-2">
					<input
						id="remember-me"
						type="checkbox"
						bind:checked={remember}
						class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500 dark:border-gray-700 dark:bg-gray-800"
					/>
					<label for="remember-me" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
						Remember me
					</label>
				</div>

				<!-- Submit Button -->
				<button
					type="submit"
					disabled={submitting}
					id="btn-login"
					class="w-full rounded-lg border border-teal-600 bg-white py-2.5 px-4 text-sm font-semibold text-teal-600 shadow-xs transition hover:bg-teal-50 focus:outline-hidden focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-900 dark:text-teal-400 dark:hover:bg-gray-800"
				>
					{#if submitting}
						<span class="inline-flex items-center justify-center gap-2">
							<svg class="h-4 w-4 animate-spin text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24">
								<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
								<path
									class="opacity-75"
									fill="currentColor"
									d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
								></path>
							</svg>
							Signing in…
						</span>
					{:else}
						Sign in
					{/if}
				</button>
			</form>
		</div>
	</div>
</div>
