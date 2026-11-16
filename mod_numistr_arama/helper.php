<?php
/**
 * @package     NumisTR Search Module
 * @subpackage  mod_numistr_arama
 * @version     1.0.0
 * @copyright   Copyright (C) 2026 NumisTR. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Saf yardımcılar (Joomla'sız test edilir: php Module/tests/arama_test.php).
 */

defined('_JEXEC') or die;

class ModNumistrAramaHelper
{
    public const VERSION = '1.0.0';
    public const MAX_Q = 60;

    /** Joomla dil etiketi → 'tr' | 'en'. */
    public static function lang(string $tag): string
    {
        return stripos($tag, 'en') === 0 ? 'en' : 'tr';
    }

    /** Ön yüz metinleri (JS'e de geçer). */
    public static function strings(string $lang): array
    {
        if ($lang === 'en') {
            return [
                'label' => 'Search the catalogue',
                'placeholder' => 'Coin, mint, ruler or ancient city',
                'placeholder_long' => 'Search coins, mints, rulers or ancient cities (e.g. Lampsacus, winged horse, Ephesus)',
                'button' => 'Search',
                'examples' => 'Try:',
                'tab_all' => 'All',
                'tab_coins' => 'Coins',
                'tab_settlements' => 'Settlements',
                'tab_articles' => 'Articles',
                'h_coins' => 'Coins',
                'h_settlements' => 'Ancient Settlements',
                'h_articles' => 'Coin Stories',
                'more_coins' => 'Show more coins',
                'more' => 'Show more',
                'all_coins' => 'All coin results',
                'obverse' => 'Obverse',
                'reverse' => 'Reverse',
                'loading' => 'Searching…',
                'empty' => 'No results found.',
                'tip_title' => 'Can’t find it?',
                'tip' => 'Try another spelling (Ephesus, Pergamum, Lampsacus) or a motif such as “winged horse”. You can also ask the assistant at the bottom right.',
                'error' => 'Search is unavailable right now. Please try again shortly.',
                'rate' => 'Too many searches in a short time. Please wait a minute and try again.',
                'short' => 'Type at least 2 characters.',
                'bc' => 'BC',
                'ad' => 'AD',
                'title_prefix' => 'Search',
            ];
        }

        return [
            'label' => 'Katalogda ara',
            'placeholder' => 'Sikke, darphane, hükümdar, antik kent…',
            'placeholder_long' => 'Sikke, darphane, hükümdar ya da antik kent ara (ör. Lampsakos, kanatlı at, Efes)',
            'button' => 'Ara',
            'examples' => 'Örnek:',
            'tab_all' => 'Tümü',
            'tab_coins' => 'Sikkeler',
            'tab_settlements' => 'Yerleşimler',
            'tab_articles' => 'Yazılar',
            'h_coins' => 'Sikkeler',
            'h_settlements' => 'Antik Yerleşimler',
            'h_articles' => 'Sikke Hikayeleri',
            'more_coins' => 'Daha fazla sikke göster',
            'more' => 'Daha fazla göster',
            'all_coins' => 'Tüm sikke sonuçları',
            'obverse' => 'Ön yüz',
            'reverse' => 'Arka yüz',
            'loading' => 'Aranıyor…',
            'empty' => 'Sonuç bulunamadı.',
            'tip_title' => 'Aradığınızı bulamadınız mı?',
            'tip' => 'Türkçe adla da arayabilirsiniz (Efes, Bergama, Lapseki) ya da “kanatlı at” gibi bir motif yazın. Sağ alttaki asistana da sorabilirsiniz.',
            'error' => 'Arama şu anda yapılamıyor. Lütfen biraz sonra tekrar deneyin.',
            'rate' => 'Kısa sürede çok fazla arama yapıldı. Bir dakika bekleyip tekrar deneyin.',
            'short' => 'En az 2 karakter yazın.',
            'bc' => 'MÖ',
            'ad' => 'MS',
            'title_prefix' => 'Arama',
        ];
    }

    /** Sorguyu sayfaya basılabilir hâle getirir (kontrol karakteri yok, en çok 60 karakter, kırpılmış). */
    public static function cleanQuery(?string $q): string
    {
        $q = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $q) ?? '';
        $q = trim(preg_replace('/\s+/u', ' ', $q) ?? '');

        return mb_strlen($q, 'UTF-8') > self::MAX_Q ? trim(mb_substr($q, 0, self::MAX_Q, 'UTF-8')) : $q;
    }

    /** Sonuç sayfası yolu: yalnız '/' ile başlayan site içi yol kabul edilir. */
    public static function resultsPath(string $lang, string $tr, string $en): string
    {
        $p = trim($lang === 'en' ? $en : $tr);
        if ($p === '' || $p[0] !== '/' || strpos($p, '//') === 0 || preg_match('/[\s"\'<>]/', $p)) {
            return $lang === 'en' ? '/en/search' : '/tr/arama';
        }

        return $p;
    }

    /** "Lampsakos, kanatlı at,, Efes" → ['Lampsakos','kanatlı at','Efes'] (en çok 4, tekil). */
    public static function examples(string $csv): array
    {
        $out = [];
        foreach (explode(',', $csv) as $e) {
            $e = self::cleanQuery($e);
            if ($e !== '' && !in_array($e, $out, true)) {
                $out[] = $e;
            }
        }

        return array_slice($out, 0, 4);
    }

    /** Örnek arama adresi. */
    public static function exampleUrl(string $path, string $q): string
    {
        return $path . (strpos($path, '?') === false ? '?' : '&') . 'q=' . rawurlencode($q);
    }

    /** API kökü: site içi yol ya da https adresi; değilse varsayılan. */
    public static function apiBase(string $v): string
    {
        $v = trim($v);

        return preg_match('#^(/[^/]|https://)#', $v) && !preg_match('/[\s"\'<>]/', $v) ? $v : '/api/index.php/v1/search';
    }

    /** JS yapılandırması (script içine güvenli gömülür). */
    public static function configJson(array $cfg): string
    {
        return (string) json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
