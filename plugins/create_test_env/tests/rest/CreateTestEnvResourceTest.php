<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\CreateTestEnv\REST;

require_once __DIR__.'/../../../../tests/rest/lib/autoload.php';

class CreateTestEnvResourceTest extends \RestBase
{

    public function testOptions()
    {
        $response  = $this->getResponseWithoutAuth($this->client->options(
            'create_test_env/'
        ));

        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testCreateProject()
    {
        $response  = $this->getResponseWithoutAuth($this->client->post(
            'create_test_env/',
            null,
            json_encode(
                [
                    'secret'    => 'a78e62ee64d594d99a800e5489c052d98cce84a54bb571bccc29b0dcd7ef4441',
                    'firstname' => 'John',
                    'lastname'  => 'Doe',
                    'email'     => 'jd@example.com',
                ],
                true
            )
        ));

        $this->assertEquals($response->getStatusCode(), 201);

        $return = $response->json();
        $this->assertInternalType('int', $return['id']);
        $this->assertEquals('test-for-john-doe', $return['project_shortname']);
        $this->assertEquals('https://localhost/projects/test-for-john-doe', $return['project_url']);
        $this->assertStringStartsWith('Test project for ', $return['project_realname']);
        $this->assertEquals('john-doe', $return['user_login']);
        $this->assertGreaterThan(15, strlen($return['user_password']));
    }
}
