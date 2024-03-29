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
 * Copyright (c) 2016-2021 (original work) Open Assessment Technologies SA;
 *
 */

use oat\taoPublishing\model\routing\ApiRoute;
use oat\taoPublishing\scripts\install\RegisterDataStoreServices;
use oat\taoPublishing\scripts\install\RegisterDeliveryEventsListener;
use oat\taoPublishing\scripts\install\RegisterGenerisSearch;
use oat\taoPublishing\scripts\install\RegisterPublishingFileSystem;
use oat\taoPublishing\scripts\install\UnRegisterGenerisSearch;
use oat\taoPublishing\scripts\install\UpdateConfigDeliveryFactoryService;
use oat\taoPublishing\scripts\install\GenerisSearchWhitelist;
use oat\taoPublishing\scripts\update\Updater;

return array(
    'name' => 'taoPublishing',
    'label' => 'Test Publishing',
    'description' => 'An extension to publish tests to a delivery environment',
    'license' => 'GPL-2.0',
    'author' => 'Open Assessment Technologies SA',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoPublishingManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoPublishingManager', array('ext' => 'taoPublishing')),
    ),
    'install' => array(
        'rdf' => array(
            __DIR__ . '/model/ontology/platform.rdf',
            __DIR__ . '/model/ontology/taodelivery.rdf',
            __DIR__ . '/model/ontology/taotest.rdf',
            __DIR__ . '/model/ontology/indexation.rdf'
        ),
        'php' => array(
            UpdateConfigDeliveryFactoryService::class,
            RegisterGenerisSearch::class,
            RegisterDeliveryEventsListener::class,
            RegisterPublishingFileSystem::class,
            RegisterDataStoreServices::class,
            GenerisSearchWhitelist::class
        )
    ),
    'uninstall' => array(
        UnRegisterGenerisSearch::class,
    ),
    'routes' => array(
        '/taoPublishing/api' => ['class' => ApiRoute::class],
        '/taoPublishing' => 'oat\\taoPublishing\\controller'
    ),
    'update' => Updater::class,
    'constants' => array(
        # views directory
        "DIR_VIEWS" => __DIR__ . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoPublishing/',

        #BASE WWW required by JS
        'BASE_WWW' => ROOT_URL . 'taoPublishing/views/'
    ),
    'extra' => array(
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    )
);
