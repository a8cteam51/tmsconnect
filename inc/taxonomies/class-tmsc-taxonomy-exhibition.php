<?php

/**
 * Taxonomy for Exhibitions.
 */
class Tmsc_Taxonomy_Exhibition extends Tmsc_Taxonomy {

	/**
	 * Name of the taxonomy.
	 *
	 * @var string
	 */
	public $name = 'exhibition';

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
	 * Creates the taxonomy.
	 */
	public function create_taxonomy() {
		register_taxonomy( $this->name, $this->object_types, array(
			'labels' => array(
				'name'                  => __( 'Exhibitions', 'tmsc' ),
				'singular_name'         => __( 'Exhibition', 'tmsc' ),
				'search_items'          => __( 'Search Exhibitions', 'tmsc' ),
				'popular_items'         => __( 'Popular Exhibitions', 'tmsc' ),
				'all_items'             => __( 'All Exhibitions', 'tmsc' ),
				'parent_item'           => __( 'Parent Exhibition', 'tmsc' ),
				'parent_item_colon'     => __( 'Parent Exhibition', 'tmsc' ),
				'edit_item'             => __( 'Edit Exhibition', 'tmsc' ),
				'view_item'             => __( 'View Exhibition', 'tmsc' ),
				'update_item'           => __( 'Update Exhibition', 'tmsc' ),
				'add_new_item'          => __( 'Add New Exhibition', 'tmsc' ),
				'new_item_name'         => __( 'New Exhibition Name', 'tmsc' ),
				'add_or_remove_items'   => __( 'Add or remove Exhibitions', 'tmsc' ),
				'choose_from_most_used' => __( 'Choose from most used Exhibitions', 'tmsc' ),
				'menu_name'             => __( 'Exhibitions', 'tmsc' ),
			),
			'rewrite' => array(
				'with_front' => false,
			),
		) );
	}
}

$taxonomy_exhibition = new Tmsc_Taxonomy_Exhibition();