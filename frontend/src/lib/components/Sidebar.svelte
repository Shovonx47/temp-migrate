<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { goto } from '$app/navigation';
	import { page } from '$app/stores';

	interface NavItem {
		label: string;
		href: string;
		icon: string;
		badge?: number;
	}

	interface NavGroup {
		group: string;
		items: NavItem[];
	}

	const navGroups: NavGroup[] = [
		{
			group: '',
			items: [{ label: 'Dashboard', href: '/', icon: 'home' }]
		},
		{
			group: 'Messaging',
			items: [
				{ label: 'Live Queue', href: '/messaging/live-queue', icon: 'signal' },
				{ label: 'Quick Message', href: '/messaging/messages/quick', icon: 'paper-airplane' },
				{ label: 'Bulk Message', href: '/messaging/messages/bulk', icon: 'sparkles' },
				{ label: 'SMS Logs', href: '/messaging/messages', icon: 'list-bullet' },
				{ label: 'Batches', href: '/messaging/batches', icon: 'queue-list' },
				{ label: 'Templates', href: '/messaging/templates', icon: 'document-text' },
				{ label: 'Sender IDs', href: '/messaging/senders', icon: 'identification' }
			]
		},
		{
			group: 'Reports & Analytics',
			items: [
				{ label: 'Analytics', href: '/analytics/messaging', icon: 'chart-bar' }
			]
		},
		{
			group: 'Audience',
			items: [
				{ label: 'Contacts', href: '/audience/contacts', icon: 'users' },
				{ label: 'Groups', href: '/audience/groups', icon: 'user-group' }
			]
		},
		{
			group: 'Billing',
			items: [
				{ label: 'Deposits', href: '/billing/deposits', icon: 'banknotes' },
				{ label: 'Transactions', href: '/billing/transactions', icon: 'arrows-right-left' },
				{ label: 'Pricing', href: '/pricing', icon: 'tag' }
			]
		},
		{
			group: 'Developer',
			items: [
				{ label: 'API Access', href: '/developer/access-tokens', icon: 'code-bracket' },
				{ label: 'IP Whitelist', href: '/developer/ip-whitelists', icon: 'shield-check' }
			]
		},
		{
			group: 'Account',
			items: [
				{ label: 'KYC Verification', href: '/account/kyc', icon: 'shield-check' },
				{ label: 'My Profile', href: '/profile', icon: 'user' }
			]
		}
	];

	let { collapsed = $bindable(false) }: { collapsed: boolean } = $props();

	let currentPath = $derived($page.url.pathname);

	const routeAliases: Record<string, string[]> = {
		'/': ['/customer'],
		'/messaging/live-queue': ['/customer/live-queue'],
		'/messaging/messages/quick': ['/customer/messages/quick'],
		'/messaging/messages/bulk': ['/customer/messages/bulk', '/messaging/messages/smart'],
		'/messaging/messages': ['/customer/messages'],
		'/messaging/batches': ['/customer/batches'],
		'/messaging/templates': ['/customer/templates'],
		'/messaging/senders': ['/customer/senders'],
		'/analytics/messaging': ['/analytics', '/customer/analytics', '/analytics/billing'],
		'/audience/contacts': ['/customer/contacts'],
		'/audience/groups': ['/customer/groups'],
		'/billing/deposits': ['/customer/deposits'],
		'/billing/transactions': ['/customer/transactions'],
		'/pricing': ['/customer/pricing'],
		'/developer/access-tokens': ['/customer/developer', '/developer'],
		'/developer/ip-whitelists': ['/customer/ip-whitelist'],
		'/account/kyc': ['/customer/kyc', '/account/pending', '/account/banned', '/account/rejected'],
		'/profile': ['/customer/profile']
	};

	function isActive(href: string): boolean {
		if (href === '/' || href === '/customer') {
			return currentPath === '/' || currentPath === '/customer';
		}
		if (currentPath === href) return true;
		if (routeAliases[href]?.some((alias) => currentPath === alias || currentPath.startsWith(alias + '/'))) {
			return true;
		}
		if (href.length > 1 && currentPath.startsWith(href + '/')) return true;
		return false;
	}

	async function handleLogout() {
		await auth.logout();
		goto('/login');
	}

	const icons: Record<string, string> = {
		home: `<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />`,
		signal: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M12 12h.008v.008H12V12Z" />`,
		'chart-bar': `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />`,
		'paper-airplane': `<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />`,
		sparkles: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />`,
		'list-bullet': `<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />`,
		'queue-list': `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />`,
		'document-text': `<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />`,
		identification: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />`,
		users: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`,
		'user-group': `<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />`,
		banknotes: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />`,
		'arrows-right-left': `<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />`,
		tag: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />`,
		'code-bracket': `<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />`,
		'shield-check': `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />`,
		user: `<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />`,
		'chevron-left': `<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />`,
		'chevron-right': `<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />`,
		logout: `<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />`
	};
</script>

<!-- Sidebar -->
<aside
	class="flex h-screen flex-col border-r border-gray-200 bg-white transition-all duration-300 dark:border-gray-800 dark:bg-gray-900 {collapsed
		? 'w-16'
		: 'w-64'}"
>
	<!-- Logo -->
	<div
		class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-4 dark:border-gray-800 {collapsed
			? 'justify-center px-2'
			: ''}"
	>
		{#if !collapsed}
			<a href="/customer" class="flex items-center overflow-hidden py-1">
				<img
					src="/logo.png"
					alt="SMS Whiteinfo"
					class="h-9 max-h-9 w-auto max-w-[180px] object-contain"
					onerror={(e) => {
						(e.currentTarget as HTMLElement).style.display = 'none';
						const fallback = document.getElementById('sidebar-logo-fallback');
						if (fallback) fallback.style.display = 'flex';
					}}
				/>
				<div
					id="sidebar-logo-fallback"
					class="hidden h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-white shadow-sm"
				>
					<img src="/icon.png" alt="Whiteinfo" class="h-7 w-7 object-contain" />
				</div>
			</a>
		{:else}
			<a href="/customer" class="flex items-center justify-center py-1">
				<img
					src="/icon.png"
					alt="SMS Whiteinfo"
					class="h-8 w-8 object-contain rounded"
					onerror={(e) => {
						(e.currentTarget as HTMLElement).style.display = 'none';
						const fallback = document.getElementById('sidebar-collapsed-logo');
						if (fallback) fallback.style.display = 'flex';
					}}
				/>
				<div
					id="sidebar-collapsed-logo"
					class="hidden h-7 w-7 items-center justify-center rounded-lg bg-teal-600 text-white shadow-sm"
				>
					<span class="text-xs font-bold">W</span>
				</div>
			</a>
		{/if}
		<button
			onclick={() => (collapsed = !collapsed)}
			class="ml-auto rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-800 hover:text-gray-200 {collapsed
				? 'mx-auto mt-1'
				: ''}"
			aria-label="Toggle sidebar"
		>
			<svg
				class="h-4 w-4"
				fill="none"
				viewBox="0 0 24 24"
				stroke-width="1.5"
				stroke="currentColor"
			>
				{#if collapsed}
					{@html icons['chevron-right']}
				{:else}
					{@html icons['chevron-left']}
				{/if}
			</svg>
		</button>
	</div>

	<!-- Navigation -->
	<nav class="flex-1 overflow-y-auto px-2 py-3">
		{#each navGroups as { group, items } (group)}
			{#if group && !collapsed}
				<p class="mb-1 mt-3 px-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500">
					{group}
				</p>
			{/if}
			<ul class="space-y-0.5">
				{#each items as item (item.href)}
					{@const active = isActive(item.href)}
					<li>
						<a
							href={item.href}
							class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-all duration-150 {active
								? 'bg-teal-50 text-teal-700 font-semibold dark:bg-primary-600/15 dark:text-primary-400'
								: 'text-gray-700 hover:bg-gray-100 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'} {collapsed
								? 'justify-center px-0'
								: ''}"
							title={collapsed ? item.label : ''}
						>
							<svg
								class="h-4.5 w-4.5 shrink-0 transition-colors {active
									? 'text-teal-600 dark:text-primary-400'
									: 'text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300'}"
								fill="none"
								viewBox="0 0 24 24"
								stroke-width="1.5"
								stroke="currentColor"
							>
								{@html icons[item.icon] ?? ''}
							</svg>
							{#if !collapsed}
								<span class="truncate">{item.label}</span>
								{#if item.badge}
									<span
										class="ml-auto rounded-full bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800 dark:bg-primary-500/20 dark:text-primary-400"
									>
										{item.badge}
									</span>
								{/if}
							{/if}
						</a>
					</li>
				{/each}
			</ul>
		{/each}
	</nav>

	<!-- User footer -->
	<div class="border-t border-gray-200 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-900">
		{#if auth.user}
			<div class="flex items-center gap-2.5 {collapsed ? 'justify-center' : ''}">
				<div
					class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-600 text-xs font-semibold text-white shadow-sm"
				>
					{auth.user.name[0]?.toUpperCase()}
				</div>
				{#if !collapsed}
					<div class="min-w-0 flex-1">
						<p class="truncate text-xs font-semibold text-gray-900 dark:text-gray-200">{auth.user.name}</p>
						<p class="truncate text-[11px] text-gray-500">{auth.user.email}</p>
					</div>
					<button
						onclick={handleLogout}
						class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-200 hover:text-red-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-red-400"
						title="Sign out"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							{@html icons.logout}
						</svg>
					</button>
				{/if}
			</div>
		{/if}
	</div>
</aside>
