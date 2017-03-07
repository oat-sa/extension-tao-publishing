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

use oat\taoPublishing\view\form\WizardForm;
use oat\generis\model\OntologyAwareTrait;
use oat\taoPublishing\model\DeployTest;
use oat\oatbox\task\Queue;
/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoPublishing
 * @license GPL-2.0
 *
 */
class Publish extends \tao_actions_CommonModule {

    use OntologyAwareTrait;
    
    public function wizard()
    {
            try {
            $formContainer = new WizardForm(array('test' => $this->getRequestParameter('id')));
            $myForm = $formContainer->getForm();
             
            if ($myForm->isValid() && $myForm->isSubmited()) {
                $test = $this->getResource($myForm->getValue('test'));
                $env = $this->getResource($myForm->getValue('environment'));
                
                $queue = $this->getServiceManager()->get(Queue::CONFIG_ID);
                $report = $queue->createTask(new DeployTest(), [$test->getUri(), $env->getUri()]);
                //$this->returnReport($report);
                $this->returnReport(\common_report_Report::createSuccess('The deployement of your test has been scheduled'));
            } else {
                $this->setData('myForm', $myForm->render());
                $this->setData('formTitle', __('Publish to a delivery environment'));
                $this->setView('form.tpl', 'tao');
            }
    
        } catch (NoTestsException $e) {
            $this->setView('DeliveryMgmt/wizard_error.tpl');
        }
    }
    
}