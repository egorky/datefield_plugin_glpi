<?php

// 1. Include GLPI Core
require_once '../../../inc/includes.php';

$plugin_name = "datefieldlimiter"; // Used for header context

// 2. Display Page Header
Html::header(
    __('Date Field Limiter Configuration', 'datefieldlimiter'),
    $_SERVER['PHP_SELF'], // Action for the header, might not be strictly necessary if form action is set
    'config',       // Parent menu
    $plugin_name,   // Plugin name for navigation context
    'config'        // Current item in navigation
);

// 3. Permission Check
if (!Session::haveRight('config', UPDATE)) {
    Html::displayRightError(); // This function handles exit
}

global $DB; // Ensure $DB is accessible

// 4. Load Existing Configuration
$plugin_configs = [];
$loaded_custom_fields = []; // Store custom fields separately: $loaded_custom_fields[profile_id] = "field1\nfield2";

// Define Predefined Fields (used for loading and display)
// Using keys for field names and values for labels for consistency, though only keys are stored/used as values here.
$predefined_field_options = [
    'planned_start_date' => __('Planned start date', 'datefieldlimiter'),
    'planned_end_date'   => __('Planned end date', 'datefieldlimiter'),
    'begin_date'         => __('Begin date (Task)', 'datefieldlimiter'), // Clarify context if needed
    'end_date'           => __('End date (Task)', 'datefieldlimiter'),   // Clarify context if needed
];
$predefined_field_keys = array_keys($predefined_field_options);

$query_configs = "SELECT `profiles_id`, `field_name` FROM `glpi_plugin_datefieldlimiter_configs`";
if ($result_configs = $DB->query($query_configs)) {
    while ($data = $DB->fetchAssoc($result_configs)) {
        if (in_array($data['field_name'], $predefined_field_keys)) {
            $plugin_configs[$data['profiles_id']][$data['field_name']] = true;
        } else {
            // Accumulate custom fields, ensuring each profile_id entry is initialized
            if (!isset($loaded_custom_fields[$data['profiles_id']])) {
                $loaded_custom_fields[$data['profiles_id']] = [];
            }
            $loaded_custom_fields[$data['profiles_id']][] = $data['field_name'];
        }
    }
}
// Convert arrays of custom fields to newline-separated strings for textareas
foreach ($loaded_custom_fields as $profile_id => $fields_array) {
    $loaded_custom_fields[$profile_id] = implode("\n", $fields_array);
}


// 5. Handle Form Submission (Save Logic)
if (isset($_POST['save_config'])) {
    // Verify CSRF token (using $_POST as Html::displayCSRFTokenField() adds it to POST)
    if (Session::checkCSRFToken($_POST)) {
        // Clear existing configurations
        // A more targeted approach might be to delete only for profiles present in the form,
        // but for a limited number of profiles/configs, this is simpler.
        $DB->query("DELETE FROM `glpi_plugin_datefieldlimiter_configs`");

        if (isset($_POST['profile_fields']) && is_array($_POST['profile_fields'])) {
            foreach ($_POST['profile_fields'] as $profile_id => $fields_data) {
                $profile_id = intval($profile_id);
                if ($profile_id <= 0) continue;

                // Save predefined fields
                if (isset($fields_data['predefined']) && is_array($fields_data['predefined'])) {
                    foreach ($fields_data['predefined'] as $field_name) {
                        // Ensure the submitted field name is actually one of the known predefined fields
                        if (in_array($field_name, $predefined_field_keys)) {
                            $insert_data = [
                                'profiles_id' => $profile_id,
                                'field_name'  => $field_name // Already validated against $predefined_field_keys
                            ];
                            $DB->insert('glpi_plugin_datefieldlimiter_configs', $DB->escapeArray($insert_data));
                        }
                    }
                }

                // Save custom fields
                if (isset($fields_data['custom']) && !empty(trim($fields_data['custom']))) {
                    $custom_field_names = array_map('trim', explode("\n", trim($fields_data['custom'])));
                    foreach ($custom_field_names as $custom_field) {
                        if (!empty($custom_field)) {
                            $insert_data = [
                                'profiles_id' => $profile_id,
                                'field_name'  => $custom_field // Should be validated/sanitized if necessary
                            ];
                            $DB->insert('glpi_plugin_datefieldlimiter_configs', $DB->escapeArray($insert_data));
                        }
                    }
                }
            }
        }
        Session::addMessageAfterRedirect(__('Configuration saved successfully.', 'datefieldlimiter'), true, INFO);
        Html::redirect(Plugin::getWebDir($plugin_name, true) . "/front/config.form.php");
    } else {
        // CSRF Check failed
        Session::addMessageAfterRedirect(__('Security token validation failed. Please try again.', 'datefieldlimiter'), true, ERROR);
        Html::redirect(Plugin::getWebDir($plugin_name, true) . "/front/config.form.php");
    }
}

