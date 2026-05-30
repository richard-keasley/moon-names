<?php

/**
 * Moon Names Calculator
 * 
 * Calculate traditional moon names for any given date, including detection of blue moons.
 * Supports two definitions of blue moon:
 * 1. Second full moon in a calendar month
 * 2. Third full moon in a season (astronomical definition)
 */

class MoonNamesCalculator
{
    /**
     * Traditional names for full moons by month
     */
    private const MOON_NAMES = [
        1 => 'Wolf Moon',
        2 => 'Snow Moon',
        3 => 'Worm Moon',
        4 => 'Pink Moon',
        5 => 'Flower Moon',
        6 => 'Strawberry Moon',
        7 => 'Buck Moon',
        8 => 'Sturgeon Moon',
        9 => 'Corn Moon',
        10 => 'Hunter\'s Moon',
        11 => 'Beaver Moon',
        12 => 'Cold Moon'
    ];

    /**
     * Seasons for astronomical blue moon definition
     */
    private const SEASONS = [
        'Winter' => [12, 1, 2],
        'Spring' => [3, 4, 5],
        'Summer' => [6, 7, 8],
        'Fall' => [9, 10, 11]
    ];

    /**
     * Get the traditional name for a full moon
     *
     * @param DateTime $date The date of the full moon
     * @return string The traditional moon name
     */
    public function getMoonName(DateTime $date): string
    {
        $month = (int)$date->format('n');
        return self::MOON_NAMES[$month] ?? 'Unknown Moon';
    }

    /**
     * Check if a date is a full moon and get moon details
     *
     * @param DateTime $date The date to check
     * @return array|null Array with moon info or null if not a full moon
     */
    public function getFullMoonInfo(DateTime $date): ?array
    {
        $fullMoonDates = $this->getFullMoonsInYear((int)$date->format('Y'));
        
        foreach ($fullMoonDates as $moonDate) {
            if ($moonDate->format('Y-m-d') === $date->format('Y-m-d')) {
                return $this->buildMoonInfo($date, $fullMoonDates);
            }
        }
        
        return null;
    }

    /**
     * Get all full moon dates in a given year
     *
     * @param int $year The year to get full moons for
     * @return DateTime[] Array of full moon dates
     */
    public function getFullMoonsInYear(int $year): array
    {
        $fullMoons = [];
        
        // Calculate full moons for each month
        for ($month = 1; $month <= 12; $month++) {
            $moonDate = $this->calculateFullMoonInMonth($year, $month);
            if ($moonDate) {
                $fullMoons[] = $moonDate;
            }
        }
        
        // Sort by date
        usort($fullMoons, function($a, $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });
        
        return $fullMoons;
    }

    /**
     * Calculate approximate full moon date for a given month
     * Uses astronomical calculation
     *
     * @param int $year The year
     * @param int $month The month
     * @return DateTime|null The approximate full moon date
     */
    private function calculateFullMoonInMonth(int $year, int $month): ?DateTime
    {
        // Using Meeus' algorithm for Moon phase
        $k = ($year - 1900) * 12.3685 + $month - 0.5;
        $k = floor($k); // Get the integer k for this month
        
        // Calculate the time of full moon (add 0.5 for full moon vs 0 for new moon)
        $k = $k + 0.5;
        
        // Calculate JDE (Ephemeris Days)
        $T = $k / 1236.85;
        $JDE = 2451550.09766 + 29.530588861 * $k
            + 0.00015437 * $T * $T
            - 0.000000150 * $T * $T * $T
            + 0.00000011 * $T * $T * $T * $T;
        
        // Convert JDE to Gregorian calendar
        $Z = floor($JDE + 0.5);
        $F = $JDE + 0.5 - $Z;
        
        if ($Z < 2299161) {
            $A = $Z;
        } else {
            $alpha = floor(($Z - 1867216.25) / 36524.25);
            $A = $Z + 1 + $alpha - floor($alpha / 4);
        }
        
        $B = $A + 1524;
        $C = floor(($B - 122.1) / 365.25);
        $D = floor(365.25 * $C);
        $E = floor(($B - $D) / 30.6001);
        
        $day = $B - $D - floor(30.6001 * $E) + $F;
        $month_calc = ($E < 14) ? $E - 1 : $E - 13;
        $year_calc = ($month_calc > 2) ? $C - 4716 : $C - 4715;
        
        if ((int)$year_calc !== $year) {
            return null;
        }
        
        $moonDate = new DateTime();
        $moonDate->setDate($year, (int)$month_calc, (int)$day);
        
        return $moonDate;
    }

