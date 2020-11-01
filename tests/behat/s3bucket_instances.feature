@repository @repository_s3bucket @_file_upload
Feature: S3 bucket repository should be seen by admins

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | email | firstname | lastname |
      | student | s@example.com | Student | 1 |
      | teacher | t@example.com | Teacher | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student | C1 | student |
      | teacher | C1 | editingteacher |
    And I enable repository "s3bucket"
    And I log in as "admin"
    And I navigate to "Plugins > Repositories > Amazon S3 bucket" in site administration
    And I click on "Create a repository instance" "button"
    And I set the following fields to these values:
        | name        | Testrepo      |
        | bucket_name | Testbucket    |
    And I click on "Save" "button"
    Then I should see "Required"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out

  @javascript
  Scenario: An admin can see the s3 bucket repository
    When I log in as "admin"
    Then I should see "No files available" in the "Private files" "block"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Testrepo"
    And I follow "Testrepo"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    Then I should see "Make a copy of the file"

  Scenario: A teacher cannot see the s3 bucket repository in private area
    When I log in as "teacher"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Testrepo"

  Scenario: A student cannot see the s3 bucket repository
    When I log in as "student"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Testrepo"
