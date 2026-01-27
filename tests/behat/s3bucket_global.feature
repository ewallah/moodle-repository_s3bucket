@repository @repository_s3bucket
Feature: S3 bucket global repositories should be seen by admins and teachers

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | student  |
      | teacher  |
    And the following "course enrolments" exist:
      | user    | course | role |
      | student | C1     | student |
      | teacher | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name    | course | idnumber | section |
      | url      | UrlA    | C1     | urlA     | 1       |
      | folder   | FolderB | C1     | folderB  | 1       |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |
    And I enable repository "s3bucket"
    And the following config values are set as admin:
      | config | value | plugin              |
      | s3mock | false | repository_s3bucket |
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | Global bucket | testbucket  | test       | test       | http://localhost:4566 | System       |

  @javascript @_file_upload
  Scenario: An admin can see the global s3 bucket repository
    Given I log in as "admin"
    And I should see "No files available" in the "Private files" "block"
    When I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    And I should see "Global bucket"
    And I follow "Global bucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I click on "Select this file" "button"
    Then I should see "testfile.jpg"
    And I click on "Save changes" "button"
    And I should see "testfile.jpg" in the "Private files" "block"
    But I should not see "No files available" in the "Private files" "block"

  @javascript @_file_upload
  Scenario: An admin can search the global s3 bucket repository
    Given I log in as "admin"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    And I should see "Global bucket"
    And I follow "Global bucket"
    And I should see "3" elements in repository content area
    And I click on "Display folder with file details" "link" in the ".file-picker" "css_element"
    And I click on "Display folder as file tree" "link" in the ".file-picker" "css_element"
    And I click on "Display folder with file icons" "link" in the ".file-picker" "css_element"
    And "Search repository" "field" should be visible
    And I set the field "Search repository" to "test"
    When I press enter
    Then I should see "2" elements in repository content area
    And I set the field "Search repository" to "2021"
    And I press enter
    And I should see "0" elements in repository content area

  Scenario: A teacher cannot see the global s3 bucket repository in private area
    When I log in as "teacher"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Global bucket"

  @javascript @_file_upload
  Scenario: A teacher can see the global s3 bucket repository in a course module
    Given I am on the "folderB" "folder activity editing" page logged in as teacher
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Global bucket"
    And I follow "Global bucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I click on "Select this file" "button"
    And I should see "testfile.jpg"
    And I click on "Save and display" "button"
    And I should see "testfile.jpg"

  @javascript
  Scenario: A teacher can not add a global s3 bucket link in a url module
    Given I am on the "urlA" "url activity editing" page logged in as teacher
    When I click on "Choose a link..." "button"
    Then I should see "Global bucket"
    And I follow "Global bucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I click on "Select this file" "button"

  Scenario: A student cannot see the global s3 bucket repository
    Given I log in as "student"
    When I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Global bucket"
