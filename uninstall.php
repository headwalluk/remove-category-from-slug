<?php
/**
 * Remove Category from Slug — Uninstaller
 *
 * Runs when the plugin is deleted from the Plugins screen, not on deactivation.
 * The plugin stores no settings; only the updater's cached release data is removed.
 *
 * @package Remove_Category_From_Slug
 */

namespace Remove_Category_From_Slug;

// Bail out if not invoked by WordPress's uninstall handler.
defined( 'WP_UNINSTALL_PLUGIN' ) || die();

require_once __DIR__ . '/constants.php';

delete_transient( UPDATER_CACHE_KEY );
delete_transient( UPDATER_FAILURE_CACHE_KEY );
