<?php

namespace App\Notifications;

use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FleetDocumentExpiringSoon extends Notification
{
    use Queueable;

    public function __construct(
        public VehicleDocument $document,
        public int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vehicle = $this->document->vehicle;

        return [
            'type' => 'fleet_document_expiring',
            'title' => 'Vehicle Document Expiring Soon',
            'message' => sprintf(
                'Vehicle %s %s expires in %d day(s).',
                $vehicle?->registration_number ?? '#'.$this->document->vehicle_id,
                str_replace('_', ' ', $this->document->document_type),
                $this->daysRemaining
            ),
            'vehicle_id' => $this->document->vehicle_id,
            'document_id' => $this->document->id,
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
