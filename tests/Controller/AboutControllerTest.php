<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;

/**
 * @coversDefaultClass \App\Controller\AboutController
 * @group integration
 */
class AboutControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/about');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/about');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/about');

        $result = $client->getCrawler()->filter('div.nav-tabs-custom ul.nav.nav-tabs li');
        $this->assertEquals(2, count($result));

        $result = $client->getCrawler()->filter('div.nav-tabs-custom div.tab-content div.tab-pane');
        $this->assertEquals(2, count($result));
    }
}
