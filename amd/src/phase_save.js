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

        var PhaseSave = function() {
            // Do nothing.
        };

        PhaseSave.prototype.rebindInputs = function() {
            $('.phase-title').unbind('change').change(this.savePhase.bind(this));
            $('.phase-cycles-container .cycle-wrapper .cycle-configuration').unbind('change').change(this.savePhase.bind(this));
            PhaseSave.prototype.savePhase(this);
        };

        PhaseSave.prototype.savePhase = function(event) {
            var phaseContainer = $(event.target).closest('.phase-container');
            var phaseCycles = [];
            let index = 0;
            $.each(phaseContainer.find('.phase-cycles-container .cycle-wrapper .cycle-configuration'), function() {
                let cycleData = $(this).val();
                if (cycleData == '') {
                    cycleData = '{}';
                }
                let cycleDataObj = JSON.parse(cycleData);
                cycleDataObj.index = index;
                index = index + 1;
                phaseCycles.push(cycleDataObj);
            });
            let title = phaseContainer.find('.fitem input.phase-title').val();
            var phaseData = {
                id: phaseContainer.closest('.phase-wrapper').data('phaseid'),
                title: title,
                cycles: phaseCycles,
            };
            phaseContainer.closest('.phase-wrapper').find('.phase-header-title').html(title);
            phaseContainer.children('input.phase-configuration').val(JSON.stringify(phaseData)).triggerHandler("change");
        };

        return {

            init: function() {
                return new PhaseSave();
            },

            rebindInputs: function() {
                PhaseSave.prototype.rebindInputs();
            }
        };
    });