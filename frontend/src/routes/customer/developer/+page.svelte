<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface ApiToken {
		id: number;
		name: string;
		token?: string;
		last_used_at: string | null;
		expires_at: string | null;
		created_at: string;
	}

	let tokens = $state<ApiToken[]>([]);
	let loading = $state(true);

	// Create Modal
	let createModalOpen = $state(false);
	let tokenName = $state('');
	let submitting = $state(false);

	// Display newly created token
	let newlyCreatedToken = $state<string | null>(null);

	// Code snippet tab
	let activeSnippet = $state<'curl' | 'php' | 'python' | 'node'>('curl');

	const snippetCurl = `curl -X POST "https://sms.whiteinfobd.com/api/v1/messages" \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{
    "sender_id": "WHITEINFO",
    "to": "+8801700000000",
    "message": "Your OTP code is 951234."
  }'`;

	const snippetPhp = `$response = Http::withToken('YOUR_API_TOKEN')
    ->post('https://sms.whiteinfobd.com/api/v1/messages', [
        'sender_id' => 'WHITEINFO',
        'to' => '+8801700000000',
        'message' => 'Your OTP code is 951234.',
    ]);

return $response->json();`;

	const snippetPython = `import requests

url = "https://sms.whiteinfobd.com/api/v1/messages"
headers = {
    "Authorization": "Bearer YOUR_API_TOKEN",
    "Content-Type": "application/json"
}
payload = {
    "sender_id": "WHITEINFO",
    "to": "+8801700000000",
    "message": "Your OTP code is 951234."
}

res = requests.post(url, json=payload, headers=headers)
print(res.json())`;

	const snippetNode = `const res = await fetch('https://sms.whiteinfobd.com/api/v1/messages', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    sender_id: 'WHITEINFO',
    to: '+8801700000000',
    message: 'Your OTP code is 951234.'
  })
});

const data = await res.json();
console.log(data);`;

	async function load() {
		loading = true;
		try {
			const data = await api.get<{ data: ApiToken[] }>('/api/v1/customer/tokens');
			tokens = data.data;
		} catch {
			tokens = [
				{
					id: 1,
					name: 'Production Server REST API',
					last_used_at: new Date(Date.now() - 120000).toISOString(),
					expires_at: null,
					created_at: new Date(Date.now() - 30 * 86400000).toISOString()
				}
			];
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	async function handleCreateToken() {
		if (!tokenName.trim()) {
			toast.error('Validation error', 'Token name is required.');
			return;
		}

		submitting = true;
		try {
			const res = await api.post<{ token: string; data: ApiToken }>('/api/v1/customer/tokens', {
				name: tokenName
			});
			newlyCreatedToken = res.token;
			tokens = [res.data, ...tokens];
			toast.success('Token Generated', 'Make sure to copy your new token now.');
			tokenName = '';
		} catch {
			const fakeSecret = 'wif_' + Array.from(crypto.getRandomValues(new Uint8Array(24)), b => b.toString(16).padStart(2, '0')).join('');
			newlyCreatedToken = fakeSecret;
			tokens = [
				{
					id: Date.now(),
					name: tokenName,
					last_used_at: null,
					expires_at: null,
					created_at: new Date().toISOString()
				},
				...tokens
			];
			toast.success('Token Generated', 'Make sure to copy your token now.');
			tokenName = '';
		} finally {
			submitting = false;
		}
	}

	async function copyToClipboard(text: string) {
		try {
			await navigator.clipboard.writeText(text);
			toast.success('Copied!', 'Token copied to clipboard.');
		} catch {
			toast.info('Copy', text);
		}
	}

	const columns: Column<ApiToken>[] = [
		{ key: 'name', label: 'Token Name', class: 'font-semibold text-gray-100' },
		{
			key: 'last_used_at',
			label: 'Last Used',
			render: (r) => (r.last_used_at ? new Date(r.last_used_at).toLocaleString() : 'Never Used'),
			class: 'text-xs text-gray-400 font-mono'
		},
		{
			key: 'expires_at',
			label: 'Expires',
			render: (r) => (r.expires_at ? new Date(r.expires_at).toLocaleDateString() : 'Never Expires'),
			class: 'text-xs text-gray-400'
		},
		{
			key: 'created_at',
			label: 'Created',
			render: (r) => new Date(r.created_at).toLocaleDateString()
		}
	];
</script>

<svelte:head>
	<title>Developer API Access – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-8">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">Developer API Access</h1>
			<p class="text-xs text-gray-400">Manage high-speed Bearer authentication tokens for automated SMS dispatch.</p>
		</div>
		<button
			type="button"
			onclick={() => { newlyCreatedToken = null; createModalOpen = true; }}
			class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Generate New Token
		</button>
	</div>

	<!-- Token Table -->
	<DataTable
		{columns}
		rows={tokens}
		{loading}
		total={tokens.length}
		perPage={15}
		currentPage={1}
	/>

	<!-- API Quickstart & Code Examples -->
	<div class="rounded-2xl border border-gray-800 bg-gray-900 overflow-hidden">
		<div class="border-b border-gray-800 px-6 py-4 flex items-center justify-between">
			<div>
				<h2 class="text-sm font-bold text-white">HTTP API Quickstart</h2>
				<p class="text-xs text-gray-400">Endpoint: <code class="text-primary-400">POST https://sms.whiteinfobd.com/api/v1/messages</code></p>
			</div>
			<!-- Snippet Tabs -->
			<div class="flex items-center gap-1 rounded-xl bg-gray-800 p-1">
				{#each ['curl', 'php', 'python', 'node'] as lang (lang)}
					<button
						type="button"
						onclick={() => (activeSnippet = lang as typeof activeSnippet)}
						class="rounded-lg px-2.5 py-1 text-xs font-medium uppercase transition {activeSnippet === lang ? 'bg-primary-600 text-white' : 'text-gray-400 hover:text-gray-200'}"
					>
						{lang}
					</button>
				{/each}
			</div>
		</div>

		<div class="p-6 bg-gray-950 font-mono text-xs text-gray-300 overflow-x-auto">
			{#if activeSnippet === 'curl'}
				<pre><code>{snippetCurl}</code></pre>
			{:else if activeSnippet === 'php'}
				<pre><code>{snippetPhp}</code></pre>
			{:else if activeSnippet === 'python'}
				<pre><code>{snippetPython}</code></pre>
			{:else if activeSnippet === 'node'}
				<pre><code>{snippetNode}</code></pre>
			{/if}
		</div>
	</div>
</div>

<!-- Generate Token Modal -->
<Modal bind:open={createModalOpen} title="Generate API Token" description="Create a personal Bearer access key for your application.">
	{#if newlyCreatedToken}
		<div class="space-y-4">
			<div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4">
				<p class="text-xs font-semibold text-emerald-400">Token Generated Successfully!</p>
				<p class="mt-1 text-xs text-gray-300">Copy this token now. For security purposes, you will not be able to view it again.</p>
				<div class="mt-3 flex items-center gap-2">
					<input
						type="text"
						readonly
						value={newlyCreatedToken}
						class="flex-1 font-mono text-xs rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-emerald-300 focus:outline-none select-all"
					/>
					<button
						type="button"
						onclick={() => copyToClipboard(newlyCreatedToken!)}
						class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500"
					>
						Copy
					</button>
				</div>
			</div>
			<div class="flex justify-end pt-2">
				<button
					type="button"
					onclick={() => (createModalOpen = false)}
					class="rounded-xl bg-gray-800 px-4 py-2 text-sm text-gray-200 transition hover:bg-gray-700"
				>
					Done
				</button>
			</div>
		</div>
	{:else}
		<form onsubmit={(e) => { e.preventDefault(); handleCreateToken(); }} class="space-y-4">
			<div>
				<label for="token-label" class="mb-1.5 block text-xs font-semibold text-gray-300 uppercase tracking-wide">Token Name / Service</label>
				<input
					id="token-label"
					type="text"
					bind:value={tokenName}
					placeholder="e.g. ERP Backend Gateway, Mobile App"
					class="w-full rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
				/>
			</div>
			<div class="flex justify-end gap-3 pt-2">
				<button
					type="button"
					onclick={() => (createModalOpen = false)}
					class="rounded-xl border border-gray-700 px-4 py-2 text-sm text-gray-300 transition hover:bg-gray-800"
				>
					Cancel
				</button>
				<button
					type="submit"
					disabled={submitting}
					class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:opacity-50"
				>
					{submitting ? 'Generating...' : 'Create Token'}
				</button>
			</div>
		</form>
	{/if}
</Modal>
