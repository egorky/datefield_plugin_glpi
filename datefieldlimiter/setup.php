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

    global $DB; // Make $DB available in this function
    $PLUGIN_HOOKS['csrf_compliant']['datefieldlimiter'] = true;

    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'projecttask.form.php') !== false) {
        $user_profile_id = isset($_SESSION['glpiactiveprofile']['id']) ? intval($_SESSION['glpiactiveprofile']['id']) : null;
        $blocked_fields_for_js = [];

        if ($user_profile_id !== null && $DB) { // Ensure $DB is available
            $config_table = 'glpi_plugin_datefieldlimiter_configs';
            // Use prepared statements or ensure $user_profile_id is an integer (which intval does)
            $query = "SELECT `field_name` FROM `".$config_table."` WHERE `profiles_id` = ".$user_profile_id;

            if ($result = $DB->query($query)) {
                while ($data = $DB->fetchAssoc($result)) {
                    $blocked_fields_for_js[] = $data['field_name'];
                }
            } else {
                // Optional: Log DB error if query fails
                // Toolbox::logError("Failed to query datefieldlimiter configs: " . $DB->error());
            }
        }

        $js_code = "
        var glpiPluginDateLimiterUserProfileId = ".json_encode($user_profile_id).";
        var glpiPluginDateLimiterBlockedFields = ".json_encode($blocked_fields_for_js).";";
        Html::scriptBlock($js_code); // This will output the script block directly

        // This should come after the scriptBlock so the variables are defined first
        $PLUGIN_HOOKS['add_javascript']['datefieldlimiter'] = ['js/date_limiter.js'];
    }

    // Register the configuration page
    $PLUGIN_HOOKS['config_page']['datefieldlimiter'] = 'front/config.form.php';
}

function plugin_datefieldlimiter_install() {
    global $DB;

    $table_name = 'glpi_plugin_datefieldlimiter_configs';

    if (!$DB->tableExists($table_name)) {
        $query = "CREATE TABLE `$table_name` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `profiles_id` INT(11) NOT NULL,
            `field_name` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `profiles_id_idx` (`profiles_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if ($DB->query($query)) {
            // Table created successfully
        } else {
            // Handle error - for now, let's assume it won't fail or log it
            // PluginManager::logError("Error creating table $table_name: " . $DB->error());
            return false; // Indicate failure
        }
    }
    return true;
}

function plugin_datefieldlimiter_uninstall() {
    global $DB;

    $table_name = 'glpi_plugin_datefieldlimiter_configs';

    if ($DB->tableExists($table_name)) {
        if ($DB->query("DROP TABLE `$table_name`")) {
            // Table dropped successfully
        } else {
            // Handle error
            // PluginManager::logError("Error dropping table $table_name: " . $DB->error());
            return false; // Indicate failure
        }
    }
    return true;
}

function plugin_datefieldlimiter_check_config() {
    return true;
}

?>
