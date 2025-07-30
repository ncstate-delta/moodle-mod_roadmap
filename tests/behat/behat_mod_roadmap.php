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

use Behat\Gherkin\Node\TableNode as TableNode;

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions related to mod_quiz.
 *
 * @copyright 2014 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_roadmap extends behat_question_base {

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
            // Accepting the alert so the framework can continue properly running
            // the following scenarios. Some browsers already closes the alert, so
            // wrapping in a try & catch.
            $this->getSession()->getDriver()->getWebDriver()->switchTo()->alert()->accept();
        } catch (Exception $e) {
            // Something other than the alert broke.
            return false;
        }
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
            $this->execute("behat_forms::i_set_the_field_with_xpath_to", [$lotitlexpath, $row['title']]);
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
            $this->execute('behat_general::click_link', [$addphasexpath, "xpath_element"]);
            $this->execute('behat_general::assert_page_contains_text', 'Phase 1');

            $heading = "Phase 1";

            // Expand the new phase.
            $phasewrapperxpath = $this->get_xpath_for_phase_wrapper($heading);
            $expandphasexpath  = $this->get_xpath_for_phase_expand_button($phasewrapperxpath);
            $this->execute("behat_general::i_click_on", [$expandphasexpath, "xpath_element"]);

            $phasetitlexpath = $this->get_xpath_for_phase_title($phasewrapperxpath);
            $this->execute("behat_forms::i_set_the_field_with_xpath_to", [$phasetitlexpath, $row['title']]);
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
        $this->execute("behat_general::i_click_on", [$expandphasexpath, "xpath_element"]);
    }

    /**
     * Return the xpath for phase title field with wrapper xpath
     * @param string $xpathphasewrapper
     * @return string
     */
    protected function get_xpath_for_add_phase_link() {
        return "//div[contains(@class, 'phase-container-controls')" .
            "/descendant::a[contains(@data-action, 'add_phase')]";
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
            "/descendant::a[contains(@data-action, 'phase_collapse_control')]";
    }

    /**
     * Return the xpath for phase expand button with wrapper xpath
     * @param string $xpathphasewrapper
     * @return string
     */
    protected function get_xpath_for_phase_expand_button($xpathphasewrapper) {
        return $xpathphasewrapper . "/descendant::div[contains(@class, 'phase-controls')]" .
            "/descendant::a[contains(@class, 'phase-collapse-control')]";
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
}
