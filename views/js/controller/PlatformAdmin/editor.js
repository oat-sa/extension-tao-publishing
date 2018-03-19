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
 * Copyright (c) 2018  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <olexander.zagovorychev@1pt.com>
 */

define([
    'jquery',
    'i18n',
    'layout/loading-bar',
    'ui/hider',
    'taoPublishing/provider/authSelector',
    'tpl!taoPublishing/controller/PlatformAdmin/tpl/authContainer'
], function ($, __, loadingBar, hider, authSelectorProvider, authContainerTpl) {
    'use strict';

    /**
     * Get or create element for authorization form
     * @returns {*|jQuery|HTMLElement}
     */
    function getAuthContainer() {
        var $propertyContainer  = $('.content-block .auth-container');
        if($propertyContainer.length) {
            return $propertyContainer;
        }
        $propertyContainer  = $(authContainerTpl());
        $('.content-block .form-toolbar').before($propertyContainer);
        return $propertyContainer;
    }

    return {
        start: function start() {
            var $container = getAuthContainer();
            var $elId = $('#id');
            var params = {};

            if($elId.length) {
                params = {
                    uri: $elId.val()
                };
            }

            /**
             * Display the auth form part that complies to the selected auth method.
             * Will be applied on the auth method selection combo box.
             */
            function showAuthFormPart() {
                hider.hide($container.find('.auth-form-part'));
                hider.show($container.find('[data-auth-method="' + this.value + '"]'));
            }

            loadingBar.start();
            authSelectorProvider.getHtml(params)
                .then(function (html) {
                    // show the form, will all auth methods
                    $container.html(html);

                    // display the form parts according to the selected auth method
                    $container.find('.auth-type-selector')
                        .each(showAuthFormPart)
                        .on('change', showAuthFormPart);
                }).catch(function() {
                    throw new Error( __('Publishing auth configuration can not be loaded'));
                }).then(function () {
                    loadingBar.stop();
                });
        }
    };
});
