<?php

namespace Reporter;

use \DateTime;
use \DateInterval;

/**
 * Date ops
 *
 * Usage:
 *
 * Intervals::lastMonths();
 * Intervals::lastMonths(2);
 * Intervals::lastMonths(3);
 */
class Intervals {

    public static function now() {

        return new DateTime();
    }

    public static function lastMonths(int $last = 1) {

        $months = [];

        for ($i = 1; $i <= $last; $i++) {
            
            $now      = self::now();
            $months[] = $now->sub(new DateInterval("P{$i}M"));            
        }

        return $months;
    }

    public static function lastWeeks(int $last = 1) {

        $weeks = [];

        for ($i = 1; $i <= $last; $i++) {
            
            $now      = self::now();
            $weeks[]  = $now->sub(new DateInterval("P{$i}W"));            
        }

        return $weeks;
    }

    public static function lastYears(int $last = 1) {

        $years = [];

        for ($i = 1; $i <= $last; $i++) {
            
            $now      = self::now();
            $years[]  = $now->sub(new DateInterval("P{$i}Y"));            
        }

        return $years;
    }
}
