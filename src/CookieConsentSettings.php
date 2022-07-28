<?php

namespace SupportHostCookieConsent;

use JsonException;

/**
 * Class CookieConsentSettings
 *
 * @package SupportHostCookieConsent
 * @author  kamil.pesek
 * Date: 25. 1. 2022
 */
class CookieConsentSettings
{

    public const COOKIE_CATEGORY_MARKETING = 'marketing';
    public const COOKIE_CATEGORY_ANALYTICS = 'analytics';
    public const COOKIE_CATEGORY_OTHER = 'other';
    public const COOKIE_CONSENT_SETTINGS_KEY = 'supporthost-cookie-consent-settings';

    /**
     * @var array
     */
    private array $savedSettings;

    /**
     * @var array
     */
    private array $availableLanguages;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', static function () {
            wp_enqueue_style('supporthost-cookie-consent-admin', SUPPORTHOST_COOKIE_CONSENT_URL . 'assets/dist/supporthost_cookie_consent_admin.min.css', [], 1.0);
        });

        add_filter('plugin_action_links_supporthost-cookie-consent/supporthost-cookie-consent.php', static function ($links) {
            $links[] = '<a href="' . admin_url('options-general.php?page=supporthost-cookie-consent-settings') . '">' . __('Settings') . '</a>';
            return $links;
        });

        add_action('admin_menu', function () {
            $tab = sanitize_text_field($_GET['tab'] ?? 'texts');
            $optionsCapability = 'manage_options';
            $optionsCapability = apply_filters('supporthost_cookie_consent/options_capability', $optionsCapability);

            add_options_page(__('Cookie consent', 'supporthost-cookie-consent'), __('SupportHost Cookie consent', 'supporthost-cookie-consent'), $optionsCapability, 'supporthost-cookie-consent-settings', function () use ($tab) {
                ?>
                <nav class="nav-tab-wrapper">
                    <a href="<?php echo admin_url('options-general.php?page=supporthost-cookie-consent-settings'); ?>&amp;tab=texts"
                       class="nav-tab <?php if ($tab === 'texts'): ?>nav-tab-active<?php endif; ?>"><?php _e('Texts', 'supporthost-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=supporthost-cookie-consent-settings'); ?>&amp;tab=appearence"
                       class="nav-tab <?php if ($tab === 'appearence'): ?>nav-tab-active<?php endif; ?>"><?php _e('Appearance', 'supporthost-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=supporthost-cookie-consent-settings'); ?>&amp;tab=settings"
                       class="nav-tab <?php if ($tab === 'settings'): ?>nav-tab-active<?php endif; ?>"><?php _e('Settings', 'supporthost-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=supporthost-cookie-consent-settings'); ?>&amp;tab=consent_report"
                       class="nav-tab <?php if ($tab === 'consent_report'): ?>nav-tab-active<?php endif; ?>"><?php _e('Consent report', 'supporthost-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=supporthost-cookie-consent-settings'); ?>&amp;tab=statistics"
                       class="nav-tab <?php if ($tab === 'statistics'): ?>nav-tab-active<?php endif; ?>"><?php _e('Statistics', 'supporthost-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=supporthost-cookie-consent-settings'); ?>&amp;tab=import_export"
                       class="nav-tab <?php if ($tab === 'import_export'): ?>nav-tab-active<?php endif; ?>"><?php _e('Import/export', 'supporthost-cookie-consent'); ?>
                    </a>
                </nav>
                <div class="wrap">
                    <?php
                    if ('texts' === $tab) {
                        $this->tabTexts();
                    }

                    if ('appearence' === $tab) {
                        $this->tabAppearance();
                    }

                    if ('settings' === $tab) {
                        $this->tabSettings();
                    }

                    if ('consent_report' === $tab) {
                        $this->tabConsentReport();
                    }

                    if ('statistics' === $tab) {
                        $this->tabStatistics();
                    }

                    if ('import_export' === $tab) {
                        $this->tabImportExport();
                    }
                    ?>
                </div>
                <?php
                //todo design, domain ...
            });
        });

        // Export cookie consent settings form handling
        add_action('admin_post_cookie_settings_export', [$this, 'handleExportForm']);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     *
     * @return void
     */
    private function printFieldRow(string $name, string $label, string $type = 'text'): void
    {
        foreach ($this->availableLanguages as $language):
            $fieldName = $language . $name;
            $id = str_replace([']', '['], ['', '-'], $fieldName);

            //get array indexes from field name - [consent_modal][title] => consent_modal::title
            $settingsIndexParts = explode('::', str_replace(['][', '[', ']'], ['::', '', ''], $name));
            $fieldValue = $this->savedSettings[$language] ?? null;
            foreach ($settingsIndexParts as $settingsIndexPart) {
                $fieldValue = $fieldValue[$settingsIndexPart] ?? null;
            }

            if (!is_array($fieldValue)) {
                $fieldValue = htmlspecialchars($fieldValue);
            }
            ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo esc_attr($id) ?>"><?php echo esc_html($label) ?> - <?php echo esc_html($language) ?></label>
                </th>
                <td>
                    <?php if ('text' === $type): ?>
                        <input type="text" value="<?php echo esc_attr($fieldValue) ?>" class="regular-text" name="<?php echo esc_attr($fieldName) ?>" id="<?php echo esc_attr($id) ?>">
                    <?php elseif ('textarea' === $type): ?>
                        <textarea rows="5" style="max-width: 700px;width: 100%;" name="<?php echo esc_attr($fieldName) ?>" id="<?php echo esc_attr($id) ?>"><?php echo esc_html($fieldValue) ?></textarea>
                    <?php else: ?>
                        <?php $fieldValue = $fieldValue ?: [['col1' => '', 'col2' => '', 'col3' => '', 'col4' => '']]; ?>
                        <button type="button" onclick="addCookieTableRow(this)" style="margin-bottom: 10px;"><?php _e('Add row', 'supporthost-cookie-consent') ?></button>
                        <table class="field-collection">
                            <tr>
                                <th><?php _e('Name', 'supporthost-cookie-consent'); ?></th>
                                <th><?php _e('Domain', 'supporthost-cookie-consent'); ?></th>
                                <th><?php _e('Expiration', 'supporthost-cookie-consent'); ?></th>
                                <th><?php _e('Description', 'supporthost-cookie-consent'); ?></th>
                                <th><?php _e('Is regex?', 'supporthost-cookie-consent'); ?></th>
                                <th></th>
                            </tr>
                            <?php foreach ($fieldValue as $rowIdx => $rowValues):
                                $rowValues = array_map('htmlspecialchars', $rowValues);
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" value="<?php echo esc_attr($rowValues['col1']) ?>" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col1]"
                                               id="<?php echo esc_attr($id) ?>">
                                    </td>
                                    <td>
                                        <input type="text" value="<?php echo esc_attr($rowValues['col2']) ?>" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col2]" aria-label="false">
                                    </td>
                                    <td>
                                        <input type="text" value="<?php echo esc_attr($rowValues['col3']) ?>" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col3]" aria-label="false">
                                    </td>
                                    <td style="padding-right: 15px;">
                                        <textarea class="" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col4]" rows="3"
                                        aria-label="false"><?php echo esc_html($rowValues['col4']) ?></textarea>
                                    </td>
                                    <td>
                                        <select name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][is_regex]">
                                            <option value="" <?php if(esc_attr($rowValues['is_regex']) == false) echo 'selected' ?>><?php _e('', 'supporthost-cookie-consent'); ?></option>
                                            <option value="true" <?php if(esc_attr($rowValues['is_regex']) == true) echo 'selected' ?>><?php _e('Yes', 'supporthost-cookie-consent'); ?></option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" onclick="removeCookieTableRow(this)"><?php _e('Remove row', 'supporthost-cookie-consent') ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php
    }

    /**
     * @return void
     */
    private function tabAppearance(): void
    {
        $this->handleAppearanceForm();
        $appearance = get_option('supporthost-cookie-consent-appearance');
        ?>
        <form action="" method="POST">
            <h2>
                <label for="app-scripts"><?php _e('Appearance', 'supporthost-cookie-consent'); ?></label>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="theme"><?php _e('Theme selection', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="theme" id="theme">
                            <option value="" <?php selected( '', $appearance['theme'] ) ?>>Default</option>
                            <option value="c_darkmode" <?php selected( 'c_darkmode', $appearance['theme'] ) ?>>Dark</option>
                            <option value="theme_funky" <?php selected( 'theme_funky', $appearance['theme'] ) ?>>Funky</option>
                            <option value="theme_turquoise" <?php selected( 'theme_turquoise', $appearance['theme'] ) ?>>Turquoise</option>
                            <option value="theme_supporthost" <?php selected( 'theme_supporthost', $appearance['theme'] ) ?>>SupportHost</option>
                        </select>
                        <p><?php _e('Select the theme for the cookie bar and cookie setting modal.', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="layout"><?php _e('Layout style', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="layout" id="layout">
                            <option value="cloud" <?php selected( 'cloud', $appearance['layout'] ) ?>>Cloud</option>
                            <option value="box" <?php selected( 'box', $appearance['layout'] ) ?>>Box</option>
                            <option value="bar" <?php selected( 'bar', $appearance['layout'] ) ?>>Bar</option>
                        </select>
                        <p><?php _e('Select the layout for the cookie bar.', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="position"><?php _e('Position', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="position" id="position">
                            <option value="bottom left" <?php selected( 'bottom left', $appearance['position'] ) ?>>Bottom Left</option>
                            <option value="bottom center" <?php selected( 'bottom center', $appearance['position'] ) ?>>Bottom Center</option>
                            <option value="bottom right" <?php selected( 'bottom right', $appearance['position'] ) ?>>Bottom Right</option>
                            <option value="middle left" <?php selected( 'middle left', $appearance['position'] ) ?>>Middle Left</option>
                            <option value="middle center" <?php selected( 'middle center', $appearance['position'] ) ?>>Middle Center</option>
                            <option value="middle right" <?php selected( 'middle right', $appearance['position'] ) ?>>Middle Right</option>
                            <option value="top left" <?php selected( 'top left', $appearance['position'] ) ?>>Top Left</option>
                            <option value="top center" <?php selected( 'top center', $appearance['position'] ) ?>>Top Center</option>
                            <option value="top right" <?php selected( 'top right', $appearance['position'] ) ?>>Top Right</option>
                        </select>
                        <p><?php _e('Select the position for the cookie bar.', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="transition"><?php _e('Transition style', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="transition" id="transition">
                            <option value="zoom" <?php selected( 'zoom', $appearance['transition'] ) ?>>Zoom</option>
                            <option value="slide" <?php selected( 'slide', $appearance['transition'] ) ?>>Slide</option>
                        </select>
                        <p><?php _e('Select the transition style for the cookie bar.', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="settings_layout"><?php _e('Transition style', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="settings_layout" id="settings_layout">
                            <option value="box" <?php selected( 'box', $appearance['settings_layout'] ) ?>>Box</option>
                            <option value="bar" <?php selected( 'bar', $appearance['settings_layout'] ) ?>>Bar</option>
                        </select>
                        <p><?php _e('Select the layout for the setting\'s dialog.', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="settings_transition"><?php _e('Transition style', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="settings_transition" id="settings_transition">
                            <option value="zoom" <?php selected( 'zoom', $appearance['settings_transition'] ) ?>>Zoom</option>
                            <option value="slide" <?php selected( 'slide', $appearance['settings_transition'] ) ?>>Slide</option>
                        </select>
                        <p><?php _e('Select the transition style for the setting\'s dialog', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="force_consent"><?php _e('Force consent', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="force_consent" <?php checked( 'on', $appearance['force_consent'] ) ?>>
                        <p><?php _e('If checked the user won\'t be able to interact with your website until he accepts or rejects cookies.', 'supporthost-cookie-consent') ?></p>
                    </td>
                </tr>
            </table>
            <input type="submit" name="save-settings" class="button button-primary" value="<?php _e('Save', 'supporthost-cookie-consent') ?>">
        </form>
        <?php
    }

    /**
     * @return void
     */
    private function tabTexts(): void
    {
        $this->savedSettings = $this->getConsentSettings();
        $this->handleTextsForm();
        ?>
        <script>
            function addCookieTableRow(button) {
                let table = button.closest('td').querySelector('table');
                let firstDiv = table.querySelector('tr:nth-child(2)');
                let clonedDiv = firstDiv.cloneNode(true);
                clonedDiv.querySelectorAll('input, textarea').forEach(element => element.value = '');

                let lastDiv = table.querySelector('tr:last-child');
                lastDiv.after(clonedDiv);

                reindexCookieTableRows(table);
            }

            function removeCookieTableRow(button) {
                let rows = button.closest('table').querySelectorAll('tr');
                if (rows.length > 2) { // +1 for header
                    let table = button.closest('table');
                    button.closest('tr').remove();
                    reindexCookieTableRows(table);
                }
            }

            function reindexCookieTableRows(table) {
                // reindex fields
                table.querySelectorAll('tr').forEach(function (element, index) {
                    element.querySelectorAll('input, textarea').forEach(function (input) {
                        let nameParts = input.name.split('][')
                        let rowIndex = nameParts[nameParts.length - 2];
                        input.name = input.name.replace('[' + rowIndex + ']', '[' + (index - 1) + ']')
                    });
                });
            }
        </script>
        <form action="" method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="status"><?php _e('Status', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="disabled" <?php if (($this->savedSettings['status'] ?? '') === 'disabled'): ?>selected<?php endif; ?>>
                                <?php _e('Disabled', 'supporthost-cookie-consent') ?>
                            </option>
                            <option value="enabled" <?php if (($this->savedSettings['status'] ?? '') === 'enabled'): ?>selected<?php endif; ?>>
                                <?php _e('Enabled', 'supporthost-cookie-consent') ?>
                            </option>
                            <option value="only_admins" <?php if (($this->savedSettings['status'] ?? '') === 'only_admins'): ?>selected<?php endif; ?>>
                                <?php _e('Show only for admins', 'supporthost-cookie-consent') ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
            <hr>
            <h2><?php _e('Consent modal', 'supporthost-cookie-consent'); ?></h2>
            <table class="form-table">
                <?php $this->printFieldRow('[consent_modal][title]', __('Header', 'supporthost-cookie-consent')) ?>
                <?php $this->printFieldRow('[consent_modal][description]', __('Description', 'supporthost-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[consent_modal][primary_btn][text]', __('Accept all button', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[consent_modal][secondary_btn][text]', __('Reject all button', 'supporthost-cookie-consent')); ?>
            </table>
            <hr>
            <h2><?php _e('Settings modal', 'supporthost-cookie-consent'); ?></h2>
            <table class="form-table">
                <?php $this->printFieldRow('[settings_modal][title]', __('Header', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][save_settings_btn]', __('Save settings button', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][accept_all_btn]', __('Accept all button', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][reject_all_btn]', __('Reject all button', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][close_btn_label]', __('Close button', 'supporthost-cookie-consent')); ?>

                <?php $this->printFieldHeader(__('Info block', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][0][title]', __('Header', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][0][description]', __('Description', 'supporthost-cookie-consent'), 'textarea'); ?>

                <?php $this->printFieldHeader(__('Necessary cookies', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][1][title]', __('Header', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][1][description]', __('Description', 'supporthost-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][1][cookie_table]', __('Cookie table', 'supporthost-cookie-consent'), 'cookietable'); ?>

                <?php $this->printFieldHeader(__('Analytics cookies', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][2][title]', __('Header', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][2][description]', __('Description', 'supporthost-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][2][cookie_table]', __('Cookie table', 'supporthost-cookie-consent'), 'cookietable'); ?>

                <?php $this->printFieldHeader(__('Marketing cookies', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][3][title]', __('Header', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][3][description]', __('Description', 'supporthost-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][3][cookie_table]', __('Cookie table', 'supporthost-cookie-consent'), 'cookietable'); ?>

                <?php $this->printFieldHeader(__('Other cookies', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][4][title]', __('Header', 'supporthost-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][4][description]', __('Description', 'supporthost-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][4][cookie_table]', __('Cookie table', 'supporthost-cookie-consent'), 'cookietable'); ?>
            </table>
            <hr>
            <h2><?php _e('Iframe placeholder', 'supporthost-cookie-consent'); ?></h2>
            <table class="form-table">
                <?php $this->printFieldRow('[iframe][placeholder_button]', __('Placeholder', 'supporthost-cookie-consent')); ?>
            </table>
            <input type="submit" name="save-settings" class="button button-primary" value="<?php _e('Save', 'supporthost-cookie-consent') ?>">
        </form>
        <?php
    }

    /**
     * @return void
     */
    private function tabSettings(): void
    {
        $this->handleJavascriptsForm();
        ?>
            
        <form action="" method="POST">
            <h2>
                <label for="app-scripts"><?php _e('Options', 'supporthost-cookie-consent'); ?></label>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cookie_expiration"><?php _e('Cookie expiration', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <input type="text" name="cookie_expiration" id="cookie_expiration" value="<?php echo esc_attr(get_option('supporthost-cookie-consent-cookie-expiration')); ?>">
                        <br>
                        <small>
                            <?php _e('Insert the number of days you want the selection cookie to be valid.', 'supporthost-cookie-consent') ?>
                        </small>
                    </td>
                </tr>
            </table>

            <h2>
                <label for="app-scripts"><?php _e('Scripts', 'supporthost-cookie-consent'); ?></label>
            </h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gtm-id"><?php _e('Google Tag Manager ID', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        GTM-<input type="text" name="gtm_id" id="gtm-id" value="<?php echo esc_attr(get_option('supporthost-cookie-consent-gtm-id')); ?>">
                        <br>
                        <small>
                            <?php _e('Google Tag Manager will be added automatically with default analytics_storage=denied & ad_storage=denied ', 'supporthost-cookie-consent') ?>
                        </small>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="head_scripts"><?php _e('Head scripts', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <textarea name="head_scripts" id="head_scripts" rows="5" style="max-width: 700px;width: 100%;"><?php echo stripslashes(base64_decode(esc_attr(get_option('supporthost-cookie-consent-head-scripts')))); ?></textarea>
                        <br>
                        <small>
                            <?php _e('Add here the javascript code you want to add in the head of your website. Take a look at the guidelines below to block the javascript until the user accepts the cookies.', 'supporthost-cookie-consent') ?>
                        </small>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="footer_scripts"><?php _e('Footer scripts', 'supporthost-cookie-consent') ?></label>
                    </th>
                    <td>
                        <textarea name="footer_scripts" id="footer_scripts" rows="5" style="max-width: 700px;width: 100%;"><?php echo stripslashes(base64_decode(esc_attr(get_option('supporthost-cookie-consent-footer-scripts')))); ?></textarea>
                        <br>
                        <small>
                            <?php _e('Add here the javascript code you want to add in the footer of your website. Take a look at the guidelines below to block the javascript until the user accepts the cookies.', 'supporthost-cookie-consent') ?>
                        </small>
                    </td>
                </tr>
            </table>
            <input type="submit" name="save-settings" class="button button-primary" value="<?php _e('Save', 'supporthost-cookie-consent') ?>">
        </form>

        <p><?php _e('NOTE: You need to add a type and a category to your &lt;script&gt; tags as shown below. Once the user accepts the cookies for a category the script will be automatically activated and run.', 'supporthost-cookie-consent') ?></p>

        <pre style="background: #f0f0f1; background: rgba(0,0,0,.07);">
            <code style="background: transparent;white-space: pre-line;">
                &lt;script type="text/script-template" data-cookiecategory="<?php echo self::COOKIE_CATEGORY_ANALYTICS; ?>"&gt;
                ...
                &lt;/script&gt;

                &lt;script type="text/script-template" data-cookiecategory="<?php echo self::COOKIE_CATEGORY_MARKETING; ?>"&gt;
                ...
                &lt;/script&gt;

                &lt;script type="text/script-template" data-cookiecategory="<?php echo self::COOKIE_CATEGORY_OTHER; ?>"&gt;
                ...
                &lt;/script&gt;
            </code>
        </pre>

        <?php
    }

    /**
     * @return void
     */
    private function tabConsentReport(): void
    {
        $this->handleConsentReportForm();

        global $wpdb;

        $cookieconsent = new CookieConsent;
        $tablename = $cookieconsent->getTableName();

        $customPagHTML      = "";
        $query              = "SELECT * FROM " . $tablename . "";
        $total_query        = "SELECT COUNT(1) FROM (${query}) AS combined_table";
        $total              = $wpdb->get_var( $total_query );
        $items_per_page     = 50;
        $page               = isset( $_GET['paged'] ) ? abs( (int) $_GET['paged'] ) : 1;
        $offset             = ( $page * $items_per_page ) - $items_per_page;
        $result             = $wpdb->get_results( $query . " ORDER BY id DESC LIMIT ${offset}, ${items_per_page}" );
        $totalPage          = ceil($total / $items_per_page);
        
        if($totalPage > 1){
            $customPagHTML     =  '<div><span>Page '.$page.' of '.$totalPage.'</span> <br /> '.paginate_links( array(
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $totalPage,
                'current' => $page
            )).'</div>';
        }

        ?>
            <form method="POST" action="">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'supporthost-cookie-consent'); ?></label>
                            <select name="action" id="bulk-action-selector-top">
                                <option value="-1"><?php _e('Bulk actions', 'supporthost-cookie-consent'); ?></option>
                                <option value="bulk-delete"><?php _e('Delete', 'supporthost-cookie-consent'); ?></option>
                            </select>
                            <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'supporthost-cookie-consent'); ?>">
                    </div>
                    <div class="tablenav-pages">
                        <span><?php _e('Total consents:', 'supporthost-cookie-consent'); ?> </span><?php echo $total ?><br />
                        <?php echo $customPagHTML ?>
                    </div>
                    <br class="clear">
                </div>
                <table class="wp-list-table widefat fixed striped table-view-list cookielawinfo_page_cli_visitor_report">
                    <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select all', 'supporthost-cookie-consent'); ?></label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" id="visitor_ip" class="manage-column column-visitor_ip column-primary"><?php _e('IP Address', 'supporthost-cookie-consent'); ?></th>
                        <th scope="col" id="visitor_date" class="manage-column column-visitor_date"><?php _e('Visited date', 'supporthost-cookie-consent'); ?></th>
                        <th scope="col" id="visitor_cookie" class="manage-column column-visitor_cookie"><?php _e('cookie details', 'supporthost-cookie-consent'); ?></th>
                        <th scope="col" id="user_id" class="manage-column column-user_id"><?php _e('User ID', 'supporthost-cookie-consent'); ?></th>
                    </tr>
                    </thead>

                    <tbody id="the-list">
                        <?php foreach ( $result as $ip ) {
                            $ipdata = unserialize($ip->data);
                            $cookies = json_decode( $ip->level );
                        ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="bulk-delete[]" value="<?php echo $ip->id ?>"></th>
                                <td class="visitor_ip column-visitor_ip has-row-actions column-primary"><strong><?php echo $ipdata['ip'] ?></strong></td>
                                <td class="visitor_date column-visitor_date"><strong><?php echo $ip->timestamp ?></strong></td>
                                <td class="visitor_cookie column-visitor_cookie">
                                    <table>
                                        <tbody>
                                            <?php foreach ( $cookies as $cookie ) { ?>
                                                <tr>
                                                    <td class="cli-report-td"> <?php echo $cookie ?></td>
                                                    <td class="cli-report-td"> : </td>
                                                    <td class="cli-report-td"><b>yes</b></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </td>
                                <td class="user_id column-user_id"><strong><?php echo $ipdata['cookie_id'] ?></strong></td>
                            </tr>
                        <?php } ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select all', 'supporthost-cookie-consent'); ?></label>
                                <input id="cb-select-all-2" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-visitor_ip column-primary"><?php _e('IP Address', 'supporthost-cookie-consent'); ?></th>
                            <th scope="col" class="manage-column column-visitor_date"><?php _e('Visited date', 'supporthost-cookie-consent'); ?></th>
                            <th scope="col" class="manage-column column-visitor_cookie"><?php _e('cookie details', 'supporthost-cookie-consent'); ?></th>
                            <th scope="col" class="manage-column column-user_id"><?php _e('User ID', 'supporthost-cookie-consent'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </form>
        <?php

    }

    /**
     * @return void
     */
    private function tabStatistics(): void
    {
        global $wpdb;
        $consents = $wpdb->get_results('SELECT level FROM ' . CookieConsent::getTableName());
        $countsByCategory = [];
        $totalConsents = count($consents);
        foreach ($consents as $consent) {
            foreach (json_decode($consent->level, true) as $level) {
                $countsByCategory[$level] = isset($countsByCategory[$level]) ? $countsByCategory[$level] + 1 : 1;
            }
        }

        if (0 === count($countsByCategory)) {
            ?><?php _e('No data', 'supporthost-cookie-consent'); ?><?php
        }

        foreach ($countsByCategory as $categoryName => $consentsCount) {
            $percents = round(($consentsCount / $totalConsents) * 100);
            $label = __('Necessary', 'supporthost-cookie-consent');
            if ('analytics' === $categoryName) {
                $label = __('Analytics', 'supporthost-cookie-consent');
            } elseif ('marketing' === $categoryName) {
                $label = __('Marketing', 'supporthost-cookie-consent');
            } elseif ('other' === $categoryName) {
                $label = __('Other', 'supporthost-cookie-consent');
            }
            ?>
            <div style="width:33%; min-width: 200px; float: left; text-align:center;">
                <div class="pie" style="--p:<?php echo esc_html($percents); ?>;"><?php echo esc_html($percents); ?>%</div>
                <h2><?php echo esc_html($label); ?> - <?php echo esc_html($consentsCount); ?>/<?php echo esc_html($totalConsents); ?></h2>
            </div>
            <?php
        }
    }

    /**
     * Tab import/export
     *
     * @return void
     * @throws JsonException
     */
    private function tabImportExport(): void
    {
        $this->handleImportForm();
        ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <h2>
                <label for="app-scripts"><?php _e('Import settings', 'supporthost-cookie-consent'); ?></label>
            </h2>
            <input type="file" name="import-setting-file" accept="application/json" required>
            <input type="submit" name="import-settings" class="button button-primary" value="<?php _e('Import', 'supporthost-cookie-consent') ?>">
        </form>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
            <h2>
                <label for="app-scripts"><?php _e('Export settings', 'supporthost-cookie-consent'); ?></label>
            </h2>
            <input type="hidden" name="action" value="cookie_settings_export">
            <input type="submit" name="export-settings" class="button button-primary" value="<?php _e('Export', 'supporthost-cookie-consent') ?>">
        </form>
        <?php
    }

    /**
     * Import cookie consent settings
     *
     * @return void
     * @throws JsonException
     */
    private function handleImportForm(): void
    {
        if (isset($_POST['import-settings'], $_FILES['import-setting-file'])) {
            $fileContent = file_get_contents($_FILES['import-setting-file']['tmp_name']);
            try {
                $settingsToImport = json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);

                if (update_option(self::COOKIE_CONSENT_SETTINGS_KEY, $settingsToImport)) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings imported successfully.', 'supporthost-cookie-consent') . '</p></div>';
                } else {
                    $error = __('Settings import failed or imported data is the same.', 'supporthost-cookie-consent');
                }
            } catch (JsonException $e) {
                $error = __('Uploaded file is not a valid JSON file.', 'supporthost-cookie-consent');
            }

            if (isset($error)) {
                echo '<div class="notice notice-error is-dismissible"><p>' . $error . '</p></div>';
            }
        }
    }

    /**
     * Export cookie consent settings
     *
     * @return void
     * @throws JsonException
     */
    public function handleExportForm(): void
    {
        if (isset($_POST['export-settings'])) {
            $settings = $this->getConsentSettings();
            $data = json_encode($settings, JSON_THROW_ON_ERROR);
            header('Content-disposition: attachment; filename=cookie-consent-settings.json');
            header('Content-type: application/json');
            echo $data;
            exit;
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    private function printFieldHeader(string $text): void
    {
        ?>
        <tr>
            <th colspan="2"><h3><?php echo esc_html($text); ?></h3></th>
        </tr>
        <?php
    }

    /**
     * @return void
     */
    private function handleAppearanceForm(): void
    {
        if (isset($_POST['save-settings'])) {
            $options = get_option('supporthost-cookie-consent-appearance');

            // reset checkboxes
            $options['force_consent'] = '';

            foreach ( $_POST as $key => $value) {
                if ( $key == 'save-settings' )
                    continue;
                $options[$key] = sanitize_text_field($_POST[$key]);
            }

            update_option('supporthost-cookie-consent-appearance', $options);
        }
    }

    /**
     * @return void
     */
    private function handleTextsForm(): void
    {
        if (isset($_POST['save-settings'])) {
            $data = $_POST;
            unset($data['save-settings']);

            $allowedAttributes = [
                'class' => [],
                'id' => [],
                'style' => [],
                'role' => [],
                'aria-live' => [],
                'aria-describedby' => [],
                'aria-details' => [],
                'aria-label' => [],
                'aria-labelledby' => [],
                'aria-hidden' => [],
                'aria-required' => [],
                'aria-invalid' => [],
                'aria-haspopup' => [],
                'disabled' => [],
                'readonly' => [],
                'title' => [],
            ];

            array_walk_recursive($data, static function (&$item) use ($allowedAttributes) {
                $item = stripslashes($item);
                $item = wp_kses($item, [
                    'a' => array_merge(['href' => [], 'data-cc' => []], $allowedAttributes),
                    'button' => array_merge(['type' => [], 'data-cc' => []], $allowedAttributes),
                    'br' => $allowedAttributes,
                    'p' => $allowedAttributes,
                    'em' => $allowedAttributes,
                    'i' => $allowedAttributes,
                    'b' => $allowedAttributes,
                    'strong' => $allowedAttributes,
                    'ol' => $allowedAttributes,
                    'ul' => $allowedAttributes,
                    'li' => $allowedAttributes,
                    'span' => $allowedAttributes,
                ]);
            });

            foreach ($this->availableLanguages as $availableLanguage) {
                $data[$availableLanguage]['consent_modal']['primary_btn']['role'] = 'accept_all';
                $data[$availableLanguage]['consent_modal']['secondary_btn']['role'] = 'accept_necessary';

                $data[$availableLanguage]['settings_modal']['blocks'][1]['toggle'] = [
                    'value' => 'necessary',
                    'enabled' => true,
                    'readonly' => true,
                ];

                $data[$availableLanguage]['settings_modal']['blocks'][2]['toggle'] = [
                    'value' => self::COOKIE_CATEGORY_ANALYTICS,
                    'enabled' => false,
                    'readonly' => false,
                ];

                $data[$availableLanguage]['settings_modal']['blocks'][3]['toggle'] = [
                    'value' => self::COOKIE_CATEGORY_MARKETING,
                    'enabled' => false,
                    'readonly' => false,
                ];

                $data[$availableLanguage]['settings_modal']['blocks'][4]['toggle'] = [
                    'value' => self::COOKIE_CATEGORY_OTHER,
                    'enabled' => false,
                    'readonly' => false,
                ];
            }

            update_option('supporthost-cookie-consent-version', '1.0');

            update_option(self::COOKIE_CONSENT_SETTINGS_KEY, $data);
            $this->savedSettings = $this->getConsentSettings();
        }
    }

    /**
     * @return void
     */
    private function handleJavascriptsForm(): void
    {
        if (isset($_POST['save-settings'])) {
            update_option('supporthost-cookie-consent-cookie-expiration', sanitize_text_field($_POST['cookie_expiration']));
            update_option('supporthost-cookie-consent-gtm-id', sanitize_text_field($_POST['gtm_id']));
            update_option('supporthost-cookie-consent-head-scripts', base64_encode($_POST['head_scripts']));
            update_option('supporthost-cookie-consent-footer-scripts', base64_encode($_POST['footer_scripts']));
        }
    }

    /**
     * @return void
     */
    private function handleConsentReportForm(): void
    {

        global $wpdb;

        $cookieconsent = new CookieConsent;
        $table = $cookieconsent->getTableName();

        if ( $_POST['action'] == 'bulk-delete' && isset( $_POST['bulk-delete'] ) ) {
            foreach ( $_POST['bulk-delete'] as $id ) {
                $wpdb->delete( $table, array( 'id' => $id ) );
            }
            echo '<div id="message" class="updated notice"><p>' . count( $_POST['bulk-delete'] ) . ' ' . __('records have been deleted.', 'supporthost-cookie-consent') . '</p></div>';
        }
    }

    /**
     * @param bool $prepareForFrontend
     *
     * @return array
     */
    public function getConsentSettings(bool $prepareForFrontend = false): array
    {
        $this->availableLanguages = [mb_substr(get_locale(), 0, 2)];

        // Polylang
        if (function_exists('pll_languages_list')) {
            $this->availableLanguages = pll_languages_list();
        }

        // WPML
        if (has_filter('wpml_active_languages')) {
            $this->availableLanguages = array_keys(apply_filters('wpml_active_languages', null));
        }

        $savedSettings = [];
        if (get_option(self::COOKIE_CONSENT_SETTINGS_KEY)) {
            $savedSettings = get_option(self::COOKIE_CONSENT_SETTINGS_KEY);
        }

        if ($prepareForFrontend && $savedSettings) {
            foreach ($this->availableLanguages as $availableLanguage) {
                $savedSettings[$availableLanguage]['settings_modal']['cookie_table_headers'] = [
                    ['col1' => __('Name', 'supporthost-cookie-consent'),],
                    ['col2' => __('Domain', 'supporthost-cookie-consent'),],
                    ['col3' => __('Expiration', 'supporthost-cookie-consent'),],
                    ['col4' => __('Description', 'supporthost-cookie-consent'),],
                ];

                /* remove empty cookies tables */
                if (isset($savedSettings[$availableLanguage]['settings_modal']['blocks'])) {
                    foreach ($savedSettings[$availableLanguage]['settings_modal']['blocks'] as $blockIdx => $block) {
                        if (isset($block['cookie_table'])) {
                            $filledRowExists = false;
                            foreach ($block['cookie_table'] as $rowsIdx => $rows) {
                                if (!empty(array_filter($rows))) {
                                    // $savedSettings[$availableLanguage]['settings_modal']['blocks'][$blockIdx]['cookie_table'][$rowsIdx]['is_regex'] = true;
                                    // $savedSettings[$availableLanguage]['settings_modal']['blocks'][$blockIdx]['cookie_table'][$rowsIdx]['path'] = "/";
                                    $filledRowExists = true;
                                    break;
                                }
                            }
                            if (!$filledRowExists) {
                                unset($savedSettings[$availableLanguage]['settings_modal']['blocks'][$blockIdx]['cookie_table']);
                            }
                        }
                    }
                }
            }

            if (!isset($savedSettings['status']) ||
                'disabled' === $savedSettings['status'] ||
                ('only_admins' === $savedSettings['status'] && !current_user_can('administrator'))) {
                $savedSettings = [];
            }
        }

        return $savedSettings;
    }


}
