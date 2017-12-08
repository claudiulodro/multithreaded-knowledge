<?php
/**
 * @uses $test
 */

$answers = array();
if ( isset( $_POST['answers'] ) ) {
	$answers = $_POST['answers'];
}

$rubric = MK_Tests::get_rubric( $test, $answers );
$questions = $rubric['questions'];
$question_number = 1;
$graded = ! empty( $rubric['score'] );
?>
<style>
	.mk_test {
		background-color: #FFFFFF;
		border: 1px solid rgba(0,0,0,.1);
		margin-bottom: 2em;
	}

	.mk_test .back {
		padding: 1em;
	}

	.mk_test .back.footing {
		margin-top: -1em;
	}

	.mk_test .test_content {
		border-top: 1px solid rgba(0,0,0,.1);
		padding: 1em;
	}

	.mk_test .test_item {
		padding-bottom: 2em;
		margin-bottom: 2em;
		border-bottom: 1px dashed rgba(0,0,0,.1);
	}

	.mk_test .test_question {
		margin-bottom: 1em;
		font-size: 1.25em;
	}

	.mk_test input[type="radio"] {
		margin-right: .5em;
	}

	.mk_test .test_submit {
		padding: .5em;
		padding-left: 1em;
		padding-right: 1em;
		margin-bottom: 1em;
	}

	.mk_test .test_option {
		padding-left: 1em;
		padding-right: 1em;
	}

	.mk_test .test_option.correct {
		background-color: #d1ffd1;
	}

	.mk_test .test_option.incorrect {
		background-color: #ffd1d1;
	}

	.mk_test .grade {
		padding: 1em;
		font-weight: bold;
		border-bottom: 1px solid rgba(0,0,0,.1);
	}
</style>

<div class="mk_test">
	<?php if ( $graded ): ?>
		<div class="grade">
			<?php MK_Tests::output_score( $rubric['score']['correct'], $rubric['score']['num_questions'] ) ?>
		</div>
	<?php endif ?>

	<div class="back">
		<?php if ( $graded ): ?>
			<a href="<?php the_permalink() ?>">Retake this test</a> or <a href="<?php the_permalink( $test->get_course_id() ) ?>#<?php echo sanitize_title( $test->get_title() ) ?>">go back to course.</a>
		<?php else: ?>
			<a href="<?php the_permalink( $test->get_course_id() ) ?>#<?php echo sanitize_title( $test->get_title() ) ?>">Go back to course.</a>
		<?php endif ?>
	</div>

	<div class="test_content">

		<form action="<?php the_permalink() ?>" method="post">
			<?php foreach ( $questions as $question_index => $question ) : ?>
				<div class="test_item">

					<div class="test_question">
						<?php echo apply_filters( 'the_content', $question_number . '. ' . html_entity_decode( $question['text'] ) ) ?>
					</div>

					<div class="test_options">
						<input type="hidden" name="answers[<?php echo intval( $question['parent'] ) ?>][<?php echo esc_attr( $question['text'] ) ?>][response]" value="">

						<?php foreach ( $question['options'] as $option_index => $option ): ?>
							<?php MK_Tests::radio( $option_index, $question_index, $rubric ) ?>
						<?php endforeach ?>
					</div>

				</div>
				<?php ++$question_number ?>
			<?php endforeach ?>

			<div class="back footing">
				<?php if ( $graded ): ?>
					<a href="<?php the_permalink() ?>">Retake this test</a> or <a href="<?php the_permalink( $test->get_course_id() ) ?>#<?php echo sanitize_title( $test->get_title() ) ?>">go back to course.</a>
				<?php else: ?>
					<input type="submit" class="test_submit" value="Submit">
				<?php endif ?>
			</div>

		</form>

	</div>
</div>
