@repository @repository_s3bucket
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
    And I should see "Create an alias"
    And I click on "Create an alias/shortcut to the file" "radio"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save changes" "button"
    Then I should not see "No files available" in the "Private files" "block"
    And I should see "2020_f.jpg" in the "Private files" "block"

  @javascript
  Scenario: An admin can search the s3 bucket repository
    When I log in as "admin"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Testrepo"
    And I follow "Testrepo"
    Then I should see "2" elements in repository content area
    And I click on "Display folder with file details" "link" in the ".file-picker" "css_element"
    And I click on "Display folder as file tree" "link" in the ".file-picker" "css_element"
    And I click on "Display folder with file icons" "link" in the ".file-picker" "css_element"
    And "Search repository" "field" should be visible
    And I set the field "Search repository" to "2020"
    When I press enter
    Then I should see "2" elements in repository content area
    And I set the field "Search repository" to "2021"
    When I press enter
    Then I should see "0" elements in repository content area

  Scenario: A teacher cannot see the s3 bucket repository in private area
    When I log in as "teacher"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Testrepo"

  @javascript
  Scenario: A teacher can see the s3 bucket repository in a course module
    When I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Folder" to section "1"
    And I set the following fields to these values:
      | Name | Folder name |
      | Description | Folder description |
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Testrepo"
    And I follow "Testrepo"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    Then I should see "Make a copy of the file"
    And I should see "Create an alias"
    And I click on "Create an alias/shortcut to the file" "radio"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save and display" "button"
    Then I should see "Folder description"
    And I should see "2020_f.jpg"

  Scenario: A student cannot see the s3 bucket repository
    When I log in as "student"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Testrepo"
