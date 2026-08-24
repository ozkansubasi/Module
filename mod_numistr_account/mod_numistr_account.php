<?php
/**
 * @package     NumisTR Account Module
 * @subpackage  mod_numistr_account
 * @version     1.2.0
 * @copyright   Copyright (C) 2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * mode=page   : Hesabım sayfası — profil / plan & abonelik / kullanım blokları
 * mode=navbar : menüde küçük "Giriş / Hesabım" bağlantısı
 *
 * Veri kaynakları (hepsi opsiyonel; tablo yoksa blok gizlenir):
 *   numistr_auth_identities (giriş yöntemi), numistr_scan_quota (aylık tarama),
 *   numistr_billing_events (mağaza aboneliği: ürün, bitiş, kaynak),
 *   numistr_subscriptions (iyzico web aboneliği — ADR-004; varsa kaynak olarak önceliklidir).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Uri\Uri;

$app  = Factory::getApplication();
$user = Factory::getUser();
$tag  = strtolower((string) Factory::getLanguage()->getTag());
$lang = strpos($tag, 'en') === 0 ? 'en' : 'tr';

$mode       = (string) $params->get('mode', 'page');
$proGroupId = (int) $params->get('pro_group_id', 10);
$freeLimit  = (int) $params->get('free_scan_limit', 10);
$isLoggedIn = $user->id > 0;
$isPro      = $isLoggedIn && in_array($proGroupId, array_map('intval', (array) $user->getAuthorisedGroups()), true);

$base = Uri::root(true) . '/index.php?option=com_ajax&plugin=numistrauth&format=raw';
$enc  = static function (string $v): string { return rawurlencode($v); };

$accountPath = (string) $params->get($lang === 'en' ? 'account_en' : 'account_tr', $lang === 'en' ? '/en/my-account' : '/tr/hesabim');
$plansPath   = (string) $params->get($lang === 'en' ? 'plans_en' : 'plans_tr', $lang === 'en' ? '/en/plans' : '/tr/abonelikler');
$proBuyUrl   = trim((string) $params->get('pro_buy_url', ''));

$urls = [
    'login'    => $base . '&task=login&return=' . $enc($accountPath),
    'signup'   => $base . '&task=signup&return=' . $enc($accountPath),
    'logout'   => $base . '&task=logout&return=' . $enc($lang === 'en' ? '/en/' : '/tr/'),
    'password' => $base . '&task=password&return=' . $enc($accountPath),
    'account'  => Uri::root(true) . $accountPath,
    'plans'    => Uri::root(true) . $plansPath,
    'pro_buy'  => $proBuyUrl !== '' ? $proBuyUrl : Uri::root(true) . $plansPath,
    'play'     => (string) $params->get('play_url', 'https://play.google.com/store/apps/details?id=com.anatoliancoins.app'),
    'appstore' => (string) $params->get('appstore_url', ''),
    'manage_play'  => 'https://play.google.com/store/account/subscriptions',
    'manage_apple' => 'https://apps.apple.com/account/subscriptions',
    'delete'   => 'mailto:' . (string) $params->get('support_email', 'bilgi@numistr.org')
        . '?subject=' . rawurlencode($lang === 'en' ? 'Account deletion request' : 'Hesap silme talebi')
        . '&body=' . rawurlencode(($lang === 'en' ? 'Please delete my NumisTR account: ' : 'NumisTR hesabımın silinmesini talep ediyorum: ') . ($isLoggedIn ? $user->email : '')),
    'support'  => 'mailto:' . (string) $params->get('support_email', 'bilgi@numistr.org'),
];

// iyzico web aboneliği uçları (plg_system_numistrbilling, ADR-004)
$billingBase = Uri::root(true) . '/index.php?option=com_ajax&plugin=numistrbilling&format=raw';
$urls['checkout_monthly'] = $billingBase . '&task=checkout&plan=monthly';
$urls['checkout_yearly']  = $billingBase . '&task=checkout&plan=yearly';
$urls['web_cancel']       = $billingBase . '&task=cancel';
$urls['web_cardupdate']   = $billingBase . '&task=cardupdate';

// ----------------------------------------------------------------------
// Account facts (all best-effort)
// ----------------------------------------------------------------------
$facts = [
    'registered'   => null,   // Y-m-d
    'login_method' => null,   // 'google' | 'email' | 'facebook' | null
    'scans_used'   => null,
    'scans_limit'  => $isPro ? null : $freeLimit,
    'sub_source'   => null,   // 'play' | 'apple' | 'web' | null
    'sub_product'  => null,   // monthly | yearly | raw product id
    'sub_expires'  => null,   // Y-m-d
    'sub_status'   => null,   // 'active' | 'expired' | 'cancelled'
    'web_active'   => false,  // iyzico aboneliği ACTIVE mi (iptal/kart butonları için)
];

if ($isLoggedIn) {
    $db = Factory::getDbo();

    try {
        $facts['registered'] = substr((string) $user->registerDate, 0, 10);
    } catch (\Throwable $e) {
    }

    try {
        $q = $db->getQuery(true)
            ->select($db->quoteName('subject'))
            ->from($db->quoteName('numistr_auth_identities'))
            ->where($db->quoteName('user_id') . ' = ' . (int) $user->id)
            ->order($db->quoteName('last_seen_at') . ' DESC')
            ->setLimit(1);
        $db->setQuery($q);
        $sub = (string) $db->loadResult();

        if ($sub !== '') {
            if (strpos($sub, 'google-oauth2|') === 0) {
                $facts['login_method'] = 'google';
            } elseif (strpos($sub, 'facebook|') === 0) {
                $facts['login_method'] = 'facebook';
            } elseif (strpos($sub, 'auth0|') === 0) {
                $facts['login_method'] = 'email';
            }
        }
    } catch (\Throwable $e) {
    }

    try {
        $q = $db->getQuery(true)
            ->select($db->quoteName('scans_used'))
            ->from($db->quoteName('numistr_scan_quota'))
            ->where($db->quoteName('user_id') . ' = ' . (int) $user->id)
            ->where($db->quoteName('month') . ' = ' . $db->quote(date('Y-m')))
            ->setLimit(1);
        $db->setQuery($q);
        $used = $db->loadResult();
        $facts['scans_used'] = $used === null ? 0 : (int) $used;
    } catch (\Throwable $e) {
    }

    // iyzico web aboneliği (numistr_subscriptions) — bulunursa kaynak olarak ÖNCELİKLİ
    try {
        $q = $db->getQuery(true)
            ->select([$db->quoteName('plan'), $db->quoteName('status'), $db->quoteName('current_period_end'), $db->quoteName('canceled_at')])
            ->from($db->quoteName('numistr_subscriptions'))
            ->where($db->quoteName('user_id') . ' = ' . (int) $user->id)
            ->where($db->quoteName('source') . ' = ' . $db->quote('iyzico'))
            ->order($db->quoteName('id') . ' DESC')
            ->setLimit(1);
        $db->setQuery($q);
        $ws = $db->loadAssoc();

        if ($ws) {
            $st       = strtoupper((string) $ws['status']);
            $pend     = !empty($ws['current_period_end']) ? substr((string) $ws['current_period_end'], 0, 10) : null;
            $inPeriod = $pend === null || strtotime($pend . ' 23:59:59') >= time();

            // Süresi çoktan bitmiş eski kayıtlar kaynak seçimini ele geçirmesin
            if ($st === 'ACTIVE' || $inPeriod) {
                $facts['sub_source']  = 'web';
                $facts['sub_product'] = in_array($ws['plan'], ['monthly', 'yearly'], true) ? $ws['plan'] : null;
                $facts['sub_expires'] = $pend;
                $facts['sub_status']  = $st === 'ACTIVE' ? 'active'
                    : ($st === 'CANCELED' ? 'cancelled' : 'expired');
                $facts['web_active']  = $st === 'ACTIVE';
            }
        }
    } catch (\Throwable $e) {
        // tablo henüz yoksa sessizce geç (plugin deploy edilmemiş olabilir)
    }

    try {
        if ($facts['sub_source'] === 'web') {
            throw new \RuntimeException('web subscription wins'); // RC fallback'ini atla
        }

        $q = $db->getQuery(true)
            ->select([$db->quoteName('event_type'), $db->quoteName('product_id'), $db->quoteName('action'), $db->quoteName('expires_at'), $db->quoteName('payload')])
            ->from($db->quoteName('numistr_billing_events'))
            ->where($db->quoteName('user_id') . ' = ' . (int) $user->id)
            ->order($db->quoteName('created_at') . ' DESC')
            ->setLimit(1);
        $db->setQuery($q);
        $ev = $db->loadAssoc();

        if ($ev) {
            $payload = json_decode((string) ($ev['payload'] ?? ''), true);
            $store   = strtoupper((string) ($payload['event']['store'] ?? $payload['store'] ?? ''));
            $pid     = strtolower((string) ($ev['product_id'] ?? ''));

            $facts['sub_source']  = $store === 'APP_STORE' ? 'apple' : ($store === 'PLAY_STORE' ? 'play' : ($store !== '' ? 'web' : null));
            $facts['sub_product'] = strpos($pid, 'year') !== false || strpos($pid, 'annual') !== false || strpos($pid, 'yillik') !== false ? 'yearly'
                : (strpos($pid, 'month') !== false || strpos($pid, 'aylik') !== false ? 'monthly' : ($pid !== '' ? $pid : null));
            $facts['sub_expires'] = !empty($ev['expires_at']) ? substr((string) $ev['expires_at'], 0, 10) : null;
            $type = strtoupper((string) ($ev['event_type'] ?? ''));
            $facts['sub_status']  = in_array($type, ['CANCELLATION'], true) ? 'cancelled'
                : (in_array($type, ['EXPIRATION'], true) || (string) ($ev['action'] ?? '') === 'revoke' ? 'expired' : 'active');
        }
    } catch (\Throwable $e) {
    }
}

// ----------------------------------------------------------------------
// i18n
// ----------------------------------------------------------------------
$t = $lang === 'en' ? [
    'hello' => 'Hello', 'plan' => 'Your plan', 'standard' => 'Standard (free)', 'pro' => 'PRO',
    'pro_desc' => 'Unlimited AI coin recognition, collection management, offline access, high-resolution images, no ads.',
    'std_desc' => 'Full database search, coin details and the interactive ancient map. Upgrade to PRO for AI recognition and more.',
    'upgrade' => 'Upgrade to PRO', 'manage' => 'Manage subscription', 'logout' => 'Sign out', 'login' => 'Sign in',
    'signup' => 'Create free account', 'account' => 'My account',
    'guest_title' => 'Your NumisTR account', 'guest_desc' => 'One free account for the website and the AnatolianCoins app.',
    'profile' => 'Profile', 'email' => 'E-mail', 'member_since' => 'Member since', 'login_method' => 'Sign-in method',
    'm_google' => 'Google', 'm_email' => 'E-mail & password', 'm_facebook' => 'Facebook', 'm_unknown' => '—',
    'password' => 'Change password', 'password_hint' => 'We e-mail you a reset link.', 'delete' => 'Delete my account',
    'subscription' => 'Plan & subscription', 'source' => 'Source', 's_play' => 'Google Play', 's_apple' => 'App Store', 's_web' => 'numistr.org', 's_none' => '—',
    'period' => 'Period', 'p_monthly' => 'Monthly', 'p_yearly' => 'Yearly', 'renews' => 'Renews / expires', 'status' => 'Status',
    'st_active' => 'Active', 'st_expired' => 'Expired', 'st_cancelled' => 'Cancelled (until period end)',
    'manage_play' => 'Manage on Google Play', 'manage_apple' => 'Manage on App Store', 'manage_web' => 'Manage subscription',
    'web_card' => 'Update card', 'web_cancel' => 'Cancel subscription',
    'web_cancel_confirm' => 'Your subscription will be cancelled; PRO access continues until the end of the paid period. Continue?',
    'usage' => 'Usage this month', 'scans' => 'AI coin scans', 'unlimited' => 'Unlimited', 'of' => 'of', 'scans_hint' => 'Resets on the 1st of each month.',
    'app' => 'AnatolianCoins app', 'app_desc' => 'Sign in to the app with the same e-mail to use PRO features on your phone.',
    'get_play' => 'Get it on Google Play', 'get_apple' => 'Download on the App Store', 'support' => 'Contact support',
] : [
    'hello' => 'Merhaba', 'plan' => 'Planınız', 'standard' => 'Standart (ücretsiz)', 'pro' => 'PRO',
    'pro_desc' => 'Sınırsız yapay zekâ sikke tanıma, koleksiyon yönetimi, çevrimdışı erişim, yüksek çözünürlüklü görseller, reklamsız.',
    'std_desc' => 'Tüm veritabanında arama, sikke detayları ve interaktif antik harita. Yapay zekâ tanıma ve daha fazlası için PRO\'ya geçin.',
    'upgrade' => 'PRO\'ya geç', 'manage' => 'Aboneliği yönet', 'logout' => 'Çıkış yap', 'login' => 'Giriş yap',
    'signup' => 'Ücretsiz üye ol', 'account' => 'Hesabım',
    'guest_title' => 'NumisTR hesabınız', 'guest_desc' => 'Web sitesi ve AnatolianCoins uygulaması için tek ücretsiz hesap.',
    'profile' => 'Profil', 'email' => 'E-posta', 'member_since' => 'Üyelik tarihi', 'login_method' => 'Giriş yöntemi',
    'm_google' => 'Google', 'm_email' => 'E-posta ve şifre', 'm_facebook' => 'Facebook', 'm_unknown' => '—',
    'password' => 'Şifre değiştir', 'password_hint' => 'E-postanıza sıfırlama bağlantısı göndeririz.', 'delete' => 'Hesabımı sil',
    'subscription' => 'Plan ve abonelik', 'source' => 'Kaynak', 's_play' => 'Google Play', 's_apple' => 'App Store', 's_web' => 'numistr.org', 's_none' => '—',
    'period' => 'Dönem', 'p_monthly' => 'Aylık', 'p_yearly' => 'Yıllık', 'renews' => 'Yenileme / bitiş', 'status' => 'Durum',
    'st_active' => 'Aktif', 'st_expired' => 'Sona erdi', 'st_cancelled' => 'İptal edildi (dönem sonuna kadar)',
    'manage_play' => 'Google Play\'de yönet', 'manage_apple' => 'App Store\'da yönet', 'manage_web' => 'Aboneliği yönet',
    'web_card' => 'Kart bilgilerini güncelle', 'web_cancel' => 'Aboneliği iptal et',
    'web_cancel_confirm' => 'Aboneliğiniz iptal edilecek; PRO erişiminiz ödenen dönemin sonuna kadar sürer. Devam edilsin mi?',
    'usage' => 'Bu ayki kullanım', 'scans' => 'Yapay zekâ sikke taraması', 'unlimited' => 'Sınırsız', 'of' => '/', 'scans_hint' => 'Her ayın 1\'inde sıfırlanır.',
    'app' => 'AnatolianCoins uygulaması', 'app_desc' => 'Telefonunuzda PRO özellikleri kullanmak için uygulamaya aynı e-postayla giriş yapın.',
    'get_play' => 'Google Play\'den indir', 'get_apple' => 'App Store\'dan indir', 'support' => 'Destek ile iletişim',
];

$fmtDate = static function (?string $ymd) use ($lang): string {
    if (!$ymd || $ymd === '0000-00-00') {
        return '—';
    }

    $ts = strtotime($ymd);

    return $ts ? date($lang === 'en' ? 'j M Y' : 'd.m.Y', $ts) : $ymd;
};

require ModuleHelper::getLayoutPath('mod_numistr_account', $params->get('layout', 'default'));
