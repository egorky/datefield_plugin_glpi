$(function() {
    // Placeholder: Define restricted users.
    // Replace this array with actual usernames or a mechanism to fetch them.
    const restrictedUsers = ['user_A', 'user_B', 'glpi']; // Added 'glpi' for testing if current user is 'glpi'

    // Placeholder: Get current GLPI user.
    // The following is a guess. You MUST find the actual way GLPI exposes the logged-in username.
    // Inspect global JavaScript variables like `glpi` or `glpi_user` in your browser's developer console on a GLPI page.
    // For example, if GLPI sets `var glpi_username = 'current_user_login';`, then use that.
    var currentUser = 'unknown_user'; // Default
    if (typeof glpi_user !== 'undefined' && glpi_user.name) { // Example: Check for a global glpi_user object
        currentUser = glpi_user.name;
    } else if (typeof glpi !== 'undefined' && glpi.user_name) { // Example: Check for a global glpi object
        currentUser = glpi.user_name;
    } else {
        // Fallback: Try to find username from a common GLPI element if available (e.g., user dropdown)
        // This is highly dependent on GLPI version and theme.
        // THIS IS A GUESS AND MIGHT NOT WORK.
        var userNameFromDOM = $('#c_user .profile-data > div > strong').text().trim();
        if (userNameFromDOM) {
            currentUser = userNameFromDOM;
        }
        console.warn('DateFieldLimiter: Could not definitively determine current GLPI user. Using:', currentUser, '. Please verify and update user detection logic.');
    }
    console.log('DateFieldLimiter: Current user identified as:', currentUser);


    function limitDateFields() {
        console.log('DateFieldLimiter: limitDateFields called for user:', currentUser);
        if (restrictedUsers.includes(currentUser)) {
            console.log('DateFieldLimiter: User', currentUser, 'is restricted. Attempting to disable date fields.');

            // Placeholder: Identify Date Fields.
            // You MUST inspect the projecttask.form.php HTML to find the correct selectors for your date fields.
            // Use your browser's developer tools (right-click on a date field -> Inspect).
            // Common examples (uncomment and adapt what seems relevant):
            // const dateFields = $('input[type="date"]');
            // const dateFields = $('.hasDatepicker'); // If GLPI uses jQuery UI Datepicker with this class
            // const dateFields = $('input[name="date"], input[name="date_mod"], input[name="date_creation"]'); // Common names in GLPI
            const dateFields = $(
                'input[name^="planned_start_date"], input[name^="planned_end_date"],' + // GLPI 10 tasks
                'input[name="begin_date"], input[name="end_date"],' + // Older GLPI or other objects
                'input[id$="_date"], input[id^="date_"]' // General ID patterns
            );


            if (dateFields.length > 0) {
                console.log('DateFieldLimiter: Found', dateFields.length, 'date fields to restrict.');
                dateFields.each(function() {
                    $(this).prop('readonly', true);
                    $(this).css('background-color', '#eee'); // Visual cue
                    // Optionally, disable the field. Readonly is often better for UX as the date is still visible.
                    // $(this).prop('disabled', true);
                    console.log('DateFieldLimiter: Restricted field:', $(this).attr('name') || $(this).attr('id'));

                    // Attempt to disable the associated datepicker icon, if any
                    $(this).next('.date-icon, .ui-datepicker-trigger').css('pointer-events', 'none').css('opacity', '0.5');
                });
            } else {
                console.warn('DateFieldLimiter: No date fields found with the current selectors for restricted user. Check selectors.');
            }
        } else {
            console.log('DateFieldLimiter: User', currentUser, 'is not in the restricted list. No action taken.');
        }
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
