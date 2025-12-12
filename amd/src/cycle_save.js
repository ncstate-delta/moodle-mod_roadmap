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
 * Handle the saving of cycle data and rebinding of inputs.
 *
 * @module     mod_roadmap/cycle_save
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    /**
     * CycleSave class handles saving and rebinding for roadmap cycles.
     * @class
     */
    class CycleSave {
        /**
         * Rebinds all inputs related to cycle and attaches save handlers.
         */
        rebindInputs() {
            $('.cycle-title').off('change').on('change', this.saveCycle.bind(this));
            $('.cycle-subtitle').off('change').on('change', this.saveCycle.bind(this));
            $('.cycle-pagelink').off('change').on('change', this.saveCycle.bind(this));
            $('.chk-learning-objectives input[type="checkbox"]')
                .off('change').on('change', this.saveCycle.bind(this));
            $('.cycle-steps-container .step-wrapper .step-configuration')
                .off('change').on('change', this.saveCycle.bind(this));
            // Initial save for all cycles (for new fields added)
            $('.cycle-container').each((_, el) => {
                this.saveCycle({target: el});
            });
        }

        /**
         * Saves the cycle data to its hidden config input.
         * @param {Event|Object} event Event or jQuery-wrapped element reference.
         */
        saveCycle(event) {
            // Support both event and direct call with element
            const cycleContainer = $(event.target).closest('.cycle-container');
            const cycleLos = [];
            cycleContainer.find('.chk-learning-objectives input[type="checkbox"]').each(function() {
                if ($(this).prop("checked")) {
                    cycleLos.push($(this).val());
                }
            });

            const cycleSteps = [];
            cycleContainer.find('.cycle-steps-container .step-wrapper .step-configuration').each(function() {
                let stepData = $(this).val() || '{}';
                cycleSteps.push(JSON.parse(stepData));
            });

            const title = cycleContainer.find('.fitem input.cycle-title').val();
            const cycleData = {
                id: cycleContainer.closest('.cycle-wrapper').data('cycleid'),
                title: title,
                subtitle: cycleContainer.find('.fitem input.cycle-subtitle').val(),
                pagelink: cycleContainer.find('.fitem input.cycle-pagelink').val(),
                learningobjectives: cycleLos.join(","),
                steps: cycleSteps,
            };

            cycleContainer.closest('.cycle-wrapper').find('.cycle-header-title').html(title);
            cycleContainer.children('input.cycle-configuration')
                .val(JSON.stringify(cycleData))
                .triggerHandler("change");
        }
    }

    // AMD export
    const instance = new CycleSave();
    return {
        /**
         * Initialize CycleSave (returns instance).
         * @returns {CycleSave}
         */
        init: function() {
            return instance;
        },
        /**
         * Rebinds all cycle input handlers.
         */
        rebindInputs: function() {
            instance.rebindInputs();
        }
    };
});