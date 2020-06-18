<?php
use oat\tao\helpers\Template;
?>


<div class="main-container flex-container-main-form">
    <h2><?=__('Publish "%s"', _dh(get_data('delivery-label')))?></h2>
    <div id="form-container" class="form-content">
        <div class="xhtml_form">
            <form action="<?= get_data('submit-url'); ?>">
                <input type="hidden" value="<?= get_data('delivery-uri') ?>" name="delivery-uri">
                <?php if (has_data('warning')) :?>
                <div class='feedback-warning'>
                    <?= get_data('warning')?>
                </div>
                <?php endif;?>

                <?php if (count(get_data('remote-environments')) > 0) :?>
                    <h3><?= __('Remote environment(s)')?></h3>
                    <div class="remotePublishingEnvironments">
                        <?php foreach (get_data('remote-environments') as $environment) :?>
                            <p>
                                <input type="checkbox" name="remote-environment[]" value="<?= $environment->getUri();?>">
                                <label><?= $environment->getLabel();?></label>
                            </p>
                        <?php endforeach;?>
                    </div>
                    <div class="form-toolbar">
                        <button type="submit" name="Publish" id="Publish" class="form-submitter btn-success small" value="Publish"><span class="icon-external"></span> <?= __('Publish')?></button>
                    </div>
                <?php else:?>
                    <em><?= __('No Remote environments defined')?></em>
                <?php endif;?>
            </form>
        </div>
    </div>
</div>
<?php
Template::inc('footer.tpl', 'tao')
?>