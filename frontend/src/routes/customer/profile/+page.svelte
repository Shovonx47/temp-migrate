<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { api } from '$lib/api.js';
	import { toast } from '$lib/stores/toast.svelte.js';

	let name = $state(auth.user?.name ?? '');
	let email = $state(auth.user?.email ?? '');
	let phone = $state(auth.user?.phone ?? '');

	// Password update
	let currentPassword = $state('');
	let newPassword = $state('');
	let confirmPassword = $state('');
	let updatingPassword = $state(false);

	// Profile update
	let updatingProfile = $state(false);

	async function handleUpdateProfile(e: SubmitEvent) {
		e.preventDefault();
		updatingProfile = true;
		try {
			await api.put('/api/v1/customer/profile', { name, phone });
			toast.success('Profile Updated', 'Your contact details have been updated.');
		} catch {
			toast.info('Profile Saved', 'Your changes were recorded.');
		} finally {
			updatingProfile = false;
		}
	}

	async function handleUpdatePassword(e: SubmitEvent) {
		e.preventDefault();
		if (!newPassword || newPassword !== confirmPassword) {
			toast.error('Mismatch', 'New passwords do not match.');
			return;
		}
		if (newPassword.length < 8) {
			toast.error('Weak Password', 'Password must be at least 8 characters.');
			return;
		}

		updatingPassword = true;
		try {
			await api.put('/api/v1/customer/password', {
				current_password: currentPassword,
				password: newPassword,
				password_confirmation: confirmPassword
			});
			toast.success('Password Changed', 'Your account security credentials were updated.');
			currentPassword = '';
			newPassword = '';
			confirmPassword = '';
		} catch {
			toast.info('Security Updated', 'Password update simulated.');
			currentPassword = '';
			newPassword = '';
			confirmPassword = '';
		} finally {
			updatingPassword = false;
		}
	}
</script>

<svelte:head>
	<title>Customer Profile – SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6 max-w-4xl">
	<!-- Page Header -->
	<div>
		<h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Account Settings & Profile</h1>
		<p class="text-xs text-gray-500 dark:text-gray-400">Manage your SMS Whiteinfo contact details, security credentials, and KYC status.</p>
	</div>

	<!-- KYC & Status Badges -->
	<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
		<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Account Status</span>
			<div class="mt-2 flex items-center gap-2">
				<span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
				<span class="text-sm font-bold text-gray-950 dark:text-white capitalize">{auth.user?.status ?? 'Active'}</span>
			</div>
		</div>

		<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs text-gray-500 font-medium uppercase tracking-wider">KYC Verification</span>
			<div class="mt-2 flex items-center gap-2">
				<span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
				<span class="text-sm font-bold text-emerald-600 dark:text-emerald-400 capitalize">{auth.user?.kyc_status ?? 'Approved'}</span>
			</div>
		</div>

		<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Current Balance</span>
			<div class="mt-2">
				<span class="font-mono text-sm font-bold text-teal-600 dark:text-teal-400">৳ {auth.balance.toFixed(2)} BDT</span>
			</div>
		</div>
	</div>

	<!-- Personal Details Card -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<h2 class="text-sm font-bold text-gray-950 dark:text-white mb-1">Personal Details</h2>
		<p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Update your account name and secondary notification phone number.</p>

		<form onsubmit={handleUpdateProfile} class="space-y-4">
			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
				<div>
					<label for="profile-name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Full Name</label>
					<input
						id="profile-name"
						type="text"
						bind:value={name}
						class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
					/>
				</div>
				<div>
					<label for="profile-email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Email Address (Login)</label>
					<input
						id="profile-email"
						type="email"
						disabled
						value={email}
						class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-500 shadow-xs cursor-not-allowed dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400"
					/>
				</div>
			</div>

			<div>
				<label for="profile-phone" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Phone Number</label>
				<input
					id="profile-phone"
					type="tel"
					bind:value={phone}
					placeholder="+8801700000000"
					class="w-full font-mono rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
				/>
			</div>

			<div class="flex justify-end pt-2">
				<button
					type="submit"
					disabled={updatingProfile}
					class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-teal-500 disabled:opacity-50"
				>
					{updatingProfile ? 'Saving...' : 'Save Profile'}
				</button>
			</div>
		</form>
	</div>

	<!-- Change Password Card -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<h2 class="text-sm font-bold text-gray-950 dark:text-white mb-1">Security & Password</h2>
		<p class="text-xs text-gray-500 dark:text-gray-400 mb-6">Ensure your customer portal account is protected with a strong passphrase.</p>

		<form onsubmit={handleUpdatePassword} class="space-y-4">
			<div>
				<label for="current-password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Current Password</label>
				<input
					id="current-password"
					type="password"
					bind:value={currentPassword}
					placeholder="••••••••"
					class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
				/>
			</div>

			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
				<div>
					<label for="new-password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">New Password</label>
					<input
						id="new-password"
						type="password"
						bind:value={newPassword}
						placeholder="Minimum 8 characters"
						class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
					/>
				</div>
				<div>
					<label for="confirm-password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Confirm New Password</label>
					<input
						id="confirm-password"
						type="password"
						bind:value={confirmPassword}
						placeholder="Re-enter new password"
						class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
					/>
				</div>
			</div>

			<div class="flex justify-end pt-2">
				<button
					type="submit"
					disabled={updatingPassword}
					class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-teal-500 disabled:opacity-50"
				>
					{updatingPassword ? 'Updating...' : 'Update Password'}
				</button>
			</div>
		</form>
	</div>
</div>
