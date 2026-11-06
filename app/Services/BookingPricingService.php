<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Court;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class BookingPricingService
{
    public function quote(Court $court, Club $club, CarbonInterface $start, CarbonInterface $end): string
    {
        if ($court->club_id !== $club->id || $end->lte($start)) {
            throw new InvalidArgumentException('Invalid court or booking interval.');
        }
        $cursor = CarbonImmutable::instance($start);
        $weightedCents = 0;
        while ($cursor->lt($end)) {
            $rate = $court->default_hourly_rate;
            foreach ($club->hourly_rates ?? [] as $band) {
                if ($cursor->format('H:i') >= $band['from'] && $cursor->format('H:i') < $band['until']) {
                    $rate = $band['rate'];
                    break;
                }
            }
            $weightedCents += (int) round((float) $rate * 100);
            $cursor = $cursor->addMinute();
        }
        $cents = intdiv($weightedCents + 30, 60);

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
