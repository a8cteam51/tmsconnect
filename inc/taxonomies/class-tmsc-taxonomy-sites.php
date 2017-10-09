<?php

/**
 * Taxonomy for Sites.
 */
class Tmsc_Taxonomy_Sites extends Tmsc_Taxonomy {

	/**
	 * Name of the taxonomy.
	 *
	 * @var string
	 */
	public $name = 'sites';

	/**
	 * Object types for this taxonomy
	 *
	 * @var array
	 */
	public $object_types;


	/**
	 * Build the taxonomy object.
	 */
	public function __construct() {
		$this->object_types = array( 'tms_object' );

		parent::__construct();
	}

	/**
	 * Args passed to register taxonomy.
	 * Allows for a filter.
	 * @param array.
	 * @return array.
	 */
	public function register_taxonomy_args( $args = array() ) {
		return array(
			'labels' => array(
				'name'                  => __( 'Sites', 'tmsc' ),
				'singular_name'         => __( 'Sites', 'tmsc' ),
				'search_items'          => __( 'Search Sites', 'tmsc' ),
				'popular_items'         => __( 'Popular Sites', 'tmsc' ),
				'all_items'             => __( 'All Sites', 'tmsc' ),
				'parent_item'           => __( 'Parent Sites', 'tmsc' ),
				'parent_item_colon'     => __( 'Parent Sites', 'tmsc' ),
				'edit_item'             => __( 'Edit Sites', 'tmsc' ),
				'view_item'             => __( 'View Sites', 'tmsc' ),
				'update_item'           => __( 'Update Sites', 'tmsc' ),
				'add_new_item'          => __( 'Add New Sites', 'tmsc' ),
				'new_item_name'         => __( 'New Sites Name', 'tmsc' ),
				'add_or_remove_items'   => __( 'Add or remove Sites', 'tmsc' ),
				'choose_from_most_used' => __( 'Choose from most used Sites', 'tmsc' ),
				'menu_name'             => __( 'Sites', 'tmsc' ),
			),
			'hierarchical' => true,
			'rewrite' => array(
				'with_front' => false,
			),
		);
	}
}

$taxonomy_sites = new Tmsc_Taxonomy_Sites();
