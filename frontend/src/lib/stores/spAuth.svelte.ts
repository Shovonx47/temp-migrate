/**
 * S P Reseller portal state store using Svelte 5 runes.
 */

export interface SpUser {
	id: number;
	name: string;
	email: string;
	balance: number;
	type: string;
}

interface SpState {
	user: SpUser | null;
	loading: boolean;
	initialized: boolean;
}

function createSpStore() {
	let state = $state<SpState>({
		user: {
			id: 2,
			name: 'Partner Reseller',
			email: 'sp@example.com',
			balance: 50000.0,
			type: 'sp'
		},
		loading: false,
		initialized: true
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
		get balance() {
			return state.user?.balance ?? 0;
		},

		async init() {
			if (typeof window !== 'undefined') {
				const saved = localStorage.getItem('sms_sp_auth');
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

		async login(email: string, password: string) {
			state.loading = true;
			try {
				if (email.includes('sp') || email.includes('reseller') || password.length >= 4) {
					const sp = {
						id: 2,
						name: 'WhiteInfo Reseller',
						email: email,
						balance: 75000.0,
						type: 'sp'
					};
					state.user = sp;
					if (typeof window !== 'undefined') {
						localStorage.setItem('sms_sp_auth', JSON.stringify(sp));
					}
					return { success: true };
				}
				return { success: false, message: 'Invalid Reseller credentials.' };
			} finally {
				state.loading = false;
			}
		},

		async logout() {
			state.user = null;
			if (typeof window !== 'undefined') {
				localStorage.removeItem('sms_sp_auth');
			}
		},

		updateBalance(newBalance: number) {
			if (state.user) {
				state.user = { ...state.user, balance: newBalance };
			}
		}
	};
}

export const spAuth = createSpStore();
