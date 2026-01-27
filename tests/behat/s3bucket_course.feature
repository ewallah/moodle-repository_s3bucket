@repository @repository_s3bucket @javascript
Feature: S3 bucket repository is private in user context

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "users" exist:
      | username    |
      | student     |
      | teacher     |
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student     | C1     | student        |
      | teacher     | C1     | editingteacher |
      | teacher     | C2     | editingteacher |
    And the following "activities" exist:
      | activity | name    | course | idnumber | section |
      | page     | PageA   | C1     | pageA    | 1       |
      | folder   | FolderB | C1     | folderB  | 1       |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |
    And I enable repository "s3bucket"
    And the following "repository_s3bucket > s3buckets" exist:
      | name         | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | coursebucket | testbucket  | test       | test       | http://localhost:4566 | Course       |

  @_file_upload
  Scenario: A teacher can add files from the s3 bucket repository in module context
    Given I am on the "folderB" "folder activity editing" page logged in as teacher
    And I click on "Add..." "button" in the "Files" "form_row"
    And I should see "coursebucket"
    And I follow "coursebucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I should see "Make a copy of the file"
    And I should see "Link to the file"
    And I should not see "Create an alias"
    And I should not see "Create an access controled link to the file"
    When I click on "Select this file" "button"
    Then I should see "testfile.jpg"
    And I click on "Save and display" "button"
    And I should see "testfile.jpg"

  Scenario: A teacher cannot add files from the s3 bucket repository in profile
    Given I log in as "teacher"
    When I open my profile in edit mode
    Then I click on "Add..." "button" in the "New picture" "form_row"
    But I should not see "coursebucket"

  Scenario: A teacher cannot see the s3 bucket repository in another course context
    When I log in as "teacher"
    And I am on "Course 2" course homepage with editing mode on
    And I navigate to "Repositories" in current page administration
    Then I should not see "coursebucket"

  Scenario: Another teacher can see the s3 bucket repository in same course context
    Given I am on the "folderB" "folder activity editing" page logged in as teacher
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "coursebucket"

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
    Then I should not see "coursebucket"

  Scenario: An admin can see a s3 course bucket
    Given I am on the "folderB" "folder activity editing" page logged in as admin
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "coursebucket"
