<?php

/**
 * Manages Lessons at a macro level.
 */
class MK_Lessons {

	const POST_TYPE = 'lesson';
	const RESOURCE_TITLE_META = 'mk_resource_title';
	const RESOURCE_URL_META = 'mk_resource_url';

	/**
	 * Save a Lesson. This only saves information that isn't managed automatically by WordPress.
	 *
	 * @param MK_Lesson $lesson
	 */
	public static function save( $lesson ) {
		$id = $lesson->get_id();
		if ( ! $id ) {
			return;
		}

		update_post_meta( $id, self::RESOURCE_TITLE_META, $lesson->get_resource_title() );
		update_post_meta( $id, self::RESOURCE_URL_META, $lesson->get_resource_url() );

		// Prevent recursion.
		remove_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
		wp_update_post( [
			'ID' => $id,
			'post_parent' => $lesson->get_course_id(),
		] );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
	}

	/**
	 * Load information into a Lesson object.
	 *
	 * @param MK_Lesson $lesson The information gets set by reference into this Lesson.
	 */
	public static function load( &$lesson ) {
		$id = $lesson->get_id();
		if ( ! $id ) {
			return;
		}

		$post = get_post( $id );
		$lesson->set_title( $post->post_title );
		$lesson->set_course_id( $post->post_parent );
		$lesson->set_description( $post->post_content );
		$lesson->set_resource_title( get_post_meta( $id, self::RESOURCE_TITLE_META, true ) );
		$lesson->set_resource_url( get_post_meta( $id, self::RESOURCE_URL_META, true ) );
	}

	/**
	 * Hook actions and filters.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save_metaboxes' ] );
		add_action( 'wp', [ __CLASS__, 'redirect_singles_to_parent_course' ], 99 );
		add_action( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'add_course_admin_column' ] );
		add_action( 'manage_posts_custom_column', [ __CLASS__, 'render_course_admin_column' ], 10, 2 );
	}

	/**
	 * Register the metaboxes.
	 */
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

	/**
	 * Save the metaboxes.
	 *
	 * @param int $post_id
	 */
	public static function save_metaboxes( $post_id ) {
		if ( ! current_user_can( 'edit_post' ) ) {
			return;
		}

		$lesson = new MK_Lesson( $post_id );

		if ( isset( $_POST[ self::RESOURCE_URL_META ] ) ) {
			$lesson->set_resource_url( $_POST[ self::RESOURCE_URL_META ] );
		}

		if ( isset( $_POST[ self::RESOURCE_TITLE_META ] ) ) {
			$lesson->set_resource_title( $_POST[ self::RESOURCE_TITLE_META ] );
		}

		if ( isset( $_POST['mk_parent_course'] ) ) {
			$old_course_id = $lesson->get_course_id();
			$lesson->set_course_id( $_POST['mk_parent_course'] );

			// Remove from old course.
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

	/**
	 * Render the Course Select metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function render_course_select_metabox( $post ) {
		$all_courses = get_posts( [ 
			'posts_per_page' => -1,
			'post_type' => MK_Courses::POST_TYPE,
			'post_status' => [ 'publish', 'draft' ],
		] );
		$selected = $post->post_parent;
		?>
		<select name="mk_parent_course">
			<option <?php selected( 0, $selected ) ?> value="0">Unassigned</option>
			<?php foreach ( $all_courses as $course_post ): ?>
				<option <?php selected( $course_post->ID, $selected ) ?> value="<?php echo $course_post->ID ?>"><?php echo esc_html( $course_post->post_title ) ?></option>
			<?php endforeach ?>
		</select>
		<?php
	}

	/**
	 * Render the lesson resource metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function render_resource_metabox( $post ) {
		$lesson = new MK_Lesson( $post->ID );
		?>
		Resource Title
		<input type="text" name="<?php echo self::RESOURCE_TITLE_META ?>" value="<?php echo esc_attr( $lesson->get_resource_title() ) ?>" style="width: 600px; margin-left: 2em"/><br />
		Resource URL 
		<input type="text" name="<?php echo self::RESOURCE_URL_META ?>" value="<?php echo esc_url( $lesson->get_resource_url() ) ?>" style="width: 600px; margin-left: 2em" /><br />
		<?php
	}

	/**
	 * Add a column to the Lessons screen with info about parent Course.
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function add_course_admin_column( $columns ) {
		$columns['course'] = 'Course';
		return $columns;
	}

	/**
	 * Populate column on Lessons screen with info about parent Course.
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public static function render_course_admin_column( $column, $post_id ) {
		if ( 'course' !== $column || self::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$lesson = new MK_Lesson( $post_id );
		$course_id = $lesson->get_course_id();
		if ( ! $course_id ) {
			return;
		}

		$course_link = get_edit_post_link( $course_id );
		$course_title = get_the_title( $course_id );
		?>
		<a href="<?php echo $course_link ?>"><?php echo $course_title ?></a>
		<?php
	}

	/**
	 * Redirect lessons to the parent course if a user tries to access the lesson directly.
	 */
	public static function redirect_singles_to_parent_course() {
		if ( is_single() && self::POST_TYPE === get_post_type() ) {
			$lesson = new MK_Lesson( get_the_ID() );
			if ( $lesson->get_course_id() ) {
				wp_safe_redirect( get_permalink( $lesson->get_course_id() ) . '#' . sanitize_title( $lesson->get_title() ), 301 );
				exit;
			}
		}
	}
}
MK_Lessons::init();