@repository @repository_s3bucket @javascript
Feature: S3 bucket repository can be used in user context

  Background:
    Given the following "users" exist:
      | username | email | firstname | lastname |
      | student | s@example.com | Student | 1 |
      | teacher | t@example.com | Teacher | 1 |
      | editor  | e@example.com | Editor  | 1 |
      | manager | m@example.com | Manager | 1 |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | teacher | editingteacher | System       | |
      | editor  | editingteacher | System       | |
      | manager | manager        | System       | |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference | pagetypepattern | defaultregion |
      | private_files | System       | 1         | my-index        | side-post     |
    And I enable repository "s3bucket"
    And I log in as "admin"
    And I navigate to "Plugins > Repositories > Amazon S3 bucket" in site administration
    And I click on "Allow users to add a repository instance into the user context" "checkbox"
    And I click on "Save" "button"
    And I log out

  Scenario: A teacher can add files from the s3 bucket repository in user context
    When I log in as "teacher"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should see "Amazon S3 bucket"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Teacher user bucket"
    And I set the field "Bucket name" to "teacherbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Teacher user bucket"
    And I follow "Teacher user bucket"
    Then I should see "2020_dir"
    And I follow "2020_f.jpg"
    #Then I should see "Make a copy of the file"
    #And I should not see "Link to the file directly"
    #And I should see "Create an alias"

  Scenario: An admin does not have access to a private s3 bucket repository of a teacher
    When I log in as "teacher"
    And I follow "Preferences" in the user menu
    And I follow "Manage instances"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Teacher user bucket"
    And I set the field "Bucket name" to "userbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out
    And I log in as "admin"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Teacher user bucket"
    And I click on "Close" "button" in the "File picker" "dialogue"

  Scenario: An admin does not have access to a private s3 bucket repository in user context
    When I log in as "teacher"
    And I follow "Preferences" in the user menu
    And I follow "Manage instances"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Teacher user bucket"
    And I set the field "Bucket name" to "userbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out
    And I log in as "admin"
    And I navigate to "Users > Accounts > Browse list of users" in site administration
    And I follow "Teacher 1"
    # TODO: Does not work in classic theme in github actions.
    # And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    # Throws an error: You can not view/edit repository instances of another user.
    # And I follow "Manage instances"

  Scenario: A manager does not have access to a private s3 bucket repository in user context
    When I log in as "teacher"
    And I follow "Preferences" in the user menu
    And I follow "Manage instances"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Teacher user bucket"
    And I set the field "Bucket name" to "userbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out
    And I log in as "manager"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Teacher user bucket"

  Scenario: Another teacher does not has access to a private s3 bucket repository in user context
    When I log in as "teacher"
    And I follow "Preferences" in the user menu
    And I follow "Manage instances"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Teacher user bucket"
    And I set the field "Bucket name" to "userbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I log out
    And I log in as "editor"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should not see "Teacher user bucket"

  Scenario: A student cannot add files from the s3 bucket repository in user context
    When I log in as "student"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should not see "Amazon S3 bucket"

  @_file_upload
  Scenario: An manager can add files from the s3 bucket repository in user context
    When I log in as "manager"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should see "Amazon S3 bucket"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Manager user bucket"
    And I set the field "Bucket name" to "userbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Manager user bucket"
    And I follow "Manager user bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    # Then I should see "Make a copy of the file"
    # And I should see "Create an alias"
    # And I should not see "Link to the file directly"
    # And I click on "Make a copy of the file" "radio"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save changes" "button"
    Then I should not see "No files available" in the "Private files" "block"
    And I should see "2020_f.jpg" in the "Private files" "block"

  Scenario: An admin can add files from the s3 bucket repository in user context
    When I log in as "admin"
    And I follow "Preferences" in the user menu
    Then I should see "Repositories"
    And I follow "Manage instances"
    Then I should see "Amazon S3 bucket"
    And I follow "Create \"Amazon S3 bucket\" instance"
    And I set the field "Name" to "Admin user bucket"
    And I set the field "Bucket name" to "userbucket"
    And I set the field "Access key" to "anoTherfake@1"
    And I set the field "Secret key" to "anotherFake_$2"
    And I click on "Save" "button"
    And I follow "Dashboard"
    And I follow "Manage private files..."
    And I click on "Add..." "button" in the "Files" "form_row"
    Then I should see "Admin user bucket"
    And I follow "Admin user bucket"
    Then I should see "2020_dir"
    And I should see "2020_f.jpg"
    And I follow "2020_f.jpg"
    # Then I should see "Make a copy of the file"
    # And I should see "Create an alias"
    # And I should not see "Link to the file directly"
    # And I click on "Make a copy of the file" "radio"
    And I click on "Select this file" "button"
    Then I should see "2020_f.jpg"
    And I click on "Save changes" "button"
    Then I should not see "No files available" in the "Private files" "block"
    And I should see "2020_f.jpg" in the "Private files" "block"
