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
 * @module     mod_roadmap/stepactivityselect
 * @package    mod_roadmap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/templates', 'core/ajax', 'mod_roadmap/dialogue'],
    function($, notification, templates, ajax, Dialogue) {

        var StepActivitySelector = function() {

        };

        StepActivitySelector.prototype.rebind_checkbox = function() {
            $('.chk-single-activity-link').unbind('click').each(function(index, chk) {
                let stepid = $(chk).data('stepid');
                $('#step-' + stepid + '-single-activity-link').prop("disabled", $(chk).prop('checked'));

                $(chk).click(function() {
                    let stepid = $(this).data('stepid');
                    $('#step-' + stepid + '-single-activity-link').prop("disabled", $(this).prop('checked'));
                });
            });

            $('.completion-modules').unbind('change').each(function(index, input) {
                StepActivitySelector.prototype.configure_checkbox(input);
            });
        };

        StepActivitySelector.prototype.configure_checkbox = function(input) {
            let stepid = $(input).data('stepid');
            let multipleActivities = ($('#step-' + stepid + '-completion-modules').val().split(',').length > 1);

            $('#step-' + stepid + '-chk-single-activity-link').prop("disabled", multipleActivities);
            if (multipleActivities) {
                $('#step-' + stepid + '-chk-single-activity-link').prop("checked", !multipleActivities);
                $('#step-' + stepid + '-single-activity-link').prop("disabled", !multipleActivities);
            }
        };

        StepActivitySelector.prototype.rebind_buttons = function() {
            $('.btn_completion_selector').unbind('click').click(this.showConfig.bind(this));
        };

        StepActivitySelector.prototype.showConfig = function(event) {
            var self = this;
            var activityData = JSON.parse($('input[name="activity_data"]').val());
            self.clickedButton = event.target;

            // Dish up the form.
            templates.render('mod_roadmap/configuration_activityselect', activityData)
                .done(function(html) {
                    new Dialogue(
                        'Select Activities for Step Completion',
                        html,
                        self.initActivityConfig.bind(self)
                    );
                }).fail(notification.exception);
        };

        StepActivitySelector.prototype.initActivityConfig = function(popup) {
            this.popup = popup;
            var body = $(popup.getContent());
            //if (this.originalscaleid === this.scaleid) {

            //}
            body.on('click', '[data-action="save"]', function() {
                let values = $('#step-activity-selector option:selected').toArray()
                        .map(item => item.value).join(',');
                let text = $('#step-activity-selector option:selected').toArray()
                        .map(item => '<li>' + item.text + '</li>').join('');

                let stepId = $(this.clickedButton).data('stepid');
                $('input[name="step-' + stepId + '-completion-modules"]').val(values);
                StepActivitySelector.prototype.configure_checkbox($('input[name="step-' + stepId + '-completion-modules"]'));

                $('#step-' + stepId + '-completion-list').children('li').remove();
                $('#step-' + stepId + '-completion-list').append(text).trigger('change');
                popup.close();
            }.bind(this));
            body.on('click', '[data-action="cancel"]', function() {
                popup.close();
            });
        };

        return {

            /**
             * Main initialisation.
             *
             * @param {String} selectSelector The select box selector.
             * @param {String} inputSelector The hidden input field selector.
             * @param {String} triggerSelector The trigger selector.
             * @return {ScaleConfig} A new instance of ScaleConfig.
             * @method init
             */
            init: function() {
                return new StepActivitySelector();
            },

            rebind_buttons: function() {
                StepActivitySelector.prototype.rebind_buttons();
                StepActivitySelector.prototype.rebind_checkbox();
            }
        };
    });