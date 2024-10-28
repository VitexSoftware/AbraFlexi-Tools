<?php

declare(strict_types=1);

/**
 * This file is part of the Tools4AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-Tools
 *
 * (C) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$loaderPath = realpath(__DIR__.'/../../../autoload.php');

if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__.'/../vendor/autoload.php';
}

\define('EASE_APPNAME', 'AbraFlexi WebHook Establisher');
\define('EASE_LOGGER', 'syslog|console');

if ($argc < 2) {
    echo 'usage: '.$argv[0]." http://webhook.processor/url [xml|json] [custom/config.json] \n";
} else {
    $hookurl = $argv[1];
    $format = \array_key_exists(2, $argv) ? $argv[2] : 'json';
    $config = \array_key_exists(3, $argv) ? $argv[3] : '/etc/abraflexi/client.json';

    if (file_exists($config)) {
        \Ease\Shared::instanced()->loadConfig($config, true);
    }

    $changer = new \AbraFlexi\Changes();
    $changer->logBanner();

    if (!$changer->getStatus()) {
        $changer->enable();
    }

    if (\strlen($hookurl)) {
        $hooker = new \AbraFlexi\Hooks();
        $hooker->setDataValue('skipUrlTest', 'true');
        //        $hooker->setDataValue('skipUrlTest', 'false');
        //        $hooker->setDataValue('lastVersion', $lastversion);
        //        $hooker->setDataValue('secKey', $secKey);

        $hookResult = $hooker->register($hookurl, $format);

        if ($hookResult) {
            $hooker->addStatusMessage(sprintf(
                _('Hook %s was registered'),
                $hookurl,
            ), 'success');
            $hookurl = '';
        } else {
            $hooker->addStatusMessage(sprintf(
                _('Hook %s not registered'),
                $hookurl,
            ), 'warning');
        }
    }
}
