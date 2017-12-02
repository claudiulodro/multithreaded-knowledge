<?php

class MK_Lesson {

	protected $title = '';
	protected $description = '';
	protected $resource_url = '';
	protected $resource_title = '';

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

	public function set_title( $title ) {
		$this->title = esc_html( $title );
	}

	public function get_title() {
		return $this->title;
	}

	public function set_description( $description ) {
		$this->description = wp_kses_post( $description );
	}

	public function get_description() {
		return $this->description;
	}

	public function set_resource_url( $url ) {
		$this->resource_url = esc_url( $url );
	}

	public function get_resource_url() {
		return $this->resource_url;
	}

	public function set_resource_title( $title ) {
		$this->resource_title = esc_html( $title );
	}

	public function get_resource_title() {
		return $this->resource_title;
	}

	protected function load() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Lessons::load( $this );
	}

	public function save() {
		MK_Lessons::save( $this );
	}
}