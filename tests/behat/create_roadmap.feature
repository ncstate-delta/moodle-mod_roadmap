@mod @mod_roadmap
Feature: Teacher can add roadmap to course

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
  Scenario: Adding a course roadmap to the course.
    When I log in as "teacher"
    And I am on "C1" course homepage with editing mode on
    And I click on "Configure Roadmap" "link"
    And I should see "Roadmap Configuration"
    And I expand all fieldsets
    And I add a Phases to the roadmap with:
      | title |
      | Phase Title 1 |
    And I press "Save Configuration"
    Then I am on "C1" course homepage
    And I should see "Phase Title 1"
