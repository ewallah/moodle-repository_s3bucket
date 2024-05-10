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
 * S3bucket upgrade
 *
 * @package   repository_s3bucket
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_repository_s3bucket_upgrade($oldversion) {
    global $DB;
    if ($oldversion < 2020110101) {
        if ($instances = $DB->get_fieldset_sql("SELECT instanceid FROM {repository_instance_config} WHERE name = 'bucket_name'")) {
            foreach ($instances as $instance) {
                $sql = "UPDATE {repository_instance_config}
                        SET value = :newvalue
                        WHERE instanceid = :instanceid
                        AND name = 'endpoint'
                        AND " . $DB->sql_like('value', "'s3.amazonaws.com'");
                $DB->execute($sql, ['instanceid' => $instance, 'newvalue' => 'us-east-1']);
            }
        }
        upgrade_plugin_savepoint(true, 2020110101, 'repository', 's3bucket');
    }
    return true;
}
