<script lang="ts">
	import { goto } from '$app/navigation';
	import { toast } from '$lib/components/Toast.svelte';
	import { auth } from '$lib/stores/auth.svelte.js';
	import { api, ApiError } from '$lib/api.js';
	import { onMount, onDestroy } from 'svelte';

	interface Props {
		planSlug?: string;
		planName?: string;
	}

	let { planSlug = '', planName = '' }: Props = $props();

	// Form State
	let name = $state('');
	let email = $state('');
	let phone = $state('');
	let password = $state('');
	let passwordConfirmation = $state('');
	let showPassword = $state(false);
	let showConfirmPassword = $state(false);

	// Email OTP
	let emailOtp = $state('');
	let emailOtpSent = $state(false);
	let emailVerified = $state(false);
	let emailVerificationToken = $state('');
	let emailCooldown = $state(0);
	let emailCooldownTimer: number | null = null;
	let emailSending = $state(false);
	let emailVerifying = $state(false);
	let emailError = $state('');
	let emailSuccess = $state('');

	// Phone OTP
	let phoneOtp = $state('');
	let phoneOtpSent = $state(false);
	let phoneVerified = $state(false);
	let phoneVerificationToken = $state('');
	let phoneCooldown = $state(0);
	let phoneCooldownTimer: number | null = null;
	let phoneSending = $state(false);
	let phoneVerifying = $state(false);
	let phoneError = $state('');
	let phoneSuccess = $state('');

	// NID / KYC
	let nidNumber = $state('');
	let frontNidFile = $state<File | null>(null);
	let backNidFile = $state<File | null>(null);
	let frontPreview = $state<string | null>(null);
	let backPreview = $state<string | null>(null);

	// Live Photo Webcam
	let livePhoto = $state<string | null>(null);
	let isCameraOpen = $state(false);
	let videoElement = $state<HTMLVideoElement | null>(null);
	let mediaStream = $state<MediaStream | null>(null);
	let cameraError = $state('');
	let isQrModalOpen = $state(false);

	// Business Information
	let businessCollapsed = $state(false);
	let businessName = $state('');
	let tradeLicenseNumber = $state('');
	let businessAddress = $state('');
	let tradeLicenseFile = $state<File | null>(null);
	let tradeLicenseFileName = $state('');

	// Terms & Submitting
	let termsAccepted = $state(false);
	let submitting = $state(false);
	let generalError = $state('');

	// Cooldown Timers
	function startEmailCooldown(seconds: number = 60) {
		emailCooldown = seconds;
		if (emailCooldownTimer) clearInterval(emailCooldownTimer);
		emailCooldownTimer = window.setInterval(() => {
			if (emailCooldown > 0) {
				emailCooldown--;
			} else {
				if (emailCooldownTimer) clearInterval(emailCooldownTimer);
				emailCooldownTimer = null;
			}
		}, 1000);
	}

	function startPhoneCooldown(seconds: number = 60) {
		phoneCooldown = seconds;
		if (phoneCooldownTimer) clearInterval(phoneCooldownTimer);
		phoneCooldownTimer = window.setInterval(() => {
			if (phoneCooldown > 0) {
				phoneCooldown--;
			} else {
				if (phoneCooldownTimer) clearInterval(phoneCooldownTimer);
				phoneCooldownTimer = null;
			}
		}, 1000);
	}

	onDestroy(() => {
		if (emailCooldownTimer) clearInterval(emailCooldownTimer);
		if (phoneCooldownTimer) clearInterval(phoneCooldownTimer);
		stopCamera();
	});

	// Email OTP Actions
	async function handleSendEmailOtp() {
		emailError = '';
		emailSuccess = '';
		if (!email.trim()) {
			emailError = 'Please enter your email address first.';
			return;
		}
		emailSending = true;
		try {
			const res = await api.post<{ success: boolean; message: string; cooldown_seconds?: number }>('/api/v1/customer/otp/send', {
				channel: 'email',
				email: email.trim()
			});
			emailOtpSent = true;
			startEmailCooldown(res.cooldown_seconds ?? 60);
			emailSuccess = res.message ?? 'Verification code sent to your email.';
			toast.success('Email OTP Sent', emailSuccess);
		} catch (err: unknown) {
			const msg = err instanceof ApiError ? err.message : 'Failed to send email verification code.';
			emailError = msg;
			toast.error('OTP Error', msg);
		} finally {
			emailSending = false;
		}
	}

	async function handleVerifyEmailOtp() {
		emailError = '';
		emailSuccess = '';
		if (!emailOtp.trim()) {
			emailError = 'Please enter the 6-digit verification code.';
			return;
		}
		emailVerifying = true;
		try {
			const res = await api.post<{ success: boolean; message: string; token: string }>('/api/v1/customer/otp/verify', {
				channel: 'email',
				email: email.trim(),
				otp: emailOtp.trim()
			});
			emailVerified = true;
			emailVerificationToken = res.token;
			emailSuccess = 'Email verified successfully!';
			toast.success('Verified', 'Email address verified successfully.');
		} catch (err: unknown) {
			const msg = err instanceof ApiError ? err.message : 'Incorrect verification code.';
			emailError = msg;
			toast.error('Verification Failed', msg);
		} finally {
			emailVerifying = false;
		}
	}

	// Phone OTP Actions
	async function handleSendPhoneOtp() {
		phoneError = '';
		phoneSuccess = '';
		if (!phone.trim()) {
			phoneError = 'Please enter your mobile number first.';
			return;
		}
		phoneSending = true;
		try {
			const res = await api.post<{ success: boolean; message: string; cooldown_seconds?: number }>('/api/v1/customer/otp/send', {
				channel: 'sms',
				phone: phone.trim()
			});
			phoneOtpSent = true;
			startPhoneCooldown(res.cooldown_seconds ?? 60);
			phoneSuccess = res.message ?? 'Verification code sent to your mobile number.';
			toast.success('SMS OTP Sent', phoneSuccess);
		} catch (err: unknown) {
			const msg = err instanceof ApiError ? err.message : 'Failed to send SMS OTP.';
			phoneError = msg;
			toast.error('OTP Error', msg);
		} finally {
			phoneSending = false;
		}
	}

	async function handleVerifyPhoneOtp() {
		phoneError = '';
		phoneSuccess = '';
		if (!phoneOtp.trim()) {
			phoneError = 'Please enter the 6-digit SMS code.';
			return;
		}
		phoneVerifying = true;
		try {
			const res = await api.post<{ success: boolean; message: string; token: string }>('/api/v1/customer/otp/verify', {
				channel: 'sms',
				phone: phone.trim(),
				otp: phoneOtp.trim()
			});
			phoneVerified = true;
			phoneVerificationToken = res.token;
			phoneSuccess = 'Mobile verified successfully!';
			toast.success('Verified', 'Mobile number verified successfully.');
		} catch (err: unknown) {
			const msg = err instanceof ApiError ? err.message : 'Incorrect SMS code.';
			phoneError = msg;
			toast.error('Verification Failed', msg);
		} finally {
			phoneVerifying = false;
		}
	}

	// File Handling
	function handleFileInput(e: Event, type: 'front' | 'back' | 'trade') {
		const target = e.target as HTMLInputElement;
		const file = target.files?.[0];
		if (!file) return;

		if (type === 'front' || type === 'back') {
			const reader = new FileReader();
			reader.onload = (ev) => {
				if (type === 'front') {
					frontNidFile = file;
					frontPreview = ev.target?.result as string;
				} else {
					backNidFile = file;
					backPreview = ev.target?.result as string;
				}
			};
			reader.readAsDataURL(file);
		} else if (type === 'trade') {
			tradeLicenseFile = file;
			tradeLicenseFileName = file.name;
		}
	}

	// Webcam Operations
	async function openCamera() {
		cameraError = '';
		isCameraOpen = true;

		try {
			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
				throw new Error('Camera access is not supported by your browser or requires HTTPS.');
			}
			const stream = await navigator.mediaDevices.getUserMedia({
				video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
				audio: false
			});
			mediaStream = stream;
			if (videoElement) {
				videoElement.srcObject = stream;
				await videoElement.play();
			}
		} catch (err: unknown) {
			cameraError = err instanceof Error ? err.message : 'Unable to access camera. Please allow camera permissions.';
		}
	}

	function stopCamera() {
		if (mediaStream) {
			mediaStream.getTracks().forEach((track) => track.stop());
			mediaStream = null;
		}
		isCameraOpen = false;
	}

	function capturePhoto() {
		if (!videoElement) return;
		const canvas = document.createElement('canvas');
		canvas.width = videoElement.videoWidth || 640;
		canvas.height = videoElement.videoHeight || 480;
		const ctx = canvas.getContext('2d');
		if (ctx) {
			ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
			livePhoto = canvas.toDataURL('image/jpeg', 0.9);
			stopCamera();
			toast.success('Live Photo Captured', 'Selfie captured successfully.');
		}
	}

	// Submit Registration
	async function handleRegister(e: SubmitEvent) {
		e.preventDefault();
		generalError = '';

		// Sequential validations matching Filament
		if (!name.trim()) {
			generalError = 'Your full name is required.';
			document.getElementById('reg-name')?.focus();
			return;
		}
		if (!email.trim()) {
			generalError = 'An email address is required.';
			document.getElementById('reg-email')?.focus();
			return;
		}
		if (!emailVerified) {
			generalError = 'Please verify your email address using the Send Email OTP button before creating an account.';
			document.getElementById('field-wrap-email')?.scrollIntoView({ behavior: 'smooth' });
			return;
		}
		if (!phone.trim()) {
			generalError = 'A mobile number is required.';
			document.getElementById('reg-phone')?.focus();
			return;
		}
		if (!phoneVerified) {
			generalError = 'Please verify your mobile number using the Send SMS OTP button before creating an account.';
			document.getElementById('field-wrap-phone')?.scrollIntoView({ behavior: 'smooth' });
			return;
		}
		if (!password) {
			generalError = 'A password is required to secure your account.';
			document.getElementById('reg-password')?.focus();
			return;
		}
		if (password !== passwordConfirmation) {
			generalError = 'The password and confirmation password do not match.';
			document.getElementById('reg-confirm-password')?.focus();
			return;
		}
		if (!nidNumber.trim()) {
			generalError = 'Your National ID (NID) number is required for identity verification.';
			document.getElementById('reg-nid')?.focus();
			return;
		}
		if (!frontNidFile) {
			generalError = 'Please upload a clear photo of the FRONT side of your NID card.';
			document.getElementById('section-nid')?.scrollIntoView({ behavior: 'smooth' });
			return;
		}
		if (!backNidFile) {
			generalError = 'Please upload a clear photo of the BACK side of your NID card.';
			document.getElementById('section-nid')?.scrollIntoView({ behavior: 'smooth' });
			return;
		}
		if (!livePhoto) {
			generalError = 'A live selfie photo is required for identity verification. Please capture your photo using your webcam.';
			document.getElementById('section-live-photo')?.scrollIntoView({ behavior: 'smooth' });
			return;
		}
		if (!businessName.trim()) {
			generalError = 'Your business or company name is required.';
			document.getElementById('reg-business-name')?.focus();
			return;
		}
		if (!tradeLicenseNumber.trim()) {
			generalError = 'Your trade license number is required.';
			document.getElementById('reg-trade-license')?.focus();
			return;
		}
		if (!businessAddress.trim()) {
			generalError = 'Your business address is required.';
			document.getElementById('reg-business-address')?.focus();
			return;
		}
		if (!tradeLicenseFile) {
			generalError = 'Please upload your Trade License document (PDF or image).';
			document.getElementById('section-business')?.scrollIntoView({ behavior: 'smooth' });
			return;
		}
		if (!termsAccepted) {
			generalError = 'You must read and agree to the Terms & Conditions and Privacy Policy before creating your account.';
			return;
		}

		submitting = true;
		try {
			const formData = new FormData();
			formData.append('name', name.trim());
			formData.append('email', email.trim().toLowerCase());
			formData.append('phone', phone.trim());
			formData.append('password', password);
			formData.append('password_confirmation', passwordConfirmation);
			formData.append('email_verification_token', emailVerificationToken);
			formData.append('phone_verification_token', phoneVerificationToken);
			formData.append('nid_number', nidNumber.trim());
			formData.append('front_nid', frontNidFile);
			formData.append('back_nid', backNidFile);
			formData.append('live_photo', livePhoto);
			formData.append('business_name', businessName.trim());
			formData.append('trade_license_number', tradeLicenseNumber.trim());
			formData.append('business_address', businessAddress.trim());
			formData.append('trade_license_file', tradeLicenseFile);
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

			const data = await res.json().catch(() => ({}));

			if (!res.ok) {
				let msg = data.message || 'Registration failed. Please check your details.';
				if (data.errors) {
					const firstKey = Object.keys(data.errors)[0];
					if (firstKey && data.errors[firstKey][0]) {
						msg = data.errors[firstKey][0];
					}
				}
				throw new Error(msg);
			}

			toast.success('Registration Complete', 'Your account has been created. Your KYC is under review.');
			await auth.init();
			goto('/customer');
		} catch (err: unknown) {
			generalError = err instanceof Error ? err.message : 'Registration failed. Please check all fields.';
			toast.error('Registration Failed', generalError);
		} finally {
			submitting = false;
		}
	}
