<?php
/**
 * @uses $course
 */
$unit_number = 1;
?>

<style>
	.mk_course .sequence_item {
		background-color: #FFFFFF;
		border: 1px solid rgba(0,0,0,.1);
		margin-bottom: 2em;
	}

	.mk_course .heading {
		opacity: .6;
		line-height: 3em;
		border-bottom: 1px solid rgba(0,0,0,.1);
		padding-left: 1em;
	}

	.mk_course .title {
		margin-top: 0;
		padding-top: 1.5em;
	}

	.mk_course .content_container {
		padding: 1em;
		padding-top: 0;
	}

	.mk_course .footing {
		padding-top: 1em;
		padding-bottom: 1em;
		line-height: 1em;
		border-top: 1px solid rgba(0,0,0,.1);
		padding-left: 1em;
		font-weight: bold;
	}
</style>

<div class="mk_course">
	<p class="course_description">
		<?php echo $course->get_description() ?>
	</p>

	<div class="course_content">
		<?php foreach ( $course->get_sequence_ids() as $item_id ):
			if ( 'publish' !== get_post_status( $item_id ) ):
				continue;
			endif;

			$post_type = get_post_type( $item_id );
			if ( MK_Tests::POST_TYPE === $post_type ) :
				$test = new MK_Test( $item_id );
				$final = $test->is_final_exam();
				?>
				<div class="sequence_item test <?php if ( $final ): echo 'final'; endif ?>">
					<a name="<?php echo sanitize_title( $test->get_title() ) ?>"></a>
					<div class="heading">
						<?php if ( $final ) : echo 'Final Exam'; else : echo 'Quiz'; endif ?>
					</div>
					<div class="content_container">
						<h3 class="title">
							<?php echo $test->get_title() ?>
						</h3>
						<p class="description">
							<?php echo $test->get_description() ?>
						</p>
					</div>
					<div class="footing">
						<a href="<?php the_permalink( $item_id ) ?>">Start</a>
					</div>
				</div>
				<?php
			elseif ( MK_Lessons::POST_TYPE === $post_type ) :
				$lesson = new MK_Lesson( $item_id );
				?>
				<div class="sequence_item lesson">
					<div class="heading">
						Unit <?php echo $unit_number ?>
					</div>
					<div class="content_container">
						<h3 class="title">
							<?php echo $lesson->get_title() ?>
						</h3>
						<p class="description">
							<?php echo $lesson->get_description() ?>
						</p>
					</div>
					<div class="footing">
						<a href="<?php echo $lesson->get_resource_url() ?>" target="_blank"><?php echo $lesson->get_resource_title() ?></a>
					</div>
				</div>
				<?php
				++$unit_number;
			else :
				continue;
			endif;
			?>
		<?php endforeach ?>
	</div>
</div>