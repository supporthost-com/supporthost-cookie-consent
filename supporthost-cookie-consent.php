<?php

use SupportHostCookieConsent\CookieConsent;

/**
 * The plugin bootstrap file
 *
 * Plugin Name: SupportHost cookie consent
 * Description: Plugin implements orestbida/cookieconsent cookie consent solution and stores consents in custom database table masquerading the ips.
 * Author: SupportHost OU
 * Author URI: https://supporthost.com/
 * Version: 1.0
 * Text Domain: supporthost-cookie-consent
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * License: GPLv2
 *
 * @author  Ivan Messina <ivan@supporthost.com>
 */


// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Includes necessary files
 */

if ( file_exists( __DIR__ . '/src/ShortCodes.php' ) ) {
	require __DIR__ . '/src/ShortCodes.php';
}

if ( file_exists( __DIR__ . '/src/hooks.php' ) ) {
	require __DIR__ . '/src/hooks.php';
}

/**
 * Define constants
 */
define('SUPPORTHOST_COOKIE_CONSENT_MIN_PHP', '7.4');
define('SUPPORTHOST_COOKIE_CONSENT_DIR', plugin_dir_path(__FILE__));
define('SUPPORTHOST_COOKIE_CONSENT_URL', plugin_dir_url(__FILE__));
define('SUPPORTHOST_COOKIE_CONSENT_BASENAME', basename(__DIR__));

/**
 * Autoload classes
 */
spl_autoload_register(static function ($class) {
    if (0 === strpos($class, 'SupportHostCookieConsent')) {
        require(__DIR__ . '/src/' . str_replace('SupportHostCookieConsent', '', str_replace('\\', '/', $class)) . '.php');
    }
});

/**
 * Register activation & deactivation hooks
 */
register_activation_hook(__FILE__, [CookieConsent::class, 'activate']);
register_deactivation_hook(__FILE__, [CookieConsent::class, 'deactivate']);

/**
 * Check the minimum required PHP version and run the plugin
 */
if (version_compare(PHP_VERSION, SUPPORTHOST_COOKIE_CONSENT_MIN_PHP, '>=')) {
    new CookieConsent();
} else {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(__('Plugin "SupportHost cookie consent" requires at least PHP version %s.'), SUPPORTHOST_COOKIE_CONSENT_MIN_PHP) . '</p></div>';
    });
}

/**
 * Show cookie consent programmatically
 *
 * @return void
 */
function supporthost_show_cookie_consent()
{
    ?>
    <script>
        if (typeof orestbidaCookieConsent === 'undefined') {
            window.addEventListener('orestbida-consent-loaded', function () {
                orestbidaCookieConsent.show();
            });
        } else {
            orestbidaCookieConsent.show();
        }
    </script>
    <?php
}
