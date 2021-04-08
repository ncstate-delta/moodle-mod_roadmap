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
        'mod_roadmap/learningobjectivesconfig', 'mod_roadmap/step_icon_select', 'mod_roadmap/step_activity_select'],
    function($, notification, templates, ec, learningobjectives, stepiconselect, stepactivityselect) {

        /**
         * Learning objectives config object.
         * @param {String} inputSelector The hidden input field selector.
         */
        var RoadmapConfig = function(inputSelector, inputConfig) {
            this.inputSelector = inputSelector;
            this.configContainer = $(inputConfig);

            this.showConfig(this);
        };

        /**
         * Displays the scale configuration dialogue.
         *
         * @method showConfig
         */
        RoadmapConfig.prototype.showConfig = function() {
            var self = this;
            var config = JSON.parse(this.configContainer.val());

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

                    learningobjectives.refresh_checklists();
                    stepiconselect.rebind_buttons();
                    stepactivityselect.rebind_buttons();
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addPhase = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var config = JSON.parse($('input[name="roadmapconfiguration"]').val());
            var nextIndex = config.phases.length;
            var newPhase = {id: -1, index: nextIndex, number: nextIndex+1};
            config.phases.push(newPhase);
            $('input[name="roadmapconfiguration"]').val(JSON.stringify(config));

            templates.render('mod_roadmap/configuration_phase', newPhase)
                .then(function(html, js) {
                    templates.appendNodeContents('#phase-container', html, js);

                    $('.phase-collapse-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.collapsePhase(e); });

                    $('#add-phase').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.addPhase(e); });

                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var phaseContainer = thisnode.closest('.phase-container');
            var cycleContainer = phaseContainer.children('.phase-cycles-container');

            var nextCycleIndex = cycleContainer.children('.cycle-wrapper').length;
            var newCycle = {id: -1, index: nextCycleIndex, number: nextCycleIndex+1};

            templates.render('mod_roadmap/configuration_cycle', newCycle)
                .then(function(html, js) {
                    templates.appendNodeContents(cycleContainer, html, js);

                    $('.cycle-collapse-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.collapseCycle(e); });

                    $('.add-cycle-step').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.addStep(e); });

                    learningobjectives.refresh_checklists();
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addStep = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var cycleContainer = thisnode.closest('.cycle-container');
            var stepsContainer = cycleContainer.children('.cycle-steps-container');

            var nextStepIndex = stepsContainer.children('.step-wrapper').length;
            var newStep = {id: -1, index: nextStepIndex, number: nextStepIndex+1};

            templates.render('mod_roadmap/configuration_step', newStep)
                .then(function(html, js) {
                    templates.appendNodeContents(stepsContainer, html, js);

                    $('.step-collapse-control').unbind('click')
                        .click(function(e) { RoadmapConfig.prototype.collapseStep(e); });

                    stepiconselect.rebind_buttons();
                    stepactivityselect.rebind_buttons();
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
