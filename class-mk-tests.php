<?php

/**
 * Manages Tests at a macro level.
 */
class MK_Tests {

	const POST_TYPE = 'test';
	const QUESTIONS_META = 'mk_test_questions';
	const FINAL_EXAM_META = 'mk_is_final_exam';
	const MAX_NUM_QUESTIONS = 20;

	/**
	 * Save a Test. This only saves information that isn't managed automatically by WordPress.
	 *
	 * @param MK_Test $test
	 */
	public static function save( $test ) {
		$id = $test->get_id();
		if ( ! $id ) {
			return;
		}

		update_post_meta( $id, self::QUESTIONS_META, $test->get_questions() );
		update_post_meta( $id, self::FINAL_EXAM_META, $test->is_final_exam() );

		// Prevent recursion.
		remove_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
		wp_update_post( [
			'ID' => $id,
			'post_parent' => $test->get_course_id(),
		] );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_metaboxes' ) );
	}

	/**
	 * Load information into a Test object.
	 *
	 * @param MK_Test $test The information gets set by reference into this Test.
	 */
	public static function load( &$test ) {
		$id = $test->get_id();
		if ( ! $id ) {
			return;
		}

		$post = get_post( $id );
		$test->set_title( $post->post_title );
		$test->set_description( $post->post_content );
		$test->set_course_id( $post->post_parent );
		$test->set_questions( (array) get_post_meta( $id, self::QUESTIONS_META, true ) );
		$test->set_is_final_exam( get_post_meta( $id, self::FINAL_EXAM_META, true ) );
	}

