<?php

declare(strict_types=1);

namespace oat\taoPublishing\view\form;

class PublishForm extends \tao_helpers_form_FormContainer
{
    /**
     * @inheritDoc
     * @throws \common_Exception
     * @throws \Exception
     */
    protected function initForm(): void
    {
        $this->form = new Form('publish');

        $createElt = \tao_helpers_form_FormFactory::getElement('save', 'Free');
        $createElt->setValue(
            '<button class="form-submitter btn-success small" type="button"><span class="icon-publish"></span> '
            . __('Publish') . '</button>'
        );
        $this->form->setDecorators([
            'actions-bottom' => new \tao_helpers_form_xhtml_TagWrapper(['tag' => 'div', 'cssClass' => 'form-toolbar']),
         ]);
        $this->form->setActions([], 'top');
        $this->form->setActions([$createElt], 'bottom');
        $this->form->setFormAction('/taoPublishing/Publication/publishDelivery');
    }

    /**
     * @inheritDoc
     */
    protected function initElements(): void
    {
        // TODO: Implement initElements() method.
    }
}