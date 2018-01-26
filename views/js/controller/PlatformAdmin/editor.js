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
    'taoPublishing/component/authSelector/authSelector',
    'taoPublishing/provider/authSelector'
], function ($, __, loadingBar, authSelectorFactory, authSelectorProvider) {
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
        $propertyContainer  = $('<div>', { 'class' : 'auth-container' });
        $('.content-block .form-toolbar').before($propertyContainer);
        return $propertyContainer;
    }

    return {
        start: function start() {
            var $container = getAuthContainer();
            var authSelectorComponent;
            loadingBar.start();
            authSelectorProvider.getConfig({uri: $('#id').val()})
                .then(function (config) {
                    loadingBar.stop();
                    authSelectorComponent = authSelectorFactory(config);
                    authSelectorComponent.render($container);
                }).catch(function() {
                    loadingBar.stop();
                    throw new Error( __('Publishing auth configuration can not be loaded'));
                });
        }
    }
});
