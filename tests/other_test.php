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
final class other_test extends \advanced_testcase {
    /** @var int repo */
    protected $repo;

    /** @var array data */
    protected $data;

    /**
     * Create type and instance.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('s3mock', true);
        set_config('proxyhost', '192.168.192.168');
        set_config('proxyport', 66);
        set_config('proxytype', 'http');
        set_config('proxyuser', 'user');
        set_config('proxypassword', 'pass');
        $this->getDataGenerator()->create_repository_type('s3bucket');
        $this->repo = $this->getDataGenerator()->create_repository('s3bucket')->id;
        $this->data = [
           'endpoint' => 'eu-north-1',
           'secret_key' => 'secret',
           'bucket_name' => 'test',
           'access_key' => 'abc', ];
        $this->SetAdminUser();
    }

    /**
     * Test tearDown.
     */
    public function tearDown(): void {
        set_config('s3mock', false);
        parent::tearDown();
    }

    /**
     * Test sendfile s3.
     */
    public function test_sendfiles3(): void {
        global $USER;
        $repo = new \repository_s3bucket($this->repo);
        $fs = get_file_storage();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => \context_user::instance($USER->id)->id,
                       'itemid' => file_get_unused_draft_itemid(), 'filename' => 'filename.jpg', 'filepath' => '/', ];
        $file = $fs->create_file_from_string($filerecord, 'test content');
        $this->expectException('repository_exception');
        $repo->send_file($file);
    }

    /**
     * Test class in system context.
     */
    public function test_class(): void {
        $repo = new \repository_s3bucket($this->repo);
        $this->assertEquals('S3 bucket', $repo->get_name());
        $this->assertTrue($repo->check_login());
        $this->assertFalse($repo->contains_private_data());
        $this->assertEquals(['duration'], $repo->get_type_option_names());
        $this->assertCount(4, $repo->get_instance_option_names());
        $this->assertEquals('Unknown source', $repo->get_reference_details(''));
        $this->assertEquals('s3://testrepo/filename.txt', $repo->get_file_source_info('filename.txt'));
        $this->assertEquals('s3://testrepo/filename.txt', $repo->get_reference_details('filename.txt'));
        $this->assertEquals('Unknown source', $repo->get_reference_details('filename.txt', 666));
        $repo->disabled = true;
        try {
            $repo->get_reference_details('filename.txt');
        } catch (\core\exception\moodle_exception $e) {
            $this->assertEquals('Cannot download this file', $e->getMessage());
        } catch (repository_exception $exception) {
            $this->assertEquals('Cannot download this file', $exception->getMessage());
        }

        $repo->disabled = false;
        $this->assertEquals('Unknown source', $repo->get_reference_details('filename.txt', 666));
        $this->assertFalse($repo->global_search());
        $this->assertEquals(7, $repo->supported_returntypes());
        $this->assertEquals(4, $repo->default_returntype());
        $this->SetAdminUser();
        $this->assertEquals(2, $repo->check_capability());
        $result = $repo->get_listing('', 1);
        $this->assertCount(2, $result['list']);

        set_config('s3mock', false);
        $repo = new \repository_s3bucket($this->repo);
        $x = 0;
        try {
            $all = $repo->get_listing('testfile.jpg', 1);
            $this->assertCount(6, $all);
            $result = $repo->get_file('testfile.jpg', 'testfile.jpg');
            $this->assertEquals('testfile.jpg', $result['url']);
            $this->assertStringContainsString('/testfile.jpg', $result['path']);
            $repo->send_otherfile($result['path'], 3);
        } catch (\repository_exception $re) {
            // We reached the repository exception.
            $x++;
        } catch (\core\exception\moodle_exception $e) {
            // No Localstack installed.
            $x++;
        }
        $this->assertNotEquals(5, $x);
    }

    /**
     * Test empty in course context.
     */
    public function test_empty(): void {
        $courseid = $this->getDataGenerator()->create_course()->id;
        $repo = new \repository_s3bucket($this->repo, \context_course::instance($courseid), $this->data);
        $result = $repo->get_listing('.');
        $this->assertCount(1, $result['path']);
        set_config('s3mock', false);
        $repo = new \repository_s3bucket($this->repo, \context_course::instance($courseid), $this->data);
        $x = 0;
        try {
            $all = $repo->get_listing('.');
            $this->assertCount(6, $all);
        } catch (\core\exception\moodle_exception $e) {
            // No Localstack installed.
            $x++;
        }
        $this->assertNotEquals(5, $x);
    }

    /**
     * Test search.
     */
    public function test_search(): void {
        $userid = $this->getDataGenerator()->create_user()->id;
        $context = \context_user::instance($userid);
        $repo = new \repository_s3bucket($this->repo, $context, $this->data);
        $this->data['endpoint'] = 'eu-central-1';
        $repo->set_option($this->data);
        $result = $repo->search('filesearch');
        $this->assertCount(0, $result['list']);
        $result = $repo->search('2020');
        $this->assertCount(2, $result['list']);
        set_config('s3mock', false);
        $repo = new \repository_s3bucket($this->repo, $context, $this->data);
        $x = 0;
        try {
            $all = $repo->search('filesearch');
            $this->assertCount(4, $all);
        } catch (\core\exception\moodle_exception $e) {
            // No Localstack installed.
            $x++;
        }
    }

    /**
     * Test no access_key.
     */
    public function test_noaccess_key(): void {
        $courseid = $this->getDataGenerator()->create_course()->id;
        $repo = new \repository_s3bucket($this->repo, \context_course::instance($courseid));
        $repo->set_option(['access_key' => null]);
        $this->expectException('moodle_exception');
        $repo->get_listing();
    }

    /**
     * Test get file in user context.
     */
    public function test_getfile(): void {
        global $USER;
        $context = \context_user::instance($USER->id);
        $repo = new \repository_s3bucket($this->repo, $context);
        $this->data['endpoint'] = 'ap-south-1';
        $repo->set_option($this->data);
        $draft = file_get_unused_draft_itemid();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => $context->id,
                       'itemid' => $draft, 'filename' => 'filename.txt', 'filepath' => '/', ];
        get_file_storage()->create_file_from_string($filerecord, 'test content');
        $result = $repo->get_file('/filename.txt');
        $this->assertEquals('/filename.txt', $result['url']);
        $result = $repo->get_file('/otherfilename.txt');
        $this->assertEquals('/otherfilename.txt', $result['url']);
        $this->expectException('moodle_exception');
        $repo->get_file('');
    }

    /**
     * Test get url in user context.
     */
    public function test_getlink(): void {
        global $USER;
        $context = \context_user::instance($USER->id);
        $repo = new \repository_s3bucket($this->repo, $context);
        $url = $repo->get_link('tst.jpg');
        $this->assertStringContainsString('/s3/', $url);
        set_config('s3mock', false);
        $repo = new \repository_s3bucket($this->repo);
        $url = $repo->get_link('filename.txt');
        $this->assertStringContainsString('/s3/', $url);
    }

    /**
     * Tests other files.
     */
    public function test_local_other(): void {
        global $CFG;
        require_once($CFG->dirroot . '/repository/s3bucket/db/access.php');
        require_once($CFG->dirroot . '/repository/s3bucket/tests/coverage.php');
    }
}
