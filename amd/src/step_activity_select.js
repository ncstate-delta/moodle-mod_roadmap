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
 * Handle opening a modal for activity selection.
 *
 * @module     mod_roadmap/stepactivityselect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/str',
    'core/notification',
    'core/templates',
    'mod_roadmap/repository',
    'core/modal_save_cancel',
    'core/modal_events',
    'mod_roadmap/step_save'
], function(
    $,
    Str,
    Notification,
    Templates,
    RoadmapRepository,
    ModalSaveCancel,
    ModalEvents,
    StepSave
) {

    var SELECTORS = {
        SELECT_ACTIVITY_BUTTON: '.btn_completion_selector'
    };

    var StepActivitySelector = function() {
        this.registerEventListeners();
    };

    StepActivitySelector.prototype.registerEventListeners = function() {

        var trigger = $(SELECTORS.SELECT_ACTIVITY_BUTTON);
        var stringkeys = [
            {
                key: 'chooseactivities',
                component: 'mod_roadmap'
            },
            {
                key: 'saveselection',
                component: 'mod_roadmap'
            }
        ];

        trigger.off('click').on('click', function(e) {
            let stepId = $(e.target).data('stepid');

            Str.get_strings(stringkeys).then(function(strings) {
                return Promise.all([
                    ModalSaveCancel.create({
                        title: strings[0],
                        body: '',
                    }),
                    strings[1],
                ]).then(function([modal, string]) {
                    this.setupFormModal(modal, stepId, string);
                    return modal;
                }.bind(this));
            }.bind(this))
            .catch(Notification.exception);
        }.bind(this));
    };

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    StepActivitySelector.prototype.getBody = async() => {
        const response = await RoadmapRepository.fetchCourseModules();

        // Get the content of the modal.
        return Templates.render('mod_roadmap/configuration_activityselect', response);
    };


    StepActivitySelector.prototype.setupFormModal = function(modal, stepId, saveText) {
        modal.setLarge();

        modal.setSaveButtonText(saveText);

        // We want to reset the form every time it is opened.
        modal.getRoot().on(ModalEvents.hidden, this.destroy.bind(this));

        modal.setBody(this.getBody());

        modal.getRoot().on('click', '#activity-select-window .activity input[type="checkbox"]', function() {
            this.updateExpectedCompleteDropdown();
        }.bind(this));

        modal.getRoot().on(ModalEvents.bodyRendered, function() {
            $('#step-id').val(stepId);

            // Check off all course modules currently selected.
            let stepCompletionModules = $('#step-' + stepId + '-completion-modules').val().split(',');
            let stepExpectedCompletionCourseModule = $('#step-' + stepId + '-expectedcomplete-coursemoduleid').val();
            let stepExpectedCompleteDatetime = $('#step-' + stepId + '-expectedcomplete-datetime').val();

            $.each(stepCompletionModules, function(i , val) {
                $('#activity-select-window .activity input[type="checkbox"][data-coursemoduleid="' + val + '"]')
                    .prop('checked', true);
            });

            this.updateExpectedCompleteDropdown();

            // Select the existing option from dropdown
            // If expected complete is a course module id, attempt to locate it in the dropdown.
            if (stepExpectedCompletionCourseModule > 0 &&
                $('#select-expected-activity-completion option[value="' + stepExpectedCompletionCourseModule + '"]').length > 0) {
                $('#select-expected-activity-completion option[value="' + stepExpectedCompletionCourseModule + '"]')
                    .attr("selected","selected");

                // If the course module doesnt exist or isn't a cmid, use the datetime and select custom.
            } else if (stepExpectedCompleteDatetime > 0) {
                var date = new Date(stepExpectedCompleteDatetime * 1000);
                $('#select-expected-activity-completion option[value="-1"]').attr("selected", "selected");
                $('#customExpectedDateContainer select[name="today"] option[value="' + date.getDate() + '"]')
                    .attr("selected","selected");
                $('#customExpectedDateContainer select[name="tomonth"] option[value="' + (date.getMonth()+1) + '"]')
                    .attr("selected","selected");
                $('#customExpectedDateContainer select[name="toyear"] option[value="' + date.getFullYear() + '"]')
                    .attr("selected","selected");
                $('#customExpectedDateContainer select[name="tohour"] option[value="' + date.getHours() + '"]')
                    .attr("selected","selected");
                $('#customExpectedDateContainer select[name="tominute"] option[value="' + date.getMinutes() + '"]')
                    .attr("selected","selected");

                // If the datetime is not a unixtime, then no completion is expected.
            } else {
                $('#select-expected-activity-completion option[value="0"]').attr("selected", "selected");
            }

            this.expectedCompleteDropdownChanged();
        }.bind(this));

        // We catch the modal save event, and use it to submit the form inside the modal.
        // Triggering a form submission will give JS validation scripts a chance to check for errors.
        modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));

        modal.getRoot().on('submit', 'form', this.submitForm.bind(this));

        this.modal = modal;

        modal.show();
    };

    StepActivitySelector.prototype.expectedCompleteDropdownChanged = function () {
        let selectedOption = $('#select-expected-activity-completion').find(':selected');
        if (selectedOption.val() == '-1') {
            $('#customExpectedDateContainer').show();
        } else {
            $('#customExpectedDateContainer').hide();
        }
    };

    StepActivitySelector.prototype.updateExpectedCompleteDropdown = function () {
        let select = $('#select-expected-activity-completion');

        $('#activity-select-window .activity input[type="checkbox"]').each(function() {
            let coursemoduleid = $(this).data('coursemoduleid');
            let completionexpecteddatetime = $(this).data('completionexpecteddatetime');
            let completionexpectedreadable = $(this).data('completionexpectedreadable');

            if (completionexpecteddatetime != 0) {
                // Is the checkbox checked?
                if ($(this).is(':checked')) {
                    let found = false;
                    $('#select-expected-activity-completion option').each(function() {
                        if ($(this).val() == coursemoduleid) {
                            found = true;
                        }
                    });
                    if (found === false) {
                        select.append($('<option>', {
                            value: coursemoduleid,
                            text: completionexpectedreadable + ' ' + $(this).data('name'),
                            'data-completionexpecteddatetime': completionexpecteddatetime,
                        }));
                    }
                } else {
                    $('#select-expected-activity-completion option').each(function() {
                        if ($(this).val() == coursemoduleid) {
                            $(this).remove();
                        }
                    });
                }
            }
        });

        select.unbind('change').change(this.expectedCompleteDropdownChanged.bind(this));
    };

    StepActivitySelector.prototype.submitForm = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();
        let stepId = $('#step-id').val();

        $('#step-' + stepId + '-completion-list li').remove();
        $('#activity-select-window .activity input[type="checkbox"]:checked').each(function() {
            $('#step-' + stepId + '-completion-list').append('<li>' + $(this).data('name') + '</li>');
        });

        // Collect the expected completion cmid and datetime
        let selectedOption = $('#select-expected-activity-completion').find(':selected');
        if (selectedOption.val() == 0) {
            $('#step-' + stepId + '-expectedcomplete-coursemoduleid').val(0);
            $('#step-' + stepId + '-expectedcomplete-datetime').val(0);
            $('#step-' + stepId + '-expectedcomplete-readable').html(selectedOption.text());

        } else if (selectedOption.val() == -1) {
            $('#step-' + stepId + '-expectedcomplete-coursemoduleid').val(-1);
            let day = $('#customExpectedDateContainer select[name="today"]').find(':selected').val();
            let month = $('#customExpectedDateContainer select[name="tomonth"]').find(':selected').val();
            let year = $('#customExpectedDateContainer select[name="toyear"]').find(':selected').val();
            let hours = $('#customExpectedDateContainer select[name="tohour"]').find(':selected').val();
            let minutes = $('#customExpectedDateContainer select[name="tominute"]').find(':selected').val();

            let jsDate = new Date(year,(month-1),day,hours,minutes);
            let unixTimestamp = Math.floor(jsDate.getTime() / 1000);
            $('#step-' + stepId + '-expectedcomplete-datetime').val(unixTimestamp);

            $('#step-' + stepId + '-expectedcomplete-readable').html(month + '/' + day + '/' + year + ' ' + hours + ':' + minutes);

        } else {
            $('#step-' + stepId + '-expectedcomplete-datetime').val(selectedOption.data('completionexpecteddatetime'));
            $('#step-' + stepId + '-expectedcomplete-coursemoduleid').val(selectedOption.val());
            $('#step-' + stepId + '-expectedcomplete-readable').html(selectedOption.text());
        }

        // Collect all checked course module ids and prep them
        // to go back to the config form.
        let values = $('#activity-select-window .activity input[type="checkbox"]:checked')
            .map(function() { return $(this).data('coursemoduleid'); }).get().join(',');

        $('#step-' + stepId + '-completion-modules').val(values).trigger('change');

        StepSave.rebindInputs();
        this.destroy();
    };

    StepActivitySelector.prototype.destroy = function() {
        this.modal.destroy();
    };

    return {

        /**
         * Main initialisation.
         *
         * @return {StepActivitySelector} A new instance of StepActivitySelector.
         * @method init
         */
        init: function() {
            return new StepActivitySelector();
        },

        rebindButtons: function() {
            StepActivitySelector.prototype.registerEventListeners();
        }
    };
});