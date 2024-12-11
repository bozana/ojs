<?php

$locales = [
    'be@cyrillic' => 'be',
    'bs' => 'bs_Latn',
    'fr_FR' => 'fr',
    'nb' => 'nb_NO',
    'pt_PT' => 'pt',
    'sr@cyrillic' => 'sr_Cyrl',
    'sr@latin' => 'sr_Latn',
    'uz@cyrillic' => 'uz',
    'uz@latin' => 'uz_Latn',
    'zh_CN' => 'zh_Hans',
];

$allFiles = [];
$result = getFiles(dirname(__FILE__), $locales);
$allFiles = array_merge($allFiles, $result);
exec("git commit -m 'locales renamed'");

function getFiles($target, $locales)
{
    clearstatcache();
    //echo 'target: ' .$target . "\n";
    $result = [];
    foreach ($locales as $oldLocale => $newLocale) {
        $useGit = false;
        foreach (glob($target . "/locale/{$oldLocale}/*.po") as $oldFilename) {
            $result[] = $oldFilename;
            $newFilename = str_replace("/locale/{$oldLocale}/", "/locale/{$newLocale}/", $oldFilename);
            if (!is_dir(dirname($newFilename))) {
                mkdir(dirname($newFilename), 0777, true);
            }

            echo 'old file name: ' . $oldFilename . "\n";
            echo 'new file name: ' . $newFilename . "\n";
            echo 'old file exists: ' . file_exists($oldFilename) . "\n";
            echo 'new folder exists: ' . is_dir(dirname($newFilename)) . "\n";

            rename($oldFilename, $newFilename);
            $useGit = true;
        }
        if ($useGit) {
            $oldDir = "{$target}/locale/{$oldLocale}/";
            $newDir = "{$target}/locale/{$newLocale}/";
            exec("git add {$newDir}*");
            exec("git rm {$oldDir}*");
        }
    }
    return $result;
}
