@repository @repository_s3bucket @editor_tiny @javascript
Feature: S3 bucket behavour in pages

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
    And the following "activity" exists:
      | course   | C1               |
      | activity | lesson           |
      | name     | Test lesson name |
    And I enable repository "s3bucket"
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And the following "repository_s3bucket > s3buckets" exist:
      | name         | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | coursebucket | testbucket  | test       | test       | http://localhost:4566 | Course       |

  @_file_upload
  Scenario Outline: A teacher can add files from the s3 bucket repository as a resouce
    When I am on the "Test lesson name" "lesson activity" page logged in as teacher
    And I follow "Add a question page"
    And I set the field "Select a question type" to "Multichoice"
    And I press "Add a question page"
    And I set the following fields to these values:
      | Page title | Multichoice question |
      | Page contents | What animal is an amphibian? |
      | id_answer_editor_0 | Frog |
      | id_response_editor_0 | Correct answer |
      | id_jumpto_0 | Next page |
      | id_score_0 | 1 |
      | id_answer_editor_1 | Cat |
      | id_response_editor_1 | Incorrect answer |
      | id_jumpto_1 | This page |
      | id_score_1 | 0 |
      | id_answer_editor_2 | <p></p><p>Dog</p> |
      | id_response_editor_2 | Incorrect answer |
      | id_jumpto_2 | This page |
      | id_score_2 | 0 |
    And I click on "Image" "button" in the "//*[@data-fieldtype='editor']/*[descendant::*[@id='id_answer_editor_0']]" "xpath_element"
    And I click on "Browse repositories" "button" in the "Insert image" "dialogue"
    And I should see "coursebucket"
    And I follow "coursebucket"
    And I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    Then I should see "Link to the file"
    And I should see "Link to the external file"
    And I should see "Make a copy of the file"
    And I should not see "Create an access controlled link to the file"
    And I click on "<link>" "radio"
    And I press "Select this file"
    And I click on "Decorative image" "checkbox"
    And I click on "Save" "button" in the "Image details" "dialogue"
    And I press "Save page"

    And I log out
    When I am on the "Test lesson name" "lesson activity" page logged in as student
    Then I should see "What animal is an amphibian?"
    Then "//img[contains(@src, 'testfile.jpg')]" "xpath_element" should exist

    Examples:
      | link                      |
      | Link to the file          |
      | Make a copy of the file   |
      | Link to the external file |
