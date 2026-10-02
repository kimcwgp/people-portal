<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Philippine regular and special non-working holidays, plus a few company
 * dates, for the current year and the next one.
 *
 * 'partners' is the client-facing calendar (days clients observe, so coverage
 * is reduced); 'people' is the internal calendar (days staff are off).
 * Dates that move year to year -- Holy Week, Eid, National Heroes' Day -- are
 * listed per year rather than computed.
 */
class HolidaySeeder extends Seeder
{
    /** Fixed-date holidays that repeat every year. [month, day, name, type, calendars] */
    private const ANNUAL = [
        [1,  1,  "New Year's Day",              'regular',             ['partners', 'people']],
        [2,  25, 'EDSA People Power Revolution','special_non_working', ['people']],
        [4,  9,  'Araw ng Kagitingan',          'regular',             ['people']],
        [5,  1,  'Labor Day',                   'regular',             ['partners', 'people']],
        [6,  12, 'Independence Day',            'regular',             ['people']],
        [8,  21, 'Ninoy Aquino Day',            'special_non_working', ['people']],
        [11, 1,  "All Saints' Day",             'special_non_working', ['people']],
        [11, 2,  "All Souls' Day",              'special_non_working', ['people']],
        [11, 30, 'Bonifacio Day',               'regular',             ['people']],
        [12, 8,  'Feast of the Immaculate Conception', 'special_non_working', ['people']],
        [12, 24, 'Christmas Eve',               'special_non_working', ['partners', 'people']],
        [12, 25, 'Christmas Day',               'regular',             ['partners', 'people']],
        [12, 30, 'Rizal Day',                   'regular',             ['people']],
        [12, 31, "New Year's Eve",              'special_non_working', ['partners', 'people']],
    ];

    /** Movable feasts, by year. [Y-m-d, name, type, calendars] */
    private const MOVABLE = [
        2026 => [
            ['2026-04-02', 'Maundy Thursday',      'regular',             ['partners', 'people']],
            ['2026-04-03', 'Good Friday',          'regular',             ['partners', 'people']],
            ['2026-04-04', 'Black Saturday',       'special_non_working', ['people']],
            ['2026-03-20', 'Eid al-Fitr',          'regular',             ['people']],
            ['2026-05-27', 'Eid al-Adha',          'regular',             ['people']],
            ['2026-08-31', "National Heroes' Day", 'regular',             ['people']],
        ],
        2027 => [
            ['2027-03-25', 'Maundy Thursday',      'regular',             ['partners', 'people']],
            ['2027-03-26', 'Good Friday',          'regular',             ['partners', 'people']],
            ['2027-03-27', 'Black Saturday',       'special_non_working', ['people']],
            ['2027-03-10', 'Eid al-Fitr',          'regular',             ['people']],
            ['2027-05-17', 'Eid al-Adha',          'regular',             ['people']],
            ['2027-08-30', "National Heroes' Day", 'regular',             ['people']],
        ],
    ];

    /** Company-declared days off. [month, day, name, calendars] */
    private const COMPANY = [
        [3,  14, 'Quarterly Team Building',      ['people']],
        [6,  26, 'Mid-Year Kickoff',             ['people']],
        [7,  10, 'Partners Summit',              ['partners']],
        [9,  15, 'Company Foundation Day',       ['people']],
        [9,  26, 'Partners Appreciation Day',    ['partners']],
        [9,  30, 'Q3 Town Hall',                 ['people']],
        [10, 15, 'Partners Quarterly Shutdown',  ['partners']],
        [10, 31, 'Company Anniversary',          ['people']],
        [12, 26, 'Company Holiday Break',        ['partners', 'people']],
    ];

    public function run(): void
    {
        $author = User::where('email', 'hr@peopleportal.test')->first()
            ?? User::where('email', 'admin@peopleportal.test')->first();

        $years = [Carbon::today()->year, Carbon::today()->year + 1];
        $created = 0;

        foreach ($years as $year) {
            foreach (self::ANNUAL as [$month, $day, $name, $type, $calendars]) {
                $created += $this->add(Carbon::create($year, $month, $day), $name, $type, $calendars, $author);
            }

            foreach (self::COMPANY as [$month, $day, $name, $calendars]) {
                $created += $this->add(Carbon::create($year, $month, $day), $name, 'company', $calendars, $author);
            }

            foreach (self::MOVABLE[$year] ?? [] as [$date, $name, $type, $calendars]) {
                $created += $this->add(Carbon::parse($date), $name, $type, $calendars, $author);
            }
        }

        $this->command->info("{$created} holidays seeded for " . implode(' and ', $years) . '.');
    }

    private function add(Carbon $date, string $name, string $type, array $calendars, ?User $author): int
    {
        $created = 0;

        foreach ($calendars as $calendar) {
            $holiday = Holiday::firstOrCreate(
                ['date' => $date->toDateString(), 'name' => $name, 'calendar' => $calendar],
                ['type' => $type, 'is_active' => true, 'created_by' => $author?->id]
            );

            $created += $holiday->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }
}
