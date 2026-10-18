<script lang="ts">
	import { api, ApiError } from '$lib/api.js';
	import { auth } from '$lib/stores/auth.svelte.js';
	import { toast } from '$lib/components/Toast.svelte';
	import { computeSmsStats } from '$lib/utils/sms.js';
	import { goto } from '$app/navigation';
	import Modal from '$lib/components/Modal.svelte';
	import { onMount } from 'svelte';

	interface Sender {
		id: number;
		sender_id: string;
	}

	let senders = $state<Sender[]>([]);
	let selectedSenderId = $state<number | null>(null);
	let phones = $state<string[]>([]);
	let phoneInput = $state('');
	let body = $state('');
	let submitting = $state(false);
	let scheduleOpen = $state(false);

	// Schedule form state
	let schedDate = $state(new Date().toISOString().slice(0, 10));
	let schedHour = $state(String(new Date().getHours() % 12 || 12).padStart(2, '0'));
	let schedMinute = $state(String((new Date().getMinutes() + 10) % 60).padStart(2, '0'));
	let schedPeriod = $state<'AM' | 'PM'>(new Date().getHours() >= 12 ? 'PM' : 'AM');

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

	function addPhone() {
		const raw = phoneInput.trim();
		if (!raw) return;
		// Split by comma or newline for bulk paste
		const nums = raw
			.split(/[\n,;]+/)
			.map((p) => p.trim())
			.filter(Boolean);
		const newPhones = [...phones];
		for (const num of nums) {
			if (!newPhones.includes(num)) newPhones.push(num);
		}
		phones = newPhones;
		phoneInput = '';
	}

	function removePhone(phone: string) {
		phones = phones.filter((p) => p !== phone);
	}

	function handlePhoneKeydown(e: KeyboardEvent) {
		if (e.key === 'Enter' || e.key === ',') {
			e.preventDefault();
			addPhone();
		}
	}

	async function sendNow() {
		if (submitting) return;
		if (!selectedSenderId) {
			toast.error('No sender selected', 'Please choose a Sender ID.');
			return;
		}
		if (phones.length === 0) {
			toast.error('No recipients', 'Add at least one phone number.');
			return;
		}
		if (!body.trim()) {
			toast.error('Empty message', 'Please write your message.');
			return;
		}

		submitting = true;
		try {
			const res = await api.post<{ data: { id: number } }>('/api/v1/customer/messages/send', {
				sender_id: selectedSenderId,
				phones,
				body: body.trim(),
				source: 'quick'
			});
			toast.success('Message sent!', 'Your quick message has been dispatched.');
			goto(`/customer/batches/${res.data.id}`);
		} catch (e) {
			if (e instanceof ApiError) {
				toast.error('Send failed', (e.data.message as string) ?? 'Please try again.');
			} else {
				toast.error('Network error', 'Could not reach the server.');
			}
		} finally {
			submitting = false;
		}
	}

	async function scheduleMessage() {
		if (!selectedSenderId || phones.length === 0 || !body.trim()) {
			toast.error('Incomplete form', 'Fill in sender, recipients, and message first.');
			return;
		}

		submitting = true;
		try {
			await api.post('/api/v1/customer/messages/schedule', {
				sender_id: selectedSenderId,
				phones,
				body: body.trim(),
				scheduled_date: schedDate,
				scheduled_hour: schedHour,
				scheduled_minute: schedMinute,
				scheduled_period: schedPeriod,
				source: 'quick'
			});
			toast.success('Message scheduled!', `Will be sent on ${schedDate} at ${schedHour}:${schedMinute} ${schedPeriod}`);
			scheduleOpen = false;
			goto('/customer/batches');
		} catch (e) {
			if (e instanceof ApiError) {
				toast.error('Schedule failed', (e.data.message as string) ?? 'Please try again.');
			}
		} finally {
			submitting = false;
		}
	}

	const hours = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
	const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));
</script>

<svelte:head>
	<title>Quick Message – SMS Whiteinfo</title>
	<meta name="description" content="Send a quick SMS to one or multiple recipients." />
</svelte:head>

