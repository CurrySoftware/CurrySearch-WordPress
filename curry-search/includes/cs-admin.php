<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class CurrySearchAdminPage {

	function __construct() {

		$this->register_settings();

		add_submenu_page('options-general.php', 'CurrySearch', __('CurrySearch', 'currysearch'), 'manage_options', 'currysearch', array( $this, 'settings_page'));
	}


	function settings_page() {
		//must check that the user has the required capability
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		if (isset($_GET['dissmiss_warning']) && check_admin_referer('dissmiss_warning')) {
			$settings = get_option(CurrySearchConstants::SETTINGS, $default = array());
			$settings['show_plan_warning'] = false;
			update_option(CurrySearchConstants::SETTINGS, $settings, 'no');
		}

		if (isset($_POST['reindex_requested']) && check_admin_referer('reindex')) {
			CurrySearch::full_indexing();
		}

		if (isset($_POST['settings_changed']) && check_admin_referer('settings_changed')) {
			$post_types = get_post_types(array('public' => true), 'objects', 'and');
			$activated_post_types = array();
			foreach($post_types as $post_type) {
				if (isset($_POST[$post_type->name])) {
					array_push($activated_post_types, $post_type->name);
				}
			}
			$settings = get_option(CurrySearchConstants::SETTINGS, $default = array());
			$settings['indexing_post_types'] = $activated_post_types;
			if (isset($_POST['inject_autocomplete'])) {
				$settings['inject_autocomplete'] = $_POST['inject_autocomplete'];
			} else  {
				unset($settings['inject_autocomplete']);
			}
			$settings['ac_colors'] = [$_POST['ac_text_col'], $_POST['ac_bkg_col'], $_POST['ac_active_text_col'], $_POST['ac_active_bkg_col']];
			
			update_option(CurrySearchConstants::SETTINGS, $settings, 'no');
		}

		include_once(CURRYSEARCH_PLUGIN_PATH.'admin/cs-setting-page.php');
	}

	function register_settings() {
		register_setting(CurrySearchConstants::SETTINGS_GROUP, CurrySearchConstants::SETTINGS);
		add_settings_section(CurrySearchConstants::SETTINGS_INDEXING_SECTION, __('Indexing Settings', 'currysearch'), array($this, 'indexing_settings'), 'currysearch');
		add_settings_field(CurrySearchConstants::SETTINGS_POST_TYPE_FIELD, __('Searchable Post Types', 'currysearch'), array($this, 'indexing_post_types'), 'currysearch', CurrySearchConstants::SETTINGS_INDEXING_SECTION);
		add_settings_field(CurrySearchConstants::SETTINGS_AUTOCOMPLETE_FIELD, __('Autocomplete', 'currysearch'), array($this, 'autocomplete_injection'), 'currysearch', CurrySearchConstants::SETTINGS_INDEXING_SECTION);
	}

	function indexing_settings() {
		echo '';
	}

	function autocomplete_injection() {
		$settings = get_option(CurrySearchConstants::SETTINGS, $default = false);
		if (isset($settings['inject_autocomplete'])) {
			echo '<label><input type="checkbox" name="inject_autocomplete" checked/>'.esc_html__('Active', 'currysearch').'</label><br /><hr />';
		} else {
			echo '<label><input type="checkbox" name="inject_autocomplete"/>'.esc_html__('Active', 'currysearch').'</label><br /><hr />';
		}
		echo '<h4>Style:</h4>';
		if (isset($settings['ac_colors'])) {
			echo '<label>'.esc_html__('Text Color', 'currysearch').'</label><input type="text" name="ac_text_col" value="'.$settings['ac_colors'][0].'" data-default-color="'.$settings['ac_colors'][0].'" class="cs-color-field" /><br />';
			echo '<label>'.esc_html__('Background Color', 'currysearch').'</label><input type="text" name="ac_bkg_col" value="'.$settings['ac_colors'][1].'" data-default-color="'.$settings['ac_colors'][1].'" class="cs-color-field" /><br />';
			echo '<label>'.esc_html__('Active Item: Text Color', 'currysearch').'</label><input type="text" name="ac_active_text_col" value="'.$settings['ac_colors'][2].'" data-default-color="'.$settings['ac_colors'][2].'" class="cs-color-field" /><br />';
			echo '<label>'.esc_html__('Active Item: Background Color', 'currysearch').'</label><input type="text" name="ac_active_bkg_col" value="'.$settings['ac_colors'][3].'" data-default-color="'.$settings['ac_colors'][3].'" class="cs-color-field" /><br />';
		} else {
			echo '<label>'.esc_html__('Text Color', 'currysearch').'</label><input type="text" name="ac_text_col" value="#000" data-default-color="#000" class="cs-color-field" /><br />';
			echo '<label>'.esc_html__('Background Color', 'currysearch').'</label><input type="text" name="ac_bkg_col" value="#DDD" data-default-color="#DDD" class="cs-color-field" /><br />';
			echo '<label>'.esc_html__('Active Item: Text Color', 'currysearch').'</label><input type="text" name="ac_active_text_col" value="#555" data-default-color="#555" class="cs-color-field" /><br />';
			echo '<label>'.esc_html__('Active Item: Background Color', 'currysearch').'</label><input type="text" name="ac_active_bkg_col" value="#EEE" data-default-color="#EEE" class="cs-color-field" /><br />';
		}
			
		
	}

	function indexing_post_types() {
		$settings = get_option(CurrySearchConstants::SETTINGS, $default = false);

		if ($settings===false) {
			$active_types = array();
		} else {
			$active_types = $settings['indexing_post_types'];
		}

		$post_types = get_post_types(array('public' => true), 'objects', 'and');
		echo '<ul>';
		foreach($post_types as $post_type) {
			echo '<li><label>';
			if (in_array($post_type->name, $active_types)) {
				echo '<input type="checkbox" checked="true" name="'.$post_type->name.'"/>'.$post_type->label;
			} else {
				echo '<input type="checkbox" name="'.$post_type->name.'"/>'.$post_type->label;
			}
			echo '</label></li>';
		}
		echo '</ul>';
		
	}
}
