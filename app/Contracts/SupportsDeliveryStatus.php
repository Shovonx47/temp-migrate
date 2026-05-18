<?php

namespace App\Contracts;

use App\Services\Sms\Data\DeliveryStatusResponse;

interface SupportsDeliveryStatus
{
    /**
     * Poll the gateway for the delivery status of a previously sent message.
     *
     * Returns a DeliveryStatusResponse with:
     *   - DeliveryOutcome::Delivered  – the carrier confirmed handset delivery
     *   - DeliveryOutcome::Failed     – the carrier could not deliver the message
     *   - DeliveryOutcome::InTransit  – the SMSC/carrier has not yet produced a final status;
     *                                   the caller should retry after a delay
     */
    public function fetchDeliveryStatus(string $gatewayMessageId): DeliveryStatusResponse;
}
