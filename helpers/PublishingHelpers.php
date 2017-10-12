<?php
/*
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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoPublishing\helpers;

class PublishingHelpers
{
    /**
     * @return array
     */
   public static function getDeliveryFieldsOptions()
   {
       $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
       $deliveryProperties = \tao_helpers_form_GenerisFormFactory::getClassProperties($deliveryClass);
       $defaultProperties = \tao_helpers_form_GenerisFormFactory::getDefaultProperties();
       $deliveryProperties = array_merge($defaultProperties, $deliveryProperties);
       $options = [];
       /** @var \core_kernel_classes_Property $deliveryProperty */
       foreach ($deliveryProperties as $deliveryProperty) {
           $options[] = [
               'data' => $deliveryProperty->getLabel(),
               'parent' => 0,
               'attributes' => [
                   'id' => \tao_helpers_Uri::encode($deliveryProperty->getUri()),
                   'class' => 'node-instance'
               ]
           ];
       }
       return $options;
   }
}
