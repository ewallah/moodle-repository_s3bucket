@repository @repository_s3bucket @javascript
Feature: S3 bucket behavour in popups

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | student  |
      | teacher  |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | student | C1     | student        |
      | teacher | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name      | course | idnumber  | section |
      | resource | ResourceA | C1     | resourceA | 1       |
    And I enable repository "s3bucket"
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And the following "repository_s3bucket > s3buckets" exist:
      | name         | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | coursebucket | testbucket  | test       | test       | http://localhost:4566 | Course       |

  @_file_upload
  Scenario Outline: A teacher can add files from the s3 bucket repository as a resouce
    Given I am on the "resourceA" "resource activity editing" page
    And I click on "Add..." "button"
    And I should see "coursebucket"
    And I follow "coursebucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I press "Select this file"
    And I follow "testfile.jpg"
    And I click on "Set main file" "button"
    And I press "Save and return to course"
    And I log out
    When I am on the "ResourceA" "resource activity" page logged in as student
    Then I should see "<result>"

    Examples:
      | display        | result |
      | Open           | ResourceA |
      | In pop-up      | ResourceA |
      | Force download | ResourceA |
