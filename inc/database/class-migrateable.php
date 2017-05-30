<?php

namespace TMSC\Database;

/**
 * Base class for any imported content source
 */
abstract class Migrateable {

	/**
	 * The current WordPress object data being migrated
	 * @var stdClass
	 */
	protected $object = null;

	/**
	 * Holds the current raw data being processed from the migration source
	 * @var stdClass
	 */
	protected $raw = null;

	/**
	 * The processor used for this migrateable
	 * @var Processor
	 */
	protected $processor = null;

	/**
	 * Queue of statements that can be executed in bulk.
	 * This will be update statements and any other calls where return values are not needed.
	 * @var array
	 */
	private $stmt_queue = array();

	/**
	 * Array of attachment migrateables created during save process
	 * @var array
	 */
	protected $children = array();

	/**
	 * The name of this migrateable object
	 * @var string
	 */
	public $name;

	/**
	 * The WP type of migrateable object. Must be set by all implementing classes.
	 * @var string $type (term, post).
	 */
	public $type;

	/**
	 * ID mapping for migratable type.
	 * @var array.
	 */
	public $id = array(
		'term' => 'term_id',
		'post' => 'ID',
	);

	/**
	 * Constructor. Set the current object to an empty class and define the type.
	 */
	public function __construct( $type ) {
		$this->type = $type;
		$this->object = null;
	}

	/**
	 * Get legacy ID
	 * @return string
	 */
	abstract public function get_legacy_id();

	/**
	 * Get legacy CN
	 * @return string
	 */
	public function get_legacy_cn() {
		if ( ! empty( $this->raw->CN ) ) {
			return $this->raw->CN;
		}
	}

	/**
	 * This is a function that gets fired before the object is saved.
	 * @return void
	 */
	protected function before_save() {
		// nothing.
	}

	/**
	 * This is a function that gets fired after the object is saved, if it's saved successfully.
	 * This is the place to set processor meta data
	 * @return void
	 */
	protected function after_save() {
		$this->set_last_updated_hash();
	}

	/**
	 * Get the current object
	 */
	public function get_object() {
		return $this->object;
	}

	/**
	 * Checks a hash of the raw data against the stored object.
	 * @return boolean
	 */
	public function requires_update() {
		if ( ! empty( $this->object )  ) {
			$last_updated = $this->get_last_updated_hash();
			if ( ! empty( $last_updated ) && ! empty( $this->raw ) && tmsc_hash_data( $this->raw ) === $last_updated ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get the hash value of the last time this migrateable was updated.
	 */
	abstract public function get_last_updated_hash();

	/**
	 * Set a md5 hash of the raw data.
	 */
	abstract public function set_last_updated_hash();

	/**
	 * Save this object
	 */
	abstract public function save();

	/**
	 * Proxy for the appropriate update meta function for this object
	 */
	public function update_meta( $k, $v, $prev_value = null ) {
		$func = 'update_' . $this->type . '_meta';
		if ( function_exists( $func ) ) {
			$id = $this->id[ $this->type ];
			$this->stmt_queue[] = array( $func, array( $this->object->{$id}, $k, $v, $prev_value ) );
		}
	}

	/**
	 * Proxy for the appropriate add meta function for this object
	 */
	public function add_meta( $k, $v, $unique = false ) {
		$func = 'add_' . $this->type . '_meta';
		if ( function_exists( $func ) ) {
			$id = $this->id[ $this->type ];
			$this->stmt_queue[] = array( $func, array( $this->object->{$id}, $k, $v, $unique ) );
		}
	}

	public function update() {
		$func = 'wp_update' . $this->type;
		if ( function_exists( $func ) ) {
			$args = func_get_args();
			$this->stmt_queue[] = array( $func, $args );
		}
	}

	/**
	 * Proxy for the appropriate get meta function for this object
	 */
	public function get_meta( $k, $single = true ) {
		$id = $this->id[ $this->type ];
		return get_metadata( $this->type, $this->object->{$id}, $k, $single );
	}

	/**
	 * Execute the meta data functions in the queue.
	 * @return void
	 */
	public function flush_stmt_queue() {
		error_log(
			strtr(
				print_r($this->stmt_queue, true),
				array(
					"\r\n"=>PHP_EOL,
					"\r"=>PHP_EOL,
					"\n"=>PHP_EOL,
				)
			)
		);

		foreach ( $this->stmt_queue as $function ) {
			call_user_func_array( $function[0], $function[1] );
		}
		$this->stmt_queue = array();
	}

	/**
	 * Get the children of this object
	 * @return array
	 */
	public function get_children() {
		return $this->children;
	}

	/**
	 * Set processor
	 * @param $processor
	 */
	public function set_processor( $processor ) {
		$this->processor = $processor;
	}

	/**
	 * Set our raw data.
	 * @param $raw
	 */
	public function set_data( $raw ) {
		$this->object = null;
		$this->raw = $raw;
	}
}
