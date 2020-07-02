<?php
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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

return array(
    'name' => 'taoPublishing',
	'label' => 'Test Publishing',
	'description' => 'An extension to publish tests to a delivery environment',
    'license' => 'GPL-2.0',
    'version' => '2.4.1',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'taoDeliveryRdf' => '>=12.0.0',
        'tao' => '>=31.6.0',
        'taoQtiTest' => '>=38.13.0',
    ),
	'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoPublishingManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoPublishingManager', array('ext'=>'taoPublishing')),
    ),
    'install' => array(
        'rdf' => array(
            __DIR__. '/model/ontology/platform.rdf',
            __DIR__. '/model/ontology/taodelivery.rdf',
            __DIR__. '/model/ontology/taotest.rdf',
            __DIR__. '/model/ontology/indexation.rdf'
        ),
        'php' => array(
            \oat\taoPublishing\scripts\install\RegisterListeners::class,
            \oat\taoPublishing\scripts\install\UpdateConfigDeliveryFactoryService::class,
            \oat\taoPublishing\scripts\install\RegisterGenerisSearch::class,
            \oat\taoPublishing\scripts\install\RegisterDeliveryEventsListener::class,
            \oat\taoPublishing\scripts\install\RegisterPublishingFileSystem::class,
        )
    ),
    'uninstall' => array(
    ),
    'routes' => array(
        '/taoPublishing/api' => ['class' => \oat\taoPublishing\model\routing\ApiRoute::class],
        '/taoPublishing' => 'oat\\taoPublishing\\controller'
    ),
    'update' => 'oat\\taoPublishing\\scripts\\update\\Updater',
	'constants' => array(
	    # views directory
	    "DIR_VIEWS" => dirname(__FILE__).DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,

		#BASE URL (usually the domain root)
		'BASE_URL' => ROOT_URL.'taoPublishing/',

	    #BASE WWW required by JS
	    'BASE_WWW' => ROOT_URL.'taoPublishing/views/'
	),
    'extra' => array(
        'structures' => dirname(__FILE__).DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    )
);
