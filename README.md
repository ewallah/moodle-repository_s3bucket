# repository_s3bucket 


Instead of giving all users access to your complete S3 account, this plugin makes it
possible to give teachers and managers access to a specific S3 folder (bucket).

Multiple instances are supported, you only have to create a IAM user who has read access
to your S3 root folder, but read and write permissions to your S3 bucket.

## Warnings ##
1. This plugin is dependent on the local_aws plugin. If you want to use the latest sdk version, you will have to use the [eWallah version](https://github.com/ewallah/moodle-local_aws) that supports all new regions.
2. Encrypted files or buckets are not supported.


[![Build Status](https://github.com/ewallah/moodle-repository_s3bucket/workflows/Tests/badge.svg)](https://github.com/ewallah/moodle-repository_s3bucket/actions)
[![Coverage Status](https://coveralls.io/repos/github/ewallah/moodle-repository_s3bucket/badge.svg?branch=main)](https://coveralls.io/github/ewallah/moodle-repository_s3bucket?branch=main)
