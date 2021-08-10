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
 * Handle the configuration of the roadmap.
 *
 * @module     mod_roadmap/roadmapconfig
 * @package    mod_roadmap
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates', 'mod_roadmap/expand_contract',
        'mod_roadmap/learningobjectivesconfig', 'mod_roadmap/step_icon_select', 'mod_roadmap/step_activity_select',
        'mod_roadmap/step_save', 'mod_roadmap/cycle_save', 'mod_roadmap/phase_save'],
    function($, notification, templates, ec, learningobjectives, stepiconselect,
             stepactivityselect, stepsave, cyclesave, phasesave) {

        /**
         * Learning objectives config object.
         * @param {String} inputSelector The hidden input field selector.
         */
        var RoadmapConfig = function(inputSelector, inputConfig) {
            this.inputSelector = inputSelector;
            this.configContainer = $(inputConfig);

            this.initForm();
        };

        /**
         * Displays the scale configuration dialogue.
         *
         * @method initForm
         */
        RoadmapConfig.prototype.initForm = function() {
            var self = this;
            var inputConfigVal = this.configContainer.val();
            if (inputConfigVal == '') {
                inputConfigVal = JSON.stringify({ 'phases':[] });
                this.configContainer.val(inputConfigVal);
            }
            var config = JSON.parse(inputConfigVal);

            var phaseId = 0;
            var cycleId = 0;
            var stepId = 0;
            $.each(config.phases, function(p) {
                let phase = config.phases[p];
                phaseId = phaseId + 1;
                phase.id = phaseId;
                phase.number = p + 1;
                phase.index = p;
                phase.configuration = JSON.stringify(phase);

                $.each(phase.cycles, function(c) {
                    let cycle = phase.cycles[c];
                    cycleId = cycleId + 1;
                    cycle.id = cycleId;
                    cycle.number = c + 1;
                    cycle.index = c;
                    cycle.configuration = JSON.stringify(cycle);

                    $.each(cycle.steps, function(s) {
                        let step = cycle.steps[s];
                        stepId = stepId + 1;
                        step.id = stepId;
                        step.number = s + 1;
                        step.index = s;
                        step.configuration = JSON.stringify(step);
                    });
                });
            });

            // Dish up the form.
            templates.render('mod_roadmap/configuration_phases', config)
                .then(function(html, js) {
                    templates.prependNodeContents(self.inputSelector, html, js);

                    $('#add-phase').click(function(e) { RoadmapConfig.prototype.addPhase(e); });
                    $('.add-phase-cycle').click(function(e) { RoadmapConfig.prototype.addCycle(e); });
                    $('.add-cycle-step').click(function(e) { RoadmapConfig.prototype.addStep(e); });

                    $('.phase-collapse-control').click(function(e) { RoadmapConfig.prototype.collapsePhase(e); });
                    $('.cycle-collapse-control').click(function(e) { RoadmapConfig.prototype.collapseCycle(e); });
                    $('.step-collapse-control').click(function(e) { RoadmapConfig.prototype.collapseStep(e); });

                    $('.phase-delete-control').click(function (e) { RoadmapConfig.prototype.deletePhase(e); });
                    $('.cycle-delete-control').click(function (e) { RoadmapConfig.prototype.deleteCycle(e); });
                    $('.step-delete-control').click(function (e) { RoadmapConfig.prototype.deleteStep(e); });

                    $('.phase-up-control').click(function (e) { RoadmapConfig.prototype.upPhase(e); });
                    $('.phase-down-control').click(function (e) { RoadmapConfig.prototype.downPhase(e); });
                    $('.cycle-up-control').click(function (e) { RoadmapConfig.prototype.upCycle(e); });
                    $('.cycle-down-control').click(function (e) { RoadmapConfig.prototype.downCycle(e); });
                    $('.step-up-control').click(function (e) { RoadmapConfig.prototype.upStep(e); });
                    $('.step-down-control').click(function (e) { RoadmapConfig.prototype.downStep(e); });

                    learningobjectives.refresh_checklists();

                    stepiconselect.rebind_buttons();
                    stepactivityselect.rebind_buttons();

                    stepsave.rebind_inputs();
                    cyclesave.rebind_inputs();
                    phasesave.rebind_inputs();

                    RoadmapConfig.prototype.bindConfigSave();
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.bindConfigSave = function() {
            $('input.phase-configuration').unbind('change').change(this.configSave.bind(this));
        };

        RoadmapConfig.prototype.configSave = function() {
            var phaseContainer = $('#phase-container');
            var roadmapData = [];
            let index = 0;
            $.each(phaseContainer.find('.phase-wrapper .phase-configuration'), function() {
                let phaseData = $(this).val();
                if (phaseData == '') { phaseData = '{}'; }
                let phaseDataObj = JSON.parse(phaseData);
                phaseDataObj.index = index;
                index = index + 1;
                phaseDataObj.id = index;
                roadmapData.push(phaseDataObj);
            });
            $('input[name="roadmapconfiguration"]').val(JSON.stringify({ phases: roadmapData }));
        };

        RoadmapConfig.prototype.addPhase = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var config = JSON.parse($('input[name="roadmapconfiguration"]').val());
            var nextIndex = config.phases.length;
            var newPhase = {id: nextIndex+1, index: nextIndex, number: nextIndex+1};
            config.phases.push(newPhase);
            $('input[name="roadmapconfiguration"]').val(JSON.stringify(config));

            templates.render('mod_roadmap/configuration_phase', newPhase)
                .then(function(html, js) {
                    templates.appendNodeContents('#phase-container', html, js);

                    $('.phase-collapse-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.collapsePhase(e); });

                    $('.phase-delete-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.deletePhase(e); });

                    $('#add-phase').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.addPhase(e); });

                    $('.add-phase-cycle').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.addCycle(e); });

                    phasesave.rebind_inputs();
                    RoadmapConfig.prototype.bindConfigSave();
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var phaseContainer = thisnode.closest('.phase-container');
            var cycleContainer = phaseContainer.children('.phase-cycles-container');

            var nextCycleIndex = cycleContainer.children('.cycle-wrapper').length;
            var totalCycles = $('.phase-cycles-container > .cycle-wrapper').length;
            var newCycle = {id: totalCycles+1, index: nextCycleIndex, number: nextCycleIndex+1};

            templates.render('mod_roadmap/configuration_cycle', newCycle)
                .then(function(html, js) {
                    templates.appendNodeContents(cycleContainer, html, js);

                    $('.cycle-collapse-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.collapseCycle(e); });

                    $('.cycle-delete-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.deleteCycle(e); });

                    $('.add-cycle-step').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.addStep(e); });

                    learningobjectives.refresh_checklists();
                    cyclesave.rebind_inputs();
                    phasesave.rebind_inputs();
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addStep = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var cycleContainer = thisnode.closest('.cycle-container');
            var stepsContainer = cycleContainer.children('.cycle-steps-container');

            var nextStepIndex = stepsContainer.children('.step-wrapper').length;
            var totalSteps = $('.step-wrapper').length;
            var dtpdata = JSON.parse($('input[name="datetimepickerdata"]').val());
            var newStep = {
                id: totalSteps+1,
                index: nextStepIndex,
                number: nextStepIndex+1,
                days: dtpdata.days,
                months: dtpdata.months,
                years: dtpdata.years,
                hours: dtpdata.hours,
                minutes: dtpdata.minutes,
                stepicon: 'icon-10',
            };

            templates.render('mod_roadmap/configuration_step', newStep)
                .then(function(html, js) {
                    templates.appendNodeContents(stepsContainer, html, js);

                    $('.step-collapse-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.collapseStep(e); });

                    $('.step-delete-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.deleteStep(e); });

                    stepiconselect.rebind_buttons();
                    stepactivityselect.rebind_buttons();
                    stepsave.rebind_inputs();
                    cyclesave.rebind_inputs();

                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.collapsePhase = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var phaseWrapper = thisnode.closest('.phase-wrapper');
            var metadata = phaseWrapper.children('.phase-container');
            ec.expandCollapse(metadata, thisnode);
        };

        RoadmapConfig.prototype.collapseCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var cycleWrapper = thisnode.closest('.cycle-wrapper');
            var metadata = cycleWrapper.children('.cycle-container');
            ec.expandCollapse(metadata, thisnode);
        };

        RoadmapConfig.prototype.collapseStep = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var cycleWrapper = thisnode.closest('.step-wrapper');
            var metadata = cycleWrapper.children('.step-container');
            ec.expandCollapse(metadata, thisnode);
        };

        RoadmapConfig.prototype.deletePhase = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (confirm("Are you sure you want to delete this Phase?")) {
                var thisnode = $(event.currentTarget);
                var phaseWrapper = thisnode.closest('.phase-wrapper');
                phaseWrapper.remove();
                RoadmapConfig.prototype.configSave();
            }
        };

        RoadmapConfig.prototype.deleteCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (confirm("Are you sure you want to delete this Cycle?")) {
                var thisnode = $(event.currentTarget);
                var cycleWrapper = thisnode.closest('.cycle-wrapper');
                var phaseContainer = thisnode.closest('.phase-container');
                cycleWrapper.remove();
                phaseContainer.find('.phase-title').triggerHandler("change");
            }
        };

        RoadmapConfig.prototype.deleteStep = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (confirm("Are you sure you want to delete this Step?")) {
                var thisnode = $(event.currentTarget);
                var stepWrapper = thisnode.closest('.step-wrapper');
                var cycleContainer = thisnode.closest('.cycle-container');
                stepWrapper.remove();
                cycleContainer.find('.cycle-title').triggerHandler("change");
            }
        };

        RoadmapConfig.prototype.upPhase = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var phaseWrapper = thisnode.closest('.phase-wrapper');
            var prevPhaseWrapper = phaseWrapper.prev();

            phaseWrapper.insertBefore(prevPhaseWrapper);
            RoadmapConfig.prototype.configSave();
        };

        RoadmapConfig.prototype.downPhase = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var phaseWrapper = thisnode.closest('.phase-wrapper');
            var nextPhaseWrapper = phaseWrapper.next();

            phaseWrapper.insertAfter(nextPhaseWrapper);
            RoadmapConfig.prototype.configSave();
        };

        RoadmapConfig.prototype.upCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var cycleWrapper = thisnode.closest('.cycle-wrapper');
            var phaseContainer = thisnode.closest('.phase-container');
            var prevCycleWrapper = cycleWrapper.prev();

            if (prevCycleWrapper) {
                cycleWrapper.insertBefore(prevCycleWrapper);
                phaseContainer.find('.phase-title').triggerHandler("change");
            }
        };

        RoadmapConfig.prototype.downCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var cycleWrapper = thisnode.closest('.cycle-wrapper');
            var phaseContainer = thisnode.closest('.phase-container');
            var nextCycleWrapper = cycleWrapper.next();

            if (nextCycleWrapper) {
                cycleWrapper.insertAfter(nextCycleWrapper);
                phaseContainer.find('.phase-title').triggerHandler("change");
            }
        };

        RoadmapConfig.prototype.upStep = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var stepWrapper = thisnode.closest('.step-wrapper');
            var cycleContainer = thisnode.closest('.cycle-container');
            var prevStepWrapper = stepWrapper.prev();

            if (prevStepWrapper) {
                stepWrapper.insertBefore(prevStepWrapper);
                cycleContainer.find('.cycle-title').triggerHandler("change");
            }
        };

        RoadmapConfig.prototype.downStep = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var stepWrapper = thisnode.closest('.step-wrapper');
            var cycleContainer = thisnode.closest('.cycle-container');
            var nextStepWrapper = stepWrapper.next();

            if (nextStepWrapper) {
                stepWrapper.insertAfter(nextStepWrapper);
                cycleContainer.find('.cycle-title').triggerHandler("change");
            }
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} inputSelector The hidden input field selector.
             * @return {LearningObjectivesConfig} A new instance of PhasesConfig.
             * @method init
             */
            init: function(inputSelector, inputConfig) {
                return new RoadmapConfig(inputSelector, inputConfig);
            }
        };
    });
