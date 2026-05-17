<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Support\CampaignDispatcher;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    public ?string $send_strategy = 'now';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->send_strategy = $data['send_strategy'] ?? 'now';

        $data['user_id'] = auth()->id();
        // Start in Draft; we'll transition once the snapshot is built.
        $data['status'] = CampaignStatus::Draft;

        if ($this->send_strategy !== 'schedule') {
            $data['scheduled_at'] = null;
        }

        unset($data['send_strategy']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Campaign $campaign */
        $campaign = static::getModel()::create($data);

        $dispatcher = app(CampaignDispatcher::class);

        $dispatcher->snapshotRecipients($campaign);

        if ($this->send_strategy === 'schedule' && $campaign->scheduled_at) {
            $dispatcher->schedule($campaign, Carbon::parse($campaign->scheduled_at));
        } else {
            $dispatcher->dispatchNow($campaign);
        }

        return $campaign->refresh();
    }
}
