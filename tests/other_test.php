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
 * @coversDefaultClass \repository_s3bucket
 */
class repository_s3bucket_other_tests extends \advanced_testcase {

    /** @var int repo */
    protected $repo;

    /**
     * Create type and instance.
     */
    public function setUp() {
        global $CFG;
        $this->resetAfterTest(true);
        $oriproxy = $CFG->proxyhost;
        $CFG->proxyhost = 'xxxxxxxxxxxxxxx.moodle.org';
        $oriport = $CFG->proxyport;
        $CFG->proxyport = '123';
        $oriuser = $CFG->proxyuser;
        $CFG->proxyuser = 'fakeproxy';
        $oripass = $CFG->proxypassword;;
        $CFG->proxypass = 'fakepass';
        $type = 's3bucket';
        $this->getDataGenerator()->create_repository_type($type);
        $this->repo = $this->getDataGenerator()->create_repository($type)->id;
        $this->SetAdminUser();
        $CFG->proxyhost = $oriproxy;
        $CFG->proxyport = $oriport;
        $CFG->proxyport = $oriuser;
        $CFG->proxyuser = $oripass;
    }

    /**
     * Test sendfile s3.
     */
    public function test_sendfiles3() {
        global $USER;
        $repo = new \repository_s3bucket($this->repo);
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
        $this->assertTrue($repo->contains_private_data());
        $this->assertCount(4, $repo->get_instance_option_names());
        $this->assertEquals('Unknown source', $repo->get_reference_details(''));
        $this->assertEquals('s3://testrepo/filename.txt', $repo->get_file_source_info('filename.txt'));
        $this->assertEquals('s3://testrepo/filename.txt', $repo->get_reference_details('filename.txt'));
        $this->assertEquals('Unknown source', $repo->get_reference_details('filename.txt', 666));
        $repo->disabled = true;
        try {
            $repo->get_reference_details('filename.txt');
        } catch (Exception $e) {
            $this->assertEquals('Cannot download this file', $e->getMessage());
        }
        $repo->disabled = false;
        $this->assertEquals('Unknown source', $repo->get_reference_details('filename.txt', 666));
        $this->assertFalse($repo->global_search());
        $this->assertEquals(7, $repo->supported_returntypes());
        $this->SetAdminUser();
        $this->assertEquals(2, $repo->check_capability());
        $result = $repo->get_listing('', 1);
        $this->assertCount(2, $result['list']);
    }

    /**
     * Test empty in course context.
     */
    public function test_empty() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $data = ['endpoint' => 's3.eu-central-1.amazonaws.com', 'secret_key' => 'secret', 'bucket_name' => 'test',
                 'access_key' => 'abc'];
        $repo = new \repository_s3bucket($this->repo, $context, $data);
        $result = $repo->get_listing('.');
        $this->assertCount(1, $result['path']);
    }

    /**
     * Test search.
     */
    public function test_search() {
        $user = $this->getDataGenerator()->create_user();
        $context = context_user::instance($user->id);
        $data = ['endpoint' => 's3.eu-central-1.amazonaws.com', 'secret_key' => 'secret', 'bucket_name' => 'test',
                 'access_key' => 'abc'];
        $repo = new \repository_s3bucket($this->repo, $context, $data);
        $result = $repo->search('filesearch');
        $this->assertCount(0, $result['list']);
        $result = $repo->search('2020');
        $this->assertCount(2, $result['list']);
    }

    /**
     * Test no access_key.
     */
    public function test_noaccess_key() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $repo = new \repository_s3bucket($this->repo, $context);
        $repo->set_option(['access_key' => null]);
        $this->expectException('moodle_exception');
        $repo->get_listing();
    }

    /**
     * Test get file in user context.
     */
    public function test_getfile() {
        global $USER;
        $context = context_user::instance($USER->id);
        $repo = new \repository_s3bucket($USER->id, $context);
        $repo->set_option(['endpoint' => 'ap-south-1', 'secret_key' => 'secret', 'bucket_name' => 'test',
                           'access_key' => 'abc']);
        $draft = file_get_unused_draft_itemid();
        $filerecord = ['component' => 'user', 'filearea' => 'draft', 'contextid' => $context->id,
                       'itemid' => $draft, 'filename' => 'filename.txt', 'filepath' => '/'];
        get_file_storage()->create_file_from_string($filerecord, 'test content');
        $result = $repo->get_file('/filename.txt');
        $this->assertEquals('/filename.txt', $result['url']);
    }

    /**
     * Test get url in user context.
     */
    public function test_getlink() {
        global $USER;
        $context = context_user::instance($USER->id);
        $repo = new \repository_s3bucket($USER->id, $context);
        $url = $repo->get_link('tst.jpg');
        $this->assertContains('/s3/', $url);
    }

    /**
     * Test get url in course context.
     */
    public function test_pluginfile() {
        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course->id]);
        $context = context_module::instance($url->cmid);
        $repo = new \repository_s3bucket($this->repo, $context);
        $cm = get_coursemodule_from_instance('url', $url->id);
        $this->assertFalse(repository_s3bucket_pluginfile($course, $cm, $context, 'h3', [$repo->id, 'tst.jpg'], true));
        try {
            repository_s3bucket_pluginfile($course, $cm, $context, 's3', [$repo->id, 'tst.jpg'], true);
        } catch (Exception $e) {
            $this->assertContains('Cannot modify header information - headers already sent', $e->getMessage());
        }
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
     * Test instance form with proxy.
     */
    public function test_instance_formproxy() {
        global $USER;
        set_config('proxyhost', '192.168.192.168');
        set_config('proxyport', 66);
        set_config('proxyuser', 'user');
        set_config('proxypassword', 'pass');
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
        $data = ['endpoint' => 's3.eu-central-1.amazonaws.com', 'secret_key' => 'secret', 'bucket_name' => 'test',
                 'access_key' => 'abc'];
        $page = new moodle_page();
        $page->set_context($context);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/repository/s3bucket/manage.php');
        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => null, 'contextid' => $context->id];
        $mform = new repository_instance_form('', $para);
        ob_start();
        $mform->display();
        $fromform = $mform->get_data();
        $out = ob_get_clean();
        $this->assertEquals('', $fromform);
        $this->assertContains('There are required fields', $out);
        $this->assertEquals([], repository_s3bucket::instance_form_validation($mform, $data, []));
        ob_start();
        $mform->display();
        $fromform = $mform->get_data();
        $out = ob_get_clean();
        $this->assertEquals('', $fromform);
        $this->assertContains('value="s3.amazonaws.com" selected', $out);
        $this->assertEquals([], repository_s3bucket::instance_form_validation($mform, $data, []));
    }
}