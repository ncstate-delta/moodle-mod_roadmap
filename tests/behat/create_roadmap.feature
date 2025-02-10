@mod @mod_roadmap
Feature: Teacher can add roadmap to course

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
  Scenario: Adding a course roadmap to the course.
    When I add a "Course Roadmap" to section "1"
    And I set the following fields to these values:
      | Name | Example Course Roadmap |
    And I press "Save and display"
    And I should see "Roadmap Configuration"
    And I expand all fieldsets
    And I add a Phases to the roadmap with:
      | title |
      | Phase Title 1 |
    And I press "Save Configuration"
    Then I am on "Course 1" course homepage
    And I should see "Phase Title 1"
