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
 * This plugin is used to access s3bucket files
 *
 * @package    repository_s3bucket
 * @copyright  2017 Renaat Debleu (www.eWallah.net) (based on work by Dongsheng Cai)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * This is a repository class used to browse a Amazon S3 bucket.
 *
 * @package    repository_s3bucket
 * @copyright  2017 Renaat Debleu (www.eWallah.net) (based on work by Dongsheng Cai)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_s3bucket extends repository {

    /** @var _s3client s3 client object */
    private $_s3client;

    /**
     * Get S3 file list
     *
     * @param string $path this parameter can a folder name, or a identification of folder
     * @param string $page the page number of file list
     * @return array the list of files, including some meta infomation
     */
    public function get_listing($path = '.', $page = '') {
        global $OUTPUT;
        $s = $this->create_s3();
        $bucket = $this->get_option('bucket_name');
        $place = [['name' => $bucket, 'path' => $path]];
        $files = [];

        try {
            $results = $s->getPaginator('ListObjects', ['Bucket' => $bucket, 'Prefix' => $path]);
        } catch (S3Exception $e) {
            throw new moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $e->getMessage());
        }
        $path = ($path === '') ? '.' : $path . '/';

        foreach ($results as $result) {
            foreach ($result['Contents'] as $object) {
                $pathinfo = pathinfo($object['Key']);
                if ($object['Size'] == 0) {
                    if ($pathinfo['dirname'] == $path) {
                        $files[] = [
                            'title' => $pathinfo['basename'],
                            'children' => [],
                            'thumbnail' => $OUTPUT->image_url(file_folder_icon(90))->out(false),
                            'thumbnail_height' => 24,
                            'thumbnail_width' => 24,
                            'path' => $object['Key']];
                    }
                } else {
                    if ($pathinfo['dirname'] == $path or $pathinfo['dirname'] . '//' == $path) {
                        $files[] = [
                            'title' => $pathinfo['basename'],
                            'size' => $object['Size'],
                            'path' => $object['Key'],
                            'datemodified' => date_timestamp_get($object['LastModified']),
                            'thumbnail_height' => 24,
                            'thumbnail_width' => 24,
                            'source' => $object['Key'],
                            'thumbnail' => $OUTPUT->image_url(file_extension_icon($object['Key'], 90))->out(false)];
                    }
                }
            }
        }
        return ['list' => $files, 'path' => $place, 'manage' => false, 'dynload' => true, 'nologin' => true, 'nosearch' => true];
    }

    /**
     * Repository method to serve the referenced file
     *
     * @param stored_file $storedfile the file that contains the reference
     * @param int $lifetime Number of seconds before the file should expire from caches (null means $CFG->filelifetime)
     * @param int $filter 0 (default)=no filtering, 1=all files, 2=html files only
     * @param bool $forcedownload If true (default false), forces download of file rather than view in browser/plugin
     * @param array $options additional options affecting the file serving
     */
    public function send_file($storedfile, $lifetime = 6000, $filter = 0, $forcedownload = false, array $options = null) {
        $this->send_otherfile($storedfile->get_reference(), "+60 minutes");
    }

    /**
     * Repository method to serve the out file
     *
     * @param string $reference the filereference
     * @param string $lifetime Number of seconds before the file should expire from caches
     */
    public function send_otherfile($reference, $lifetime) {
        $s3 = $this->create_s3();
        $cmd = $s3->getCommand('GetObject', ['Bucket' => $this->get_option('bucket_name'), 'Key' => $reference]);
        $req = $s3->createPresignedRequest($cmd, "+60 minutes");
        header('Location: ' . (string)$req->getUri());
        die;
    }

    /**
     * This method derives a download link from the public share URL.
     *
     * @param string $url relative path to the chosen file
     * @return string the generated download link.
     */
    public function get_link($url) {
        $cid = context_system::instance()->id;
        return moodle_url::make_pluginfile_url($cid, 'repository_s3bucket', 's3', $this->id, '/', $url)->out();
    }

    /**
     * Get human readable file info from a the reference.
     *
     * @param string $reference
     * @param int $filestatus 0 - ok, 666 - source missing
     */
    public function get_reference_details($reference, $filestatus = 0) {
        if ($this->disabled) {
            throw new repository_exception('cannotdownload', 'repository');
        }
        if ($filestatus == 666) {
            $reference = '';
        }
        return $this->get_file_source_info($reference);
    }

    /**
     * Download S3 files to moodle
     *
     * @param string $filepath
     * @param string $file The file path in moodle
     * @return array with elements:
     *   path: internal location of the file
     *   url: URL to the source (from parameters)
     */
    public function get_file($filepath, $file = '') {
        $path = $this->prepare_file($file);
        $s = $this->create_s3();
        $bucket = $this->get_option('bucket_name');
        try {
            $s->getObject(['Bucket' => $bucket, 'Key' => $filepath, 'SaveAs' => $path]);
        } catch (S3Exception $e) {
            throw new moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $e->getMessage());
        }
        return ['path' => $path, $url => $url];
    }

    /**
     * Return the source information
     *
     * @param stdClass $filepath
     * @return string
     */
    public function get_file_source_info($filepath) {
        if (empty($filepath) or $filepath == '') {
            return get_string('unknownsource', 'repository');
        }
        return 's3://' . $this->get_option('bucket_name') . '/' . $filepath;
    }

    /**
     * Return names of the instance options.
     * By default: no instance option name
     *
     * @return array
     */
    public static function get_instance_option_names() {
        return ['access_key', 'secret_key', 'endpoint', 'bucket_name'];
    }

    /**
     * Edit/Create Instance Settings Moodle form
     *
     * @param moodleform $mform Moodle form (passed by reference)
     */
    public static function instance_config_form($mform) {
        global $CFG;
        parent::instance_config_form($mform);
        $strrequired = get_string('required');
        $textops = ['maxlength' => 255, 'size' => 50];
        $endpointselect = [];
        $endpointselect['s3.amazonaws.com'] = 's3.amazonaws.com';
        $all = require($CFG->dirroot . '/local/aws/sdk/Aws/data/endpoints.json.php');
        $endpoints = $all['partitions'][0]['regions'];
        foreach ($endpoints as $key => $value) {
            $endpointselect[$key] = $value['description'];
        }

        $mform->addElement('passwordunmask', 'access_key', get_string('access_key', 'repository_s3'), $textops);
        $mform->setType('access_key', PARAM_RAW_TRIMMED);
        $mform->addElement('passwordunmask', 'secret_key', get_string('secret_key', 'repository_s3'), $textops);
        $mform->setType('secret_key', PARAM_RAW_TRIMMED);
        $mform->addElement('text', 'bucket_name', get_string('bucketname', 'repository_s3bucket'), $textops);
        $mform->setType('bucket_name', PARAM_RAW_TRIMMED);
        $mform->addElement('select', 'endpoint', get_string('endpoint', 'repository_s3'), $endpointselect);
        $mform->setDefault('endpoint', 's3.amazonaws.com');

        $mform->addRule('access_key', $strrequired, 'required', null, 'client');
        $mform->addRule('secret_key', $strrequired, 'required', null, 'client');
        $mform->addRule('bucket_name', $strrequired, 'required', null, 'client');
    }

    /**
     * Validate repository plugin instance form
     *
     * @param moodleform $mform moodle form
     * @param array $data form data
     * @param array $errors errors
     * @return array errors
     */
    public static function instance_form_validation($mform, $data, $errors) {
        if (isset($data['access_key']) && isset($data['secret_key']) && isset($data['bucket_name'])) {
            $endpoint = self::fixendpoint($data['endpoint']);
            $credentials = ['key' => $data['access_key'], 'secret' => $data['secret_key']];
            $arr = ['version' => 'latest', 'signature_version' => 'v4', 'credentials' => $credentials, 'region' => $endpoint];
            $s3 = \Aws\S3\S3Client::factory($arr);
            try {
                $s3->getPaginator('ListObjects', ['Bucket' => $data['bucket_name'], 'Prefix' => '.']);
            } catch (S3Exception $e) {
                $errors[] = get_string('errorwhilecommunicatingwith', 'repository');
            }
        }
        return $errors;
    }

    /**
     * S3 plugins does support return links of files
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL | FILE_REFERENCE | FILE_EXTERNAL;
    }

    /**
     * Get S3
     *
     * @return s3
     */
    private function create_s3() {
        if ($this->_s3client == null) {
            $accesskey = $this->get_option('access_key');
            if (empty($accesskey)) {
                throw new moodle_exception('needaccesskey', 'repository_s3');
            }
            $credentials = ['key' => $accesskey, 'secret' => $this->get_option('secret_key')];
            $endpoint = self::fixendpoint($this->get_option('endpoint'));
            $arr = ['version' => 'latest', 'signature_version' => 'v4', 'credentials' => $credentials, 'region' => $endpoint];
            $this->_s3client = \Aws\S3\S3Client::factory($arr);
        }
        return $this->_s3client;
    }

    /**
     * Fix endpoint string
     *
     * @param string $endpoint point of entry
     * @return string fixedendpoint
     */
    private static function fixendpoint($endpoint) {
        if ($endpoint == 's3.amazonaws.com') {
            return 'us-east-1';
        } else {
            $endpoint = str_replace('.amazonaws.com', '', $endpoint);
            return str_replace('s3-', '', $endpoint);
        }
    }
}


/**
 * Serve the files from the repository_s3bucket file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param context $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return
 */
function repository_s3bucket_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    if ($filearea !== 's3') {
        return false;
    }
    $itemid = array_shift($args);
    $reference = array_pop($args); // The last item in the $args array.
    $repo = repository::get_repository_by_id($itemid, $context);
    $repo->check_capability();
    $repo->send_otherfile($reference, "+60 minutes");
}
