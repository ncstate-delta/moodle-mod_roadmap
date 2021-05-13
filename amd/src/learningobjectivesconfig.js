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
 * @package    mod_roadmap
 * @copyright  2021 Steve Bader <smbader@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates'],
    function($, notification, templates) {

        /**
         * Learning objectives config object.
         * @param {String} inputSelector The hidden input field selector.
         */
        var LearningObjectivesConfig = function(inputSelector, inputConfig) {
            this.inputSelector = $(inputSelector).parent();
            this.configContainer = $(inputConfig);

            this.showConfig(this);
        };


        /**
         * Displays the scale configuration dialogue.
         *
         * @method showConfig
         */
        LearningObjectivesConfig.prototype.showConfig = function() {
            var self = this;
            var inputConfigVal = this.configContainer.val();
            if (inputConfigVal == '') {
                inputConfigVal = JSON.stringify({ 'learningobjectives':[] });
                this.configContainer.val(inputConfigVal);
            }
            var config = JSON.parse(inputConfigVal);
            config.learningobjectives.forEach(function (learningobjective) {
                learningobjective.number = learningobjective.index + 1;
            });

            // Dish up the form.
            templates.render('mod_roadmap/configuration_learningobjectives', config)
                .then(function(html, js) {
                    templates.prependNodeContents(self.inputSelector, html, js);

                    $('.learning-objective-name').change(function() {
                        LearningObjectivesConfig.prototype.saveConfig();
                    });
                    $('#add-learning-objective').click(function(e) {
                        e.preventDefault();

                        var nextloIndex = $('#learningobjective-container').children('.learningobjective').length;
                        var newLo = {id: nextloIndex, number: nextloIndex+1};

                        templates.render('mod_roadmap/configuration_learningobjective', newLo)
                            .then(function(html, js) {
                                templates.appendNodeContents('#learningobjective-container', html, js);
                                LearningObjectivesConfig.prototype.saveConfig();

                                $('.learning-objective-name').unbind('change').change(function() {
                                    LearningObjectivesConfig.prototype.saveConfig();
                                });
                            }).fail(notification.exception);
                    });
                }).fail(notification.exception);
        };

        LearningObjectivesConfig.prototype.saveConfig = function() {

            var arrlo = [];
            $('#learningobjective-container').children('.learningobjective').each(function (index) {
                let lonode = $('#learningobjective-container').children('.learningobjective')[index];
                let id = $(lonode).data('id');
                let name = $(lonode).find('input.learning-objective-name').val();
                arrlo.push({index: index, id: id, name: name});
            });
            $('#id_learningobjectivesconfiguration').val(JSON.stringify({learningobjectives: arrlo}));
            LearningObjectivesConfig.prototype.refreshChecklists();
        };

        LearningObjectivesConfig.prototype.refreshChecklists = function() {
            var config = JSON.parse($('#id_learningobjectivesconfiguration').val());
            var chkAreas = $('.chk-learning-objectives');

            $(chkAreas).each(function(i, e) {
                let selectedIds = [];
                let configVal = $(this).closest('.cycle-container').children('.cycle-configuration').val();
                if (configVal != '') {
                    // get the configuration line from the local hidden field
                    let stepConfig = JSON.parse(configVal);
                    if (!stepConfig.learningobjectives) {
                        stepConfig.learningobjectives = '';
                    }
                    selectedIds = stepConfig.learningobjectives.split(',').map(x => parseInt(x));
                }

                // remove all inputs
                $(e).empty();

                // re-add inputs and remark any selected.
                config.learningobjectives.forEach(function (learningobjective) {
                    var li = $('<li/>').attr('data-id', learningobjective.id).appendTo($(e));
                    $('<input/>').attr('type', 'checkbox').attr('value', learningobjective.id)
                        .attr('class', 'form-control')
                        .attr('checked', ($.inArray(learningobjective.id, selectedIds)>=0)).appendTo(li);
                    $('<span>').text(learningobjective.name).appendTo(li);
                });

            });
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} inputSelector The hidden input field selector.
             * @return {LearningObjectivesConfig} A new instance of LearningObjectivesConfig.
             * @method init
             */
            init: function(inputSelector, inputConfig) {
                return new LearningObjectivesConfig(inputSelector, inputConfig);
            },

            refresh_checklists: function() {
                LearningObjectivesConfig.prototype.refreshChecklists();
            },
        };
    });
