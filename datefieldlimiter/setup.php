<?php

define('PLUGIN_DATEFIELDLIMITER_VERSION', '0.1.0');

function plugin_version_datefieldlimiter() {
    return [
        'name'           => 'Date Field Limiter',
        'version'        => PLUGIN_DATEFIELDLIMITER_VERSION,
        'author'         => 'AI Assistant via User Request',
        'license'        => 'GLPv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
                'max' => '10.0.99',
            ],
        ],
    ];
}

function plugin_init_datefieldlimiter() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['datefieldlimiter'] = true;

    if (strpos($_SERVER['REQUEST_URI'], 'projecttask.form.php') !== false) {
        $PLUGIN_HOOKS['add_javascript']['datefieldlimiter'] = ['js/date_limiter.js'];
    }
}

function plugin_datefieldlimiter_check_config() {
    return true;
}

?>
