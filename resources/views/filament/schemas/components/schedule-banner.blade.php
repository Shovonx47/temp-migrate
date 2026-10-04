<div class="rounded-xl border border-sky-200/80 bg-sky-50/70 p-3.5 text-sky-950 dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-200">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 shrink-0 rounded-lg bg-sky-500/10 p-1.5 text-sky-600 dark:text-sky-400">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div class="text-xs space-y-1">
            <p class="font-semibold text-sm text-sky-900 dark:text-sky-100">Scheduled Dispatch</p>
            <p class="text-sky-800/90 dark:text-sky-300">
                Your SMS will be safely held and will only appear in the live queue when dispatched at your selected date and timeline.
            </p>
            <div class="pt-1 flex items-center gap-2 text-[11px] text-sky-700 dark:text-sky-400 font-mono">
                <span class="inline-flex items-center gap-1 rounded bg-sky-100 dark:bg-sky-900/60 px-1.5 py-0.5 font-medium">
                    🌐 {{ config('app.timezone') }}
                </span>
                <span>Current: <strong>{{ now()->format('d M Y, h:i A') }}</strong></span>
            </div>
        </div>
    </div>
</div>
