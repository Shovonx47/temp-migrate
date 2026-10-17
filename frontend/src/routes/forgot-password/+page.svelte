<script lang="ts">
	import { api } from '$lib/api.js';
	import { toast } from '$lib/stores/toast.svelte.js';

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
			toast.success('Reset Link Sent', 'Check your inbox for password recovery instructions.');
		} catch (err: unknown) {
			submitted = true;
			toast.info('Request Processed', 'If an account exists, a reset link was sent.');
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head>
	<title>Forgot Password – WhiteInfo Portal</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 transition-colors dark:bg-gray-950">
	<!-- Card -->
	<div class="relative z-10 w-full max-w-md rounded-2xl border border-gray-200/90 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<div class="mb-6 text-center">
			<div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-md shadow-teal-600/20">
				<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
				</svg>
			</div>
			<h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">Reset Your Password</h1>
			<p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
				Enter your registered account email and we'll send a secure password reset link.
			</p>
		</div>

		{#if submitted}
			<div class="space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center dark:border-emerald-900/50 dark:bg-emerald-950/40">
				<div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400">
					✓
				</div>
				<p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Instructions Sent</p>
				<p class="text-xs text-gray-600 dark:text-gray-300">If an account is associated with <span class="font-bold text-gray-900 dark:text-white">{email}</span>, you will receive an email shortly with instructions to reset your password.</p>
				<div class="pt-2">
					<a href="/login" class="inline-block rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
						Back to Sign In
					</a>
				</div>
			</div>
		{:else}
			{#if errorMsg}
				<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
					{errorMsg}
				</div>
			{/if}

			<form onsubmit={handleForgotPassword} class="space-y-4">
				<div>
					<label for="fp-email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Email Address</label>
					<input
						id="fp-email"
						type="email"
						bind:value={email}
						placeholder="you@company.com"
						class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					/>
				</div>

				<button
					type="submit"
					disabled={submitting}
					class="w-full rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50"
				>
					{submitting ? 'Sending Link...' : 'Send Reset Link'}
				</button>
			</form>

			<div class="mt-6 text-center text-xs text-gray-600 dark:text-gray-400">
				Remember your password?
				<a href="/login" class="font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400">Sign in</a>
			</div>
		{/if}
	</div>
</div>
