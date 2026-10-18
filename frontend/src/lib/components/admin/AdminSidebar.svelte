<script lang="ts">
	import { page } from '$app/stores';

	let { collapsed = $bindable(false) }: { collapsed: boolean } = $props();

	let openGroups = $state<Record<string, boolean>>({
		'user': true,
		'kyc': false,
		'billing': false,
		'messaging': false,
		'approval_center': false
	});

	function toggleGroup(key: string) {
		openGroups[key] = !openGroups[key];
	}

	let currentPath = $derived($page.url.pathname);

	const navSections = [
		{
			id: 'live_dashboard',
			label: 'Live Dashboard',
			href: '/superman',
			icon: 'bolt'
		},
		{
			id: 'user',
			label: 'User Management',
			icon: 'user',
			items: [
				{ label: 'Customers', href: '/superman/users/customers' },
				{ label: 'S P Resellers', href: '/superman/users/sps' }
			]
		},
		{
			id: 'kyc',
			label: 'KYC / Verification',
			icon: 'identification',
			items: [
				{ label: 'KYC Requests', href: '/superman/kyc/requests', badge: 1 }
			]
		},
		{
			id: 'billing',
			label: 'Billing',
			icon: 'credit-card',
			items: [
				{ label: 'Deposits', href: '/superman/finance/deposits' },
				{ label: 'Transactions', href: '/superman/finance/transactions' }
			]
		},
		{
			id: 'sender_cli',
			label: 'Sender / CLI Management',
			icon: 'phone',
			items: [
				{ label: 'Sender IDs', href: '/superman/senders' }
			]
		},
		{
			id: 'providers',
			label: 'Provider Settings',
			icon: 'server',
			items: [
				{ label: 'Gateways', href: '/superman/settings/gateways' }
			]
		},
		{
			id: 'reports',
			label: 'Reports',
			icon: 'document-text',
			items: [
				{ label: 'SMS Reports', href: '/superman/reports/sms' }
			]
		},
		{
			id: 'analytics',
			label: 'Analytics',
			icon: 'chart-bar',
			items: [
				{ label: 'Overview', href: '/superman/analytics' }
			]
		},
		{
			id: 'messaging',
			label: 'Messaging',
			icon: 'chat-bubble',
			items: [
				{ label: 'SMS Logs', href: '/superman/system-logs/sms-logs' }
			]
		},
		{
			id: 'audience',
			label: 'Audience',
			icon: 'users',
			items: [
				{ label: 'Contacts', href: '/superman/audience/contacts' }
			]
		},
		{
			id: 'settings',
			label: 'Settings',
			icon: 'cog',
			items: [
				{ label: 'General Settings', href: '/superman/system-settings' }
			]
		},
		{
			id: 'approval_center',
			label: 'Approval Center',
			icon: 'clipboard-check',
			items: [
				{ label: 'IP Whitelists', href: '/superman/approval-center/ip-whitelists' }
			]
		},
		{
			id: 'system_logs',
			label: 'System Logs',
			icon: 'document-magnifying-glass',
			items: [
				{ label: 'SMS Logs', href: '/superman/system-logs/sms-logs' }
			]
		},
		{
			id: 'system_settings',
			label: 'System Settings',
			icon: 'cog-8-tooth',
			items: [
				{ label: 'Configurations', href: '/superman/system-settings' }
			]
		}
	];

	function isPathActive(href: string): boolean {
		if (href === '/superman') {
			return currentPath === '/superman';
		}
		return currentPath.startsWith(href);
	}
</script>

<aside
	class="flex flex-col border-r border-gray-200/80 bg-white transition-all duration-300 dark:border-gray-800 dark:bg-gray-900 shrink-0 select-none {collapsed ? 'w-16' : 'w-[280px] lg:w-[300px]'}"
>
	<!-- Navigation list -->
	<div class="flex-1 overflow-y-auto px-3 py-3 space-y-1">
		{#each navSections as section}
			{#if section.href}
				<!-- Direct link (like Live Dashboard) -->
				<a
					href={section.href}
					class="group flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-medium transition-colors {isPathActive(section.href)
						? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 font-semibold'
						: 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'}"
				>
					<svg class="h-4 w-4 shrink-0 {isPathActive(section.href) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500'}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
					</svg>
					{#if !collapsed}
						<span class="truncate">{section.label}</span>
					{/if}
				</a>
			{:else}
				<!-- Collapsible Group -->
				<div>
					<button
						type="button"
						onclick={() => toggleGroup(section.id)}
						class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/60 dark:hover:text-gray-200 transition-colors"
					>
						<div class="flex items-center gap-3 truncate">
							<!-- Group Icon -->
							<svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
							</svg>
							{#if !collapsed}
								<span class="truncate">{section.label}</span>
							{/if}
						</div>
						{#if !collapsed}
							<svg
								class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200 {openGroups[section.id] ? 'rotate-180' : ''}"
								fill="none"
								viewBox="0 0 24 24"
								stroke-width="2"
								stroke="currentColor"
							>
								<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
							</svg>
						{/if}
					</button>

					<!-- Sub-items -->
					{#if !collapsed && openGroups[section.id] && section.items}
						<div class="ml-4 pl-3 border-l border-gray-100 dark:border-gray-800 mt-1 space-y-0.5">
							{#each section.items as item}
								<a
									href={item.href}
									class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs transition-colors {isPathActive(item.href)
										? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-950/40 dark:text-emerald-400'
										: 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-white'}"
								>
									<span class="truncate">{item.label}</span>
									{#if item.badge}
										<span class="rounded-full bg-emerald-100 px-1.5 py-0.2 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
											{item.badge}
										</span>
									{/if}
								</a>
							{/each}
						</div>
					{/if}
				</div>
			{/if}
		{/each}
	</div>
</aside>
