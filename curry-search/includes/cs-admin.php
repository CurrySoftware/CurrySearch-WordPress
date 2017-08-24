<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class CurrySearchAdminPage {

	function __construct() {
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

		include_once(CURRYSEARCH_PLUGIN_PATH.'admin/cs-setting-page.php');
	}
}