<div class="mx-auto max-w-2xl space-y-5">
	<!-- Header -->
	<div>
		<h2 class="text-lg font-semibold text-gray-950 dark:text-gray-100">Quick Message</h2>
		<p class="mt-0.5 text-sm text-gray-500">Send an SMS to one or multiple phone numbers instantly.</p>
	</div>

	<!-- Form card -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
		<!-- Sender ID -->
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="sender-select">
				Sender ID <span class="text-red-500">*</span>
			</label>
			<select
				id="sender-select"
				bind:value={selectedSenderId}
				class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 transition focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			>
				<option value={null} disabled>Select a Sender ID</option>
				{#each senders as s (s.id)}
					<option value={s.id}>{s.sender_id}</option>
				{/each}
			</select>
		</div>

		<!-- Phone Numbers (tag input) -->
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="phone-input">
				Phone Numbers <span class="text-red-500">*</span>
			</label>
			<!-- Tags display -->
			{#if phones.length > 0}
				<div class="mb-2 flex flex-wrap gap-1.5">
					{#each phones as phone (phone)}
						<span
							class="flex items-center gap-1 rounded-full bg-teal-50 border border-teal-200 px-2.5 py-1 text-xs font-medium text-teal-800 dark:border-teal-800/40 dark:bg-teal-950/30 dark:text-teal-300"
						>
							{phone}
							<button
								onclick={() => removePhone(phone)}
								class="text-teal-600 hover:text-red-500 transition dark:text-teal-400"
								aria-label="Remove {phone}"
							>
								<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
								</svg>
							</button>
						</span>
					{/each}
				</div>
			{/if}
			<div class="flex gap-2">
				<input
					id="phone-input"
					type="tel"
					bind:value={phoneInput}
					onkeydown={handlePhoneKeydown}
					onblur={addPhone}
					placeholder="e.g. +8801700000000 (Enter or comma to add)"
					class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
				/>
				<button
					onclick={addPhone}
					class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300"
				>
					Add
				</button>
			</div>
			<p class="mt-1.5 text-xs text-gray-500">
				{phones.length} number{phones.length !== 1 ? 's' : ''} added. Paste multiple numbers separated by commas.
			</p>
		</div>

		<!-- Message body -->
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="msg-body">
				Message <span class="text-red-500">*</span>
			</label>
			<textarea
				id="msg-body"
				bind:value={body}
				rows={5}
				placeholder="Type your message here…"
				class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			></textarea>

			<!-- SMS Stats bar -->
			<div
				class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400"
			>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-700 dark:text-gray-300">Remaining:</span>
					<span class:text-red-500={smsStats.remaining < 10}>
						{smsStats.remaining}/{smsStats.perPartTotal}
					</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-700 dark:text-gray-300">Parts:</span>
					<span>{smsStats.parts || (body ? 1 : 0)}</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-700 dark:text-gray-300">Encoding:</span>
					<span class:text-amber-600={smsStats.encoding === 'Unicode'}>{smsStats.encoding}</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="font-medium text-gray-700 dark:text-gray-300">Recipients:</span>
					<span class="font-semibold text-gray-900 dark:text-gray-200">{phones.length}</span>
				</div>
				{#if phones.length > 0 && smsStats.parts > 0}
					<div class="flex items-center gap-1.5">
						<span class="font-medium text-gray-700 dark:text-gray-300">Total SMS:</span>
						<span class="font-semibold text-teal-600 dark:text-teal-400">{smsStats.parts * phones.length}</span>
					</div>
				{/if}
			</div>
		</div>

		<!-- Actions -->
		<div class="flex gap-3 pt-1">
			<button
				id="btn-send-now"
				onclick={sendNow}
				disabled={submitting}
				class="flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-60"
			>
				{#if submitting}
					<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
					</svg>
				{:else}
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
					</svg>
				{/if}
				Send Now
			</button>
			<button
				id="btn-schedule"
				onclick={() => (scheduleOpen = true)}
				disabled={submitting}
				class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 disabled:opacity-60"
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
<Modal bind:open={scheduleOpen} title="Schedule Quick Message" description="Choose when this message should be automatically sent." size="md">
	<div class="space-y-4">
		<!-- Date -->
		<div>
			<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="sched-date">Select Day</label>
			<input
				id="sched-date"
				type="date"
				bind:value={schedDate}
				min={new Date().toISOString().slice(0, 10)}
				class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			/>
		</div>

		<!-- Time -->
		<div class="grid grid-cols-3 gap-3">
			<div>
				<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="sched-hour">Hour</label>
				<select
					id="sched-hour"
					bind:value={schedHour}
					class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-teal-600 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
				>
					{#each hours as h (h)}<option value={h}>{h}</option>{/each}
				</select>
			</div>
			<div>
				<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="sched-min">Minute</label>
				<select
					id="sched-min"
					bind:value={schedMinute}
					class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-teal-600 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
				>
					{#each minutes as m (m)}<option value={m}>{m}</option>{/each}
				</select>
			</div>
			<div>
				<span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">AM / PM</span>
				<div class="flex gap-2 pt-1">
					{#each ['AM', 'PM'] as p (p)}
						<button
							onclick={() => (schedPeriod = p as 'AM' | 'PM')}
							class="flex-1 rounded-lg py-2 text-sm font-medium transition {schedPeriod === p
								? 'bg-teal-600 text-white shadow-sm'
								: 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'}"
						>
							{p}
						</button>
					{/each}
				</div>
			</div>
		</div>

		<!-- Actions -->
		<div class="flex justify-end gap-3 pt-2">
			<button
				onclick={() => (scheduleOpen = false)}
				class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
			>
				Cancel
			</button>
			<button
				id="btn-confirm-schedule"
				onclick={scheduleMessage}
				disabled={submitting}
				class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-60"
			>
				{#if submitting}
					<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
					</svg>
				{/if}
				Confirm & Schedule
			</button>
		</div>
	</div>
</Modal>
