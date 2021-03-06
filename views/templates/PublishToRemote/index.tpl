<?php
use oat\tao\helpers\Template;
?>


<div class="main-container flex-container-main-form">
    <h2><?=__('Publish "%s"', _dh(get_data('subject-label')))?></h2>
    <div id="form-container" class="form-content">
        <div class="xhtml_form">
            <form action="<?= get_data('submit-url'); ?>" id="publish-remote">
                <input type="hidden" value="<?= get_data('subject-uri') ?>" name="subject-uri" id="selected-subject-uri">
                <input type="hidden" value="<?= get_data('class-content-exceeded') ?>" name="class-content-exceeded" id="class-content-exceeded">
                <input type="hidden" value="<?= get_data('class-content-limit') ?>" name="class-content-limit" id="class-content-limit">
                <?php if (has_data('warning')) :?>
                <div class='feedback-warning'>
                    <?= get_data('warning')?>
                </div>
                <?php endif;?>

                <?php if (count(get_data('remote-environments')) > 0) :?>
                    <h3><?= __('Remote environment(s)')?></h3>
                    <ul class="plain">
                        <?php foreach (get_data('remote-environments') as $index => $environment) :?>
                            <li>
                                <input
                                        type="checkbox"
                                        <?php if (!$environment->isPublishingEnabled()): ?>
                                            disabled
                                        <?php endif;?>
                                        name="remote-environments[]"
                                        value="<?= $environment->getUri();?>"
                                        id="remote-environment-<?= $index ?>"
                                >
                                <label for="remote-environment-<?= $index ?>"><?= $environment->getLabel();?></label>
                            </li>
                        <?php endforeach;?>
                    </ul>
                    <div class="form-toolbar">
                        <button type="submit" name="Publish" id="publish-to-remote" class="form-submitter btn-success small hidden" value="Publish"><span class="icon-external"></span> <?= __('Publish')?></button>
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
