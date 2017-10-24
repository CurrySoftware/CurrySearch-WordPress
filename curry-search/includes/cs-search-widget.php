<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class CS_SidebarSearch_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'cs_searchsidebar_widget', // Base ID
			esc_html__( 'CurrySearch Sidebar Widget', 'currysearch' ), // Name
			array( 'description' => __( 'Displays the CurrySearch Searchbox', 'currysearch' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * In our case: A searchform with javascript hook + possible tag filters
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		/**
		 * To stay visually compatible with most themes we do not build our own search form.
		 * Rather, we inject necessary code into the themes standard search form
		 * That includes reading its id or injecting our id.
		 * The id is later used to attach the autocomplete js functionality
		 *
		 * Also, we add an extra field so that we can distinguish between CurrySearch and non-CurrySearch requests.
		 */

		//TODO: This has a bug with doubled value field. Example:
		// <input autocomplete="off" value="" id="curry-search-input" type="search" class="search-field" placeholder="Search &hellip;" value="" name="s" />
		$form = "";
		if (false === ($form === get_transient(CurrySearchConstants::SEARCHFORMTRANSIENT))) {

			$form = get_search_form(false);

			// Looks for an input field with the name="s" (the WordPress search parameter)
			if (preg_match('/<input[^>]*name="s"[^>]*\/>/', $form, $matches)) {
				// We found one
				$input_field = $matches[0];
				// Check if id is set
				if (preg_match('/id="([^"]*)"/', $input_field, $id_matches)) {
					$id = $id_matches[1];
				} else {
					//There is no id. We have to set our own
					$input_field =str_replace('<input', '<input id="curry-search-input"', $input_field);
					$id = 'curry-search-input';
				}
				// Check if value is set
				if (!strpos('value="', $input_field)) {
					// If no value is set, we will set our own to the current query
					$input_field =str_replace('<input', '<input value="'.get_search_query().'"', $input_field);
				}
				// turn browser autocomplete off. We ship our own
				$input_field =str_replace('<input', '<input autocomplete="off"', $input_field);
				$form = preg_replace('/<input[^>]*name="s"[^>]*\/>/', $input_field, $form);
			} else {
				// We didnt find any search form... this probably means the theme does not have a searchform.
				// Anyways.. We will just create a blank one
				$form = '<form method="get" action="' . esc_url( home_url( '/' ) ) . '">
  <input value"'.get_search_query().'" id="curry-search-input_blank" autocomplete="off" type="search" />
  <input value="'.esc_html__("Search", "currysearch").'" type="submit"></form>';
				$id = 'curry-search-input_blank';
			}
			// Keep it for one hour
			set_transient(CurrySearchConstants::SEARCHFORMTRANSIENT, $form, 3600);
		}

		echo $form;
		/**
		 * Form is created and echoed
		 **/

		$session_hash = CurrySearchUtils::get_session_hash();
		$public_api_key = CurrySearch::get_public_api_key();
		$url = CurrySearchConstants::APPLICATION_URL.':'.CurrySearch::get_port().'/'.CurrySearchConstants::QAC_ACTION;
		$input_id = $id;

		// js thats hooks up the elm code
		include_once(CURRYSEARCH_PLUGIN_PATH.'public/html/elm-hook.html');
		echo $args['after_widget'];

		// If we have an active query and filter results
		// Render these
		if (isset(CurrySearch::$cs_query) &&
			isset(CurrySearch::$cs_query->filter_results) &&
			is_array(CurrySearch::$cs_query->filter_results)) {

			$this->render_filters($args);
		}


	}


	function render_filters($args) {
		foreach (CurrySearch::$cs_query->filter_results as $taxo_slug=>$taxo_values) {
			$taxo = get_taxonomy($taxo_slug);
			if (!isset($taxo)) {
				// Taxonomy doesn't exist. This shouldn't happen.
				// Just in case... ignore it
				continue;
			}
			$term_ids = array_column($taxo_values, 'term');
			if (count($term_ids) == 0) {
				// Don't echo anything if there is nothing
				continue;
			}
			$wp_terms = array_column(get_terms(array('include' => $term_ids)), 'name', 'term_id');

			$title = sprintf(__('Filter search results by %s', 'currysearch'),  $taxo->label);
            echo $args['before_widget'];
			echo $args['before_title']. apply_filters('widget_title', $title) .$args['after_title'];
			echo "<ul class='cs_filter_list' style='list-style: none;'>";
			foreach ($taxo_values as $k=>$v) {
				if ($v["active"] == true) {
					global $wp;
					$current_url = esc_url(remove_query_arg( 'cs_'.$taxo_slug.'_'.$v['term'],
															 home_url( add_query_arg( null, null )) ));
					echo "<li class='cs_active_filter'><a href='". $current_url . "'>";
					echo "<input type='checkbox' checked='true' />";
					echo $wp_terms[$v['term']];
					echo "</a></li>";
				} else {
					global $wp;
					$current_url = esc_url(add_query_arg( 'cs_'.$taxo_slug.'_'.$v['term'] , $v['term'],
														  home_url( add_query_arg( null, null )) ));
					echo "<li><a href='". $current_url . "'>";
					echo "<input type='checkbox' />";
					echo $wp_terms[$v['term']];
					echo " (".$v['count'].")";
					echo "</a></li>";
				}
			}
			echo "</ul>";
            echo $args['after_widget'];
		}
	}


	/**
	 * Back-end widget form.
	 *
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( '', 'text_domain' );
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

}
