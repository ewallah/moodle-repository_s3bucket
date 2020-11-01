@repository @repository_s3bucket @javascript
Feature: S3 bucket repository should throw no errors
  An admin should be able to configure the plugin

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
    And I click on "Allow users to add a repository instance into the course" "checkbox"
    And I click on "Allow users to add a repository instance into the user context" "checkbox"
    And I click on "Save" "button"
    And I log out

  Scenario: An admin can add a user and course instances
    When I log in as "admin"
    And I navigate to "Plugins > Repositories > Manage repositories" in site administration
    Then I should see "Amazon S3 bucket"
    And I should see "1 Site-wide common instance(s)"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should see "Amazon S3 bucket"
    And I should see "Create"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Repositories" in current page administration
    Then I should see "Amazon S3 bucket"
    And I should see "Create"

  Scenario: A teacher cannot add a user or course instance
    When I log in as "teacher"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should not see "Amazon S3 bucket"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Repositories" in current page administration
    Then I should see "Amazon S3 bucket"
    And I should see "Create"

  Scenario: A student cannot add an instance
    When I log in as "student"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should not see "Amazon S3 bucket"
    And I am on "Course 1" course homepage
    And I should not see "Amazon S3 bucket"
