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
class behat_repository_s3bucket_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     */
    protected function get_creatable_entities(): array {
        return [
            's3buckets' => [
                'datagenerator' => 's3bucket',
                'required' => ['access_key', 'secret_key', 'bucket_name', 'endpoint', 'contextlevel'],
            ],
        ];
    }
}
