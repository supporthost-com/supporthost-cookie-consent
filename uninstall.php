<?php

// if uninstall is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN'))
    die;

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS wp_supporthost_cookie_consent" );
delete_option("supporthost-cookie-consent-version");
delete_option("supporthost-cookie-consent-appearance");
delete_option("supporthost-cookie-consent-settings");
delete_option('supporthost-cookie-consent-cookie-expiration');
delete_option('supporthost-cookie-consent-gtm-id');
delete_option('supporthost-cookie-consent-head-scripts');
delete_option('supporthost-cookie-consent-footer-scripts');