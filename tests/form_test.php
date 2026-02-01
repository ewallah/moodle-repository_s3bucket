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
 * Form tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_s3bucket;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Form tests.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 */
#[CoversClass(\repository_s3bucket::class)]
final class form_test extends \advanced_testcase {
    /** @var s3repository repo */
    protected $repo;

    /**
     * Create type and instance.
     */
    public function setUp(): void {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        require_once($CFG->dirroot . '/repository/lib.php');
        require_once($CFG->dirroot . '/repository/s3bucket/lib.php');
        parent::setUp();

        if (\repository_s3bucket::no_localstack()) {
            $this->markTestSkipped('Skipping as localstack is not installed.');
        }

        $this->resetAfterTest(true);
        $this->getDataGenerator()->create_repository_type('s3bucket');
        $repoid = $this->getDataGenerator()->create_repository('s3bucket')->id;
        $context = \context_system::instance();

        $repository = new \repository_s3bucket($repoid, $context);
        $repository->set_option(['endpoint' => 'http://localhost:4566']);
        $repository->set_option(['region' => 'HTTP://localhost:4566']);
        $repository->set_option(['secret_key' => 'test']);
        $repository->set_option(['bucket_name' => 'testbucket']);
        $repository->set_option(['access_key' => 'test']);

        $this->repo = $repository;
        $this->SetAdminUser();
    }

    /**
     * Test type config form.
     */
    public function test_type_config_form(): void {
        set_config('duration', 30, 'repository_s3bucket');
        $this->assertEquals(get_config('repository_s3bucket', 'duration'), 30);
        $context = \context_system::instance();
        $para = [
            'plugin' => 's3bucket',
            'action' => 'show',
            'pluginname' => 'repository_s3bucket',
            'contextid' => $context->id, ];
        $mform = new \repository_type_form('', $para);
        $this->assertEquals([], \repository_s3bucket::type_form_validation($mform, null, []));
        ob_start();
        $mform->display();
        $html = ob_get_clean();
        $cleaned = preg_replace('/\s+/', '', $html);
        $strs = [
            '<inputname="repos"type="hidden"value="s3bucket"/>',
            'Pre-SignedURLexpirationtime(inminutes)',
            '<optionvalue="1">1',
            '<optionvalue="2">2',
            '<optionvalue="3">10',
            '<optionvalue="4">15',
            '<optionvalue="5"selected>30',
            '<optionvalue="6">60',
            '<inputtype="checkbox"name="enablecourseinstances"class="form-check-input"value="1"id="id_enablecourseinstances">',
            '<inputtype="checkbox"name="enableuserinstances"class="form-check-input"value="1"id="id_enableuserinstances">',

        ];
        foreach ($strs as $str) {
            $this->assertStringContainsString($str, $cleaned);
        }
    }

    /**
     * Test instance form.
     */
    public function test_instance_form(): void {
        global $USER;

        $context = \context_user::instance($USER->id);
        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => null, 'contextid' => $context->id];
        $data = ['endpoint' => 'eu-west-2', 'secret_key' => 'secret', 'bucket_name' => 'test', 'access_key' => 'abc'];

