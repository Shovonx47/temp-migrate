/**
 * API client for the Laravel backend.
 * Uses cookie-based Sanctum SPA auth by default.
 */

const BASE_URL = import.meta.env.VITE_API_URL ?? '';

export class ApiError extends Error {
	constructor(
		public status: number,
		public data: Record<string, unknown>
	) {
		super((data.message as string) ?? `HTTP ${status}`);
	}
}

async function request<T>(
	method: string,
	path: string,
	body?: unknown,
	signal?: AbortSignal
): Promise<T> {
	// Fetch CSRF cookie before any state-mutating request
	if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
		await fetch(`${BASE_URL}/sanctum/csrf-cookie`, {
			credentials: 'include'
		});
	}

	const headers: Record<string, string> = {
		Accept: 'application/json',
		'X-Requested-With': 'XMLHttpRequest'
	};

	if (body !== undefined) {
		headers['Content-Type'] = 'application/json';
	}

	// Include XSRF token from cookie
	const xsrfToken = getCookie('XSRF-TOKEN');
	if (xsrfToken) {
		headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
	}

	const res = await fetch(`${BASE_URL}${path}`, {
		method,
		headers,
		body: body !== undefined ? JSON.stringify(body) : undefined,
		credentials: 'include',
		signal
	});

	if (!res.ok) {
		let data: Record<string, unknown> = {};
		try {
			data = await res.json();
		} catch {
			// ignore parse errors
		}
		throw new ApiError(res.status, data);
	}

	if (res.status === 204) return undefined as T;

	return res.json() as Promise<T>;
}

function getCookie(name: string): string | null {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);
	if (parts.length === 2) return parts.pop()?.split(';').shift() ?? null;
	return null;
}

export const api = {
	get: <T>(path: string, signal?: AbortSignal) => request<T>('GET', path, undefined, signal),
	post: <T>(path: string, body?: unknown) => request<T>('POST', path, body),
	put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body),
	patch: <T>(path: string, body?: unknown) => request<T>('PATCH', path, body),
	delete: <T>(path: string) => request<T>('DELETE', path)
};
