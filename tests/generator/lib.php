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
    #[\Override]
    protected function prepare_type_record(array $record) {
        $record = parent::prepare_type_record($record);
        if (!isset($record['duration'])) {
            $record['duration'] = 5;
        }

        return $record;
    }

    #[\Override]
    protected function prepare_record(array $record) {
        $record = parent::prepare_type_record($record);
        $arr = [
            'contextid' => \context_system::instance()->id,
            'name' => 'S3 bucket',
            'access_key' => 'access',
            'secret_key' => 'secret',
            'endpoint' => 'us-east-1',
            'bucket_name' => 'testbucket', ];
        return array_merge($arr, $record);
    }

    /**
     * Create s3repository.
     *
     * @param array $record Record
     * @return array Prepared record
     */
    public function create_s3bucket($record) {
        global $DB;
        $userid = 2;
        switch ($record['contextlevel']) {
            case 'Course':
                // We do not have access to $COURSE, so we use the last created course.
                $context = \context_course::instance(array_key_last(get_courses()));
                break;
            case 'User':
                // We do not have access to $USER, so we fall back to role name.
                $who = str_replace('bucket', '', ($record['name']));
                $userid = $DB->get_field('user', 'id', ['username' => $who]);
                $context = \context_user::instance($userid);
                break;
            default:
                $context = \context_system::instance();
        }

        $id = $DB->get_field('repository', 'id', ['type' => 's3bucket']);
        if (!$id) {
            $plugintype = new repository_type('s3bucket', []);
            $id = $plugintype->create(false);
        }

        repository_s3bucket::create('s3bucket', $userid, $context, $record);
        return $record;
    }
}
