<?php

/**
 * all the shortcodes for the SupportHost cookie consent plugin
 */

// exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * Insert settings button/text
 */

add_shortcode( 'sh-cookie-settings', 'supporthost_cookie_consent_sh_cookie_settings' );

function supporthost_cookie_consent_sh_cookie_settings( $atts ) {

    $a = shortcode_atts( array(
        'tag' => 'button',
        'text' => 'Cookie settings',
        'classes' => '',
        ), $atts );

        if ( !in_array( $a['tag'], ['a', 'button'] ) )
            $a['tag'] = 'button';

    return '<' . $a['tag'] . ' data-cc="c-settings" class="cc-link ' . $a['classes'] . '" aria-haspopup="dialog">' . $a['text'] . '</' . $a['tag'] . '>';
}