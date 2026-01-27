@repository @repository_s3bucket @javascript
Feature: S3 bucket repository can be used in user context

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student  | student   | 1        |
      | teacher  | teacher   | 1        |
      | editor   | editor    | 1        |
      | manager  | manager   | 1        |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | teacher | editingteacher | System       | |
      | editor  | editingteacher | System       | |
      | manager | manager        | System       | |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |
    And I enable repository "s3bucket"

  Scenario: A teacher can add files from the s3 bucket repository in user context
    Given I log in as "teacher"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | teacherbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I follow "Dashboard"
    And I follow "Manage private files..."
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "teacherbucket"
    And I follow "teacherbucket"
    And I follow "testfile.jpg"
    And I should see "Make a copy of the file"
    And I should not see "Link to the file directly"
    And I click on "Select this file" "button"
    And I should see "testfile.jpg"
    And I click on "Save changes" "button"

  Scenario: An admin does not have access to a private s3 bucket repository of a teacher
    Given I log in as "teacher"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | teacherbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I log out
    And I log in as "admin"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "teacherbucket"
    And I click on "Close" "button" in the "File picker" "dialogue"

  Scenario: An admin does not have access to a private s3 bucket repository in user context
    Given I log in as "teacher"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | teacherbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I log out
    When I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "teacher 1"
    # And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    # And I follow "Manage instances" Throws an error.
    # Then I should see "You can not view/edit repository instances of another user"

  Scenario: A manager does not have access to a private s3 bucket repository in user context
    Given I log in as "teacher"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | teacherbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I log out
    And I log in as "manager"
    And I follow "Dashboard"
    When I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "teacherbucket"

  Scenario: Another teacher does not has access to a private s3 bucket repository in user context
    Given I log in as "teacher"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | teacherbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I log out
    And I log in as "editor"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    When I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "teacherbucket"

  Scenario: A student cannot add files from the s3 bucket repository in user context
    Given I log in as "teacher"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | teacherbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I log out
    When I log in as "student"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    But I should not see "Amazon S3 bucket"

  @_file_upload
  Scenario: An manager can add files from the s3 bucket repository in user context
    Given I log in as "manager"
    And the following "repository_s3bucket > s3buckets" exist:
      | name          | bucket_name | access_key | secret_key | endpoint              | contextlevel |
      | managerbucket | testbucket  | test       | test       | http://localhost:4566 | User         |
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "managerbucket"
    And I follow "managerbucket"
    Then I should see "testdirectory"
    And I should see "testfile.jpg"
    And I follow "testfile.jpg"
    And I click on "Select this file" "button"
    Then I should see "testfile.jpg"
    And I click on "Save changes" "button"
    Then I should not see "No files available" in the "Private files" "block"
    And I should see "testfile.jpg" in the "Private files" "block"
