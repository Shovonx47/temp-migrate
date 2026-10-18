<script lang="ts">
	import { page } from '$app/stores';

	let { collapsed = $bindable(false) }: { collapsed: boolean } = $props();

	let currentPath = $derived($page.url.pathname);

	const navItems = [
		{ label: 'Dashboard', href: '/sp', icon: 'home' },
		{ label: 'Customers', href: '/sp/customers', icon: 'users' },
		{ label: 'Messaging Logs', href: '/sp/messaging/messages', icon: 'chat-bubble' },
		{ label: 'Billing & Ledger', href: '/sp/billing', icon: 'credit-card' },
		{ label: 'Wallet & Distribution', href: '/sp/wallet', icon: 'banknotes' }
	];

	function isPathActive(href: string): boolean {
		if (href === '/sp') {
			return currentPath === '/sp';
		}
		return currentPath.startsWith(href);
	}
</script>

<aside
	class="flex flex-col border-r border-gray-200/80 bg-white transition-all duration-300 dark:border-gray-800 dark:bg-gray-900 shrink-0 select-none {collapsed ? 'w-16' : 'w-[260px] lg:w-[280px]'}"
>
	<div class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
		{#each navItems as item}
			<a
				href={item.href}
				class="group flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-medium transition-colors {isPathActive(item.href)
					? 'bg-amber-50 text-amber-900 dark:bg-amber-950/40 dark:text-amber-300 font-semibold'
					: 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'}"
			>
				<svg class="h-4 w-4 shrink-0 {isPathActive(item.href) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500'}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
					{#if item.icon === 'home'}
						<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
					{:else if item.icon === 'users'}
						<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
					{:else if item.icon === 'chat-bubble'}
						<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.502 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
					{:else if item.icon === 'banknotes'}
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
					{:else}
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
					{/if}
				</svg>
				{#if !collapsed}
					<span class="truncate">{item.label}</span>
				{/if}
			</a>
		{/each}
	</div>
</aside>
