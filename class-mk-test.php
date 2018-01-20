<?php

/**
 * Sanatizes and compartmentalizes the data for a Test.
 */
class MK_Test {

	protected $id = 0;
	protected $parent_id = 0;
	protected $title = '';
	protected $description = '';
	protected $questions = [];
	protected $is_final_exam = false;

	/**
	 * Constructor.
	 *
	 * @param int $id Test post ID
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
	 * Set the questions.
	 *
	 * @param array $questions Array with following indexes:
	 *					'text' => string Question text
	 *					'correct' => string Correct answer
	 *					'incorrect' => array of strings Incorrect answers
	 */
	public function set_questions( $questions ) {
		$sanitized = array();

		foreach ( $questions as $question ) {
			$text = isset( $question['text'] ) ? esc_attr( $question['text'] ) : '';
			if ( ! $text ) {
				continue;
			}

			$correct = isset( $question['correct'] ) ? esc_attr( $question['correct'] ) : '';
			$incorrect = isset( $question['incorrect'] ) ? array_map( 'esc_attr', $question['incorrect'] ) : array();

			$sanitized[] = [
				'text' => $text,
				'correct' => $correct,
				'incorrect' => $incorrect,
				'parent' => $this->get_id(),
			];
		}

		$this->questions = $sanitized;
	}

	/**
	 * Get the questions.
	 *
	 * @return array See set_questions for format.
	 */
	public function get_questions() {
		return $this->questions;
	}

	/**
	 * Set whether this is a final exam. Final exams behave in special ways.
	 *
	 * @param bool $is
	 */
	public function set_is_final_exam( $is ) {
		$this->is_final_exam = boolval( $is );
	}

	/**
	 * Return whether this is a final exam.
	 *
	 * @return bool
	 */
	public function is_final_exam() {
		return $this->is_final_exam;
	}

	/**
	 * Load the data into this object. This must have an ID to do anything.
	 */
	protected function load() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Tests::load( $this );
	}

	/**
	 * Save the data from this object. This must have an ID to do anything.
	 */
	public function save() {
		if ( ! $this->get_id() ) {
			return;
		}

		MK_Tests::save( $this );
	}
}