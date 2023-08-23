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
 * Handle opening a dialogue to configure scale data.
 *
 * @module     mod_roadmap/stepsave
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'],
    function($) {

        var StepSave = function() {
            // Do nothing.
        };

        StepSave.prototype.rebindInputs = function() {
            // Rebind any new fields with save step on change.
            $('.roadmap-form-control-step')
                .unbind('change')
                .change(this.saveStep.bind(this));

            // Run save step if a new field has been added.
            StepSave.prototype.saveStep(this);
        };

        StepSave.prototype.saveStep = function(event) {
            // Find step container that we are saving data from.
            var stepContainer = $(event.target).closest('.step-container');

            // Expected complete check box option.
            var expectedComplete = 0;
            if (stepContainer.find('.completionexpected').prop("checked") == true) {
                expectedComplete = 1;
            }

            // Link single activity check box option.
            var linksingleactivity = 0;
            if (stepContainer.find('.fitem input.chk-single-activity-link').prop("checked") == true) {
                linksingleactivity = 1;
            }

            // Save rollover text and use to set the step header title.
            let rollovertext = stepContainer.find('.fitem input.step-rollovertext').val();

            // Step data object collection.
            var stepData = {
                id: stepContainer.closest('.step-wrapper').data('stepid'),
                rollovertext: rollovertext,
                stepicon: stepContainer.find('.fitem input.step-icon').val(),
                completionmodules: stepContainer.find('.fitem input.step-completion-modules').val(),
                linksingleactivity: linksingleactivity,
                pagelink: stepContainer.find('.fitem input.step-single-activity-link').val(),
                expectedcomplete: expectedComplete,
                completionexpectedday: stepContainer.find('.fitem select.completionexpectedday').val(),
                completionexpectedmonth: stepContainer.find('.fitem select.completionexpectedmonth').val(),
                completionexpectedyear: stepContainer.find('.fitem select.completionexpectedyear').val(),
                completionexpectedhour: stepContainer.find('.fitem select.completionexpectedhour').val(),
                completionexpectedminute: stepContainer.find('.fitem select.completionexpectedminute').val(),
            };

            // Clean up data object calendar binding data.
            delete stepData.days;
            delete stepData.months;
            delete stepData.years;
            delete stepData.hours;
            delete stepData.minutes;

            // Set the header title with the new step roll over text and save the step data object as json to config.
            stepContainer.closest('.step-wrapper').find('.step-header-title').html(rollovertext);
            stepContainer.children('input.step-configuration').val(JSON.stringify(stepData)).triggerHandler("change");
        };

        return {
            init: function() {
                return new StepSave();
            },

            rebindInputs: function() {
                StepSave.prototype.rebindInputs();
            }
        };
    });