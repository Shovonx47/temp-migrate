/**
 * Global authentication and user state store using Svelte 5 runes.
 */

import { api, ApiError } from '../api.js';

export interface User {
	id: number;
	name: string;
	email: string;
	phone?: string;
	balance: string;
	status: string;
	kyc_status: string;
	created_at: string;
}

interface AuthState {
	user: User | null;
	loading: boolean;
	initialized: boolean;
}

function createAuthStore() {
	let state = $state<AuthState>({
		user: null,
		loading: false,
		initialized: false
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
			return parseFloat(state.user?.balance ?? '0');
		},

		async init() {
			if (state.initialized) return;
			state.loading = true;
			try {
				const data = await api.get<{ data: User }>('/api/v1/customer/me');
				state.user = data.data;
			} catch (e) {
				if (e instanceof ApiError && e.status === 401) {
					state.user = null;
				}
			} finally {
				state.loading = false;
				state.initialized = true;
			}
		},

		async login(email: string, password: string) {
			state.loading = true;
			try {
				await api.post('/customer/login', { email, password });
				const data = await api.get<{ data: User }>('/api/v1/customer/me');
				state.user = data.data;
				return { success: true };
			} catch (e) {
				state.user = null;
				if (e instanceof ApiError) {
					return { success: false, message: (e.data.message as string) ?? 'Login failed' };
				}
				return { success: false, message: 'Network error. Please try again.' };
			} finally {
				state.loading = false;
			}
		},

		async logout() {
			try {
				await api.post('/customer/logout');
			} catch {
				// ignore
			} finally {
				state.user = null;
				state.initialized = false;
			}
		},

		updateBalance(newBalance: string) {
			if (state.user) {
				state.user = { ...state.user, balance: newBalance };
			}
		}
	};
}

export const auth = createAuthStore();
