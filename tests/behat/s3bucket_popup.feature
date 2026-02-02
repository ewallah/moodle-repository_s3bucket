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
    And I enable repository "s3bucket"
    And the following config values are set as admin:
      | displayoptions | 0,1,2,3,4,5,6 | resource |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And the following "repository_s3bucket > s3buckets" exist:
      | name         | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | coursebucket | testbucket  | test       | test       | http://localhost:4566 | Course       |

  @_file_upload
  Scenario Outline: A teacher can add files from the s3 bucket repository as a resouce
    Given I add a "File" to section "1" using the activity chooser
    And I set the following fields to these values:
      | Name                      | ResourceA |
      | id_display                | <display> |
      | Show size                 | 1         |
      | Show type                 | 1         |
      | Show upload/modified date | 1         |
    And I click on "Add..." "button"
    And I should see "coursebucket"
    And I follow "coursebucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I press "Select this file"
    And I follow "testfile.jpg"
    And I click on "Set main file" "button"
    And I press "Save and display"
    And I should see "JPG"
    And I log out
    When I am on the "Course 1" "course" page logged in as "student"
    And I follow "ResourceA"
    And I should see "JPG"

    Examples:
      | display |
      | 0       |
      | 1       |
      # In frame skiped because JPG not detected. | 2       |
      | 3       |
      | 4       |
      | 5       |
      | 6       |
