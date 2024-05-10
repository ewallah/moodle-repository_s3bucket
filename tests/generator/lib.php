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
 * Amazon S3bucket repository data generator
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Amazon S3bucket repository data generator
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_s3bucket_generator extends testing_repository_generator {
    /**
     * Fill in type record defaults.
     *
     * @param array $record
     * @return array
     */
    protected function prepare_type_record(array $record) {
        $record = parent::prepare_type_record($record);
        if (!isset($record['duration'])) {
            $record['duration'] = 5;
        }
        return $record;
    }

    /**
     * Fill in record defaults.
     *
     * @param array $record
     * @return array
     */
    protected function prepare_record(array $record) {
        $record = parent::prepare_type_record($record);
        $arr = [
            'contextid' => \context_system::instance()->id,
            'name' => 'S3 bucket',
            'access_key' => 'access',
            'secret_key' => 'secret',
            'endpoint' => 'us-east-1',
            'bucket_name' => 'testrepo', ];
        return array_merge($arr, $record);
    }
}
