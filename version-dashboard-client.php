<?php
/*
Plugin Name: Version Dashboard Client
Plugin URI: http://www.evermade.fi
Description: Provide an API endpoint for version information dashboard.
Author: Jaakko Alajoki @jalajoki / Evermade Oy @evermadefi
Version: 1.0.0
Author URI: http://www.evermade.fi
*/

if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Register API endpoint.
 */
add_action('rest_api_init', function () {
    register_rest_route( 'versiondashboardclient/v1', '/get_version_information/', array(
        'methods' => 'GET',
        'callback' => 'vdc_api_get_version_information',
    ) );
} );



/**
 * API endpoint handler for version query.
 *
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
function vdc_api_get_version_information($data) {

	if ($data['key'] == WP_VERSION_DASHBOARD_KEY) {
		return vdc_get_version_information();
	} else {
		return array('message' => 'Invalid API key. Remember to define WP_VERSION_DASHBOARD_KEY on your wp-config.php');
	}

}

/**
 * Get list of used plugins and information if they need update or not.
 * Also get core version.
 *
 * Response array format:
 *
 * {
 * 		plugin_count: 12,
 * 		outdated_plugin_count: 4,
 * 		core_current_version: 0,
 * 		core_new_version: 0,
 * 		core_needs_update: 0,
 * 		plugins:
 *      [
 *      	{
 *      		name: "Plugin name",
 *      		slug: "Plugin slug",
 *      		needs_update: true/false,
 *      		current_version: "Version",
 *      		new_version: "New version"
 *       	}
 *      ]
 * }
 *
 * Note! This plugin uses 'update_plugins' transient which
 * is updated one in a day.
 *
 * @return Array Array of plugin information objects.
 */
//add_action( 'init', 'emvdc_get_version_information' );
function vdc_get_version_information() {

    // Get core information.
    $coreInformation = get_site_transient('update_core');

    // Get plugin update info.
    $updatePluginsTransient = get_site_transient('update_plugins');

    // Current version.
    $currentVersion = isset($coreInformation->version_checked) ? $coreInformation->version_checked : -1;

    // Response data.
    $pluginInformation = array(
        'plugin_count' => isset($updatePluginsTransient->response) ? count($updatePluginsTransient->response) + count($updatePluginsTransient->no_update) : -1,
        'outdated_plugin_count' => isset($updatePluginsTransient->no_update) ? count($updatePluginsTransient->no_update) : -1,
        'core_current_version' => isset($coreInformation->version_checked) ? $coreInformation->version_checked : -1,
        'core_new_version' => isset($coreInformation->updates) && count($coreInformation->updates) > 0 ? $coreInformation->updates[0]->version : $currentVersion,
        'core_needs_update' => isset($coreInformation->updates) && count($coreInformation->updates) > 0,
        'plugins' => [],
    );

    // Go through plugins.
    if (isset($updatePluginsTransient->response)) {

        // First add outdated plugins.
        foreach ($updatePluginsTransient->response as $filename => $details) {
            $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/' . $filename);
            array_push($pluginInformation['plugins'], array(
                'name' => $pluginData['Name'],
                'slug' => $details->slug,
                'needs_update' => true,
                'current_version' => $pluginData['Version'],
                'new_version' => $details->new_version
            ));
        }

        // Then add plugins up to date.
        foreach ($updatePluginsTransient->no_update as $filename => $details) {
            $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/' . $filename);
            array_push($pluginInformation['plugins'], array(
                'name' => $pluginData['Name'],
                'slug' => $details->slug,
                'needs_update' => false,
                'current_version' => $pluginData['Version'],
                'new_version' => $details->new_version
            ));
        }

    }

    return $pluginInformation;

}
