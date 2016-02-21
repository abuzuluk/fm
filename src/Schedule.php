<?php

namespace Fm;

use DateTime;
use SplFileObject;
use Symfony\Component\Yaml\Yaml;
use Fm\Enum\DayOfWeek;

class Schedule
{
    /**
     * @var array[]
     */
    protected $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public static function fromYaml($path)
    {
        $file    = new SplFileObject($path);
        $records = Yaml::parse(implode(iterator_to_array($file)));

        return new static($records);
    }

    /**
     * @param DateTime $moment
     *
     * @return Song
     */
    public function search(DateTime $moment)
    {
        $dayOfWeek = DayOfWeek::fromDateTime($moment);

        return new Song($this->records[$dayOfWeek]);
    }
}