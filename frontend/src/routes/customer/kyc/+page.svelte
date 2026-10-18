<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { api } from '$lib/api.js';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { onMount } from 'svelte';

	interface KycData {
		user: {
			id: number;
			name: string;
			email: string;
			kyc_status: string;
			nid_number?: string;
			trade_license_number?: string;
			kyc_rejected_reason?: string;
		};
		is_resubmission: boolean;
		requested_fields: string[] | null;
		title: string;
		heading: string;
		subheading: string;
	}

	let kycData = $state<KycData | null>(null);
	let loading = $state(true);
	let submitting = $state(false);

	// Form fields
	let nidNumber = $state('');
	let tradeLicenseNumber = $state('');
	let frontNidFile = $state<File | null>(null);
	let backNidFile = $state<File | null>(null);
	let livePhotoFile = $state<File | null>(null);
	let tradeLicenseFile = $state<File | null>(null);

	// Previews
	let frontPreview = $state<string | null>(null);
	let backPreview = $state<string | null>(null);
	let livePreview = $state<string | null>(null);

	async function loadKyc() {
		loading = true;
		try {
			const res = await api.get<{ data: KycData }>('/api/v1/customer/kyc');
			kycData = res.data;
			if (res.data.user.nid_number) nidNumber = res.data.user.nid_number;
			if (res.data.user.trade_license_number) tradeLicenseNumber = res.data.user.trade_license_number;
		} catch {
			// Fallback local status
			kycData = {
				user: {
					id: auth.user?.id ?? 1,
					name: auth.user?.name ?? 'Customer',
					email: auth.user?.email ?? '',
					kyc_status: auth.user?.kyc_status ?? 'under_review'
				},
				is_resubmission: false,
				requested_fields: null,
				title: 'KYC Document Verification',
				heading: 'Account Compliance & BTRC Verification',
				subheading: 'Please upload official National ID documents to comply with Bangladesh Telecommunication Regulatory Commission guidelines.'
			};
		} finally {
			loading = false;
		}
	}

	onMount(() => loadKyc());

	function handleFileChange(e: Event, type: 'front' | 'back' | 'live' | 'trade') {
		const input = e.target as HTMLInputElement;
		const file = input.files?.[0];
		if (!file) return;

		const reader = new FileReader();
		reader.onload = (event) => {
			const url = event.target?.result as string;
			if (type === 'front') {
				frontNidFile = file;
				frontPreview = url;
			} else if (type === 'back') {
				backNidFile = file;
				backPreview = url;
			} else if (type === 'live') {
				livePhotoFile = file;
				livePreview = url;
			} else if (type === 'trade') {
				tradeLicenseFile = file;
			}
		};
		reader.readAsDataURL(file);
	}

	async function handleSubmit(e: SubmitEvent) {
		e.preventDefault();

		if (!nidNumber.trim()) {
			toast.error('Validation Error', 'National ID Number is required.');
			return;
		}

		submitting = true;
		try {
			const formData = new FormData();
			formData.append('nid_number', nidNumber.trim());
			if (frontNidFile) formData.append('front_nid', frontNidFile);
			if (backNidFile) formData.append('back_nid', backNidFile);
			if (livePhotoFile) formData.append('live_photo', livePhotoFile);
			if (tradeLicenseNumber.trim()) formData.append('trade_license_number', tradeLicenseNumber.trim());
			if (tradeLicenseFile) formData.append('trade_license_file', tradeLicenseFile);

			// Direct fetch for multipart/form-data
			const res = await fetch('/api/v1/customer/kyc', {
				method: 'POST',
				body: formData,
				credentials: 'include',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				}
			});

			if (!res.ok) {
				const errorData = await res.json().catch(() => ({}));
				throw new Error(errorData.message ?? 'KYC submission failed');
			}

			toast.success('KYC Submitted', 'Your documents have been submitted for review.');
			if (kycData) {
				kycData.user.kyc_status = 'under_review';
				kycData.is_resubmission = false;
			}
		} catch (err: unknown) {
			const msg = err instanceof Error ? err.message : 'KYC submission failed';
			toast.error('Submission Failed', msg);
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head>
	<title>KYC Verification – SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6 max-w-4xl">
	<!-- Page Header -->
	<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">KYC Verification Center</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Bangladesh Telecommunication Regulatory Commission (BTRC) identity compliance.</p>
		</div>

		<!-- Status Badge -->
		{#if kycData}
			{@const status = kycData.user.kyc_status}
			<span class="inline-flex items-center gap-1.5 self-start rounded-full px-3 py-1 text-xs font-semibold
				{status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30' :
				 status === 'under_review' ? 'bg-teal-50 text-teal-700 border border-teal-200 dark:bg-cyan-500/15 dark:text-cyan-400 dark:border-cyan-500/30' :
				 status === 'resubmit_required' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30' :
				 'bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'}"
			>
				<span class="h-2 w-2 rounded-full {status === 'approved' ? 'bg-emerald-500' : status === 'under_review' ? 'bg-teal-500 animate-pulse' : 'bg-amber-500'}"></span>
				{status === 'approved' ? 'KYC Verified' : status === 'under_review' ? 'Under Review' : status === 'resubmit_required' ? 'Action Required' : 'Unsubmitted'}
			</span>
		{/if}
	</div>

	<!-- Status Card / Alert -->
	{#if kycData?.is_resubmission}
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900 shadow-xs dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
			<div class="flex items-start gap-3">
				<svg class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
				</svg>
				<div class="text-xs space-y-1">
					<p class="font-bold text-sm text-amber-950 dark:text-amber-300">Resubmission Requested by Compliance Officer</p>
					<p class="text-amber-800 dark:text-amber-200">{kycData.subheading}</p>
					{#if kycData.requested_fields && kycData.requested_fields.length > 0}
						<p class="font-medium text-amber-900 dark:text-amber-300 pt-1">
							Fields needing update: <span class="font-mono">{kycData.requested_fields.join(', ')}</span>
						</p>
					{/if}
				</div>
			</div>
		</div>
	{:else if kycData?.user.kyc_status === 'approved'}
		<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900 shadow-xs dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
			<div class="flex items-start gap-3">
				<svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
				</svg>
				<div class="text-xs space-y-1">
					<p class="font-bold text-sm text-emerald-950 dark:text-emerald-300">Identity Verified & Compliant</p>
					<p>Your account has been fully verified and approved. All maskings, high-throughput bulk SMS routes, and API dispatch are unlocked.</p>
				</div>
			</div>
		</div>
	{/if}

	<!-- Upload Form -->
	<form onsubmit={handleSubmit} class="space-y-6">
		<!-- Section 1: National ID Info -->
		<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5 dark:border-gray-800 dark:bg-gray-900">
			<h2 class="text-sm font-bold text-gray-950 dark:text-white">1. National Identity Card (Smart NID / Old NID)</h2>

			<div>
				<label for="nid-number" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
					NID Number (10, 13, or 17 digits) <span class="text-red-500">*</span>
				</label>
				<input
					id="nid-number"
					type="text"
					bind:value={nidNumber}
					placeholder="e.g. 19852692510000123"
					class="w-full font-mono rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
				/>
			</div>

			<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
				<!-- Front Side -->
				<div>
					<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
						NID Front Side <span class="text-red-500">*</span>
					</span>
					<div class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-5 text-center transition hover:border-teal-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-teal-500">
						{#if frontPreview}
							<img src={frontPreview} alt="NID Front" class="max-h-36 rounded-lg object-contain" />
							<p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">File selected: {frontNidFile?.name}</p>
						{:else}
							<svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
							</svg>
							<span class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Click or Drag NID Front</span>
							<span class="text-[10px] text-gray-500">JPG, PNG, or PDF up to 5MB</span>
						{/if}
						<input
							type="file"
							accept="image/*,.pdf"
							onchange={(e) => handleFileChange(e, 'front')}
							class="absolute inset-0 cursor-pointer opacity-0"
						/>
					</div>
				</div>

				<!-- Back Side -->
				<div>
					<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
						NID Back Side <span class="text-red-500">*</span>
					</span>
					<div class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-5 text-center transition hover:border-teal-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-teal-500">
						{#if backPreview}
							<img src={backPreview} alt="NID Back" class="max-h-36 rounded-lg object-contain" />
							<p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">File selected: {backNidFile?.name}</p>
						{:else}
							<svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
							</svg>
							<span class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Click or Drag NID Back</span>
							<span class="text-[10px] text-gray-500">JPG, PNG, or PDF up to 5MB</span>
						{/if}
						<input
							type="file"
							accept="image/*,.pdf"
							onchange={(e) => handleFileChange(e, 'back')}
							class="absolute inset-0 cursor-pointer opacity-0"
						/>
					</div>
				</div>
			</div>
		</div>

		<!-- Section 2: Live Photo Verification -->
		<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4 dark:border-gray-800 dark:bg-gray-900">
			<h2 class="text-sm font-bold text-gray-950 dark:text-white">2. Live Selfie / Portrait Photo</h2>
			<p class="text-xs text-gray-500 dark:text-gray-400">Upload a clear passport-style selfie or photo of the applicant facing the camera.</p>

			<div class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-6 text-center transition hover:border-teal-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-teal-500">
				{#if livePreview}
					<img src={livePreview} alt="Live portrait preview" class="h-32 w-32 rounded-full object-cover border-2 border-teal-600" />
					<p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">Photo selected: {livePhotoFile?.name}</p>
				{:else}
					<svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
						<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
					</svg>
					<span class="mt-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Upload Applicant Photo</span>
					<span class="text-[10px] text-gray-500">Good lighting, clear face without sunglasses</span>
				{/if}
				<input
					type="file"
					accept="image/*"
					onchange={(e) => handleFileChange(e, 'live')}
					class="absolute inset-0 cursor-pointer opacity-0"
				/>
			</div>
		</div>

		<!-- Section 3: Trade License (Optional/Corporate) -->
		<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4 dark:border-gray-800 dark:bg-gray-900">
			<div class="flex items-center justify-between">
				<h2 class="text-sm font-bold text-gray-950 dark:text-white">3. Trade License (Required for Businesses)</h2>
				<span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-400">Optional for Personal</span>
			</div>

			<div>
				<label for="trade-number" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
					Trade License Number
				</label>
				<input
					id="trade-number"
					type="text"
					bind:value={tradeLicenseNumber}
					placeholder="e.g. TRAD/DNCC/12345/2026"
					class="w-full font-mono rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-xs placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
				/>
			</div>

			<div>
				<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
					Trade License Document
				</span>
				<input
					type="file"
					accept="image/*,.pdf"
					onchange={(e) => handleFileChange(e, 'trade')}
					class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs text-gray-700 shadow-xs file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-200 dark:hover:file:bg-gray-600"
				/>
			</div>
		</div>

		<!-- Submit Button -->
		<div class="flex items-center justify-end gap-3 pt-2">
			<button
				type="submit"
				disabled={submitting}
				class="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-teal-500 disabled:opacity-50"
			>
				{submitting ? 'Submitting Documents...' : 'Submit Verification Request'}
			</button>
		</div>
	</form>
</div>
