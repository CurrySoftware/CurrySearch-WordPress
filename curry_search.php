<?php
/*
Plugin Name: CurrySearch Official
Plugin URI:  https://wp.curry-search.com
Description: Interface to the CurrySearch System. Provides fast and reliable results with category and tag filters.
Version:     0.1
Author:      CurrySoftware GmbH
Author URI:  https://www.curry-software.com
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

CurrySearch Official Plugin for WordPess
Copyright (C) 2017 CurrySoftware GmbH

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get Plugin Path to
define('CURRYSEARCH_PLUGIN_PATH', plugin_dir_path(__FILE__));
// Load Constants
include_once(CURRYSEARCH_PLUGIN_PATH.'includes/cs_constants.php');
// And Utils
include_once(CURRYSEARCH_PLUGIN_PATH.'includes/cs_utils.php');


register_activation_hook(__FILE__, array('CurrySearch', 'install'));
register_deactivation_hook( __FILE__, array('CurrySearch', 'uninstall' ));

// Hook to intercept queries
add_action("pre_get_posts", array("CurrySearch", "intercept_query"));
add_action("wp_enqueue_scripts", array("CurrySearch", "enqueue_scripts"));
add_action("widgets_init", array("CurrySearch", "register_widgets"));


/**
 * The central static class in this plugin. All methods are static, nothing is to be instantiated!
 * It's tasks are plugin registration, indexing, keeping the index up to date
 * and intercepting search requests that are meant for the CurrySearch API.
 *
 */
class CurrySearch {

	public static $cs_query;

	static $options;

	/**
     * Gets the api_key of the current wordpress installation.
	 */
	static function get_apikey() {
		if (!isset(CurrySearch::$options)) {
			CurrySearch::$options = get_option(CurrySearchConstants::OPTIONS, $default = false);
		}
		$key = CurrySearch::$options["api_key"];
		return $key;
	}

	/**
	 * Gets the port to communicate to with the search system.
	 * This port is assigned after the first successfull indexing process.
	 * It lies between 36000 and 36099
	 *
	 * A nonexisten port indicates an unsuccessfull indexing process
	 */
	static function get_port() {
		if (!isset(CurrySearch::$options)) {
			CurrySearch::$options = get_option(CurrySearchConstants::OPTIONS, $default = false);
		}
		if (isset(CurrySearch::$options["port"])) {
			$port = CurrySearch::$options["port"];
			return $port;
		}
		return null;
	}

	/**
	 * Gets the public portion (first 8bytes) of the api_key
	 * This is needed for autocomplete requests which are not handled by WordPress but
	 * the users browser.
	 */
	static function get_public_api_key() {
		$key = get_option(CurrySearchConstants::APIKEY_OPTION, $default = false);
		return substr($key, 0, 16);
	}

	/**
	 * Registers all necessary widgets
	 *
	 * Currently only the Sidebar Search
	 */
	static function register_widgets() {
		register_widget("CS_SidebarSearch_Widget");
	}

	/**
	 * Register and Enqueue JavaScripts and CSSs
	 *
	 * These are mainly for query autocompletion
	 */
	static function enqueue_scripts() {
		wp_register_script("currysearch_autocomplete.js",
						   plugins_url("assets/currysearch_autocomplete.js", __FILE__));
		wp_register_style("currysearch.css",
						  plugins_url("assets/currysearch.css",  __FILE__));

		wp_enqueue_script("currysearch_autocomplete.js");
		wp_enqueue_style("currysearch.css");
	}

