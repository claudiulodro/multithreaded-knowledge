<?php

/**
 * Manages Courses at a macro level.
 */
class MK_Courses {

	const POST_TYPE = 'course';
	const SEQUENCE_IDS_META = 'mk_sequence_ids';

	/**
	 * Save a Course. This only saves information that isn't managed automatically by WordPress.
	 *
	 * @param MK_Course $course
	 */
	public static function save( $course ) {
		$id = $course->get_id();
		if ( ! $id ) {
			return;
		}

		update_post_meta( $id, self::SEQUENCE_IDS_META, $course->get_sequence_ids() );
	}

	/**
	 * Load information into a Course object.
	 *
	 * @param MK_Course $course The information gets set by reference into this Course.
	 */
	public static function load( &$course ) {
		$id = $course->get_id();
		if ( ! $id ) {
			return;
		}

		$post = get_post( $id );
		$course->set_name( $post->post_title );
		$course->set_description( $post->post_content );
		$course->set_sequence_ids( (array) get_post_meta( $id, self::SEQUENCE_IDS_META, true ) );
	}

	/**
	 * Hook actions and filters.
	 */
	public static function init() {
		add_action( 'add_meta_boxes',[ __CLASS__, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
		add_filter( 'the_content', [ __CLASS__, 'render_course' ], 99 );
		add_shortcode( 'courses', [ __CLASS__, 'courses_shortcode' ] );
	}

	/**
	 * Register the metaboxes.
	 */
	public static function register_metaboxes() {
    	add_meta_box( 
    		'mk-sequence',
    		'Sequence', 
    		[ __CLASS__, 'render_sequence_metabox' ], 
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

		$course = new MK_Course( $post_id );

		if ( ! isset( $_POST[ self::SEQUENCE_IDS_META ] ) ) {
			return;
		}

		$ids = explode( ',', $_POST[ self::SEQUENCE_IDS_META ] );
		$course->set_sequence_ids( $ids );
		$course->save();
	}

	/**
	 * Render the sequence metabox used for setting the order of Tests and Lessons in the Course.
	 *
	 * @param WP_Post $post
	 */
	public static function render_sequence_metabox( $post ) {
		$course = new MK_Course( $post->ID );
		$sequence_ids = $course->get_sequence_ids();

		?>
		<style>
		.mk_sequence_order .sequence_item {
			line-height: 1em;
			padding-top: 1em;
			padding-bottom: 1em;
			margin-bottom: .5em;
			border: 1px solid silver;
			text-align: center;
			width: 600px;
			padding-left: 1em;
			padding-right: 1em;
			cursor: move;
		}

		.mk_sequence_order .sequence_item.lesson {
			background-color: #d1eef5;
		}

		.mk_sequence_order .sequence_item.test {
			background-color: #feffa6;
		}
		</style>

		<div class="mk_sequence_order">
			<?php 
			foreach ( $sequence_ids as $id ) :
				$post_type = get_post_type( $id );
				if ( MK_Tests::POST_TYPE === $post_type ) :
					$test = new MK_Test( $id );
					$text = $test->get_title();
					if ( $test->is_final_exam() ) :
						$text = '(Final Exam) ' . $text;
					endif;
				elseif ( MK_Lessons::POST_TYPE === $post_type ) :
					$lesson = new MK_Lesson( $id );
					$text = $lesson->get_title();
				else :
					continue;
				endif;
				?>
				<div class="sequence_item <?php echo esc_attr( $post_type ) ?>" data-id="<?php echo $id ?>">
					<?php echo $text ?>
				</div>
				<?php
			endforeach;
			?>
		</div>

		<input type="hidden" id="mk_sequence_input" name="<?php echo self::SEQUENCE_IDS_META ?>" value="<?php echo implode( ',', $sequence_ids ) ?>" />
		<?php

		add_action( 'admin_print_footer_scripts', function() {
			?>
			<script>
				( function( $ ) {
					var update_order = function() {
						// 500ms to let sortable finish UI effects and stuff.
						setTimeout( function() {
							var $items = $( '.mk_sequence_order .sequence_item' );
							var ids = [];

							$items.each( function(){
								ids.push( $( this ).data( 'id' ) );
							} );

							$( '#mk_sequence_input' ).val( ids.join() );
						}, 500 );
					}

					$( '.mk_sequence_order' ).sortable();
					$( '.mk_sequence_order .sequence_item' ).on( 'mouseup', update_order );
				} )( jQuery );
			</script>
			<?php
		} );
	}

	/**
	 * Render the Course in the content.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function render_course( $content ) {
		global $post;
		$id = get_the_ID();
		if ( self::POST_TYPE !== get_post_type( $id ) ) {
			return $content;
		}

		remove_all_filters( 'the_content' );
		$course = new MK_Course( $id );
		ob_start();
		include __DIR__ . '/templates/course.php';
		$content = ob_get_clean();
		$post->post_content = $content;
		return $content;
	}

	/**
	 * Handle the courses shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function courses_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'subject' => '',
		], $atts );

		$args = [
			'post_type' => self::POST_TYPE,
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
		];

		$subject = sanitize_title( $atts['subject'] );
		if ( $subject ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'subject',
					'field' => 'slug',
					'terms' => $subject,
				]
			];
		}

		$courses = get_posts( $args );
		ob_start();
		?>
		<ul class="mk_course_list">
			<?php foreach ( $courses as $course ): ?>
				<li><a href="<?php the_permalink( $course->ID ) ?>"><?php echo esc_html( $course->post_title ) ?></a></li>
			<?php endforeach ?>
		</ul>
		<?php

		return ob_get_clean();
	}
}
MK_Courses::init();