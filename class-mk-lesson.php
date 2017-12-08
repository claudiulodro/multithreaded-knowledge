<?php

/**
 * Sanatizes and compartmentalizes the data for a Lesson.
 */
class MK_Lesson {

	protected $id = 0;
	protected $course_id = 0;
	protected $title = '';
	protected $description = '';
	protected $resource_url = '';
	protected $resource_title = '';

	/**
	 * Constructor.
	 *
	 * @param int $id Lesson post ID
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
	 * @param int $id Post ID for the Lesson.
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
	 * Set the parent Course ID.
	 *
	 * @param int $id Post ID for the Course.
	 */
	public function set_course_id( $id ) {
		$this->course_id = absint( $id );
	}

	/**
	 * Get the parent Course ID.
	 *
	 * @return int
	 */
	public function get_course_id() {
		return $this->course_id;
	}

	/**
	 * Set the title.
	 *
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->title = esc_html( $title );
	}

	/**
	 * Get the title.,
	 *
	 * @return string $title
	 */
	public function get_title() {
		return $this->title;
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
		return apply_filters( 'the_content', $this->description );
	}

	/**
	 * Set the resource URL.
	 *
	 * @param string $url A full link to somewhere.
	 */
	public function set_resource_url( $url ) {
		$this->resource_url = esc_url( $url );
	}

	/**
	 * Get the resource URL.
	 *
	 * @return string
	 */
	public function get_resource_url() {
		return $this->resource_url;
	}

	/**
	 * Set the resource title.
	 *
	 * @param string
	 */
	public function set_resource_title( $title ) {
		$this->resource_title = esc_html( $title );
	}

	/**
	 * Get the resource title.
	 *
	 * @return string
	 */
	public function get_resource_title() {
		return $this->resource_title;
	}

	/**
	 * Load the data into this object. This must have an ID to do anything.
	 */
	protected function load() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Lessons::load( $this );
	}

	/**
	 * Save the data from this object. This must have an ID to do anything.
	 */
	public function save() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Lessons::save( $this );
	}
}