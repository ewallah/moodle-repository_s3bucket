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
                            'thumbnail_height' => 64,
                            'thumbnail_width' => 64,
                            'path' => $object['Key']];
                    }
                } else {
                    if ($pathinfo['dirname'] == $path or $pathinfo['dirname'] . '//' == $path) {
                        $files[] = [
                            'title' => $pathinfo['basename'],
                            'size' => $object['Size'],
                            'path' => $object['Key'],
                            'datemodified' => date_timestamp_get($object['LastModified']),
                            'thumbnail_height' => 64,
                            'thumbnail_width' => 64,
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
    public function send_file($storedfile, $lifetime = 6, $filter = 0, $forcedownload = false, array $options = null) {
        $reference  = $storedfile->get_reference();
        $cloudfront = $this->get_option('cloudfront');
        $life = time() + $lifetime;
        if ($cloudfront) {
            $cfkey = $this->get_option('cfkey');
            $tmp = tempnam('/tmp', 'cf');
            $handle = fopen($tmp, "w");
            fwrite($handle, $this->get_option('cfpem'));
            fclose($handle);

            $cf = new \Aws\CloudFront\CloudFrontClient(
                ['profile' => 'default', 'version' => '2014-11-06', 'region' => 'us-east-1']);
            if ($this->get_option('cookie') === 1) {
                $cookie = $cf->getSignedCookie([
                    'url' => 'https://' . $cloudfront . '/' . $reference,
                    'expires' => $life,
                    'private_key' => $tmp,
                    'key_pair_id' => $cfkey
                ]);
                foreach ($cookie as $name => $value) {
                    setcookie($name, $value, 0, '', $cloudfront, true, true);
                }
            }
            $policy = $cf->getSignedUrl([
                'url' => 'https://' . $cloudfront . '/' . $reference,
                'expires' => $life,
                'private_key' => $tmp,
                'key_pair_id' => $cfkey
            ]);
            unlink($tmp);
            header('Location: ' . (string)$policy);
        } else {
            $s3 = $this->create_s3();
            $cmd = $s3->getCommand('GetObject', ['Bucket' => $this->get_option('bucket_name'), 'Key' => $reference]);
            $req = $s3->createPresignedRequest($cmd, "+1 minutes");
            header('Location: ' . (string)$req->getUri());
        }
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
     * @return array The local stored path
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
        return ['path' => $path];
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
        $protocol = ($this->get_option('cloudfront') == '') ? 's3' : 'cf';
        return $protocol . '://' . $this->get_option('bucket_name') . '/' . $filepath;
    }

    /**
     * S3 doesn't require login
     *
     * @return bool
     */
    public function check_login() {
        return true;
    }

    /**
     * S3 doesn't provide search
     *
     * @return bool
     */
    public function global_search() {
        return false;
    }

    /**
     * Return names of the instance options.
     * By default: no instance option name
     *
     * @return array
     */
    public static function get_instance_option_names() {
        return ['access_key', 'secret_key', 'endpoint', 'bucket_name', 'cloudfront', 'cfkey', 'cfpem', 'cookies'];
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

        $mform->addElement('text', 'cloudfront', get_string('cloudfront', 'repository_s3bucket'), $textops);
        $mform->addHelpButton('cloudfront', 'cloudfront', 'repository_s3bucket');
        $mform->setType('cloudfront', PARAM_URL);
        $mform->addRule('cloudfront', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addElement('passwordunmask', 'cfkey', get_string('cfkey', 'repository_s3bucket'), $textops);
        $mform->addHelpButton('cfkey', 'cfkey', 'repository_s3bucket');
        $mform->setType('cfkey', PARAM_RAW_TRIMMED);
        $mform->addElement('checkbox', 'cookies', get_string('cookies', 'repository_s3bucket'));
        $mform->addHelpButton('cookies', 'cookies', 'repository_s3bucket');
        $mform->addElement('textarea', 'cfpem', get_string('cfpem', 'repository_s3bucket'), ['cols' => 60, 'rows' => 30]);
        $mform->setType('type', PARAM_ALPHANUM);
        $mform->addHelpButton('cfpem', 'cfpem', 'repository_s3bucket');
        $mform->setType('cfpem', PARAM_RAW_TRIMMED);

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
                $s3->getPaginator('ListObjects', ['Bucket' => $data['bucket_name'], 'Prefix' => '']);
            } catch (S3Exception $e) {
                $errors[] = get_string('errorwhilecommunicatingwith', 'repository');
            }
        }
        if (isset($data['cloudfront'])) {
            // TODO: check cloudfront access.
            return $errors;
        }
        return $errors;
    }

    /**
     * S3 plugins doesn't support return links of files
     *
     * @return int
     */
    public function supported_returntypes() {
        if ($this->get_option('cloudfront') == '') {
            return FILE_INTERNAL | FILE_REFERENCE;
        } else {
            return FILE_EXTERNAL;
        }
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
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