</script>

<svelte:head>
	<title>Sign up – SMS Whiteinfo</title>
	<meta name="description" content="Register your SMS Whiteinfo customer account." />
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 transition-colors dark:bg-gray-950">
	<div class="w-full max-w-4xl">
		<!-- Outer Filament Card -->
		<div class="rounded-2xl border border-gray-200/80 bg-white p-6 sm:p-10 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<!-- Header inside Card -->
			<div class="mb-8 text-center">
				<div class="mx-auto mb-4 flex justify-center">
					<img
						src="/logo.png"
						alt="SMS Whiteinfo"
						class="h-[50px] w-auto object-contain"
						onerror={(e) => {
							(e.currentTarget as HTMLElement).style.display = 'none';
							const fallback = document.getElementById('reg-logo-fallback');
							if (fallback) fallback.style.display = 'block';
						}}
					/>
					<span id="reg-logo-fallback" class="hidden text-xl font-bold tracking-tight text-gray-900 dark:text-white">
						SMS Whiteinfo
					</span>
				</div>
				<h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-950 dark:text-white">Sign up</h1>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					or <a href="/login" class="font-medium text-teal-600 hover:text-teal-500 hover:underline dark:text-teal-400">sign in to your account</a>
				</p>
				{#if planName}
					<div class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800 dark:border-teal-900/50 dark:bg-teal-950/40 dark:text-teal-300">
						<span>Selected Plan:</span>
						<span class="font-bold text-teal-900 dark:text-teal-100">{planName}</span>
					</div>
				{/if}
			</div>

			<!-- General Error Notification -->
			{#if generalError}
				<div class="mb-6 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
					<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
					</svg>
					<p>{generalError}</p>
				</div>
			{/if}

			<form onsubmit={handleRegister} class="space-y-6">
				<!-- ==========================================
				     1. Personal Information Section
				     ========================================== -->
				<section class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
					<!-- Section Header -->
					<div class="flex items-start gap-3">
						<svg class="mt-0.5 h-5 w-5 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
						</svg>
						<div>
							<h2 class="text-base font-semibold text-gray-950 dark:text-white">Personal Information</h2>
							<p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
								Provide your personal details and verify your email and mobile number.
							</p>
						</div>
					</div>

					<div class="my-4 border-b border-gray-100 dark:border-gray-800"></div>

					<div class="space-y-4">
						<!-- Full Name -->
						<div>
							<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-name">
								Full Name <span class="text-red-500">*</span>
							</label>
							<div class="relative">
								<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
									<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
									</svg>
								</div>
								<input
									id="reg-name"
									type="text"
									bind:value={name}
									placeholder="Enter your full name"
									required
									class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
								/>
							</div>
						</div>

						<!-- Grid: Email & Mobile with OTP -->
						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<!-- Email with Verification -->
							<div id="field-wrap-email" class="space-y-2">
								<label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-email">
									Email Address <span class="text-red-500">*</span>
								</label>
								<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
									<div class="relative flex-1">
										<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
											<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
											</svg>
										</div>
										<input
											id="reg-email"
											type="email"
											bind:value={email}
											disabled={emailVerified}
											placeholder="Enter your email address"
											required
											class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:disabled:bg-gray-800"
										/>
									</div>
									<div>
										{#if emailVerified}
											<div class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 shadow-xs dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-400">
												<svg class="h-4 w-4 text-teal-600 dark:text-teal-400" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
												</svg>
												<span>Verified</span>
											</div>
										{:else}
											<button
												type="button"
												onclick={handleSendEmailOtp}
												disabled={emailCooldown > 0 || emailSending}
												class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 focus:outline-hidden focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
											>
												{#if emailSending}
													<span>Sending...</span>
												{:else if emailCooldown > 0}
													<span>Resend ({emailCooldown}s)</span>
												{:else}
													<span>Send Email OTP</span>
												{/if}
											</button>
										{/if}
									</div>
								</div>

								{#if emailError}
									<div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
										<svg class="h-4 w-4 text-red-600 dark:text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
										</svg>
										<span>{emailError}</span>
									</div>
								{/if}

								{#if emailSuccess}
									<div class="flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 p-2.5 text-xs text-teal-700 dark:border-teal-900 dark:bg-teal-950/30 dark:text-teal-300">
										<svg class="h-4 w-4 text-teal-600 dark:text-teal-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
										</svg>
										<span>{emailSuccess}</span>
									</div>
								{/if}

								{#if !emailVerified && emailOtpSent}
									<div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60 animate-fade-in">
										<div class="relative flex-1">
											<input
												type="text"
												bind:value={emailOtp}
												maxlength="6"
												placeholder="Enter 6-digit Email OTP"
												class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-mono text-sm tracking-wider text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
											/>
										</div>
										<button
											type="button"
											onclick={handleVerifyEmailOtp}
											disabled={emailVerifying}
											class="rounded-lg bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 disabled:opacity-50"
										>
											{emailVerifying ? 'Verifying...' : 'Verify'}
										</button>
									</div>
								{/if}
							</div>

							<!-- Mobile with Verification -->
							<div id="field-wrap-phone" class="space-y-2">
								<label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-phone">
									Mobile Number <span class="text-red-500">*</span>
								</label>
								<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
									<div class="relative flex-1">
										<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
											<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
											</svg>
										</div>
										<input
											id="reg-phone"
											type="tel"
											bind:value={phone}
											disabled={phoneVerified}
											placeholder="+8801XXXXXXXXX"
											required
											class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 disabled:bg-gray-50 disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:disabled:bg-gray-800 font-mono"
										/>
									</div>
									<div>
										{#if phoneVerified}
											<div class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 shadow-xs dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-400">
												<svg class="h-4 w-4 text-teal-600 dark:text-teal-400" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
												</svg>
												<span>Verified</span>
											</div>
										{:else}
											<button
												type="button"
												onclick={handleSendPhoneOtp}
												disabled={phoneCooldown > 0 || phoneSending}
												class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 focus:outline-hidden focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
											>
												{#if phoneSending}
													<span>Sending...</span>
												{:else if phoneCooldown > 0}
													<span>Resend ({phoneCooldown}s)</span>
												{:else}
													<span>Send SMS OTP</span>
												{/if}
											</button>
										{/if}
									</div>
								</div>

								{#if phoneError}
									<div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
										<svg class="h-4 w-4 text-red-600 dark:text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
										</svg>
										<span>{phoneError}</span>
									</div>
								{/if}

								{#if phoneSuccess}
									<div class="flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 p-2.5 text-xs text-teal-700 dark:border-teal-900 dark:bg-teal-950/30 dark:text-teal-300">
										<svg class="h-4 w-4 text-teal-600 dark:text-teal-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
										</svg>
										<span>{phoneSuccess}</span>
									</div>
								{/if}

								{#if !phoneVerified && phoneOtpSent}
									<div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60 animate-fade-in">
										<div class="relative flex-1">
											<input
												type="text"
												bind:value={phoneOtp}
												maxlength="6"
												placeholder="Enter 6-digit SMS OTP"
												class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-mono text-sm tracking-wider text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
											/>
										</div>
										<button
											type="button"
											onclick={handleVerifyPhoneOtp}
											disabled={phoneVerifying}
											class="rounded-lg bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-teal-700 disabled:opacity-50"
										>
											{phoneVerifying ? 'Verifying...' : 'Verify'}
										</button>
									</div>
								{/if}
							</div>
						</div>

						<!-- Grid: Password & Confirm Password -->
						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-password">
									Password <span class="text-red-500">*</span>
								</label>
								<div class="relative">
									<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
										<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
										</svg>
									</div>
									<input
										id="reg-password"
										type={showPassword ? 'text' : 'password'}
										bind:value={password}
										placeholder="Create a strong password"
										required
										class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-10 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
									/>
									<button
										type="button"
										onclick={() => (showPassword = !showPassword)}
										class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
										aria-label={showPassword ? 'Hide password' : 'Show password'}
									>
										{#if showPassword}
											<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
											</svg>
										{:else}
											<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
												<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
											</svg>
										{/if}
									</button>
								</div>
							</div>

							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-confirm-password">
									Confirm Password <span class="text-red-500">*</span>
								</label>
								<div class="relative">
									<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
										<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
										</svg>
									</div>
									<input
										id="reg-confirm-password"
										type={showConfirmPassword ? 'text' : 'password'}
										bind:value={passwordConfirmation}
										placeholder="Re-enter your password"
										required
										class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-10 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
									/>
									<button
										type="button"
										onclick={() => (showConfirmPassword = !showConfirmPassword)}
										class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
										aria-label={showConfirmPassword ? 'Hide password' : 'Show password'}
									>
										{#if showConfirmPassword}
											<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
											</svg>
										{:else}
											<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
												<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
											</svg>
										{/if}
									</button>
								</div>
							</div>
						</div>
					</div>
				</section>

				<!-- ==========================================
				     2. NID / KYC Verification Section
				     ========================================== -->
				<section id="section-nid" class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
					<!-- Section Header -->
					<div class="flex items-start gap-3">
						<svg class="mt-0.5 h-5 w-5 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
						</svg>
						<div>
							<h2 class="text-base font-semibold text-gray-950 dark:text-white">NID / KYC Verification</h2>
							<p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
								Provide your National ID number and official document photos for KYC review.
							</p>
						</div>
					</div>

					<div class="my-4 border-b border-gray-100 dark:border-gray-800"></div>

					<div class="space-y-4">
						<!-- NID Number -->
						<div>
							<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-nid">
								NID Number <span class="text-red-500">*</span>
							</label>
							<div class="relative">
								<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
									<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
									</svg>
								</div>
								<input
									id="reg-nid"
									type="text"
									bind:value={nidNumber}
									placeholder="Enter your 10, 13, or 17-digit NID Number"
									required
									class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 font-mono text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
								/>
							</div>
						</div>

						<!-- Front & Back NID -->
						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<div>
								<span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
									NID Front Side <span class="text-red-500">*</span>
								</span>
								<div class="relative flex items-center">
									<input
										id="file-nid-front"
										type="file"
										accept="image/*"
										onchange={(e) => handleFileInput(e, 'front')}
										class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-200"
									/>
								</div>
								{#if frontPreview}
									<div class="mt-2 relative inline-block">
										<img src={frontPreview} alt="NID Front Preview" class="h-20 w-32 rounded-lg border border-gray-200 object-cover dark:border-gray-700" />
										<span class="absolute top-1 right-1 rounded-full bg-teal-600 p-0.5 text-white">
											<svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" />
											</svg>
										</span>
									</div>
								{/if}
								<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Clear color photo of the front side of your NID card.</p>
							</div>

							<div>
								<span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
									NID Back Side <span class="text-red-500">*</span>
								</span>
								<div class="relative flex items-center">
									<input
										id="file-nid-back"
										type="file"
										accept="image/*"
										onchange={(e) => handleFileInput(e, 'back')}
										class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-200"
									/>
								</div>
								{#if backPreview}
									<div class="mt-2 relative inline-block">
										<img src={backPreview} alt="NID Back Preview" class="h-20 w-32 rounded-lg border border-gray-200 object-cover dark:border-gray-700" />
										<span class="absolute top-1 right-1 rounded-full bg-teal-600 p-0.5 text-white">
											<svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" />
											</svg>
										</span>
									</div>
								{/if}
								<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Clear color photo of the back side of your NID card.</p>
							</div>
						</div>
					</div>
				</section>

				<!-- ==========================================
				     3. Live Camera Photo Section
				     ========================================== -->
				<section id="section-live-photo" class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
					<!-- Section Header -->
					<div class="flex items-start gap-3">
						<svg class="mt-0.5 h-5 w-5 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
						</svg>
						<div>
							<h2 class="text-base font-semibold text-gray-950 dark:text-white">Live Photo (Camera Capture Only)</h2>
							<p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
								Live selfie photo captured via your device camera. Gallery upload is restricted.
							</p>
						</div>
					</div>

					<div class="my-4 border-b border-gray-100 dark:border-gray-800"></div>

					<div>
						<span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
							Live Camera Photo <span class="text-red-500">*</span>
						</span>

						{#if livePhoto}
							<!-- Photo Captured View -->
							<div class="inline-block rounded-2xl border border-gray-200 p-3 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
								<div class="relative h-44 w-44 overflow-hidden rounded-xl border border-gray-200 bg-black dark:border-gray-700">
									<img src={livePhoto} alt="Live selfie" class="h-full w-full object-cover" />
									<div class="absolute top-2 right-2 rounded-full bg-teal-600 p-1 text-white shadow-md">
										<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" />
										</svg>
									</div>
								</div>
								<div class="mt-2 flex items-center justify-between gap-2">
									<span class="flex items-center gap-1 text-xs font-semibold text-teal-700 dark:text-teal-400">
										<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M1 8a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.664-.89l.812-1.22A2 2 0 0 1 8.07 3h3.86a2 2 0 0 1 1.664.89l.812 1.22A2 2 0 0 0 16.07 6H17a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8Zm13.5 3a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM10 14a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd" />
										</svg>
										Live Photo Captured
									</span>
									<button
										type="button"
										onclick={openCamera}
										class="text-xs font-medium text-gray-500 hover:text-teal-600 underline cursor-pointer"
									>
										Retake
									</button>
								</div>
							</div>
						{:else}
							<!-- Live Photo Dashed Placeholder Box -->
							<div class="rounded-2xl border-2 border-dashed border-gray-300 p-6 text-center transition hover:border-teal-500 dark:border-gray-700 dark:bg-gray-900/30">
								<div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full border border-teal-200 bg-teal-50 text-teal-600 shadow-xs dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-400">
									<svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
										<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
									</svg>
								</div>
								<h3 class="text-sm font-semibold text-gray-900 dark:text-white">Live Selfie Photo Required</h3>
								<p class="mx-auto mt-1 max-w-sm text-xs text-gray-500 dark:text-gray-400">
									Photo must be taken directly via device camera. Gallery and file uploads are strictly restricted for verification security.
								</p>
								<div class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-3">
									<button
										type="button"
										onclick={openCamera}
										class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:ring-4 focus:ring-teal-500/20 cursor-pointer"
									>
										<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M1 8a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.664-.89l.812-1.22A2 2 0 0 1 8.07 3h3.86a2 2 0 0 1 1.664.89l.812 1.22A2 2 0 0 0 16.07 6H17a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8Zm13.5 3a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM10 14a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd" />
										</svg>
										<span>Open Webcam & Take Photo</span>
									</button>
									<button
										type="button"
										onclick={() => (isQrModalOpen = true)}
										class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:border-teal-500 hover:text-teal-600 focus:ring-4 focus:ring-teal-500/10 cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:text-teal-400"
									>
										<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
										</svg>
										<span>Use Phone Camera (QR)</span>
									</button>
								</div>
							</div>
						{/if}
					</div>
				</section>

				<!-- ==========================================
				     4. Business Information Section
				     ========================================== -->
				<section id="section-business" class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
					<!-- Collapsible Header -->
					<div class="flex items-center justify-between cursor-pointer select-none" onclick={() => (businessCollapsed = !businessCollapsed)} role="button" tabindex="0" onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') businessCollapsed = !businessCollapsed; }}>
						<div class="flex items-start gap-3">
							<svg class="mt-0.5 h-5 w-5 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
							</svg>
							<div>
								<h2 class="text-base font-semibold text-gray-950 dark:text-white">Business Information</h2>
								<p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
									Provide your business details and trade license. All fields in this section are required.
								</p>
							</div>
						</div>
						<div class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
							<svg class="h-5 w-5 transition-transform duration-200 {businessCollapsed ? 'rotate-180' : ''}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
							</svg>
						</div>
					</div>

					{#if !businessCollapsed}
						<div class="my-4 border-b border-gray-100 dark:border-gray-800"></div>

						<div class="space-y-4 animate-fade-in">
							<!-- Grid: Business Name & Trade License Number -->
							<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
								<div>
									<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-business-name">
										Business / Company Name <span class="text-red-500">*</span>
									</label>
									<div class="relative">
										<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
											<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
											</svg>
										</div>
										<input
											id="reg-business-name"
											type="text"
											bind:value={businessName}
											placeholder="Enter your business or company name"
											required
											class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
										/>
									</div>
								</div>

								<div>
									<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-trade-license">
										Trade License Number <span class="text-red-500">*</span>
									</label>
									<div class="relative">
										<div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
											<svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
											</svg>
										</div>
										<input
											id="reg-trade-license"
											type="text"
											bind:value={tradeLicenseNumber}
											placeholder="Enter your trade license number"
											required
											class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 font-mono text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
										/>
									</div>
								</div>
							</div>

							<!-- Business Address -->
							<div>
								<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" for="reg-business-address">
									Business Address <span class="text-red-500">*</span>
								</label>
								<textarea
									id="reg-business-address"
									bind:value={businessAddress}
									rows="2"
									placeholder="Enter your business address"
									required
									class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-xs transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white resize-none"
								></textarea>
							</div>

							<!-- Trade License File Upload -->
							<div>
								<span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
									Trade License Document <span class="text-red-500">*</span>
								</span>
								<div class="relative flex items-center">
									<input
										id="file-trade-license"
										type="file"
										accept="image/*,.pdf"
										onchange={(e) => handleFileInput(e, 'trade')}
										class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-200"
									/>
								</div>
								{#if tradeLicenseFileName}
									<div class="mt-2 flex items-center gap-2 text-xs font-medium text-teal-600 dark:text-teal-400">
										<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
										</svg>
										<span>Selected file: {tradeLicenseFileName}</span>
									</div>
								{/if}
								<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF or image of your Trade License document (Max size 5MB).</p>
							</div>
						</div>
					{/if}
				</section>

				<!-- ==========================================
				     5. Terms and Agreement Section
				     ========================================== -->
				<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
					<label class="flex items-start gap-2.5 cursor-pointer select-none">
						<input
							type="checkbox"
							bind:checked={termsAccepted}
							required
							class="mt-1 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500 dark:border-gray-700 dark:bg-gray-800"
						/>
						<span class="text-sm text-gray-700 dark:text-gray-300">
							I agree to the <a href="/terms" target="_blank" class="font-medium text-teal-600 hover:text-teal-500 underline dark:text-teal-400">Terms &amp; Conditions</a> and <a href="/terms" target="_blank" class="font-medium text-teal-600 hover:text-teal-500 underline dark:text-teal-400">Privacy Policy</a> <span class="text-red-500">*</span>
						</span>
					</label>
				</section>

				<!-- Bottom Action Button (Filament Action Button Replica) -->
				<div>
					<button
						type="submit"
						disabled={submitting}
						id="btn-register"
						class="w-full rounded-lg border border-teal-600 bg-white py-2.5 px-4 text-sm font-semibold text-teal-600 shadow-xs transition hover:bg-teal-50 focus:outline-hidden focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-900 dark:text-teal-400 dark:hover:bg-gray-800"
					>
						{#if submitting}
							<span class="inline-flex items-center justify-center gap-2">
								<svg class="h-4 w-4 animate-spin text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
								</svg>
								Creating account…
							</span>
						{:else}
							Sign up
						{/if}
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- ==========================================
     Live Camera Modal
     ========================================== -->
{#if isCameraOpen}
	<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs animate-fade-in">
		<div class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900">
			<!-- Modal Header -->
			<div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
				<div class="flex items-center gap-2">
					<svg class="h-5 w-5 text-teal-600" viewBox="0 0 20 20" fill="currentColor">
						<path fill-rule="evenodd" d="M1 8a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.664-.89l.812-1.22A2 2 0 0 1 8.07 3h3.86a2 2 0 0 1 1.664.89l.812 1.22A2 2 0 0 0 16.07 6H17a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8Zm13.5 3a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM10 14a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd" />
					</svg>
					<h3 class="text-sm font-bold text-gray-900 dark:text-white">Live Photo Capture</h3>
				</div>
				<button
					type="button"
					onclick={stopCamera}
					aria-label="Close camera modal"
					class="rounded-lg p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
				>
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
					</svg>
				</button>
			</div>

			<!-- Modal Body -->
			<div class="p-5 space-y-4">
				{#if cameraError}
					<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
						<p class="font-bold flex items-center gap-1.5">
							<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
							</svg>
							Camera Access Error
						</p>
						<p class="mt-1">{cameraError}</p>
					</div>
				{:else}
					<div class="relative overflow-hidden rounded-xl border border-gray-200 bg-black aspect-video flex items-center justify-center">
						<!-- svelte-ignore a11y_media_has_caption -->
						<video
							bind:this={videoElement}
							autoplay
							playsinline
							muted
							class="h-full w-full object-cover -scale-x-100"
						></video>
						<!-- Face Guide Oval Overlay -->
						<div class="pointer-events-none absolute inset-0 flex items-center justify-center">
							<div class="h-48 w-36 rounded-full border-2 border-dashed border-teal-400/80 bg-transparent"></div>
						</div>
					</div>
					<p class="text-center text-xs text-gray-500 dark:text-gray-400">
						Position your face inside the oval and ensure good lighting before capturing.
					</p>
				{/if}

				<!-- Modal Actions -->
				<div class="flex items-center justify-end gap-3 pt-2">
					<button
						type="button"
						onclick={stopCamera}
						class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
					>
						Cancel
					</button>
					{#if !cameraError}
						<button
							type="button"
							onclick={capturePhoto}
							class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700 cursor-pointer"
						>
							<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M1 8a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.664-.89l.812-1.22A2 2 0 0 1 8.07 3h3.86a2 2 0 0 1 1.664.89l.812 1.22A2 2 0 0 0 16.07 6H17a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8Zm13.5 3a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM10 14a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" clip-rule="evenodd" />
							</svg>
							<span>Capture Photo</span>
						</button>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/if}

<!-- Phone QR Modal -->
{#if isQrModalOpen}
	<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs animate-fade-in">
		<div class="w-full max-w-sm overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl text-center dark:border-gray-800 dark:bg-gray-900">
			<div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-600 dark:bg-teal-950/50 dark:text-teal-400">
				<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
				</svg>
			</div>
			<h3 class="text-sm font-bold text-gray-900 dark:text-white">Scan with Your Phone</h3>
			<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
				Scan this code with your mobile camera to take a live photo directly on your phone.
			</p>
			<div class="my-4 flex justify-center">
				<div class="rounded-xl border border-gray-200 bg-white p-3 shadow-inner dark:border-gray-700">
					<!-- QR representation -->
					<div class="flex h-40 w-40 items-center justify-center rounded-lg bg-gray-50 font-mono text-[10px] text-gray-400 dark:bg-gray-800">
						[QR Code Available On HTTPS / Production Host]
					</div>
				</div>
			</div>
			<button
				type="button"
				onclick={() => (isQrModalOpen = false)}
				class="w-full rounded-xl border border-gray-300 bg-white py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
			>
				Close
			</button>
		</div>
	</div>
{/if}
