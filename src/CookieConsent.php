<?php

namespace SupportHostCookieConsent;

use DateTime;
use JsonException;

/**
 * Class CookieConsent
 *
 * @package SupportHostCookieConsent
 * @author  kamil.pesek
 * Date: 25. 1. 2022
 */
class CookieConsent
{
    private array $languagesSettings = [];

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'checkInstallUpgrade']);

        // Register Languages
        add_action('init', function () {
            load_plugin_textdomain('supporthost-cookie-consent', false, SUPPORTHOST_COOKIE_CONSENT_BASENAME . '/languages');

            $cookieConsentSettings = new CookieConsentSettings();
            $this->languagesSettings = $cookieConsentSettings->getConsentSettings(true);
        });

        add_action('wp_enqueue_scripts', function () {
            if ($this->languagesSettings) {
                wp_enqueue_script('orestbida-cookie-consent-js', SUPPORTHOST_COOKIE_CONSENT_URL . 'assets/dist/lib/cookieconsent.js', [], 1.0, true);
                wp_enqueue_script('supporthost-cookie-consent', SUPPORTHOST_COOKIE_CONSENT_URL . 'assets/dist/supporthost_cookie_consent.min.js', [], 1.0, false);
                wp_enqueue_style('supporthost-cookie-consent', SUPPORTHOST_COOKIE_CONSENT_URL . 'assets/dist/supporthost_cookie_consent.min.css', [], 1.0);

                $inlineJs = $this->initConsentJavascript($this->languagesSettings);
                wp_add_inline_script('supporthost-cookie-consent', $inlineJs, 'before');
            }
        });

        add_action('wp_head', function () {
            if ($this->languagesSettings) {
                do_action('supporthost_cookie_consent/add_app_scripts');
            }
        });

        // make it deferred
        add_filter('script_loader_tag', static function ($tag, $handle) {
            if ($handle === 'orestbida-cookie-consent-js') {
                $tag = str_replace('></script>', ' defer></script>', $tag);
            }

            return $tag;
        }, 11, 2);

        add_action('wp_ajax_nopriv_cookies-accepted', [$this, 'cookiesAcceptedAction']);
        add_action('wp_ajax_cookies-accepted', [$this, 'cookiesAcceptedAction']);
    }


    /**
     * Cookie has been accepted
     */
    public function cookiesAcceptedAction(): void
    {
        $actionName = 'ajax-cookies-accepted';
        $nonceName = $actionName . '-nonce';

        if (!isset($_POST[$nonceName]) || !wp_verify_nonce($_POST[$nonceName], $actionName)) {
            wp_send_json(['msg' => __('Nonce is not valid')], 403);
            wp_die();
        }

        $ip = $_SERVER['HTTP_CLIENT_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $cookieData = json_decode(stripslashes(sanitize_text_field($_POST['cookie-data'])), true);

        // anonimize IP
        $ip = preg_replace(
            ['/\.\d*$/', '/[\da-f]*:[\da-f]*$/'],
            ['.***', '****:****'],
            $ip
        );
        $cookieData['ip'] = $ip;
        $cookieData['user_agent'] = $userAgent;

        $level = $cookieData['level'];
        $cookieExpiresInDays = (int) $cookieData['cookie_expires_in_days'];

        unset($cookieData['level'], $cookieData['cookie_expires_in_days']);

        global $wpdb;
        $wpdb->insert(self::getTableName(), [
            'level' => json_encode($level),
            'timestamp' => (new DateTime('now'))->format('Y-m-d_H:i:s'),
            'cookie_expires_in_days' => $cookieExpiresInDays,
            'data' => serialize($cookieData),
        ], ['%s', '%s', '%d', '%s']);

        wp_send_json([
            'msg' => 'ok',
        ], 200);
        wp_die();
    }

    /**
     * Init consent JS
     *
     * @param array $languagesSettings
     *
     * @return string
     */
    private function initConsentJavascript(array $languagesSettings): string
    {
        $appearance = get_option('supporthost-cookie-consent-appearance');
        $force_consent = ($appearance['force_consent'] == 'on') ? true : false;
        $cookieConsentOptions = [
            'autorun' => apply_filters('supporthost_cookie_consent/autorun', true),
            'autoclear_cookies' => true,
            'theme_css' => SUPPORTHOST_COOKIE_CONSENT_URL . 'assets/dist/lib/cookieconsent.css',
            'page_scripts' => true,
            'cookie_expiration' => get_option('supporthost-cookie-consent-cookie-expiration'),
            'force_consent' => $force_consent,
            'current_lang' => mb_substr(get_bloginfo('language'), 0, 2),
            'gui_options' => [
                'consent_modal' => [
                    'layout' => $appearance['layout'],               // box/cloud/bar
                    'position' => $appearance['position'],     // bottom/middle/top + left/right/center
                    'transition' => $appearance['transition']            // zoom/slide
                ],
                'settings_modal' => [
                    'layout' => $appearance['settings_layout'],                 // box/bar
                    'transition' => $appearance['settings_transition']            // zoom/slide
                ],
            ],
            'languages' => $languagesSettings,
        ];

        $cookieConsentOptions = apply_filters('supporthost_cookie_consent/javascript_options', $cookieConsentOptions);

        $inlineJs = "const cookieConsentOptions = " . json_encode($cookieConsentOptions) . ";";
        $inlineJs .= "const cookieCategoryAnalytics = '" . CookieConsentSettings::COOKIE_CATEGORY_ANALYTICS . "';";
        $inlineJs .= "const cookieCategoryMarketing = '" . CookieConsentSettings::COOKIE_CATEGORY_MARKETING . "';";
        $inlineJs .= "const cookieCategoryOther = '" . CookieConsentSettings::COOKIE_CATEGORY_OTHER . "';";
        $inlineJs .= "const cookiesAcceptedNonce = '" . wp_create_nonce('ajax-cookies-accepted') . "';";
        $inlineJs .= "const adminUrl = '" . esc_url(admin_url('admin-ajax.php')) . "';";
        $inlineJs .= "const placeholderButtonText = '" . sanitize_text_field($languagesSettings[substr(get_bloginfo('language'), 0, 2)]['iframe']['placeholder_button']) . "';";
        $inlineJs .= "const cookieName = 'accepted-cookie-consent-id';";
        if (get_option('supporthost-cookie-consent-gtm-id')) {
            $inlineJs .= "const gtmId = '" . wp_kses(get_option('supporthost-cookie-consent-gtm-id'), []) . "';";
        } else {
            $inlineJs .= "const gtmId = null;";
        }

        $inlineJs = str_replace('"path":"\/"', '"path":"/"', $inlineJs);

        return $inlineJs;
    }

    /**
     * Activation hook
     * Must be static!
     */
    public static function activate(): void
    {
        // Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
        if (version_compare(PHP_VERSION, SUPPORTHOST_COOKIE_CONSENT_MIN_PHP, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(sprintf(__('Plugin requires at least PHP version %s.', 'supporthost-cookie-consent'), SUPPORTHOST_COOKIE_CONSENT_MIN_PHP));
        }
    }

    /**
     * Deactivation hook
     * Must be static!
     */
    public static function deactivate(): void
    {
        // empty for now
    }

    /**
     *
     * @return void
     */
    public function checkInstallUpgrade(): void
    {
        if (function_exists('get_sites')) {
            $currentBlogId = get_current_blog_id();
            foreach (get_sites() as $site) {
                switch_to_blog($site->blog_id);
                $this->installUpgrade();
            }

            switch_to_blog($currentBlogId);
        } else {
            $this->installUpgrade();
        }
    }

    /**
     * Install/upgrade plugin hook
     *
     * @return void
     */
    private function installUpgrade(): void
    {
        // install v1.0.0
        if (!get_option('supporthost-cookie-consent-version')) {
            global $wpdb;
            $charsetCollate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS " . self::getTableName() . " (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    level varchar(100) NOT NULL,
                    timestamp datetime NOT NULL,
                    cookie_expires_in_days INT(4) UNSIGNED NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY (id),
                    INDEX (level)
                ) $charsetCollate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            update_option('supporthost-cookie-consent-version', '1.0');

            // Add default settings
            $data = array(
                'theme' => '',
                // consent modal
                'layout' => 'cloud',               // box/cloud/bar
                'position' => 'bottom center',     // bottom/middle/top + left/right/center
                'transition' => 'zoom',            // zoom/slide
                //settings modal
                'settings_layout' => 'box',        // box/bar
                'settings_transition' => 'zoom'    // zoom/slide
            );
            add_option('supporthost-cookie-consent-appearance', $data);
            add_option('supporthost-cookie-consent-cookie-expiration', 182);

            // import default data
            $defaultConfig = file_get_contents(SUPPORTHOST_COOKIE_CONSENT_DIR . 'config/default.json');
            try {
                $settingsToImport = json_decode($defaultConfig, true, 512, JSON_THROW_ON_ERROR);
                add_option(CookieConsentSettings::COOKIE_CONSENT_SETTINGS_KEY, $settingsToImport);
            } catch (JsonException $e) {
                add_action('admin_notices', static function () {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to import initial settings data.', 'supporthost-cookie-consent') . '</p></div>';
                });
            }
        }
    }


    /**
     * Get table name
     *
     * @return string
     */
    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'supporthost_cookie_consent';
    }

}
