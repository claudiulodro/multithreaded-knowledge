<?php
/*
Plugin Name: Multithreaded Knowledge
Plugin URI: Multithreaded.Link
Description: A decentralized learning platform powered by all the information already on the internet.
Version: 1.0
Author: Multithreaded.Link
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multithreaded Knowledge is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Multithreaded Knowledge is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
For license, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

/**
 * Get the party started.
 */
function mk_init() {

	mk_register_post_types();
	mk_register_taxonomies();

	require __DIR__ . '/class-mk-course.php';
	require __DIR__ . '/class-mk-courses.php';
	require __DIR__ . '/class-mk-lesson.php';
	require __DIR__ . '/class-mk-lessons.php';
	require __DIR__ . '/class-mk-test.php';
	require __DIR__ . '/class-mk-tests.php';
}
add_action( 'init', 'mk_init' );

/**
 * Register the Course, Lesson, and Test post types.
 */
function mk_register_post_types() {
	register_post_type( 'course', [
		'labels' => [
	        'name' => 'Course',
	        'singular_name'=> 'Course',
	        'menu_name' => 'Courses',
	        'name_admin_bar' => 'Course',
	        'add_new' => 'Add New',
	        'add_new_item'  => 'Add New Course',
	        'new_item' => 'New Course',
	        'edit_item' => 'Edit Course',
	        'view_item' => 'View Course',
	        'all_items' => 'All Courses',
	        'search_items' => 'Search Courses',
	        'parent_item_colon' => 'Parent Courses:',
	        'not_found' => 'No Courses found',
	        'not_found_in_trash' => 'No Courses found in Trash',
	        'archives' => 'Course archives',
	        'insert_into_item' => 'Insert into Course',
	        'uploaded_to_this_item' => 'Uploaded to this Course',
	        'filter_items_list' => 'Filter Courses list',
	        'items_list_navigation' => 'Courses list navigation',
	        'items_list' => 'Courses list',

		],
		'description' => 'A course educates about one overarching topic.',
		'public' => true,
		'show_in_rest' => false,
		'taxonomies' => [ 'subject' ],
	] );

	register_post_type( 'lesson', [
		'labels' => [
	        'name' => 'Lesson',
	        'singular_name'=> 'Lesson',
	        'menu_name' => 'Lessons',
	        'name_admin_bar' => 'Lesson',
	        'add_new' => 'Add New',
	        'add_new_item'  => 'Add New Lesson',
	        'new_item' => 'New Lesson',
	        'edit_item' => 'Edit Lesson',
	        'view_item' => 'View Lesson',
	        'all_items' => 'All Lessons',
	        'search_items' => 'Search Lessons',
	        'parent_item_colon' => 'Parent Lessons:',
	        'not_found' => 'No Lessons found',
	        'not_found_in_trash' => 'No Lessons found in Trash',
	        'archives' => 'Lesson archives',
	        'insert_into_item' => 'Insert into Lesson',
	        'uploaded_to_this_item' => 'Uploaded to this Lesson',
	        'filter_items_list' => 'Filter Lessons list',
	        'items_list_navigation' => 'Lessons list navigation',
	        'items_list' => 'Lessons list',

		],
		'description' => 'A Lesson educates about one thing in a Course.',
		'public' => true,
		'show_in_rest' => false,
		'taxonomies' => [],
	] );

	register_post_type( 'test', [
		'labels' => [
	        'name' => 'Test',
	        'singular_name'=> 'Test',
	        'menu_name' => 'Tests',
	        'name_admin_bar' => 'Test',
	        'add_new' => 'Add New',
	        'add_new_item'  => 'Add New Test',
	        'new_item' => 'New Test',
	        'edit_item' => 'Edit Test',
	        'view_item' => 'View Test',
	        'all_items' => 'All Tests',
	        'search_items' => 'Search Tests',
	        'parent_item_colon' => 'Parent Tests:',
	        'not_found' => 'No Tests found',
	        'not_found_in_trash' => 'No Tests found in Trash',
	        'archives' => 'Test archives',
	        'insert_into_item' => 'Insert into Test',
	        'uploaded_to_this_item' => 'Uploaded to this Test',
	        'filter_items_list' => 'Filter Tests list',
	        'items_list_navigation' => 'Tests list navigation',
	        'items_list' => 'Tests list',
		],
		'description' => 'A Test is an assessment in a course.',
		'public' => true,
		'show_in_rest' => false,
		'taxonomies' => [],
	] );
}

/**
 * Register the Subject taxonomy.
 */
function mk_register_taxonomies() { 
    $args = array(
        'hierarchical' => false,
        'labels' => [
	        'name' => 'Subject',
	        'singular_name' => 'Subject',
	        'search_items' => 'Search Subjects',
	        'all_items' => 'All Subjects',
	        'edit_item' => 'Edit Subject',
	        'update_item' => 'Update Subject',
	        'add_new_item' => 'Add New Subject',
	        'new_item_name' => 'New Subject Name',
	        'menu_name'  => 'Subjects',
        ],
        'public' => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'subject' ),
    );
    register_taxonomy( 'subject', array( 'course' ), $args );
}