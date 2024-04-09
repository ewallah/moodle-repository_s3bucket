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
    And I log in as "admin"
    And I navigate to "Plugins > Repositories > Amazon S3 bucket" in site administration
    And I click on "Create a repository instance" "button"
    And I set the field "Name" to "Global bucket"
    And I set the field "Bucket name" to "globalbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out

  @javascript @_file_upload
  Scenario: An admin can see the global s3 bucket repository
    When I log in as "admin"
    Then I should see "No files available" in the "Private files" "block"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Global bucket"
    And I follow "Global bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save changes" "button"
    Then I should not see "No files available" in the "Private files" "block"
    And I should see "2020_f.jpg" in the "Private files" "block"

  @javascript @_file_upload
  Scenario: An admin can search the global s3 bucket repository
    When I log in as "admin"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Global bucket"
    And I follow "Global bucket"
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

  Scenario: A teacher cannot see the global s3 bucket repository in private area
    When I log in as "teacher"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Global bucket"

  @javascript @_file_upload
  Scenario: A teacher can see the global s3 bucket repository in a course module
    Given I am on the "folderB" "folder activity editing" page logged in as teacher
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Global bucket"
    And I follow "Global bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save and display" "button"
    And I should see "2020_f.jpg"

  @javascript
  Scenario: A teacher can not add a global s3 bucket link in a url module
    Given I am on the "urlA" "url activity editing" page logged in as teacher
    And I click on "Choose a link..." "button"
    Then I should see "Global bucket"
    And I follow "Global bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    And I click on "Select this file" "button"

  Scenario: A student cannot see the global s3 bucket repository
    When I log in as "student"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Global bucket"
