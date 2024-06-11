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
 * @copyright  eWallah (www.eWallah.net) (based on work by Dongsheng Cai)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * This is a repository class used to browse a Amazon S3 bucket.
 *
 * @package    repository_s3bucket
 * @copyright  eWallah (www.eWallah.net) (based on work by Dongsheng Cai)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_s3bucket extends repository {
    /** @var s3client s3 client object */
    private $s3client;

    /**
     * Get S3 file list
     *
     * @param string $path this parameter can a folder name, or a identification of folder
     * @param string $page the page number of file list
     * @return array the list of files, including some meta infomation
     */
    public function get_listing($path = '.', $page = 1) {
        global $OUTPUT;
        $diricon = $OUTPUT->image_url(file_folder_icon())->out(false);
        $bucket = $this->get_option('bucket_name');
        $place = [['name' => $bucket, 'path' => $path]];
        $epath = ($path === '') ? '.' : $path . '/';
        $options = [
            'Bucket' => $bucket,
            'FetchOwner' => false,
            'Prefix' => $path,
            'MaxKeys' => 1000,
            'EncodingType' => 'url',
            'Delimiter' => '/', ];
        $results = $files = [];
        $s3 = $this->create_s3();
        try {
            $results = $s3->listObjectsV2($options);
        } catch (\Exception $e) {
            throw new \moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $e->getMessage());
        }

        foreach ($results->search('CommonPrefixes[].{Prefix: Prefix}') as $item) {
             $files[] = [
                  'title' => basename($item['Prefix']),
                  'children' => [],
                  'thumbnail' => $diricon,
                  'thumbnail_height' => 64,
                  'thumbnail_width' => 64,
                  'path' => $item['Prefix'], ];
        }

        $filesearch = 'Contents[?StorageClass != \'DEEP_ARCHIVE\'';
        $filesearch .= ' && StorageClass != \'GLACIER\'';
        $filesearch .= ' && starts_with(Key, \'' . $path . '\')]';
        $filesearch .= '.{Key: Key, Size: Size, LastModified: LastModified}';
        foreach ($results->search($filesearch) as $item) {
            $pathinfo = pathinfo($item['Key']);
            if ($pathinfo['dirname'] == $epath || $pathinfo['dirname'] . '//' == $epath) {
                $files[] = [
                   'title' => $pathinfo['basename'],
                   'size' => $item['Size'],
                   'path' => $item['Key'],
                   'datemodified' => date_timestamp_get($item['LastModified']),
                   'thumbnail_height' => 64,
                   'thumbnail_width' => 64,
                   'source' => $item['Key'],
                   'thumbnail' => $OUTPUT->image_url(file_extension_icon($pathinfo['basename']))->out(false), ];
            }
        }
        return [
           'list' => $files,
           'path' => $place,
           'manage' => false,
           'dynload' => true,
           'nologin' => true,
           'nosearch' => false, ];
    }

    /**
     * Search through all the files.
     *
     * @param  String  $q    The query string.
     * @param  integer $page The page number.
     * @return array of results.
     */
    public function search($q, $page = 1) {
        global $OUTPUT;
        $diricon = $OUTPUT->image_url(file_folder_icon())->out(false);
        $bucket = $this->get_option('bucket_name');
        $options = [
            'Bucket' => $bucket,
            'FetchOwner' => false,
            'MaxKeys' => 1000,
            'EncodingType' => 'url',
            'Delimiter' => '/', ];
        $results = $files = [];
        $s3 = $this->create_s3();
        try {
            $results = $s3->listObjectsV2($options);
        } catch (\Exception $e) {
            throw new \moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $e->getMessage());
        }

        $dirsearch = 'CommonPrefixes[?contains(Prefix, \'' . $q . '\')].{Prefix: Prefix}';
        foreach ($results->search($dirsearch) as $item) {
             $files[] = [
                  'title' => basename($item['Prefix']),
                  'children' => [],
                  'thumbnail' => $diricon,
                  'thumbnail_height' => 64,
                  'thumbnail_width' => 64,
                  'path' => $item['Prefix'], ];
        }

        $filesearch = 'Contents[?StorageClass != \'DEEP_ARCHIVE\'';
        $filesearch .= ' && StorageClass != \'GLACIER\'';
        $filesearch .= ' && contains(Key, \'' . $q . '\')]';
        $filesearch .= '.{Key: Key, Size: Size, LastModified: LastModified}';
        foreach ($results->search($filesearch) as $item) {
            $pathinfo = pathinfo($item['Key']);
            $files[] = [
               'title' => $pathinfo['basename'],
               'size' => $item['Size'],
               'path' => $item['Key'],
               'datemodified' => date_timestamp_get($item['LastModified']),
               'thumbnail_height' => 64,
               'thumbnail_width' => 64,
               'source' => $item['Key'],
               'thumbnail' => $OUTPUT->image_url(file_extension_icon($pathinfo['basename']))->out(false),
            ];
        }
        return ['list' => $files, 'dynload' => true, 'pages' => 0, 'page' => $page];
    }

    /**
     * Repository method to serve the referenced file
     *
     * @param stored_file $storedfile the file that contains the reference
     * @param int $lifetime Number of seconds before the file should expire from caches (null means $CFG->filelifetime)
     * @param int $filter 0 (default)=no filtering, 1=all files, 2=html files only
     * @param bool $forcedownload If true (default true), forces download of file rather than view in browser/plugin
     * @param array|null $options additional options affecting the file serving
     */
    public function send_file($storedfile, $lifetime = null, $filter = 0, $forcedownload = true, ?array $options = null) {
        $duration = get_config('s3bucket', 'duration');
        $this->send_otherfile($storedfile->get_reference(), "+$duration minutes");
    }

    /**
     * Repository method to serve the out file
     *
     * @param string $reference the filereference
     * @param string $lifetime Number of seconds before the file should expire from caches
     */
    public function send_otherfile($reference, $lifetime) {
        if ($reference != '') {
            $s3 = $this->create_s3();
            $options = [
               'Bucket' => $this->get_option('bucket_name'),
               'Key' => $reference,
               'ResponseContentDisposition' => 'attachment', ];
            try {
                $result = $s3->getCommand('GetObject', $options);
                $req = $s3->createPresignedRequest($result, $lifetime);
            } catch (\Exception $e) {
                throw new \moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $e->getMessage());
            }
            $uri = $req->getUri()->__toString();
            $mimetype = get_mimetype_description(['filename' => $reference]);
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Pragma: no-cache');
            header("Content-Type: $mimetype\n");
            header("Content-Disposition: attachment; filename=\"$reference\"");
            header("Location: $uri");
            die;
        }
        throw new \repository_exception('cannotdownload', 'repository');
    }

    /**
     * This method creates a download link from the repository.
     *
     * @param string $url relative path to the chosen file
     * @return string the generated download link.
     */
    public function get_link($url) {
        $cid = $this->context->id;
        $path = pathinfo($url);
        $file = $path['basename'];
        $directory = $path['dirname'];
        $directory = $directory == '.' ? '/' : '/' . $directory . '/';
        return \moodle_url::make_pluginfile_url($cid, 'repository_s3bucket', 's3', $this->id, $directory, $file)->out();
    }

    /**
     * Get human readable file info from a the reference.
     *
     * @param string $reference
     * @param int $filestatus 0 - ok, 666 - source missing
     */
    public function get_reference_details($reference, $filestatus = 0) {
        if ($this->disabled) {
            throw new \repository_exception('cannotdownload', 'repository');
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
        $s3 = $this->create_s3();
        $options = [
           'Bucket' => $this->get_option('bucket_name'),
           'Key' => $filepath,
           'SaveAs' => $path, ];
        try {
            $s3->getObject($options);
        } catch (\Exception $e) {
            throw new \moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $e->getMessage());
        }
        return ['path' => $path, 'url' => $filepath];
    }

    /**
     * Return the source information
     *
     * @param stdClass $filepath
     * @return string
     */
    public function get_file_source_info($filepath) {
        if (empty($filepath) || $filepath == '') {
            return get_string('unknownsource', 'repository');
        }
        return $this->get_short_filename('s3://' . $this->get_option('bucket_name') . '/' . $filepath, 50);
    }

    /**
     * Return names of the general options.
     *
     * @return array
     */
    public static function get_type_option_names() {
        return ['duration'];
    }

    /**
     * Edit/Create Admin Settings Moodle form
     *
     * @param moodleform $mform Moodle form (passed by reference)
     * @param string $classname repository class name
     */
    public static function type_config_form($mform, $classname = 'repository') {
        $duration = get_config('s3bucket', 'duration') ?? 2;
        $choices = ['1' => 1, '2' => 2, '10' => 10, '15' => 15, '30' => 30, '60' => 60];
        $mform->addElement('select', 'duration', get_string('duration', $classname), $choices, $duration);
        $mform->setType('duration', PARAM_INT);
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
        $mform->setDefault('endpoint', 'us-east-1');

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
            $credentials = ['key' => $data['access_key'], 'secret' => $data['secret_key']];
            $arr = self::addproxy(['credentials' => $credentials, 'region' => $data['endpoint']]);
            $s3 = \Aws\S3\S3Client::factory($arr);
            try {
                // Check if the bucket exists.
                $s3->getCommand('HeadBucket', ['Bucket' => $data['bucket_name']]);
            } catch (\Exception $e) {
                $errors[] = get_string('errorwhilecommunicatingwith', 'repository');
            }
        }
        return $errors;
    }

    /**
     * Which return type should be selected by default.
     *
     * @return int
     */
    public function default_returntype() {
        return FILE_REFERENCE;
    }

    /**
     * S3 plugins does support return links of files
     *
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL | FILE_REFERENCE | FILE_EXTERNAL;
    }

    /**
     * Get S3
     *
     * @return s3
     */
    private function create_s3() {
        if ($this->s3client == null) {
            $accesskey = $this->get_option('access_key');
            if (empty($accesskey)) {
                throw new \moodle_exception('needaccesskey', 'repository_s3');
            }
            $arr = self::addproxy([
                'credentials' => ['key' => $accesskey, 'secret' => $this->get_option('secret_key')],
                'use_path_style_endpoint' => true,
                'region' => $this->get_option('endpoint'), ]);
            $this->s3client = \Aws\S3\S3Client::factory($arr);
        }
        return $this->s3client;
    }

    /**
     * Add proxy
     *
     * @param array $settings
     * @return array
     */
    private static function addproxy($settings) {
        global $CFG;
        $settings['version'] = 'latest';
        $settings['signature_version'] = 'v4';
        if (!empty($CFG->proxyhost) && !empty($CFG->proxytype) && $CFG->proxytype != 'SOCKS5') {
            $host = (empty($CFG->proxyport)) ? $CFG->proxyhost : $CFG->proxyhost . ':' . $CFG->proxyport;
            $type = (empty($CFG->proxytype)) ? 'http://' : $CFG->proxytype;
            $cond = (!empty($CFG->proxyuser) && !empty($CFG->proxypassword));
            $user = $cond ? $CFG->proxyuser . '.' . $CFG->proxypassword . '@' : '';
            $settings['request.options'] = ['proxy' => "$type$user$host"];
        }
        if (defined('BEHAT_SITE_RUNNING') || get_config('core', 's3mock')) {
            $mock = new \Aws\MockHandler();
            $day = new DateTime();
            $result = new \Aws\Result([
                'CommonPrefixes' => [['Prefix' => '2020_dir']],
                'Contents' => [['Key' => '2020_f.jpg', 'Size' => 15, 'StorageClass' => 'STANDARD', 'LastModified' => $day]], ]);
            $mock->append($result, $result);
            $settings['handler'] = $mock;
        }
        return $settings;
    }

    /**
     * Is this repository accessing private data?
     *
     * This function should return false to give access to private repository data.
     * @return boolean True when the repository accesses private external data.
     */
    public function contains_private_data() {
        return ($this->context->contextlevel === CONTEXT_USER);
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
function repository_s3bucket_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    $handled = false;
    if ($filearea == 's3') {
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $handled = has_capability('moodle/course:view', $context);
        } else if ($context->contextlevel === CONTEXT_COURSE) {
            $handled = $course && has_capability('moodle/course:view', $context);
        } else if ($cm) {
            if (has_capability('mod/' . $cm->modname . ':view', $context)) {
                $modinfo = get_fast_modinfo($course);
                $cmi = $modinfo->cms[$cm->id];
                $handled = ($cmi->uservisible && $cmi->is_visible_on_course_page());
            }
        }
    }
    if ($handled) {
        $duration = get_config('s3bucket', 'duration');
        $itemid = array_shift($args);
        $reference = implode('/', $args);
        $repo = repository::get_repository_by_id($itemid, $context);
        $repo->send_otherfile($reference, "+$duration minutes");
    }
    return false;
}
