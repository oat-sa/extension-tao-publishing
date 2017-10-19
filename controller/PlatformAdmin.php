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

namespace oat\taoPublishing\controller;

use oat\taoPublishing\helpers\PublishingHelpers;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoPublishing
 * @license GPL-2.0
 *
 */
class PlatformAdmin extends \tao_actions_SaSModule {

    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getClassService();
    }

    public function editInstance()
    {
        $clazz = $this->getCurrentClass();
        $instance = $this->getCurrentInstance();
        $myFormContainer = new \tao_actions_form_Instance($clazz, $instance);

        $myForm = $myFormContainer->getForm();
        if($myForm->isSubmited()){
            if($myForm->isValid()){

                $values = $myForm->getValues();
                // save properties
                $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($instance);
                $instance = $binder->bind($values);
                $message = __('Instance saved');

                $this->setData('message',$message);
                $this->setData('reload', true);
            }
        }
        $deliveryElementClass = \tao_helpers_Uri::encode(PublishingService::DELIVERY_FIELDS);
        $deliveryElement = $myForm->getElement($deliveryElementClass);
        $deliveryElement->setOptions(PublishingHelpers::getDeliveryFieldsOptions());
        $myForm->removeElement($deliveryElement);
        $myForm->addElement($deliveryElement);

        $this->setData('formTitle', __('Edit Instance'));
        $this->setData('myForm', $myForm->render());
        $this->setView('form.tpl', 'tao');
    }

    public function addInstanceForm()
    {
        if(!\tao_helpers_Request::isAjax()){
            throw new \Exception("wrong request mode");
        }

        $clazz = $this->getCurrentClass();
        $formContainer = new \tao_actions_form_CreateInstance(array($clazz), array());
        $myForm = $formContainer->getForm();

        if($myForm->isSubmited()){
            if($myForm->isValid()){

                $properties = $myForm->getValues();
                $instance = $this->createInstance(array($clazz), $properties);

                $this->setData('message', __($instance->getLabel().' created'));
                $this->setData('reload', true);
                //return $this->redirect(_url('editInstance', null, null, array('uri' => $instance)));
            }
        }

        $deliveryElementClass = \tao_helpers_Uri::encode(PublishingService::DELIVERY_FIELDS);
        $deliveryElement = $myForm->getElement($deliveryElementClass);
        $deliveryElement->setOptions(PublishingHelpers::getDeliveryFieldsOptions());

        $myForm->removeElement($deliveryElement);
        $myForm->addElement($deliveryElement);

        $this->setData('formTitle', __('Create instance of ').$clazz->getLabel());
        $this->setData('myForm', $myForm->render());

        $this->setView('form.tpl', 'tao');
    }

    /**
     * (non-PHPdoc)
     *
     * @see tao_actions_RdfController::getClassService()
     */
    public function getClassService()
    {
        return PlatformService::singleton();
    }
}