	/**
	 * A full indexing task.
	 *
	 * Gets all relevant Posts (published) and sends them to the API.
	 * Also registers tags and categories for later filtering
	 */
	static function full_indexing() {
		//Get ApiKey from options
		$key = get_option(CurrySearchConstants::APIKEY_OPTION, $default = false);

		//Get published posts count
		$count_posts = wp_count_posts();
		$published_posts = (int)$count_posts->publish;

		//Initiate indexing
		CurrySearchUtils::ms_call_post(
			CurrySearchConstants::INDEXING_START_URL, $key, $published_posts );

		//Get all posts
		//https://codex.wordpress.org/Template_Tags/get_posts
		$postlist = get_posts(array(
			'numberposts' => -1
		));

		//Chunk them into parts of 100
		$post_chunks = array_chunk($postlist, 100);

		//Register some fields that will be searched
		//Title, body, post_tag and category
		CurrySearchUtils::ms_call_post(CurrySearchConstants::REGISTER_FIELDS_URL, $key, array(
			array(
				'name' => 'title',
				'data_type' => 'Text',
				'field_type' => 'Search',
				'autocomplete_source' => true
			),
			array(
				'name' => 'body',
				'data_type' => 'Text',
				'field_type' => 'Search',
				'autocomplete_source' => false
			),
			array(
				'name' => 'post_tag',
				'data_type' => 'Number',
				'field_type' => 'Filter',
				'autocomplete_source' => false
			),
			array(
				'name' => 'category',
				'data_type' => 'Number',
				'field_type' => 'HierarchyFilter',
				'autocomplete_source' => false
			))
		);

		// Register categories
		CurrySearch::register_hierarchy($key, "category");
		$taxos = array("post_tag", "category");

		$part_count = 0;
		//Index posts chunk by chunk
		foreach ($post_chunks as $chunk) {
			$posts = array();
			// For each post
			foreach ($chunk as $post) {
				$taxo_terms = array();
				// Get all its taxo terms (category and tag)
				foreach ($taxos as $taxo) {
					$taxo_terms[$taxo] = array();
					$wp_terms = get_the_terms( $post->ID, $taxo );
					if (is_array($wp_terms) && count($wp_terms) > 0 ) {
						foreach( $wp_terms as $term) {
							array_push($taxo_terms[$taxo], $term->term_id);
						}
					}
				}

				// Add its title, contents and taxo terms to the processed chunk
				array_push($posts, array(
					"id" => $post->ID,
					//TODO: Check if strip_tags is ok
					//TODO: Check if html_entity_decode is ok
					"raw_fields" =>  array (
						array("title", html_entity_decode( strip_tags( $post->post_title), ENT_QUOTES, "UTF-8")),
						array("body", html_entity_decode(
							strip_tags( wp_strip_all_tags( $post->post_content ) ), ENT_QUOTES, "UTF-8" )),
						//The need of implode for this sucks!
						//Fix it!
						array("post_tag", implode(" ", $taxo_terms["post_tag"])),
						array("category", implode(" ", $taxo_terms["category"]))
					)
				));
			}
			// Send chunk to the server
			CurrySearchUtils::ms_call_post(
				CurrySearchConstants::INDEXING_PART_URL, $key, array("posts" => $posts));
			$part_count += 1;
			$posts = array();
		}

		// Wrapping up... telling the API that we are finished
	 	$port = CurrySearchUtils::ms_call_post(
			CurrySearchConstants::INDEXING_DONE_URL, $key, array( 'parts' => $part_count ));

		$options = ['api_key' => $api_key, 'port' => $port];
		update_option(CurrySearchConstants::APIKEY_OPTION, $options, /*autoload*/'yes');

		add_action( 'admin_notices', array('CurrySearch', 'successfull_indexing_notice'));
	}


	/**
	 * Callback for a successfull indexing
	 **/
	static function successfull_indexing_notice() {
		?>
		<div class="notice notice-success is-dismissible">
        	<p>Successfully indexed all your content to the CurrySearch API! You can use the CurrySearch Widget now.</p>
		</div>
		<?php
	}

	/**
	 * This function is called from indexing.
	 * It registers a hierarchical taxonomy with the API.
	 */
	static function register_hierarchy($key, $taxo) {
		// Only register it as hierarchical if it really IS hierarchical
		if (is_taxonomy_hierarchical($taxo)) {
			$terms = get_terms( array(
				'taxonomy' => $taxo,
				'hide_empty' => false,
			));

			$cs_terms = array();
			// Get all (term_id, Option<parent_term_id>) pairs
			foreach($terms as $term) {
				if (isset($term->parent) && ($term->parent != 0)) {
					array_push($cs_terms, array($term->term_id, $term->parent));
				} else {
				    array_push($cs_terms, array($term->term_id, null));
				}
			}

			// And send them to the api.
			CurrySearchUtils::ms_call_post(
				CurrySearchConstants::REGISTER_HIERARCHY_URL, $key, array( $taxo, $cs_terms));
		}
	}


	/**
	 * Activation callback
	 *
	 * First it registers the index to the endpoint, then it indexes all posts.
	 */
	static function install() {
		//register index
		$api_key = CurrySearchUtils::call_ms(CurrySearchConstants::REGISTER_ACTION, NULL, NULL);

		$options = ['api_key' => $api_key];
		add_option(CurrySearchConstants::APIKEY_OPTION, $options, /*deprecated parameter*/'', /*autoload*/'yes');

		self::full_indexing();
	}

	/**
	 * Deactivation callback
	 *
     * Tell backend that we where deactivated, remove api-key from options!
	 */
	static function uninstall() {
		delete_option(CurrySearchConstants::APIKEY_OPTION);
	}

	/**
	 * Callback for 'pre_get_posts'.
	 * Checks if we want to handle this particular search request
	 * If so, we create a 'CurrySearchQuery' object which will handle all the rest!
	 *
	 * Be sure to check out 'CurrySearchQuery' for details!
	 */
	static function intercept_query($query) {
		if ($query->is_search() && isset($query->query['s'])) {
			//Intercept!
			self::$cs_query =
					  new CurrySearchQuery(
						  self::get_apikey(),
						  CurrySearchUtils::get_session_hash(),
						  $query->query['s'],
						  $query->get('paged'));
		}
	}
}


