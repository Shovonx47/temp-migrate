<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class LivePhotoCapture extends Field
{
    protected string $view = 'filament.forms.components.live-photo-capture';

    protected string $directory = 'live_photos';

    protected string $disk = 'private';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);
    }

    public function directory(string $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }
}
