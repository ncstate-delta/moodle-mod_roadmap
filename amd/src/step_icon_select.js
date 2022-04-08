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
define(['jquery', 'core/notification', 'core/templates', 'core/ajax', 'mod_roadmap/dialogue'],
    function($, notification, templates, ajax, Dialogue) {

        var StepIconSelector = function() {

        };

        StepIconSelector.prototype.rebind_buttons = function() {
            $('.btn_icon_selector').unbind('click').click(this.showConfig.bind(this));

        };

        StepIconSelector.prototype.showConfig = function(event) {
            var self = this;
            var iconsData = JSON.parse($('input[name="icon_data"]').val());
            self.clickedButton = event.target;

            // Dish up the form.
            templates.render('mod_roadmap/configuration_iconselect', iconsData)
                .done(function(html) {
                    new Dialogue(
                        'Select Icon for Cycle',
                        html,
                        self.initIconConfig.bind(self)
                    );
                }).fail(notification.exception);
        };

        StepIconSelector.prototype.initIconConfig = function(popup) {
            this.popup = popup;
            var body = $(popup.getContent());
            //if (this.originalscaleid === this.scaleid) {

            //}
            body.on('click', 'img.icon', function(e) {
                let iconContainer = $(e.target).parent('.icon-container');
                let iconName = iconContainer.data('iconname');
                let stepId = $(this.clickedButton).data('stepid');
                $('input[name="step-'+stepId+'-icon"]').val(iconName).trigger('change');
                $('input[name="step-'+stepId+'-icon"]').parent('.step-icon-display').children('img').remove();
                $('input[name="step-'+stepId+'-icon"]').parent('.step-icon-display').append($(e.target));
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
             * @return {ScaleConfig} A new instance of ScaleConfig.
             * @method init
             */
            init: function() {
                return new StepIconSelector();
            },

            rebind_buttons: function() {
                StepIconSelector.prototype.rebind_buttons();
            }
        };
});