/**
 * The 'CurrySearchQuery' class handles all processes regarding query execution.
 *
 * During construction it registers some hooks (see CurrySearchQuery::setup())
 * and calls the endpoint to execute the query.
 *
 * These hooks have two basic purposes:
 * 1. Make sure, that WordPress does not execute the original query.
 * This is not strictly necessary but reduces pressure on the database and page load time.
 *
 * This is handled by CurrySearchQuery::posts_request and CurrySearchQuery::found_posts_query
 *
 * 2. Retrieve the ids of all relevant posts for the specified page from the endpoint.
 * Then hand all these ids to a WP_Query object, execute it and return the resulting posts.
 *
 * API communication happens in the constructor (see CurrySearchQuery::execute).
 * The posts are fetched from the database and returned to the usual WordPress flow in 'CurrySearch::posts_results'
 *
 * It is worth noting, that the endpoint only returns the necessary ids (the ones visible on the specified page)
 * together with a total result count.
 * To enable pagination nevertheless, we also hook 'found_posts' and return this total count.
 *
 *
 * As soon as we injected our results we can tear down all the hooks again
 * to allow subsequent querys to run without disturbance.
 */
class CurrySearchQuery{

	/** Needed for communication with the api */
	private $api_key;
	/** stores the original query string */
	private $query;
	/** contains the ids of all relevant posts */
	private $query_result;
	/** Total number of relevant posts for this query */
	private $result_count;
	/** The page we are currently on. Starts at 1!*/
	private $page;
	/** The session hash for this request*/
	private $hash;

	/** Filter results needed by the Sidebar Widget **/
	public $filter_results;

	/** Hierarchy results needed by the Sidebar Widget **/
	public $hierarchy_results;

	/**
	 * Constructor.
     * Sets up all the hooks and gets the query result from the api-endpoint.
     */
	function __construct($api_key, $hash, $query, $page) {
		$this->hash = $hash;
		$this->api_key = $api_key;
		$this->query = $query;
		if ($page === 0) {
			$this->page =  1;
		} else {
			$this->page = $page;
		}
		// Setup hooks
		$this->setup();
		// Run query against API
		$this->execute();
	}

	/**
	 * Sets up all necessary hooks.
	 */
	function setup() {
		add_filter("posts_request", array( $this, "posts_request"));
		add_filter("found_posts", array( $this, "found_posts"));
		add_filter("posts_results", array( $this, "posts_results"));
		add_filter("found_posts_query", array( $this, "found_posts_query"));
	}

	/**
	 * Tears down all the necessary hooks again for subsequent queries to be undisturbed from our plugin!
	 */
	function tear_down() {
		remove_filter("posts_request", array( $this, "posts_request"));
		remove_filter("found_posts", array( $this, "found_posts"));
		remove_filter("posts_results", array( $this, "posts_results"));
		remove_filter("found_posts_query", array( $this, "found_posts_query"));
	}

	/**
	 * Call the api-endpoint and retrieve relevant posts and total result count
     */
	function execute() {
		$query_args = array();

		// Get read the filterarguments
		// For non hierarchical taxonomies more than one term can be filtered
		foreach($_GET as $k=>$v) {
			if (preg_match('/cs_(\w+)_(\d+)/', $k, $matches)) {
				// If we already have filters for that taxonomy
				if (isset($query_args['filter'][$matches[1]]) &&
					is_array($query_args['filter'][$matches[1]])) {
					// Add the term
					array_push($query_args['filter'][$matches[1]], $v);
				} else {
					// Otherwise create a new array
					$query_args['filter'][$matches[1]] = array($v);
				}
			}
		}
		$query_args["value"] = $this->query;
		$query_args["page"] = (int)$this->page;
		$query_args["page_size"] = (int)get_option('posts_per_page');

		// Start the request
		$response = wp_remote_retrieve_body(
			CurrySearchUtils::as_call_post(
				CurrySearchConstants::SEARCH_URL, $this->api_key, $this->hash, $query_args)
		);

		// Parse the response
		$decoded = json_decode($response, true);
		$this->query_result = $decoded['posts'];
		$this->result_count = $decoded['estimated_count'];
		$this->filter_results = $decoded['filters'];
		$this->hierarchy_results = $decoded['hierarchies'];
	}


		/**
	 * Callback for 'posts_results'.
	 *
	 * Inject our results into the original WP_Query.
	 * This is done by creating a new WP_Query that only retrieves the relevant posts by id.
	 * Then return these posts.
	 */
	function posts_results($posts) {
		//last hook for this request. Remove actions
		$this->tear_down();
		$query = new WP_Query(array(
			'post__in' => $this->query_result
		));
		return $this->query_result;
	}

	/**
	 * Callback for 'found_posts'.
	 *
	 * Injects our result_count into the original WP_Query.
	 */
	function found_posts($found_posts=0) {
		return $this->result_count;
	}

	/**
	 * Callback for 'posts_request',
	 *
	 * Prevent WordPress from executing its query.
	 */
	static function posts_request($sqlQuery) {
		return '';
	}

	/**
	 * Callback for 'found_posts_query'
	 *
	 * Prevent WordPress from executing its query.
	 */
	static function found_posts_query($query) {
	    return '';
	}

}
