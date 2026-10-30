<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $court = Court::where('id', $data['court_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);

            // Validar que no se superpongan reservas
            $isOverlapping = Booking::where('court_id', $court->id)
                ->where('status', '!=', 'canceled')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($isOverlapping) {
                throw new Exception("El horario seleccionado para {$court->name} ya está ocupado.");
            }

            return Booking::create([
                'court_id'        => $court->id,
                'user_id'         => $data['user_id'] ?? null,
                'customer_name'   => $data['customer_name'],
                'customer_phone'  => $data['customer_phone'],
                'customer_email'  => $data['customer_email'] ?? null,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'total_price'     => $data['total_price'],
                'deposit_paid'    => $data['deposit_paid'] ?? 0,
                'status'          => $data['status'] ?? 'pending',
                'payment_method'  => $data['payment_method'] ?? 'cash',
                'notes'           => $data['notes'] ?? null,
            ]);
        });
    }
}