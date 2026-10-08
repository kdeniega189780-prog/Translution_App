<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class VehicleReady extends Notification
{
    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['booking_id' => $this->booking->id, 'message' => 'Your '.$this->booking->vehicle->plate_number.' is ready. Your invoice is available.'];
    }
}
