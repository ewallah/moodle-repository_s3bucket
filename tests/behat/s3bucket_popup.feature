@repository @repository_s3bucket @javascript
Feature: S3 bucket behavour in popups

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username    |
      | student     |
      | teacher     |
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student     | C1     | student        |
      | teacher     | C1     | editingteacher |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |
    And I enable repository "s3bucket"
    And I log in as "admin"
    And I navigate to "Plugins > Repositories > Amazon S3 bucket" in site administration
    And I click on "Allow users to add a repository instance into the course" "checkbox"
    And I click on "Save" "button"
    And I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Repositories" in current page administration
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "coursebucket"
    And I set the field "Bucket name" to "coursebucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"

  @_file_upload
  Scenario Outline: A teacher can add files from the s3 bucket repository as a resouce
    When I am on "Course 1" course homepage with editing mode on
    And I add a "File" to section "1"
    And I set the following fields to these values:
      | Name                      | Myfile    |
      | id_display                | <display> |
      | Show size                 | 0         |
      | Show type                 | 0         |
      | Show upload/modified date | 0         |
    And I click on "Add..." "button"
    Then I should see "coursebucket"
    And I follow "coursebucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    And I press "Select this file"
    And I press "Save and return to course"
    And I log out
    And I log in as "student"
    And I am on "Course 1" course homepage
    Then I should see "Myfile"
    And I follow "Myfile"
    #And I am on the "Myfile" "resource activity" page
    # TODO: Fix InvalidAccessKeyId.
    # Then I should see "<result>"

    Examples:
      | display        | result |
      | Open           | InvalidAccessKeyId |
      | In pop-up      | InvalidAccessKeyId |
      | Force download | InvalidAccessKeyId |
