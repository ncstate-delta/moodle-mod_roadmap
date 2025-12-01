<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions related to mod_roadmap.
 *
 * @package   mod_roadmap
 * @category  test
 * @copyright 2022 Stephen Bader
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../lib/behat/behat_field_manager.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Steps definitions related to mod_quiz.
 *
 * @copyright 2014 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_roadmap extends behat_question_base {
    /**
     * Expand all phases in the roadmap configuration page
     *
     * The button for expanding all phases should be on the configuration page
     *
     * @When /^I expand all phases$/
     */
    public function i_expand_all_phases() {
        $expandallphasexpath = $this->get_xpath_for_expand_all_phases_button();
        $this->wait_and_click($expandallphasexpath);
    }

    /**
     * Expand cycle with given title
     *
     * The button for expanding a cycle should be on the configuration page
     * @When /^I expand cycle "(?P<cycle_name>(?:[^"]|\\")*)"$/
     * @param String $cyclename the name of the cycle being expanded
     */
    public function i_expand_cycle($cyclename) {
        // Find the last phase wrapper.
        $cyclewrapperxpath = $this->get_xpath_for_cycle_wrapper($cyclename);
        $expandcyclexpath = $this->get_xpath_for_cycle_expand_button($cyclewrapperxpath);
        $this->wait_and_click($expandcyclexpath);
    }


    /**
     * Moves a learning objective up
     *
     * The form for moving learning objectives should be on the configuration page
     *
     * @When /^I move up learning objective "(?P<objective_name>(?:[^"]|\\")*)"$/
     * @param String $objectivename the name of the learning objective being sorted up
     */
    public function i_move_up_learning_objective($objectivename) {
        // Expand the new phase.
        $moveupbuttonxpath = $this->get_xpath_for_learning_objective_moveup_button($objectivename);
        $this->execute("behat_general::i_click_on", [$moveupbuttonxpath, "xpath_element"]);
    }

    /**
     * Moves a learning objective down
     *
     * The form for moving learning objectives should be on the configuration page
     *
     * @When /^I move down learning objective "(?P<objective_name>(?:[^"]|\\")*)"$/
     * @param String $objectivename the name of the learning objective being sorted down
     */
    public function i_move_down_learning_objective($objectivename) {
        // Expand the new phase.
        $movedownbuttonxpath = $this->get_xpath_for_learning_objective_movedown_button($objectivename);
        $this->execute("behat_general::i_click_on", [$movedownbuttonxpath, "xpath_element"]);
    }

    /**
     * Deletes a learning objective
     *
     * The form for deleting learning objectives should be on the configuration page
     *
     * @When /^I delete learning objective "(?P<objective_name>(?:[^"]|\\")*)"$/
     * @param String $objectivename the name of the learning objective being sorted to be deleted
     */
    public function i_delete_learning_objective($objectivename) {

        $deletebuttonxpath = $this->get_xpath_for_learning_objective_delete_button($objectivename);

        try {
            $this->execute("behat_general::i_click_on", [$deletebuttonxpath, "xpath_element"]);
        } catch (Facebook\WebDriver\Exception\UnexpectedAlertOpenException $e) {
            // Accept the alert so the framework can continue running the following scenarios.
            // Some browsers already close the alert; wrap this in a try and catch.
            $this->getSession()->getDriver()->getWebDriver()->switchTo()->alert()->accept();
        } catch (Exception $e) {
            // Something other than the alert broke.
            return false;
        }

        // If we reached here without exception, indicate success.
        return true;
    }

    /**
     * Adds a learning objective to the existing roadmap with filling the form
     *
     * The form for creating a learning objective should be on the configuration page
     *
     * @When /^I add a Learning Objectives to the roadmap with:$/
     * @param TableNode $data with data for filling in the new phase form
     */
    public function i_add_learning_objectives_to_roadmap_with(TableNode $data) {

        // Multiple phases can be added by TableNode.
        $number = 1;
        foreach ($data as $row) {
            // Create a new phase by clicking link.
            $this->execute('behat_general::click_link', 'Add a course learning objective');

            $lotitlexpath = $this->get_xpath_for_learning_objective_input($number);
            $this->wait_and_set_field($lotitlexpath, $row['title']);
            $number++;
        }
    }

    /**
     * Adds a phase to the existing roadmap with filling the form
     *
     * The form for creating a phase should be on the configuration page
     *
     * @When /^I add a Phases to the roadmap with:$/
     * @param TableNode $data with data for filling in the new phase form
     */
    public function i_add_phases_to_roadmap_with(TableNode $data) {

        // Multiple phases can be added by TableNode.
        foreach ($data as $row) {
            // Create a new phase by clicking link.
            $addphasexpath = $this->get_xpath_for_add_phase_link();
            $this->wait_and_click($addphasexpath);

            // Now find the last phase added. It will be the last in the list.
            $phasewrapperxpath = "//div[contains(@class, 'phase-wrapper')][last()]";
            $expandphasexpath  = $this->get_xpath_for_phase_expand_button($phasewrapperxpath);
            $this->wait_and_click($expandphasexpath);

            $phasetitlexpath = $this->get_xpath_for_phase_title($phasewrapperxpath);
            $this->wait_and_set_field($phasetitlexpath, $row['title']);
        }
    }

    /**
     * Moves a phase up in the sort order of an existing roadmap
     *
     * The controls for moving a phase should be on the configuration page
     *
     * @When /^I move up phase "(?P<phase_name>(?:[^"]|\\")*)"$/
     * @param String $phasename the name of the phase being sorted up
     */
    public function i_move_up_phase($phasename) {
        // Expand the new phase.
        $phasewrapperxpath = $this->get_xpath_for_phase_wrapper($phasename);
        $expandphasexpath  = $this->get_xpath_for_phase_moveup_button($phasewrapperxpath);
        $this->wait_and_click($expandphasexpath);
    }

    /**
     * step with rollover text should appear before step with rollover text
     *
     *  Verifies that one step appears before another in the roadmap
     *
     * @When /^step with rollover text "(?P<rollovertext1>(?:[^"]|\\")*)" should appear before step with rollover text "(?P<rollovertext2>(?:[^"]|\\")*)"$/
     * @param String $rollovertext1 rollover text of the first step in order
     * @param String $rollovertext2 rollover text of the second step in order
     */
    public function step_with_rollover_text_should_appear_another($rollovertext1, $rollovertext2) {
        $step1xpath = "//div[contains(@class, 'roadmap-step') and contains(@title, '" . $this->escape($rollovertext1) . "')]";
        $step2xpath = "//div[contains(@class, 'roadmap-step') and contains(@title, '" . $this->escape($rollovertext2) . "')]";

        $this->execute("behat_general::should_exist", [$step1xpath, "xpath_element"]);
        $this->execute("behat_general::should_exist", [$step2xpath, "xpath_element"]);

        // Step1 xpath should appear before step2 xpath in the page source.
        $pagesource = $this->getSession()->getDriver()->getWebDriver()->getPageSource();

        $pos1 = strpos($pagesource, $rollovertext1);
        $pos2 = strpos($pagesource, $rollovertext2);

        if ($pos1 === false || $pos2 === false) {
            throw new ExpectationException("One or both steps not found in page source.", $this->getSession());
        }

        if ($pos1 > $pos2) {
            throw new ExpectationException("Step with rollover text \"{$rollovertext1}\" does not appear before \"{$rollovertext2}\".", $this->getSession());
        }

         return true;
    }


    /**
     * Moves a step up in the sort order of an existing roadmap
     *
     * The controls for moving a step should be on the configuration page
     *
     * @When /^I move up step "(?P<step_name>(?:[^"]|\\")*)"$/
     * @param String $rollovertext rollover text of the step being sorted up
     */
    public function i_move_up_step($rollovertext) {
        $stepxpath = $this->get_xpath_for_step_wrapper($rollovertext);
        $this->execute("behat_general::should_exist", [$stepxpath, "xpath_element"]);

        $expandstepxpath  = $this->get_xpath_for_step_moveup_button($stepxpath);
        $this->wait_and_click($expandstepxpath);
    }

    /**
     * Return the xpath for phase title field with wrapper xpath
     * @return string
     */
    protected function get_xpath_for_add_phase_link() {
        return "//a[@data-action='add_phase'][1]";
    }

    /**
     * Return the xpath for phase title field with wrapper xpath
     * @param string $xpathphasewrapper
     * @return string
     */
    protected function get_xpath_for_phase_title($xpathphasewrapper) {
        return $xpathphasewrapper . "/descendant::input[contains(@class, 'phase-title')]";
    }

    /**
     * Return the xpath for phase expand button with wrapper xpath
     * @param string $xpathphasewrapper
     * @return string
     */
    protected function get_xpath_for_phase_moveup_button($xpathphasewrapper) {
        return $xpathphasewrapper . "/descendant::div[contains(@class, 'phase-controls')]" .
            "/descendant::a[contains(@data-action, 'phase_up_control')]";
    }

    /**
     * Return the xpath for step expand button with wrapper xpath
     * @param string $xpathstepwrapper
     * @return string
     */
    protected function get_xpath_for_step_moveup_button($xpathstepwrapper) {
        return $xpathstepwrapper . "/descendant::div[contains(@class, 'step-controls')]" .
            "/descendant::a[contains(@data-action, 'step_up_control')]";
    }

    /**
     * Return the xpath for phase expand button with wrapper xpath
     * @param string $xpathphasewrapper
     * @return string
     */
    protected function get_xpath_for_phase_expand_button($xpathphasewrapper) {
        return $xpathphasewrapper . "/descendant::div[contains(@class, 'phase-controls')]" .
            "/descendant::a[contains(@data-action, 'phase_collapse_control')]";
    }

    /**
     * Return the xpath for cycle expand button with wrapper xpath
     * @param string $xpathcyclewrapper
     * @return string
     */
    protected function get_xpath_for_cycle_expand_button($xpathcyclewrapper) {
        return $xpathcyclewrapper . "/descendant::div[contains(@class, 'cycle-controls')]" .
            "/descendant::a[contains(@data-action, 'cycle_collapse_control')]";
    }

    /**
     * Return the xpath for expand all phase button with wrapper xpath
     * @return string
     */
    protected function get_xpath_for_expand_all_phases_button() {
        return "//div[@class='phase-container-controls']/descendant::a[contains(@data-action, 'expand_all_phases')]";
    }

    /**
     * Return the xpath for phase container with heading
     * @param string $heading
     * @return string
     */
    protected function get_xpath_for_phase_wrapper($heading) {
        return "//div[contains(@class, 'phase-header-title') and contains(., '" . $this->escape($heading) . "')]" .
            "/ancestor::div[contains(@class, 'phase-wrapper')]";
    }

    /**
     * Return the xpath for cycle container with title
     * @param string $title
     * @return string
     */
    protected function get_xpath_for_cycle_wrapper($title) {
        return "//div[contains(@class, 'cycle-header-title') and contains(., '" . $this->escape($title) . "')]" .
            "/ancestor::div[contains(@class, 'cycle-wrapper')]";
    }

    /**
     * Return the xpath for step container with title
     * @param string $rollovertext
     * @return string
     */
    protected function get_xpath_for_step_wrapper($rollovertext) {
        return "//div[contains(@class, 'step-header-title') and contains(., '" . $this->escape($rollovertext) . "')]" .
            "/ancestor::div[contains(@class, 'step-wrapper')]";
    }

    /**
     * Return the xpath for the learning objective textbox by number
     * @param integer $number
     * @return string
     */
    protected function get_xpath_for_learning_objective_input($number) {
        $index = $number - 1;
        return "//tr[contains(@class, 'learningobjective') and contains(@data-id, '" . $index . "')]" .
            "/descendant::input[contains(@class, 'learning-objective-name')]";
    }

    /**
     * Return the xpath for the learning objective move up anchor
     * @param string $lotitle
     * @return string
     */
    protected function get_xpath_for_learning_objective_moveup_button($lotitle) {
        return "//input[contains(@class, 'learning-objective-name') and contains(@value, '" . $this->escape($lotitle) . "')]" .
            "/ancestor::tr[contains(@class, 'learningobjective')]" .
            "/descendant::a[contains(@class, 'learningobjective-up-control')]";
    }

    /**
     * Return the xpath for the learning objective move up anchor
     * @param string $lotitle
     * @return string
     */
    protected function get_xpath_for_learning_objective_movedown_button($lotitle) {
        return "//input[contains(@class, 'learning-objective-name') and contains(@value, '" . $this->escape($lotitle) . "')]" .
            "/ancestor::tr[contains(@class, 'learningobjective')]" .
            "/descendant::a[contains(@class, 'learningobjective-down-control')]";
    }

    /**
     * Return the xpath for the learning objective delete anchor
     * @param string $lotitle
     * @return string
     */
    protected function get_xpath_for_learning_objective_delete_button($lotitle) {
        return "//input[contains(@class, 'learning-objective-name') and contains(@value, '" . $this->escape($lotitle) . "')]" .
            "/ancestor::tr[contains(@class, 'learningobjective')]" .
            "/descendant::a[contains(@class, 'learningobjective-delete-control')]";
    }

    /**
     * Wait for an element to exist and click it.
     *
     * @param string $selector xpath/css/button/etc selector
     * @param string $elementtype element type used by the execute wrappers (default: 'xpath_element')
     * @param bool $wait whether to call wait_until_exists before clicking (default: true)
     */
    protected function wait_and_click($selector, $elementtype = 'xpath_element', $wait = true) {
        if ($wait) {
            $this->execute('behat_general::wait_until_exists', [$selector, $elementtype]);
        }
        $this->execute('behat_general::i_click_on', [$selector, $elementtype]);
    }

    /**
     * Wait for a field to exist and set its value.
     *
     * @param string $selector xpath selector for the field
     * @param string $value the value to set
     * @param string $elementtype element type used by the execute wrappers (default: 'xpath_element')
     */
    protected function wait_and_set_field($selector, $value, $elementtype = 'xpath_element') {
        $this->execute('behat_general::wait_until_exists', [$selector, $elementtype]);
        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$selector, $value]);
    }

    /**
     * Adds cycles to the last phase in the roadmap
     *
     * @When /^I add cycles to the last phase with:$/
     * @param TableNode $data with data for filling in the new cycle form
     */
    public function i_add_cycles_to_last_phase_with(TableNode $data) {
        foreach ($data as $row) {
            // Find the last phase wrapper and ensure it's expanded.
            $lastphasexpath = "//div[contains(@class, 'phase-wrapper')][last()]";
            $expandphasexpath = $this->get_xpath_for_expand_all_phases_button();

            // Wait for the phase to exist and be interactable.
            $this->wait_and_click($expandphasexpath);

            // Click add cycle button.
            $addcyclexpath = $lastphasexpath . "/descendant::a[@data-action='add_phase_cycle']";
            $this->wait_and_click($addcyclexpath);

            // Find the last cycle added and expand it.
            $lastcyclexpath = $lastphasexpath . "/descendant::div[contains(@class, 'cycle-wrapper')][last()]";
            $expandcyclexpath = $lastcyclexpath . "/descendant::a[@data-action='cycle_collapse_control']";
            $this->wait_and_click($expandcyclexpath);

            // Fill in cycle data.
            if (isset($row['title'])) {
                $cycletitlexpath = $lastcyclexpath . "/descendant::input[contains(@class, 'cycle-title')]";
                $this->wait_and_set_field($cycletitlexpath, $row['title']);
            }

            if (isset($row['subtitle'])) {
                $cyclesubtitlexpath = $lastcyclexpath . "/descendant::input[contains(@class, 'cycle-subtitle')]";
                $this->wait_and_set_field($cyclesubtitlexpath, $row['subtitle']);
            }

            if (isset($row['pagelink'])) {
                $cyclepagelinkxpath = $lastcyclexpath . "/descendant::input[contains(@class, 'cycle-pagelink')]";
                $this->wait_and_set_field($cyclepagelinkxpath, $row['pagelink']);
            }
        }
    }

    /**
     * Adds steps to the last cycle in the last phase
     *
     * @When /^I add steps to the last cycle with:$/
     * @param TableNode $data with data for filling in the new step form
     */
    public function i_add_steps_to_last_cycle_with(TableNode $data) {
        foreach ($data as $row) {
            // Find the last phase wrapper
            $lastphasexpath = "//div[contains(@class, 'phase-wrapper')][last()]";
            // Find the last cycle within that phase
            $lastcyclexpath = $lastphasexpath . "/descendant::div[contains(@class, 'cycle-wrapper')][last()]";

            // Wait for the cycle to exist
            $this->execute('behat_general::wait_until_exists', [$lastcyclexpath, "xpath_element"]);

            // Click add step button
            $addstepxpath = $lastcyclexpath . "/descendant::a[@data-action='add_cycle_step']";
            $this->wait_and_click($addstepxpath);

            // Find the last step added and expand it
            $laststepxpath = $lastcyclexpath . "/descendant::div[contains(@class, 'step-wrapper')][last()]";
            $expandstepxpath = $laststepxpath . "/descendant::a[@data-action='step_collapse_control']";
            $this->wait_and_click($expandstepxpath);

            // Fill in step data
            if (isset($row['rollovertext'])) {
                $steprollovertextxpath = $laststepxpath . "/descendant::input[contains(@class, 'step-rollovertext')]";
                $this->wait_and_set_field($steprollovertextxpath, $row['rollovertext']);

                if (isset($row['completionmodule'])) {
                    $this->i_configure_step_to_require_completion_of($row['rollovertext'], $row['completionmodule']);

                    if (isset($row['pagelink'])) {
                        $singlelinkcheckboxxpath = $laststepxpath . "/descendant::input[contains(@class, 'chk-single-activity-link')]";
                        $this->wait_and_click($singlelinkcheckboxxpath);

                        $steppagelinkxpath = $laststepxpath . "/descendant::input[contains(@class, 'step-single-activity-link')]";
                        $this->wait_and_set_field($steppagelinkxpath, $row['pagelink']);
                    }
                }
            }
        }
    }

    /**
     * Check that a step displays in the roadmap
     *
     * @Then /^I should see step "(?P<rollovertext>(?:[^"]|\\")*)" in the roadmap$/
     * @param string $rollovertext the rollover text of the step
     */
    public function i_should_see_step_in_roadmap($rollovertext) {
        $stepxpath = "//div[contains(@class, 'roadmap-step') and contains(@title, '" . $this->escape($rollovertext) . "')]";
        $this->execute("behat_general::should_exist", [$stepxpath, "xpath_element"]);
    }

    /**
     * Check that step icon is displaying a certain percentage
     *
     * @Then /^I should see step icon for "(?P<rollovertext>(?:[^"]|\\")*)" showing "(?P<percentage>(?:[^"]|\\")*)" percent completed/
     * @param string $rollovertext the rollover text of the step
     * @param string $percentage the expected completion percentage
     */
    public function i_should_see_step_icon_showing_completion($rollovertext, $percentage) {
        $stepxpath = "//div[contains(@class, 'roadmap-step') and contains(@title, '" . $this->escape($rollovertext) . "')]";
        $this->execute("behat_general::should_exist", [$stepxpath, "xpath_element"]);

        // Check for completion percentage text.
        $percentagexpath = $stepxpath . "/img[contains(@src, 'percent=" . $this->escape($percentage) . "')]";
        $this->execute("behat_general::should_exist", [$percentagexpath, "xpath_element"]);
    }

    /**
     * Set up activity completion requirements for a step
     *
     * @When /^I configure step "(?P<rollovertext>(?:[^"]|\\")*)" to require completion of "(?P<activities>(?:[^"]|\\")*)"$/
     * @param string $rollovertext the rollover text of the step to configure
     * @param string $activities comma-separated list of activity names
     */
    public function i_configure_step_to_require_completion_of($rollovertext, $activities) {
        // Find the step wrapper by rollover text.
        $stepxpath = "//div[contains(@class, 'step-header-title') and contains(text(), '" . $this->escape($rollovertext) . "')]" .
            "/ancestor::div[contains(@class, 'step-wrapper')]";

        // Wait for the step to exist.
        $this->execute('behat_general::wait_until_exists', [$stepxpath, "xpath_element"]);

        // Click the completion selector button.
        $completionbuttonxpath = $stepxpath . "/descendant::button[contains(@class, 'btn_completion_selector')]";
        $this->wait_and_click($completionbuttonxpath);

        // Verify modal is open.
        $this->execute("behat_general::should_exist", ["div.modal", "css_element"]);

        // Select activities from the list.
        $activitylist = explode(',', $activities);
        foreach ($activitylist as $activity) {
            $activity = trim($activity);
            $activitycheckboxxpath = "//div[@id='activity-select-window']//input[@type='checkbox' and @data-name='" . $this->escape($activity) . "']";
            $this->wait_and_click($activitycheckboxxpath);
        }

        // Save the modal.
        $this->i_save_activity_completion_modal();
    }

    /**
     * Configure step icon through modal interface
     *
     * @When /^I configure step "(?P<rollovertext>(?:[^"]|\\")*)" to use icon "(?P<icon_name>(?:[^"]|\\")*)"$/
     * @param string $rollovertext the rollover text of the step to configure
     * @param string $iconname the name of the icon to select
     */
    public function i_configure_step_to_use_icon($rollovertext, $iconname) {
        // Find the step wrapper by rollover text.
        $stepxpath = "//input[contains(@class, 'step-rollovertext') and contains(@value, '" . $this->escape($rollovertext) . "')]" .
            "/ancestor::div[contains(@class, 'step-wrapper')]";

        // Click the icon selector button.
        $iconbuttonxpath = $stepxpath . "/descendant::button[contains(@class, 'btn_icon_selector')]";
        $this->wait_and_click($iconbuttonxpath);

        // Verify modal is open.
        $this->execute("behat_general::should_exist", ["div.modal", "css_element"]);

        // Select the icon.
        $iconxpath = "//div[contains(@class, 'modal-body')]//img[contains(@data-iconfilename, '" . $this->escape($iconname) . "')]";
        $this->wait_and_click($iconxpath);

        // Save the modal.
        $this->execute("behat_general::i_click_on", ["Save", "button"]);
    }

    /**
     * Verify activity completion modal opens and contains expected activities
     *
     * @When /^I open activity completion modal for step "(?P<rollovertext>(?:[^"]|\\")*)"$/
     * @param string $rollovertext the rollover text of the step
     */
    public function i_open_activity_completion_modal_for_step($rollovertext) {
        // Find the step wrapper by rollover text.
        $stepxpath = "//div[contains(@class, 'step-header-title') and contains(text(), '" . $this->escape($rollovertext) . "')]" .
            "/ancestor::div[contains(@class, 'step-wrapper')]";

        // Wait for the step to exist.
        $this->execute('behat_general::wait_until_exists', [$stepxpath, "xpath_element"]);

        // Click the completion selector button.
        $completionbuttonxpath = $stepxpath . "/descendant::button[contains(@class, 'btn_completion_selector')]";
        $this->wait_and_click($completionbuttonxpath);

        // Verify modal is open.
        $this->execute("behat_general::should_exist", ["div.modal", "css_element"]);
    }

    /**
     * Verify activity is available in completion modal
     *
     * @Then /^I should see activity "(?P<activity_name>(?:[^"]|\\")*)" in the completion modal$/
     * @param string $activityname the name of the activity to check for
     */
    public function i_should_see_activity_in_completion_modal($activityname) {
        // Wait for the modal content to load.
        $this->execute('behat_general::wait_until_exists', ["div#activity-select-window", "css_element"]);

        $activityxpath = "//div[@id='activity-select-window']//input[@type='checkbox' and @data-name='" . $this->escape($activityname) . "']";
        $this->execute("behat_general::should_exist", [$activityxpath, "xpath_element"]);
    }

    /**
     * Check an activity in the completion modal
     *
     * @When /^I check activity "(?P<activity_name>(?:[^"]|\\")*)" in the completion modal$/
     * @param string $activityname the name of the activity to check
     */
    public function i_check_activity_in_completion_modal($activityname) {
        // Wait for the modal content to be fully loaded.
        $this->execute('behat_general::wait_until_exists', ["div#activity-select-window", "css_element"]);

        $activitycheckboxxpath = "//div[@id='activity-select-window']//input[@type='checkbox' and @data-name='" . $this->escape($activityname) . "']";
        $this->wait_and_click($activitycheckboxxpath);
    }

    /** Verify an activity is checked in the completion modal
     *
     * @Then /^activity "(?P<activity_name>(?:[^"]|\\")*)" should be checked in the completion modal$/
     * @param string $activityname the name of the activity to check
     */
    public function activity_should_be_checked_in_completion_modal($activityname) {
        // Wait for the modal content to be fully loaded.
        $this->execute('behat_general::wait_until_exists', ["div#activity-select-window", "css_element"]);

        $activitycheckboxxpath = "//div[@id='activity-select-window']//input[@type='checkbox' and @data-name='" . $this->escape($activityname) . "']";
        $this->execute('behat_general::wait_until_exists', [$activitycheckboxxpath, "xpath_element"]);
        // The checked xpath would be: $checkedxpath = $activitycheckboxxpath . "[@checked='checked']".
        $this->execute("behat_general::should_exist", [$activitycheckboxxpath, "xpath_element"]);
    }

    /**
     * Save and close the activity completion modal
     *
     * @When /^I save the activity completion modal$/
     */
    public function i_save_activity_completion_modal() {
        // Wait for the Save button to be available.
        $savebuttonxpath = "//div[contains(@class, 'modal-footer')]" .
            "/descendant::button[contains(@data-action, 'save')]";

        $this->wait_and_click($savebuttonxpath);
    }

    /**
     * Cancel and close the activity completion modal
     *
     * @When /^I cancel the activity completion modal$/
     */
    public function i_cancel_activity_completion_modal() {
        // Wait for the Cancel button to be available.
        $cancelbuttonxpath = "//div[contains(@class, 'modal-footer')]" .
            "/descendant::button[contains(@data-action, 'save')]";

        $this->wait_and_click($cancelbuttonxpath);
    }

    /**
     * Verify that selected activities are displayed in the step configuration
     *
     * @Then /^step "(?P<rollovertext>(?:[^"]|\\")*)" should show selected activities "(?P<activities>(?:[^"]|\\")*)"$/
     * @param string $rollovertext the rollover text of the step
     * @param string $activities comma-separated list of expected activity names
     */
    public function step_should_show_selected_activities($rollovertext, $activities) {
        // Find the step wrapper.
        $stepxpath = "//div[contains(@class, 'step-header-title') and contains(text(), '" . $this->escape($rollovertext) . "')]" .
            "/ancestor::div[contains(@class, 'step-wrapper')]";

        // Wait for the step to exist.
        $this->execute('behat_general::wait_until_exists', [$stepxpath, "xpath_element"]);

        // Check each activity is in the completion list.
        $activitylist = explode(',', $activities);
        foreach ($activitylist as $activity) {
            $activity = trim($activity);
            $activitylistxpath = $stepxpath . "/descendant::ul[contains(@class, 'step-completion-list')]/descendant::li[contains(., '" . $this->escape($activity) . "')]";
            $this->execute('behat_general::wait_until_exists', [$activitylistxpath, "xpath_element"]);
            $this->execute("behat_general::should_exist", [$activitylistxpath, "xpath_element"]);
        }
    }
}
