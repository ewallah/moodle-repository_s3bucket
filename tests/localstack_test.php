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
 * Localstack tests.
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
 * Localstack tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\repository_s3bucket::class)]
final class localstack_test extends \advanced_testcase {
    /**
     * Test mock exception s3.
     */
    public function test_localstack(): void {
        if (\repository_s3bucket::no_localstack()) {
            $this->markTestSkipped('Skipping as localstack is not installed.');
        }

        $this->resetAfterTest(true);
        $this->getDataGenerator()->create_repository_type('s3bucket');
        $repo = $this->getDataGenerator()->create_repository('s3bucket')->id;
        $this->SetAdminUser();

        $s3bucket = new \repository_s3bucket($repo);
        $s3bucket->set_option(['endpoint' => 'http://localhost:4566']);
        $s3bucket->set_option(['region' => 'http://localhost:4566']);
        $s3bucket->set_option(['secret_key' => 'test']);
        $s3bucket->set_option(['bucket_name' => 'testbucket']);
        $s3bucket->set_option(['access_key' => 'test']);

        $reflection = new \ReflectionClass($s3bucket);
        $method = $reflection->getMethod('create_s3');
        $client = $method->invoke($s3bucket);
        $this->assertInstanceOf('Aws\S3\S3Client', $client);
        $this->assertInstanceOf('Aws\Command', $client->getCommand('HeadBucket', ['Bucket' => 'testbucket']));
        $this->assertInstanceOf('Aws\ResultPaginator', $client->getPaginator('ListObjects', []));
        $arr = ['Bucket' => 'testbucket', 'Key' => 'testfile.jpg', 'ResponseContentDisposition' => 'attachment'];
        $result = $client->getCommand('GetObject', $arr);
        $this->assertInstanceOf('Aws\Command', $result);
        $this->assertNotEmpty($client->createPresignedRequest($result, 2));
        $result = $s3bucket->search('testfile.jpg');
        $this->assertCount(1, $result['list']);
        $result = $s3bucket->get_link('testfile.jpg');
        $this->assertStringStartsWith('https://www.example.com/moodle/pluginfile.php/1/repository_s3bucket/s3/', $result);
        $this->assertStringEndsWith('/testfile.jpg', $result);

        ob_start();
        $s3bucket->send_otherfile('testfile.jpg', 30);
        $out = ob_get_clean();
        $strs = [
            'Cache-Control: private, must-revalidate, pre-check=0, post-check=0',
            'max-age=0',
            'Pragma: no-cache',
            'Content-Type: Image (JPEG)',
            'Content-Disposition: attachment;',
            'filename="testfile.jpg"',
            'Location: http://localhost:4566/testbucket/testfile.jpg',
            'response-content-disposition=attachment',
            'X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&',
            'X-Amz-Algorithm=AWS4-HMAC-SHA256&',
            'X-Amz-Credential=test',
            'us-east-1',
            'Faws4_request',
            'X-Amz-Date=',
            'X-Amz-SignedHeaders=host&',
            'X-Amz-Expires=-',
            '&X-Amz-Signature=',
        ];

        foreach ($strs as $str) {
            $this->assertStringContainsString($str, $out);
        }
    }
}
