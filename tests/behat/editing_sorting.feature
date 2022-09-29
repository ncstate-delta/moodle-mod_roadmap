@mod @mod_roadmap
Feature: Teacher can sort phases, cycles, and steps

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
  Scenario: Changing the sort order of the phases.
    When I add a "Roadmap" to section "1"
    And I set the following fields to these values:
      | Name | Example Roadmap |
    And I press "Save and display"
    And I should see "Roadmap Configuration"
    And I expand all fieldsets
    And I add a Phases to the roadmap with:
      | title |
      | Phase Title 1 |
      | Phase Title 3 |
      | Phase Title 4 |
      | Phase Title 2 |
    And I press "Save Configuration"
    When I am on "Course 1" course homepage
    And "Phase Title 1" "text" should appear before "Phase Title 3" "text"
    And "Phase Title 3" "text" should appear before "Phase Title 4" "text"
    And "Phase Title 4" "text" should appear before "Phase Title 2" "text"
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And I move up phase "Phase Title 2"
    And I move up phase "Phase Title 2"
    And I press "Save Configuration"
    Then I am on "Course 1" course homepage
    And "Phase Title 1" "text" should appear before "Phase Title 2" "text"
    And "Phase Title 2" "text" should appear before "Phase Title 3" "text"
    And "Phase Title 3" "text" should appear before "Phase Title 4" "text"
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And "Phase Title 1" "text" should appear before "Phase Title 2" "text"
    And "Phase Title 2" "text" should appear before "Phase Title 3" "text"
    And "Phase Title 3" "text" should appear before "Phase Title 4" "text"
