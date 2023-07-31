# repository_s3bucket 

Instead of giving all users access to your complete S3 account, this plugin makes it
possible to give teachers and managers access to a specific S3 folder (bucket).

Multiple instances are supported, you only have to create a IAM user who has read access
to your S3 root folder, but read and write permissions to your S3 bucket.

## Dependencies

* Currently this plugin is using the [Amazon's SDK for PHP plugin](https://moodle.org/plugins/local_aws).

## Warnings

* Encrypted files or buckets are not supported.

## Theme support

This plugin is developed and tested on Moodle Core's Boost theme and Boost child themes, including Moodle Core's Classic theme.

## Database support

This plugin is developed and tested using

* MYSQL
* MariaDB
* PostgreSQL

## Plugin repositories

This plugin will be published and regularly updated on [Github](https://github.com/ewallah/moodle-repository_s3bucket)

## Bug and problem reports / Support requests

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.
Please report bugs and problems on [Github](https://github.com/ewallah/moodle-repository_s3bucket/issues)
We will do our best to solve your problems, but please note that we can't provide per-case support.

## Feature proposals

- Please issue feature proposals on [Github](https://github.com/ewallah/moodle-repository_s3bucket/issues)
- Please create pull requests on [Github](https://github.com/ewallah/moodle-repository_s3bucket/pulls)
- We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature proposals and not as feature requests.


[![Build Status](https://github.com/ewallah/moodle-repository_s3bucket/workflows/Tests/badge.svg)](https://github.com/ewallah/moodle-repository_s3bucket/actions)
[![Coverage Status](https://coveralls.io/repos/github/ewallah/moodle-repository_s3bucket/badge.svg?branch=main)](https://coveralls.io/github/ewallah/moodle-repository_s3bucket?branch=main)
