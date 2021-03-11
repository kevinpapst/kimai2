<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class ActivitiesSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.activities' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $event->addSearchToggle();
        $event->addColumnToggle('#modal_activity_admin');
        $event->addQuickExport($this->path('activity_export'));

        if ($this->isGranted('create_activity')) {
            $event->addCreate($this->path('admin_activity_create'));
        }

        $event->addHelp($this->documentationLink('activity.html'));
    }
}
