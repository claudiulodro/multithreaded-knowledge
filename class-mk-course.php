<?php

class MK_Course {

	protected $id = 0;
	protected $name = '';
	protected $description = '';
	protected $sequence_ids = [];

	public function __construct( $id = 0 ) {
		if ( $id ) {
			$this->set_id( $id );
			$this->load();
		}
	}

	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	public function get_id() {
		return $this->id;
	}

	public function set_name( $name ) {
		$this->name = esc_html( $name );
	}

	public function get_name() {
		return $this->name;
	}

	public function set_description( $description ) {
		$this->description = wp_kses_post( $description );
	}

	public function get_description() {
		return $this->description;
	}

	public function set_sequence_ids( $ids ) {
		$this->sequence_ids = array_unique( array_filter( array_map( 'absint', (array) $ids ) ) );
	}

	public function get_sequence_ids() {
		return $this->sequence_ids;
	}

	protected function load() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Courses::load( $this );
	}

	public function save() {
		MK_Courses::save( $this );
	}

}