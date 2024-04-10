@repository @repository_s3bucket @javascript
Feature: S3 bucket repository is private in user context

  Background:
    # TODO: Why is this not working on github actions?
    Given the site is running Moodle version 4.3 or lower
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "users" exist:
      | username    |
      | student     |
      | teacher     |
      | facilitator |
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student     | C1     | student        |
      | teacher     | C1     | editingteacher |
      | teacher     | C2     | editingteacher |
      | facilitator | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name    | course | idnumber | section |
      | page     | PageA   | C1     | pageA    | 1       |
      | folder   | FolderB | C1     | folderB  | 1       |
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
    And I set the field "Name" to "Course 1 Bucket"
    And I set the field "Bucket name" to "coursebucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out

  @_file_upload
  Scenario: A teacher can add files from the s3 bucket repository in module context
    And I am on the "folderB" "folder activity editing" page logged in as teacher
    And I click on "Add..." "button" in the "Files" "form_row"
    And I should see "Course 1 Bucket"
    And I follow "Course 1 Bucket"
    And I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    Then I should see "Make a copy of the file"
    And I should see "Link to the file"
    And I should not see "Create an alias"
    And I should not see "Create an access controled link to the file"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save and display" "button"
    And I should see "2020_f.jpg"

  Scenario: A teacher cannot add files from the s3 bucket repository in profile
    Given I log in as "teacher"
    And I open my profile in edit mode
    And I click on "Add..." "button" in the "New picture" "form_row"
    Then I should not see "Course 1 Bucket"

  Scenario: A teacher cannot see the s3 bucket repository in another course context
    When I log in as "teacher"
    And I am on "Course 2" course homepage with editing mode on
    And I navigate to "Repositories" in current page administration
    Then I should not see "Course 1 Bucket"

  Scenario: Another teacher can see the s3 bucket repository in same course context
    Given I am on the "folderB" "folder activity editing" page logged in as teacher
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Course 1 Bucket"

  Scenario: A student cannot see a s3 course bucket
    Given the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | name                                | Test assignment name    |
      | intro                               | Submit your online text |
      | submissiondrafts                    | 0                       |
      | assignsubmission_onlinetext_enabled | 0                       |
      | assignsubmission_file_enabled       | 1                       |
      | assignsubmission_file_maxfiles      | 2                       |
      | assignsubmission_file_maxsizebytes  | 1000000                 |
    When I am on the "Test assignment name" "assign activity" page logged in as student
    And I press "Add submission"
    And I follow "Add..."
    Then I should not see "Course 1 Bucket"

  Scenario: An admin can see a s3 course bucket
    Given I am on the "folderB" "folder activity editing" page logged in as admin
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Course 1 Bucket"
