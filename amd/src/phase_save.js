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
 * Handle the saving of phase data and rebinding of inputs.
 *
 * @module     mod_roadmap/phase_save
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    /**
     * PhaseSave class handles saving and rebinding for roadmap phases.
     * @class
     */
    class PhaseSave {
        /**
         * Rebinds all inputs related to phase and attaches save handlers.
         */
        rebindInputs() {
            $('.phase-title').off('change').on('change', this.savePhase.bind(this));
            $('.phase-cycles-container .cycle-wrapper .cycle-configuration')
                .off('change').on('change', this.savePhase.bind(this));
            // Initial save for all phases (for new fields added)
            $('.phase-container').each((_, el) => {
                this.savePhase({target: el});
            });
        }

        /**
         * Saves the phase data to its hidden config input.
         * @param {Event|Object} event Event or jQuery-wrapped element reference.
         */
        savePhase(event) {
            // Support both event and direct call with element
            const phaseContainer = $(event.target).closest('.phase-container');
            const phaseCycles = [];
            let index = 0;
            phaseContainer.find('.phase-cycles-container .cycle-wrapper .cycle-configuration').each(function() {
                let cycleData = $(this).val() || '{}';
                let cycleDataObj = JSON.parse(cycleData);
                cycleDataObj.index = index++;
                phaseCycles.push(cycleDataObj);
            });
            const title = phaseContainer.find('.fitem input.phase-title').val();
            const phaseData = {
                id: phaseContainer.closest('.phase-wrapper').data('phaseid'),
                title: title,
                cycles: phaseCycles,
            };

            phaseContainer.closest('.phase-wrapper').find('.phase-header-title').html(title);
            phaseContainer.children('input.phase-configuration')
                .val(JSON.stringify(phaseData))
                .triggerHandler("change");
         }
    }

    // AMD export
    const instance = new PhaseSave();
    return {
        /**
         * Initialize PhaseSave (returns instance).
         * @returns {PhaseSave}
         */
        init: function() {
            return instance;
        },
        /**
         * Rebinds all phase input handlers.
         */
        rebindInputs: function() {
            instance.rebindInputs();
        }
    };
});