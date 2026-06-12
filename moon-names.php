<?php

/**
 * Moon Names Calculator
 * 
 * Calculate traditional moon names for any given date, including detection of blue moons,
 * and determine the current moon phase (illumination percentage and phase name).
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
     * Moon phase names and their illumination ranges
     */
    private const MOON_PHASES = [
        'New Moon' => [0, 1],
        'Waxing Crescent' => [1, 25],
        'First Quarter' => [25, 26],
        'Waxing Gibbous' => [26, 75],
        'Full Moon' => [75, 76],
        'Waning Gibbous' => [76, 99],
        'Last Quarter' => [99, 100],
        'Waning Crescent' => [100, 101]
    ];

    /**
     * Inline SVG moon symbols for visual representation
     */
    private const MOON_SYMBOLS = [
        'New Moon' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#000"/></svg>',
        'Waxing Crescent' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><mask id="wc"><rect width="100" height="100" fill="white"/><circle cx="70" cy="50" r="38" fill="black"/></mask></defs><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700" mask="url(#wc)"/></svg>',
        'First Quarter' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><mask id="fq"><rect width="100" height="100" fill="white"/><rect x="50" y="0" width="50" height="100" fill="black"/></mask></defs><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700" mask="url(#fq)"/></svg>',
        'Waxing Gibbous' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><mask id="wg"><rect width="100" height="100" fill="white"/><circle cx="30" cy="50" r="38" fill="black"/></mask></defs><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700" mask="url(#wg)"/></svg>',
        'Full Moon' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700"/><circle cx="45" cy="35" r="3" fill="#DDD" opacity="0.6"/><circle cx="60" cy="50" r="2.5" fill="#DDD" opacity="0.6"/><circle cx="40" cy="65" r="2" fill="#DDD" opacity="0.6"/></svg>',
        'Waning Gibbous' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><mask id="ngb"><rect width="100" height="100" fill="white"/><circle cx="70" cy="50" r="38" fill="black"/></mask></defs><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700" mask="url(#ngb)"/></svg>',
        'Last Quarter' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><mask id="lq"><rect width="100" height="100" fill="white"/><rect x="0" y="0" width="50" height="100" fill="black"/></mask></defs><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700" mask="url(#lq)"/></svg>',
        'Waning Crescent' => '<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><mask id="nc"><rect width="100" height="100" fill="white"/><circle cx="30" cy="50" r="38" fill="black"/></mask></defs><circle cx="50" cy="50" r="40" fill="none" stroke="#333" stroke-width="2"/><circle cx="50" cy="50" r="38" fill="#FFD700" mask="url(#nc)"/></svg>',
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
     * Calculate the moon phase for a given date
     * 
     * @param DateTime $date The date to calculate the moon phase for
     * @return array Array containing 'illumination' (0-100), 'phase_name', and 'symbol'
     */
    public function getMoonPhase(DateTime $date): array
    {
        // Reference date: New Moon on January 6, 2000, 18:14 UTC
        $referenceNewMoon = new DateTime('2000-01-06 18:14:00', new DateTimeZone('UTC'));
        
        // Lunar cycle is approximately 29.53058867 days
        $lunarCycle = 29.53058867;
        
        // Convert input date to UTC for consistency
        $date->setTimezone(new DateTimeZone('UTC'));
        
        // Calculate days since reference new moon
        $interval = $date->diff($referenceNewMoon);
        $daysSinceReference = $interval->days + ($interval->h / 24) + ($interval->i / 1440) + ($interval->s / 86400);
        
        // Account for date direction (past or future)
        if ($interval->invert) {
            $daysSinceReference = -$daysSinceReference;
        }
        
        // Calculate position in current lunar cycle (0-1, where 0 is new moon, 0.5 is full moon)
        $cyclePosition = fmod($daysSinceReference, $lunarCycle) / $lunarCycle;
        
        // Ensure positive value
        if ($cyclePosition < 0) {
            $cyclePosition += 1;
        }
        
        // Calculate illumination percentage
        // Formula: (1 - cos(2π * cyclePosition)) / 2 * 100
        $illumination = (1 - cos(2 * M_PI * $cyclePosition)) / 2 * 100;
        
        // Round to nearest integer
        $illumination = (int)round($illumination);
        
        // Determine phase name based on illumination
        $phaseName = $this->getPhaseNameByIllumination($illumination);
        $symbol = self::MOON_SYMBOLS[$phaseName] ?? '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#FFD700"/></svg>';
        
        return [
            'illumination' => $illumination,
            'phase_name' => $phaseName,
            'symbol' => $symbol,
            'cycle_position' => round($cyclePosition, 4)
        ];
    }

    /**
     * Get the moon phase name based on illumination percentage
     *
     * @param int $illumination Illumination percentage (0-100)
     * @return string The phase name
     */
    private function getPhaseNameByIllumination(int $illumination): string
    {
        $illumination = $illumination % 101;
        
        if ($illumination <= 1 || $illumination >= 99) {
            return 'New Moon';
        } elseif ($illumination > 1 && $illumination < 25) {
            return 'Waxing Crescent';
        } elseif ($illumination >= 25 && $illumination <= 26) {
            return 'First Quarter';
        } elseif ($illumination > 26 && $illumination < 75) {
            return 'Waxing Gibbous';
        } elseif ($illumination >= 75 && $illumination <= 76) {
            return 'Full Moon';
        } elseif ($illumination > 76 && $illumination < 99) {
            return 'Waning Gibbous';
        } else {
            return 'Waning Crescent';
        }
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
     * Get complete moon information for any date (including phase)
     *
     * @param DateTime $date The date to get moon information for
     * @return array Complete moon information including phase
     */
    public function getMoonInfoForDate(DateTime $date): array
    {
        $phase = $this->getMoonPhase($date);
        $fullMoonInfo = $this->getFullMoonInfo($date);
        
        $info = [
            'date' => $date->format('Y-m-d'),
            'phase' => $phase,
            'is_full_moon' => $fullMoonInfo !== null,
        ];
        
        // Add full moon specific info if applicable
        if ($fullMoonInfo) {
            $info['moon_name'] = $fullMoonInfo['name'];
            $info['is_blue_monthly'] = $fullMoonInfo['is_blue_monthly'];
            $info['is_blue_astronomical'] = $fullMoonInfo['is_blue_astronomical'];
        }
        
        return $info;
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
        $year_calc = ($month_calc > 2) ? $C - 4716 : $C - 4716;
        
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
    
    // Moon phase examples
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Moon Phase Examples for Today\n";
    echo str_repeat("=", 80) . "\n\n";
    
    $testDates = [
        new DateTime('2026-06-11'),
        new DateTime('2026-06-04'),
        new DateTime('2026-06-11'),
        new DateTime('2026-06-18'),
    ];
    
    foreach ($testDates as $testDate) {
        $fullInfo = $calculator->getMoonInfoForDate($testDate);
        echo sprintf(
            "%s %s | Phase: %-20s | Illumination: %3d%% | %s\n",
            $fullInfo['phase']['symbol'],
            $testDate->format('Y-m-d'),
            $fullInfo['phase']['phase_name'],
            $fullInfo['phase']['illumination'],
            $fullInfo['is_full_moon'] ? "🌕 FULL MOON: {$fullInfo['moon_name']}" : ''
        );
    }
}
?>
