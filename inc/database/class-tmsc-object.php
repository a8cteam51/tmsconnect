<?php

namespace TMSC\Database;

/**
 * Base class for any imported post
 */
class TMSC_Object extends \TMSC\Database\Migrateable {

	/**
	 * The type of migrateable object. Must be set by all implementing classes.
	 * @var string
	 */
	public $type = 'post';

	/**
	 * Constructor. Set this as a post migrateable.
	 */
	public function __construct() {
		parent::__construct( $this->type );
	}

	/**
	 * Get legacy ID
	 * @return int
	 *
	 */
	public function get_legacy_id() {
		if ( ! empty( $this->raw->ObjectID ) ) {
			return $this->raw->ObjectID;
		}
	}

	/**
	 * Get the last updated data hash.
	 * @return mixed string|false
	 */
	public function get_last_updated_hash() {
		if ( ! empty( $this->object ) ) {
			return $this->get_meta( 'tmsc_last_updated', true );
		}
		return false;
	}

	/**
	 * Set the last updated data hash.
	 */
	public function set_last_updated_hash() {
		if ( ! empty( $this->raw ) ) {
			$this->update_meta( 'tmsc_last_updated', tmsc_hash_data( $this->raw ) );
		}
	}

	/**
	 * Get excerpt
	 * @return html
	 */
	public function get_excerpt(){
		return apply_filters( "tmsc_set_{$this->processor_type}_excerpt", '', $this->raw );
	}

	/**
	 * Get title
	 * @return string
	 */
	public function get_title(){
		$title = ( empty( $this->raw->Title ) ) ? $this->raw->RawTitle : $this->raw->Title;
		return apply_filters( "tmsc_set_{$this->processor_type}_title", $title, $this->raw );
	}

	/**
	 * Get post author.
	 * @return int ID of author user.
	 */
	public function get_post_author() {
		// Use the admin user by default
		return apply_filters( "tmsc_set_{$this->processor_type}_author", 1, $this->raw );
	}

	/**
	 * Get date of publication
	 * @return int unix timestamp
	 */
	public function get_pubdate(){
		return apply_filters( "tmsc_set_{$this->processor_type}_pubdate", time(), $this->raw );
	}

	/**
	 * Get body
	 * @return HTML
	 */
	public function get_body(){
		return apply_filters( "tmsc_set_{$this->processor_type}_body", '', $this->raw );
	}

	/**
	 * Get post slug
	 * @return string post slug
	 */
	public function get_post_name() {
		return sanitize_title_with_dashes( $this->get_title() );
	}

	/**
	 * Get post parent
	 * @return integer parent post id
	 */
	public function get_post_parent() {
		return 0;
	}

	/**
	 * Get post type
	 * @return string
	 */
	public function get_post_type() {
		return apply_filters( "tmsc_set_{$this->processor_type}_post_type", 'tms_object' );
	}

	/**
	 * Get post status
	 * @return string
	 */
	public function get_post_status() {
		return 'publish';
	}

	/**
	 * Save the final post status
	 * @return string
	 */
	public function save_final_object_status() {
		$this->object->post_status = $this->get_post_status();
		$this->update( $this->object );
	}

	/**
	 * Get post object
	 * @return WP_Post
	 */
	public function get_post() {
		return $this->get_object();
	}

	/**
	 * Update the post (used in after_save usually)
	 */
	public function update() {
		wp_update_post( $this->object );
	}

	/**
	 * Load an existing post if it exists.
	 */
	public function load_existing() {
		$this->object = null;
		// Check for existing post by legacy ID
		$legacy_id = $this->get_legacy_id();
		if ( ! empty( $legacy_id ) ) {
			$existing_post = tmsc_get_object_by_legacy_id( $legacy_id );
			if ( ! empty( $existing_post ) ) {
				$this->object = $existing_post;
			}
		}
	}

	/**
	 * Save this post
	 * @return boolean true if successfully saved
	 */
	public function save() {
		$this->before_save();

		$this->load_existing();
		if ( $this->requires_update() ) {

			$this->object = $this->save_post();

			if ( empty( $this->object->ID ) ) {
				return false;
			}

			// Update queue with post meta.
			$this->save_meta_data();

			// Save term relationships
			$this->save_term_relationships();

			// Save related_objects
			$this->save_related_objects();

			// Save Media Attachments
			$this->save_media_attachments();

			// Update status.
			$this->after_save();

			return true;
		}
		return false;
	}

	/**
	 * Save post
	 * @return WP_Object Object
	 */
	public function save_post() {
		$date = date( 'Y-m-d H:i:s', $this->get_pubdate() );
		$post = array(
			'ID' => empty( $this->object->ID ) ? 0 : $this->object->ID,
			'post_title' => $this->get_title(),
			'post_status' => 'migrating',
			'post_author' => $this->get_post_author(),
			'post_date' => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
			'post_type' => $this->get_post_type(),
			'post_content' => $this->get_body(),
			'post_excerpt' => $this->get_excerpt(),
			'post_name' => $this->get_post_name(),
			'comment_status' => 'closed',
		);

		$post_id = wp_insert_post( $post );
		if ( ! empty( $post_id ) ) {
			return get_post( $post_id );
		}
		return false;
	}

	/**
	 * Save post meta data
	 * @return void
	 */
	public function save_meta_data() {
		if ( ! empty( $this->object->ID ) ) {
			// Get our meta data mapping and iterate through it.
			foreach ( $this->get_meta_keys() as $key => $db_field ) {
				$this->update_meta( $key, $this->raw->$db_field );
			}
		}
		return;
	}

	/**
	 * Map our raw data keys to our meta keys
	 * @return array. An array of post meta keys and corresponding db fields in our raw data.
	 */
	public function get_meta_keys() {
		return apply_filters( "tmsc_{$this->processor_type}_meta_keys", array() );
	}

	/**
	 * Save object terms
	 * @return void
	 */
	public function save_term_relationships() {
		if ( ! empty( $this->object->ID ) && ! empty( $this->raw->ObjectID ) ) {
			$terms = $this->processor->get_related_terms( $this->raw->ObjectID );
			foreach ( $terms as $taxonomy => $term_ids ) {
				wp_set_object_terms( $this->object->ID, $term_ids, $taxonomy );
			}
		}
	}

	/**
	 * Save related objects.
	 */
	public function save_related_objects() {
		if ( ! empty( $this->object->ID ) && ! empty( $this->raw->ObjectID ) ) {
			// Store with migratable type as key.
			$related_ids = $this->processor->get_related_objects( $this->raw->ObjectID );
			foreach ( $related_ids as $rid ) {

			}
		}
	}

	/**
	 * Save media attachments.
	 */
	public function save_media_attachments() {
		if ( ! empty( $this->object->ID ) && ! empty( $this->raw->ObjectID ) ) {
			$this->raw->wp_parent_id = $this->object->ID;
			// Store with migratable type as key.
			$this->children['Media'] = $this->raw;
		}
	}

	/**
	 * Save children migratables
	 * This migratable expects objects and media as children.
	 */
	public function migrate_children(){
		foreach( $this->children as $migratable_type => $raw_data ) {
			$child_processor = \TMSC\TMSC::instance()->get_processor( $migratable_type );
			$child_processor->set_parent_object( $raw_data );
			$child_processor->run();
			tmsc_stop_the_insanity();
		}
		return true;
	}
}
