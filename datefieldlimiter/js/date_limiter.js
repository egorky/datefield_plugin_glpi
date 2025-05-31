$(function() {
    function limitDateFields() {
        // Check if the global variables from PHP are defined
        if (typeof glpiPluginDateLimiterUserProfileId === 'undefined' || typeof glpiPluginDateLimiterBlockedFields === 'undefined') {
            console.warn('DateFieldLimiter: Essential configuration variables (glpiPluginDateLimiterUserProfileId or glpiPluginDateLimiterBlockedFields) are not defined. Aborting.');
            return;
        }

        if (!Array.isArray(glpiPluginDateLimiterBlockedFields) || glpiPluginDateLimiterBlockedFields.length === 0) {
            console.log('DateFieldLimiter: No fields configured for restriction for profile ID:', glpiPluginDateLimiterUserProfileId, '(or profile ID not applicable). No action taken.');
            return;
        }

        console.log('DateFieldLimiter: Applying restrictions for profile ID:', glpiPluginDateLimiterUserProfileId, 'on fields:', glpiPluginDateLimiterBlockedFields.join(', '));

        glpiPluginDateLimiterBlockedFields.forEach(function(fieldName) {
            // Construct the selector for the input field by its name attribute
            // Important: The fieldName stored in the DB must exactly match the 'name' attribute of the input field.
            const fieldSelector = 'input[name="' + fieldName + '"]';
            const $field = $(fieldSelector);

            if ($field.length > 0) {
                $field.each(function() { // Use .each() in case multiple elements somehow share the same name (though unlikely for unique form fields)
                    $(this).prop('readonly', true);
                    $(this).css('background-color', '#eee'); // Visual cue

                    console.log('DateFieldLimiter: Restricted field with name:', fieldName);

                    // Attempt to disable the associated datepicker icon (common in GLPI)
                    // This selector might need adjustment based on GLPI's specific structure for datepicker icons.
                    // It looks for common classes or elements immediately following the input.
                    $(this).nextAll('img.ui-datepicker-trigger, span.date-icon, button.date-icon, a.date-icon').first()
                           .css({'pointer-events': 'none', 'opacity': '0.5'});
                });
            } else {
                // This is not necessarily an error, as a field might not be present on all task forms
                // or could be a misconfiguration (field name doesn't exist on this page).
                // console.log('DateFieldLimiter: Field with name "' + fieldName + '" not found on this page.');
            }
        });
    }

    // Ensure the function runs on initial page load and when GLPI tabs are loaded/reloaded.
    // Added class to body to prevent multiple applications if events fire unexpectedly.

    if (!$('body').hasClass('date_limiter_applied_initial')) {
        console.log('DateFieldLimiter: Applying initial restrictions.');
        limitDateFields();
        $('body').addClass('date_limiter_applied_initial');
    } else {
        console.log('DateFieldLimiter: Initial restrictions already applied.');
    }

    // GLPI specific: re-apply if tabs are loaded (e.g., dynamic content)
    // This handles cases where parts of the form might be reloaded via AJAX within a tab.
    // The selector ".glpi_tabs" might need adjustment based on the actual GLPI version/theme.
    // If projecttask.form.php doesn't use GLPI tabs in a way that reloads date fields, this might not be strictly necessary
    // but serves as a common pattern for GLPI plugin JavaScript.
    $(document).on("tabsload", ".glpi_tabs", function(event, ui) {
        console.log('DateFieldLimiter: tabsload event triggered.');
        // We use a separate class for tab loads to allow re-application if the tab itself is reloaded.
        // The body class `date_limiter_applied_initial` ensures it doesn't run if the whole page was just loaded.
        // However, if a tab reloads content, we might need to reapply.
        // For simplicity and robustness, we can just re-run, or add a more specific tab-related flag.
        // Let's try re-running if the tab content specifically contains our form elements.
        // This check might be too generic; ideally, check if ui.panel contains the relevant form.
        if (ui && ui.panel && $(ui.panel).find('input[name^="planned_start_date"]').length > 0) {
             console.log('DateFieldLimiter: Relevant tab loaded, re-applying restrictions.');
             limitDateFields(); // Re-apply to the newly loaded tab content
        }
    });

    // More general AJAX completion handler as a fallback.
    // This attempts to catch AJAX calls that might refresh the form or parts of it.
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Check if the AJAX request URL is relevant to the project task form.
        // This is a guess; you might need to identify specific GLPI AJAX actions.
        if (settings && settings.url && (settings.url.includes('projecttask.form.php') || settings.url.includes('ajax/projecttaskform.php'))) {
            console.log('DateFieldLimiter: ajaxComplete event triggered for relevant URL:', settings.url);
            // Re-apply restrictions. It's important that limitDateFields is idempotent.
            // No need for extra class here as limitDateFields itself checks conditions.
            limitDateFields();
        }
    });
    console.log('DateFieldLimiter: Script loaded and event handlers set up.');
});
