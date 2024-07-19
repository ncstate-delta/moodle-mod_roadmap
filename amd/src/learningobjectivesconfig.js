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
 * Handle the configuration of learning objectives.
 *
 * @module     mod_roadmap/learningobjectivesconfig
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates', 'mod_roadmap/cycle_save'],
    function($, notification, templates, cyclesave) {

        /**
         * Learning objectives config object.
         * @param {String} inputSelector The hidden input field selector.
         * @param {String} inputConfig The hidden input field configuration.
         */
        var LearningObjectivesConfig = function(inputSelector, inputConfig) {
            this.inputSelector = $(inputSelector);
            this.configContainer = $(inputConfig);

            this.showConfig(this);
        };


        /**
         * Displays the learning objectives in a list.
         *
         * @method showConfig
         */
        LearningObjectivesConfig.prototype.showConfig = function() {
            var self = this;
            var inputConfigVal = this.configContainer.val();
            if (inputConfigVal == '') {
                inputConfigVal = JSON.stringify({'learningobjectives': []});
                this.configContainer.val(inputConfigVal);
            }
            var config = JSON.parse(inputConfigVal);
            config.learningobjectives.forEach(function(learningobjective) {
                learningobjective.number = learningobjective.index + 1;
            });

            // Dish up the form.
            templates.render('mod_roadmap/configuration_learningobjectives', config)
                .then(function(html, js) {
                    templates.prependNodeContents(self.inputSelector, html, js);

                    $('#add-learning-objective').click(function(e) {
                        e.preventDefault();

                        var nextloIndex = $('#learningobjective-container').children('.learningobjective').length;
                        var newLo = {id: nextloIndex, number: nextloIndex + 1};

                        templates.render('mod_roadmap/configuration_learningobjective', newLo)
                            .then(function(html, js) {
                                templates.appendNodeContents('#learningobjective-container', html, js);
                                LearningObjectivesConfig.prototype.rebindInputs();
                                LearningObjectivesConfig.prototype.saveConfig();
                                return null;
                            }).fail(notification.exception);
                    });

                    LearningObjectivesConfig.prototype.rebindInputs();
                    return null;
                }).fail(notification.exception);
        };

        LearningObjectivesConfig.prototype.rebindInputs = function() {
            $('.learning-objective-name').unbind('change').change(function() {
                LearningObjectivesConfig.prototype.saveConfig();
            });
            $('.learningobjective-delete-control').unbind('click').click(function(e) {
                LearningObjectivesConfig.prototype.deleteLearningObjective(e);
            });
            $('.learningobjective-up-control').unbind('click').click(function(e) {
                LearningObjectivesConfig.prototype.upLearningObjective(e);
            });
            $('.learningobjective-down-control').unbind('click').click(function(e) {
                LearningObjectivesConfig.prototype.downLearningObjective(e);
            });
        };

        LearningObjectivesConfig.prototype.saveConfig = function() {

            var arrlo = [];
            $('#learningobjective-container').children('.learningobjective').each(function(index) {
                let lonode = $('#learningobjective-container').children('.learningobjective')[index];
                let id = $(lonode).data('id');
                let name = $(lonode).find('input.learning-objective-name').val();
                arrlo.push({index: index, id: id, name: name});
            });
            $('input[name="learningobjectivesconfiguration"]').val(JSON.stringify({learningobjectives: arrlo}));
            LearningObjectivesConfig.prototype.refreshChecklists();
            cyclesave.rebindInputs();

            // Save any changes to the checklists
            $('.chk-learning-objectives input[type="checkbox"]').trigger('change');
        };

        LearningObjectivesConfig.prototype.refreshChecklists = function() {
            var config = JSON.parse($('input[name="learningobjectivesconfiguration"]').val());
            var chkAreas = $('.chk-learning-objectives');

            $(chkAreas).each(function(i, e) {
                let selectedIds = [];
                let configVal = $(this).closest('.cycle-container').children('.cycle-configuration').val();
                if (configVal != '') {
                    // Get the configuration line from the local hidden field
                    let stepConfig = JSON.parse(configVal);
                    if (!stepConfig.learningobjectives) {
                        stepConfig.learningobjectives = '';
                    }
                    selectedIds = stepConfig.learningobjectives.split(',').map(x => parseInt(x));
                }

                // Remove all inputs
                $(e).empty();

                // Re-add inputs and remark any selected.
                config.learningobjectives.forEach(function(learningobjective) {
                    var li = $('<li/>').attr('data-id', learningobjective.id).appendTo($(e));
                    $('<input/>').attr('type', 'checkbox').attr('value', learningobjective.id)
                        .attr('class', 'form-control')
                        .attr('checked', ($.inArray(learningobjective.id, selectedIds) >= 0)).appendTo(li);
                    $('<span>').text(' ' + learningobjective.name).appendTo(li);
                });
            });
        };

        LearningObjectivesConfig.prototype.deleteLearningObjective = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (window.confirm("Are you sure you want to delete this Learning Objective?")) {
                var thisnode = $(event.currentTarget);
                var loItem = thisnode.closest('.learningobjective');
                loItem.remove();
                LearningObjectivesConfig.prototype.saveConfig();
            }
        };

        LearningObjectivesConfig.prototype.upLearningObjective = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var loItem = thisnode.closest('.learningobjective');
            var prevloItem = loItem.prev();

            loItem.insertBefore(prevloItem);
            LearningObjectivesConfig.prototype.saveConfig();
        };

        LearningObjectivesConfig.prototype.downLearningObjective = function(event) {
            event.preventDefault();
            event.stopPropagation();

            var thisnode = $(event.currentTarget);
            var loItem = thisnode.closest('.learningobjective');
            var nextloItem = loItem.next();

            loItem.insertAfter(nextloItem);
            LearningObjectivesConfig.prototype.saveConfig();
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} inputSelector The hidden input field selector.
             * @param {String} inputConfig The hidden input configuration.
             * @return {LearningObjectivesConfig} A new instance of LearningObjectivesConfig.
             * @method init
             */
            init: function(inputSelector, inputConfig) {
                return new LearningObjectivesConfig(inputSelector, inputConfig);
            },

            refreshChecklists: function() {
                LearningObjectivesConfig.prototype.refreshChecklists();
            },
        };
    });
