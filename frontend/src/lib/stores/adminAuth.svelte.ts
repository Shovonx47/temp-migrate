/**
 * Superman Admin state store using Svelte 5 runes.
 */

export interface AdminUser {
	id: number;
	name: string;
	email: string;
	type: string;
}

export interface TopbarStat {
	title: string;
	short_title: string;
	value: string;
	url: string;
	theme: string;
	icon: string;
	bg: string;
	border: string;
}

interface AdminState {
	user: AdminUser | null;
	loading: boolean;
	initialized: boolean;
	stats: Record<string, TopbarStat>;
}

function createAdminStore() {
	let state = $state<AdminState>({
		user: {
			id: 1,
			name: 'Superman',
			email: 'superman@example.com',
			type: 'superman'
		},
		loading: false,
		initialized: true,
		stats: {
			kyc_pending: {
				title: 'KYC Pending',
				short_title: 'KYC',
				value: '1',
				url: '/superman/kyc/requests',
				theme: 'purple',
				icon: 'identification',
				bg: '#7c3aed',
				border: '#6d28d9'
			},
			today_deposit: {
				title: 'Today Deposit',
				short_title: 'Deposit',
				value: '৳0.00',
				url: '/superman/finance/deposits',
				theme: 'green',
				icon: 'banknotes',
				bg: '#059669',
				border: '#047857'
			},
			today_sms: {
				title: 'Today SMS Sent',
				short_title: 'SMS',
				value: '0',
				url: '/superman/system-logs/sms-logs',
				theme: 'blue',
				icon: 'chat-bubble',
				bg: '#0284c7',
				border: '#0369a1'
			},
			today_profit: {
				title: 'Today Profit',
				short_title: 'Profit',
				value: '৳0.00',
				url: '/superman/profit/global',
				theme: 'orange',
				icon: 'trending-up',
				bg: '#d97706',
				border: '#b45309'
			},
			low_balance: {
				title: 'Low Balance',
				short_title: 'Low Bal',
				value: '4',
				url: '/superman/users/customers?filter=low_balance',
				theme: 'red',
				icon: 'exclamation-triangle',
				bg: '#e11d48',
				border: '#be123c'
			},
			open_tickets: {
				title: 'Open Tickets',
				short_title: 'Tickets',
				value: '0',
				url: '/superman/sms/inquiry',
				theme: 'cyan',
				icon: 'ticket',
				bg: '#0891b2',
				border: '#0e7490'
			},
			ip_whitelist: {
				title: 'IP Whitelist',
				short_title: 'IP Whitelist',
				value: '0',
				url: '/superman/approval-center/ip-whitelists',
				theme: 'indigo',
				icon: 'shield-check',
				bg: '#4f46e5',
				border: '#4338ca'
			}
		}
	});

	return {
		get user() {
			return state.user;
		},
		get loading() {
			return state.loading;
		},
		get initialized() {
			return state.initialized;
		},
		get isLoggedIn() {
			return state.user !== null;
		},
		get stats() {
			return state.stats;
		},

		async init() {
			// Check if stored in localStorage or session
			if (typeof window !== 'undefined') {
				const saved = localStorage.getItem('sms_superman_auth');
				if (saved) {
					try {
						state.user = JSON.parse(saved);
					} catch {
						// ignore
					}
				}
			}
			state.initialized = true;
		},

		async login(email: string, password: string, remember: boolean = true) {
			state.loading = true;
			try {
				// Superman validation
				if (email.trim().toLowerCase() === 'superman@example.com' && (password === 'superman' || password === 'password')) {
					const admin = {
						id: 1,
						name: 'Superman',
						email: 'superman@example.com',
						type: 'superman'
					};
					state.user = admin;
					if (typeof window !== 'undefined') {
						localStorage.setItem('sms_superman_auth', JSON.stringify(admin));
					}
					return { success: true };
				}

				return { success: false, message: 'Invalid administrator credentials.' };
			} finally {
				state.loading = false;
			}
		},

		async logout() {
			state.user = null;
			if (typeof window !== 'undefined') {
				localStorage.removeItem('sms_superman_auth');
			}
		}
	};
}

export const adminAuth = createAdminStore();
