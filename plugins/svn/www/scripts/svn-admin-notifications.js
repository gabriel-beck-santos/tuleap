/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

document.addEventListener('DOMContentLoaded', function () {
    var show_edit_mode_buttons = document.querySelectorAll('.svn-notification-edit-show');
    [].forEach.call(show_edit_mode_buttons, function (button) {
        button.addEventListener('click', showEditMode);
    });

    var hide_edit_mode_buttons = document.querySelectorAll('.svn-notification-edit-hide');
    [].forEach.call(hide_edit_mode_buttons, function (button) {
        button.addEventListener('click', hideEditMode);
    });

    function hideEditMode() {
        var form       = document.getElementById('svn-admin-notifications-form'),
            read_cells = document.querySelectorAll('.svn-notifications-checkbox-cell-read'),
            edit_cells = document.querySelectorAll('.svn-notifications-checkbox-cell-write');

        form.reset();

        [].forEach.call(read_cells, function (cell) {
            cell.classList.remove('svn-notifications-checkbox-cell-hidden');
        });
        [].forEach.call(edit_cells, function (cell) {
            cell.classList.add('svn-notifications-checkbox-cell-hidden');
        });
    }

    function showEditMode() {
        hideEditMode();

        var tr         = this.parentNode.parentNode.parentNode,
            read_cells = tr.querySelectorAll('.svn-notifications-checkbox-cell-read'),
            edit_cells = tr.querySelectorAll('.svn-notifications-checkbox-cell-write');

        [].forEach.call(read_cells, function (cell) {
            cell.classList.add('svn-notifications-checkbox-cell-hidden');
        });
        [].forEach.call(edit_cells, function (cell) {
            cell.classList.remove('svn-notifications-checkbox-cell-hidden');
            var inputs = cell.getElementsByTagName('input');
            [].forEach.call(inputs, function (input) {
                input.disabled = false;
            });
        });
    }
});