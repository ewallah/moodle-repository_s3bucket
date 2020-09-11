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
 * @copyright  2017 Renaat Debleu (www.eWallah.net) (based on work by Dongsheng Cai)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

/**
 * Mock tests.
 *
 * @package    repository_s3bucket
 * @copyright  2017 Renaat Debleu (www.eWallah.net) (based on work by Dongsheng Cai)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \repository_s3bucket
 */
class repository_s3bucket_mock_tests extends \advanced_testcase {

    /**
     * Test listobjects s3.
     */
    public function test_listobjects() {
        $result = new Aws\Result(['key1' => 'object1', 'key2' => 'object2', 'key3' => 'object3']);
        $args = ['Bucket' => 'test', 'Key' => 'key', 'SaveAs' => 'path'];
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['listObjects', 'getPaginator', 'getObject'])
            ->setConstructorArgs([['Bucket' => 'test'], ['Bucket' => 'test'], $args])
            ->getMock();
        $client->expects($this->once())
            ->method('listObjects')
            ->with(['Bucket' => 'test'])
            ->will($this->returnValue($result));

        $list = $client->listObjects(['Bucket' => 'test']);
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
        $client->expects( $this->once())
            ->method('getObject')
            ->with(['Bucket' => 'testbucket', 'key' => 'key'])
            ->will($this->returnValue([]));
        $list = $client->getObject(['Bucket' => 'testbucket', 'key' => 'key']);
    }

    /**
     * Test mock exception s3.
     */
    public function test_mockexception() {
        $this->resetAfterTest(true);
        $type = 's3bucket';
        $this->getDataGenerator()->create_repository_type($type);
        $repo = $this->getDataGenerator()->create_repository($type)->id;
        $this->SetAdminUser();
        $s3bucket = new repository_s3bucket($repo);
        $reflection = new ReflectionClass($s3bucket);
        $method = $reflection->getMethod('create_s3');
        $method->setAccessible(true);
        $this->assertInstanceOf('Aws\S3\S3Client', $method->invoke($s3bucket));
    }
}