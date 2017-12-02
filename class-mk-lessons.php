<?php

class MK_Lessons {

	const POST_TYPE = 'lesson';
	const RESOURCE_TITLE_META = 'mk_resource_title';
	const RESOURCE_URL_META = 'mk_resource_url';

	// Just save the meta because everything else is saved during normal post save.
	public static function save( $lesson ) {
		$id = $lesson->get_id();
		if ( ! $id ) {
			return;
		}

		update_post_meta( $id, self::RESOURCE_TITLE_META, $lesson->get_resource_title() );
		update_post_meta( $id, self::RESOURCE_URL_META, $lesson->get_resource_url() );
	}

	public static function load( &$lesson ) {
		$id = $lesson->get_id();
		if ( ! $id ) {
			return;
		}

		$post = get_post( $id );
		$lesson->set_title( $post->post_title );
		$lesson->set_description( $post->post_content );
		$lesson->set_resource_title( get_post_meta( $id, self::RESOURCE_TITLE_META, true ) );
		$lesson->set_resource_url( get_post_meta( $id, self::RESOURCE_URL_META, true ) );
	}

	public static function get_parent_course_id( $lesson_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value=%d AND meta_key=%s LIMIT 1", $lesson_id, MK_Courses::SEQUENCE_IDS_META );
		$parent = $wpdb->get_var( $query );

		return $parent ? absint( $parent ) : 0;
	}

	public static function init() {
		add_action( 'add_meta_boxes',[ __CLASS__, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
	}

	public static function register_metaboxes() {
    	add_meta_box( 
    		'mk-course-select',
    		'Select Course', 
    		[ __CLASS__, 'render_course_select_metabox' ], 
    		self::POST_TYPE,
    		'side'
    	);

    	add_meta_box( 
    		'mk-lesson-resource',
    		'Resource', 
    		[ __CLASS__, 'render_resource_metabox' ], 
    		self::POST_TYPE
    	);

	}

	public static function save_metaboxes( $post_id ) {
		$lesson = new MK_Lesson( $post_id );

		if ( isset( $_POST[ self::RESOURCE_URL_META ] ) ) {
			$lesson->set_resource_url( $_POST[ self::RESOURCE_URL_META ] );
		}

		if ( isset( $_POST[ self::RESOURCE_TITLE_META ] ) ) {
			$lesson->set_resource_title( $_POST[ self::RESOURCE_TITLE_META ] );
		}

		if ( isset( $_POST[ 'mk_parent_course' ] ) ) {

			// Remove from old course.
			$old_course_id = self::get_parent_course_id( $post_id );
			if ( $old_course_id && absint( $_POST[ 'mk_parent_course' ] ) !== $old_course_id ) {
				$old_course = new MK_Course( $old_course_id );
				$old_course_ids = $old_course->get_sequence_ids();
				$replacement = array();
				foreach ( $old_course_ids as $old_course_id ) {
					if ( $old_course_id !== $post_id ) {
						$replacement[] = $old_course_id;
					}
				}
				$old_course->set_sequence_ids( $replacement );
				$old_course->save();
			}

			// Add to new course.
			if ( $_POST[ 'mk_parent_course' ] ) {
				$course = new MK_Course( $_POST[ 'mk_parent_course' ] );
				$course_ids = $course->get_sequence_ids();
				if ( ! in_array( $post_id, $course_ids ) ) {
					$course_ids[] = $post_id;
					$course->set_sequence_ids( $course_ids );
					$course->save();
				}
			}
		}

		$lesson->save();
	}

	public static function render_course_select_metabox( $post ) {
		$all_courses = get_posts( [ 
			'posts_per_page' => -1,
			'post_type' => MK_Courses::POST_TYPE,
		] );
		$selected = self::get_parent_course_id( $post->ID );
		?>
		<select name="mk_parent_course">
			<option <?php selected( $course_post->ID, $selected ) ?> value="0">Unassigned</option>
			<?php foreach ( $all_courses as $course_post ): ?>
				<option <?php selected( $course_post->ID, $selected ) ?> value="<?php echo $course_post->ID ?>"><?php echo esc_html( $course_post->post_title ) ?></option>
			<?php endforeach ?>
		</select>
		<?php
	}

	public static function render_resource_metabox( $post ) {
		$lesson = new MK_Lesson( $post->ID );
		?>
		Resource Title
		<input type="text" name="<?php echo self::RESOURCE_TITLE_META ?>" value="<?php echo esc_attr( $lesson->get_resource_title() ) ?>" style="width: 600px; margin-left: 2em"/><br />
		Resource URL 
		<input type="text" name="<?php echo self::RESOURCE_URL_META ?>" value="<?php echo esc_url( $lesson->get_resource_url() ) ?>" style="width: 600px; margin-left: 2em" /><br />
		<?php
	}

}
MK_Lessons::init();