        $mform = new \repository_instance_form('', $para);
        $errors = \repository_s3bucket::instance_form_validation($mform, $data, []);
        $this->assertEquals([], $errors);
        ob_start();
        $mform->display();
        $html = ob_get_clean();
        $cleaned = preg_replace('/\s+/', '', $html);
        $strs = [
            '<divclass="text-danger"title="Required"aria-hidden="true">',
            '<inputname="contextid"type="hidden"value="5"/>',
            '<inputtype="text"class="form-control"name="name"id="id_name"value=""size="30"aria-required="true"maxlength="100">',
            '<divclass="form-control-feedbackinvalid-feedback"id="id_error_name"></div>',
            '"data-fieldtype="passwordunmask"><divdata-passwordunmask="wrapper"data-passwordunmaskid="id_access_key">',
            '<inputtype="password"name="access_key"id="id_access_key"value=""',
            'class="form-controld-none"data-size="50"aria-required="true"maxlength="255"autocomplete="new-password"',
            '<optionvalue="us-east-1"selected>USEast(N.Virginia)</option>',
            'data-fieldtype="autocomplete"><selectclass="form-select"name="endpoint"',
            '<divclass="form-control-feedbackinvalid-feedback"id="id_error_endpoint">',
            '<divclass="form-control-feedbackinvalid-feedback"id="id_error_secret_key">',
            '<divclass="form-control-feedbackinvalid-feedback"id="id_error_access_key">',
            '<script>varskipClientValidation=false;</script>',
            '<inputtype="submit"class="btnbtn-secondary"name="cancel"',
            '<inputtype="submit"class="btnbtn-primary"name="submitbutton"id="id_submitbutton"value="Save">',
        ];
        foreach ($strs as $str) {
            $this->assertStringContainsString($str, $cleaned);
        }
    }

    /**
     * Test instance form error.
     */
    public function test_instance_form2(): void {
        // TODO: review tests.
        $context = \context_system::instance();
        $region = 'HTTP://localhost:4566';
        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => $this->repo->id, 'contextid' => $context->id];

        $mform = new \repository_instance_form('', $para);
        $this->assertCount(4, \repository_s3bucket::instance_form_validation($mform, [], []));

        $data = ['endpoint' => 'a', 'secret_key' => 'b', 'bucket_name' => 'c', 'access_key' => 'd'];
        $this->assertCount(0, \repository_s3bucket::instance_form_validation($mform, $data, []));
        $data = ['secret_key' => 'test', 'bucket_name' => 'testbucket', 'access_key' => 'test'];
        $this->assertCount(1, \repository_s3bucket::instance_form_validation($mform, $data, []));
        $data = ['region' => $region, 'secret_key' => 'none', 'bucket_name' => 'none', 'access_key' => 'none'];
        $this->assertCount(1, \repository_s3bucket::instance_form_validation($mform, $data, []));
    }


    /**
     * Test instance form with proxy.
     */
    public function test_instance_formproxy(): void {
        global $USER;
        $context = \context_user::instance($USER->id);
        $para = ['plugin' => 's3bucket', 'typeid' => null, 'instance' => null, 'contextid' => $context->id];
        $data = ['endpoint' => 'eu-west-2', 'secret_key' => 'secret', 'bucket_name' => 'test', 'access_key' => 'abc'];
        $mform = new \repository_instance_form('', $para);
        $this->assertEquals([], \repository_s3bucket::instance_form_validation($mform, $data, []));
        ob_start();
        $mform->display();
        $out = ob_get_clean();
        $this->assertStringContainsString('Required', $out);
    }

    /**
     * Test form.
     */
    public function test_form(): void {
        global $USER;
        $data = ['endpoint' => 'eu-west-2', 'secret_key' => 'secret', 'bucket_name' => 'test', 'access_key' => 'abc'];

        $context = \context_user::instance($USER->id);
        $page = new \moodle_page();
        $page->set_context($context);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/repository/s3bucket/manage.php');

        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => null, 'contextid' => $context->id];
        $mform = new \repository_instance_form('', $para);
        ob_start();
        $mform->display();
        $fromform = $mform->get_data();
        $out = ob_get_clean();
        $this->assertEquals('', $fromform);
        $this->assertStringContainsString('Required', $out);
        $this->assertEquals([], \repository_s3bucket::instance_form_validation($mform, $data, []));
        ob_start();
        $mform->display();
        $fromform = $mform->get_data();
        $out = ob_get_clean();
        $this->assertEquals('', $fromform);
        $this->assertStringContainsString('value="us-east-1" selected', $out);
        $data = ['endpoint' => 'eu-west-2', 'access_key' => 'abc'];
        $this->assertCount(2, \repository_s3bucket::instance_form_validation($mform, $data, []));
        $mform = new \repository_instance_form('', $para);
        $repo = new \repository_s3bucket($USER->id, $context);
        $para = ['plugin' => 's3bucket', 'typeid' => '', 'instance' => $repo, 'contextid' => $context->id];
        $mform = new \repository_instance_form('', $para);
        ob_start();
        $mform->display();
        $fromform = $mform->get_data();
        $out = ob_get_clean();
        $this->assertEquals('', $fromform);
        $this->assertStringContainsString('<option value="us-east-1" >US East (N. Virginia)</option>', $out);
    }
}
