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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *               
 * 
 */
namespace oat\taoPublishing\view\form;

use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoPublishing\model\PlatformService;
/**
 * Create a form from a  resource of your ontology. 
 * Each property will be a field, regarding it's widget.
 *
 * @access public
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package tao
 
 */
class WizardForm
    extends \tao_helpers_form_FormContainer
{

    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('simpleWizard');
        
        $createElt = \tao_helpers_form_FormFactory::getElement('create', 'Free');
		$createElt->setValue('<button class="form-submitter btn-success small" type="button"><span class="icon-publish"></span> ' .__('Publish').'</button>');
		$this->form->setActions(array(), 'top');
		$this->form->setActions(array($createElt), 'bottom');

    }

    /*
    * Short description of method initElements
    *
    * @access public
    * @author Joel Bout, <joel.bout@tudor.lu>
    * @return mixed
    */
    public function initElements()
    {
        $testId = $this->data['test'];
        
        $testUriElt = \tao_helpers_form_FormFactory::getElement('test', 'Hidden');
        $testUriElt->setValue($testId);
        $this->form->addElement($testUriElt);
        
        //create the element to select the import format

        $formatElt = \tao_helpers_form_FormFactory::getElement('environment', 'Combobox');
        $formatElt->setDescription(__('Select the environment to publish to'));
        $platforms = PlatformService::singleton()->getRootClass()->getInstances(true);
        foreach ($platforms as $test) {
            $options[$test->getUri()] = $test->getLabel();
        } 
        
        if (!empty($options)) {
            $formatElt->setOptions($options);
            $formatElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
            $this->form->addElement($formatElt);
        } else {
            $this->form->setActions(array(), 'bottom');
        }
    }
}