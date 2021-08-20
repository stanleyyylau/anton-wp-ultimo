# [WP Ultimo - Actions and Filters]( https://wpultimo.com )

**Version:** `1.10.13`

## TODO

## ../wp-ultimo.php

-  **apply_filters** `(line 223)` ('wu_admin_notices', WP_Ultimo()->messages[$network ? 'network_admin' : 'admin']);

## ../inc/class-wu-admin-settings.php

-  **apply_filters** `(line 122)` ('wu_get_setting', $setting_value, $setting, $default);
-  **apply_filters** `(line 146)` ('wu_get_logo', $attachment_url);
-  **apply_filters** `(line 156)` ('wu_get_logo', $logo);
-  **do_action** `(line 197)` ('wu_before_save_settings', $post);
-  **do_action** `(line 262)` ('wu_save_setting', $field_slug, $field, $post);
-  **do_action** `(line 274)` ('wu_after_save_settings', $post);
-  **apply_filters** `(line 478)` ('wu_get_post_types', $post_types);
-  **apply_filters** `(line 570)` ('wu_settings_section_general', array(
-  **apply_filters** `(line 708)` ('wu_settings_section_network', array(
-  **apply_filters** `(line 1216)` ('wu_settings_section_domain_mapping', array(
-  **apply_filters** `(line 1339)` ('wu_settings_section_payment_gateways', $gateway_fields) : $gateway_fields,
-  **apply_filters** `(line 1346)` ('wu_settings_section_emails', array(
-  **apply_filters** `(line 1362)` ('wu_settings_section_styling', array(
-  **apply_filters** `(line 1511)` ('wu_settings_section_tools', array(
-  **apply_filters** `(line 1520)` ('wu_settings_section_advanced', array(
-  **apply_filters** `(line 1675)` ('wu_settings_sections', $sections);
-  **apply_filters** `(line 1865)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 1918)` ('wu_registered_cpts', array(

## ../inc/class-wu-api.php

-  **apply_filters** `(line 210)` ('wu_should_log_api_calls', WU_Settings::get_setting('api-log-calls', false))) {
-  **apply_filters** `(line 255)` ('wu_is_api_enabled', WU_Settings::get_setting('enable-api', true));

## ../inc/class-wu-broadcasts-list-table.php

-  **apply_filters** `(line 46)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 87)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 142)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 162)` ('wu_get_broadcasts_table_headers', array(

## ../inc/class-wu-domain-mapping.php

-  **apply_filters** `(line 200)` ('wu_domain_mapping_get_ip_address', $ip, $_SERVER['SERVER_ADDR']);
-  **apply_filters** `(line 545)` ('wu_skip_redirect', false)) {
-  **apply_filters** `(line 731)` ('wu_post_types_unmap', array('post', 'page')), 'side', null);
-  **apply_filters** `(line 760)` ('wu_post_types_unmap', array('post', 'page'));
-  **do_action** `(line 853)` ('wu_after_domain_mapping', $url, $site->ID, $site->site_owner->ID);
-  **do_action** `(line 888)` ('wu_after_domain_mapping_removed', $url, $site->ID, $site->site_owner->ID);
-  **apply_filters** `(line 1032)` ( 'mercator.redirect.enabled', $mapping->is_active(), $mapping );
-  **apply_filters** `(line 1044)` ( 'mercator.redirect.admin.enabled', false );
-  **apply_filters** `(line 1055)` ( 'mercator.redirect.legacy.enabled', true );
-  **apply_filters** `(line 1101)` ('wu_skip_redirect', false)) {

## ../inc/class-wu-exporter-importer.php

-  **apply_filters** `(line 57)` ('wu_get_exporter_temp_folder', $path['path']);
-  **apply_filters** `(line 136)` ('wu_exporter_importer_get_data_post_types', array(
-  **apply_filters** `(line 164)` ('wu_exporter_importer_get_import_options', $options);

## ../inc/class-wu-geolocation.php

-  **apply_filters** `(line 51)` ( 'wu_geolocation_update_database_periodically', false ) ) {
-  **apply_filters** `(line 130)` ( 'wu_geolocation_ip_lookup_apis', self::$ip_lookup_apis );
-  **apply_filters** `(line 139)` ( 'wu_geolocation_ip_lookup_api_response', ($response['body']), $service_name );
-  **apply_filters** `(line 159)` ( 'wu_geolocate_ip', false, $ip_address, $fallback, $api_fallback );
-  **apply_filters** `(line 210)` ( 'wu_geolocation_local_database_path', $upload_dir['basedir'] . '/GeoIP' . $version . '.dat', $version );
-  **apply_filters** `(line 291)` ( 'wu_geolocation_geoip_apis', self::$geoip_apis );
-  **apply_filters** `(line 314)` ( 'wu_geolocation_geoip_response_' . $service_name, '', $response['body'] );

## ../inc/class-wu-gutenberg-support.php

-  **apply_filters** `(line 66)` ('wu_gutenberg_support_should_load', true);
-  **apply_filters** `(line 95)` ('wu_gutenberg_support_preview_message', sprintf(__('<strong>%s</strong> is generating the preview...', 'wp-ultimo'), get_network_option(null, 'site_name')));

## ../inc/class-wu-jumper.php

-  **apply_filters** `(line 411)` ('wu_link_list', $choices);

## ../inc/class-wu-light-ajax.php

-  **do_action** `(line 103)` ('wp_ajax_' . $action);
-  **do_action** `(line 107)` ('wp_ajax_nopriv_' . $action);

## ../inc/class-wu-links.php

-  **apply_filters** `(line 100)` ('wu_links_list', $links);
-  **apply_filters** `(line 124)` ('wu_get_link', $link, $slug, $this->default_link);

## ../inc/class-wu-logger.php

-  **apply_filters** `(line 64)` ('wu_get_logs_folder', self::get_uploads_folder() . "/wu-logs" . '/');
-  **do_action** `(line 198)` ('wu_log_add', $handle, $message);
-  **do_action** `(line 212)` ('wu_log_clear', $handle);

## ../inc/class-wu-login.php

-  **apply_filters** `(line 315)` ('wu_replace_login_urls', array('wp-login', 'wp-login.php'));

## ../inc/class-wu-mail.php

-  **apply_filters** `(line 54)` ('wu_email_shortcodes', array(
-  **apply_filters** `(line 259)` ('wu_email_template_style', WU_Settings::get_setting('email_template_style', 'html') == 'html');
-  **apply_filters** `(line 260)` ("wu_email_template_style_$slug", $style);
-  **apply_filters** `(line 301)` ('wu_mail_send_template', true, $slug, $to, $shortcodes, $attachments) == false) {
-  **apply_filters** `(line 392)` ('wu_retrieve_password_url', network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login'));

## ../inc/class-wu-notifications.php

-  **apply_filters** `(line 194)` ('wu_days_to_check_expired', 1);
-  **apply_filters** `(line 345)` ('wu_days_to_check_expired', 1) * DAY_IN_SECONDS;

## ../inc/class-wu-pages-addons.php

-  **apply_filters** `(line 337)` ( 'all_addons', $this->get_remote_addons_list() );

## ../inc/class-wu-pages-broadcast.php

-  **apply_filters** `(line 103)` ('wu_broadcast_count', 10), 1, 'message', $subscription->get_date('created_at', 'Y-m-d H:i:s'));
-  **apply_filters** `(line 276)` ('wu_email_template_style', WU_Settings::get_setting('email_template_style', 'html') == 'html');
-  **apply_filters** `(line 277)` ("wu_email_template_style_$style", $style);

## ../inc/class-wu-pages-edit.php

-  **do_action** `(line 125)` ("wu_{$this->object_id}");
-  **apply_filters** `(line 170)` ('wu_page_edit_get_title_links', $this->action_links, $this->object, $this);

## ../inc/class-wu-pages-feature-plugins.php

-  **apply_filters** `(line 320)` ( 'all_addons', $this->get_remote_addons_list() );

## ../inc/class-wu-pages-list.php

-  **apply_filters** `(line 183)` ('wu_page_list_get_title_links', $this->action_links, $this);

## ../inc/class-wu-pages-my-account.php

-  **apply_filters** `(line 88)` ('wu_my_accounts_page_title', __('Account', 'wp-ultimo')),
-  **apply_filters** `(line 203)` ('wu_my_accounts_page_title', __('Account', 'wp-ultimo')),
-  **apply_filters** `(line 204)` ('wu_my_accounts_page_title', __('Account', 'wp-ultimo')),
-  **apply_filters** `(line 205)` ('wu_my_accounts_page_icon', 'dashicons-id'),
-  **apply_filters** `(line 206)` ('wu_my_account_menu_position', 999999),

## ../inc/class-wu-pages.php

-  **do_action** `(line 207)` ('wu_page_load', $this->id, $this->page_hook);
-  **do_action** `(line 216)` ("wu_page_{$this->id}_load", $this->id, $this->page_hook);
-  **do_action** `(line 251)` ('wu_page_before_render', $this->id, $this);
-  **do_action** `(line 260)` ("wu_page_{$this->id}_before_render", $this->id, $this);
-  **do_action** `(line 276)` ('wu_page_after_render', $this->id, $this);
-  **do_action** `(line 285)` ("wu_page_{$this->id}_after_render", $this->id, $this);
-  **do_action** `(line 397)` ('wu_enqueue_extra_hooks', $this->page_hook);

## ../inc/class-wu-plans-limits.php

-  **apply_filters** `(line 71)` ('wu_apply_plan_limits', $this->plan, $this->user_id) ) return;
-  **apply_filters** `(line 129)` ('wu_block_frontend_message', __('This site is not available at this time.', 'wp-ultimo'), wu_get_current_site()->subscription);
-  **apply_filters** `(line 315)` ('wu_move_posts_alert_on_downgrade', $message, $action, $new_plan);
-  **apply_filters** `(line 364)` ('wu_block_frontend_message', __('This site is not available at this time.', 'wp-ultimo'), wu_get_current_site()->subscription);
-  **apply_filters** `(line 411)` ('wu_reset_visits_count_days', 30);
-  **do_action** `(line 560)` ('wu_flush_known_caches');
-  **apply_filters** `(line 694)` ('wu_limit_visits_message', __('This site is not available at this time.', 'wp-ultimo'), $site, $site->get_subscription() );
-  **apply_filters** `(line 719)` ('wu_signup_payment_step_title', $title ?: __('Payment Integration Needed', 'wp-ultimo'));
-  **apply_filters** `(line 733)` ('wu_signup_payment_step_text', $desc ?: __('We now need to complete your payment settings, setting up a payment subscription. Use the buttons below to add a integration.', 'wp-ultimo'));
-  **apply_filters** `(line 843)` ("wu_gateway_integration_button_$gateway->id", $button, $content); 
-  **apply_filters** `(line 947)` ('wu_limits_is_post_type_supported', ! $this->plan->is_post_type_disabled($post_type), $this->plan, $this->user_id);
-  **apply_filters** `(line 972)` ('wu_post_count_statuses', array('publish', 'private'), $post_type);
-  **apply_filters** `(line 993)` ('wu_post_count', $count, $post_counts, $post_type);
-  **apply_filters** `(line 1026)` ('wu_limits_is_post_above_limit', $quota > 0 && $post_count >= $quota, $this->plan, $this->user_id);

## ../inc/class-wu-plans-list-table.php

-  **apply_filters** `(line 32)` ('wu_core_get_table_prefix', $wpdb->base_prefix);

## ../inc/class-wu-plans.php

-  **do_action** `(line 265)` ('activate_plugin', trim($plugin));        
-  **do_action** `(line 267)` ('activate_' . trim($plugin));
-  **do_action** `(line 268)` ('activated_plugin', trim($plugin));
-  **do_action** `(line 284)` ('deactivate_plugin', trim($plugin));
-  **do_action** `(line 288)` ('deactivate_' . trim($plugin));
-  **do_action** `(line 289)` ('deactivated_plugin', trim($plugin));
-  **apply_filters** `(line 441)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **do_action** `(line 814)` ('wu_save_plan', $plan);
-  **do_action** `(line 960)` ('wp_ultimo_coupon_after_save', $coupon);

## ../inc/class-wu-scripts.php

-  **apply_filters** `(line 118)` ('wu_js_variables', $settings);

## ../inc/class-wu-shortcodes.php

-  **apply_filters** `(line 284)` ('wp_signup_location', network_site_url('wp-signup.php')); //wp_registration_url();

## ../inc/class-wu-signup.php

-  **apply_filters** `(line 170)` ('wu_domain_step_title', __('Domain', 'wp-ultimo')),
-  **apply_filters** `(line 171)` ('wu_domain_step_tooltip', ''),
-  **apply_filters** `(line 256)` ('wp_signup_location', network_site_url('wp-signup.php'));
-  **apply_filters** `(line 318)` ('wu_signup_transiente_lifetime', 40 * MINUTE_IN_SECONDS, $this));
-  **apply_filters** `(line 321)` ('wp_signup_location', network_site_url('wp-signup.php'));
-  **apply_filters** `(line 399)` ('wu_signup_display_logo', true) === false) return;
-  **apply_filters** `(line 554)` ('wu_replace_signup_urls', array('wp-signup', 'wp-signup.php'));
-  **apply_filters** `(line 599)` ('wu_replace_signup_urls_exclude', array('wu-signup-customizer-preview'));
-  **apply_filters** `(line 767)` ('wu_signup_fields_domain', array(
-  **apply_filters** `(line 771)` ('wu_signup_site_title_label', __('Site Title', 'wp-ultimo')),
-  **apply_filters** `(line 775)` ('wu_signup_site_title_tooltip', __('Select the title your site is going to have.', 'wp-ultimo')),
-  **apply_filters** `(line 782)` ('wu_signup_site_url_label', __('URL', 'wp-ultimo')),
-  **apply_filters** `(line 786)` ('wu_signup_site_url_tooltip', __('Site urls can only contain lowercase letters (a-z) and numbers and must be at least 4 characters. .', 'wp-ultimo')),
-  **apply_filters** `(line 816)` ('wu_signup_username_label', __('Username', 'wp-ultimo')),
-  **apply_filters** `(line 820)` ('wu_signup_username_tooltip', __('Username must be at least 4 characters.', 'wp-ultimo')),
-  **apply_filters** `(line 827)` ('wu_signup_email_label', __('Email', 'wp-ultimo')),
-  **apply_filters** `(line 831)` ('wu_signup_email_tooltip', ''),
-  **apply_filters** `(line 838)` ('wu_signup_password_label', __('Password', 'wp-ultimo')),
-  **apply_filters** `(line 842)` ('wu_signup_password_tooltip', __('Your password should be at least 6 characters long.', 'wp-ultimo')),
-  **apply_filters** `(line 850)` ('wu_signup_password_conf_label', __('Confirm Password', 'wp-ultimo')),
-  **apply_filters** `(line 854)` ('wu_signup_password_conf_tooltip', ''),
-  **apply_filters** `(line 946)` ('wu_signup_fields_account', $account_fields),
-  **apply_filters** `(line 952)` ('wp_ultimo_registration_steps', $steps) : $steps;
-  **apply_filters** `(line 1246)` ('wu_current_step', $current_step);
-  **apply_filters** `(line 1296)` ("wu_signup_step_handler_$this->step", $handler_function);
-  **do_action** `(line 1305)` ('wu_before_signup_header');
-  **do_action** `(line 1604)` ("wp_ultimo_registration_step_{$this->step}_save", $transient);
-  **apply_filters** `(line 1638)` ('wu_geolocation_error_message', __('Sorry. Our service is not allowed in your country.', 'wp-ultimo')));
-  **apply_filters** `(line 1730)` ('wu_signup_transiente_lifetime', 40 * MINUTE_IN_SECONDS, $this));
-  **apply_filters** `(line 1765)` ('wp_ultimo_registration_step_plans_save_transient', $transient);
-  **do_action** `(line 1768)` ('wp_ultimo_registration_step_plans_save', $transient);
-  **do_action** `(line 1809)` ('wp_ultimo_registration_step_domain_save', $transient);
-  **apply_filters** `(line 1841)` ('wu_register_trial_value', $default_trial_value, $plan);
-  **apply_filters** `(line 1889)` ('wu_password_min_length', 6);
-  **do_action** `(line 1943)` ('wp_ultimo_registration_step_account_save', $transient);
-  **apply_filters** `(line 2025)` ('wu_site_template_id', $plan->site_template, $user_id);
-  **apply_filters** `(line 2030)` ('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator'), $transient);
-  **apply_filters** `(line 2038)` ('wu_create_site_meta', array(
-  **do_action** `(line 2101)` ('wp_ultimo_registration', $site_id, $user_id, $transient, $plan);
-  **apply_filters** `(line 2124)` ('wp_ultimo_redirect_url_after_signup', get_admin_url($site_id), $site_id, $user_id, $transient);
-  **apply_filters** `(line 2222)` ('wu_register_should_pay_setup_fee', $plan && $plan->has_setup_fee(), $plan);
-  **apply_filters** `(line 2232)` ('wu_set_active_until', WU_Signup::get_active_until_with_trial($now, $trial), $plan_data['plan_id'], $plan_data['plan_freq']),
-  **apply_filters** `(line 2283)` ('wu_signup_transfer_posts_to_new_user_id', $user_id, $site_id);
-  **do_action** `(line 2484)` ('wu_signup_after_create_site', $user_id, $site_id);
-  **apply_filters** `(line 2542)` ('wu_signup_transiente_lifetime', 40 * MINUTE_IN_SECONDS, $this));
-  **apply_filters** `(line 2603)` ('get_site_url_for_previewer', $domain, $domain_options);

## ../inc/class-wu-site-hooks.php

-  **apply_filters** `(line 289)` ('wu_core_get_table_prefix', $wpdb->base_prefix) . 'sitemeta';
-  **apply_filters** `(line 501)` ('mucd_string_to_replace', array(), false, $to_site_id);
-  **apply_filters** `(line 605)` ('wu_search_and_replace_on_duplication', $search_and_replace_settings, $from_site_id, $to_site_id);
-  **apply_filters** `(line 768)` ('wu_switch_template_settings_to_keep', array(
-  **apply_filters** `(line 891)` ('wu_get_template_preview_slug', 'template-preview');
-  **apply_filters** `(line 921)` ('wu_get_template_preview_url', $url, $site_id);
-  **apply_filters** `(line 947)` ('wu_wp_die_title', $title);
-  **apply_filters** `(line 950)` ('wu_wp_die_message', $message),
-  **do_action** `(line 1144)` ( 'pre_network_site_new_created_user', $email );
-  **do_action** `(line 1163)` ( 'network_site_new_created_user', $user_id );
-  **do_action** `(line 1167)` ('wp_ultimo_after_site_creation', $user_id);
-  **do_action** `(line 1446)` ('wu_subscription_change_plan', $subscription, $plan);
-  **apply_filters** `(line 1486)` ('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator'), array(
-  **apply_filters** `(line 1518)` ('wu_site_template_id', $post['site_template'], $user_id);
-  **apply_filters** `(line 1524)` ('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator'), array(
-  **apply_filters** `(line 1525)` ('wu_extra_sites_plan_id', false)
-  **apply_filters** `(line 1541)` ('wu_extra_sites_plan_id', false);
-  **apply_filters** `(line 1542)` ('wu_extra_sites_freq', 1);
-  **do_action** `(line 1637)` ('wu_clear_available_site_template_cache');
-  **apply_filters** `(line 1742)` ('wu_duplicate_keep_users', 'no'),
-  **do_action** `(line 1810)` ('wu_duplicate_site', $duplicated);
-  **apply_filters** `(line 1845)` ('wu_duplicate_keep_users', 'no'),
-  **do_action** `(line 1889)` ('wu_after_switch_template', $duplicated['site_id']);

## ../inc/class-wu-site-templates.php

-  **apply_filters** `(line 218)` ( 'all_site_templates', array_keys($templates));
-  **apply_filters** `(line 264)` ('wu_prepare_site_templates', array($categories, $prepared_templates), $categories, $prepared_templates);

## ../inc/class-wu-subscriptions-list-table.php

-  **apply_filters** `(line 82)` ('wu_subscriptions_status', array(
-  **apply_filters** `(line 109)` ('wu_subscriptions_get_views', $actions);

## ../inc/class-wu-subscriptions.php

-  **do_action** `(line 209)` ('wu_remove_account', $user_id);
-  **apply_filters** `(line 620)` ('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator')));

## ../inc/class-wu-transactions-list-table.php

-  **apply_filters** `(line 50)` ('wu_transactions_list_table_get_transactions', $results, self::$user_id, $per_page, $page_number, $orderby, $order);
-  **apply_filters** `(line 89)` ('wu_transactions_list_table_get_transactions_count', $results, self::$user_id);
-  **apply_filters** `(line 122)` ('wu_get_transactions_table_headers', $columns);
-  **apply_filters** `(line 140)` ('wu_get_transactions_table_sortable_columns', $sortable_columns);
-  **apply_filters** `(line 281)` ('wu_transaction_item_actions', $actions, $item));

## ../inc/class-wu-transactions.php

-  **apply_filters** `(line 25)` ('wu_core_get_table_prefix', $wpdb->base_prefix) . 'wu_transactions';
-  **apply_filters** `(line 138)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 156)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 168)` ('wu_transactions_get_transactions_total', $results->total, $user_id);
-  **apply_filters** `(line 181)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 193)` ('wu_transactions_get_refunds_total', $results->total, $user_id);
-  **apply_filters** `(line 216)` ('wu_core_get_table_prefix', $wpdb->base_prefix);

## ../inc/class-wu-ui-elements.php

-  **apply_filters** `(line 89)` ('wu_admin_theme_classes', array(
-  **apply_filters** `(line 106)` ('wu_has_admin_theme_active', $has_admin_theme_active, $admin_theme_classes);
-  **apply_filters** `(line 132)` ('wu_is_branded_page', in_array(str_replace('-network', '', $screen->id), $this->pages), $screen->id, $this->pages);

## ../inc/class-wu-webhooks.php

-  **do_action** `(line 89)` ('wu_register_webhook_listeners');
-  **apply_filters** `(line 130)` ('wu_event_payload_account_created', $data, $user_id));
-  **apply_filters** `(line 152)` ('wu_event_payload_new_domain_mapping', $data, $user_id));
-  **apply_filters** `(line 174)` ('wu_event_payload_payment_received', $data, $user_id));
-  **apply_filters** `(line 196)` ('wu_event_payload_payment_failed', $data, $user_id));
-  **apply_filters** `(line 218)` ('wu_event_payload_refund_issued', $data, $user_id));
-  **apply_filters** `(line 239)` ('wu_event_payload_account_deleted', $data, $user_id));
-  **apply_filters** `(line 264)` ('wu_event_payload_plan_change', $data, $subscription->user_id));
-  **apply_filters** `(line 677)` ('wu_send_event_data', array(

## ../inc/class-wu-widgets.php

-  **apply_filters** `(line 250)` ('wu_should_display_admin_widgets', true)) return;

## ../inc/duplicate/data.php

-  **apply_filters** `(line 60)` ('wu_filter_tables_to_copy', MUCD_Data::do_sql_query($sql_query, 'col')); 
-  **apply_filters** `(line 169)` ('mucd_string_to_replace', $string_to_replace, $from_site_id, $to_site_id);

## ../inc/duplicate/duplicate.php

-  **do_action** `(line 63)` ('wu_add_element_permissions');
-  **do_action** `(line 84)` ( 'mucd_before_copy_files', $from_site_id, $to_site_id );
-  **do_action** `(line 86)` ( 'mucd_after_copy_files', $from_site_id, $to_site_id );
-  **do_action** `(line 90)` ( 'mucd_before_copy_data', $from_site_id, $to_site_id );
-  **do_action** `(line 92)` ( 'mucd_after_copy_data', $from_site_id, $to_site_id );
-  **do_action** `(line 96)` ( 'mucd_before_copy_users', $from_site_id, $to_site_id );
-  **do_action** `(line 98)` ( 'mucd_after_copy_users', $from_site_id, $to_site_id );
-  **do_action** `(line 135)` ( 'mucd_before_copy_files', $from_site_id, $to_site_id );
-  **do_action** `(line 137)` ( 'mucd_after_copy_files', $from_site_id, $to_site_id );
-  **do_action** `(line 141)` ( 'mucd_before_copy_data', $from_site_id, $to_site_id );
-  **do_action** `(line 143)` ( 'mucd_after_copy_data', $from_site_id, $to_site_id );
-  **do_action** `(line 147)` ( 'mucd_before_copy_users', $from_site_id, $to_site_id );
-  **do_action** `(line 149)` ( 'mucd_after_copy_users', $from_site_id, $to_site_id );

## ../inc/duplicate/files.php

-  **apply_filters** `(line 34)` ('mucd_copy_dirs', $dirs, $from_site_id, $to_site_id);

## ../inc/duplicate/option.php

-  **apply_filters** `(line 121)` ('mucd_copy_blog_data_saved_options', MUCD_Option::get_default_saved_option());
-  **apply_filters** `(line 149)` ('mucd_default_fields_to_update', MUCD_Option::get_default_fields_to_update());
-  **apply_filters** `(line 177)` ('mucd_default_primary_tables_to_copy', MUCD_Option::get_default_primary_tables_to_copy());

## ../inc/gateways/class-wu-gateway-manual.php

-  **apply_filters** `(line 103)` ('wu_gateway_menual_is_transaction_subscription_payment', $check, $transaction, $this);
-  **do_action** `(line 244)` ('wp_ultimo_payment_completed', $transaction->user_id, $this->id, $subscription->price, $setup_fee_value);
-  **do_action** `(line 575)` ('wp_ultimo_payment_refunded', $transaction->user_id, $this->id, $value);
-  **do_action** `(line 667)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **do_action** `(line 752)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);

## ../inc/gateways/class-wu-gateway-paypal.php

-  **apply_filters** `(line 423)` ('wu_gateway_paypal_use_logo', WU_Settings::get_logo())) {
-  **do_action** `(line 846)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **do_action** `(line 864)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **apply_filters** `(line 981)` ('wu_gateway_paypal_use_logo', WU_Settings::get_logo('medium'))) {
-  **apply_filters** `(line 1208)` ('wu_gateway_paypal_max_failed_payments', 3);
-  **do_action** `(line 1443)` ('wu_paypal_handle_notifications', $ipn_object);
-  **apply_filters** `(line 1471)` ('wu_paypal_get_target_subscription', $user_id, $ipn, $this);
-  **do_action** `(line 1554)` ('wp_ultimo_payment_failed', $user_id, $this->id, $ipn->mc_gross);
-  **do_action** `(line 1587)` ('wp_ultimo_payment_refunded', $user_id, $this->id, $ipn->mc_gross);
-  **do_action** `(line 1645)` ('wp_ultimo_payment_refunded', $user_id, $this->id, $ipn->mc_gross);
-  **do_action** `(line 1707)` ('wp_ultimo_payment_completed', $user_id, $this->id, $ipn->mc_gross, $setup_fee_value);
-  **do_action** `(line 1804)` ('wp_ultimo_payment_completed', $user_id, $this->id, $ipn->initial_payment_amount, $setup_fee_value);

## ../inc/gateways/class-wu-gateway-stripe.php

-  **apply_filters** `(line 106)` ('wu_update_payment_method_success_title', __('Success!', 'wp-ultimo'));
-  **apply_filters** `(line 108)` ('wu_update_payment_method_success_message', __('Payment method successfully updated!', 'wp-ultimo'));
-  **apply_filters** `(line 362)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 507)` ('wu_setup_fee_message', sprintf(__('Setup fee for Plan %s', 'wp-ultimo'), $this->plan->title), $this->plan);
-  **apply_filters** `(line 526)` ('wu_stripe_should_set_48h_trial', true, $trial_end) ? $trial_end : false;
-  **apply_filters** `(line 875)` ('wu_stripe_user_metadata', array(), $this->subscription->user_id, $this->plan)),
-  **apply_filters** `(line 897)` ('wu_setup_fee_message', sprintf(__('Setup fee for Plan %s', 'wp-ultimo'), $this->plan->title), $this->plan);
-  **apply_filters** `(line 921)` ('wu_stripe_user_metadata', array(), $this->subscription->user_id, $this->plan));
-  **do_action** `(line 1013)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **do_action** `(line 1082)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **do_action** `(line 1505)` ('wp_ultimo_payment_completed', $user_id, $this->id, $this->format_from_stripe( $event->data->object->amount_due ), $setup_fee_value);
-  **do_action** `(line 1546)` ('wp_ultimo_payment_failed', $user_id, $this->id, $this->format_from_stripe( $event->data->object->amount_due ));
-  **do_action** `(line 1579)` ('wp_ultimo_payment_refunded', $user_id, $this->id, $value);
-  **apply_filters** `(line 1652)` ('wu_stripe_user_metadata', array(), $this->user_id, $this->plan_id);

## ../inc/gateways/class-wu-gateway.php

-  **apply_filters** `(line 211)` ("wu_gateway_supports_single_payments_$this->id", false);
-  **apply_filters** `(line 238)` ('wu_gateway_get_url', admin_url(sprintf('admin.php?page=wu-my-account&action=%s&code=%s&gateway=%s', $page_slug, $security_code, $this->id)), $page_slug, $security_code);
-  **apply_filters** `(line 266)` ('wu_get_gateway_title_' . $this->id, $this->title);
-  **do_action** `(line 335)` ("wu_gateway_page_load_$action", $this->subscription);
-  **do_action** `(line 358)` ("wu_gateway_page_load_$action", $this->subscription);
-  **apply_filters** `(line 382)` ('wu_signup_payment_step_button_text', $label, $this->title, $this); 
-  **do_action** `(line 528)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **do_action** `(line 559)` ('wu_subscription_change_plan', $this->subscription, $new_plan, $current_plan);
-  **apply_filters** `(line 577)` ('wu_payment_integration_success_title', __('Success!', 'wp-ultimo'));
-  **apply_filters** `(line 578)` ('wu_payment_integration_success_message', __('Your new payment method was added with success! You should get the first receipt for your subscription soon. Do not panic if no integration appears at first. It may take up to 5 minutes before we hear back from your selected payment method.', 'wp-ultimo'));
-  **do_action** `(line 723)` ('wu_subscription_create_integration', $subscription, $plan, $integration_key, $this);
-  **apply_filters** `(line 815)` ('wu_gateway_should_generate_invoice', $should_generate_invoice, $this) == false) return false;
-  **apply_filters** `(line 845)` ('wu_invoice_title', __('Invoice', 'wp-ultimo'), $subscription, $invoice, $this); 
-  **apply_filters** `(line 865)` ('wu_invoice_reference', "SUB #$subscription->user_id", $subscription);
-  **apply_filters** `(line 878)` ('wu_invoice_from', $merchant_info);
-  **apply_filters** `(line 884)` ('wu_invoice_to', array(
-  **apply_filters** `(line 962)` ('wu_invoice_bottom_message', false, $subscription);

## ../inc/mercator/admin.php

-  **do_action** `(line 221)` _ref_array( "mercator_aliases_bulk_action-{$action}", array( $mappings, &$processed, $action ) );
-  **apply_filters** `(line 301)` ( 'mercator_aliases_bulk_messages', $bulk_messages, $processed );

## ../inc/mercator/class-mapping.php

-  **do_action** `(line 138)` ( 'mercator.mapping.made_primary', $this );
-  **do_action** `(line 227)` ( 'mercator.mapping.updated', $this, $old_mapping );
-  **do_action** `(line 256)` ( 'mercator.mapping.deleted', $this );
-  **do_action** `(line 497)` ( 'mercator.mapping.created', $mapping );

## ../inc/mercator/inc/admin/class-alias-list-table.php

-  **apply_filters** `(line 66)` ( 'mercator_alias_bulk_actions', $actions );
-  **apply_filters** `(line 93)` ( "bulk_actions-{$this->screen->id}", $this->_actions );
-  **apply_filters** `(line 225)` ( 'mercator_alias_actions', $actions, $mapping );

## ../inc/mercator/inc/cli/class-mapping-command.php

-  **apply_filters** `(line 33)` ( 'mercator.cli.mapping.fields', $data, $mapping );

## ../inc/mercator/inc/cli/class-network-mapping-command.php

-  **apply_filters** `(line 33)` ( 'mercator.cli.mapping.fields', $data, $mapping );

## ../inc/mercator/mercator.php

-  **do_action** `(line 173)` ( 'mercator_load' );
-  **apply_filters** `(line 228)` ( 'mercator.use_mapping', $mapping->is_active(), $mapping, $domain );

## ../inc/mercator/multinetwork.php

-  **apply_filters** `(line 24)` ( 'mercator.multinetwork.enabled', false );
-  **apply_filters** `(line 242)` ( 'mercator.multinetwork.host_parts_segments_count', $segments, $domain );

## ../inc/mercator/sso-multinetwork.php

-  **apply_filters** `(line 27)` ( 'mercator.sso.multinetwork.enabled', Multinetwork\is_enabled() );
-  **apply_filters** `(line 52)` ( 'mercator.sso.multinetwork.skip_cookiehash_check', false );

## ../inc/mercator/sso.php

-  **apply_filters** `(line 31)` ( 'mercator.sso.enabled', true );
-  **apply_filters** `(line 159)` ( 'mercator.sso.main_domain_network', $network, $domain, $supplied_network );
-  **apply_filters** `(line 196)` ( 'mercator.sso.is_main_domain', $is_main, $domain, $network );
-  **apply_filters** `(line 237)` ( 'mercator.sso.main_site_for_actions', get_main_site() );
-  **apply_filters** `(line 247)` ( 'mercator.sso.action_url', $url, $action, $args );
-  **apply_filters** `(line 507)` ( 'mercator.sso.login_url', $url, $args );
-  **apply_filters** `(line 563)` ( 'mercator.sso.expiration', 5 * MINUTE_IN_SECONDS );

## ../inc/models/wu-coupon.php

-  **do_action** `(line 213)` ('wu_save_coupon', $this);

## ../inc/models/wu-plan.php

-  **apply_filters** `(line 262)` ('wu_plan_should_override_templates', $this->override_templates, $this);
-  **apply_filters** `(line 274)` ('wu_plan_is_contact_us', $this->is_contact_us, $this);
-  **apply_filters** `(line 286)` ('wu_plan_get_contact_us_label', $this->contact_us_label ?: __('Contact Us', 'wp-ultimo'), $this);
-  **apply_filters** `(line 357)` ('wu_plan_should_copy_media', $should_copy_media, $this);
-  **apply_filters** `(line 369)` ('wu_plan_should_allow_unlimited_extra_users', (bool) $this->unlimited_extra_users, $this);
-  **apply_filters** `(line 424)` ('wu_plan_get_quota', $quota, $quota_type, $quotas, $this);
-  **apply_filters** `(line 616)` ('wu_plan_get_price', $price, $billing_frequency);
-  **apply_filters** `(line 640)` ('wu_plan_get_setup_fee', $this->setup_fee ? (float) $this->setup_fee : 0, $this);
-  **apply_filters** `(line 692)` ('wu_get_post_types', $post_types);
-  **apply_filters** `(line 804)` ("wu_get_pricing_table_lines_$this->id", $pricing_table_lines, $this);

## ../inc/models/wu-site-owner.php

-  **apply_filters** `(line 25)` ('wu_core_get_table_prefix', $wpdb->base_prefix) . 'wu_site_owner';

## ../inc/models/wu-site.php

-  **apply_filters** `(line 234)` ('mercator.redirect.enabled', $mapping->is_active(), $mapping);
-  **apply_filters** `(line 272)` ('wu_site_get_user_count_exclude_roles', array(
-  **apply_filters** `(line 292)` ('wu_site_get_user_count', count($users), $users);
-  **apply_filters** `(line 536)` ('wu_reset_visits_count_days', 30);

## ../inc/models/wu-subscription.php

-  **apply_filters** `(line 123)` ('wu_core_get_table_prefix', $wpdb->base_prefix) . 'wu_subscriptions';
-  **apply_filters** `(line 258)` ('wu_subscription_before_save', get_object_vars($this), $this);
-  **apply_filters** `(line 341)` ('wu_activation_permission_time', 600, $this);
-  **apply_filters** `(line 479)` ('wu_subscription_is_active', $active_until > $now, $this->user_id, $this);
-  **apply_filters** `(line 490)` ('wu_subscription_on_hold_gateways', array('manual'));
-  **apply_filters** `(line 539)` ('wu_subscription_status_labels', array(
-  **apply_filters** `(line 592)` ('wu_subscription_get_allowed_sites', $site_list, $this);
-  **apply_filters** `(line 608)` ('wu_subscription_get_allowed_sites', $allowed_sites, $this);
-  **apply_filters** `(line 622)` ('wu_subscription_get_site_count', count($sites), $sites, $this);
-  **do_action** `(line 881)` ('wp_ultimo_apply_coupon_code', $coupon, $this);
-  **apply_filters** `(line 1003)` ('wu_subscription_get_plan', new WU_Plan($this->plan_id), $this->plan_id, $this->user_id, $this);
-  **apply_filters** `(line 1040)` ('wu_subscription_get_plan_id', $this->plan_id ?: false, $this->plan_id, $this->user_id, $this);
-  **apply_filters** `(line 1057)` ('wu_core_get_table_prefix', $wpdb->base_prefix);
-  **apply_filters** `(line 1106)` ('wu_subscription_on_hold_gateways', array('manual')));
-  **apply_filters** `(line 1213)` ('wu_subscription_get_price', $this->price, $this);
-  **apply_filters** `(line 1246)` ('wu_subscription_get_price_after_coupon_code', $value, $this);
-  **apply_filters** `(line 1281)` ('wu_get_manage_url', $url, $this);
-  **apply_filters** `(line 1307)` ('wu_get_days_used', $days_used, $this);
-  **apply_filters** `(line 1321)` ('wu_calculate_credit', $this->get_days_used() * ((float) $this->get_price_after_coupon_code() / $days_to_divide), $this);
-  **apply_filters** `(line 1381)` ('wu_subscription_get_credit', (float) $this->credit, $this);
-  **apply_filters** `(line 1407)` ('wu_subscription_get_invoice_lines', $this->lines, $this); 
-  **apply_filters** `(line 1438)` ('wu_subscription_invoice_line_credit_message', __('Credit gained from previous plan usage.', 'wp-ultimo')) 
-  **apply_filters** `(line 1439)` ('wu_subscription_invoice_line_debit_message', __('Pro-rated adjustment from previous plan.', 'wp-ultimo'));
-  **apply_filters** `(line 1451)` ('wu_subscription_get_outstanding_amount', $price, $this);
-  **do_action** `(line 1480)` ("wu_subscription_charge_$this->gateway", $amount, $description, $this, $type);
-  **apply_filters** `(line 1492)` ('wu_subscription_has_paid_setup_fee', $this->paid_setup_fee, $this);

## ../inc/setup/class-wu-setup.php

-  **apply_filters** `(line 158)` ('wu_setup_slug', 'wu-setup');
-  **apply_filters** `(line 160)` ( $this->theme_name . '_theme_setup_wizard_parent_slug', '' );
-  **apply_filters** `(line 165)` ('wu_setup_page_url', $this->page_url);
-  **apply_filters** `(line 256)` ($this->theme_name . '_theme_setup_wizard_tgmpa_menu_slug', $this->tgmpa_menu_slug);
-  **apply_filters** `(line 260)` ($this->theme_name . '_theme_setup_wizard_tgmpa_url', $tgmpa_parent_slug.'?page='.$this->tgmpa_menu_slug);
-  **apply_filters** `(line 325)` (  $this->theme_name . '_theme_setup_wizard_steps', $this->steps );
-  **do_action** `(line 404)` ( 'admin_enqueue_scripts' ); ?>
-  **do_action** `(line 405)` ( 'admin_print_styles' ); ?>
-  **do_action** `(line 406)` ( 'admin_print_scripts' ); ?>
-  **do_action** `(line 407)` ( 'admin_head' ); ?>
-  **do_action** `(line 438)` ( 'admin_footer' ); // this was spitting out some errors in some admin templates. quick @ fix until I have time to find out what's causing errors.
-  **do_action** `(line 439)` ( 'admin_print_footer_scripts' );
-  **do_action** `(line 846)` ('wu_save_setting', $field_slug, $field, $post);
-  **do_action** `(line 856)` ('wu_after_save_settings');

## ../inc/setup/importer/wordpress-importer.php

-  **do_action** `(line 160)` ( 'import_start' );
-  **do_action** `(line 181)` ( 'import_end' );
-  **apply_filters** `(line 396)` ( 'wp_import_categories', $this->categories );
-  **apply_filters** `(line 442)` ( 'wp_import_tags', $this->tags );
-  **apply_filters** `(line 482)` ( 'wp_import_terms', $this->terms );
-  **apply_filters** `(line 531)` ( 'wp_import_posts', $this->posts );
-  **apply_filters** `(line 534)` ( 'wp_import_post_data_raw', $post );
-  **do_action** `(line 540)` ( 'wp_import_post_exists', $post );
-  **apply_filters** `(line 593)` ( 'wp_import_post_data_processed', $postdata, $post );
-  **do_action** `(line 614)` ( 'wp_import_insert_post', $post_id, $original_post_ID, $postdata, $post );
-  **apply_filters** `(line 636)` ( 'wp_import_post_terms', $post['terms'], $post_id, $post );
-  **do_action** `(line 650)` ( 'wp_import_insert_term', $t, $term, $post_id, $post );
-  **do_action** `(line 656)` ( 'wp_import_insert_term_failed', $t, $term, $post_id, $post );
-  **do_action** `(line 665)` ( 'wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $post );
-  **apply_filters** `(line 673)` ( 'wp_import_post_comments', $post['comments'], $post_id, $post );
-  **do_action** `(line 705)` ( 'wp_import_insert_comment', $inserted_comments[$key], $comment, $comment_post_ID, $post );
-  **apply_filters** `(line 721)` ( 'wp_import_post_meta', $post['postmeta'], $post_id, $post );
-  **apply_filters** `(line 726)` ( 'import_post_meta_key', $meta['key'], $post_id, $post );
-  **do_action** `(line 742)` ( 'import_post_meta', $post_id, $key, $value );
-  **apply_filters** `(line 1082)` ( 'import_allow_create_users', true );
-  **apply_filters** `(line 1093)` ( 'import_allow_fetch_attachments', true );
-  **apply_filters** `(line 1103)` ( 'import_attachment_size_limit', 0 );

## ../inc/wu-functions.php

-  **apply_filters** `(line 139)` ('wu_currencies',
-  **apply_filters** `(line 326)` ('wu_currency_symbol', $currency_symbol, $currency);
-  **apply_filters** `(line 350)` ('wu_format_currency', $format, $currency_symbol, $value);

## ../inc/wu-pluggable.php

-  **do_action** `(line 72)` ( 'retrieve_password_key', $user->user_login, $key );
-  **do_action** `(line 145)` ( 'check_ajax_referer', $action, $result );

## ../views/account/my-account.php

-  **do_action** `(line 1)` ('wu-my-account-page'); ?>
-  **apply_filters** `(line 88)` ('wu_my_accounts_page_title', __('Account', 'wp-ultimo')); ?></h1>
-  **do_action** `(line 90)` ('admin_notices'); ?>

## ../views/base/edit.php

-  **do_action** `(line 27)` ('wu_page_edit_after_title', $object, $page);
-  **do_action** `(line 72)` ('wu_edit_page_after_title_input', $object, $page);
-  **do_action** `(line 177)` ('wu_page_edit_footer', $object, $page);

## ../views/base/list.php

-  **do_action** `(line 26)` ('wu_page_list_after_title', $page);
-  **do_action** `(line 82)` ('wu_page_list_footer', $page);

## ../views/forms/add-owner.php

-  **do_action** `(line 135)` ('wp_ultimo_site_settings', $site_id);

## ../views/forms/new-site-for-user.php

-  **do_action** `(line 116)` ( 'network_site_new_form_for_user' );

## ../views/meta/confirm-remove-account.php

-  **do_action** `(line 9)` ('wu_remove_account_alerts'); ?>

## ../views/meta/error-page.php

-  **do_action** `(line 121)` ('admin_print_styles'); ?>
-  **do_action** `(line 122)` ('admin_print_scripts'); ?>
-  **do_action** `(line 123)` ('admin_head'); ?>
-  **do_action** `(line 125)` ('signup_header'); ?>
-  **do_action** `(line 126)` ('login_enqueue_scripts'); ?>
-  **do_action** `(line 156)` ('admin_print_footer_scripts'); ?>

## ../views/meta/system-info.php

-  **apply_filters** `(line 29)` ('wu_system_info_tabs', array(
-  **do_action** `(line 285)` ('wu_system_info_extra_tabs');

## ../views/settings/base.php

-  **do_action** `(line 671)` ('wu_after_settings_section_'.$_GET['wu-tab']);

## ../views/signup/pricing-table/plan.php

-  **apply_filters** `(line 39)` ("wu_pricing_table_plan", $plan_attrs, $plan);
-  **apply_filters** `(line 47)` ('wu_featured_plan_label', __('Featured Plan', 'wp-ultimo'), $plan); ?></h6>
-  **apply_filters** `(line 63)` ('wu_plan_contact_us_price_line', __('--', 'wp-ultimo')); ?></span>
-  **apply_filters** `(line 115)` ('wu_plan_select_button_attributes', "", $plan, $current_plan);
-  **apply_filters** `(line 117)` ('wu_plan_select_button_label', $button_label, $plan, $current_plan);

## ../views/signup/signup-main.php

-  **do_action** `(line 22)` ('wu_signup_enqueue_scripts');
-  **apply_filters** `(line 62)` ('wu_signup_page_title', sprintf(__('%s - Signup', 'wp-ultimo'), get_bloginfo('Name'), get_bloginfo('Name'))); ?>
-  **do_action** `(line 66)` ('signup_header'); ?>
-  **do_action** `(line 67)` ('login_enqueue_scripts'); ?>
-  **do_action** `(line 68)` ('admin_print_scripts'); ?>
-  **do_action** `(line 69)` ('admin_print_styles'); ?>
-  **do_action** `(line 70)` ('wu_signup_enqueue_scripts'); ?>
-  **do_action** `(line 72)` ('admin_head'); ?>
-  **do_action** `(line 86)` ('wu_signup_header'); ?>
-  **do_action** `(line 101)` ('wu_before_signup_form');
-  **do_action** `(line 111)` ('wu_after_signup_form');
-  **do_action** `(line 134)` ('wu_signup_footer'); ?>
-  **do_action** `(line 141)` ('admin_print_footer_scripts'); ?>

## ../views/signup/signup-nav-links.php

-  **apply_filters** `(line 30)` ('wu_signup_form_nav_links', array(

## ../views/signup/steps/step-default.php

-  **do_action** `(line 45)` ("wp_ultimo_registration_step_$signup->step"); ?>

## ../views/signup/steps/step-template.php

-  **apply_filters** `(line 130)` ('wu_step_template_display_header', true)) : ?>

## ../views/widgets/account/account-actions.php

-  **do_action** `(line 3)` ('wu_button_subscription_on_site'); ?>

## ../views/widgets/account/account-status.php

-  **apply_filters** `(line 89)` ('wu_account_integrated_method_title', $gateway ? $gateway->get_title() : ucfirst($subscription->gateway), $gateway, $subscription);
-  **do_action** `(line 97)` ('wu_account_integrated_method_actions_before', $gateway, $subscription);
-  **apply_filters** `(line 101)` ('wu_account_display_cancel_integration_link', true)) : ?>
-  **do_action** `(line 116)` ('wu_account_integrated_method_actions_after', $gateway, $subscription);
-  **do_action** `(line 145)` ('wu_account_integration_meta_box', $subscription, $plan); ?>
-  **apply_filters** `(line 158)` ('wu_display_payment_integration_buttons', true, $subscription)) : 
-  **apply_filters** `(line 181)` ("wu_gateway_integration_button_$gateway->id", $button, $content); ?>
-  **apply_filters** `(line 207)` ('wu_cancel_integration_text', __('Are you sure you want to cancel your current payment integration?', 'wp-ultimo'))); ?>,

## ../views/widgets/account/custom-domain.php

-  **do_action** `(line 44)` ('wu_custom_domain_after', $custom_domain);

## ../views/widgets/account/limits-and-quotas.php

-  **apply_filters** `(line 10)` ('wu_get_post_types', $post_types);
-  **apply_filters** `(line 25)` ('wu_post_count_status', array('publish'), $post_type);
-  **apply_filters** `(line 36)` ('wu_post_count', $post_count, $post_type_slug);

## ../views/widgets/coupon/advanced-options.php

-  **apply_filters** `(line 11)` ('wu_coupons_advanced_options_tabs', array(
-  **do_action** `(line 74)` ('wp_ultimo_coupon_advanced_options', $coupon);
-  **do_action** `(line 149)` ('wu_coupons_advanced_options_after_panels', $coupon);

## ../views/widgets/plan/advanced-options.php

-  **apply_filters** `(line 10)` ('wu_plans_advanced_options_tabs', array(
-  **apply_filters** `(line 262)` ('wu_get_post_types', $post_types);
-  **apply_filters** `(line 496)` ('all_plugins', get_plugins());
-  **do_action** `(line 530)` ('wu_allowed_plugins_form', $plugin_path, $item, $plan->id, is_array($plan->allowed_plugins) && in_array($plugin_path, $plan->allowed_plugins)); ?>
-  **apply_filters** `(line 556)` ('all_themes', wp_get_themes());
-  **do_action** `(line 574)` ('wu_allowed_themes_form', $theme_slug, $item, $plan->id, is_array($plan->allowed_themes) && in_array($theme_slug, $plan->allowed_themes)); ?>
-  **do_action** `(line 588)` ('wu_plans_advanced_options_after_panels', $plan);

## ../views/widgets/subscriptions/edit/coupon-code.php

-  **do_action** `(line 16)` ('wu_edit_subscription_coupon_code_meta_box', $subscription); ?>

## ../views/widgets/subscriptions/edit/integration.php

-  **do_action** `(line 29)` ('wu_integration_status_widget_actions', $subscription);
-  **do_action** `(line 47)` ('wu_edit_subscription_integration_meta_box', $subscription); ?>

## ../views/widgets/subscriptions/edit/setup-fee.php

-  **do_action** `(line 32)` ('wu_edit_subscription_setup_fee_meta_box', $subscription); ?>


* * *

Last generated: Wed Dec 09 2020 11:09:47 by [grunt-todo](https://github.com/leny/grunt-todo).
