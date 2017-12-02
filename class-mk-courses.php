<?php

class MK_Courses {

	const POST_TYPE = 'course';
	const SEQUENCE_IDS_META = 'mk_sequence_ids';

	// Just save the meta because everything else is saved during normal post save.
	public static function save( $course ) {
		$id = $course->get_id();
		if ( ! $id ) {
			return;
		}

		delete_post_meta( $id, self::SEQUENCE_IDS_META );
		foreach ( $course->get_sequence_ids() as $sequence_id ) {
			add_post_meta( $id, self::SEQUENCE_IDS_META, $sequence_id, false );
		}
	}

	public static function load( &$course ) {
		$id = $course->get_id();
		if ( ! $id ) {
			return;
		}

		$post = get_post( $id );
		$course->set_name( $post->post_title );
		$course->set_description( $post->post_content );
		$course->set_sequence_ids( get_post_meta( $id, self::SEQUENCE_IDS_META, false ) );
	}

	public static function init() {
		add_action( 'add_meta_boxes',[ __CLASS__, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
	}

	public static function register_metaboxes() {
    	add_meta_box( 
    		'mk-sequence',
    		'Sequence', 
    		[ __CLASS__, 'render_sequence_metabox' ], 
    		self::POST_TYPE 
    	);
	}

	public static function save_metaboxes( $post_id ) {
		$course = new MK_Course( $post_id );

		if ( ! isset( $_GET[ self::SEQUENCE_IDS_META ] ) ) {
			return;
		}

		var_dump( $_GET[ self::SEQUENCE_IDS_META ] ); die(); //todo.
	}

	public static function render_sequence_metabox( $post ) {
		$course = new MK_Course( $post->ID );
		$sequence_ids = $course->get_sequence_ids();

		foreach ( $sequence_ids as $id ) {
			echo $id . '<br/>'; //todo.
		}
	}

}
MK_Courses::init();