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
        $this->form->setDecorators(
            [
                'actions-bottom' => new \tao_helpers_form_xhtml_TagWrapper(
                    ['tag' => 'div', 'cssClass' => 'form-toolbar']
                ),
            ]
        );
        $this->form->setActions([], 'top');
        $this->form->setActions([$createElt], 'bottom');
    }

    /**
     * @inheritDoc
     */
    protected function initElements(): void
    {
        $class = $this->data['class'];
        $instance = $this->data['instance'];

        $classUriElt = \tao_helpers_form_FormFactory::getElement('classUri', 'Hidden');
        $classUriElt->setValue($class->getUri());
        $this->form->addElement($classUriElt);

        $class = $this->data['class'];

        $classUriElt = \tao_helpers_form_FormFactory::getElement('uri', 'Hidden');
        $classUriElt->setValue($instance->getUri());
        $this->form->addElement($classUriElt);
    }
}