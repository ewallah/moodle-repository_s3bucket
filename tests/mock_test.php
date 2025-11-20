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

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Mock tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\repository_s3bucket::class)]
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
     * Test mock exception s3.
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
