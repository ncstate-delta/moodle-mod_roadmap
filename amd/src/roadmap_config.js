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
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
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
    ],
    function(
        $,
        notification,
        templates,
        ec,
        learningobjectives,
        stepiconselect,
        stepactivityselect,
        stepsave,
        cyclesave,
        phasesave,
        roadmaprepository,
    ) {

        /**
         * Learning objectives config object.
         * @param {String} inputSelector The hidden input field selector.
         * @param {String} inputConfig The hidden input configuration.
         */
        var RoadmapConfig = function(inputSelector, inputConfig) {
            this.inputSelector = inputSelector;
            this.configContainer = $(inputConfig);

            this.initForm();
        };

        /**
         * Use configuration data to fill phases, cycle, and step containers.
         *
         * @method initForm
         */
        RoadmapConfig.prototype.initForm = function() {
            var self = this;
            var inputConfigVal = this.configContainer.val();
            if (inputConfigVal == '') {
                inputConfigVal = JSON.stringify({'phases': []});
                this.configContainer.val(inputConfigVal);
            }
            var config = JSON.parse(inputConfigVal);

            $.each(config.phases, function(p) {
                let phase = config.phases[p];
                phase.configuration = JSON.stringify(phase);

                $.each(phase.cycles, function(c) {
                    let cycle = phase.cycles[c];
                    cycle.configuration = JSON.stringify(cycle);

                    $.each(cycle.steps, function(s) {
                        let step = cycle.steps[s];
                        step.configuration = JSON.stringify(step);
                    });
                });
            });

            // Dish up the form.
            templates.render('mod_roadmap/configuration_phases', config)
                .then(function(html, js) {
                    templates.prependNodeContents(self.inputSelector, html, js);

                    $('#add-phase').click(function(e) {
                        RoadmapConfig.prototype.addPhase(e);
                    });
                    $('.add-phase-cycle').click(function(e) {
                        RoadmapConfig.prototype.addCycle(e);
                    });
                    $('.add-cycle-step').click(function(e) {
                        RoadmapConfig.prototype.addStep(e);
                    });

                    $('.phase-collapse-control').click(function(e) {
                        RoadmapConfig.prototype.collapsePhase(e);
                    });
                    $('.cycle-collapse-control').click(function(e) {
                        RoadmapConfig.prototype.collapseCycle(e);
                    });
                    $('.step-collapse-control').click(function(e) {
                        RoadmapConfig.prototype.collapseStep(e);
                    });

                    $('.phase-delete-control').click(function(e) {
                        RoadmapConfig.prototype.deletePhase(e);
                    });
                    $('.cycle-delete-control').click(function(e) {
                        RoadmapConfig.prototype.deleteCycle(e);
                    });
                    $('.step-delete-control').click(function(e) {
                        RoadmapConfig.prototype.deleteStep(e);
                    });

                    $('.phase-up-control').click(function(e) {
                        RoadmapConfig.prototype.upPhase(e);
                    });
                    $('.phase-down-control').click(function(e) {
                        RoadmapConfig.prototype.downPhase(e);
                    });
                    $('.cycle-up-control').click(function(e) {
                        RoadmapConfig.prototype.upCycle(e);
                    });
                    $('.cycle-down-control').click(function(e) {
                        RoadmapConfig.prototype.downCycle(e);
                    });
                    $('.step-up-control').click(function(e) {
                        RoadmapConfig.prototype.upStep(e);
                    });
                    $('.step-down-control').click(function(e) {
                        RoadmapConfig.prototype.downStep(e);
                    });

                    $('.expand-collapse-controls .collapse_everything').click(function(e) {
                        RoadmapConfig.prototype.collapseEverything(e);
                    });
                    $('.expand-collapse-controls .expand_everything').click(function(e) {
                        RoadmapConfig.prototype.expandEverything(e);
                    });

                    // Instead of binding and unbinding specific elements over and over as they are created,
                    // This event will capture all clicks, always and based on data we can determine the action.
                    $('#roadmapconfiguration').click(RoadmapConfig.prototype.clickHandler);

                    RoadmapConfig.prototype.phaseColorChange($('select[name="phasecolorpattern"]'));

                    learningobjectives.refreshChecklists();

                    stepiconselect.rebindButtons();
                    stepactivityselect.rebindButtons();

                    stepsave.rebindInputs();
                    cyclesave.rebindInputs();
                    phasesave.rebindInputs();

                    RoadmapConfig.prototype.bindConfigSave();

                    require(['theme_boost/loader']);
                    return null;
                }).fail(notification.exception);

            $('select[name="phasecolorpattern"]').change(function(e) {
                RoadmapConfig.prototype.phaseColorChange($(e.target));
            });
        };

        RoadmapConfig.prototype.clickHandler = function(event) {
            var thisnode = $(event.target);
            var action = thisnode.data('action');

            if (action == 'collapse_all_phases') {
                RoadmapConfig.prototype.collapsePhases();
            } else if (action == 'expand_all_phases') {
                RoadmapConfig.prototype.expandPhases();
            } else if (action == 'collapse_all_cycles') {
                RoadmapConfig.prototype.collapseCycles(thisnode.parent('.cycle-container-controls')
                    .next('.phase-cycles-container'));
            } else if (action == 'expand_all_cycles') {
                RoadmapConfig.prototype.expandCycles(thisnode.parent('.cycle-container-controls')
                    .next('.phase-cycles-container'));
            } else if (action == 'collapse_all_steps') {
                RoadmapConfig.prototype.collapseSteps(thisnode.parent('.step-container-controls')
                    .next('.cycle-steps-container'));
            } else if (action == 'expand_all_steps') {
                RoadmapConfig.prototype.expandSteps(thisnode.parent('.step-container-controls')
                    .next('.cycle-steps-container'));
            }
        };

        RoadmapConfig.prototype.phaseColorChange = function(node) {
            var colorId = node.val();
            RoadmapConfig.prototype.getColorSet(colorId);
        };

        RoadmapConfig.prototype.getColorSet = async(colorId) => {
            $('.phase-color-display').remove();

            const response = await roadmaprepository.fetchColorPattern(colorId);

            var colorTable = $("<div>").addClass('phase-color-display');
            response.forEach(function(color) {
                colorTable.append('<div class="color" style="background-color:' + color + '"/></div>');
            });

            $('select[name="phasecolorpattern"]').parent().append(colorTable);

            $('#roadmapconfiguration #phase-container > .phase-wrapper > div.row').each(function(index) {
                let numcolors = response.length;
                let idxcolor = index % numcolors;

                $(this).css('border-bottom', 'solid 1px ' + response[idxcolor] + '');
            });
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
                if (phaseData == '') {
                    phaseData = '{}';
                }
                let phaseDataObj = JSON.parse(phaseData);
                phaseDataObj.index = index;
                index = index + 1;
                roadmapData.push(phaseDataObj);
            });
            $('input[name="roadmapconfiguration"]').val(JSON.stringify({phases: roadmapData,
                phaseDeletes: $('#phase-deletes').val(),
                cycleDeletes: $('#cycle-deletes').val(),
                stepDeletes: $('#step-deletes').val()
            }));
        };

        RoadmapConfig.prototype.addPhase = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var config = JSON.parse($('input[name="roadmapconfiguration"]').val());
            var nextIndex = config.phases.length;
            var maxPhaseId = parseInt(RoadmapConfig.prototype.maxValue('phase'));
            var newPhase = {id: maxPhaseId + 1, index: nextIndex, number: nextIndex + 1};
            config.phases.push(newPhase);
            $('input[name="roadmapconfiguration"]').val(JSON.stringify(config));

            templates.render('mod_roadmap/configuration_phase', newPhase)
                .then(function(html, js) {
                    templates.appendNodeContents('#phase-container', html, js);

                    $('.phase-collapse-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.collapsePhase(e);
                        });

                    $('.phase-delete-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.deletePhase(e);
                        });

                    $('.phase-up-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.upPhase(e);
                        });

                    $('.phase-down-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.downPhase(e);
                        });

                    $('#add-phase').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.addPhase(e);
                        });

                    $('.add-phase-cycle').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.addCycle(e);
                        });

                    phasesave.rebindInputs();
                    RoadmapConfig.prototype.bindConfigSave();
                    return null;
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var phaseContainer = thisnode.closest('.phase-container');
            var cycleContainer = phaseContainer.children('.phase-cycles-container');

            var nextCycleIndex = cycleContainer.children('.cycle-wrapper').length;
            let maxCycleId = parseInt(RoadmapConfig.prototype.maxValue('cycle'));
            var newCycle = {id: maxCycleId + 1, index: nextCycleIndex, number: nextCycleIndex + 1};

            templates.render('mod_roadmap/configuration_cycle', newCycle)
                .then(function(html, js) {
                    templates.appendNodeContents(cycleContainer, html, js);

                    $('.cycle-collapse-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.collapseCycle(e);
                        });

                    $('.cycle-delete-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.deleteCycle(e);
                        });

                    $('.cycle-up-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.upCycle(e);
                        });

                    $('.cycle-down-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.downCycle(e);
                        });

                    $('.add-cycle-step').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.addStep(e);
                        });

                    learningobjectives.refreshChecklists();
                    cyclesave.rebindInputs();
                    phasesave.rebindInputs();
                    return null;
                }).fail(notification.exception);
        };

        RoadmapConfig.prototype.addStep = function(event) {
            event.preventDefault();
            event.stopPropagation();
            var thisnode = $(event.currentTarget);
            var cycleContainer = thisnode.closest('.cycle-container');
            var stepsContainer = cycleContainer.children('.cycle-steps-container');

            var nextStepIndex = stepsContainer.children('.step-wrapper').length;
            let maxStepId = parseInt(RoadmapConfig.prototype.maxValue('step'));
            let iconUrl = $('input[name="icon_url"]').val();

            var newStep = {
                id: maxStepId + 1,
                index: nextStepIndex,
                number: nextStepIndex + 1,
                stepicon: 'icon-10',
                iconurl: iconUrl + '?name=icon-10&percent=100&flags=n',
            };

            templates.render('mod_roadmap/configuration_step', newStep)
                .then(function(html, js) {
                    templates.appendNodeContents(stepsContainer, html, js);

                    $('.step-collapse-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.collapseStep(e);
                        });

                    $('.step-delete-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.deleteStep(e);
                        });

                    $('.step-up-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.upStep(e);
                        });

                    $('.step-down-control').unbind('click')
                        .click(function(e) {
                            RoadmapConfig.prototype.downStep(e);
                        });

                    stepiconselect.rebindButtons();
                    stepactivityselect.rebindButtons();
                    stepsave.rebindInputs();
                    cyclesave.rebindInputs();
                    return null;
                }).fail(notification.exception);
        };

        //* New expand all and contract all functionality *//
        RoadmapConfig.prototype.expandPhases = function() {
            RoadmapConfig.prototype.expandChildrenClass($('#roadmapconfiguration'), 'phase');
        };

        RoadmapConfig.prototype.expandCycles = function(node) {
            RoadmapConfig.prototype.expandChildrenClass(node, 'cycle');
        };

        RoadmapConfig.prototype.expandSteps = function(node) {
            RoadmapConfig.prototype.expandChildrenClass(node, 'step');
        };

        RoadmapConfig.prototype.collapsePhases = function() {
            var node = $('#roadmapconfiguration');
            RoadmapConfig.prototype.collapseCycles(node);
            RoadmapConfig.prototype.collapseChildrenClass(node, 'phase');
        };

        RoadmapConfig.prototype.collapseCycles = function(node) {
            RoadmapConfig.prototype.collapseSteps(node);
            RoadmapConfig.prototype.collapseChildrenClass(node, 'cycle');
        };

        RoadmapConfig.prototype.collapseSteps = function(node) {
            RoadmapConfig.prototype.collapseChildrenClass(node, 'step');
        };

        RoadmapConfig.prototype.expandChildrenClass = function(parentNode, groupingName) {

            parentNode.find('a.' + groupingName + '-collapse-control').each(function() {
                var wrapper = $(this).closest('.' + groupingName + '-wrapper');
                var container = wrapper.children('.' + groupingName + '-container');
                if (container.hasClass("hide")) {
                    $(this).click();
                }
            });
        };

        RoadmapConfig.prototype.collapseChildrenClass = function(parentNode, groupingName) {

            parentNode.find('a.' + groupingName + '-collapse-control').each(function() {
                var wrapper = $(this).closest('.' + groupingName + '-wrapper');
                var container = wrapper.children('.' + groupingName + '-container');
                if (container.hasClass("visible")) {
                    $(this).click();
                }
            });
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
            if (window.confirm("Are you sure you want to delete this Phase?")) {
                var thisnode = $(event.currentTarget);
                var phaseWrapper = thisnode.closest('.phase-wrapper');
                var phaseId = phaseWrapper.data('phaseid');
                $('#phase-deletes').val($('#phase-deletes').val() + phaseId + ',');
                phaseWrapper.remove();
                RoadmapConfig.prototype.configSave();
            }
        };

        RoadmapConfig.prototype.deleteCycle = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (window.confirm("Are you sure you want to delete this Cycle?")) {
                var thisnode = $(event.currentTarget);
                var cycleWrapper = thisnode.closest('.cycle-wrapper');
                var phaseContainer = thisnode.closest('.phase-container');
                var cycleId = cycleWrapper.data('cycleid');
                $('#cycle-deletes').val($('#cycle-deletes').val() + cycleId + ',');
                cycleWrapper.remove();
                phaseContainer.find('.phase-title').triggerHandler("change");
            }
        };

        RoadmapConfig.prototype.deleteStep = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (window.confirm("Are you sure you want to delete this Step?")) {
                var thisnode = $(event.currentTarget);
                var stepWrapper = thisnode.closest('.step-wrapper');
                var cycleContainer = thisnode.closest('.cycle-container');
                var stepId = stepWrapper.data('stepid');
                $('#step-deletes').val($('#step-deletes').val() + stepId + ',');
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

        RoadmapConfig.prototype.maxValue = function(dataType) {
            var arrID = new Array();
            $('.' + dataType + '-wrapper').each(function() {
                let stepId = parseInt($(this).data(dataType + 'id'));
                if (isNaN(stepId) === false) {
                    arrID.push(stepId);
                }
            });
            var newArrID = arrID.sort(function(a, b) {
                return a - b;
            });

            return newArrID[arrID.length - 1];
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} inputSelector The hidden input field selector.
             * @param {String} inputConfig The hidden input configuration.
             * @return {LearningObjectivesConfig} A new instance of PhasesConfig.
             * @method init
             */
            init: function(inputSelector, inputConfig) {
                return new RoadmapConfig(inputSelector, inputConfig);
            }
        };
    });
