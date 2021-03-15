<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class ProjectsSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.projects' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addSearchToggle();
        $event->addColumnToggle('#modal_project_admin');
        $event->addQuickExport($this->path('project_export'));

        if ($this->isGranted('create_project')) {
            $event->addCreate($this->path('admin_project_create'));
        }

        $event->addHelp($this->documentationLink('project.html'));
    }
}
