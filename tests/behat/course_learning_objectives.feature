@mod @mod_roadmap
Feature: Teacher can add, sort, edit, and delete learning objectives.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Example | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Adding three learning objectives, sorting them, then deleting one.
    When I add a "Roadmap" to section "1"
    And I set the following fields to these values:
      | Name | Example Roadmap |
    And I press "Save and display"
    And I should see "Roadmap Configuration"
    And I expand all fieldsets
    And I add a Learning Objectives to the roadmap with:
      | title |
      | Learning Objective 1 |
      | Learning Objective 3 |
      | Learning Objective 4 |
      | Learning Objective 2 |
    And I press "Save Configuration"
    When I am on "Course 1" course homepage
    And "Learning Objective 1" "text" should appear before "Learning Objective 3" "text"
    And "Learning Objective 3" "text" should appear before "Learning Objective 4" "text"
    And "Learning Objective 4" "text" should appear before "Learning Objective 2" "text"
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And I move up learning objective "Learning Objective 2"
    And I move down learning objective "Learning Objective 3"
    And I press "Save Configuration"
    Then I am on "Course 1" course homepage
    And "Learning Objective 1" "text" should appear before "Learning Objective 2" "text"
    And "Learning Objective 2" "text" should appear before "Learning Objective 3" "text"
    And "Learning Objective 3" "text" should appear before "Learning Objective 4" "text"
