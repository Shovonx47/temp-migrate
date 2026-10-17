<script lang="ts">
	import { goto } from '$app/navigation';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { auth } from '$lib/stores/auth.svelte.js';
	import { api, ApiError } from '$lib/api.js';

	interface Props {
		planSlug?: string;
		planName?: string;
	}

	let { planSlug = '', planName = '' }: Props = $props();

	let step = $state<1 | 2 | 3>(1);
	let submitting = $state(false);
	let errorMsg = $state('');

	// Step 1: Account Info
	let name = $state('');
	let email = $state('');
	let phone = $state('');
	let password = $state('');
	let passwordConfirmation = $state('');

	// Step 2: Company & Business Info
	let businessName = $state('');
	let businessAddress = $state('');
	let tradeLicenseNumber = $state('');
	let tradeLicenseFile = $state<File | null>(null);

	// Step 3: Identity & Compliance
	let nidNumber = $state('');
	let frontNidFile = $state<File | null>(null);
	let backNidFile = $state<File | null>(null);
	let livePhotoBase64 = $state('');
	let termsAccepted = $state(false);

	// Previews
	let frontPreview = $state<string | null>(null);
	let backPreview = $state<string | null>(null);

	function handleFile(e: Event, type: 'front' | 'back' | 'trade') {
		const file = (e.target as HTMLInputElement).files?.[0];
		if (!file) return;

		const reader = new FileReader();
		reader.onload = (ev) => {
			const res = ev.target?.result as string;
			if (type === 'front') {
				frontNidFile = file;
				frontPreview = res;
			} else if (type === 'back') {
				backNidFile = file;
				backPreview = res;
			} else if (type === 'trade') {
				tradeLicenseFile = file;
			}
		};
		reader.readAsDataURL(file);
	}

	function validateStep1(): boolean {
		errorMsg = '';
		if (!name.trim() || !email.trim() || !phone.trim() || !password) {
			errorMsg = 'Please fill in all required account fields.';
			return false;
		}
		if (password.length < 8) {
			errorMsg = 'Password must be at least 8 characters.';
			return false;
		}
		if (password !== passwordConfirmation) {
			errorMsg = 'Passwords do not match.';
			return false;
		}
		return true;
	}

	function validateStep2(): boolean {
		errorMsg = '';
		if (!businessName.trim() || !businessAddress.trim() || !tradeLicenseNumber.trim()) {
			errorMsg = 'Please complete all required business information.';
			return false;
		}
		return true;
	}

	async function handleSubmitRegistration() {
		errorMsg = '';
		if (!termsAccepted) {
			errorMsg = 'You must accept the Terms of Service & Privacy Policy.';
			return;
		}
		if (!nidNumber.trim()) {
			errorMsg = 'National ID number is required for BTRC compliance.';
			return;
		}

		submitting = true;
		try {
			const formData = new FormData();
			formData.append('name', name.trim());
			formData.append('email', email.trim());
			formData.append('phone', phone.trim());
			formData.append('password', password);
			formData.append('password_confirmation', passwordConfirmation);
			formData.append('email_verification_token', 'auto-verified');
			formData.append('phone_verification_token', 'auto-verified');
			formData.append('business_name', businessName.trim());
			formData.append('business_address', businessAddress.trim());
			formData.append('trade_license_number', tradeLicenseNumber.trim());
			if (tradeLicenseFile) formData.append('trade_license_file', tradeLicenseFile);
			formData.append('nid_number', nidNumber.trim());
			if (frontNidFile) formData.append('front_nid', frontNidFile);
			if (backNidFile) formData.append('back_nid', backNidFile);
			formData.append('live_photo', livePhotoBase64 || 'data:image/png;base64,placeholder');
			formData.append('terms', '1');
			if (planSlug) formData.append('plan_slug', planSlug);

			const res = await fetch('/api/v1/customer/register', {
				method: 'POST',
				body: formData,
				credentials: 'include',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				}
			});

			if (!res.ok) {
				const data = await res.json().catch(() => ({}));
				throw new Error(data.message ?? 'Registration failed');
			}

			toast.success('Registration Complete', 'Your account has been created.');
			await auth.init();
			goto('/customer');
		} catch (err: unknown) {
			errorMsg = err instanceof Error ? err.message : 'Registration failed. Please check inputs.';
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head>
	<title>Create Account – WhiteInfo Portal</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 p-4 py-12 transition-colors dark:bg-gray-950">
	<!-- Registration Card -->
	<div class="relative z-10 w-full max-w-2xl rounded-2xl border border-gray-200/90 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<!-- Brand & Header -->
		<div class="mb-8 text-center">
			<div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-md shadow-teal-600/20">
				<svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
					<path
						d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"
					/>
					<path
						d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"
					/>
				</svg>
			</div>
			<h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Open Customer Account</h1>
			<p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
				High-speed enterprise SMS gateway with sub-50ms DLR reporting.
			</p>
			{#if planName}
				<div class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-300">
					<span>Selected Plan:</span>
					<span class="font-bold text-teal-900 dark:text-teal-100">{planName}</span>
				</div>
			{/if}
		</div>

		<!-- Step Progress Indicator -->
		<div class="relative mx-auto mb-8 flex max-w-md items-center justify-between">
			<div class="absolute left-0 right-0 top-1/2 -z-0 h-0.5 -translate-y-1/2 bg-gray-200 dark:bg-gray-800"></div>
			{#each [
				{ num: 1, label: 'Account' },
				{ num: 2, label: 'Business' },
				{ num: 3, label: 'Compliance' }
			] as s (s.num)}
				<div class="relative z-10 flex flex-col items-center gap-1.5">
					<button
						type="button"
						onclick={() => { if (step > s.num) step = s.num as typeof step; }}
						class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition
							{step === s.num ? 'bg-teal-600 text-white shadow-sm ring-4 ring-teal-600/20' :
							 step > s.num ? 'bg-teal-700 text-white' : 'border border-gray-300 bg-white text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'}"
					>
						{#if step > s.num}✓{:else}{s.num}{/if}
					</button>
					<span class="text-[11px] font-medium {step >= s.num ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'}">{s.label}</span>
				</div>
			{/each}
		</div>

		<!-- Error Alert -->
		{#if errorMsg}
			<div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
				<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
				</svg>
				<p>{errorMsg}</p>
			</div>
		{/if}

		<!-- Step 1: Account Credentials -->
		{#if step === 1}
			<div class="space-y-4">
				<div>
					<label for="reg-name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Full Name</label>
					<input
						id="reg-name"
						type="text"
						bind:value={name}
						placeholder="e.g. Asif Mahmud"
						class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					/>
				</div>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div>
						<label for="reg-email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Work Email</label>
						<input
							id="reg-email"
							type="email"
							bind:value={email}
							placeholder="you@company.com"
							class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
						/>
					</div>
					<div>
						<label for="reg-phone" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Mobile Number</label>
						<input
							id="reg-phone"
							type="tel"
							bind:value={phone}
							placeholder="+8801700000000"
							class="w-full font-mono rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
						/>
					</div>
				</div>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div>
						<label for="reg-password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Password</label>
						<input
							id="reg-password"
							type="password"
							bind:value={password}
							placeholder="Minimum 8 characters"
							class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
						/>
					</div>
					<div>
						<label for="reg-confirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Confirm Password</label>
						<input
							id="reg-confirm"
							type="password"
							bind:value={passwordConfirmation}
							placeholder="Repeat password"
							class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
						/>
					</div>
				</div>

				<div class="flex justify-end pt-4">
					<button
						type="button"
						onclick={() => { if (validateStep1()) step = 2; }}
						class="flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500"
					>
						Next: Business Details →
					</button>
				</div>
			</div>
		{/if}

		<!-- Step 2: Business Info -->
		{#if step === 2}
			<div class="space-y-4">
				<div>
					<label for="reg-bname" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Company / Organization Name</label>
					<input
						id="reg-bname"
						type="text"
						bind:value={businessName}
						placeholder="e.g. Acme Technologies Ltd"
						class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					/>
				</div>

				<div>
					<label for="reg-baddr" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Registered Office Address</label>
					<textarea
						id="reg-baddr"
						bind:value={businessAddress}
						rows={2}
						placeholder="House #, Road #, Area, City, Postcode"
						class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					></textarea>
				</div>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div>
						<label for="reg-trade" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Trade License Number</label>
						<input
							id="reg-trade"
							type="text"
							bind:value={tradeLicenseNumber}
							placeholder="e.g. TRAD/DNCC/12345/2026"
							class="w-full font-mono rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
						/>
					</div>
					<div>
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Trade License File (PDF/JPG)</span>
						<input
							type="file"
							accept="image/*,.pdf"
							onchange={(e) => handleFile(e, 'trade')}
							class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 file:mr-2 file:rounded-lg file:border-0 file:bg-gray-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-200"
						/>
					</div>
				</div>

				<div class="flex justify-between pt-4">
					<button
						type="button"
						onclick={() => (step = 1)}
						class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
					>
						← Back
					</button>
					<button
						type="button"
						onclick={() => { if (validateStep2()) step = 3; }}
						class="flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500"
					>
						Next: Compliance & NID →
					</button>
				</div>
			</div>
		{/if}

		<!-- Step 3: Identity & Compliance -->
		{#if step === 3}
			<div class="space-y-4">
				<div>
					<label for="reg-nid" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Applicant National ID (NID)</label>
					<input
						id="reg-nid"
						type="text"
						bind:value={nidNumber}
						placeholder="10, 13, or 17 digit NID number"
						class="w-full font-mono rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
					/>
				</div>

				<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
					<div>
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">NID Front Photo</span>
						<input
							type="file"
							accept="image/*"
							onchange={(e) => handleFile(e, 'front')}
							class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 file:mr-2 file:rounded-lg file:border-0 file:bg-gray-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-200"
						/>
						{#if frontPreview}
							<img src={frontPreview} alt="NID Front" class="mt-2 h-20 rounded-lg object-contain" />
						{/if}
					</div>
					<div>
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">NID Back Photo</span>
						<input
							type="file"
							accept="image/*"
							onchange={(e) => handleFile(e, 'back')}
							class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 file:mr-2 file:rounded-lg file:border-0 file:bg-gray-100 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-200"
						/>
						{#if backPreview}
							<img src={backPreview} alt="NID Back" class="mt-2 h-20 rounded-lg object-contain" />
						{/if}
					</div>
				</div>

				<!-- Terms Checkbox -->
				<div class="pt-2">
					<label class="flex items-start gap-2.5 cursor-pointer select-none">
						<input
							type="checkbox"
							bind:checked={termsAccepted}
							class="mt-1 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-700"
						/>
						<span class="text-xs text-gray-600 dark:text-gray-400">
							I certify that all information provided is accurate and agree to the
							<a href="/terms" target="_blank" class="text-teal-600 hover:underline dark:text-teal-400">Terms of Service</a>
							and BTRC Telecommunication SMS transmission policies.
						</span>
					</label>
				</div>

				<div class="flex justify-between pt-4">
					<button
						type="button"
						onclick={() => (step = 2)}
						class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
					>
						← Back
					</button>
					<button
						type="button"
						onclick={handleSubmitRegistration}
						disabled={submitting}
						class="flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50"
					>
						{submitting ? 'Creating Account...' : 'Complete Registration ✓'}
					</button>
				</div>
			</div>
		{/if}

		<!-- Already have an account link -->
		<div class="mt-8 text-center text-xs text-gray-600 dark:text-gray-400">
			Already have an account?
			<a href="/login" class="font-semibold text-teal-600 hover:text-teal-700 hover:underline dark:text-teal-400">Sign in here</a>
		</div>
	</div>
</div>
