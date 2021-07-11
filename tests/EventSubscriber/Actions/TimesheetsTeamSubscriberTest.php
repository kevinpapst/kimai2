<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber\Actions;

use App\EventSubscriber\Actions\TimesheetsTeamSubscriber;

/**
 * @covers \App\EventSubscriber\Actions\AbstractActionsSubscriber
 * @covers \App\EventSubscriber\Actions\AbstractTimesheetsSubscriber
 * @covers \App\EventSubscriber\Actions\TimesheetsTeamSubscriber
 */
class TimesheetsTeamSubscriberTest extends AbstractActionsSubscriberTest
{
    public function testEventName()
    {
        $this->assertGetSubscribedEvent(TimesheetsTeamSubscriber::class, 'timesheets_team');
    }
}
