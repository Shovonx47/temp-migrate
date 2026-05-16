@php
    use App\Services\Sms\OperatorRateResolver;

    $resolver = OperatorRateResolver::forUser(auth()->user());
    $nonMasking = $resolver->ratesForUser(false);
    $masking = $resolver->ratesForUser(true);

    $format = function (array $rates): string {
        if ($rates === []) {
            return '—';
        }
        $min = min($rates);
        $max = max($rates);

        return $min === $max
            ? '৳'.number_format($min, 2)
            : '৳'.number_format($min, 2).'–৳'.number_format($max, 2);
    };
@endphp

<div
    x-data="smsStats"
    class="flex flex-wrap items-center gap-x-5 gap-y-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-600 dark:text-gray-400"
>
    <div class="flex items-center gap-1.5">
        <span class="font-medium text-gray-700 dark:text-gray-300">Remaining:</span>
        <span><span x-text="remaining"></span>/<span x-text="total"></span></span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="font-medium text-gray-700 dark:text-gray-300">Parts:</span>
        <span x-text="parts"></span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="font-medium text-gray-700 dark:text-gray-300">Encoding:</span>
        <span x-text="encoding"></span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="font-medium text-gray-700 dark:text-gray-300">Non-masking:</span>
        <span>{{ $format($nonMasking) }}/part</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="font-medium text-gray-700 dark:text-gray-300">Masking:</span>
        <span>{{ $format($masking) }}/part</span>
    </div>
</div>