    /**
     * Build complete moon information array
     *
     * @param DateTime $date The date
     * @param DateTime[] $yearFullMoons All full moons in the year
     * @return array Moon information
     */
    private function buildMoonInfo(DateTime $date, array $yearFullMoons): array
    {
        $month = (int)$date->format('n');
        $year = (int)$date->format('Y');
        
        $name = $this->getMoonName($date);
        $isBlueCalendar = $this->isBlueMonthly($date, $yearFullMoons);
        $isBlueAstronomical = $this->isBlueSeasonal($date, $yearFullMoons);
        
        if ($isBlueCalendar) {
            $name .= ' (Blue Moon)';
        } elseif ($isBlueAstronomical) {
            $name .= ' (Astronomical Blue Moon)';
        }
        
        return [
            'date' => $date->format('Y-m-d'),
            'name' => $name,
            'month_name' => $date->format('F'),
            'is_blue_monthly' => $isBlueCalendar,
            'is_blue_astronomical' => $isBlueAstronomical,
            'timestamp' => $date->getTimestamp()
        ];
    }

    /**
     * Check if this is a blue moon (second full moon in a calendar month)
     *
     * @param DateTime $date The date
     * @param DateTime[] $yearFullMoons All full moons in the year
     * @return bool True if this is a blue moon
     */
    private function isBlueMonthly(DateTime $date, array $yearFullMoons): bool
    {
        $targetMonth = (int)$date->format('n');
        $targetYear = (int)$date->format('Y');
        
        $moonsInMonth = array_filter($yearFullMoons, function($moonDate) use ($targetMonth, $targetYear) {
            return (int)$moonDate->format('n') === $targetMonth 
                && (int)$moonDate->format('Y') === $targetYear;
        });
        
        return count($moonsInMonth) > 1;
    }

    /**
     * Check if this is an astronomical blue moon (third full moon in a season)
     *
     * @param DateTime $date The date
     * @param DateTime[] $yearFullMoons All full moons in the year
     * @return bool True if this is an astronomical blue moon
     */
    private function isBlueSeasonal(DateTime $date, array $yearFullMoons): bool
    {
        $targetMonth = (int)$date->format('n');
        $targetYear = (int)$date->format('Y');
        
        // Find which season this month belongs to
        $season = null;
        foreach (self::SEASONS as $seasonName => $months) {
            if (in_array($targetMonth, $months)) {
                $season = $months;
                break;
            }
        }
        
        if (!$season) {
            return false;
        }
        
        // Count full moons in this season within the year
        $moonsInSeason = array_filter($yearFullMoons, function($moonDate) use ($season, $targetYear) {
            $moonMonth = (int)$moonDate->format('n');
            $moonYear = (int)$moonDate->format('Y');
            return in_array($moonMonth, $season) && $moonYear === $targetYear;
        });
        
        // For winter, we need to check across year boundaries
        if (in_array(12, $season)) {
            $decemberMoons = array_filter($yearFullMoons, function($moonDate) use ($targetYear) {
                return (int)$moonDate->format('n') === 12 && (int)$moonDate->format('Y') === $targetYear;
            });
            
            $nextYearJanuaryFebruary = $this->getFullMoonsInYear($targetYear + 1);
            $janFebMoons = array_filter($nextYearJanuaryFebruary, function($moonDate) {
                $month = (int)$moonDate->format('n');
                return in_array($month, [1, 2]);
            });
            
            if (in_array($targetMonth, [1, 2])) {
                $moonsInSeason = array_merge($decemberMoons, $janFebMoons);
            }
        }
        
        return count($moonsInSeason) > 3;
    }

    /**
     * Get all moon information for a given year
     *
     * @param int $year The year
     * @return array Array of moon information for all full moons
     */
    public function getMoonsForYear(int $year): array
    {
        $fullMoons = $this->getFullMoonsInYear($year);
        $moonsInfo = [];
        
        foreach ($fullMoons as $fullMoon) {
            $moonsInfo[] = $this->buildMoonInfo($fullMoon, $fullMoons);
        }
        
        return $moonsInfo;
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    $calculator = new MoonNamesCalculator();
    
    echo "Moon Names for 2026\n";
    echo str_repeat("=", 80) . "\n\n";
    
    $moons = $calculator->getMoonsForYear(2026);
    
    foreach ($moons as $moon) {
        echo sprintf(
            "%-12s | %-30s | %s%s\n",
            $moon['date'],
            $moon['name'],
            $moon['is_blue_monthly'] ? '[Monthly Blue Moon]' : '',
            $moon['is_blue_astronomical'] ? '[Astronomical Blue Moon]' : ''
        );
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    
    // Check specific date
    $checkDate = new DateTime('2026-05-23');
    $info = $calculator->getFullMoonInfo($checkDate);
    if ($info) {
        echo "\nSpecific Date Check: {$checkDate->format('Y-m-d')}\n";
        echo "Moon Name: {$info['name']}\n";
        echo "Is Blue Moon (Monthly): " . ($info['is_blue_monthly'] ? 'Yes' : 'No') . "\n";
        echo "Is Blue Moon (Astronomical): " . ($info['is_blue_astronomical'] ? 'Yes' : 'No') . "\n";
    }
}
?>