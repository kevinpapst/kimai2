<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Timesheet;
use Doctrine\ORM\EntityManagerInterface;

final class TimesheetLoader implements LoaderInterface
{
    /**
     * @var TimesheetIdLoader
     */
    private $loader;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new TimesheetIdLoader($entityManager);
    }

    public function setPreloadMetaFields(bool $preload): LoaderInterface
    {
        $this->loader->setPreloadMetaFields($preload);

        return $this;
    }

    public function setPreloadUser(bool $preload): LoaderInterface
    {
        $this->loader->setPreloadUser($preload);

        return $this;
    }

    public function setPreloadTags(bool $preload): LoaderInterface
    {
        $this->loader->setPreloadTags($preload);

        return $this;
    }

    /**
     * @param Timesheet[] $timesheets
     */
    public function loadResults(array $timesheets): void
    {
        $ids = array_map(function (Timesheet $timesheet) {
            return $timesheet->getId();
        }, $timesheets);

        $this->loader->loadResults($ids);
    }
}
