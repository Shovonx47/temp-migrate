<script lang="ts">
	import { page } from '$app/stores';
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { toast } from '$lib/stores/toast.svelte.js';

	let token = $derived($page.url.searchParams.get('token') ?? '');
	let email = $derived($page.url.searchParams.get('email') ?? '');

	let password = $state('');
	let passwordConfirmation = $state('');
	let submitting = $state(false);
	let errorMsg = $state('');

	async function handleResetPassword(e: SubmitEvent) {
		e.preventDefault();
		if (!password || password !== passwordConfirmation) {
			errorMsg = 'Passwords do not match.';
			return;
		}
		if (password.length < 8) {
			errorMsg = 'Password must be at least 8 characters.';
			return;
		}

		submitting = true;
		errorMsg = '';
		try {
			await api.post('/api/v1/customer/reset-password', {
				token,
				email,
				password,
				password_confirmation: passwordConfirmation
			});
			toast.success('Password Reset', 'Your password has been updated. You can now sign in.');
			goto('/login');
		} catch (err: unknown) {
			errorMsg = err instanceof Error ? err.message : 'Password reset failed. The link may have expired.';
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head>
	<title>Set New Password – WhiteInfo Portal</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 transition-colors dark:bg-gray-950">
	<!-- Card -->
	<div class="relative z-10 w-full max-w-md rounded-2xl border border-gray-200/90 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<div class="mb-6 text-center">
			<div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-md shadow-teal-600/20">
				<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
				</svg>
			</div>
			<h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">Create New Password</h1>
			<p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
				Choose a strong, secure passphrase for your WhiteInfo customer account.
			</p>
		</div>

		{#if errorMsg}
			<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
				{errorMsg}
			</div>
		{/if}

		<form onsubmit={handleResetPassword} class="space-y-4">
			<div>
				<label for="rp-password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">New Password</label>
				<input
					id="rp-password"
					type="password"
					bind:value={password}
					placeholder="Minimum 8 characters"
					class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
				/>
			</div>

			<div>
				<label for="rp-confirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Confirm New Password</label>
				<input
					id="rp-confirm"
					type="password"
					bind:value={passwordConfirmation}
					placeholder="Re-enter password"
					class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
				/>
			</div>

			<button
				type="submit"
				disabled={submitting}
				class="w-full rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50"
			>
				{submitting ? 'Resetting Password...' : 'Save New Password'}
			</button>
		</form>
	</div>
</div>
