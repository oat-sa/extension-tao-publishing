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
    'ui/component',
    'tpl!taoPublishing/component/authSelector/tpl/authSelector',
    'tpl!taoPublishing/component/authSelector/tpl/basicAuthForm',
    'tpl!taoPublishing/component/authSelector/tpl/oAuthForm',
    'tpl!taoPublishing/component/authSelector/tpl/oauth2AuthForm'
], function ($, component, authSelectorTpl, basicAuthFormTpl, oauthFormTpl) {
    'use strict';

    /**
     * Default configuration for the authorization type
     * @type {{type: string}}
     * @private
     */
    var _defaults = {
        type: 'http://www.tao.lu/Ontologies/TAO.rdf#BasicAuthConsumer'
    };

    return function authSelectorFactory (config) {
        var initConfig = _.defaults(config || {}, _defaults);

        function changeAuthForm($container) {
            var $authForm, authFormTpl;
            switch (initConfig.type) {
                case 'http://www.tao.lu/Ontologies/TAO.rdf#BasicAuthConsumer':
                    authFormTpl = basicAuthFormTpl;
                    break;
                // oAuth 1.0
                case 'http://www.tao.lu/Ontologies/TAO.rdf#OauthConsumer':
                    authFormTpl = oauthFormTpl;
                    break;
                default: throw new Error('Undefined auth type ' + initConfig.type);
            }
            $authForm = $(authFormTpl(initConfig));
            $container.text('');
            $authForm.appendTo($container);
        }

        return component()
            .setTemplate(authSelectorTpl)
            .on('render', function ($container) {
                 var $authContainer = $('.authenticator-settings', $container);
                $('.auth-type-selector', $container).on('change', function () {
                    initConfig.type = this.value;
                    changeAuthForm($authContainer);
                });
                changeAuthForm($authContainer);
            }).init(initConfig);
    }
});
