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
 * Amazon S3bucket behat step test
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace repository_s3bucket;

/**
 * Amazon S3bucket behat step tests
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behatstep_test extends \advanced_testcase {
    /**
     * Basic test of step.
     *
     * @return void
     * @covers \behat_repository_s3bucket
     */
    public function test_do_step(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        $this->SetAdminUser();
        require_once($CFG->dirroot . '/repository/s3bucket/tests/behat/behat_repository_s3bucket.php');
        $this->assertFalse($DB->record_exists('repository', ['type' => 's3bucket']));
        $beha = new \behat_repository_s3bucket();
        $beha->i_enable_repository('s3bucket');
        $this->assertTrue($DB->record_exists('repository', ['type' => 's3bucket']));
    }
}
