<script lang="ts">
	import { api, ApiError } from '$lib/api.js';
	import { toast } from '$lib/components/Toast.svelte';
	import { computeSmsStats } from '$lib/utils/sms.js';
	import { goto } from '$app/navigation';
	import Modal from '$lib/components/Modal.svelte';
	import { onMount } from 'svelte';

	interface Sender {
		id: number;
		sender_id: string;
	}

	type Source = 'manual' | 'upload' | 'group';

	interface Recipient {
		phone: string;
		values: Record<string, string>;
	}

	let senders = $state<Sender[]>([]);
	let selectedSenderId = $state<number | null>(null);
	let source = $state<Source>('manual');
	let body = $state('');
	let submitting = $state(false);
	let scheduleOpen = $state(false);

	// Manual recipients
	let recipients = $state<Recipient[]>([{ phone: '', values: {} }]);

	// CSV upload
	let csvFile = $state<File | null>(null);
	let csvHeaders = $state<string[]>([]);
	let csvRows = $state<Record<string, string>[]>([]);
	let phoneColumn = $state('');
	let uploadProgress = $state(0);

	// Schedule
	let schedDate = $state(new Date().toISOString().slice(0, 10));
	let schedHour = $state(String(new Date().getHours() % 12 || 12).padStart(2, '0'));
	let schedMinute = $state(String((new Date().getMinutes() + 10) % 60).padStart(2, '0'));
	let schedPeriod = $state<'AM' | 'PM'>(new Date().getHours() >= 12 ? 'PM' : 'AM');

	// Derived: extract {variable} tokens from body
	let bodyVariables = $derived([...new Set([...body.matchAll(/\{(\w+)\}/g)].map((m) => m[1]))]);
	let smsStats = $derived(computeSmsStats(body));

	async function loadSenders() {
		try {
			const data = await api.get<{ data: Sender[] }>('/api/v1/customer/senders');
			senders = data.data;
			if (senders.length === 1) selectedSenderId = senders[0].id;
		} catch {
			senders = [];
		}
	}

	onMount(loadSenders);

	function addRecipient() {
		recipients = [...recipients, { phone: '', values: {} }];
	}

	function removeRecipient(i: number) {
		recipients = recipients.filter((_, idx) => idx !== i);
	}

	async function handleCsvUpload(e: Event) {
		const file = (e.target as HTMLInputElement).files?.[0];
		if (!file) return;
		csvFile = file;
		uploadProgress = 0;

		// Dynamic import for papaparse-style parsing using browser's built-in
		const text = await file.text();
		const lines = text.split('\n').filter((l) => l.trim());
		if (lines.length < 2) {
			toast.error('Invalid CSV', 'File must have a header row and at least one data row.');
			return;
		}

		const parseCSVLine = (line: string): string[] => {
			const result: string[] = [];
			let inQuotes = false;
			let current = '';
			for (const ch of line) {
				if (ch === '"') {
					inQuotes = !inQuotes;
				} else if (ch === ',' && !inQuotes) {
					result.push(current.trim());
					current = '';
				} else {
					current += ch;
				}
			}
			result.push(current.trim());
			return result;
		};

		csvHeaders = parseCSVLine(lines[0]).map((h) => h.replace(/^"|"$/g, ''));
		csvRows = lines
			.slice(1)
			.map((line) => {
				const vals = parseCSVLine(line).map((v) => v.replace(/^"|"$/g, ''));
				return Object.fromEntries(csvHeaders.map((h, i) => [h, vals[i] ?? '']));
			})
			.filter((row) => Object.values(row).some((v) => v));

		// Auto-detect phone column
		const phoneCol = csvHeaders.find((h) =>
			/phone|mobile|number|msisdn/i.test(h)
		);
		phoneColumn = phoneCol ?? csvHeaders[0] ?? '';
		uploadProgress = 100;
		toast.success('CSV loaded', `${csvRows.length} rows ready.`);
	}

	async function handleSubmit(scheduled?: { date: string; hour: string; minute: string; period: string }) {
		if (!selectedSenderId) {
			toast.error('No sender selected', 'Please choose a Sender ID.');
			return;
		}
		if (!body.trim()) {
			toast.error('Empty message', 'Please write your message.');
			return;
		}

		submitting = true;
		try {
			const payload: Record<string, unknown> = {
				sender_id: selectedSenderId,
				body: body.trim(),
				source
			};

			if (source === 'manual') {
				payload.recipients = recipients.filter((r) => r.phone.trim());
			} else if (source === 'upload' && csvFile) {
				payload.file_headers = csvHeaders;
				payload.phone_column = phoneColumn;
				payload.file_rows = csvRows.slice(0, 5000); // send first 5k for now
				payload.total_rows = csvRows.length;
			}

			if (scheduled) {
				Object.assign(payload, {
					scheduled_date: scheduled.date,
					scheduled_hour: scheduled.hour,
					scheduled_minute: scheduled.minute,
					scheduled_period: scheduled.period
				});
			}

			const res = await api.post<{ data: { id: number } }>('/api/v1/customer/messages/bulk-send', payload);
			toast.success(scheduled ? 'Batch scheduled!' : 'Batch queued!', 'Your bulk campaign is being processed.');
			goto(`/customer/batches/${res.data.id}`);
		} catch (e) {
			if (e instanceof ApiError) {
				toast.error('Send failed', (e.data.message as string) ?? 'Please try again.');
			}
		} finally {
			submitting = false;
			scheduleOpen = false;
		}
	}

	const hours = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
	const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));

	let recipientCount = $derived(
		source === 'manual'
			? recipients.filter((r) => r.phone.trim()).length
			: source === 'upload'
				? csvRows.length
				: 0
	);
