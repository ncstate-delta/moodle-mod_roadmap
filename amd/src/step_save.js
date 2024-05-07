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
            StepSave.prototype.loadList();
            StepSave.prototype.saveStep(this);
        };

        StepSave.prototype.loadList = function() {
            var activityData = JSON.parse($('input[name="activity_data"]').val());
            var listAreas = $('ul.step-completion-list');

            $(listAreas).each(function(i, e) {

                // Get the configuration line from the local hidden field
                var stepCompletionModules = $(this).closest('.step-activity-container').find('.step-completion-modules').val();
                var selectedIds = stepCompletionModules.split(',');

                $(e).children('li').remove();
                // Use the selected ids to get course module information
                activityData.activities.forEach(function(activity) {
                    if ($.inArray(activity.id, selectedIds) >= 0) {
                        var li = $('<li/>').attr('data-id', activity.id).appendTo($(e));
                        $('<span>').text(activity.name).appendTo(li);
                        $(e).append(li);
                    }
                });
            });
        };

        StepSave.prototype.saveStep = function(event) {
            // Find step container that we are saving data from.
            var stepContainer = $(event.target).closest('.step-container');

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
                completionexpectedcmid: stepContainer.find('.fitem input.expectedcomplete-coursemoduleid').val(),
                completionexpecteddatetime: stepContainer.find('.fitem input.expectedcomplete-datetime').val(),
            };

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