<?php
use oat\tao\helpers\Template;
?>

<div class="delivery-headings flex-container-full">
    <header>
        <h2>Publish "<?=_dh(get_data('label'))?>"</h2>
    </header>
</div>

<header class="flex-container-full">
    <h3><?=get_data('formTitle')?></h3>
</header>
<div class="main-container flex-container-main-form">
    <div id="form-container">
        <?= tao_helpers_Scriptloader::render() ?>
        <header class="section-header flex-container-full">
            <h2><?=get_data('formTitle')?></h2>
        </header>
        <div class="main-container flex-container-main-form">
            <div class="form-content">
                <?=get_data('form')?>
            </div>
        </div>
        <div class="data-container-wrapper flex-container-remaining"></div>
    </div>
</div>

<?php
Template::inc('footer.tpl', 'tao');
?>
