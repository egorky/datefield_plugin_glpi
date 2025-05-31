# Date Field Limiter Plugin for GLPI

## Purpose

The Date Field Limiter plugin allows GLPI administrators to prevent specific user profiles from editing certain date fields within the Project Task page (`projecttask.form.php`). This helps in enforcing data integrity rules where start/end dates or other date-related information should not be altered by certain user groups once set or at certain stages.

## Compatibility

*   **GLPI:** Version 10.0.x and later (as defined in `setup.php`).

## Installation

1.  Download the `datefieldlimiter` plugin.
2.  Place the `datefieldlimiter` directory into the `<GLPI_ROOT>/plugins/` directory on your GLPI server.
3.  Navigate to **Setup > Plugins** in GLPI.
4.  Find "Date Field Limiter" in the list, click **Install**, and then click **Enable**.

## Configuration

Once installed and enabled, you can configure the plugin:

1.  Navigate to **Setup > Plugins**.
2.  Click on the "Date Field Limiter" plugin name or its configuration link.

### Configuration Options

The configuration page allows you to specify which date fields are made read-only for which GLPI profiles:

*   **Profiles:** The page lists all user profiles currently defined in your GLPI instance.
*   **Predefined Date Fields:** For each profile, you can check boxes for commonly used date fields in project tasks:
    *   `planned_start_date`
    *   `planned_end_date`
    *   `begin_date` (Often used for actual start)
    *   `end_date` (Often used for actual end)
*   **Custom Date Fields:** If the date field you want to restrict is not in the predefined list, you can add its `name` attribute to the "Custom Fields" textarea for the respective profile (one field name per line).

### Identifying Date Field `name` Attributes

For the plugin to correctly identify which fields to make read-only, you **must** provide the exact `name` attribute of the HTML input field. Here's how to find it:

1.  Navigate to the GLPI page containing the date field you want to restrict (e.g., a Project Task form).
2.  Right-click on the specific date input field in your browser.
3.  Select **"Inspect"** or **"Inspect Element"** from the context menu. This will open your browser's developer tools, highlighting the HTML code for that field.
4.  Look for the `name="..."` part within the `<input ...>` tag. For example:
    ```html
    <input type="text" id="dpzreal_start_date_0" name="real_start_date" class="hasDatepicker">
    ```
    In this example, the value you need to enter into the plugin's "Custom Fields" configuration is `real_start_date`.

    Another example for a field that might be part of an array (common in GLPI for multiple items):
    ```html
    <input type="text" name="task[1][planned_start_date]" class="hasDatepicker">
    ```
    In this case, you would enter `task[1][planned_start_date]` into the custom fields.

**Important:** The plugin relies on an exact match of this `name` attribute. If the name is incorrect, the field will not be restricted.

## How it Works

The plugin uses JavaScript to modify the project task form on the client-side (in the user's browser). When a user belonging to a restricted profile loads the task form:
1.  The plugin checks its configuration for that user's active profile.
2.  If restrictions are defined, it finds the specified date input fields by their `name` attribute.
3.  It makes these fields `readonly` and applies a visual style (gray background) to indicate they cannot be edited.
4.  It also attempts to disable the associated calendar/datepicker pop-up icon.

This client-side restriction is for user interface convenience and guidance.

## Uninstallation

1.  Navigate to **Setup > Plugins** in GLPI.
2.  Find "Date Field Limiter" in the list.
3.  Click **Disable**, and then click **Uninstall**.
4.  The plugin's configuration table (`glpi_plugin_datefieldlimiter_configs`) will be removed from the database.
