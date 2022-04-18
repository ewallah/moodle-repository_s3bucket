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
      | facilitator |
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student     | C1     | student        |
      | teacher     | C1     | editingteacher |
      | teacher     | C2     | editingteacher |
      | facilitator | C1     | editingteacher |
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
    When I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Folder" to section "1"
    And I set the following fields to these values:
      | Name | Folder name |
      | Description | Folder description |
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Course 1 Bucket"
    And I follow "Course 1 Bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    # Then I should see "Make a copy of the file"
    # And I should see "Create an alias"
    # And I should not see "Create an access controled link to the file"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save and display" "button"
    And I should see "2020_f.jpg"

  @_file_upload @atto
  Scenario: A teacher can add files from the s3 bucket repository in course context
    When I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name | Workshop with embedded images  |
    And I am on the "Workshop with embedded images" "workshop activity editing" page
    And I expand all fieldsets
    And I set the field "Instructions for submission" to "<p>Image test</p>"
    And I select the text in the "Instructions for submission" Atto editor
    And I click on "Insert or edit image" "button" in the "//*[@data-fieldtype='editor']/*[descendant::*[@id='id_instructauthorseditor']]" "xpath_element"
    And I click on "Browse repositories..." "button"
    Then I should see "Course 1 Bucket"
    And I follow "Course 1 Bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    # Then I should see "Make a copy of the file"
    # And I should see "Create an alias"
    # And I should see "Link to the file directly"
    # And I should not see "Create an access controled link to the file"
    And I click on "Select this file" "button"
    And I set the field "This image is decorative only" to "1"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' atto_image_widthentry ')]" to "100"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' atto_image_heightentry ')]" to "100"
    And I click on "Save image" "button"
    And I click on "Save and display" "button"

  Scenario: A teacher cannot see the s3 bucket repository in another course context
    When I log in as "teacher"
    And I am on "Course 2" course homepage with editing mode on
    And I navigate to "Repositories" in current page administration
    Then I should not see "Course 1 Bucket"

  Scenario: Another teacher can see the s3 bucket repository in same course context
    When I log in as "facilitator"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Folder" to section "1"
    And I set the following fields to these values:
      | Name | Folder name |
      | Description | Folder description |
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Course 1 Bucket"

  Scenario: A student cannot see a s3 course bucket
    When I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 0 |
      | assignsubmission_file_enabled | 1 |
      | Maximum number of uploaded files | 2 |
    And I log out
    When I am on the "Test assignment name" "assign activity" page logged in as student
    And I press "Add submission"
    And I follow "Add..."
    Then I should not see "Course 1 Bucket"

  Scenario: An admin can see a s3 course bucket
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Folder" to section "1"
    And I set the following fields to these values:
      | Name | Folder name |
      | Description | Folder description |
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Course 1 Bucket"
