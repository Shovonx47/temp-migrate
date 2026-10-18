<script lang="ts">
	import { api } from '$lib/api.js';
	import { toast } from '$lib/components/Toast.svelte';

	let email = $state('');
	let submitting = $state(false);
	let submitted = $state(false);
	let errorMsg = $state('');

	async function handleForgotPassword(e: SubmitEvent) {
		e.preventDefault();
		if (!email.trim()) {
			errorMsg = 'Please enter your email address.';
			return;
		}

		submitting = true;
		errorMsg = '';
		try {
			await api.post('/api/v1/customer/forgot-password', { email: email.trim() });
			submitted = true;
			toast.success('Reset link sent', 'If that email exists in our system, a password reset link has been sent.');
		} catch (err: unknown) {
			submitted = true;
			toast.info('Request processed', 'If that email exists in our system, a password reset link has been sent.');
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head>
	<title>Reset your password – SMS Whiteinfo</title>
	<meta name="description" content="Reset your customer account password." />
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 transition-colors dark:bg-gray-950">
	<div class="w-full max-w-lg">
		<!-- Filament Card -->
		<div class="rounded-2xl border border-gray-200/80 bg-white p-8 sm:p-10 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<!-- Header inside Card -->
			<div class="mb-8 text-center">
				<div class="mx-auto mb-4 flex justify-center">
					<img
						src="/logo.png"
						alt="SMS Whiteinfo"
						class="h-[50px] w-auto object-contain"
						onerror={(e) => {
							(e.currentTarget as HTMLElement).style.display = 'none';
							const fallback = document.getElementById('fp-logo-fallback');
							if (fallback) fallback.style.display = 'block';
						}}
					/>
					<span id="fp-logo-fallback" class="hidden text-xl font-bold tracking-tight text-gray-900 dark:text-white">
						SMS Whiteinfo
					</span>
				</div>
				<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Reset your password</h1>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					or <a href="/login" class="font-medium text-teal-600 hover:text-teal-500 hover:underline dark:text-teal-400">sign in to your account</a>
				</p>
			</div>

			{#if submitted}
				<div class="space-y-4 rounded-xl border border-teal-200 bg-teal-50 p-5 text-center dark:border-teal-900/50 dark:bg-teal-950/40">
					<div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-teal-100 text-teal-600 dark:bg-teal-900/50 dark:text-teal-400">
						<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" />
						</svg>
					</div>
					<p class="text-sm font-semibold text-teal-900 dark:text-teal-200">Reset Link Sent</p>
					<p class="text-xs text-gray-600 dark:text-gray-300">
						If an account exists for <span class="font-semibold text-gray-900 dark:text-white">{email}</span>, you will receive an email shortly with instructions to reset your password.
					</p>
					<div class="pt-2">
						<a
							href="/login"
							class="inline-block rounded-lg border border-teal-600 bg-white px-4 py-2 text-xs font-semibold text-teal-600 shadow-xs transition hover:bg-teal-50 dark:bg-gray-900 dark:text-teal-400 dark:hover:bg-gray-800"
						>
							Back to Sign in
						</a>
					</div>
				</div>
			{:else}
				{#if errorMsg}
					<div class="mb-6 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
						<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
						</svg>
						<p>{errorMsg}</p>
					</div>
				{/if}

				<form onsubmit={handleForgotPassword} class="space-y-6">
					<div>
						<label for="fp-email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
							Email address <span class="text-red-500">*</span>
						</label>
						<input
							id="fp-email"
							type="email"
							bind:value={email}
							required
							autocomplete="email"
							placeholder=""
							class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
						/>
					</div>

					<button
						type="submit"
						disabled={submitting}
						class="w-full rounded-lg border border-teal-600 bg-white py-2.5 px-4 text-sm font-semibold text-teal-600 shadow-xs transition hover:bg-teal-50 focus:outline-hidden focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-900 dark:text-teal-400 dark:hover:bg-gray-800"
					>
						{#if submitting}
							<span class="inline-flex items-center justify-center gap-2">
								<svg class="h-4 w-4 animate-spin text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
								</svg>
								Sending link…
							</span>
						{:else}
							Send password reset link
						{/if}
					</button>
				</form>
			{/if}
		</div>
	</div>
</div>
