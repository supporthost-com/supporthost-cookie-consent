<?php

/**
 * all the hooks necessary for the SupportHost cookie consent plugin
 */

// exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * Adds class to html for theme selection
 */

add_filter( 'language_attributes', 'supporthost_cookie_consent_add_class', 10, 2 );

function supporthost_cookie_consent_add_class( $output, $doctype ) {
    if ( 'html' !== $doctype ) {
        return $output;
    }

    $options = get_option('supporthost-cookie-consent-appearance');

    $output .= ' class="' . $options['theme'] . '"';

    return $output;
}

/**
 * Adds manage settings in the footer
 */
add_action('wp_footer', 'supporthost_cookie_consent_add_manage_settings');
function supporthost_cookie_consent_add_manage_settings() {
    
    $appearance = get_option('supporthost-cookie-consent-appearance');

    if ( $appearance['hide_manage_consent'] == 'on' )
        return;

    $side = ( in_array( $appearance['position'], ['top left', 'middle left', 'bottom left']) ) ? 'left' : '' ;

    if( !isset( $_COOKIE['cc_cookie'] ) )
        $hidden = 'hidden';

    echo '<button type="button" data-cc="c-settings" id="c-settings" class="c-bn c_link supporthost-cookie-manage-consent ' . $hidden . $side . '">' . __('Cookie settings', 'supporthost-cookie-consent') . '</button>';

}


/**
 * Adds scripts to the head of your website if in settings
 */
add_action('wp_head', 'supporthost_cookie_consent_add_head_scripts');
function supporthost_cookie_consent_add_head_scripts(){
    echo stripslashes(base64_decode(get_option('supporthost-cookie-consent-head-scripts')));
};


/**
 * Adds scripts to the footer of your website if in settings
 */
add_action('wp_footer', 'supporthost_cookie_consent_add_footer_scripts');
function supporthost_cookie_consent_add_footer_scripts() {
    echo stripslashes(base64_decode(get_option('supporthost-cookie-consent-footer-scripts')));
}