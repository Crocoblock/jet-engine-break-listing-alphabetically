<?php
/**
 * Plugin Name: JetEngine - break listing alphabetically
 * Plugin URI:  
 * Description: Allow to group listing items alphabetically
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

class Jet_Engine_Break_Listing_Alphabetically {

	public function __construct() {

		add_action(
			'elementor/element/jet-listing-grid/section_general/after_section_end',
			[ $this, 'register_controls' ]
		);

		add_action( 'jet-engine/listing/before-grid-item', [ $this, 'handle_item' ], 10, 2 );
		add_filter( 'jet-engine-break-alphabetically/prev-post', [ $this, 'posts_query_prev_post' ], 10, 3 );
		add_filter( 'jet-engine-break-alphabetically/render-first', [ $this, 'render_first' ], 10 );

	}

	public function register_controls( $widget ) {

		$widget->start_controls_section(
			'jet_break_section',
			array(
				'label' => __( 'Break Listing Alphabetically', 'jet-engine' ),
			)
		);

		$widget->add_control(
			'jet_break_alphabetically',
			array(
				'type'           => \Elementor\Controls_Manager::SWITCHER,
				'label'          => __( 'Enable', 'jet-engine' ),
				'render_type'    => 'template',
				'description'    => 'Please note! Listing should use Query from JetEngine Query Builder to make it to work',
				'style_transfer' => false,
			)
		);

		$widget->add_control(
			'jet_break_by_prop',
			array(
				'label'       => __( 'Break by field', 'jet-engine' ),
				'label_block' => true,
				'description' => 'Object propert yo get first letter from',
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'post_title',
				'groups'      => jet_engine()->listings->data->get_object_fields(),
				'condition'   => array(
					'jet_break_alphabetically' => 'yes',
				),
			)
		);

		$widget->add_control(
			'jet_break_markup',
			array(
				'label'   => __( 'HTML Markup of group title', 'jet-smart-filters' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => $this->get_default_markup(),
				'condition' => array(
					'jet_break_alphabetically' => 'yes',
				),
			)
		);

		$widget->end_controls_section();

	}

	public function posts_query_prev_post( $post, $query, $listing ) {

		if ( $post || $query->query_type !== 'posts' ) {
			return $post;
		}
		
		$page = $query->get_current_items_page();
		
		$args = $query->get_query_args();
		
		$args['paged'] = $page - 1;
		
		$posts_query = new \WP_Query( $args );
		
		$posts = $posts_query->get_posts();

		$post = $posts[ array_key_last( $posts ) ] ?? null;
		
		return $post;

	}

	public function render_first( $render ) {

		//do not render first header on JetEngine Load More
		if ( ! empty( $_REQUEST['handler'] ) && $_REQUEST['handler'] === 'listing_load_more' ) {
			return false;
		}

		//do not render first header on JetSmartFilters Load More pagination
		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'jet_smart_filters' && ! empty( $_REQUEST['props']['pages'] ) ) {
			return false;
		}

		return $render;

	}

	public function get_default_markup() {
		return '<h4 class="jet-engine-break-listing">%s</h4>';
	}

	public function handle_item( $post, $listing ) {

		if ( empty( $listing->query_vars['request']['query_id'] ) ) {
			return;
		}

		$settings = $listing->get_settings();

		if ( empty( $settings['jet_break_alphabetically'] ) ) {
			return;
		}

		$query = \Jet_Engine\Query_Builder\Manager::instance()->get_query_by_id( $listing->query_vars['request']['query_id'] );

		if ( ! $query ) {
			return;
		}

		$index    = jet_engine()->listings->data->get_index();
		$break_by = ! empty( $settings['jet_break_by_prop'] ) ? $settings['jet_break_by_prop'] : 'post_title';
		$markup   = ! empty( $settings['jet_break_markup'] ) ? $settings['jet_break_markup'] : $this->get_default_markup();

		if ( false === strpos( $markup, '%s' ) && false === strpos( $markup, '%1$s' ) ) {
			$markup = $this->get_default_markup();
		}

		if ( apply_filters( 'jet-engine-break-alphabetically/render-first', 0 === $index ) ) {
			$this->render_break( $post, $break_by, $markup );
		} else {

			$items     = $query->get_items();
			$prev_post = apply_filters( 'jet-engine-break-alphabetically/prev-post', $items[ $index - 1 ] ?? null, $query, $listing );

			$prev_prop    = $this->get_item_prop( $prev_post, $break_by );
			$current_prop = $this->get_item_prop( $post, $break_by );

			if ( $prev_prop
				&& $current_prop
				&& $prev_prop !== $current_prop 
			) {
				$this->render_break( $post, $break_by, $markup );
			}

		}

	}

	public function get_item_prop( $object, $prop ) {

		if ( ! isset( $object->$prop ) ) {
			return;
		}

		return mb_substr( $object->$prop, 0, 1 );

	}

	public function render_break( $object, $break_by, $markup ) {

		$current_prop = $this->get_item_prop( $object, $break_by );

		if ( ! $current_prop ) {
			return;
		}

		echo '<div style="width:100%; flex: 0 0 100%; grid-column: 1 / -1;">';
		printf( $markup, $current_prop );
		echo '</div>';

	}

}

new Jet_Engine_Break_Listing_Alphabetically();
