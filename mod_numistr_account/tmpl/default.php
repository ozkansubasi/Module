<?php
defined('_JEXEC') or die;

$h = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$sfx = $h($params->get('moduleclass_sfx', ''));

if ($mode === 'navbar') : ?>
<div class="mod-numistr-account mod-numistr-account--navbar<?php echo $sfx; ?>">
    <?php if ($isLoggedIn) : ?>
        <a class="uk-button uk-button-text" href="<?php echo $h($urls['account']); ?>"><?php echo $h($t['account']); ?><?php echo $isPro ? ' <span class="uk-label uk-label-warning" style="margin-left:6px">PRO</span>' : ''; ?></a>
    <?php else : ?>
        <a class="uk-button uk-button-text" href="<?php echo $h($urls['login']); ?>"><?php echo $h($t['login']); ?></a>
    <?php endif; ?>
</div>
<?php return; endif; ?>

<div class="mod-numistr-account uk-card uk-card-default uk-card-body uk-border-rounded<?php echo $sfx; ?>">
<?php if (!$isLoggedIn) : ?>
    <h3 class="uk-card-title uk-margin-small-bottom"><?php echo $h($t['guest_title']); ?></h3>
    <p class="uk-margin-small"><?php echo $h($t['guest_desc']); ?></p>
    <div class="uk-flex uk-flex-wrap" style="gap:10px">
        <a class="uk-button uk-button-primary" href="<?php echo $h($urls['signup']); ?>"><?php echo $h($t['signup']); ?></a>
        <a class="uk-button uk-button-default" href="<?php echo $h($urls['login']); ?>"><?php echo $h($t['login']); ?></a>
    </div>
<?php else : ?>
    <h3 class="uk-card-title uk-margin-small-bottom"><?php echo $h($t['hello']); ?>, <?php echo $h($user->name); ?></h3>
    <p class="uk-text-meta uk-margin-remove-top"><?php echo $h($user->email); ?></p>
    <p class="uk-margin-small">
        <strong><?php echo $h($t['plan']); ?>:</strong>
        <?php if ($isPro) : ?>
            <span class="uk-label uk-label-warning">PRO</span>
        <?php else : ?>
            <span class="uk-label"><?php echo $h($t['standard']); ?></span>
        <?php endif; ?>
    </p>
    <p class="uk-margin-small"><?php echo $h($isPro ? $t['pro_desc'] : $t['std_desc']); ?></p>
    <div class="uk-flex uk-flex-wrap" style="gap:10px">
        <?php if ($isPro) : ?>
            <a class="uk-button uk-button-default" href="<?php echo $h($urls['plans']); ?>"><?php echo $h($t['manage']); ?></a>
        <?php else : ?>
            <a class="uk-button uk-button-primary" href="<?php echo $h($urls['pro_buy']); ?>"><?php echo $h($t['upgrade']); ?></a>
        <?php endif; ?>
        <a class="uk-button uk-button-text" href="<?php echo $h($urls['logout']); ?>"><?php echo $h($t['logout']); ?></a>
    </div>
    <p class="uk-text-meta uk-margin-small-top uk-margin-remove-bottom"><?php echo $h($t['app']); ?></p>
<?php endif; ?>
</div>