	/**
	 * Hook actions and filters.
	 */
	public static function init() {
		add_action( 'add_meta_boxes',[ __CLASS__, 'register_metaboxes' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save_metaboxes' ] );
		add_filter( 'the_content', [ __CLASS__, 'render_test' ], 99 );
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
    		'mk-is-final-exam',
    		'Final Exam', 
    		[ __CLASS__, 'render_final_exam_metabox' ], 
    		self::POST_TYPE,
    		'side'
    	);

    	add_meta_box( 
    		'mk-test-questions',
    		'Questions', 
    		[ __CLASS__, 'render_questions_metabox' ], 
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

		$test = new MK_Test( $post_id );

		if ( isset( $_POST[ self::QUESTIONS_META ] ) ) {
			$test->set_questions( $_POST[ self::QUESTIONS_META ] );
		}

		if ( isset( $_POST[ self::FINAL_EXAM_META ] ) ) {
			$test->set_is_final_exam( $_POST[ self::FINAL_EXAM_META ] );
		}

		if ( isset( $_POST['mk_parent_course'] ) ) {
			$old_course_id = $test->get_course_id();
			$test->set_course_id( $_POST['mk_parent_course'] );

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

		$test->save();
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
	 * Render the questions metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function render_questions_metabox( $post ) {
		$test = new MK_Test( $post->ID );
		$questions = $test->get_questions();
		?>
		<style>
		.mk-question-input {
			margin-bottom: 2em;
			border: 1px solid silver;
		}

		.mk-question-input input {
			width: calc(100% - 2px);
		}

		.mk-question-input .correct input {
			background-color: #d1ffd1;
		}

		.mk-question-input .incorrect input {
			background-color: #ffd1d1;
		}
		</style>

		<?php for ( $i = 0; $i < self::MAX_NUM_QUESTIONS; ++$i ):
			$text = isset( $questions[ $i ], $questions[ $i ]['text'] ) ? esc_attr( $questions[ $i ]['text'] ) : '';
			$correct = isset( $questions[ $i ], $questions[ $i ]['correct'] ) ? esc_attr( $questions[ $i ]['correct'] ) : '';
			$incorrect = ['', '', '',];
			if ( isset( $questions[ $i ], $questions[ $i ]['incorrect'] ) ) {
				for ( $j = 0; $j < 3; ++ $j ) {
					$incorrect[ $j ] = isset( $questions[ $i ]['incorrect'][ $j ] ) ? esc_attr( $questions[ $i ]['incorrect'][ $j ] ) : '';
				}
			}
			?>
			<div class="mk-question-input">
				<div class="text">
					<input type="text" placeholder="Question" name="<?php echo self::QUESTIONS_META ?>[<?php echo $i ?>][text]" value="<?php echo $text ?>" />
				</div>
				<div class="correct">
					<input type="text" placeholder="Correct Answer" name="<?php echo self::QUESTIONS_META ?>[<?php echo $i ?>][correct]" value="<?php echo $correct ?>" />
				</div>
				<?php foreach ( $incorrect as $index => $incorrect_question ): ?>
					<div class="incorrect">
						<input type="text" placeholder="Incorrect Answer" name="<?php echo self::QUESTIONS_META ?>[<?php echo $i ?>][incorrect][<?php echo $index ?>]" value="<?php echo $incorrect_question ?>" />
					</div>
				<?php endforeach ?>
			</div>
		<?php endfor;
	}

	/**
	 * Render the metabox for setting whether a Test is a final exam.
	 *
	 * @param WP_Post $post
	 */
	public static function render_final_exam_metabox( $post ) {
		$test = new MK_Test( $post->ID );
		$is_final_exam = $test->is_final_exam();
		?>
		<input type="hidden" name="<?php echo self::FINAL_EXAM_META ?>" value="0" />
		<input type="checkbox" name="<?php echo self::FINAL_EXAM_META ?>" value="1" <?php checked( $is_final_exam ) ?> />
		This is a final exam. <br /><br />
		<em>Final exams are required to complete a course and will also pull in questions randomly from other tests in the same course.</em>
		<?php
	}

	/**
	 * Make a Test as the content.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function render_test( $content ) {
		global $post;

		$id = get_the_ID();
		if ( self::POST_TYPE !== get_post_type( $id ) ) {
			return $content;
		}

		remove_all_filters( 'the_content' );
		remove_filter( 'the_content', [ __CLASS__, 'render_test' ] );
		$test = new MK_Test( $id );
		ob_start();
		include __DIR__ . '/templates/test.php';
		$content = ob_get_clean();
		$post->post_content = $content;
		return $content;
	}

	/**
	 * Get random questions from other tests in the same Course.
	 *
	 * @param MK_Test $test
	 * @param int $amount Max # of questions to get.
	 * @return array of question arrays
	 */
	public static function get_random_questions_not_from_test( $test, $amount = 20 ) {
		$test_posts = get_posts( [
			'post__not_in' => [ $test->get_id() ],
			'post_status' => 'publish',
			'post_type' => self::POST_TYPE,
			'post_parent' => $test->get_course_id(),
			'posts_per_page' => -1,
		] );

		$question_pool = [];
		foreach ( $test_posts as $post ) {
			$test = new MK_Test( $post->ID );
			$questions = $test->get_questions();
			$question_pool = array_merge( $question_pool, $questions );
		}

		shuffle( $question_pool );
		return array_slice( $question_pool, 0, $amount );
	}

	/**
	 * Get randomized questions ready for being used in a Test.
	 *
	 * @param MK_Test Test to get questions for.
	 * @return array of question arrays
	 */
	public static function get_prepared_questions( $test ) {
		$questions = $test->get_questions();
		foreach ( $questions as $question ) {
			$question['parent'] = $test->get_id();
		}

		if ( $test->is_final_exam() ) {
			$random = self::get_random_questions_not_from_test( $test );
			$questions = array_merge( $questions, $random );
		}

		shuffle( $questions );
		foreach ( $questions as &$question ) {
			$options = $question['incorrect'];
			$options[] = $question['correct'];
			shuffle( $options );
			$question['options'] = $options;
		}
		return $questions;
	}

	/***
	 * Get the test rubric.
	 *
	 * @param MK_Test $test
	 * @param array $answers Submitted answer form
	 * @return array of this format:
			$rubric = [
				'score' => [
					'correct' : 5,
					'incorrect' : 3,
				],
				'questions' => [
					[
						'text' => '',
						'correct' => '',
						'incorrect' => ['','',''],
						'response' => '',
						'options' => ['','','',''],
						'parent' => 12, 
					]
				],
			];
	 */
	public static function get_rubric( $test, $answers = [] ) {
		if ( empty( $answers ) ) {
			return [
				'score' => [],
				'questions' => self::get_prepared_questions( $test ),
			];
		}

		return self::score_test( $answers );
	}

	/**
	 * Convert an answer form to a graded rubric.
	 *
	 * @param array $answers Answer form.
	 * @return array See get_rubric for format.
	 */
	public static function score_test( $answers ) {
		$test_cache = [];
		$correct = 0;
		$total = 0;

		$rubric = [
			'questions' => [],
		];

		foreach ( $answers as $test_id => $answer ) {
			if ( ! isset( $test_cache[ $test_id ] ) ) {
				$test_cache[ $test_id ] = new MK_Test( $test_id );
			}

			foreach ( $answer as $question_text => $answer_details ) {
				$test_questions = $test_cache[ $test_id ]->get_questions();

				foreach ( $test_questions as $test_question ) {
					if ( esc_attr( $question_text ) === esc_attr( $test_question['text'] ) ) {
						$rubric['questions'][] = [
							'text' => esc_attr( $question_text ),
							'correct' => $test_question['correct'],
							'incorrect' => $test_question['incorrect'],
							'response' => esc_attr( $answer_details['response'] ),
							'options' => $answer_details['options'],
							'parent' => $test_cache[ $test_id ]->get_id(),
						];
						if ( esc_attr( $answer_details['response'] ) === esc_attr( $test_question['correct'] ) ) {
							++$correct;
						}
						++$total;
						break;
					}
				}
			}
		}

		$rubric['score'] = [
			'correct' => $correct,
			'num_questions' => $total, 
		];

		return $rubric;
	}

	/**
	 * Output a radio button for a test question option.
	 *
	 * @param int $option_index array index of option in question.
	 * @param int $question_index array index of question in rubric.
	 * @param array $rubric A test rubric.
	 */
	public static function radio( $option_index, $question_index, $rubric ) {
		$question = $rubric['questions'][ $question_index ];
		$option = $question['options'][ $option_index ];
		$attr = '';
		$class = '';

		if ( ! empty( $rubric['score'] ) ) {
			$attr .= 'disabled ';
			if ( esc_attr( $question['response'] ) === esc_attr( $option ) ) {
				$attr .= 'checked ';

				if ( esc_attr( $question['correct'] ) !== esc_attr( $option ) ) {
					$class = 'incorrect';
				}
			}

			// Correct answer.
			if ( esc_attr( $question['correct'] ) === esc_attr( $option ) ) {
				$class = 'correct';

			// All wrong answers when nothing is answered.
			} elseif ( empty ( $question['response'] ) ) {
				$class = 'incorrect';
			}
		}

		?>
		<div class="test_option <?php echo $class ?>">
			<input type="hidden" 
				name="answers[<?php echo intval( $question['parent'] ) ?>][<?php echo esc_attr( $question['text'] ) ?>][options][]" 
				value="<?php echo esc_attr( $option ) ?>" />

			<label>
				<input type="radio" 
					<?php echo $attr ?>
					name="answers[<?php echo intval( $question['parent'] ) ?>][<?php echo esc_attr( $question['text'] ) ?>][response]" 
					value="<?php echo esc_attr( $option ) ?>">

				<?php echo esc_html( $option ) ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Output the test results score.
	 *
	 * @param int $correct The number of questions correct.
	 * @param int $total The total number of questions.
	 */
	public static function output_score( $correct, $total ) {
		$percent = $total ? round( ( $correct / $total ) * 100 ) : 0;


		if ( $correct === $total ) {
			$message = 'You got them all correct! 100% Good job!';
		} elseif ( $percent > 70 ) {
			$message = sprintf( 'You got %d correct of %d questions for a score of %d%%!', $correct, $total, $percent );
		} else {
			$message = sprintf( 'You only got %d correct of %d questions for a score of %d%%. Review the lessons then try again.', $correct, $total, $percent );
		}

		echo $message;
	}
}
MK_Tests::init();