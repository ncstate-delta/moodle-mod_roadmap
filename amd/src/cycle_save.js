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

        var CycleSave = function() {

        };

        CycleSave.prototype.rebind_inputs = function() {

            $('.cycle-title').unbind('change').change(this.saveCycle.bind(this));
            $('.cycle-subtitle').unbind('change').change(this.saveCycle.bind(this));
            $('.cycle-pagelink').unbind('change').change(this.saveCycle.bind(this));
            $('.chk-learning-objectives input[type="checkbox"]').unbind('change').change(this.saveCycle.bind(this));
            $('.cycle-steps-container .step-wrapper .step-configuration').unbind('change').change(this.saveCycle.bind(this));
            CycleSave.prototype.saveCycle(this);
        };

        CycleSave.prototype.saveCycle = function(event) {

            var cycleContainer = $(event.target).closest('.cycle-container');
            var cycleLos = [];
            $.each(cycleContainer.find('.chk-learning-objectives input[type="checkbox"]'), function(){
                if($(this).prop("checked") == true){
                    cycleLos.push($(this).val());
                }
            });

            var cycleSteps = [];
            $.each(cycleContainer.find('.cycle-steps-container .step-wrapper .step-configuration'), function() {
                let stepData = $(this).val();
                if (stepData == '') { stepData = '{}'; }
                cycleSteps.push(JSON.parse(stepData));
            });
            let title = cycleContainer.find('.fitem input.cycle-title').val();
            var cycleData = {
                id: cycleContainer.closest('.cycle-wrapper').data('cycleid'),
                title: title,
                subtitle: cycleContainer.find('.fitem input.cycle-subtitle').val(),
                pagelink: cycleContainer.find('.fitem input.cycle-pagelink').val(),
                learningobjectives: cycleLos.join(","),
                steps: cycleSteps,
            };
            cycleContainer.closest('.cycle-wrapper').find('.cycle-header-title').html(title);
            cycleContainer.children('input.cycle-configuration').val(JSON.stringify(cycleData)).triggerHandler("change");
        };

        return {

            init: function() {
                return new CycleSave();
            },

            rebind_inputs: function() {
                CycleSave.prototype.rebind_inputs();
            }
        };
    });