@mod @mod_roadmap @mod_
Feature: Teacher can sort phases, cycles, and steps

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "users" exist:
      | username | firstname  | lastname | email                |
      | student1 | Vinnie    | Student1 | student1@example.com |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activity" exists:
      | activity           | roadmap             |
      | course             | C1                  |
      | name               | Test Course Roadmap |

  @javascript
  Scenario: Changing the sort order of the phases.
    When I log in as "teacher1"
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
