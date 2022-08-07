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
 * @module     mod_roadmap/stepiconselect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'],
    function($) {

        var StepSave = function() {

        };

        StepSave.prototype.rebind_inputs = function() {
            $('.step-rollovertext').unbind('change').change(this.saveStep.bind(this));
            $('.chk-single-activity-link').unbind('change').change(this.saveStep.bind(this));
            $('.step-single-activity-link').unbind('change').change(this.saveStep.bind(this));
            $('.completionexpected_day').unbind('change').change(this.saveStep.bind(this));
            $('.completionexpected_month').unbind('change').change(this.saveStep.bind(this));
            $('.completionexpected_year').unbind('change').change(this.saveStep.bind(this));
            $('.completionexpected_hour').unbind('change').change(this.saveStep.bind(this));
            $('.completionexpected_minute').unbind('change').change(this.saveStep.bind(this));
            $('.completionexpected').unbind('change').change(this.saveStep.bind(this));
            $('.step-icon').unbind('change').change(this.saveStep.bind(this));
            $('.step-completion-modules').unbind('change').change(this.saveStep.bind(this));
            StepSave.prototype.saveStep(this);
        };

        StepSave.prototype.saveStep = function(event) {
            var stepContainer = $(event.target).closest('.step-container');
            var expectedComplete = 0;
            if(stepContainer.find('.completionexpected').prop("checked") == true){
                expectedComplete = 1;
            }
            let rollovertext = stepContainer.find('.fitem input.step-rollovertext').val();
            var stepData = {
                id: stepContainer.closest('.step-wrapper').data('stepid'),
                rollovertext: rollovertext,
                stepicon: stepContainer.find('.fitem input.step-icon').val(),
                completionmodules: stepContainer.find('.fitem input.step-completion-modules').val(),
                linksingleactivity: stepContainer.find('.fitem input.chk-single-activity-link').val(),
                pagelink: stepContainer.find('.fitem input.step-single-activity-link').val(),
                expectedcomplete: expectedComplete,
                completionexpected_day: stepContainer.find('.fitem select.completionexpected_day').val(),
                completionexpected_month: stepContainer.find('.fitem select.completionexpected_month').val(),
                completionexpected_year: stepContainer.find('.fitem select.completionexpected_year').val(),
                completionexpected_hour: stepContainer.find('.fitem select.completionexpected_hour').val(),
                completionexpected_minute: stepContainer.find('.fitem select.completionexpected_minute').val(),
            };
            delete stepData.days;
            delete stepData.months;
            delete stepData.years;
            delete stepData.hours;
            delete stepData.minutes;
            stepContainer.closest('.step-wrapper').find('.step-header-title').html(rollovertext);
            stepContainer.children('input.step-configuration').val(JSON.stringify(stepData)).triggerHandler("change");
        };

        return {

            init: function() {
                return new StepSave();
            },

            rebind_inputs: function() {
                StepSave.prototype.rebind_inputs();
            }
        };
    });