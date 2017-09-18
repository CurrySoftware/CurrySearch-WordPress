<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class CurrySearchConstants {

	const MANAGEMENT_URL = "https://ms.curry-search.com/";
	const APPLICATION_URL = "https://as.curry-search.com";

	const REGISTER_ACTION  = "register";
	const DEACTIVATE_ACTION = "deactivate";
	const STATUS_ACTION = "tenant_status";
	const REGISTER_FIELDS_ACTION = "register_fields";
	const REGISTER_HIERARCHY_ACTION = "register_hierarchy";
	const INDEXING_START_ACTION = "full_indexing_start";
	const INDEXING_PART_ACTION = "full_indexing_part";
	const INDEXING_DONE_ACTION = "full_indexing_done";

	const SEARCH_ACTION = "search";
	const QAC_ACTION = "autocomplete";

	const SEARCHFORMTRANSIENT = "currysearch_searchform";

	const OPTIONS  = "currysearch_options";
	const API_VERSION = "WP1.0.4";

	//SETTINGS
	const SETTINGS = "currysearch_settings";
	const SETTINGS_GROUP = 'cs_settings_group';
	const SETTINGS_INDEXING_SECTION = 'cs_settings_indexing_section';
	const SETTINGS_POST_TYPE_FIELD = 'cs_settings_post_type';
}

?>