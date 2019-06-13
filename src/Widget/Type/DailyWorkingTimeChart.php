<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Security\CurrentUser;
use DateTime;

class DailyWorkingTimeChart extends SimpleWidget
{
    public const DEFAULT_CHART = 'bar';

    /**
     * @var TimesheetRepository
     */
    protected $repository;

    public function __construct(TimesheetRepository $repository, CurrentUser $user)
    {
        $this->repository = $repository;
        $this->setId('DailyWorkingTimeChart');
        $this->setTitle('stats.yourWorkingHours');
        $this->setOptions([
            'begin' => 'monday this week 00:00:00',
            'end' => 'sunday this week 23:59:59',
            'color' => '',
            'user' => $user->getUser(),
            'type' => self::DEFAULT_CHART,
            'id' => '',
        ]);
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (!in_array($options['type'], ['bar', 'line'])) {
            $options['type'] = self::DEFAULT_CHART;
        }

        if (empty($options['id'])) {
            $options['id'] = uniqid('DailyWorkingTimeChart_');
        }

        return $options;
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $user = $options['user'];
        $begin = new DateTime($options['begin']);
        $end = new DateTime($options['end']);

        return $this->repository->getDailyStats($user, $begin, $end);
    }
}