</script>

<svelte:head>
	<title>Bulk Message – SMS Portal</title>
	<meta name="description" content="Send bulk SMS campaigns to many recipients." />
</svelte:head>

<div class="mx-auto max-w-3xl space-y-5">
	<div>
		<h2 class="text-lg font-semibold text-gray-100">Bulk Message</h2>
		<p class="mt-0.5 text-sm text-gray-500">
			Send personalized bulk SMS campaigns from a CSV file or manual entry.
		</p>
	</div>

	<div class="rounded-xl border border-gray-800 bg-gray-900 p-5 space-y-5">
		<!-- Sender -->
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-300" for="bulk-sender">
				Sender ID <span class="text-red-400">*</span>
			</label>
			<select
				id="bulk-sender"
				bind:value={selectedSenderId}
				class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-gray-200 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			>
				<option value={null} disabled>Select a Sender ID</option>
				{#each senders as s (s.id)}<option value={s.id}>{s.sender_id}</option>{/each}
			</select>
		</div>

		<!-- Source tabs -->
		<div>
			<p class="mb-2 text-sm font-medium text-gray-300">Recipients Source</p>
			<div class="flex rounded-lg border border-gray-700 p-0.5 bg-gray-800 gap-0.5">
				{#each [['manual', 'Manual Entry'], ['upload', 'CSV Upload']] as [val, label] (val)}
					<button
						onclick={() => (source = val as Source)}
						class="flex-1 rounded-md py-1.5 text-sm font-medium transition {source === val
							? 'bg-primary-600 text-white shadow'
							: 'text-gray-400 hover:text-gray-200'}"
					>
						{label}
					</button>
				{/each}
			</div>
		</div>

		<!-- Manual recipients -->
		{#if source === 'manual'}
			<div class="space-y-2">
				{#each recipients as recipient, i (i)}
					<div class="flex gap-2">
						<input
							type="tel"
							bind:value={recipient.phone}
							placeholder="+8801700000000"
							class="flex-1 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-gray-200 placeholder-gray-500 focus:border-primary-500 focus:outline-none"
						/>
						{#if bodyVariables.length > 0}
							{#each bodyVariables as varName (varName)}
								<input
									type="text"
									bind:value={recipient.values[varName]}
									placeholder="{'{' + varName + '}'}"
									class="w-28 rounded-lg border border-gray-700 bg-gray-800 px-2 py-2 text-sm text-gray-200 placeholder-gray-600 focus:border-primary-500 focus:outline-none"
								/>
							{/each}
						{/if}
						<button
							onclick={() => removeRecipient(i)}
							class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-700 hover:text-red-400"
							aria-label="Remove recipient"
						>
							<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
				{/each}
				<button
					onclick={addRecipient}
					class="flex items-center gap-1.5 text-sm text-primary-400 transition hover:text-primary-300"
				>
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
					</svg>
					Add recipient
				</button>
			</div>
		{/if}

		<!-- CSV Upload -->
		{#if source === 'upload'}
			<div class="space-y-3">
				<label
					class="flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed border-gray-700 bg-gray-800/50 p-8 transition hover:border-primary-600 hover:bg-gray-800"
					for="csv-upload"
				>
					<svg class="h-10 w-10 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
					</svg>
					{#if csvFile}
						<div class="text-center">
							<p class="text-sm font-medium text-gray-200">{csvFile.name}</p>
							<p class="text-xs text-primary-400">{csvRows.length} rows loaded</p>
						</div>
					{:else}
						<div class="text-center">
							<p class="text-sm font-medium text-gray-300">Drop your CSV file here</p>
							<p class="text-xs text-gray-500">or click to browse</p>
						</div>
					{/if}
					<input
						id="csv-upload"
						type="file"
						accept=".csv,.txt"
						onchange={handleCsvUpload}
						class="sr-only"
					/>
				</label>

				{#if csvHeaders.length > 0}
					<div>
						<label class="mb-1.5 block text-sm font-medium text-gray-300" for="phone-col">
							Phone Number Column
						</label>
						<select
							id="phone-col"
							bind:value={phoneColumn}
							class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-gray-200 focus:border-primary-500 focus:outline-none"
						>
							{#each csvHeaders as h (h)}<option value={h}>{h}</option>{/each}
						</select>
					</div>

					<!-- Preview table -->
					{#if csvRows.length > 0}
						<div class="overflow-x-auto rounded-lg border border-gray-700">
							<table class="min-w-full divide-y divide-gray-700 text-xs">
								<thead class="bg-gray-800">
									<tr>
										{#each csvHeaders.slice(0, 6) as h (h)}
											<th class="px-3 py-2 text-left font-medium text-gray-400">{h}</th>
										{/each}
									</tr>
								</thead>
								<tbody class="divide-y divide-gray-800">
									{#each csvRows.slice(0, 3) as row, i (i)}
										<tr>
											{#each csvHeaders.slice(0, 6) as h (h)}
												<td class="px-3 py-2 text-gray-300">{row[h] ?? ''}</td>
											{/each}
										</tr>
									{/each}
								</tbody>
							</table>
						</div>
						<p class="text-xs text-gray-500">
							Showing 3 of {csvRows.length} rows. {csvRows.length} recipients will receive the message.
						</p>
					{/if}
				{/if}
			</div>
		{/if}

		<!-- Message body -->
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-300" for="bulk-body">
				Message Template <span class="text-red-400">*</span>
			</label>
			{#if bodyVariables.length > 0 || true}
				<div class="mb-2 flex flex-wrap gap-1.5">
					<span class="text-xs text-gray-500">Variables:</span>
					{#each ['name', 'amount', 'due', 'date'] as v (v)}
						<button
							onclick={() => (body += `{${v}}`)}
							class="rounded-md bg-gray-800 px-2 py-0.5 text-xs font-mono text-primary-400 transition hover:bg-gray-700"
						>
							{'{' + v + '}'}
						</button>
					{/each}
				</div>
			{/if}
			<textarea
				id="bulk-body"
				bind:value={body}
				rows={5}
				placeholder={'Hello {name}, your payment of ৳{amount} is due on {due}.'}
				class="w-full resize-none rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-gray-200 placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			></textarea>

			<!-- Stats bar -->
			<div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1.5 rounded-lg border border-gray-700 bg-gray-800/60 px-3 py-2 text-xs text-gray-400">
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-300">Remaining:</span>
					<span class:text-red-400={smsStats.remaining < 10}>{smsStats.remaining}/{smsStats.perPartTotal}</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-300">Parts:</span>
					<span>{smsStats.parts || (body ? 1 : 0)}</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-300">Encoding:</span>
					<span class:text-yellow-400={smsStats.encoding === 'Unicode'}>{smsStats.encoding}</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-300">Recipients:</span>
					<span class="font-semibold text-gray-200">{recipientCount}</span>
				</div>
				{#if recipientCount > 0 && smsStats.parts > 0}
					<div class="flex items-center gap-1.5">
						<span class="font-medium text-gray-300">Total SMS:</span>
						<span class="font-semibold text-primary-400">{smsStats.parts * recipientCount}</span>
					</div>
				{/if}
			</div>
		</div>

		<!-- Actions -->
		<div class="flex gap-3 pt-1">
			<button
				id="btn-bulk-send"
				onclick={() => handleSubmit()}
				disabled={submitting}
				class="flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-500 disabled:opacity-60"
			>
				{#if submitting}
					<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
					</svg>
				{:else}
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
					</svg>
				{/if}
				Send Now
			</button>
			<button
				id="btn-bulk-schedule"
				onclick={() => (scheduleOpen = true)}
				disabled={submitting}
				class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800 px-5 py-2.5 text-sm font-medium text-gray-300 transition hover:bg-gray-700 disabled:opacity-60"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
				</svg>
				Schedule
			</button>
		</div>
	</div>
</div>

<!-- Schedule Modal -->
<Modal bind:open={scheduleOpen} title="Schedule Bulk Message" description="Choose when this batch should be automatically sent." size="md">
	<div class="space-y-4">
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-300" for="bulk-sched-date">Select Day</label>
			<input id="bulk-sched-date" type="date" bind:value={schedDate} min={new Date().toISOString().slice(0, 10)}
				class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-gray-200 focus:border-primary-500 focus:outline-none" />
		</div>
		<div class="grid grid-cols-3 gap-3">
			<div>
				<label class="mb-1.5 block text-sm font-medium text-gray-300" for="bulk-sched-hour">Hour</label>
				<select id="bulk-sched-hour" bind:value={schedHour} class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-gray-200 focus:outline-none">
					{#each hours as h (h)}<option value={h}>{h}</option>{/each}
				</select>
			</div>
			<div>
				<label class="mb-1.5 block text-sm font-medium text-gray-300" for="bulk-sched-min">Minute</label>
				<select id="bulk-sched-min" bind:value={schedMinute} class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-gray-200 focus:outline-none">
					{#each minutes as m (m)}<option value={m}>{m}</option>{/each}
				</select>
			</div>
			<div>
				<span class="mb-1.5 block text-sm font-medium text-gray-300">AM / PM</span>
				<div class="flex gap-2 pt-1">
					{#each ['AM', 'PM'] as p (p)}
						<button onclick={() => (schedPeriod = p as 'AM' | 'PM')}
							class="flex-1 rounded-lg py-2 text-sm font-medium transition {schedPeriod === p ? 'bg-primary-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'}">{p}</button>
					{/each}
				</div>
			</div>
		</div>
		<div class="flex justify-end gap-3 pt-2">
			<button onclick={() => (scheduleOpen = false)} class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300 hover:bg-gray-800">Cancel</button>
			<button id="btn-bulk-confirm-sched" onclick={() => handleSubmit({ date: schedDate, hour: schedHour, minute: schedMinute, period: schedPeriod })} disabled={submitting}
				class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-60">
				Confirm & Schedule
			</button>
		</div>
	</div>
</Modal>
