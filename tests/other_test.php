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
 * @copyright  2017 Renaat Debleu (www.eWallah.net) (based on work by Dongsheng Cai)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir. '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/s3bucket/lib.php');

/**
 * Other tests.
 *
 * @package    repository_s3bucket
 * @copyright  2017 Renaat Debleu (www.eWallah.net) (based on work by Dongsheng Cai)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass repository_s3bucket
 */
class repository_s3bucket_other_tests extends \advanced_testcase {

    /** @var int repo */
    protected $repo;

    /**
     * Create type and instance.
     */
    public function setUp() {
        $this->resetAfterTest(true);
        $type = 's3bucket';
        $this->getDataGenerator()->create_repository_type($type);
        $this->repo = $this->getDataGenerator()->create_repository($type)->id;
        $this->SetAdminUser();
    }

    /**
     * Test sendfile cf.
     */
    public function test_sendfilecf() {
        global $USER;
        $fs = get_file_storage();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => context_user::instance($USER->id)->id,
                       'itemid' => file_get_unused_draft_itemid(), 'filename' => 'filename.jpg', 'filepath' => '/'];
        $file = $fs->create_file_from_string($filerecord, 'test content');
        $repo = new \repository_s3bucket($this->repo);
        $this->expectException('InvalidArgumentException');
        $repo->send_file($file);
    }

    /**
     * Test sendfile s3.
     */
    public function test_sendfiles3() {
        global $USER;
        $repo = new \repository_s3bucket($this->repo);
        $repo->set_option(['cloudfront' => '']);
        $fs = get_file_storage();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => context_user::instance($USER->id)->id,
                       'itemid' => file_get_unused_draft_itemid(), 'filename' => 'filename.jpg', 'filepath' => '/'];
        $file = $fs->create_file_from_string($filerecord, 'test content');
        $this->expectException('InvalidArgumentException');
        $repo->send_file($file);
    }

    /**
     * Test class in system context.
     */
    public function test_class() {
        $repo = new \repository_s3bucket($this->repo);
        $this->assertEquals('s3bucket 1', $repo->get_name());
        $this->assertTrue($repo->check_login());
        $this->assertFalse($repo->contains_private_data());
        $this->assertCount(8, $repo->get_instance_option_names());
        $this->assertEquals('Unknown source', $repo->get_reference_details(''));
        $this->assertEquals('cf://testrepo/filename.txt', $repo->get_file_source_info('filename.txt'));
        $this->assertEquals('Unknown source', $repo->get_reference_details('filename.txt', 666));
        $this->assertEquals('cf://testrepo/filename.txt', $repo->get_reference_details('filename.txt'));
        $this->assertFalse($repo->global_search());
        $this->assertEquals(1, $repo->supported_returntypes());
        $this->SetAdminUser();
        $this->assertEquals(2, $repo->check_capability());
        $repo->set_option(['cloudfront' => '', 'cfpem' => '', 'cfkey' => '']);
        $this->assertEquals('s3bucket 1', $repo->get_name());
        $this->assertTrue($repo->check_login());
        $this->assertFalse($repo->contains_private_data());
        $this->assertCount(8, $repo->get_instance_option_names());
        $this->assertEquals('s3://testrepo/filename.txt', $repo->get_file_source_info('filename.txt'));
        $this->assertEquals('s3://testrepo/filename.txt', $repo->get_reference_details('filename.txt'));
        $this->assertFalse($repo->global_search());
        $this->assertEquals(6, $repo->supported_returntypes());
        $this->assertEquals(2, $repo->check_capability());
        $this->expectException('Aws\S3\Exception\S3Exception');
        $repo->get_listing();
    }

    /**
     * Test empty in course context.
     */
    public function test_empty() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $repo = new \repository_s3bucket($this->repo, $context);
        $this->expectException('Aws\S3\Exception\S3Exception');
        $repo->get_listing();
    }

    /**
     * Test get file in user context.
     */
    public function test_getfile() {
        global $USER;
        $context = context_user::instance($USER->id);
        $repo = new \repository_s3bucket($USER->id, $context);
        $repo->set_option(['endpoint' => 's3.eu-central-1.amazonaws.com', 'secret_key' => 'secret', 'bucket_name' => 'test',
                           'access_key' => 'abc']);
        $draft = file_get_unused_draft_itemid();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => $context->id,
                       'itemid' => $draft, 'filename' => 'filename.txt', 'filepath' => '/'];
        get_file_storage()->create_file_from_string($filerecord, 'test content');
        $this->expectException('Aws\S3\Exception\S3Exception');
        $repo->get_file('/filename.txt');
    }

    /**
     * Test instance form.
     */
    public function test_instance_form() {
        global $USER;
        $context = context_user::instance($USER->id);
        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => null, 'contextid' => $context->id];
        $mform = new repository_instance_form('', $para);
        $data = ['endpoint' => 's3.amazonaws.com', 'secret_key' => 'secret', 'bucket_name' => 'test',
                 'access_key' => 'abc'];
        $this->assertEquals([], repository_s3bucket::instance_form_validation($mform, $data, []));
        ob_start();
        $mform->display();
        $out = ob_get_clean();
        $this->assertContains('There are required fields in this form marked', $out);
    }

    /**
     * Test form.
     */
    public function test_form() {
        global $USER;
        $context = context_user::instance($USER->id);
        $page = new moodle_page();
        $page->set_context($context);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/repository/s3bucket/manage.php');
        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => null, 'contextid' => $context->id];
        $mform = new repository_instance_form('', $para);
        ob_start();
        $mform->display();
        $out = ob_get_clean();
        $this->assertContains('There are required fields', $out);
        $data = ['endpoint' => 's3.eu-central-1.amazonaws.com', 'secret_key' => 'secret', 'bucket_name' => 'test',
                 'access_key' => 'abc'];
        $this->assertEquals([], repository_s3bucket::instance_form_validation($mform, $data, []));
        ob_start();
        $mform->display();
        $out = ob_get_clean();
        $this->assertContains('value="s3.amazonaws.com" selected', $out);
        $this->assertEquals([], repository_s3bucket::instance_form_validation($mform, $data, []));
    }

    /**
     * Test access.
     */
    public function test_access() {
        global $CFG;
        require_once($CFG->dirroot . '/repository/s3bucket/db/access.php');
    }
}