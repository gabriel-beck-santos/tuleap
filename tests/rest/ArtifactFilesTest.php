<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once dirname(__FILE__).'/../lib/autoload.php';
require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group ArtifactFilesTest
 */
class ArtifactFilesTest extends RestBase {

    private $first_file;
    private $second_file;
    private $second_chunk = 'with more data';
    private $third_file;

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    protected function getResponseForDifferentUser($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_2_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        $this->first_file = array(
            'name'        => 'my file',
            'description' => 'a very LARGE file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very LARGE file'),
        );

        $this->second_file = array(
            'name'        => 'my file 2',
            'description' => 'a very small file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very small file'),
        );

        $this->third_file = array(
            'name'        => 'my file 3',
            'description' => 'a very small file',
            'mimetype'    => 'text/plain',
            'content'     => base64_encode('a very small file'),
        );
    }

    public function testOptionsArtifactFiles() {
        $response = $this->getResponse($this->client->options('artifact_temporary_files'));
        $this->assertEquals(array('OPTIONS', 'GET', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPostArtifactFile() {
        $post_resource = json_encode($this->first_file);

        $request  = $this->client->post('artifact_temporary_files', null, $post_resource);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $file_representation = $response->json();

        $this->assertGreaterThan(0, $file_representation['id']);
        $this->assertEquals($file_representation['name'], 'my file');
        $this->assertEquals($file_representation['description'], 'a very LARGE file');
        $this->assertEquals($file_representation['type'], 'text/plain');
        $this->assertEquals($file_representation['size'], strlen('a very LARGE file'));
        $this->assertEquals($file_representation['submitted_by'], TestDataBuilder::TEST_USER_1_ID);

        return $file_representation['id'];
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testArtifactTemporaryFilesGetId($file_id) {
        $request  = $this->client->get('artifact_temporary_files/'.$file_id);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $data = $json['data'];

        $this->assertEquals($this->first_file['content'], $data);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactFileId($file_id) {
        $second_chunk = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($second_chunk),
            'offset'  => "2",
        ));

        $request  = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $file_representation = $response->json();

        $this->assertEquals($file_representation['name'], 'my file');
        $this->assertEquals($file_representation['description'], 'a very LARGE file');
        $this->assertEquals($file_representation['type'], 'text/plain');
        $this->assertEquals($file_representation['size'], strlen('a very LARGE file'.$second_chunk));
        $this->assertEquals($file_representation['submitted_by'], TestDataBuilder::TEST_USER_1_ID);

        return $file_id;
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactId_isForbiddenForADifferentUser($file_id) {
        $second_chunk = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($second_chunk),
            'offset'  => "2",
        ));

        $request = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);

        $unauthorised = false;
        try {
            $this->getResponseForDifferentUser($request);
        } catch (Exception $e) {
            $unauthorised = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 401);
        }

        $this->assertTrue($unauthorised);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testPutArtifactId_throwsErrorForAWrongOffset($file_id) {
        $second_chunk = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($second_chunk),
            'offset'  => "45",
        ));

        $request = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);

        $error = false;
        try {
            $this->getResponse($request);
        } catch (Exception $e) {
            $error = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 406);
        }

        $this->assertTrue($error);
    }

    public function testPutArtifactId_throwsErrorForInvalidFile() {
        $file_id = 1453655565245655;
        $chunk   = 'with more data';

        $put_resource = json_encode(array(
            'content' => base64_encode($chunk),
            'offset'  => "2",
        ));

        $request = $this->client->put('artifact_temporary_files/'.$file_id, null, $put_resource);

        $error = false;
        try {
            $this->getResponse($request);
        } catch (Exception $e) {
            $error = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
        }

        $this->assertTrue($error);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testArtifactTemporaryFilesGet($file_id) {
        $request  = $this->client->get('artifact_temporary_files');
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();

        $this->assertCount(1, $json);
        $this->assertEquals($file_id, $json[0]['id']);
        $this->assertEquals($this->first_file['name'], $json[0]['name']);
        $this->assertEquals($this->first_file['description'], $json[0]['description']);
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testOptionsArtifactTemporaryFilesId($file_id) {
        $response = $this->getResponse($this->client->options('artifact_temporary_files/'.$file_id));

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'DELETE'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testPostArtifactFile
     */
    public function testOptionsArtifactId_isForbiddenForADifferentUser($file_id) {
        $request = $this->client->options('artifact_temporary_files/'.$file_id);

        $unauthorised = false;
        try {
            $response = $this->getResponseForDifferentUser($request);
            var_dump($response->getBody(true));
        } catch (Exception $e) {
            $unauthorised = true;
            $this->assertEquals($e->getResponse()->getStatusCode(), 401);
        }

        $this->assertTrue($unauthorised);
    }

    public function testAttachFileToPostArtifact() {
        $post_resource = json_encode($this->third_file);
        $request  = $this->client->post('artifact_temporary_files', null, $post_resource);
        $response = $this->getResponse($request);
        $file_representation = $response->json();

        $request = $this->client->get('trackers/'. TestDataBuilder::USER_STORIES_TRACKER_ID);
        $structure = json_decode($this->getResponse($request)->getBody(true), true);
        foreach ($structure['fields'] as $field) {
            if ($field['type'] == 'file') {
                $field_id_file = $field['field_id'];
            }
            if ($field['label'] == 'I want to') {
                $field_id_summary = $field['field_id'];
            }
            if ($field['label'] == 'Status') {
                $field_id_status = $field['field_id'];
            }
        }
        $this->assertNotNull($field_id_file);
        $this->assertNotNull($field_id_summary);
        $this->assertNotNull($field_id_status);

        $params = json_encode(array(
            'tracker' => array(
                'id'  => TestDataBuilder::USER_STORIES_TRACKER_ID,
                'uri' => 'trackers/' . TestDataBuilder::USER_STORIES_TRACKER_ID
            ),
            'values' => array(
                array(
                    'field_id' => $field_id_summary,
                    'value'    => 'I want 2',
                ),
                array(
                    'field_id'       => $field_id_status,
                    'bind_value_ids' => array(205),
                ),
                array(
                    'field_id' => $field_id_file,
                    'value'    => array($file_representation['id']),
                ),
            ),
        ));

        $response = $this->getResponse($this->client->post('artifacts' , null, $params));
        $this->assertEquals($response->getStatusCode(), 200);
        $posted_artifact = $response->json();

        $response = $this->getResponse($this->client->get('artifacts/' . $posted_artifact['id']));
        $posted_artifact = $response->json();
        $this->assertCount(3, $posted_artifact['values']);
    }

    /**
     * @depends testPutArtifactFileId
     */
    public function testAttachFileToPutArtifact($file_id) {
        $artifact_id = TestDataBuilder::STORY_1_ARTIFACT_ID;

        $request = $this->client->get('trackers/'. TestDataBuilder::USER_STORIES_TRACKER_ID);
        $structure = json_decode($this->getResponse($request)->getBody(true), true);
        foreach ($structure['fields'] as $field) {
            if ($field['type'] == 'file') {
                $field_id = $field['field_id'];
                break;
            }
        }
        $this->assertNotNull($field_id);

        $params = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => array($file_id),
                ),
            ),
        ));

        $response = $this->getResponse($this->client->put('artifacts/'. $artifact_id, null, $params));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('artifacts/' . $artifact_id));
        $posted_artifact = $response->json();
        $this->assertCount(4, $posted_artifact['values']);

        return $file_id;
    }

    /**
     * @depends testAttachFileToPutArtifact
     */
    public function testArtifactAttachedFilesGetId($file_id) {
        $request  = $this->client->get('artifact_files/' . $file_id);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $data = $json['data'];

        $expected = base64_encode(base64_decode($this->first_file['content']).$this->second_chunk);

        $this->assertEquals($expected, $data);
    }

    /**
     * @depends testAttachFileToPutArtifact
     */
    public function testOptionsArtifactAttachedFilesId($file_id) {
        $response = $this->getResponse($this->client->options('artifact_files/'.$file_id));

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testArtifactTemporaryFilesDeleteId() {
        $post_resource = json_encode($this->second_file);
        $request  = $this->client->post('artifact_temporary_files', null, $post_resource);
        $response = $this->getResponse($request);
        $file_representation = $response->json();

        $response = $this->getResponse($this->client->delete('artifact_temporary_files/'.$file_representation['id']));
        $this->assertEquals($response->getStatusCode(), 200);
    }
}