// 6. Display Introductory Text
echo "<div class='center card' style='padding:1em;'>"; // Start of a card for styling
echo "<h2>" . __('Date Field Limiter Configuration', 'datefieldlimiter') . "</h2>";
echo "<p>" . __('Select which date fields should be limited for each GLPI profile on the Project Task page.', 'datefieldlimiter') . "</p>";
echo "<p>" . __('The limitation makes the field read-only and is applied via JavaScript in the user\'s browser.', 'datefieldlimiter') . "</p>";

// 7. Start HTML Form
// The action can be empty as it defaults to the current script.
echo "<form name='datefieldlimiter_config' method='post' action=''>";

// 8. Fetch GLPI Profiles
$all_profiles = Profile::getAllProfiles(); // This is a GLPI core function

if (empty($all_profiles)) {
    echo "<p class='center b'>" . __('No profiles found in GLPI or an error occurred.', 'datefieldlimiter') . "</p>";
} else {
    // 10. Create Configuration Table
    echo "<table class='tab_cadre_fixe responsive-table'>";
    echo "<thead><tr>";
    echo "<th>" . __('Profile') . "</th>";
    echo "<th>" . __('Predefined Fields to Restrict') . "</th>";
    echo "<th>" . __('Custom Field Names to Restrict (HTML `name` attribute, one per line)') . "</th>";
    echo "</tr></thead>";
    echo "<tbody>";

    foreach ($all_profiles as $profile_data) { // Profile::getAllProfiles() usually returns id, name, comment
        $profile_id = $profile_data['id'];
        $profile_name = Html::entities($profile_data['name']); // Escape profile name for display

        echo "<tr>";
        echo "<td>" . $profile_name . "</td>";

        // Predefined Fields Cell
        echo "<td>";
        foreach ($predefined_field_options as $field_key => $field_label) {
            // $plugin_configs was populated based on $predefined_field_keys
            $is_checked = isset($plugin_configs[$profile_id][$field_key]);
            echo "<label style='display: block; margin-bottom: 5px;'>";
            echo "<input type='checkbox' name='profile_fields[{$profile_id}][predefined][]' value='{$field_key}'" . ($is_checked ? " checked" : "") . "> ";
            echo Html::entities($field_label); // Display the translated label
            echo "</label>";
        }
        echo "</td>";

        // Custom Fields Cell
        echo "<td>";
        // $loaded_custom_fields was populated with profile_id => "field1\nfield2"
        $custom_field_text = $loaded_custom_fields[$profile_id] ?? '';
        echo "<textarea name='profile_fields[{$profile_id}][custom]' rows='4' class='glpi_textarea' style='width:95%; min-height:60px;'>" . htmlspecialchars($custom_field_text) . "</textarea>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    // 11. CSRF Token Field
    Html::displayCSRFTokenField();

    // 12. Save Button
    echo "<div class='center' style='margin-top:20px; padding-bottom:10px;'>"; // Added padding-bottom
    echo "<input type='submit' name='save_config' value=\"" . _sx('button', 'Save configuration') . "\" class='submit'>";
    echo "</div>";

} // End of if (empty($all_profiles)) else

// 13. End HTML Form
echo "</form>";
echo "</div>"; // End of the card

// 14. Display Page Footer
Html::footer();

?>
