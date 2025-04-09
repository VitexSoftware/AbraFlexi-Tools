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

require \dirname(__DIR__).'/vendor/autoload.php';

\define('EASE_APPNAME', 'Wipe AbraFlexi WebHooks');
\define('EASE_LOGGER', 'syslog|console');

$config_file = $argc > 1 ? $argv[1] : '/etc/abraflexi/client.json';

if (file_exists($config_file)) {
    \Ease\Shared::singleton()->loadConfig($config_file, true);
}

$hooker = new \AbraFlexi\Hooks();

try {
    $hooks = $hooker->getFlexiData();

    if (!isset($hooks['message']) && \is_array($hooks) && !empty(current($hooks)) && \count(current($hooks))) {
        foreach ($hooks as $hookinfo) {
            if ($hooker->unregister($hookinfo['id'])) {
                $hooker->addStatusMessage(sprintf(
                    _('Hook %s was unregistered'),
                    $hookinfo['url'],
                ), 'success');
            } else {
                $hooker->addStatusMessage(sprintf(
                    _('Hook %s was not unregistered'),
                    $hookinfo['url'],
                ), 'warning');
            }
        }
    }
} catch (\AbraFlexi\Exception $exc) {
}
