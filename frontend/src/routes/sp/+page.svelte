<script lang="ts">
	import { spAuth } from '$lib/stores/spAuth.svelte.js';

	let customerCount = $state(12);
	let totalDistributed = $state(125000.0);
</script>

<svelte:head>
	<title>S P Dashboard — SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Heading -->
	<div>
		<h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">S P Dashboard</h1>
		<p class="text-xs text-gray-500 dark:text-gray-400">Manage reseller wallet balance, client allocations, and messaging volumes.</p>
	</div>

	<!-- 3 Stats Overview Cards (Replicating Filament SpStatsWidget & SpBalanceWidget) -->
	<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
		<!-- Card 1: Current Balance -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between">
			<div class="space-y-1">
				<span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Current Balance</span>
				<div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">
					৳{spAuth.balance.toLocaleString('en-US', { minimumFractionDigits: 2 })}
				</div>
			</div>
			<p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
				<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
				Available to distribute to customers
			</p>
		</div>

		<!-- Card 2: Customers under this SP -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between">
			<div class="space-y-1">
				<span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Customers</span>
				<div class="text-2xl font-extrabold text-gray-900 dark:text-white font-mono">
					{customerCount}
				</div>
			</div>
			<p class="mt-3 text-[11px] text-sky-600 dark:text-sky-400 flex items-center gap-1">
				<span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
				Under this S P Partner account
			</p>
		</div>

		<!-- Card 3: Total Distributed -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between">
			<div class="space-y-1">
				<span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Distributed</span>
				<div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">
					৳{totalDistributed.toLocaleString('en-US', { minimumFractionDigits: 2 })}
				</div>
			</div>
			<p class="mt-3 text-[11px] text-amber-600 dark:text-amber-400 flex items-center gap-1">
				<span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
				All-time client wallet funding
			</p>
		</div>
	</div>

	<!-- Quick Actions Grid -->
	<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Reseller Quick Actions</h2>
		<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
			<a
				href="/sp/customers"
				class="flex items-center gap-3 p-3.5 rounded-xl border border-gray-100 hover:border-amber-300 hover:bg-amber-50/30 dark:border-gray-800 dark:hover:bg-amber-950/20 transition-all"
			>
				<div class="h-10 w-10 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
				</div>
				<div>
					<div class="text-xs font-bold text-gray-900 dark:text-white">Distribute Balance</div>
					<div class="text-[11px] text-gray-500">Fund your customer accounts instantly</div>
				</div>
			</a>

			<a
				href="/sp/messaging/messages"
				class="flex items-center gap-3 p-3.5 rounded-xl border border-gray-100 hover:border-amber-300 hover:bg-amber-50/30 dark:border-gray-800 dark:hover:bg-amber-950/20 transition-all"
			>
				<div class="h-10 w-10 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.502 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
				</div>
				<div>
					<div class="text-xs font-bold text-gray-900 dark:text-white">Customer Traffic</div>
					<div class="text-[11px] text-gray-500">Inspect real-time delivery logs</div>
				</div>
			</a>

			<a
				href="/sp/wallet"
				class="flex items-center gap-3 p-3.5 rounded-xl border border-gray-100 hover:border-amber-300 hover:bg-amber-50/30 dark:border-gray-800 dark:hover:bg-amber-950/20 transition-all"
			>
				<div class="h-10 w-10 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
				</div>
				<div>
					<div class="text-xs font-bold text-gray-900 dark:text-white">Wallet Statements</div>
					<div class="text-[11px] text-gray-500">History of all credits and transfers</div>
				</div>
			</a>
		</div>
	</div>
</div>
