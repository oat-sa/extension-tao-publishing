/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */

define(['jquery', 'i18n', 'ui/dialog/alert', 'taoPublishing/controller/Publish/selectRemoteEnvironments'], function (
    $,
    __,
    alertDialog,
    selectRemoteEnvironments
) {
    'use strict';

    return {
        start: function start() {
            const uriResource = $('#selected-subject-uri').val();
            const exceedFlag = $('#class-content-exceeded').val() === '1';

            if (exceedFlag) {
                alertDialog(
                    __(
                        "The class can't be published.<br/><br/>The selected class contains too many deliveries allowed for a single remote publication (maximum %s).<br/>Please reorganize the deliveries or publish a class with fewer deliveries.",
                        $('#class-content-limit').val()
                    )
                );
            } else {
                selectRemoteEnvironments.start();
            }
        }
    };
});
