<?php

declare(strict_types=1);

namespace Dot\Twig\Extension;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;

use function ctype_digit;
use function strtolower;
use function substr;

class DateExtension extends AbstractExtension
{
    public static array $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('time_diff', [$this, 'diff'], ['needs_environment' => true]),
        ];
    }

    /**
     * Filters for converting dates to a time ago string like Facebook and Twitter has.
     * If none given, the current time will be used.
     *
     * @throws Exception
     */
    public function diff(
        Environment $env,
        string|DateTimeInterface|null $date,
        string|DateTimeInterface|null $now = null,
        string|DateTimeZone|null $timezone = null,
    ): string {
        if (null === $timezone) {
            $timezone = $env->getExtension(CoreExtension::class)->getTimezone();
        } elseif (! $timezone instanceof DateTimeZone) {
            $timezone = new DateTimeZone($timezone);
        }

        // Convert both dates to DateTime instances.
        $date = $this->convertDate($date, $timezone);
        $now  = $this->convertDate($now, $timezone);

        // Get the difference between the two DateTime objects.
        $diff = $date->diff($now);

        // Check for each interval if it appears in the $diff object.
        foreach (self::$units as $attribute => $unit) {
            $count = $diff->$attribute;

            if (0 !== $count) {
                return $this->getPluralizedInterval($count, $diff->invert, $unit);
            }
        }

        return '';
    }

    /**
     * @throws Exception
     */
    protected function convertDate(string|DateTimeInterface|null $date, DateTimeZone $timezone): DateTimeInterface
    {
        if ($date instanceof DateTimeImmutable) {
            return $date->setTimezone($timezone);
        }

        if ($date instanceof DateTimeInterface) {
            $date = clone $date;
            $date->setTimezone($timezone);

            return $date;
        }

        if (null === $date || 'now' === strtolower($date)) {
            if (null === $date) {
                $date = 'now';
            }

            return new DateTime($date, $timezone);
        }

        if (
            ctype_digit($date)
            || (! empty($date) && '-' === $date[0] && ctype_digit(substr($date, 1)))
        ) {
            return new DateTime('@' . $date, $timezone);
        } else {
            return new DateTime($date, $timezone);
        }
    }

    public function getPluralizedInterval(mixed $count, int $invert, string $unit): string
    {
        if (1 !== $count) {
            $unit .= 's';
        }

        return $invert ? "in $count $unit" : "$count $unit ago";
    }
}
