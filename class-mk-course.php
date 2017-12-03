<?php

/**
 * Sanatizes and compartmentalizes the data for a Course.
 */
class MK_Course {

	protected $id = 0;
	protected $name = '';
	protected $description = '';
	protected $sequence_ids = [];

	/**
	 * Constructor.
	 *
	 * @param int $id Course post ID
	 */
	public function __construct( $id = 0 ) {
		if ( $id ) {
			$this->set_id( $id );
			$this->load();
		}
	}

	/**
	 * Set the ID.
	 *
	 * @param int $id Post ID for the Course.
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Get the ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the name.
	 *
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->name = esc_html( $name );
	}

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Set the description.
	 *
	 * @param string $description
	 */
	public function set_description( $description ) {
		$this->description = wp_kses_post( $description );
	}

	/**
	 * Get the description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Set the sequence of Lessons and Tests.
	 *
	 * @param array $ids Array of Lesson and Test post IDs
	 */
	public function set_sequence_ids( $ids ) {
		$this->sequence_ids = array_unique( array_filter( array_map( 'absint', (array) $ids ) ) );
	}

	/**
	 * Get the sequence of Lessons and Tests.
	 *
	 * @return array of post IDs.
	 */
	public function get_sequence_ids() {
		return $this->sequence_ids;
	}

	/**
	 * Load the data into this object. This must have an ID to do anything.
	 */
	protected function load() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Courses::load( $this );
	}

	/**
	 * Save the data from this object. This must have an ID to do anything.
	 */
	public function save() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Courses::save( $this );
	}
}