@php
    use App\Models\Contact;
    use App\Models\Sender;

    $source = $get('source') ?? 'manual';
    $body = $get('body') ?? '';
    $bodyVariables = $get('body_variables') ?? [];
    $senderId = $get('sender_id');
    $mapping = $get('mapping') ?? [];
    $phoneCol = $mapping['phone'] ?? null;
    $varMap = $mapping['variables'] ?? [];

    $sender = $senderId ? Sender::find($senderId) : null;

    $recipientCount = 0;
    $sampleRow = [];
    $sampleHeaders = [];

    if ($source === 'manual') {
        $recipients = $get('recipients') ?? [];
        $recipientCount = count($recipients);
        $first = $recipients[0] ?? null;
        if ($first) {
            $sampleHeaders = ['Phone', ...array_map('ucfirst', $bodyVariables)];
            $row = [$first['phone'] ?? ''];
            foreach ($bodyVariables as $v) {
                $row[] = $first['values'][$v] ?? '';
            }
            $sampleRow = $row;
        }
    } elseif ($source === 'upload') {
        $headers = $get('file_headers') ?? [];
        $sample = $get('file_sample') ?? [];
        $recipientCount = (int) ($get('file_total_rows') ?? 0);
        if ($sample && $headers) {
            $first = $sample[0] ?? [];
            $sampleHeaders = ['Phone', ...array_map('ucfirst', $bodyVariables)];
            $row = [$phoneCol ? ($first[$phoneCol] ?? '—') : '—'];
            foreach ($bodyVariables as $v) {
                $col = $varMap[$v] ?? null;
                $row[] = $col ? ($first[$col] ?? '') : '';
            }
            $sampleRow = $row;
        }
    } elseif ($source === 'group') {
        $groupIds = $get('groups') ?? [];
        $contactsQuery = Contact::query()
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds));
        $recipientCount = $contactsQuery->count();
        $firstContact = $contactsQuery->first();
        if ($firstContact) {
            $sampleHeaders = ['Phone'];
            $sampleRow = [(string) $firstContact->phone];
        }
    }

    // Render message body with first row's values (manual/upload only — group sends body as-is).
    $renderedBody = $body;
    if ($source !== 'group') {
        foreach ($bodyVariables as $i => $v) {
            $value = $sampleRow[$i + 1] ?? '';
            $renderedBody = str_replace("{{$v}}", $value !== '' ? $value : "{{$v}}", $renderedBody);
        }
    }

    $sourceLabel = match ($source) {
        'manual' => 'Manual entry',
        'upload' => 'File upload',
        'group' => 'Group',
        default => '—',
    };

    // SMS stats for the (rendered) message body.
    $statsBody = $renderedBody !== '' ? $renderedBody : $body;
    $gsmBasic = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    $gsmExtended = "\f^{}\\[~]|€";

    $isUnicode = false;
    $gsmCount = 0;
    foreach (mb_str_split($statsBody) as $ch) {
        if (mb_strpos($gsmExtended, $ch) !== false) {
            $gsmCount += 2;
        } elseif (mb_strpos($gsmBasic, $ch) !== false) {
            $gsmCount += 1;
        } else {
            $isUnicode = true;
            break;
        }
    }

    $encoding = $isUnicode ? 'Unicode' : 'GSM-7';
    $singleLimit = $isUnicode ? 70 : 160;
    $concatLimit = $isUnicode ? 67 : 153;
    $msgLength = $isUnicode ? mb_strlen($statsBody) : $gsmCount;

    if ($msgLength === 0) {
        $partsPerMessage = 0;
        $perPartTotal = $singleLimit;
        $remainingInPart = $singleLimit;
    } elseif ($msgLength <= $singleLimit) {
        $partsPerMessage = 1;
        $perPartTotal = $singleLimit;
        $remainingInPart = $singleLimit - $msgLength;
    } else {
        $partsPerMessage = (int) ceil($msgLength / $concatLimit);
        $perPartTotal = $concatLimit;
        $remainingInPart = ($partsPerMessage * $concatLimit) - $msgLength;
    }

    $totalSms = $partsPerMessage * $recipientCount;
@endphp

<div class="space-y-4">
    {{-- Summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-3 bg-white dark:bg-gray-950">
            <p class="text-xs text-gray-500 dark:text-gray-400">Sender</p>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">
                {{ $sender?->sender_id ?? '—' }}
            </p>
        </div>
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-3 bg-white dark:bg-gray-950">
            <p class="text-xs text-gray-500 dark:text-gray-400">Source</p>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $sourceLabel }}</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-3 bg-white dark:bg-gray-950">
            <p class="text-xs text-gray-500 dark:text-gray-400">Recipients</p>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $recipientCount }}</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-3 bg-white dark:bg-gray-950">
            <p class="text-xs text-gray-500 dark:text-gray-400">Encoding</p>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $encoding }}</p>
        </div>
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg px-4 py-3 bg-white dark:bg-gray-950">
            <p class="text-xs text-gray-500 dark:text-gray-400">Parts / message</p>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{{ $partsPerMessage }}</p>
        </div>
        <div
            class="border border-primary-200 dark:border-primary-900 rounded-lg px-4 py-3 bg-primary-50 dark:bg-primary-950">
            <p class="text-xs text-primary-700 dark:text-primary-300">Total SMS</p>
            <p class="text-sm font-semibold text-primary-900 dark:text-primary-100 mt-1">{{ $totalSms }}</p>
        </div>
    </div>

    {{-- Template --}}
    <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Message Template</p>
        </div>
        <div class="px-4 py-3 bg-white dark:bg-gray-950">
            <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $body ?: '(empty)' }}</p>
        </div>
    </div>

    {{-- Preview rendered with first recipient --}}
    @if($sampleRow)
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    Preview (first recipient)
                </p>
            </div>
            <div class="px-4 py-3 bg-white dark:bg-gray-950 space-y-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">To</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $sampleRow[0] }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Message</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $renderedBody }}</p>
                </div>
                <div
                    class="flex flex-wrap items-center gap-x-5 gap-y-1 pt-2 border-t border-gray-100 dark:border-gray-900 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Remaining:</span>
                        <span>{{ $remainingInPart }}/{{ $perPartTotal }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Parts:</span>
                        <span>{{ $partsPerMessage }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Encoding:</span>
                        <span>{{ $encoding }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Variable mappings list (upload only) --}}
    @if($bodyVariables && $source === 'upload')
        <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Variable Mappings</p>
            </div>
            <div class="px-4 py-3 bg-white dark:bg-gray-950 space-y-1">
                @foreach($bodyVariables as $v)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-primary-600 dark:text-primary-400 font-medium">{{ '{'.$v.'}' }}</span>
                        <span class="text-gray-400 dark:text-gray-600">→</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $varMap[$v] ?? '—' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
