@mod @mod_roadmap
Feature: Teacher can sort phases, cycles, and steps

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Test | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher | Teacher | First | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course | section | name |
      | roadmap    | C1     | 1       | Course Roadmap |

  @javascript
  Scenario: Changing the sort order of the phases.
    When I log in as "teacher"
    And I am on "C1" course homepage with editing mode on
    And I click on "Configure Roadmap" "link"
    And I should see "Roadmap Configuration"
    And I expand all fieldsets
    And I add a Phases to the roadmap with:
      | title |
      | Phase Title 1 |
      | Phase Title 3 |
      | Phase Title 4 |
      | Phase Title 2 |
    And I press "Save Configuration"
    When I am on "C1" course homepage
    And "Phase Title 1" "text" should appear before "Phase Title 3" "text"
    And "Phase Title 3" "text" should appear before "Phase Title 4" "text"
    And "Phase Title 4" "text" should appear before "Phase Title 2" "text"
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And I move up phase "Phase Title 2"
    And I move up phase "Phase Title 2"
    And I press "Save Configuration"
    Then I am on "C1" course homepage
    And "Phase Title 1" "text" should appear before "Phase Title 2" "text"
    And "Phase Title 2" "text" should appear before "Phase Title 3" "text"
    And "Phase Title 3" "text" should appear before "Phase Title 4" "text"
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And "Phase Title 1" "text" should appear before "Phase Title 2" "text"
    And "Phase Title 2" "text" should appear before "Phase Title 3" "text"
    And "Phase Title 3" "text" should appear before "Phase Title 4" "text"
