<?php
/**
 * mod_numistr_arama saf mantık testleri (Joomla'sız): php Module/tests/arama_test.php
 */

define('_JEXEC', 1);
require __DIR__ . '/../mod_numistr_arama/helper.php';

$H = 'ModNumistrAramaHelper';
$gecti = $kaldi = 0;

function check($ad, $kosul, $ayrinti = '')
{
    global $gecti, $kaldi;
    $kosul ? $gecti++ : $kaldi++;
    echo ($kosul ? '  ok   ' : '  FAIL ') . $ad . ($kosul || $ayrinti === '' ? '' : ' -- ' . $ayrinti) . "\n";
}

// ---- dil
check('tr-TR → tr', $H::lang('tr-TR') === 'tr');
check('en-GB → en', $H::lang('en-GB') === 'en');
check('bilinmeyen → tr', $H::lang('') === 'tr');

// ---- metinler: iki dilde aynı anahtarlar, boş değer yok
$tr = $H::strings('tr');
$en = $H::strings('en');
check('TR/EN anahtarları aynı', array_keys($tr) === array_keys($en), implode(',', array_diff(array_keys($tr), array_keys($en))));
check('boş metin yok', !in_array('', $tr, true) && !in_array('', $en, true));
check('sonuç sayısı metni yok (konumlandırma)', !preg_match('/sonuç sayısı|\d+ sonuç|results? found:/iu', implode(' ', $tr) . implode(' ', $en)));
check('MÖ/MS ve BC/AD', $tr['bc'] === 'MÖ' && $tr['ad'] === 'MS' && $en['bc'] === 'BC' && $en['ad'] === 'AD');

// ---- sorgu temizleme
check('kırpma + boşluk sıkıştırma', $H::cleanQuery("  kanatlı \t  at ") === 'kanatlı at');
check('kontrol karakteri', $H::cleanQuery("efes\x00\x1F") === 'efes');
check('60 karakter', mb_strlen($H::cleanQuery(str_repeat('ş', 80)), 'UTF-8') === 60);
check('null', $H::cleanQuery(null) === '');

// ---- sonuç yolu
check('TR varsayılan', $H::resultsPath('tr', '/tr/arama', '/en/search') === '/tr/arama');
check('EN', $H::resultsPath('en', '/tr/arama', '/en/search') === '/en/search');
check('dış adres reddedilir', $H::resultsPath('tr', 'https://kotu.example/x', '') === '/tr/arama');
check('// reddedilir', $H::resultsPath('tr', '//kotu.example', '') === '/tr/arama');
check('tırnak reddedilir', $H::resultsPath('en', '', '/en/"x') === '/en/search');

// ---- örnekler
check('örnekler: virgül, tekil, boş atlanır', $H::examples('Lampsakos, kanatlı at,, Efes ,Lampsakos') === ['Lampsakos', 'kanatlı at', 'Efes']);
check('örnekler: en çok 4', count($H::examples('a1,b2,c3,d4,e5,f6')) === 4);
check('örnekler: boş', $H::examples('') === []);
check('örnek adresi kodlanır', $H::exampleUrl('/tr/arama', 'kanatlı at') === '/tr/arama?q=kanatl%C4%B1%20at');
check('örnek adresi mevcut sorguyla', $H::exampleUrl('/x?a=1', 'Efes') === '/x?a=1&q=Efes');

// ---- API kökü
check('API varsayılan', $H::apiBase('') === '/api/index.php/v1/search');
check('API site içi yol', $H::apiBase('/api/index.php/v1/search') === '/api/index.php/v1/search');
check('API https', $H::apiBase('https://www.numistr.org/api/index.php/v1/search') === 'https://www.numistr.org/api/index.php/v1/search');
check('API javascript: reddedilir', $H::apiBase('javascript:alert(1)') === '/api/index.php/v1/search');
check('API // reddedilir', $H::apiBase('//kotu.example/x') === '/api/index.php/v1/search');

// ---- JS yapılandırması script içinde güvenli
$j = $H::configJson(['q' => '</script><script>alert(1)</script>', 'a' => "O'Neil & \"x\""]);
check('configJson: </script> kaçışlı', strpos($j, '</script>') === false && strpos($j, '<') === false, $j);
check('configJson: tırnak/ampersan kaçışlı', strpos($j, "'") === false && strpos($j, '&') === false, $j);
check('configJson: Türkçe korunur', $H::configJson(['s' => 'Aranıyor']) === '{"s":"Aranıyor"}');

echo "\n$gecti passed, $kaldi failed\n";
exit($kaldi ? 1 : 0);
