<?php
/*
Plugin Name: CurrySearch - Bessere Suche - Better Search
Plugin URI:  https://github.com/CurrySoftware/CurrySearch-WordPress
Description: CurrySearch is an better cloud-based search for WordPress. It supports custom post types, advanced autocomplete, relevance based results and filter.
Version:     1.0.3
Author:      CurrySoftware GmbH
Author URI:  https://www.curry-software.com/en/
Text Domain: currysearch
Domain Path: /languages
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
include_once(CURRYSEARCH_PLUGIN_PATH.'includes/cs-constants.php');
// And Utils
include_once(CURRYSEARCH_PLUGIN_PATH.'includes/cs-utils.php');
// And the Sidebar widget
include_once(CURRYSEARCH_PLUGIN_PATH.'includes/cs-search-widget.php');
// And the Admin Page
include_once(CURRYSEARCH_PLUGIN_PATH.'includes/cs-admin.php');


register_activation_hook(__FILE__, array('CurrySearch', 'install'));
register_deactivation_hook( __FILE__, array('CurrySearch', 'uninstall' ));

// Hook to intercept queries
add_action('pre_get_posts', array('CurrySearch', 'intercept_query'));
add_action('wp_enqueue_scripts', array('CurrySearch', 'enqueue_scripts'));
add_action('widgets_init', array('CurrySearch', 'register_widgets'));
add_action('plugins_loaded', array('CurrySearch', 'load_textdomain'));
add_action('admin_menu', array('CurrySearch', 'init_menu'));

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
	 * Abstracts away the loading of options
	 */
	static function options() {
		if (!isset(CurrySearch::$options)) {
			CurrySearch::$options = get_option(CurrySearchConstants::OPTIONS, $default = false);
		}
		return CurrySearch::$options;
	}

	/**
     * Gets the api_key of the current wordpress installation.
	 */
	static function get_apikey() {
		return CurrySearch::options()['api_key'];
	}

	/**
	 * Gets the port to communicate to with the search system.
	 * This port is assigned after the first successfull indexing process.
	 * It lies between 36000 and 36099
	 *
	 * A nonexisten port indicates an unsuccessfull indexing process
	 */
	static function get_port() {
		return CurrySearch::options()['port'];
	}

	/**
	 * Gets the public portion (first 8bytes) of the api_key
	 * This is needed for autocomplete requests which are not handled by WordPress but
	 * the users browser.
	 */
	static function get_public_api_key() {
		$key = CurrySearch::get_apikey();
		return substr($key, 0, 16);
	}

	/**
	 * Time of last successfull indexing as determined by the CurrySearch System
	 */
	static function get_last_indexing() {
		return CurrySearch::options()['last_indexing'];
	}

	/**
	 * Language of the content as detected by the CurrySearch System
	 */
	static function get_detected_language() {
		return CurrySearch::options()['detected_language'];
	}

	/**
	 * Number of indexed documents as determined by the CurrySearch System
	 */
	static function get_indexed_documents() {
		return CurrySearch::options()['document_count'];
	}

	/**
	 * Registers all necessary widgets
	 *
	 * Currently only the Sidebar Search
	 */
	static function register_widgets() {
		register_widget('CS_SidebarSearch_Widget');
	}

	/**
	 * Registers the menu
	 */
	static function init_menu() {
		new CurrySearchAdminPage();
	}

	/**
	 *
	 */
	static function get_status() {
		$status = json_decode(CurrySearchUtils::call_ms(
			CurrySearchConstants::STATUS_ACTION, CurrySearch::get_apikey(), NULL));
		$options = CurrySearch::options();
		$options['detected_language'] = $status->detected_language;
		$options['document_count'] = $status->document_count;
		$date = new DateTime();
		$options['last_indexing'] = $date->setTimestamp($status->last_indexing->secs_since_epoch);
		CurrySearch::$options = $options;
		update_option(CurrySearchConstants::OPTIONS, $options, /*autoload*/'yes');
	}

	/**
	 * Register and Enqueue JavaScripts and CSSs
	 *
	 * These are mainly for query autocompletion
	 */
	static function enqueue_scripts() {
		wp_register_script('cs-autocomplete.min.js',
						   plugins_url('public/js/cs-autocomplete.min.js', __FILE__));
		wp_register_style("currysearch.css",
						  plugins_url('public/css/currysearch.css',  __FILE__));

		wp_enqueue_script('cs-autocomplete.min.js');
		wp_enqueue_style('currysearch.css');
	}

	static function load_textdomain() {
		load_plugin_textdomain('currysearch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	}

	/**
	 * A full indexing task.
	 *
	 * Gets all relevant Posts (published) and sends them to the API.
	 * Also registers tags and categories for later filtering
	 */
	static function full_indexing() {
		//Get ApiKey from options
		$key = CurrySearch::get_apikey();

		$settings = get_option(CurrySearchConstants::SETTINGS);
		$post_types = $settings['indexing_post_types'];

		//Get published posts count
		$published_posts = 0;
		foreach($post_types as $post_type) {
			$published_posts += (int)wp_count_posts($post_type)->publish;
		}

		//Initiate indexing
		CurrySearchUtils::call_ms(
			CurrySearchConstants::INDEXING_START_ACTION, $key, $published_posts );

		//Get all posts
		//https://codex.wordpress.org/Template_Tags/get_posts
		if (!isset($post_types) || empty($post_types)) {
			$postlist = array();
		} else {
			$postlist = get_posts(array(
				'numberposts' => -1,
				'post_type' => $post_types
			));
		}

		//Chunk them into parts of 100
		$post_chunks = array_chunk($postlist, 100);

		//Register some fields that will be searched
		//Title, body, post_tag and category
		CurrySearchUtils::call_ms(CurrySearchConstants::REGISTER_FIELDS_ACTION, $key, array(
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
				'autocomplete_source' => true
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
		CurrySearch::register_hierarchy($key, 'category');
		$taxos = array('post_tag', 'category');

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
					'id' => $post->ID,
					'raw_fields' =>  array (
						array('title', html_entity_decode( strip_tags( $post->post_title), ENT_QUOTES, 'UTF-8')),
						// We could leave the tags. Then we would have more information during indexing...
						array('body', html_entity_decode(
							strip_tags( wp_strip_all_tags( $post->post_content ) ), ENT_QUOTES, 'UTF-8' )),
						array('post_tag', implode(' ', $taxo_terms['post_tag'])),
						array('category', implode(' ', $taxo_terms['category']))
					)
				));
			}
			// Send chunk to the server
			CurrySearchUtils::call_ms(
				CurrySearchConstants::INDEXING_PART_ACTION, $key, array('posts' => $posts));
			$part_count += 1;
			$posts = array();
		}

		// Wrapping up... telling the API that we are finished
	 	$port = CurrySearchUtils::call_ms(
			CurrySearchConstants::INDEXING_DONE_ACTION, $key, array( 'parts' => $part_count ));

		$port = json_decode($port, true);

		$options = ['api_key' => $key, 'port' => $port];
		update_option(CurrySearchConstants::OPTIONS, $options, /*autoload*/'yes');
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
			CurrySearchUtils::call_ms(
				CurrySearchConstants::REGISTER_HIERARCHY_ACTION, $key, array( $taxo, $cs_terms));
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
		$api_key = json_decode($api_key, true);

		$options = ['api_key' => $api_key];
		add_option(CurrySearchConstants::OPTIONS, $options, /*deprecated parameter*/'', /*autoload*/'yes');

		$settings = ['indexing_post_types' => array('post', 'page')];
		add_option(CurrySearchConstants::SETTINGS, $settings, '', 'no');

		self::full_indexing();
	}

	/**
	 * Deactivation callback
	 *
     * Tell backend that we where deactivated, remove api-key from options!
	 */
	static function uninstall() {
		$port = CurrySearch::get_port();
		$key = CurrySearch::get_apikey();

		CurrySearchUtils::call_ms(CurrySearchConstants::DEACTIVATE_ACTION."/".$port, $key, NULL);

		delete_option(CurrySearchConstants::OPTIONS);
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
						  CurrySearch::get_apikey(),
						  CurrySearch::get_port(),
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
	/** Needed for communicating with the correct server */
	private $port;
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
	function __construct($api_key, $port, $hash, $query, $page) {
		$this->hash = $hash;
		$this->api_key = $api_key;
		$this->query = $query;
		$this->port = $port;
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
		$query_args['value'] = $this->query;
		$query_args['page'] = (int)$this->page;
		$query_args['page_size'] = (int)get_option('posts_per_page');

		// Start the request
		$response =	CurrySearchUtils::call_as(
				CurrySearchConstants::SEARCH_ACTION, $this->port, $this->api_key, $this->hash, $query_args);

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
