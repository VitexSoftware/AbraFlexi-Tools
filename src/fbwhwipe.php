<?php

/**
 * AbraFlexi Tools  - WebHook Wiper
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2020 Vitex Software
 */
$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

define('EASE_APPNAME', 'Wipe AbraFlexi WebHooks');
define('EASE_LOGGER', 'syslog|console');

$config_file = $argc > 1 ? $argv[1] : '/etc/abraflexi/client.json';

if (file_exists($config_file)) {
    \Ease\Shared::instanced()->loadConfig($config_file, true);

    $hooker = new \AbraFlexi\Hooks();
    $hooks = $hooker->getFlexiData();
    if (!isset($hooks['message']) && is_array($hooks) && !empty(current($hooks)) && count(current($hooks))) {
        foreach ($hooks as $hookinfo) {
            if ($hooker->unregister($hookinfo['id'])) {
                $hooker->addStatusMessage(sprintf(_('Hook %s was unregistered'),
                                $hookinfo['url']), 'success');
            } else {
                $hooker->addStatusMessage(sprintf(_('Hook %s was not unregistered'),
                                $hookinfo['url']), 'warning');
            }
        }
    }
} else {
    \Ease\Shared::instanced()->addStatusMessage(_('Cannot read %s'),
            $config_file, 'error');
}

