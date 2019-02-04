<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\LocaleSettings;
use DateTime;
use Twig\TwigFilter;

/**
 * Date specific twig extensions
 */
class DateExtensions extends \Twig_Extension
{
    // FIXME remove me
    private const FALLBACK_TIME = 'm-d H:i';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('month_name', [$this, 'monthName']),
            new TwigFilter('date_short', [$this, 'dateShort']),
            new TwigFilter('date_time', [$this, 'dateTime']),
        ];
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateShort(DateTime $date)
    {
        $format = $this->localeSettings->getDateFormat();

        return date_format($date, $format);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateTime(DateTime $date)
    {
        // FIXME new logic using LocaleSettings
        $locale = $this->getLocale();
        $format = self::FALLBACK_TIME;

        if (isset($this->dateSettings[$locale]['date_time'])) {
            $format = $this->dateSettings[$locale]['date_time'];
        }

        return date_format($date, $format);
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    public function monthName(\DateTime $date)
    {
        return 'month.' . $date->format('n');
    }
}
