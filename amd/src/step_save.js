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
 * Handle the saving of step data and rebinding of inputs.
 *
 * @module     mod_roadmap/step_save
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    /**
     * StepSave class handles saving and rebinding for roadmap steps.
     * @class
     */
    class StepSave {
        /**
         * Rebinds all inputs related to step and attaches save handlers.
         */
        rebindInputs() {
            $('.roadmap-form-control-step')
                .off('change').on('change', this.saveStep.bind(this));
            this.loadList();
            // Initial save for all steps (for new fields added)
            $('.step-container').each((_, el) => {
                this.saveStep({target: el});
            });
        }

        /**
         * Loads and renders the completion activity list for each step.
         */
        loadList() {
            const activityData = JSON.parse($('input[name="activity_data"]').val() || '{}');
            $('ul.step-completion-list').each(function() {
                const $list = $(this);
                // Get the configuration line from the local hidden field
                const stepCompletionModules = $list.closest('.step-activity-container')
                    .find('.step-completion-modules').val() || '';
                const selectedIds = stepCompletionModules.split(',').filter(x => x);

                $list.empty();
                if (activityData.activities && Array.isArray(activityData.activities)) {
                    activityData.activities.forEach(function(activity) {
                        if (selectedIds.includes(activity.id)) {
                            const li = $('<li/>').attr('data-id', activity.id);
                            $('<span>').text(activity.name).appendTo(li);
                            $list.append(li);
                        }
                    });
                }
            });
        }

        /**
         * Saves the step data to its hidden config input.
         * @param {Event|Object} event Event or jQuery-wrapped element reference.
         */
        saveStep(event) {
            // Support both event and direct call with element
            const $stepContainer = $(event.target).closest('.step-container');
            // Link single activity check box option.
            const linksingleactivity = $stepContainer.find('.fitem input.chk-single-activity-link')
                .prop("checked") ? 1 : 0;

            const rollovertext = $stepContainer.find('.fitem input.step-rollovertext').val();

            const stepData = {
                id: $stepContainer.closest('.step-wrapper').data('stepid'),
                rollovertext: rollovertext,
                stepicon: $stepContainer.find('.fitem input.step-icon').val(),
                completionmodules: $stepContainer.find('.fitem input.step-completion-modules').val(),
                linksingleactivity: linksingleactivity,
                pagelink: $stepContainer.find('.fitem input.step-single-activity-link').val(),
                completionexpectedcmid: $stepContainer.find('.fitem input.expectedcomplete-coursemoduleid').val(),
                completionexpecteddatetime: $stepContainer.find('.fitem input.expectedcomplete-datetime').val(),
            };

            $stepContainer.closest('.step-wrapper').find('.step-header-title').html(rollovertext);
            $stepContainer.children('input.step-configuration')
                .val(JSON.stringify(stepData))
                .triggerHandler("change");
        }
    }

    // AMD export
    const instance = new StepSave();
    return {
        /**
         * Initialize StepSave (returns instance).
         * @returns {StepSave}
         */
        init: function() {
            return instance;
        },
        /**
         * Rebinds all step input handlers.
         */
        rebindInputs: function() {
            instance.rebindInputs();
        }
    };
});