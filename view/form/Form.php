<?php

namespace oat\taoPublishing\view\form;

use tao_helpers_form_xhtml_Form;

class Form extends tao_helpers_form_xhtml_Form
{
    /** @var string */
    private $formAction;

    /**
     * @return string
     */
    public function render(): string
    {
        $formView = "<div class='xhtml_form'>\n";

        $formView .= $this->renderFormHeader();

        $formView .= "<input type='hidden' class='global' name='{$this->name}_sent' value='1' />\n";

        $formView .= $this->generateFormErrorBlock();

        $formView .= $this->renderElements();

        $formView .= $this->renderActions();

        $formView .= "</form>\n";
        $formView .= "</div>\n";

        return $formView;
    }

    /**
     * @return string
     */
    public function getFormAction(): string
    {
        if (!$this->formAction) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $this->formAction = strpos($requestUri, '?') > 0
                ? substr($requestUri, 0, strpos($requestUri, '?'))
                : $requestUri;

            // Defensive code, prevent double leading slashes issue.
            if (strpos($this->formAction, '//') === 0) {
                $this->formAction = substr($this->formAction, 1);
            }
        }

        return $this->formAction;
    }

    /**
     * @return string
     */
    private function renderFormHeader(): string
    {
        $action = $this->getFormAction();

        $formView = "<form method='post' id='{$this->name}' name='{$this->name}' action='$action' ";

        if ($this->hasFileUpload()) {
            $formView .= "enctype='multipart/form-data' ";
        }

        $formView .= ">\n";

        return $formView;
    }

    /**
     * @return string
     */
    private function generateFormErrorBlock(): string
    {
        if (!empty($this->error)) {
            return '<div class="xhtml_form_error">' . $this->error . '</div>';
        }

        return '';
    }
}