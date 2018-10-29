<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class CurrySearchConstants {

	const MANAGEMENT_URL = "https://ms.curry-search.com/";
	const STATS_URL = "https://my.curry-search.com/api/";
	// This has no slash at the end because we need to specify the port!
	const APPLICATION_URL = "https://as.curry-search.com";

	const REGISTER_ACTION  = "register";
	const DEACTIVATE_ACTION = "deactivate";
	const STATUS_ACTION = "tenant_status";
	const REGISTER_FIELDS_ACTION = "register_fields";
	const REGISTER_HIERARCHY_ACTION = "register_hierarchy";
	const INDEXING_START_ACTION = "full_indexing_start";
	const INDEXING_PART_ACTION = "full_indexing_part";
	const INDEXING_DONE_ACTION = "full_indexing_done";
	const TOKEN_ACTION = "token";

	const SEARCH_ACTION = "search";
	const QAC_ACTION = "autocomplete";

	const SEARCHFORMTRANSIENT = "currysearch_searchform";

	const OPTIONS  = "currysearch_options";

	const API_VERSION = "WP1.4";
	const MS_API_VERSION = "1";
	const AS_API_VERSION = "1";

	//SETTINGS
	const SETTINGS = "currysearch_settings";
	const SETTINGS_GROUP = 'cs_settings_group';
	const SETTINGS_INDEXING_SECTION = 'cs_settings_indexing_section';
	const SETTINGS_POST_TYPE_FIELD = 'cs_settings_post_type';
	const SETTINGS_AUTOCOMPLETE_FIELD = 'cs_inject_autocomplete';


	static function autocomplete_style($colors) {
		return "<style>
          .cs_ac_results{background-color:$colors[1];color:$colors[0];}
          .cs_ac_result:hover{background-color:$colors[3];color:$colors[2];}
          .cs_ac_active{background-color:$colors[3];color:$colors[2];}</style>";
	}

	static function elm_hook($url, $public_api_key, $session_hash, $input_id) {
		$autocomplete_id = 'curry-search-autocomplete'.uniqid();
		$app_id = 'app'.uniqid();
		$search_field = 'search_field'.uniqid();
		$suggest_list = 'suggest_list'.uniqid();
		return "<div id='$autocomplete_id'></div>

<script>
  function insertAfter(referenceNode, newNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
  }

  var flags = {
    url: '$url',
    public_api_key: '$public_api_key',
    session_hash: '$session_hash'
  }

  var $search_field = document.getElementById('$input_id');
  var $suggest_list = document.getElementById('$autocomplete_id');

  var search_rect = $search_field.getBoundingClientRect();
  var suggest_rect = $suggest_list.getBoundingClientRect();

  var height_diff = search_rect.bottom - suggest_rect.top;
  var hoz_diff = search_rect.left - suggest_rect.left;
  var width = search_rect.right - search_rect.left;
  var neg_height_diff = -height_diff;
  $suggest_list.style = 'z-index:100;width: '+ width + 'px;margin-top: ' + height_diff+'px;margin-left: '+hoz_diff+'px; position:absolute;';
  var $app_id = Elm.CurrySearchAutoComplete.embed($suggest_list, flags);

  $search_field.addEventListener('keydown', function(e) {
      if (e.keyCode == '38' ) {
          //Up Arrow
          $app_id.ports.search_box_arrow.send(-1);
          e.preventDefault();
      } else if (e.keyCode == '40') {
          //Down arrow
          $app_id.ports.search_box_arrow.send(1);
          e.preventDefault();
      } else {
          $app_id.ports.search_box_caret.send($search_field.selectionStart);
      };
  });

  $search_field.addEventListener('input', function(e) {
      $app_id.ports.search_box_input.send(e.target.value);
  });

  $app_id.ports.suggest_choice.subscribe(function(term) {
      $search_field.value = term;
      $search_field.focus();
   });
</script>";
	}
}



?>
