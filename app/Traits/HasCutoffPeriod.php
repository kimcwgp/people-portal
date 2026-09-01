<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasCutoffPeriod
{
    private function getCurrentCutoffPeriod($date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        $day = $date->day;

        if ($day >= 25) {
            $start = $date->copy()->day(25)->startOfDay();
            $end = $date->copy()->addMonth()->day(9)->endOfDay();
        } elseif ($day >= 10) {
            $start = $date->copy()->day(10)->startOfDay();
            $end = $date->copy()->day(24)->endOfDay();
        } else {
            $start = $date->copy()->subMonth()->day(25)->startOfDay();
            $end = $date->copy()->day(9)->endOfDay();
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    public function getCutoffPeriods()
    {
        $periods = [];
        $currentDate = now();

        $monthsToCheck = [
            $currentDate->copy(),
            $currentDate->copy()->subMonth()
        ];

        foreach ($monthsToCheck as $date) {
            $firstStart = $date->copy()->day(10)->startOfDay();
            $firstEnd = $date->copy()->day(24)->endOfDay();

            if (!$firstStart->isFuture()) {
                $periods[] = [
                    'id' => $firstStart->format('Y-m-d') . '_' . $firstEnd->format('Y-m-d'),
                    'label' => $firstStart->format('M d, Y') . ' - ' . $firstEnd->format('M d, Y'),
                    'start_date' => $firstStart->format('Y-m-d'),
                    'end_date' => $firstEnd->format('Y-m-d'),
                    'is_current' => $this->isCurrentPeriod($firstStart, $firstEnd),
                    'type' => 'first'
                ];
            }

            $secondStart = $date->copy()->day(25)->startOfDay();
            $secondEnd = $date->copy()->addMonth()->day(9)->endOfDay();

            if (!$secondStart->isFuture()) {
                $periods[] = [
                    'id' => $secondStart->format('Y-m-d') . '_' . $secondEnd->format('Y-m-d'),
                    'label' => $secondStart->format('M d, Y') . ' - ' . $secondEnd->format('M d, Y'),
                    'start_date' => $secondStart->format('Y-m-d'),
                    'end_date' => $secondEnd->format('Y-m-d'),
                    'is_current' => $this->isCurrentPeriod($secondStart, $secondEnd),
                    'type' => 'second'
                ];
            }
        }

        usort($periods, function($a, $b) {
            return strtotime($b['start_date']) - strtotime($a['start_date']);
        });

        return response()->json([
            'success' => true,
            'data' => $periods
        ]);
    }

    private function isCurrentPeriod($start, $end)
    {
        $current = $this->getCurrentCutoffPeriod();
        return $start->isSameDay($current['start']) && $end->isSameDay($current['end']);
    }
}
