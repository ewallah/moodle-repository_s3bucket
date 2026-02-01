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
 * Other tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace repository_s3bucket;

use advanced_testcase;
use context_course;
use context_system;
use context_user;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/s3bucket/lib.php');

/**
 * Other tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\repository_s3bucket::class)]
final class other_test extends advanced_testcase {
    /** @var int repo */
    protected $repo;

    /**
     * Create type and instance.
     */
    public function setUp(): void {
        parent::setUp();
        if (\repository_s3bucket::no_localstack()) {
            $this->markTestSkipped('Skipping as localstack is not installed.');
        }
        $this->resetAfterTest(true);
        $this->getDataGenerator()->create_repository_type('s3bucket');
        $this->repo = $this->getDataGenerator()->create_repository('s3bucket')->id;
        $this->SetAdminUser();
    }

    /**
     * Test sendfile s3.
     */
    public function test_sendfile_s3(): void {
        global $USER;
        $repo = $this->create_repo(context_system::instance());
        $fs = get_file_storage();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => context_user::instance($USER->id)->id,
                       'itemid' => file_get_unused_draft_itemid(), 'filename' => 'filename.jpg', 'filepath' => '/', ];
        $file = $fs->create_file_from_string($filerecord, 'test content');
        $this->expectException('repository_exception');
        $repo->send_file($file);
    }

    #[\core\attribute\label('Test disabled.')]
    public function test_disabled(): void {
        $repo = $this->create_repo(context_system::instance());
        $repo->disabled = true;
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Cannot download this file');
        $repo->get_reference_details('testfile.jpg');
    }

    /**
     * Test class in system context.
     */
    public function test_class(): void {
        $repo = $this->create_repo(context_system::instance());
        $this->assertEquals('S3 bucket', $repo->get_name());
        $this->assertTrue($repo->check_login());
        $this->assertFalse($repo->contains_private_data());
        $this->assertEquals(['duration'], $repo->get_type_option_names());
        $this->assertCount(4, $repo->get_instance_option_names());
        $this->assertEquals('Unknown source', $repo->get_reference_details(''));
        $this->assertEquals('s3://testbucket/testfile.jpg', $repo->get_file_source_info('testfile.jpg'), 1);
        $this->assertEquals('s3://testbucket/testfile.jpg', $repo->get_reference_details('testfile.jpg'), 0);
        $this->assertEquals('Unknown source', $repo->get_reference_details('testfile.jpg', 666));
        $this->assertEquals('s3://testbucket/testfile.jpg', $repo->get_reference_details('testfile.jpg', 999));
        $this->assertFalse($repo->global_search());
        $this->assertEquals(7, $repo->supported_returntypes());
        $this->assertEquals(4, $repo->default_returntype());
        $this->SetAdminUser();
        $this->assertEquals(2, $repo->check_capability());

        $all = $repo->get_listing();
        $this->assertCount(6, $all);
        $result = $repo->get_file('testfile.jpg', 'testfile.jpg');
        $this->assertEquals('testfile.jpg', $result['url']);
        $this->assertStringContainsString('/testfile.jpg', $result['path']);
    }

    /**
     * Test empty in course context.
     */
    public function test_empty(): void {
        $courseid = $this->getDataGenerator()->create_course()->id;
        $repo = $this->create_repo(context_course::instance($courseid));
        $result = $repo->get_listing('');
        $path = ['name' => 'testbucket', 'path' => ''];
        $this->assertCount(1, $result['path']);
        $this->assertEquals($path, $result['path'][0]);
        $this->assertCount(3, $result['list']);
        $directory = [
            'title' => 'fakedirectory',
            'children' => [],
            'thumbnail' => 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/folder',
            'thumbnail_height' => 64,
            'thumbnail_width' => 64,
            'path' => 'fakedirectory/',
        ];
        $this->assertEquals($directory, $result['list'][0]);
        $directory = [
            'title' => 'testdirectory',
            'children' => [],
            'thumbnail' => 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/folder',
            'thumbnail_height' => 64,
            'thumbnail_width' => 64,
            'path' => 'testdirectory/',
        ];
        $this->assertEquals($directory, $result['list'][1]);
        $this->assertEquals($result['list'][2]['title'], 'testfile.jpg');
        $this->assertEquals($result['list'][2]['size'], '645');
        $this->assertEquals($result['list'][2]['path'], 'testfile.jpg');
        $this->assertEquals($result['list'][2]['thumbnail_height'], 64);
        $this->assertEquals($result['list'][2]['thumbnail_width'], 64);
        $this->assertEquals($result['list'][2]['source'], 'testfile.jpg');
        $this->assertEquals($result['list'][2]['thumbnail'], 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/image');
        $this->assertFalse($result['manage']);
        $this->assertTrue($result['dynload']);
        $this->assertTrue($result['nologin']);
        $this->assertFalse($result['nosearch']);

        $result = $repo->get_listing('testdirectory');
        $this->assertCount(6, $result);
        $path = ['name' => 'testbucket', 'path' => 'testdirectory'];
        $this->assertEquals($path, $result['path'][0]);
        $list = [
            'title' => 'testdirectory',
            'children' => [],
            'thumbnail' => 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/folder',
            'thumbnail_height' => 64,
            'thumbnail_width' => 64,
            'path' => 'testdirectory/',
        ];
        $this->assertEquals($list, $result['list'][0]);
        $this->assertFalse($result['manage']);
        $this->assertTrue($result['dynload']);
        $this->assertTrue($result['nologin']);
        $this->assertFalse($result['nosearch']);

        $result = $repo->get_listing('testdirectory/');
        $path = ['name' => 'testbucket', 'path' => 'testdirectory/'];
        $this->assertEquals($path, $result['path'][0]);
        $this->assertEquals($result['list'][0]['title'], 'testfile.jpg');
        $this->assertEquals($result['list'][0]['size'], '7237');
        $this->assertEquals($result['list'][0]['path'], 'testdirectory/testfile.jpg');
        $this->assertEquals($result['list'][0]['thumbnail_height'], 64);
        $this->assertEquals($result['list'][0]['thumbnail_width'], 64);
        $this->assertEquals($result['list'][0]['source'], 'testdirectory/testfile.jpg');
        $this->assertEquals($result['list'][0]['thumbnail'], 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/image');

        $this->assertEquals($repo->get_file_source_info('test.png'), 's3://testbucket/test.png');
        $s1 = 'testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest.jpg';
        $s2 = 's3://testbucket/testtesttesttesttesttesttesttestte...';
        $this->assertEquals($repo->get_file_source_info($s1), $s2);
    }

    /**
     * Test search.
     */
    public function test_search(): void {
        $userid = $this->getDataGenerator()->create_user()->id;
        $context = context_user::instance($userid);
        $repo = $this->create_repo($context);

        $result = $repo->search('filesearch', 2);
        $this->assertCount(0, $result['list']);
        $this->assertEquals($result['page'], 2);
        $this->assertTrue($result['dynload']);
        $this->assertEquals($result['pages'], 0);

        $result = $repo->search('test');
        $this->assertCount(2, $result['list']);
        $list = $result['list'];
        $this->assertEquals($list[0]['title'], 'testdirectory');
        $this->assertEquals($list[0]['children'], []);
        $this->assertEquals($list[0]['thumbnail'], 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/folder');
        $this->assertEquals($list[0]['thumbnail_height'], 64);
        $this->assertEquals($list[0]['thumbnail_width'], 64);
        $this->assertEquals($list[0]['path'], 'testdirectory/');
        $this->assertEquals($list[1]['title'], 'testfile.jpg');
        $this->assertEquals($list[1]['size'], '645');
        $this->assertEquals($list[1]['path'], 'testfile.jpg');
        $this->assertEquals($list[1]['thumbnail_height'], 64);
        $this->assertEquals($list[1]['thumbnail_width'], 64);
        $this->assertEquals($list[1]['source'], 'testfile.jpg');
        $this->assertEquals($list[1]['thumbnail'], 'https://www.example.com/moodle/theme/image.php/boost/core/1/f/image');
        $this->assertTrue($result['dynload']);
        $this->assertEquals($result['pages'], 0);
        $this->assertEquals($result['page'], 1);
    }

    /**
     * Test no access_key.
     */
    public function test_noaccess_key(): void {
        $courseid = $this->getDataGenerator()->create_course()->id;
        $repo = $this->create_repo(context_course::instance($courseid));
        $repo->set_option(['access_key' => null]);
        $this->expectException('moodle_exception');
        $repo->get_listing();
    }

    /**
     * Test get file in user context.
     */
    public function test_getfile(): void {
        global $USER;
        $context = context_user::instance($USER->id);
        $repo = $this->create_repo($context);
        $draft = file_get_unused_draft_itemid();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => $context->id,
                       'itemid' => $draft, 'filename' => 'testfile.jpg', 'filepath' => '/', ];
        get_file_storage()->create_file_from_string($filerecord, 'test content');
        $result = $repo->get_file('testfile.jpg');
        $this->assertEquals('testfile.jpg', $result['url']);
        $this->assertStringStartsWith('/tmp/requestdir', $result['path']);

        $this->expectException('moodle_exception');
        $repo->get_file('/otherfilename.txt');
    }

    /**
     * Test get url in user context.
     */
    public function test_getlink(): void {
        global $USER;
        $context = context_user::instance($USER->id);
        $userid = $context->id;
        $repo = $this->create_repo($context);
        $url = $repo->get_link('testdir/tst.jpg');
        $this->assertStringStartsWith("https://www.example.com/moodle/pluginfile.php/{$userid}/repository_s3bucket/s3/", $url);
        $this->assertStringEndsWith('/testdir/tst.jpg', $url);
        $this->assertStringContainsString('/s3/', $url);

        $context = context_system::instance();
        $repo = $this->create_repo($context);
        $url = $repo->get_link('testfile.jpg');
        $this->assertStringStartsWith('https://www.example.com/moodle/pluginfile.php/1/repository_s3bucket/s3/', $url);
        $this->assertStringEndsWith('/testfile.jpg', $url);
    }

    /**
     * Tests other files.
     */
    public function test_local_other(): void {
        global $CFG;
        require_once($CFG->dirroot . '/repository/s3bucket/db/access.php');
        require_once($CFG->dirroot . '/repository/s3bucket/tests/coverage.php');
    }

    /**
     * Tests localstack.
     */
    public function test_localstack_available(): void {
        $this->assertFalse(\repository_s3bucket::no_localstack());
        $s = 'http://localhost:4567/testbucket/testfile.jpg';
        $this->assertTrue(\repository_s3bucket::no_localstack($s));
        $s = 'http://localhost/testbucket/testfile.jpg';
        $this->assertTrue(\repository_s3bucket::no_localstack($s));
    }

    /**
     * Create localstack repository.
     * @param context $context Context
     */
    private function create_repo($context): \repository_s3bucket {
        $repository = new \repository_s3bucket($this->repo, $context);
        $repository->set_option(['endpoint' => 'http://localhost:4566']);
        $repository->set_option(['region' => 'HTTp://localhost:4566']);
        $repository->set_option(['secret_key' => 'test']);
        $repository->set_option(['bucket_name' => 'testbucket']);
        $repository->set_option(['access_key' => 'test']);
        return $repository;
    }
}
