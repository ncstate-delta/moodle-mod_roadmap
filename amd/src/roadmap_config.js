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
 * Handle the configuration of the roadmap. Modernized version.
 *
 * @module     mod_roadmap/roadmapconfig
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/notification',
    'core/templates',
    'mod_roadmap/expand_contract',
    'mod_roadmap/learningobjectivesconfig',
    'mod_roadmap/step_icon_select',
    'mod_roadmap/step_activity_select',
    'mod_roadmap/step_save',
    'mod_roadmap/cycle_save',
    'mod_roadmap/phase_save',
    'mod_roadmap/repository',
], (
    $,
    notification,
    templates,
    expandContract,
    learningObjectives,
    stepIconSelect,
    stepActivitySelect,
    stepSave,
    cycleSave,
    phaseSave,
    roadmapRepo
) => {

    // Helper: Confirm dialog wrapper
    const confirmDialog = (msg) => window.confirm(msg);

    // Helper: Find the max ID for a given type
    const getMaxValue = (dataType) => {
        let ids = [];
        $(`.${dataType}-wrapper`).each(function () {
            const id = parseInt($(this).data(`${dataType}id`));
            if (!isNaN(id)) {
                ids.push(id);
            }
        });
        return ids.length ? Math.max(...ids) : 0;
    };

    // Helper: Move wrapper up/down
    const moveWrapper = ($wrapper, direction) => {
        if (direction === 'up') {
            const $prev = $wrapper.prev();
            if ($prev.length) {
                $wrapper.insertBefore($prev);
            }
        } else if (direction === 'down') {
            const $next = $wrapper.next();
            if ($next.length) {
                $wrapper.insertAfter($next);
            }
        }
    };

    // Main class for roadmap config UI logic
    class RoadmapConfig {
        /**
         * @param {String} inputSelector - Selector where config UI is injected.
         * @param {String} inputConfig - Selector for hidden config input.
         */
        constructor(inputSelector, inputConfig) {
            this.inputSelector = inputSelector;
            this.configContainer = $(inputConfig);
            this.initForm();
        }

        /**
         * (Re)Initializes the roadmap configuration UI.
         */
        initForm() {
            let inputConfigVal = this.configContainer.val();
            if (!inputConfigVal) {
                inputConfigVal = JSON.stringify({ phases: [] });
                this.configContainer.val(inputConfigVal);
            }
            let config;
            try {
                config = JSON.parse(inputConfigVal);
            } catch (e) {
                notification.exception(e);
                config = { phases: [] };
            }

            // Attach serialized config for template rendering
            for (const phase of config.phases) {
                phase.configuration = JSON.stringify(phase);
                for (const cycle of (phase.cycles || [])) {
                    cycle.configuration = JSON.stringify(cycle);
                    for (const step of (cycle.steps || [])) {
                        step.configuration = JSON.stringify(step);
                    }
                }
            }

            // Render UI
            templates.render('mod_roadmap/configuration_phases', config)
                .then((html, js) => {
                    templates.prependNodeContents(this.inputSelector, html, js);

                    // Bind all event handlers
                    this.bindExpandCollapseControls();

                    // Delegated event binding for dynamic elements
                    $('#roadmapconfiguration')
                        .off('click', 'a[data-action]')
                        .on('click', 'a[data-action]', (e) => this.clickHandler(e));

                    this.phaseColorChange($('select[name="phasecolorpattern"]')); // Initial display

                    learningObjectives.refreshChecklists();
                    stepIconSelect.rebindButtons();
                    stepActivitySelect.rebindButtons();
                    stepSave.rebindInputs();
                    cycleSave.rebindInputs();
                    phaseSave.rebindInputs();
                    this.bindConfigSave();

                    require(['theme_boost/loader']);
                })
                .fail(notification.exception);

            // Color pattern changes (live preview)
            $('select[name="phasecolorpattern"]').off('change').on('change', (e) => {
                this.phaseColorChange($(e.target));
            });
        }

        /**
         * Handles all roadmap action links via event delegation.
         * @param {Event} event The event object.
         */
        clickHandler(event) {
            const $node = $(event.currentTarget);
            const action = $node.data('action');
            // All possible actions:
            switch (action) {
                case 'collapse_all_phases': return this.collapsePhases();
                case 'expand_all_phases': return this.expandPhases();
                case 'collapse_all_cycles':
                    // FIX: robustly find the phase-cycles-container for this phase
                    return this.collapseCycles($node.closest('.phase-wrapper').find('.phase-cycles-container').first());
                case 'expand_all_cycles':
                    // FIX: robustly find the phase-cycles-container for this phase
                    return this.expandCycles($node.closest('.phase-wrapper')
                        .find('.phase-cycles-container').first());
                case 'collapse_all_steps': return this.collapseSteps($node.parent('.step-container-controls')
                    .next('.cycle-steps-container'));
                case 'expand_all_steps': return this.expandSteps($node.parent('.step-container-controls')
                    .next('.cycle-steps-container'));
                case 'phase_collapse_control': return this.collapsePhase($node);
                case 'cycle_collapse_control': return this.collapseCycle($node);
                case 'step_collapse_control': return this.collapseStep($node);
                case 'phase_delete_control': return this.deletePhase($node);
                case 'cycle_delete_control': return this.deleteCycle($node);
                case 'step_delete_control': return this.deleteStep($node);
                case 'add_phase': return this.addPhase();
                case 'phase_up_control': return this.upPhase($node);
                case 'phase_down_control': return this.downPhase($node);
                case 'add_phase_cycle': return this.addCycle($node);
                case 'cycle_up_control': return this.upCycle($node);
                case 'cycle_down_control': return this.downCycle($node);
                case 'add_cycle_step': return this.addStep($node);
                case 'step_up_control': return this.upStep($node);
                case 'step_down_control': return this.downStep($node);
            }
        }

        /**
         * Show phase color pattern preview and update UI.
         * @param {Object} $node The jQuery node for the color selector.
         */
        async phaseColorChange($node) {
            const colorId = $node.val();
            this.getColorSet(colorId);
        }

        /**
         * Fetch and display a color pattern for phase UI.
         * @param {string|number} colorId The color pattern identifier.
         */
        async getColorSet(colorId) {
            $('.phase-color-display').remove();
            let colors = [];
            try {
                colors = await roadmapRepo.fetchColorPattern(colorId);
            } catch (e) {
                notification.exception(e);
            }
            const $colorTable = $('<div>').addClass('phase-color-display');
            for (const color of colors) {
                $colorTable.append(`<div class="color" style="background-color:${color}"></div>`);
            }
            $('select[name="phasecolorpattern"]').parent().append($colorTable);

            // Color border for each phase
            $('#roadmapconfiguration #phase-container > .phase-wrapper > div.row').each(function (i) {
                const numColors = colors.length;
                const idxColor = i % numColors;
                $(this).css('border-bottom', `solid 1px ${colors[idxColor]}`);
            });
        }

        /**
         * Binds config save to all phase configuration changes.
         */
        bindConfigSave() {
            $('input.phase-configuration').off('change').on('change', () => this.configSave());
        }

        /**
         * Serializes the whole roadmap config and saves to hidden input.
         */
        configSave() {
            const $phaseContainer = $('#phase-container');
            const roadmapData = [];
            let index = 0;
            $phaseContainer.find('.phase-wrapper .phase-configuration').each(function () {
                let phaseData = $(this).val() || '{}';
                let phaseDataObj = JSON.parse(phaseData);
                phaseDataObj.index = index++;
                roadmapData.push(phaseDataObj);
            });
            $('input[name="roadmapconfiguration"]').val(JSON.stringify({
                phases: roadmapData,
                phaseDeletes: $('#phase-deletes').val(),
                cycleDeletes: $('#cycle-deletes').val(),
                stepDeletes: $('#step-deletes').val(),
            }));
        }

        /**
         * Add a new phase.
         */
        addPhase() {
            const config = JSON.parse($('input[name="roadmapconfiguration"]').val());
            const nextIndex = config.phases.length;
            const maxPhaseId = getMaxValue('phase');
            const newPhase = { id: maxPhaseId + 1, index: nextIndex, number: nextIndex + 1 };
            config.phases.push(newPhase);
            $('input[name="roadmapconfiguration"]').val(JSON.stringify(config));

            templates.render('mod_roadmap/configuration_phase', newPhase)
                .then((html, js) => {
                    templates.appendNodeContents('#phase-container', html, js);
                    phaseSave.rebindInputs();
                    this.bindConfigSave();
                }).fail(notification.exception);
        }

        /**
         * Add a new cycle to a phase.
         * @param {Object} $node The jQuery node that triggered the add cycle action.
         */
        addCycle($node) {
            const $phaseContainer = $node.closest('.phase-container');
            const $cycleContainer = $phaseContainer.children('.phase-cycles-container');
            const nextCycleIndex = $cycleContainer.children('.cycle-wrapper').length;
            const maxCycleId = getMaxValue('cycle');
            const newCycle = { id: maxCycleId + 1, index: nextCycleIndex, number: nextCycleIndex + 1 };

            templates.render('mod_roadmap/configuration_cycle', newCycle)
                .then((html, js) => {
                    templates.appendNodeContents($cycleContainer, html, js);
                    learningObjectives.refreshChecklists();
                    cycleSave.rebindInputs();
                    phaseSave.rebindInputs();
                }).fail(notification.exception);
        }

        /**
         * Add a new step to a cycle.
         * @param {Object} $node The jQuery node that triggered the add step action.
         */
        addStep($node) {
            const $cycleContainer = $node.closest('.cycle-container');
            const $stepsContainer = $cycleContainer.children('.cycle-steps-container');
            const nextStepIndex = $stepsContainer.children('.step-wrapper').length;
            const maxStepId = getMaxValue('step');
            const iconUrl = $('input[name="icon_url"]').val();

            const newStep = {
                id: maxStepId + 1,
                index: nextStepIndex,
                number: nextStepIndex + 1,
                stepicon: 'icon-10',
                iconurl: `${iconUrl}?name=icon-10&percent=100&flags=n`,
            };

            templates.render('mod_roadmap/configuration_step', newStep)
                .then((html, js) => {
                    templates.appendNodeContents($stepsContainer, html, js);
                    stepIconSelect.rebindButtons();
                    stepActivitySelect.rebindButtons();
                    stepSave.rebindInputs();
                    cycleSave.rebindInputs();
                }).fail(notification.exception);
        }

        // ---- Expand/Collapse controls ----

        bindExpandCollapseControls() {
            $('.expand-collapse-controls .collapse_everything').off('click').on('click', (e) => {
                e.preventDefault();
                this.collapseEverything();
            });
            $('.expand-collapse-controls .expand_everything').off('click').on('click', (e) => {
                e.preventDefault();
                this.expandEverything();
            });
        }

        expandPhases() {
            this.expandChildrenClass($('#roadmapconfiguration'), 'phase');
        }
        expandCycles(node) {
            this.expandChildrenClass(node, 'cycle');
        }
        expandSteps(node) {
            this.expandChildrenClass(node, 'step');
        }
        collapsePhases() {
            const node = $('#roadmapconfiguration');
            this.collapseCycles(node);
            this.collapseChildrenClass(node, 'phase');
        }
        collapseCycles(node) {
            this.collapseSteps(node);
            this.collapseChildrenClass(node, 'cycle');
        }
        collapseSteps(node) {
            this.collapseChildrenClass(node, 'step');
        }
        expandChildrenClass($parent, className) {
            $parent.find(`a[data-action="${className}_collapse_control"]`).each(function () {
                const $wrapper = $(this).closest(`.${className}-wrapper`);
                const $container = $wrapper.children(`.${className}-container`);
                if ($container.hasClass('hide')) {
                    $(this).click();
                }
            });
        }
        collapseChildrenClass($parent, className) {
            $parent.find(`a[data-action="${className}_collapse_control"]`).each(function () {
                const $wrapper = $(this).closest(`.${className}-wrapper`);
                const $container = $wrapper.children(`.${className}-container`);
                if ($container.hasClass('visible')) {
                    $(this).click();
                }
            });
        }

        /**
         * Collapse a single phase.
         * @param {Object} $node The jQuery node for the control.
         */
        collapsePhase($node) {
            const $wrapper = $node.closest('.phase-wrapper');
            const $container = $wrapper.children('.phase-container');
            expandContract.expandCollapse($container, $node);
        }

        /**
         * Collapse a single cycle.
         * @param {Object} $node The jQuery node for the control.
         */
        collapseCycle($node) {
            const $wrapper = $node.closest('.cycle-wrapper');
            const $container = $wrapper.children('.cycle-container');
            expandContract.expandCollapse($container, $node);
        }

        /**
         * Collapse a single step.
         * @param {Object} $node The jQuery node for the control.
         */
        collapseStep($node) {
            const $wrapper = $node.closest('.step-wrapper');
            const $container = $wrapper.children('.step-container');
            expandContract.expandCollapse($container, $node);
        }

        // ---- Deletion ----

        /**
         * Delete a phase.
         * @param {Object} $node The jQuery node for the delete control.
         */
        deletePhase($node) {
            if (confirmDialog('Are you sure you want to delete this Phase?')) {
                const $wrapper = $node.closest('.phase-wrapper');
                const phaseId = $wrapper.data('phaseid');
                $('#phase-deletes').val($('#phase-deletes').val() + phaseId + ',');
                $wrapper.remove();
                this.configSave();
            }
        }

        /**
         * Delete a cycle.
         * @param {Object} $node The jQuery node for the delete control.
         */
        deleteCycle($node) {
            if (confirmDialog('Are you sure you want to delete this Cycle?')) {
                const $wrapper = $node.closest('.cycle-wrapper');
                const $phaseContainer = $node.closest('.phase-container');
                const cycleId = $wrapper.data('cycleid');
                $('#cycle-deletes').val($('#cycle-deletes').val() + cycleId + ',');
                $wrapper.remove();
                $phaseContainer.find('.phase-title').triggerHandler('change');
            }
        }

        /**
         * Delete a step.
         * @param {Object} $node The jQuery node for the delete control.
         */
        deleteStep($node) {
            if (confirmDialog('Are you sure you want to delete this Step?')) {
                const $wrapper = $node.closest('.step-wrapper');
                const $cycleContainer = $node.closest('.cycle-container');
                const stepId = $wrapper.data('stepid');
                $('#step-deletes').val($('#step-deletes').val() + stepId + ',');
                $wrapper.remove();
                $cycleContainer.find('.cycle-title').triggerHandler('change');
            }
        }

        // ---- Move up/down ----

        upPhase($node) {
            const $wrapper = $node.closest('.phase-wrapper');
            moveWrapper($wrapper, 'up');
            this.configSave();
        }
        downPhase($node) {
            const $wrapper = $node.closest('.phase-wrapper');
            moveWrapper($wrapper, 'down');
            this.configSave();
        }
        upCycle($node) {
            const $wrapper = $node.closest('.cycle-wrapper');
            const $phaseContainer = $node.closest('.phase-container');
            moveWrapper($wrapper, 'up');
            $phaseContainer.find('.phase-title').triggerHandler('change');
        }
        downCycle($node) {
            const $wrapper = $node.closest('.cycle-wrapper');
            const $phaseContainer = $node.closest('.phase-container');
            moveWrapper($wrapper, 'down');
            $phaseContainer.find('.phase-title').triggerHandler('change');
        }
        upStep($node) {
            const $wrapper = $node.closest('.step-wrapper');
            const $cycleContainer = $node.closest('.cycle-container');
            moveWrapper($wrapper, 'up');
            $cycleContainer.find('.cycle-title').triggerHandler('change');
        }
        downStep($node) {
            const $wrapper = $node.closest('.step-wrapper');
            const $cycleContainer = $node.closest('.cycle-container');
            moveWrapper($wrapper, 'down');
            $cycleContainer.find('.cycle-title').triggerHandler('change');
        }

        // ---- Expand/collapse everything ----
        expandEverything() {
            this.expandPhases();
        }
        collapseEverything() {
            this.collapsePhases();
        }
    }

    return {
        /**
         * Main initialisation.
         * @param {String} inputSelector The hidden input field selector.
         * @param {String} inputConfig The hidden input configuration.
         * @return {Object} RoadmapConfig instance
         */
        init: function (inputSelector, inputConfig) {
            return new RoadmapConfig(inputSelector, inputConfig);
        }
    };
});