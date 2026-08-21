<?php
/**
 * @package     NumisTR Account Module
 * @subpackage  mod_numistr_account
 * @version     1.0.0
 * @copyright   Copyright (C) 2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Giriş durumu + plan kartı. Giriş/üye ol/çıkış linkleri plg_system_numistrauth (com_ajax) uçlarına gider.
 * mode=page   : Hesabım sayfası kartı
 * mode=navbar : menüde küçük "Giriş / Hesabım" bağlantısı
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
$isLoggedIn = $user->id > 0;
$isPro      = $isLoggedIn && in_array($proGroupId, array_map('intval', (array) $user->getAuthorisedGroups()), true);

$current = Uri::getInstance()->toString(['path', 'query']);
$base    = Uri::root(true) . '/index.php?option=com_ajax&plugin=numistrauth&format=raw';
$enc     = static function (string $v): string { return rawurlencode($v); };

$accountPath = (string) $params->get($lang === 'en' ? 'account_en' : 'account_tr', $lang === 'en' ? '/en/my-account' : '/tr/hesabim');
$plansPath   = (string) $params->get($lang === 'en' ? 'plans_en' : 'plans_tr', $lang === 'en' ? '/en/plans' : '/tr/abonelikler');
$proBuyUrl   = trim((string) $params->get('pro_buy_url', ''));

$urls = [
    'login'   => $base . '&task=login&return=' . $enc($accountPath),
    'signup'  => $base . '&task=signup&return=' . $enc($accountPath),
    'logout'  => $base . '&task=logout&return=' . $enc($lang === 'en' ? '/en/' : '/tr/'),
    'account' => Uri::root(true) . $accountPath,
    'plans'   => Uri::root(true) . $plansPath,
    'pro_buy' => $proBuyUrl !== '' ? $proBuyUrl : Uri::root(true) . $plansPath,
];

$t = $lang === 'en' ? [
    'hello'       => 'Hello',
    'plan'        => 'Your plan',
    'standard'    => 'Standard (free)',
    'pro'         => 'PRO',
    'pro_desc'    => 'Unlimited AI coin recognition, collection management, offline access, high-resolution images, no ads.',
    'std_desc'    => 'Full database search, coin details and the interactive ancient map. Upgrade to PRO for AI recognition and more.',
    'upgrade'     => 'Upgrade to PRO',
    'manage'      => 'Manage subscription',
    'app'         => 'Use the same account in the AnatolianCoins app.',
    'logout'      => 'Sign out',
    'login'       => 'Sign in',
    'signup'      => 'Create free account',
    'account'     => 'My account',
    'guest_title' => 'Your NumisTR account',
    'guest_desc'  => 'One free account for the website and the AnatolianCoins app.',
] : [
    'hello'       => 'Merhaba',
    'plan'        => 'Planınız',
    'standard'    => 'Standart (ücretsiz)',
    'pro'         => 'PRO',
    'pro_desc'    => 'Sınırsız yapay zekâ sikke tanıma, koleksiyon yönetimi, çevrimdışı erişim, yüksek çözünürlüklü görseller, reklamsız.',
    'std_desc'    => 'Tüm veritabanında arama, sikke detayları ve interaktif antik harita. Yapay zekâ tanıma ve daha fazlası için PRO\'ya geçin.',
    'upgrade'     => 'PRO\'ya geç',
    'manage'      => 'Aboneliği yönet',
    'app'         => 'Aynı hesapla AnatolianCoins uygulamasına giriş yapabilirsiniz.',
    'logout'      => 'Çıkış yap',
    'login'       => 'Giriş yap',
    'signup'      => 'Ücretsiz üye ol',
    'account'     => 'Hesabım',
    'guest_title' => 'NumisTR hesabınız',
    'guest_desc'  => 'Web sitesi ve AnatolianCoins uygulaması için tek ücretsiz hesap.',
];

require ModuleHelper::getLayoutPath('mod_numistr_account', $params->get('layout', 'default'));
