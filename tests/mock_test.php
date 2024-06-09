<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mock tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_s3bucket;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

/**
 * Mock tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mock_test extends \advanced_testcase {
    /**
     * Create type and instance.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test tearDown.
     */
    public function tearDown(): void {
        set_config('s3mock', false);
        parent::tearDown();
    }

    /**
     * Test listobjects s3.
     * @covers \repository_s3bucket
     */
    public function test_listobjects(): void {
        $result = new \Aws\Result(['key1' => 'object1', 'key2' => 'object2', 'key3' => 'object3']);
        $args = ['Bucket' => 'test', 'Key' => 'key', 'SaveAs' => 'path'];
        $options = [
            'Bucket' => 'test',
            'FetchOwner' => false,
            'Prefix' => '.',
            'MaxKeys' => 1000,
            'EncodingType' => 'url',
            'Delimiter' => '/', ];
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['listObjectsV2', 'getPaginator', 'getObject'])
            ->setConstructorArgs([$options, ['Bucket' => 'test'], $args])
            ->getMock();
        $client->expects($this->once())
            ->method('listObjectsV2')
            ->with(['Bucket' => 'test'])
            ->will($this->returnValue($result));

        $list = $client->listObjectsV2(['Bucket' => 'test']);
        $this->assertTrue($list->hasKey('key1'));
        $this->assertFalse($list->hasKey('object2'));

        $client->expects($this->once())
            ->method('getPaginator')
            ->with('listObjects', ['Bucket' => 'testbucket'])
            ->will($this->returnValue($result));
        $list = $client->getPaginator('listObjects', ['Bucket' => 'testbucket']);
        $this->assertTrue($list->hasKey('key1'));
        $this->assertFalse($list->hasKey('object2'));

        $client->expects($this->once())
            ->method('getObject')
            ->with($args)
            ->will($this->returnValue($result));
        $list = $client->getObject($args);
        $this->assertTrue($list->hasKey('key1'));
        $this->assertFalse($list->hasKey('object2'));

        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->setConstructorArgs(['key', 'secret'])
            ->getMock();
        $client->expects($this->once())
            ->method('getObject')
            ->with(['Bucket' => 'testbucket', 'key' => 'key'])
            ->will($this->returnValue([]));
        $list = $client->getObject(['Bucket' => 'testbucket', 'key' => 'key']);
    }

    /**
     * Test mock exception s3.
     * @covers \repository_s3bucket
     */
    public function test_mockexception(): void {
        $this->resetAfterTest(true);
        $this->getDataGenerator()->create_repository_type('s3bucket');
        $repo = $this->getDataGenerator()->create_repository('s3bucket')->id;
        $this->SetAdminUser();

        set_config('s3mock', true);
        $s3bucket = new \repository_s3bucket($repo);
        $s3bucket->set_option(['endpoint' => 'us-east-1']);
        $reflection = new \ReflectionClass($s3bucket);
        $method = $reflection->getMethod('create_s3');
        $client = $method->invoke($s3bucket);
        $this->assertInstanceOf('Aws\S3\S3Client', $client);
        $this->assertInstanceOf('Aws\Command', $client->getCommand('HeadBucket', ['Bucket' => 'testwallah']));
        $this->assertInstanceOf('Aws\ResultPaginator', $client->getPaginator('ListObjects', []));
        $arr = ['Bucket' => 'testwallah', 'Key' => 'testfile', 'ResponseContentDisposition' => 'attachment'];
        $result = $client->getCommand('GetObject', $arr);
        $this->assertInstanceOf('Aws\Command', $result);
        $this->assertNotEmpty($client->createPresignedRequest($result, 2));

        set_config('s3mock', false);
        $s3bucket = new \repository_s3bucket($repo);
        $s3bucket->set_option(['endpoint' => 'us-west-1']);
        $reflection = new \ReflectionClass($s3bucket);
        $method = $reflection->getMethod('create_s3');
        $client = $method->invoke($s3bucket);
        $this->assertInstanceOf('Aws\S3\S3Client', $client);
        $this->assertInstanceOf('Aws\ResultPaginator', $client->getPaginator('ListObjects', []));
        $this->assertInstanceOf('Aws\Command', $client->getCommand('HeadBucket', ['Bucket' => 'testwallah']));
        $arr = ['Bucket' => 'testwallah', 'Key' => 'testfile', 'ResponseContentDisposition' => 'attachment'];
        $result = $client->getCommand('GetObject', $arr);
        $this->assertInstanceOf('Aws\Command', $result);
        $this->assertNotEmpty($client->createPresignedRequest($result, 2));
    }
}
