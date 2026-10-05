<?php
/**
 * Plugin Constants
 *
 * @package Remove_Category_From_Slug
 * @since 1.1.0
 */

namespace Remove_Category_From_Slug;

// Exit if accessed directly.
defined( 'ABSPATH' ) || die();

/**
 * Query var carrying the path of a legacy "/category/..." request to the redirect.
 *
 * @since 1.0.0
 */
const QUERY_VAR_REDIRECT = 'category_redirect';

/**
 * Category base WordPress uses when the "category_base" option is empty.
 *
 * @since 1.0.0
 */
const DEFAULT_CATEGORY_BASE = 'category';

/**
 * GitHub updater.
 *
 * @since 1.1.0
 */
const UPDATER_GITHUB_REPO       = 'headwalluk/remove-category-from-slug';
const UPDATER_CACHE_TTL         = 12 * HOUR_IN_SECONDS;
const UPDATER_CACHE_KEY         = 'rcfs_github_release';
const UPDATER_FAILURE_CACHE_KEY = 'rcfs_github_failed';
const UPDATER_FAILURE_CACHE_TTL = HOUR_IN_SECONDS;
const UPDATER_REQUEST_TIMEOUT   = 10;
