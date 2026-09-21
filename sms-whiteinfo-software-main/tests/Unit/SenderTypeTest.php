<?php

use App\Enums\SenderType;

test('sender type labels are always masking or non-masking', function () {
    expect(SenderType::Numeric->getLabel())->toBe('Non-masking')
        ->and(SenderType::AlphaNumeric->getLabel())->toBe('Masking')
        ->and(SenderType::Shortcode->getLabel())->toBe('Masking');
});

test('sender type masking category options only include masking and non-masking', function () {
    expect(SenderType::maskingCategoryOptions())->toBe([
        'masking' => 'Masking',
        'non-masking' => 'Non-masking',
    ]);
});
