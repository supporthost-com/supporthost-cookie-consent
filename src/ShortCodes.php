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

add_shortcode( 'sh-cookie-delete', 'supporthost_cookie_consent_sh_cookie_delete' );

function supporthost_cookie_consent_sh_cookie_delete( $atts ) {

    $a = shortcode_atts( array(
        'tag' => 'button',
        'text' => 'Delete all cookies',
        'classes' => '',
        ), $atts );

        if ( !in_array( $a['tag'], ['a', 'button'] ) )
            $a['tag'] = 'button';

    return '<' . $a['tag'] . ' data-cc="c-delete" class="cc-delete ' . $a['classes'] . '" aria-haspopup="dialog">' . $a['text'] . '</' . $a['tag'] . '>';
}

add_shortcode( 'sh-cookie-info', 'supporthost_cookie_consent_sh_cookie_info' );

function supporthost_cookie_consent_sh_cookie_info( $atts ) {

    $a = shortcode_atts( array(
        'title' => 'Cookie preferences',
        ), $atts );
	
	$currentLanguage = mb_substr(get_locale(), 0, 2);
	
	$options = get_option('supporthost-cookie-consent-settings');
	$texts = $options[$currentLanguage]['settings_modal']['blocks'];

    $return = '';

    foreach ( $texts as $text ) {
        if ( $text['title'] == '' ) {
            $return .= '<h2>' . $a['title'] . '</h2>';
        } else {
            $return .= '<h3>' . $text['title'] . '</h3>';
        }
        $return .= '<p>' . nl2br($text['description']) . '</p>';

        if ( isset($text['cookie_table']) && is_array($text['cookie_table']) ) {
            $return .= '<figure class="wp-block-table"><table><thead><tr><th>' . __('Name', 'supporthost-cookie-consent') . '</th><th>' . __('Domain', 'supporthost-cookie-consent') . '</th><th>' . __('Expiration', 'supporthost-cookie-consent') . '</th><th>' . __('Description', 'supporthost-cookie-consent') . '</th></tr></thead><tbody>';

            foreach ( ( $text['cookie_table']) as $row ) {
                $return .= '<tr><td>' . $row['col1'] . '</td><td>' . $row['col2'] . '</td><td>' . $row['col3'] . '</td><td>' . $row['col4'] . '</td></tr>';
            }

            $return .= '</tbody></table></figure>';
        }
    }

    return $return;
}