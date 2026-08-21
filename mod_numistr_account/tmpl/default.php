<?php
defined('_JEXEC') or die;

$h   = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$sfx = $h($params->get('moduleclass_sfx', ''));

if ($mode === 'navbar') : ?>
<div class="mod-numistr-account mod-numistr-account--navbar<?php echo $sfx; ?>">
    <?php if ($isLoggedIn) : ?>
        <a class="uk-button uk-button-text" href="<?php echo $h($urls['account']); ?>"><span uk-icon="icon: user; ratio: .85"></span> <?php echo $h($t['account']); ?><?php echo $isPro ? ' <span class="uk-label uk-label-warning" style="margin-left:6px">PRO</span>' : ''; ?></a>
    <?php else : ?>
        <a class="uk-button uk-button-text" href="<?php echo $h($urls['login']); ?>"><span uk-icon="icon: sign-in; ratio: .85"></span> <?php echo $h($t['login']); ?></a>
    <?php endif; ?>
</div>
<?php return; endif; ?>

<style>
.mod-numistr-account .nt-acc-card{border:1px solid #ececec;border-radius:16px;padding:24px;background:#fff;height:100%}
.mod-numistr-account .nt-acc-card h3{font-size:16px;letter-spacing:.06em;text-transform:uppercase;color:#6b6b6b;margin:0 0 14px}
.mod-numistr-account dl{display:grid;grid-template-columns:max-content 1fr;gap:6px 16px;margin:0 0 16px;font-size:14px}
.mod-numistr-account dt{color:#6b6b6b}
.mod-numistr-account dd{margin:0;color:#222}
.mod-numistr-account .nt-acc-actions{display:flex;flex-wrap:wrap;gap:8px}
.mod-numistr-account .nt-acc-actions .uk-button{font-size:13px}
.mod-numistr-account .nt-meter{height:8px;border-radius:4px;background:#eee;overflow:hidden;margin:8px 0 4px}
.mod-numistr-account .nt-meter span{display:block;height:100%;background:linear-gradient(90deg,#8B4513,#D4AF37)}
.mod-numistr-account .nt-hero{border-radius:16px;padding:24px;background:linear-gradient(135deg,rgba(139,69,19,.08),rgba(212,175,55,.18));margin-bottom:20px}
</style>

<div class="mod-numistr-account<?php echo $sfx; ?>">
<?php if (!$isLoggedIn) : ?>
    <div class="nt-hero">
        <h3 class="uk-card-title uk-margin-small-bottom"><?php echo $h($t['guest_title']); ?></h3>
        <p class="uk-margin-small"><?php echo $h($t['guest_desc']); ?></p>
        <div class="nt-acc-actions">
            <a class="uk-button uk-button-primary" href="<?php echo $h($urls['signup']); ?>"><?php echo $h($t['signup']); ?></a>
            <a class="uk-button uk-button-default" href="<?php echo $h($urls['login']); ?>"><?php echo $h($t['login']); ?></a>
        </div>
    </div>
<?php else : ?>
    <div class="nt-hero uk-flex uk-flex-middle uk-flex-between uk-flex-wrap" style="gap:12px">
        <div>
            <h3 class="uk-card-title uk-margin-remove"><?php echo $h($t['hello']); ?>, <?php echo $h($user->name); ?></h3>
            <div class="uk-text-meta"><?php echo $h($user->email); ?></div>
        </div>
        <div class="nt-acc-actions">
            <?php if ($isPro) : ?>
                <span class="uk-label uk-label-warning" style="font-size:13px;padding:6px 12px">PRO</span>
            <?php else : ?>
                <a class="uk-button uk-button-primary" href="<?php echo $h($urls['pro_buy']); ?>"><?php echo $h($t['upgrade']); ?></a>
            <?php endif; ?>
            <a class="uk-button uk-button-text" href="<?php echo $h($urls['logout']); ?>"><?php echo $h($t['logout']); ?></a>
        </div>
    </div>

    <div class="uk-grid uk-grid-small uk-child-width-1-3@m uk-grid-match" uk-grid>
        <!-- Profil -->
        <div>
            <div class="nt-acc-card">
                <h3><?php echo $h($t['profile']); ?></h3>
                <dl>
                    <dt><?php echo $h($t['email']); ?></dt><dd><?php echo $h($user->email); ?></dd>
                    <dt><?php echo $h($t['member_since']); ?></dt><dd><?php echo $h($fmtDate($facts['registered'])); ?></dd>
                    <dt><?php echo $h($t['login_method']); ?></dt><dd><?php echo $h($t['m_' . ($facts['login_method'] ?: 'unknown')]); ?></dd>
                </dl>
                <div class="nt-acc-actions">
                    <?php if ($facts['login_method'] === 'email' || $facts['login_method'] === null) : ?>
                        <a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($urls['password']); ?>" title="<?php echo $h($t['password_hint']); ?>"><?php echo $h($t['password']); ?></a>
                    <?php endif; ?>
                    <a class="uk-button uk-button-text uk-button-small" href="<?php echo $h($urls['delete']); ?>"><?php echo $h($t['delete']); ?></a>
                </div>
            </div>
        </div>

        <!-- Plan & abonelik -->
        <div>
            <div class="nt-acc-card">
                <h3><?php echo $h($t['subscription']); ?></h3>
                <dl>
                    <dt><?php echo $h($t['plan']); ?></dt>
                    <dd><?php echo $isPro ? '<span class="uk-label uk-label-warning">PRO</span>' : $h($t['standard']); ?></dd>
                    <?php if ($isPro) : ?>
                        <dt><?php echo $h($t['source']); ?></dt><dd><?php echo $h($t['s_' . ($facts['sub_source'] ?: 'none')]); ?></dd>
                        <?php if ($facts['sub_product']) : ?>
                            <dt><?php echo $h($t['period']); ?></dt>
                            <dd><?php echo $h(in_array($facts['sub_product'], ['monthly', 'yearly'], true) ? $t['p_' . $facts['sub_product']] : $facts['sub_product']); ?></dd>
                        <?php endif; ?>
                        <?php if ($facts['sub_expires']) : ?>
                            <dt><?php echo $h($t['renews']); ?></dt><dd><?php echo $h($fmtDate($facts['sub_expires'])); ?></dd>
                        <?php endif; ?>
                        <?php if ($facts['sub_status']) : ?>
                            <dt><?php echo $h($t['status']); ?></dt><dd><?php echo $h($t['st_' . $facts['sub_status']]); ?></dd>
                        <?php endif; ?>
                    <?php endif; ?>
                </dl>
                <p class="uk-text-small uk-margin-small"><?php echo $h($isPro ? $t['pro_desc'] : $t['std_desc']); ?></p>
                <div class="nt-acc-actions">
                    <?php if ($isPro) : ?>
                        <?php if ($facts['sub_source'] === 'play') : ?>
                            <a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($urls['manage_play']); ?>" target="_blank" rel="noopener"><?php echo $h($t['manage_play']); ?></a>
                        <?php elseif ($facts['sub_source'] === 'apple') : ?>
                            <a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($urls['manage_apple']); ?>" target="_blank" rel="noopener"><?php echo $h($t['manage_apple']); ?></a>
                        <?php else : ?>
                            <a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($urls['plans']); ?>"><?php echo $h($t['manage_web']); ?></a>
                        <?php endif; ?>
                    <?php else : ?>
                        <a class="uk-button uk-button-primary uk-button-small" href="<?php echo $h($urls['pro_buy']); ?>"><?php echo $h($t['upgrade']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kullanım + uygulama -->
        <div>
            <div class="nt-acc-card">
                <h3><?php echo $h($t['usage']); ?></h3>
                <?php if ($facts['scans_used'] !== null) : ?>
                    <div class="uk-text-small"><?php echo $h($t['scans']); ?></div>
                    <?php if ($isPro) : ?>
                        <div class="uk-h4 uk-margin-remove"><?php echo (int) $facts['scans_used']; ?> · <span class="uk-text-muted" style="font-size:14px"><?php echo $h($t['unlimited']); ?></span></div>
                    <?php else : ?>
                        <?php $pct = $facts['scans_limit'] > 0 ? min(100, (int) round($facts['scans_used'] * 100 / $facts['scans_limit'])) : 0; ?>
                        <div class="uk-h4 uk-margin-remove"><?php echo (int) $facts['scans_used']; ?> <span class="uk-text-muted" style="font-size:14px"><?php echo $h($t['of']); ?> <?php echo (int) $facts['scans_limit']; ?></span></div>
                        <div class="nt-meter"><span style="width:<?php echo $pct; ?>%"></span></div>
                    <?php endif; ?>
                    <div class="uk-text-meta"><?php echo $h($t['scans_hint']); ?></div>
                <?php endif; ?>
                <hr class="uk-margin-small">
                <div class="uk-text-small uk-text-bold"><?php echo $h($t['app']); ?></div>
                <p class="uk-text-small uk-margin-small"><?php echo $h($t['app_desc']); ?></p>
                <div class="nt-acc-actions">
                    <?php if ($urls['play'] !== '') : ?>
                        <a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($urls['play']); ?>" target="_blank" rel="noopener"><?php echo $h($t['get_play']); ?></a>
                    <?php endif; ?>
                    <?php if ($urls['appstore'] !== '') : ?>
                        <a class="uk-button uk-button-default uk-button-small" href="<?php echo $h($urls['appstore']); ?>" target="_blank" rel="noopener"><?php echo $h($t['get_apple']); ?></a>
                    <?php endif; ?>
                    <a class="uk-button uk-button-text uk-button-small" href="<?php echo $h($urls['support']); ?>"><?php echo $h($t['support']); ?></a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
