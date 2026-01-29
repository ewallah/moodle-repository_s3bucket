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
use core\exception\moodle_exception;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');
// @codeCoverageIgnoreEnd

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

    #[\Override]
    public function get_listing($path = '.', $page = 1) {
        global $OUTPUT;
        $diricon = $OUTPUT->image_url(file_folder_icon())->out();
        $bucket = $this->get_option('bucket_name');
        $place = [['name' => $bucket, 'path' => $path]];
        $epath = ($path === '') ? '.' : $path . '/';
        $options = [
            'Bucket' => $bucket,
            'Prefix' => $path,
            'EncodingType' => 'url',
            'Delimiter' => '/', ];
        $results = [];
        $files = [];
        $s3 = $this->create_s3();
        try {
            $results = $s3->listObjectsV2($options);
            // @codeCoverageIgnoreStart
        } catch (\Exception $exception) {
            $this->throw_error($exception->getMessage());
            // @codeCoverageIgnoreEnd
        }

        $items = $results->search('CommonPrefixes[].{Prefix: Prefix}');
        if ($items) {
            foreach ($items as $item) {
                $files[] = [
                      'title' => basename($item['Prefix']),
                      'children' => [],
                      'thumbnail' => $diricon,
                      'thumbnail_height' => 64,
                      'thumbnail_width' => 64,
                      'path' => $item['Prefix'], ];
            }
        }
        $items = $results->search($this->filesearch(''));
        if ($items) {
            foreach ($items as $item) {
                $pathinfo = pathinfo($item['Key']);
                if ($pathinfo['dirname'] == $epath || $pathinfo['dirname'] . '//' === $epath) {
                    $files[] = [
                       'title' => $pathinfo['basename'],
                       'size' => $item['Size'],
                       'path' => $item['Key'],
                       'datemodified' => date_timestamp_get($item['LastModified']),
                       'thumbnail_height' => 64,
                       'thumbnail_width' => 64,
                       'source' => $item['Key'],
                       'thumbnail' => $OUTPUT->image_url(file_extension_icon($pathinfo['basename']))->out(), ];
                }
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

    #[\Override]
    public function search($q, $page = 1) {
        global $OUTPUT;
        $diricon = $OUTPUT->image_url(file_folder_icon())->out();
        $bucket = $this->get_option('bucket_name');
        $options = [
            'Bucket' => $bucket,
            'EncodingType' => 'url',
            'Delimiter' => '/', ];
        $results = [];
        $files = [];
        $s3 = $this->create_s3();
        try {
            $results = $s3->listObjectsV2($options);
            // @codeCoverageIgnoreStart
        } catch (\Exception $exception) {
            $this->throw_error($exception->getMessage());
            // @codeCoverageIgnoreEnd
        }

        $dirsearch = sprintf("CommonPrefixes[?contains(Prefix, '%s')].{Prefix: Prefix}", $q);
        $items = $results->search($dirsearch);
        if ($items) {
            foreach ($items as $item) {
                $files[] = [
                    'title' => basename($item['Prefix']),
                    'children' => [],
                    'thumbnail' => $diricon,
                    'thumbnail_height' => 64,
                    'thumbnail_width' => 64,
                    'path' => $item['Prefix'],
                ];
            }
        }

        $items = $results->search($this->filesearch($q));
        if ($items) {
            foreach ($items as $item) {
                $pathinfo = pathinfo($item['Key']);
                $files[] = [
                   'title' => $pathinfo['basename'],
                   'size' => $item['Size'],
                   'path' => $item['Key'],
                   'datemodified' => date_timestamp_get($item['LastModified']),
                   'thumbnail_height' => 64,
                   'thumbnail_width' => 64,
                   'source' => $item['Key'],
                   'thumbnail' => $OUTPUT->image_url(file_extension_icon($pathinfo['basename']))->out(),
                ];
            }
        }

        return ['list' => $files, 'dynload' => true, 'pages' => 0, 'page' => $page];
    }

    /**
     * Repository method to serve the out file
     *
     * @param string $search The text to search for
     * @return string The code we use to search for files
     */
    private function filesearch(string $search): string {
        $s = "Contents[";
        $s .= "?StorageClass != 'DEEP_ARCHIVE'";
        $s .= " && StorageClass != 'GLACIER' ";
        $s .= " && contains(Key, '" . $search . "')]";
        return $s . ".{Key: Key, Size: Size, LastModified: LastModified}";
    }

    #[\Override]
    public function send_file($storedfile, $lifetime = null, $filter = 0, $forcedownload = true, ?array $options = null): void {
        $duration = get_config('s3bucket', 'duration');
        $this->send_otherfile($storedfile->get_reference(), sprintf('+%s minutes', $duration));
    }

    /**
     * Repository method to serve the out file
     *
     * @param string $reference the filereference
     * @param string $lifetime Number of seconds before the file should expire from caches
     */
    public function send_otherfile($reference, $lifetime): void {
        if ($reference != '') {
            $s3 = $this->create_s3();
            $options = [
               'Bucket' => $this->get_option('bucket_name'),
               'Key' => $reference,
               'ResponseContentDisposition' => 'attachment', ];
            try {
                $result = $s3->getCommand('GetObject', $options);
                $req = $s3->createPresignedRequest($result, $lifetime);
                // @codeCoverageIgnoreStart
            } catch (\Exception $e) {
                $this->throw_error($e->getMessage());
                // @codeCoverageIgnoreEnd
            }

            $uri = $req->getUri()->__toString();
            if (!PHPUNIT_TEST) {
                // @codeCoverageIgnoreStart
                header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
                header('Pragma: no-cache');
                header('Content-Type: ' . get_mimetype_description(['filename' => $reference]));
                header(sprintf('Content-Disposition: attachment; filename="%s"', $reference));
                header('Location: ' . $uri);
                die;
                // @codeCoverageIgnoreEnd
            }
        }

        throw new \repository_exception('cannotdownload', 'repository');
    }

    /**
     * This method throws a repository exception.
     *
     * @param string $message Optional message
     */
    private function throw_error(string $message = ''): void {
        throw new moodle_exception('errorwhilecommunicatingwith', 'repository', '', $this->get_name(), $message);
    }

    #[\Override]
    public function get_link($url) {
        $cid = $this->context->id;
        $path = pathinfo($url);
        $file = $path['basename'];
        $directory = $path['dirname'];
        $directory = $directory == '.' ? '/' : '/' . $directory . '/';

        return \moodle_url::make_pluginfile_url($cid, 'repository_s3bucket', 's3', $this->id, $directory, $file)->out();
    }

    #[\Override]
    public function get_reference_details($reference, $filestatus = 0) {
        if ($this->disabled) {
            throw new \repository_exception('cannotdownload', 'repository');
        }

        if ($filestatus == 666) {
            $reference = '';
        }

        return $this->get_file_source_info($reference);
    }

    #[\Override]
    public function get_file($filepath, $file = '') {
        $path = $this->prepare_file($file);
        $s3 = $this->create_s3();
        $options = [
           'Bucket' => $this->get_option('bucket_name'),
           'Key' => $filepath,
           'SaveAs' => $path, ];
        try {
            $s3->getObject($options);
        } catch (\Exception $exception) {
            // @codeCoverageIgnoreStart
            $this->throw_error($exception->getMessage());
            // @codeCoverageIgnoreEnd
        }

        return ['path' => $path, 'url' => $filepath];
    }

    #[\Override]
    public function get_file_source_info($filepath) {
        if (empty($filepath)) {
            return get_string('unknownsource', 'repository');
        }

        return $this->get_short_filename('s3://' . $this->get_option('bucket_name') . '/' . $filepath, 50);
    }

    #[\Override]
    public static function get_type_option_names() {
        return ['duration'];
    }

    #[\Override]
    public static function type_config_form($mform, $classname = 'repository'): void {
        $duration = get_config('s3bucket', 'duration') ?? '2';
        $duration = intval($duration);

        $choices = [1 => 1, 2 => 2, 3 => 10, 4 => 15, 5 => 30, 6 => 60];
        $mform->addElement('select', 'duration', get_string('duration', $classname), $choices);
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', $duration);
    }

    #[\Override]
    public static function get_instance_option_names() {
        return ['access_key', 'secret_key', 'endpoint', 'bucket_name'];
    }

    #[\Override]
    public static function instance_config_form($mform): void {
        global $CFG;
        parent::instance_config_form($mform);
        $strrequired = get_string('required');
        $textops = ['maxlength' => 255, 'size' => 50];
        $endpointselect = [];
        $all = require($CFG->libdir . '/aws-sdk/src/data/endpoints.json.php');
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

        $boptions = ['placeholder' => 'us-east-1', 'tags' => true];
        $mform->addElement('autocomplete', 'endpoint', get_string('endpoint', 'repository_s3'), $endpointselect, $boptions);
        $mform->setDefault('endpoint', 'us-east-1');

        $mform->addRule('access_key', $strrequired, 'required', null, 'client');
        $mform->addRule('secret_key', $strrequired, 'required', null, 'client');
        $mform->addRule('bucket_name', $strrequired, 'required', null, 'client');
    }

    #[\Override]
    public static function instance_form_validation($mform, $data, $errors) {
        if (!isset($data['access_key'])) {
            $errors[] = get_string('missingparam', 'error', get_string('access_key', 'repository_s3'));
        }

        if (!isset($data['secret_key'])) {
            $errors[] = get_string('missingparam', 'error', get_string('secret_key', 'repository_s3'));
        }

        if (!isset($data['bucket_name'])) {
            $errors[] = get_string('missingparam', 'error', get_string('bucketname', 'repository_s3bucket'));
        }

        if (!isset($data['endpoint'])) {
            $errors[] = get_string('missingparam', 'error', get_string('endpoint', 'repository_s3'));
        }

        // TODO: Check if the bucket exists.
        return $errors;
    }

    #[\Override]
    public function default_returntype() {
        return FILE_REFERENCE;
    }

    #[\Override]
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
                throw new moodle_exception('needaccesskey', 'repository_s3');
            }

            $arr = $this->addproxy([
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
     * @param array $settings Settings
     * @return array Array of settings
     */
    private function addproxy(array $settings): array {
        $settings['version'] = 'latest';
        $settings['signature_version'] = 'v4';

        $region = $settings['region'] ?? 'us-east-1';
        if (str_starts_with(strtolower($region), 'http')) {
            $settings['endpoint'] = $region;
            $settings['region'] = 'us-east-1';
        }

        return $settings;
    }

    #[\Override]
    public function contains_private_data() {
        return ($this->context->contextlevel === CONTEXT_USER);
    }

    /**
     * Do we have localstack available?
     *
     * @return bool True if no localstack installed.
     */
    public static function no_localstack(): bool {
        $curl = new \curl();
        $curl->head('http://localhost:4566/testbucket/testfile.jpg');
        $info = $curl->get_info();
        $installed = !empty($info['http_code']) && $info['http_code'] == 200;
        return !$installed;
    }
}

// @codeCoverageIgnoreStart
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
function repository_s3bucket_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    $handled = false;
    if ($filearea == 's3') {
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $handled = has_capability('moodle/course:view', $context);
        } else if ($context->contextlevel === CONTEXT_COURSE) {
            $handled = $course && has_capability('moodle/course:view', $context);
        } else if ($cm && has_capability('mod/' . $cm->modname . ':view', $context)) {
            $modinfo = get_fast_modinfo($course);
            $cmi = $modinfo->cms[$cm->id];
            $handled = ($cmi->uservisible && $cmi->is_visible_on_course_page());
        }
    }

    if ($handled) {
        $duration = get_config('s3bucket', 'duration');
        $itemid = array_shift($args);
        $reference = implode('/', $args);
        $repo = repository::get_repository_by_id($itemid, $context);
        $repo->send_otherfile($reference, sprintf('+%s minutes', $duration));
    }

    return false;
}

// @codeCoverageIgnoreEnd
