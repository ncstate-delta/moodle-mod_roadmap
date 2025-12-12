@mod @mod_roadmap @mod_roadmap_step
Feature: Step progress tracking based on activity completion.
  In order to track student progress effectively
  As a teacher
  I need to create steps tied to activities
  As a student
  I need to verify completion states are reflected when activities are complete.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Test      | Student1 | student1@example.com |
      | teacher1 | Test     | Teacher1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | course | name                 | completion | completionexpected    |
      | roadmap    | C1     | Test Course Roadmap  |            |                       |
      | assign     | C1     | Assignment 1         | 1          | ##tomorrow##          |
      | assign     | C1     | Assignment 2         | 1          | ##next week##         |
      | forum      | C1     | Discussion Forum     | 1          |                       |
      | quiz       | C1     | Knowledge Check      | 1          | ##tomorrow##          |

  @javascript
  Scenario: Step icons show incomplete state when activities are not completed
    Given I log in as "teacher1"
    And I am on "C1" course homepage with editing mode on
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets

    # Create a single phase named "Test Phase One".
    And I add a Phases to the roadmap with:
      | title         |
      | Test Phase One |

    # Add a cycle to the "Test Phase One" phase.
    And I add cycles to the last phase with:
      | title            | subtitle                     |
      | Test Cycle One   | Activity Progress Tracking   |

    # Add three steps to the cycle.
    And I add steps to the last cycle with:
      | rollovertext      |
      | Test Step One     |
      | Test Step Two     |
      | Test Step Three   |

    # Configure the first step to require completion of Assignment 1
    When I open activity completion modal for step "Test Step One"
    And I check activity "Assignment 1" in the completion modal
    And I save the activity completion modal
    Then step "Test Step One" should show selected activities "Assignment 1"

    # Configure the second step to require completion of Assignment 1 and Assignment 2
    When I open activity completion modal for step "Test Step Two"
    And I check activity "Assignment 1" in the completion modal
    And I check activity "Assignment 2" in the completion modal
    And I save the activity completion modal
    Then step "Test Step Two" should show selected activities "Assignment 1, Assignment 2"

    # Configure the second step to require completion of all activities.
    When I open activity completion modal for step "Test Step Three"
    And I check activity "Assignment 1" in the completion modal
    And I check activity "Assignment 2" in the completion modal
    And I check activity "Discussion Forum" in the completion modal
    And I check activity "Knowledge Check" in the completion modal
    And I save the activity completion modal
    Then step "Test Step Three" should show selected activities "Assignment 1, Assignment 2, Discussion Forum, Knowledge Check"

    # Save the Roadmap configuration.
    And I press "Save Configuration"

    # Verify steps display as incomplete initially.
    When I am on "C1" course homepage
    Then I should see "Test Phase One"
    And I should see "Test Cycle One"
    And I should see step icon for "Test Step One" showing "0" percent completed
    And I should see step icon for "Test Step Two" showing "0" percent completed
    And I should see step icon for "Test Step Three" showing "0" percent completed

    # Login as student and verify initial step states.
    When I log in as "student1"
    When I am on "C1" course homepage
    Then I should see "Test Phase One"
    And I should see "Test Cycle One"
    And I should see step icon for "Test Step One" showing "0" percent completed
    And I should see step icon for "Test Step Two" showing "0" percent completed
    And I should see step icon for "Test Step Three" showing "0" percent completed

    # Complete Assignment 1 as Student1.
    And I toggle the manual completion state of "Assignment 1"
    And I am on "C1" course homepage

    # Verify step icons update after completing Assignment 1.
    And I should see step icon for "Step One" showing "100" percent completed
    And I should see step icon for "Step Two" showing "50" percent completed
    And I should see step icon for "Step Three" showing "25" percent completed

    # Complete Assignment 2 as Student1.
    And I toggle the manual completion state of "Assignment 2"
    And I am on "C1" course homepage

    # Verify step icons update after completing Assignment 2.
    And I should see step icon for "Step One" showing "100" percent completed
    And I should see step icon for "Step Two" showing "100" percent completed
    And I should see step icon for "Step Three" showing "50" percent completed

    # Complete all activities as Student1.
    And I toggle the manual completion state of "Discussion Forum"
    And I toggle the manual completion state of "Knowledge Check"
    And I am on "C1" course homepage

    # Verify all step icons are fully complete.
    And I should see step icon for "Step One" showing "100" percent completed
    And I should see step icon for "Step Two" showing "100" percent completed
    And I should see step icon for "Step Three" showing "100" percent completed

  @javascript
  Scenario: Testing cancel modal functionality for step configuration
    Given I log in as "teacher1"
    And I am on "C1" course homepage with editing mode on
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And I add a Phases to the roadmap with:
      | title         |
      | Modal Testing Phase |
    And I add cycles to the last phase with:
      | title            | subtitle              |
      | Modal Testing    | Test modal interfaces |
    And I add steps to the last cycle with:
      | rollovertext              |
      | Modal Test Step           |

    # Test activity completion modal
    When I open activity completion modal for step "Modal Test Step"
    Then I should see activity "Assignment 1" in the completion modal
    And I should see activity "Assignment 2" in the completion modal
    And I should see activity "Discussion Forum" in the completion modal

    # Select activities and save
    When I check activity "Assignment 1" in the completion modal
    And I check activity "Assignment 2" in the completion modal
    And I save the activity completion modal

    # Verify activities were saved
    Then step "Modal Test Step" should show selected activities "Assignment 1, Assignment 2"

    # Test modal cancel functionality
    When I open activity completion modal for step "Modal Test Step"
    And I check activity "Discussion Forum" in the completion modal
    And I cancel the activity completion modal

    # Verify previous selection is maintained (Discussion Forum should not be added)
    Then step "Modal Test Step" should show selected activities "Assignment 1, Assignment 2"

    And I press "Save Configuration"
    When I am on "C1" course homepage
    Then I should see step "Modal Test Step" in the roadmap

  @javascript
  Scenario: Multiple steps display in correct order within cycle before and after sorting.
    Given I log in as "teacher1"
    And I am on "C1" course homepage with editing mode on
    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And I add a Phases to the roadmap with:
      | title         |
      | Order Testing Phase |
    And I add cycles to the last phase with:
      | title            | subtitle              |
      | Sequential Steps | Order verification    |
    And I add steps to the last cycle with:
      | rollovertext              |
      | Fourth Step               |
      | Third Step                |
      | Second Step               |
      | First Step                |
    And I press "Save Configuration"

    When I am on "C1" course homepage
    Then step rollover text "Fourth Step" should appear before step rollover text "Third Step"
    And step rollover text "Third Step" should appear before step rollover text "Second Step"
    And step rollover text "Second Step" should appear before step rollover text "First Step"

    And I click on "Configure Roadmap" "link"
    And I expand all fieldsets
    And I expand all phases
    And I expand cycle "Sequential Steps"
    And I move up step "First Step"
    And I move up step "First Step"
    And I move up step "First Step"
    And I move up step "Second Step"
    And I move up step "Second Step"
    And I move up step "Third Step"
    And I press "Save Configuration"

    When I am on "C1" course homepage
    And step rollover text "First Step" should appear before step rollover text "Second Step"
    And step rollover text "Second Step" should appear before step rollover text "Third Step"
    And step rollover text "Third Step" should appear before step rollover text "Fourth Step"
