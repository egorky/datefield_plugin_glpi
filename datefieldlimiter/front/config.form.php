<?php

include ("../../../inc/includes.php");

$plugin_name = "datefieldlimiter";

// Check if the user has rights to update configuration
if (!Session::haveRight('config', UPDATE)) {
    Html::displayRightError();
}

$all_profiles = [];
$profile_dropdown_options = [];
// Using Profile::getAllIDs() might be cleaner if it exists and returns what's needed.
// For now, let's fetch all profiles directly.
$query = "SELECT `id`, `name` FROM `glpi_profiles` ORDER BY `name`";
$result = $DB->query($query);
if ($result && $DB->numrows($result) > 0) {
    while ($data = $DB->fetchAssoc($result)) {
        $all_profiles[$data['id']] = $data['name'];
        $profile_dropdown_options[$data['id']] = $data['name'];
    }
}

$predefined_fields = [
    'planned_start_date' => __('Planned start date', 'datefieldlimiter'),
    'planned_end_date'   => __('Planned end date', 'datefieldlimiter'),
    'begin_date'         => __('Begin date', 'datefieldlimiter'), // Often used in tickets
    'end_date'           => __('End date', 'datefieldlimiter'),   // Often used in tickets
];

$current_configs = [];
$query_configs = "SELECT `profiles_id`, `field_name` FROM `glpi_plugin_datefieldlimiter_configs`";
$result_configs = $DB->query($query_configs);
if ($result_configs && $DB->numrows($result_configs) > 0) {
    while ($data = $DB->fetchAssoc($result_configs)) {
        $current_configs[$data['profiles_id']][] = $data['field_name'];
    }
}

if (isset($_POST['save_config'])) {
    Session::checkCSRF($_POST);

    // Clear existing configurations first
    $DB->query("DELETE FROM `glpi_plugin_datefieldlimiter_configs`"); // Simple approach for now
                                                                  // In a multi-user scenario or more complex setup,
                                                                  // might need to clear per profile if editing one at a time.

    if (isset($_POST['profile_fields']) && is_array($_POST['profile_fields'])) {
        foreach ($_POST['profile_fields'] as $profile_id => $fields) {
            $profile_id = intval($profile_id);
            if ($profile_id > 0) {
                // Predefined fields
                if (isset($fields['predefined']) && is_array($fields['predefined'])) {
                    foreach ($fields['predefined'] as $field_name => $value) {
                        if ($value == '1' && array_key_exists($field_name, $predefined_fields)) {
                            $insert_data = [
                                'profiles_id' => $profile_id,
                                'field_name'  => $field_name
                            ];
                            $DB->insert('glpi_plugin_datefieldlimiter_configs', $insert_data);
                        }
                    }
                }

                // Custom fields
                if (isset($fields['custom']) && !empty(trim($fields['custom']))) {
                    $custom_field_names = array_map('trim', explode("
", trim($fields['custom'])));
                    foreach ($custom_field_names as $custom_field) {
                        if (!empty($custom_field)) {
                            $insert_data = [
                                'profiles_id' => $profile_id,
                                'field_name'  => $custom_field // Consider addslashes_deep or similar if not using prepared statements
                            ];
                             // Use $DB->insert which should handle escaping
                            $DB->insert('glpi_plugin_datefieldlimiter_configs', $DB->escapeArray($insert_data));
                        }
                    }
                }
            }
        }
    }
    // Redirect to avoid form resubmission
    Html::back();
}


Html::header(
    __('Date Field Limiter Configuration', 'datefieldlimiter'),
    $_SERVER['PHP_SELF'],
    'config',
    $plugin_name,
    'config'
);

echo "<form method='post' action='config.form.php'>";
echo "<div class='center card' style='padding:1em;'>";

echo "<h2>" . __('Date Field Limiter Configuration', 'datefieldlimiter') . "</h2>";
echo "<p>" . __('Select which date fields should be limited for each profile.', 'datefieldlimiter') . "</p>";
echo "<p>" . __('The limitation logic itself (e.g., not allowing past dates) will be applied via JavaScript.', 'datefieldlimiter') . "</p>";


if (empty($all_profiles)) {
    echo "<p class='center b'>" . __('No profiles found in GLPI.', 'datefieldlimiter') . "</p>";
} else {
    echo "<table class='tab_cadre_fixe responsive-table'>";
    echo "<thead>";
    // Note: The user's prompt mentioned line 156 for token generation,
    // but in the provided full script, it's closer to line 150 after the table.
    // The context below is the </table> and the div with the CSRF token and button.
    // This should correctly target the CSRF generation line.
    echo "<tr>";
    echo "<th>" . __('Profile') . "</th>";
    echo "<th>" . __('Predefined Fields to Limit') . "</th>";
    echo "<th>" . __('Additional Custom Field Names (one per line, input field `name` attribute)') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($all_profiles as $profile_id => $profile_name) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($profile_name, ENT_QUOTES, 'UTF-8') . "</td>";

        echo "<td>";
        $profile_custom_fields_text = "";
        $profile_predefined_selected = [];

        if (isset($current_configs[$profile_id])) {
            foreach ($current_configs[$profile_id] as $field_name) {
                if (array_key_exists($field_name, $predefined_fields)) {
                    $profile_predefined_selected[] = $field_name;
                } else {
                    $profile_custom_fields_text .= $field_name . "
";
                }
            }
        }

        foreach ($predefined_fields as $field_key => $field_label) {
            $checked = in_array($field_key, $profile_predefined_selected) ? "checked" : "";
            echo "<label style='display: block; margin-bottom: 5px;'>";
            echo "<input type='checkbox' name='profile_fields[$profile_id][predefined][$field_key]' value='1' $checked> ";
            echo htmlspecialchars($field_label, ENT_QUOTES, 'UTF-8');
            echo "</label>";
        }
        echo "</td>";

        echo "<td>";
        echo "<textarea name='profile_fields[$profile_id][custom]' rows='3' class='glpi_textarea' style='width:95%;'>" . htmlspecialchars(trim($profile_custom_fields_text), ENT_QUOTES, 'UTF-8') . "</textarea>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    echo "<div class='center' style='margin-top:20px;'>";
    // CSRF token field is now generated by Html::closeForm()
    $save_button_text = _sx('button', 'Save configuration');
    echo "<input type='submit' name='save_config' value='" . htmlspecialchars($save_button_text, ENT_QUOTES, 'UTF-8') . "' class='submit'>";
    echo "</div>";

}

echo "</div>";
Html::closeForm();

Html::footer();
?>
