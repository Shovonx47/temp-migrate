<?php

use App\Support\RecipientFileParser;

beforeEach(function () {
    $this->parser = new RecipientFileParser;
    $this->tmpPath = tempnam(sys_get_temp_dir(), 'parser-test-').'.csv';
});

afterEach(function () {
    @unlink($this->tmpPath);
});

function writeCsv(string $path, array $rows): void
{
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row, escape: '\\');
    }
    fclose($handle);
}

it('detects header row and returns headers + sample + total', function () {
    writeCsv($this->tmpPath, [
        ['phone', 'name'],
        ['01711000001', 'Rahim'],
        ['01711000002', 'Karim'],
        ['01711000003', 'Sumi'],
    ]);

    $preview = $this->parser->peek($this->tmpPath);

    expect($preview['has_header_row'])->toBeTrue();
    expect($preview['headers'])->toBe(['phone', 'name']);
    expect($preview['total_rows'])->toBe(3);
    expect($preview['sample'])->toHaveCount(3);
    expect($preview['sample'][0])->toBe(['phone' => '01711000001', 'name' => 'Rahim']);
});

it('treats first row as data when it already looks like a phone number', function () {
    writeCsv($this->tmpPath, [
        ['01711000001', 'Rahim'],
        ['01711000002', 'Karim'],
    ]);

    $preview = $this->parser->peek($this->tmpPath);

    expect($preview['has_header_row'])->toBeFalse();
    expect($preview['headers'])->toBe(['Column 1', 'Column 2']);
    expect($preview['total_rows'])->toBe(2);
    expect($preview['sample'][0])->toBe(['Column 1' => '01711000001', 'Column 2' => 'Rahim']);
});

it('limits sample size while still counting all rows', function () {
    $rows = [['phone', 'name']];
    for ($i = 0; $i < 50; $i++) {
        $rows[] = ['0171100'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'User '.$i];
    }
    writeCsv($this->tmpPath, $rows);

    $preview = $this->parser->peek($this->tmpPath, sampleSize: 5);

    expect($preview['total_rows'])->toBe(50);
    expect($preview['sample'])->toHaveCount(5);
});

it('streams all data rows as associative arrays', function () {
    writeCsv($this->tmpPath, [
        ['phone', 'name'],
        ['01711000001', 'Rahim'],
        ['01711000002', 'Karim'],
    ]);

    $rows = iterator_to_array($this->parser->stream($this->tmpPath), preserve_keys: false);

    expect($rows)->toBe([
        ['phone' => '01711000001', 'name' => 'Rahim'],
        ['phone' => '01711000002', 'name' => 'Karim'],
    ]);
});

it('pads short rows with empty strings against header width', function () {
    writeCsv($this->tmpPath, [
        ['phone', 'name', 'balance'],
        ['01711000001', 'Rahim'],
    ]);

    $rows = iterator_to_array($this->parser->stream($this->tmpPath), preserve_keys: false);

    expect($rows[0])->toBe(['phone' => '01711000001', 'name' => 'Rahim', 'balance' => '']);
});

it('throws for unsupported file extensions', function () {
    $bad = tempnam(sys_get_temp_dir(), 'parser-test-').'.pdf';
    file_put_contents($bad, 'noop');

    expect(fn () => $this->parser->peek($bad))
        ->toThrow(InvalidArgumentException::class);

    @unlink($bad);
});
