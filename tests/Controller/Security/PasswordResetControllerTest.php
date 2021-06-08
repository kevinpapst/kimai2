<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Security;

use App\Tests\Controller\ControllerBaseTest;

/**
 * @group integration
 */
class PasswordResetControllerTest extends ControllerBaseTest
{
    private function testResetActionWithDeactivatedFeature(string $route)
    {
        $client = self::createClient();
        $this->setSystemConfiguration('user.password_reset', false);
        $this->request($client, $route);
        $this->assertRouteNotFound($client);
    }

    public function testResetRequestWithDeactivatedFeature()
    {
        $this->testResetActionWithDeactivatedFeature('/auth/resetting/request');
    }

    public function testSendEmailRequestWithDeactivatedFeature()
    {
        $this->testResetActionWithDeactivatedFeature('/auth/resetting/send-email');
    }

    public function testCheckEmailWithDeactivatedFeature()
    {
        $this->testResetActionWithDeactivatedFeature('/auth/resetting/check-email');
    }

    public function testResetWithDeactivatedFeature()
    {
        $this->testResetActionWithDeactivatedFeature('/auth/resetting/reset/1234567890');
    }
}
