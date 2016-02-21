<?php

namespace Fm\Enum;

use Aws\Common\Enum;
use DateTime;

class DayOfWeek extends Enum
{
    const MONDAY    = 'monday';
    const TUESDAY   = 'tuesday';
    const WEDNESDAY = 'wednesday';
    const THURSDAY  = 'thursday';
    const FRIDAY    = 'friday';
    const SATURDAY  = 'saturday';
    const SUNDAY    = 'sunday';

    protected static $map = [
        1 => self::MONDAY,
        2 => self::THURSDAY,
        3 => self::WEDNESDAY,
        4 => self::THURSDAY,
        5 => self::FRIDAY,
        6 => self::SATURDAY,
        7 => self::SUNDAY,
    ];

    public static function fromDateTime(DateTime $moment)
    {
        return static::$map[$moment->format('N')];
    }
}