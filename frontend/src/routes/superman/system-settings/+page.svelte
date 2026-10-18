<script lang="ts">
	let appName = $state('SMS Whiteinfo');
	let defaultCurrency = $state('BDT');
	let kycEnforced = $state(true);
	let autoApproveSms = $state(false);
	let maintenanceMode = $state(false);
	let saved = $state(false);

	function handleSave(e: SubmitEvent) {
		e.preventDefault();
		saved = true;
		setTimeout(() => (saved = false), 3000);
	}
</script>

<svelte:head>
	<title>System Settings — SMS Whiteinfo Administrator</title>
</svelte:head>

<div class="space-y-4">
	<div class="flex items-center justify-between">
		<div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">System Settings</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Manage application identity, verification policies, and dispatch limits.</p>
		</div>
	</div>

	{#if saved}
		<div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 text-xs text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
			Settings saved successfully.
		</div>
	{/if}

	<form onsubmit={handleSave} class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-5 max-w-2xl">
		<div>
			<label for="appName" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Website & Platform Name</label>
			<input
				id="appName"
				type="text"
				bind:value={appName}
				class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-2xs outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
			/>
		</div>

		<div>
			<label for="currency" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Default Currency</label>
			<input
				id="currency"
				type="text"
				bind:value={defaultCurrency}
				class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-2xs outline-none focus:border-emerald-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
			/>
		</div>

		<div class="space-y-3 pt-2">
			<label class="flex items-center gap-2 cursor-pointer">
				<input type="checkbox" bind:checked={kycEnforced} class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
				<span class="text-xs text-gray-700 dark:text-gray-300 font-medium">Enforce KYC verification before allowing live SMS dispatch</span>
			</label>

			<label class="flex items-center gap-2 cursor-pointer">
				<input type="checkbox" bind:checked={autoApproveSms} class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
				<span class="text-xs text-gray-700 dark:text-gray-300 font-medium">Auto-approve bulk campaigns under 5,000 recipients</span>
			</label>

			<label class="flex items-center gap-2 cursor-pointer">
				<input type="checkbox" bind:checked={maintenanceMode} class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
				<span class="text-xs text-gray-700 dark:text-gray-300 font-medium">Enable Maintenance Mode (Clients cannot log in)</span>
			</label>
		</div>

		<div class="pt-3 border-t border-gray-100 dark:border-gray-800">
			<button
				type="submit"
				class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition"
			>
				Save Changes
			</button>
		</div>
	</form>
</div>
