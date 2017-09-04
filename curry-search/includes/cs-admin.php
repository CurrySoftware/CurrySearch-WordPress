<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class CurrySearchAdminPage {

	function __construct() {

		$this->register_settings();

		add_submenu_page('options-general.php', 'CurrySearch', __('CurrySearch', 'curry-search'), 'manage_options', 'curry-search', array( $this, 'settings_page'));
	}


	function settings_page() {
		//must check that the user has the required capability
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
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
			update_option(CurrySearchConstants::SETTINGS, $settings, 'no');
		}

		include_once(CURRYSEARCH_PLUGIN_PATH.'admin/cs-setting-page.php');
	}

	function register_settings() {
		register_setting(CurrySearchConstants::SETTINGS_GROUP, CurrySearchConstants::SETTINGS);
		add_settings_section(CurrySearchConstants::SETTINGS_INDEXING_SECTION, __('Indexing Settings', 'curry-search'), array($this, 'indexing_settings'), 'curry-search');
		add_settings_field(CurrySearchConstants::SETTINGS_POST_TYPE_FIELD, __('Searchable Post Types', 'curry-search'), array($this, 'indexing_post_types'), 'curry-search', CurrySearchConstants::SETTINGS_INDEXING_SECTION);
	}

	function indexing_settings() {
		echo '';
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
