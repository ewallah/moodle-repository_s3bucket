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
 * Amazon S3bucket repository data generator test
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace repository_s3bucket;

/**
 * Amazon S3bucket repository data generator test
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generator_test extends \advanced_testcase {
    /**
     * Create type and instance.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Basic test of creation of repository types.
     * @covers \repository_s3bucket
     * @covers \repository_s3bucket_generator
     */
    public function test_create_type(): void {
        global $DB;
        $repotype = $this->getDataGenerator()->create_repository_type('s3bucket');
        $this->assertEquals($repotype->type, 's3bucket', 'Unexpected name after creating repository type s3bucket');
        $this->assertTrue($DB->record_exists('repository', ['type' => 's3bucket', 'visible' => 1]));

        $caughtexception = false;
        try {
            $this->getDataGenerator()->create_repository_type('s3bucket');
        } catch (\repository_exception $e) {
            if ($e->getMessage() === 'This repository already exists') {
                $caughtexception = true;
            }
        }
        $this->assertTrue($caughtexception, "Repository type 's3bucket' should have already been enabled");
    }

    /**
     * Basic test of creation of repository instance.
     * @covers \repository_s3bucket
     * @covers \repository_s3bucket_generator
     */
    public function test_create_instance(): void {
        $this->getDataGenerator()->create_repository_type('s3bucket');
        $repo = $this->getDataGenerator()->create_repository('s3bucket');
        $this->assertEquals(0, $repo->userid);
    }

    /**
     * Installing repository tests
     * @covers \repository_s3bucket
     * @covers \repository_s3bucket_generator
     */
    public function test_install_repository(): void {
        $plugintype = new \repository_type('s3bucket');
        $pluginid = $plugintype->create(false);
        $this->assertIsInt($pluginid);
    }

    /**
     * Mocking generator
     * @covers \repository_s3bucket
     * @covers \repository_s3bucket_generator
     */
    public function test_class(): void {
        $s3generator = new \repository_s3bucket_generator($this->getDataGenerator());
        \phpunit_util::call_internal_method($s3generator, 'prepare_type_record', [['s3bucket']], 'repository_s3bucket_generator');
        \phpunit_util::call_internal_method($s3generator, 'prepare_record', [['s3bucket']], 'repository_s3bucket_generator');
    }
}
