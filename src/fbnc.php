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

$config_file = '/etc/abraflexi/client.json';

\define('BACKUP_DIRECTORY', sys_get_temp_dir().\DIRECTORY_SEPARATOR);
\define('EASE_APPNAME', 'AbraFlexi Create Company');
\define('EASE_LOGGER', 'syslog|console');

function urlToOptions($url)
{
    $optionsRaw = parse_url($url);
    $options['url'] = $optionsRaw['scheme'].'://'.$optionsRaw['host'].':'.$optionsRaw['port'];
    $options['company'] = str_replace('/c/', '', $optionsRaw['path']);
    $options['user'] = $optionsRaw['user'];
    $options['password'] = $optionsRaw['pass'];

    return $options;
}

if ($argc !== 2) {
    echo 'usage: '.$argv[0]." nova_firma_a_s_ / https://[user:password@]abraflexi.source.cz:5434/c/nova_firma_a_s_\n";
} else {
    if (strstr($argv[1], '://')) {
        $srcOptions = urlToOptions($argv[1]);
    } else {
        if (file_exists($config_file)) {
            \Ease\Shared::instanced()->loadConfig($config_file);
        } else {
            \Ease\Shared::instanced()->addStatusMessage(_('Cannot read %s'), $config_file);
        }

        $srcOptions = ['company' => $argv[1]];
    }

    $srcOptions['ignore404'] = true;
    $source = new \AbraFlexi\Company(
        $srcOptions['company'],
        $srcOptions,
    );
    $company = $source->getDataValue('nazev');

    if (null === $company) {
        $source->addStatusMessage(
            sprintf(
                _('Company %s establishing'),
                $source->getApiURL(),
            ),
            $source->createNew($srcOptions['company']) ? 'success' : 'error',
        );
    } else {
        $source->addStatusMessage(sprintf(
            _('Company %s already exists (%s) '),
            $company,
            $source->getApiURL(),
        ), 'warning');
    }
}
