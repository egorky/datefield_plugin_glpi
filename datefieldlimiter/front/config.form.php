<?php
require_once '../../../inc/includes.php';

Html::header(__('Date Field Limiter Configuration', 'datefieldlimiter'), $_SERVER['PHP_SELF'], 'config', 'plugins', 'datefieldlimiter');

if (!Session::haveRight('config', UPDATE)) {
    Html::displayRightError();
}

global $DB;
$plugin_name = 'datefieldlimiter';
$config_table = 'glpi_plugin_datefieldlimiter_configs';

// Load existing configuration
$loaded_config = [];
$loaded_custom_fields = []; // Store custom fields separately: $loaded_custom_fields[profile_id] = "field1
field2";

$query = "SELECT `profiles_id`, `field_name` FROM `{$config_table}`";
if ($result = $DB->query($query)) {
    $predefined_fields_list = ['planned_start_date', 'planned_end_date', 'begin_date', 'end_date'];
    while ($data = $DB->fetchAssoc($result)) {
        if (in_array($data['field_name'], $predefined_fields_list)) {
            $loaded_config[$data['profiles_id']][$data['field_name']] = true;
        } else {
            if (!isset($loaded_custom_fields[$data['profiles_id']])) {
                $loaded_custom_fields[$data['profiles_id']] = '';
            }
            $loaded_custom_fields[$data['profiles_id']] .= $data['field_name'] . "
";
        }
    }
}
// Trim trailing newline from custom fields
foreach ($loaded_custom_fields as $prof_id => $fields_text) {
    $loaded_custom_fields[$prof_id] = trim($fields_text);
}


// Handle form submission
if (isset($_POST['save_config'])) {
    if (Session::checkCSRFToken($_POST)) {
        // Clear existing config
        $DB->delete($config_table, []); // Empty WHERE clause deletes all rows

        if (isset($_POST['profile_fields']) && is_array($_POST['profile_fields'])) {
            foreach ($_POST['profile_fields'] as $profile_id => $fields) {
                $profile_id = intval($profile_id);

                // Save predefined fields
                if (isset($fields['predefined']) && is_array($fields['predefined'])) {
                    foreach ($fields['predefined'] as $field_name) {
                        if (!empty($field_name)) {
                             $DB->insert($config_table, [
                                'profiles_id' => $profile_id,
                                'field_name'  => $DB->escape(trim($field_name))
                            ]);
                        }
                    }
                }

                // Save custom fields
                if (isset($fields['custom']) && !empty($fields['custom'])) {
                    $custom_field_names = explode("
", $fields['custom']);
                    foreach ($custom_field_names as $field_name) {
                        $trimmed_field_name = trim($field_name);
                        if (!empty($trimmed_field_name)) {
                            $DB->insert($config_table, [
                                'profiles_id' => $profile_id,
                                'field_name'  => $DB->escape($trimmed_field_name)
                            ]);
                        }
                    }
                }
            }
        }
        Session::addMessageAfterRedirect(__('Configuration saved successfully.', 'datefieldlimiter'), true, INFO);
        Html::redirect(Plugin::getWebDir($plugin_name, true) . "/front/config.form.php");
    } else {
        Session::addMessageAfterRedirect(__('Invalid CSRF token. Please try again.', 'datefieldlimiter'), true, ERROR);
        Html::redirect(Plugin::getWebDir($plugin_name, true) . "/front/config.form.php");
    }
}

// Display introductory text
echo "<div class='infos_bloc_content'>";
echo "<p>" . __('Date Field Limiter Configuration', 'datefieldlimiter') . "</p>";
echo "<p>" . __('Select which date fields should be limited for each GLPI profile on the Project Task page.', 'datefieldlimiter') . "</p>";
echo "<p>" . __('The limitation makes the field read-only and is applied via JavaScript in the user's browser.', 'datefieldlimiter') . "</p>";
echo "</div>";

echo "<form name='datefieldlimiter_config' method='post' action='config.form.php'>";

// THIS IS THE LINE THAT WILL BE CHANGED IN THE NEXT STEP
$profile_obj = new Profile();
$all_profiles = $profile_obj->find([]);
$predefined_fields = ['planned_start_date', 'planned_end_date', 'begin_date', 'end_date'];

echo "<!-- DEBUG: DateFieldLimiter - Before profiles table -->";
echo "<table class='tab_cadre_fixe glpi_table'>";
echo "<thead><tr>";
echo "<th>" . __('Profile') . "</th>";
echo "<th>" . __('Predefined Fields to Restrict', 'datefieldlimiter') . "</th>";
echo "<th>" . __('Custom Field Names to Restrict (one per line)', 'datefieldlimiter') . "</th>";
echo "</tr></thead>";
echo "<tbody>";

if (is_array($all_profiles) && count($all_profiles) > 0) {
    foreach ($all_profiles as $profile) {
        $profile_id = $profile['id'];
        $profile_name = $profile['name'];

        echo "<tr>";
        echo "<td>" . htmlspecialchars($profile_name) . "</td>";

        echo "<td>";
        foreach ($predefined_fields as $field_name) {
            $is_checked = isset($loaded_config[$profile_id][$field_name]);
            echo "<label style='display: block; margin-bottom: 5px;'>";
            echo "<input type='checkbox' name='profile_fields[{$profile_id}][predefined][]' value='{$field_name}'" . ($is_checked ? " checked='checked'" : "") . "> ";
            echo htmlspecialchars($field_name);
            echo "</label>";
        }
        echo "</td>";

        echo "<td>";
        $custom_field_text = isset($loaded_custom_fields[$profile_id]) ? $loaded_custom_fields[$profile_id] : '';
        echo "<textarea name='profile_fields[{$profile_id}][custom]' rows='4' cols='50' style='width:90%;'>" . htmlspecialchars($custom_field_text) . "</textarea>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>" . __('No profiles found.') . "</td></tr>";
}

echo "</tbody>";
echo "</table>";
echo "<!-- DEBUG: DateFieldLimiter - After profiles table, before CSRF -->";

Html::displayCSRFTokenField();

echo "<div class='center padded'>";
echo "<input type='submit' name='save_config' value='" . __('Save configuration') . "' class='submit'>";
echo "</div>";

echo "</form>";
echo "<!-- DEBUG: DateFieldLimiter - After form, before footer -->";

Html::footer();
?>
