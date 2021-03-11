<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Constants;
use App\Event\PageActionsEvent;

class PluginsSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.plugins' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $event->addAction('shop', ['url' => Constants::HOMEPAGE . '/store/', 'target' => '_blank']);
        $event->addHelp($this->documentationLink('plugins.html'));
    }
}