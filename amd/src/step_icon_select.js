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
define([
    'jquery',
    'core/notification',
    'core/templates',
    'core/ajax',
    'core/modal_factory',
    'core/modal_events'
], function(
    $,
    notification,
    templates,
    ajax,
    ModalFactory,
    ModalEvents
) {

        var SELECTORS = {
            SELECT_ICON_BUTTON: '.btn_icon_selector'
        };

        var StepIconSelector = function() {
            this.registerEventListeners();
        };

        StepIconSelector.prototype.registerEventListeners = function() {

            var trigger = $(SELECTORS.SELECT_ICON_BUTTON);

            trigger.off('click').on('click', function(e) {
                let stepId = $(e.target).data('stepid');

                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: 'Choose Step Icon',
                    body: '',
                }, trigger).done(function(modal) {
                    this.setupFormModal(modal, stepId, 'Save Selection');
                }.bind(this));
            }.bind(this));
        };

        /**
         * @method getBody
         * @private
         * @return {Promise}
         */
        StepIconSelector.prototype.getBody = function() {
            // TODO, would like to fetch icon list with a server call
            var iconsData = JSON.parse($('input[name="icon_data"]').val());

            // Let's find all of the currently selected icons and add them.
            $('.step-wrapper .step-container .step-icon-display img.step-icon').each(function() {
                let iconfilename = $(this).data('iconfilename');
                let iconsrc = $(this).attr('src');
                let usedicon = { file: iconfilename, iconurl: iconsrc };

                if (iconsData.categories[0].icons.some(icon => icon.file === iconfilename)) {
                    // Icon already exists in currently used category.
                } else {
                    iconsData.categories[0].icons.push(usedicon);
                }
            });

            // Get the content of the modal.
            return templates.render('mod_roadmap/configuration_iconselect', iconsData);
        };

        StepIconSelector.prototype.setupFormModal = function(modal, stepId, saveText) {
            modal.setLarge();

            modal.setSaveButtonText(saveText);

            // We want to reset the form every time it is opened.
            modal.getRoot().on(ModalEvents.hidden, this.destroy.bind(this));

            modal.setBody(this.getBody());

            modal.getRoot().on('click', 'img.icon', function(e) {
                let iconFileName = $(e.target).data('iconfilename');
                $('.modal-body .icon-container img.icon.selected').removeClass('selected');
                $(e.target).addClass('selected');

                // I have the icon name, I need to hold it temp in the modal until save.
                $('#current-selected-icon').val(iconFileName);

                // I need to update the example icons in the footer of the modal.
                this.updatePreviewIcons(iconFileName);

            }.bind(this));


            modal.getRoot().on(ModalEvents.bodyRendered, function() {
                var iconFileName = $("input[name=step-" + stepId + "-icon]").val();
                $(".modal-body .icon-container img.icon[data-iconfilename=" + iconFileName + "]").addClass('selected');

                // I have the icon name, I need to hold it temp in the modal until save.
                $('#current-selected-icon').val(iconFileName);
                $('#step-id').val(stepId);

                // I need to update the example icons in the footer of the modal.
                StepIconSelector.prototype.updatePreviewIcons(iconFileName);
            }).bind(this);

            // We catch the modal save event, and use it to submit the form inside the modal.
            // Triggering a form submission will give JS validation scripts a chance to check for errors.
            modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));

            modal.getRoot().on('submit', 'form', this.submitForm.bind(this));

            this.modal = modal;

            modal.show();
        };

        StepIconSelector.prototype.updatePreviewIcons = function(iconFileName) {
            let iconUrl = $('input[name="icon_url"]').val();

            $('div.selected-icon-container > span.img-incomplete > img.icon').attr('src',
                iconUrl + '?name=' + iconFileName + '&percent=0');
            $('div.selected-icon-container > span.img-partial > img.icon').attr('src',
                iconUrl + '?name=' + iconFileName + '&percent=66');
            $('div.selected-icon-container > span.img-alert > img.icon').attr('src',
                iconUrl + '?name=' + iconFileName + '&percent=66&flags=a');
            $('div.selected-icon-container > span.img-complete > img.icon').attr('src',
                iconUrl + '?name=' + iconFileName + '&percent=100');
            $('div.selected-icon-container > span.img-ontime > img.icon').attr('src',
                iconUrl + '?name=' + iconFileName + '&percent=100&flags=s');
        };

        StepIconSelector.prototype.submitForm = function(e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            let iconUrl = $('input[name="icon_url"]').val();
            let iconFileName = $('#current-selected-icon').val();
            let stepId = $('#step-id').val();

            let inputStep = $('input[name="step-' + stepId + '-icon"]');
            let imgStep = inputStep.parent('.step-icon-display').children('img').first();

            imgStep.attr('src', iconUrl + '?name=' + iconFileName + '&percent=100&flags=n');
            imgStep.removeAttr('data-iconfilename').removeData('iconfilename');
            imgStep.attr('data-iconfilename', iconFileName);

            inputStep.val(iconFileName);
            inputStep.trigger('change');
            this.destroy();
        };

        StepIconSelector.prototype.destroy = function() {
            this.modal.destroy();
        };

        StepIconSelector.prototype.initIconConfig = function(popup) {
            this.popup = popup;
            var body = $(popup.getContent());


            body.on('click', '[data-action="cancel"]', function() {
                popup.close();
            });
        };

        return {

            /**
             * Main initialisation.
             *
             * @return {ScaleConfig} A new instance of ScaleConfig.
             * @method init
             */
            init: function() {
                return new StepIconSelector();
            },

            rebindButtons: function() {
                StepIconSelector.prototype.registerEventListeners();
            }
        };
});