import tailwindcss from '@tailwindcss/vite';
import adapter from '@sveltejs/adapter-static';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [
		tailwindcss(),
		sveltekit({
			compilerOptions: {
				// Force runes mode for the project, except for libraries. Can be removed in svelte 6.
				runes: ({ filename }) =>
					filename.split(/[/\\]/).includes('node_modules') ? undefined : true
			},
			adapter: adapter({
				fallback: 'index.html',
				strict: false
			})
		})
	],
	server: {
		port: 5173,
		proxy: {
			'/api': {
				target: 'http://127.0.0.1:8000',
				changeOrigin: true
			},
			'/sanctum': {
				target: 'http://127.0.0.1:8000',
				changeOrigin: true
			},
			'/superman': {
				target: 'http://127.0.0.1:8000',
				changeOrigin: true
			},
			'/filament': {
				target: 'http://127.0.0.1:8000',
				changeOrigin: true
			},
			'/livewire': {
				target: 'http://127.0.0.1:8000',
				changeOrigin: true
			}
		}
	}
});
