<?php
$authType = get_data('authType');
$allowedTypes = get_data('allowedTypes');
?>

<div class="auth-selector-component">
    <div class="auth-type-container">
        <label for="taoPlatformAuthType" class="form_desc"><?=__('Auth type')?></label>
        <select name="<?= \tao_helpers_Uri::encode(\oat\taoPublishing\model\PlatformService::PROPERTY_AUTH_TYPE)?>" id="taoPlatformAuthType" class="auth-type-selector">
            <?php foreach($allowedTypes as $type) :?>
                <option value="<?= $type->getAuthClass()->getUri() ?>"
                        <?php if ($type->getAuthClass()->getUri() == $authType->getAuthClass()->getUri()) :?>selected="selected"<?php endif; ?>
                ><?=$type->getAuthClass()->getLabel()?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="authenticator-settings">
        <?php foreach ($allowedTypes as $allowedType) :?>
            <div data-auth-method="<?= $allowedType->getAuthClass()->getUri(); ?>" class="auth-form-part<?php if ($allowedType->getAuthClass()->getUri() != $authType->getAuthClass()->getUri()) :?> hidden<?php endif; ?>">
            <?=$allowedType->getTemplate()?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
