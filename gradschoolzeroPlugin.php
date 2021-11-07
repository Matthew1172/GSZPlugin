<?php
/*
Plugin Name: Grad School Zero
Plugin URI: 
Description: This is the plugin for the gradschoolzero Website.
Version: 1.0.0
Author: Team M
Author URI: 
License: MIT
Text Domain: gradschoolzero
*/
/**
 * @package gradschoolzero
 * @subpackage gradschoolzero_Plugin
 * @since gradschoolzero Plugin 1.0
 *
 */


defined('ABSPATH') or die('No script kiddies please!');

/**
   * Create all custom gradschoolzero tables in wordpress database
*/
function gradschoolzero_install()
{
  /*This is the global variable that give us connection to the wordpress database.
It will let us call special functions and also run SQL statements
  */
  
  //we only need include this wordpress file if we want to create a new table in wpdb using dbDelta()
  //i'm not sure why we need this file yet
  require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
  //select the wp_users and wp_posts databases. The prefix is the special name you give during installation
  //the prefix look like this: "something_"


//I want to put this in the themes function.php activation hook
/*
  //This is how our plugin automatically creates pages for our theme to use
  $new_page_title     = __('Archive', 'text-domain'); // Page's title
  $new_page_content   = '';                           // Content goes here
  //the template is the actual php file in the theme with the html in it
  $new_page_template  = 'archive.php';       // The template to use for the page
  $page_check = get_page_by_title($new_page_title);   // Check if the page already exists
  //This array is all he settings for the page, including post type (because pages are technically wp posts)
  //post status is if it's visible or not, and the post author is 1 so that is the root user.
  $new_page = array(
    'post_type'     => 'page',
    'post_title'    => $new_page_title,
    'post_content'  => $new_page_content,
    'post_status'   => 'publish',
    'post_author'   => 1
  );
  // If the page doesn't already exist, create it
  if (!isset($page_check->ID)) {
    $new_page_id = wp_insert_post($new_page);
    if (!empty($new_page_template)) {
      update_post_meta($new_page_id, '_wp_page_template', $new_page_template);
    }
  }
*/


  //we call this whenever we add a table to wpdb (specifically dbDelta() function which is located in that .php file we included)
  flush_rewrite_rules();
}
//This hook means call the gradschoolzero_install function when we activate the plugin.
register_activation_hook(__FILE__, 'gradschoolzero_install');

/*
This function is called when we deactivate the plugin. We will want to do things like delete pages, delete custom user roles,
delete custom post types, and drop the databases we made?
*/
function gradschoolzero_deactivation()
{
  // unregister the post type, so the rules are no longer in memory
  //This will delete the CPT including its tags and categories
  unregister_post_type('gradschoolzeroclass');
  unregister_post_type('gradschoolzeromsg');

  //remove taxonomy

  //remove our custom roles
  remove_role( 'instructor' );
  remove_role( 'student' );


  //I want to put this in the themes function.php deactivate theme hook
  /*
  //This is how our plugin automatically deletes pages from our theme. First we get the page by the name
  //We gave it
  $page_check = get_page_by_title('Archive');
  if (isset($page_check->ID)) {
    //This will delete the page by the ID we got abaove, and the True boolean is to delete it from the trash
    //If it were False or nothing, then the page would still exist in the trash.
    wp_delete_post($page_check->ID, True);
  }
  */



  global $wpdb;

  $table_name = $wpdb->prefix . 'gradschoolzero_userPost';
  $wpdb->query("DROP TABLE IF EXISTS $table_name");

  // clear the permalinks to remove our post type's rules from the database
  flush_rewrite_rules();
}
//this hook is called when we deactivate, which is different then deletion
register_deactivation_hook(__FILE__, 'gradschoolzero_deactivation');


/*
This is the init function that's called after the install function,
but essentially they're the same thing. Just note that this one is called after. Also, we can
have multiple 'init' hooks, so they're all put together. Although I think we should just keep everything
in one function and put it sequentially
*/
function gradschoolzero_setup_post_type()
{
  //This is where we add our custom roles. We can use the admin role for the registrar, and we don't need a role for visitors
  //the reason we put two 'instuctor' strings is because one is used as a label and another is used for urls
  $seeker_role = add_role(
    'instructor',
    'instructor',
    array()
  );
  $employer_role = add_role(
    'student',
    'student',
    array()
  );


  /*
  This is where we will create our 'class' custom post type. It's not in a custom table in the WPDB, instead
  it uses the existing wp_posts table and we can add the meta information like gpa, rating, capacity, etc.
  In order to keep track of students who are registered to this class, we will use the many-to-many custom table
  And we can also use that same table to keep track of the instructor(s) for this class
  */
  /*
  We also have to add all the string labels for when wordpress wants to do something with the post.
  */
  $labels = array(
    'name'               => __('Class'),
    'singular_name'      => __('Class'),
    'add_new'            => __('Add New Class'),
    'add_new_item'       => __('Add New Class'),
    'edit_item'          => __('Edit Class'),
    'new_item'           => __('Add New Class'),
    'view_item'          => __('View Class'),
    'search_items'       => __('Search Classes'),
    'not_found'          => __('No Classes found'),
    'not_found_in_trash' => __('No Classes found in trash')
  );
  $supports = array(
    'title',
    'editor',
    'revisions',
    'comments'
  );
  $args = array(
    'labels'               => $labels,
    'supports'             => $supports,
    'public'               => true,
    'capability_type'      => 'post',
    'rewrite'              => array('slug' => 'gradschoolzeroclass'),
    'has_archive'          => true,
    'menu_position'        => 30,
    'menu_icon'            => 'dashicons-hammer',
    'register_meta_box_cb' => 'gradschoolzero_class_metaboxes',
    'taxonomies' => array('post_tag', 'category')
  );
  //This is the function that will actually call the WP engine to create the CPT in the WPDB
  register_post_type('gradschoolzeroclass', $args);

  //I think we will need a 'complain' post type  	--
  //I think we will need a 'warning' post type  	--
  


  //Create the heirarchial custom taxonomy "Message type" for use with gradschoolzeromsg custom post type
  $labels = array(
    'name' => _x( 'Message types', 'taxonomy general name' ),
    'singular_name' => _x( 'Message type', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Message types' ),
    'all_items' => __( 'All Message types' ),
    'parent_item' => __( 'Parent Message type' ),
    'parent_item_colon' => __( 'Parent Message type:' ),
    'edit_item' => __( 'Edit Message type' ), 
    'update_item' => __( 'Update Message type' ),
    'add_new_item' => __( 'Add New Message type' ),
    'new_item_name' => __( 'New Message type Name' ),
    'menu_name' => __( 'Message types' ),
  );    
 
//register the taxonomy
  register_taxonomy('messagetypes',array('gradschoolzeromsg'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'messagetype' ),
  ));






  /*
  I think we will need a 'messages' post type
  we can just have a meta field in messages saying what type of message it is, 
  (complaint, justification, warning, expulsion/suspension/termination, admission?, new hire?, graduation application)
  */

  $labels = array(
    'name'               => __('Message'),
    'singular_name'      => __('Message'),
    'add_new'            => __('Add New Message'),
    'add_new_item'       => __('Add New Message'),
    'edit_item'          => __('Edit Message'),
    'new_item'           => __('Add New Message'),
    'view_item'          => __('View Message'),
    'search_items'       => __('Search Messages'),
    'not_found'          => __('No Messages found'),
    'not_found_in_trash' => __('No Messages found in trash')
  );
  $supports = array(
    'title',
    'editor',
    'revisions'
  );
  $args = array(
    'labels'               => $labels,
    'supports'             => $supports,
    'public'               => true,
    'capability_type'      => 'post',
    'rewrite'              => array('slug' => 'gradschoolzeromsg'),
    'has_archive'          => true,
    'menu_position'        => 30,
    'menu_icon'            => 'dashicons-hammer',
    'register_meta_box_cb' => 'gradschoolzero_msg_metaboxes',
    'taxonomies' => array('messagetypes')
  );
  //This is the function that will actually call the WP engine to create the CPT in the WPDB
  register_post_type('gradschoolzeromsg', $args);







  wp_insert_term(
    'Warning',
    'messagetypes',
    array(
      'description' => 'A warning message from the registrar.',
      'slug' => 'warning'
    )
  );
  $parent_warning = term_exists('Warning', 'messagetypes');
  if($parent_warning){
	wp_insert_term(
		'Expulsion',
		'messagetypes',
		array(
		  'description' => 'An expulsion message from the registrar.',
		  'slug' => 'expulsion',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Suspension',
		'messagetypes',
		array(
		  'description' => 'A suspension message from the registrar.',
		  'slug' => 'suspension',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Termination',
		'messagetypes',
		array(
		  'description' => 'A termination message from the registrar.',
		  'slug' => 'termination',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Bad justification',
		'messagetypes',
		array(
		  'description' => 'A reply message to your justification, from the registrar.',
		  'slug' => 'bjustification',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Unreasonable complaint',
		'messagetypes',
		array(
		  'description' => 'A reply message to your complaint, from the registrar.',
		  'slug' => 'bcomplaint',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Reckless application',
		'messagetypes',
		array(
		  'description' => 'A reply message to your application for graduation, from the registrar.',
		  'slug' => 'bapplication',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Too few classes',
		'messagetypes',
		array(
		  'description' => 'A message from the registrar about your enrollment.',
		  'slug' => 'fewclasses',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Course cancelled',
		'messagetypes',
		array(
		  'description' => 'A message from the registrar about your course.',
		  'slug' => 'coursecancelled',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Poor course rating',
		'messagetypes',
		array(
		  'description' => 'A message from the registrar about your low course rating.',
		  'slug' => 'poorrating',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Taboo words',
		'messagetypes',
		array(
		  'description' => 'A message from the registrar about your course rating.',
		  'slug' => 'taboowords',
		  'parent' => $parent_warning['term_id']
		)
	  );
	  wp_insert_term(
		'Didn\'t assign grades',
		'messagetypes',
		array(
		  'description' => 'A message from the registrar about your course rating.',
		  'slug' => 'taboowords',
		  'parent' => $parent_warning['term_id']
		)
	  );
  }
  wp_insert_term(
    'Complaint',
    'messagetypes',
    array(
      'description' => 'A complaint message from a student or instructor about another student or instructor.',
      'slug' => 'complaint'
    )
  );
  wp_insert_term(
    'Graduation application',
    'messagetypes',
    array(
      'description' => 'An application for graduation from a student.',
      'slug' => 'gradapp'
    )
  );
  wp_insert_term(
    'Justification',
    'messagetypes',
    array(
      'description' => 'A justification from a student or instructor.',
      'slug' => 'justification'
    )
  );
}
//This hook is calls the function gradschoolzero_setup_post_type after we activate the plugin and after the 'install' function. It's only called once.
add_action('init', 'gradschoolzero_setup_post_type');
























function gradschoolzero_msg_metaboxes(){
	add_meta_box(
		'gradschoolzero_msg_recipients',
		'Message recipients',
		'gradschoolzero_msg_recipients',
		'gradschoolzeromsg',
		'normal',
		'default'
	  );
}
add_action('add_meta_boxes', 'gradschoolzero_msg_metaboxes');

function gradschoolzero_msg_recipients(){
	wp_nonce_field(basename(__FILE__), 'msgfields');

	//for each student and instructor, display a checkbox that when checked it will
	//asscociate those users to this msg so that we can query them in the profile page on the theme.

	global $post;
	$pid = absint($post->ID);

	$blogusers = get_users(array('role__in' => array('student', 'instructor')));
	// Array of WP_User objects.
	foreach ($blogusers as $user) {
		$uid = absint($user->ID);
		$rec_key = strval($uid).'_recipient';
		$rec = get_post_meta($pid ,$rec_key, true);

		echo '<fieldset>';
		echo '<input type="checkbox" name="'.$rec_key.'" value="yes"';
		checked($rec, "yes");
		echo '/>';
		echo '<label for="'.$rec_key.'">'.esc_html($user->display_name).'</label>';
		echo '</fieldset>';
	}
}

/**
 * Save the gradschoolzeromsg metabox data
 */
function gradschoolzero_save_msg_meta($post_id, $post)
{
  // Return if the user doesn't have edit permissions.
  if (!current_user_can('edit_post', $post_id)) {
    return $post_id;
  }
  // Verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times.
  if (!array_key_exists('msgfields', $_POST)) {
    return $post_id;
  }
  // Now that we're authenticated, time to save the data.
  // This sanitizes the data from the field and saves it into an array $class_meta.

  $blogusers = get_users(array('role__in' => array('student', 'instructor')));
  //go through every user
  foreach ($blogusers as $user) {
	  //save the user id
	$uid = absint($user->ID);
	//create a post meta value key to save in the DB, which is the user-id_recipient as a string. 
	//The value will be "yes" to indicate that the user is a recipient of this msg, and it will be non-existent if they're not.
	$rec_key = strval($uid).'_recipient';
	//check if the value we got from the post of the key is equal to "yes"
		if($_POST[$rec_key] == "yes"){
			//save the meta data as user-id_recipient and the value as "yes"
			$message_meta[$rec_key] = "yes";
		}else{
			$message_meta[$rec_key] = null;
		}
	}


  // Cycle through the $message_meta array.
  // Note, in this example we just have one item, but this is helpful if you have multiple.
  foreach ($message_meta as $key => $value) :
    // Don't store custom data twice
    if ('revision' === $post->post_type) {
      return;
    }
    if (get_post_meta($post_id, $key, false)) {
      // If the custom field already has a value, update it.
      update_post_meta($post_id, $key, $value);
    } else {
      // If the custom field doesn't have a value, add it.
      add_post_meta($post_id, $key, $value);
    }
    if (!$value) {
      // Delete the meta key if there's no value
      delete_post_meta($post_id, $key);
    }
  endforeach;
}
add_action('save_post', 'gradschoolzero_save_msg_meta', 1, 2);



































/*
This function is called when we register our CPT. 
*/
function gradschoolzero_class_metaboxes()
{
  /*
Basically this is a section in the post creation GUI
in the wordpress admin area. It serves as a GUI container 
for the meta information that will be
available to the registrar when they add a class.
Each of these GUI containers will call a function called (ex gradschoolzero_class_info) which will be responsible
for outputting the html in the registrar GUI. 
  */
  add_meta_box(
    'gradschoolzero_class_info',
    'Class information',
    'gradschoolzero_class_info',
    'gradschoolzeroclass',
    'normal',
    'default'
  );
  add_meta_box(
    'gradschoolzeroclass_class_prereq',
    'Class prerequisites',
    'gradschoolzeroclass_class_prereq',
    'gradschoolzeroclass',
    'normal',
    'default'
  );
}
add_action('add_meta_boxes', 'gradschoolzero_class_metaboxes');

/**
 * Output the HTML for the gradschoolzeroclass info metaboxes.
 */
function gradschoolzero_class_info()
{
  global $post;

  //This will be uniform throughout the class meta boxes. When we save the CPT into wordpress, it will
  //look for these names in the metabox containers name called 'classfields'
  wp_nonce_field(basename(__FILE__), 'classfields');

  $comments = get_comments(array('post_id' => absint($post->ID)));
  $s = 0;
  $n = 0;
  foreach ( $comments as $comment ):
    $s += absint(get_comment_meta($comment->comment_ID, 'rat', true));
    $n+=1;
  endforeach;
  if($n > 0) echo '<h1>Average class rating: '.$s/$n.'/5</h1>';
  else echo '<h1>There are no reviews for this class.</h1>';

  //This is an input field in the CPT add class gui
  echo '<fieldset>';
  $cap = get_post_meta($post->ID, 'cap', true);
  echo '<label for="cap">Capacity</label>';
  echo '<input type="text" name="cap" value="' . esc_textarea($cap)  . '" class="widefat">';
  echo '</fieldset>';

  echo '<fieldset>';
  $startTime = get_post_meta($post->ID, 'startTime', true);
  echo '<label for="startTime">Starting time</label>';
  echo '<input type="time" name="startTime" value="' . esc_textarea($startTime)  . '" class="widefat">';
  echo '</fieldset>';

  echo '<fieldset>';
  $endTime = get_post_meta($post->ID, 'endTime', true);
  echo '<label for="endTime">Ending time</label>';
  echo '<input type="time" name="endTime" value="' . esc_textarea($endTime)  . '" class="widefat">';
  echo '</fieldset>';

  echo '<fieldset>';
  $startDate = get_post_meta($post->ID, 'startDate', true);
  echo '<label for="startDate">Start date</label>';
  echo '<input type="date" name="startDate" value="' . esc_textarea($startDate)  . '" class="widefat">';

  $endDate = get_post_meta($post->ID, 'endDate', true);
  echo '<label for="endDate">End date</label>';
  echo '<input type="date" name="endDate" value="' . esc_textarea($endDate)  . '" class="widefat">';
  echo '</fieldset>';

  //THe selector fields are gnarly
  echo '<label for="loc">Location</label>';
  echo '<fieldset>';
  $loc = get_post_meta($post->ID, 'loc', true);
  echo '<select name="loc" class="postbox"><option value="c" '; 
  selected($loc, 'c'); 
  echo '>Campus</option><option value="v" ';
  selected($loc, 'v'); 
  echo '>Virtual</option></select>';
  echo '</fieldset>';
}


function gradschoolzeroclass_class_prereq(){
  global $post;
  global $wpdb;
  wp_nonce_field(basename(__FILE__), 'classfields');

  //Store the post id in a variable because we can't use global $post after we call the_post()
  $id = $post->ID;
  $qry = array(
    'post_type'    => 'gradschoolzeroclass'
  );
  $classes = new WP_Query($qry);
  if ($classes->have_posts()) {
      while ($classes->have_posts()) {
          $classes->the_post();
          //a prereq cannot be the class itself
          if(get_the_id() != $id){
            echo '<fieldset>';
            $pre = get_post_meta($id, get_the_id().'_pre', true);
            echo '<input type="checkbox" name="'.get_the_id().'_pre" value="yes"';
            checked($pre, "yes");
            echo '/>';

            echo '<label for="'.get_the_id().'_pre">'.get_the_title().'</label>';
            echo '</fieldset>';
          }
      }
  }
}

/**
 * Save the gradschoolzeroclass metabox data
 */
function gradschoolzero_save_class_meta($post_id, $post)
{
  // Return if the user doesn't have edit permissions.
  if (!current_user_can('edit_post', $post_id)) {
    return $post_id;
  }
  // Verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times.
  if (!array_key_exists('classfields', $_POST)) {
    return $post_id;
  }
  // Now that we're authenticated, time to save the data.
  // This sanitizes the data from the field and saves it into an array $class_meta.
  $class_meta['cap'] = esc_textarea($_POST['cap']);
  $class_meta['startTime'] = esc_textarea($_POST['startTime']);
  $class_meta['endTime'] = esc_textarea($_POST['endTime']);
  $class_meta['startDate'] = esc_textarea($_POST['startDate']);
  $class_meta['endDate'] = esc_textarea($_POST['endDate']);
  $class_meta['loc'] = esc_textarea($_POST['loc']);


  global $wpdb;
  
  //saves the class prerequistes fields as id_pre where id is the CPT id of the prerequiste
  $qry = array(
    'post_type' => 'gradschoolzeroclass'
  );
  $classes = new WP_Query($qry);
  if ($classes->have_posts()) {
      while ($classes->have_posts()) {
          $classes->the_post();
          $class_meta[get_the_id().'_pre'] = esc_textarea($_POST[get_the_id().'_pre']);
      }
  }


  // Cycle through the $class_meta array.
  // Note, in this example we just have one item, but this is helpful if you have multiple.
  foreach ($class_meta as $key => $value) :
    // Don't store custom data twice
    if ('revision' === $post->post_type) {
      return;
    }
    if (get_post_meta($post_id, $key, false)) {
      // If the custom field already has a value, update it.
      update_post_meta($post_id, $key, $value);
    } else {
      // If the custom field doesn't have a value, add it.
      add_post_meta($post_id, $key, $value);
    }
    if (!$value) {
      // Delete the meta key if there's no value
      delete_post_meta($post_id, $key);
    }
  endforeach;
}
add_action('save_post', 'gradschoolzero_save_class_meta', 1, 2);





























function add_rating_meta($comment) {
  $user = get_current_user_id();
  $rat = get_comment_meta($comment->comment_ID, 'rat', true);
  echo '<label for="rat">Rating</label>';
  echo '<fieldset>';

  echo '<select name="rat" class="postbox">';
  
  echo'<option value="1" '; 
  selected($rat, '1'); 
  echo '>1</option>';
  
  echo'<option value="2" ';
  selected($rat, '2'); 
  echo '>2</option>';

  echo'<option value="3" ';
  selected($rat, '3'); 
  echo '>3</option>';

  echo'<option value="4" ';
  selected($rat, '4'); 
  echo '>4</option>';

  echo'<option value="5" ';
  selected($rat, '5'); 
  echo '>5</option>';
  
  echo '</select>';
  echo '</fieldset>';
}

function save_comment_rating($comment_content) {
  global $wpdb;
  $user = get_current_user_id();
  $rat = absint($_POST['rat']);
  $comment_ID = absint($_POST['comment_ID']);
  if (get_comment_meta($comment_ID, 'rat', true)) {
    // If the custom field already has a value, update it.
    update_comment_meta($comment_ID, 'rat', $rat);
  } else {
    // If the custom field doesn't have a value, add it.
    add_comment_meta($comment_ID, 'rat', $rat, $unique = true);
  }
}
add_filter('comment_save_pre', 'save_comment_rating' );

function add_comment_metaboxes(){
  add_meta_box('comment_rating', __('Rating'), 'add_rating_meta', 'comment', 'normal');
}
add_action('add_meta_boxes', 'add_comment_metaboxes');





















/*
We need to add the change-phase-crp bulk action to the users page, as well as bulk actions for all other phases
*/
/*
Bulk action to change all selected users to class set-up period phase
*/
function bulk_change_phase_csp($bulk_actions) {
	$bulk_actions['phase-csp'] = __('Change phase to class set-up period', 'txtdomain');
	return $bulk_actions;
}
add_filter('bulk_actions-users', 'bulk_change_phase_csp');

function handle_bulk_change_phase_csp($redirect_url, $action, $user_ids) {
	if ($action == 'phase-csp') {
		foreach ($user_ids as $user_id) {
			update_user_meta(
				$user_id,
				'phase', 
				'csp'
			);
		}
		$redirect_url = add_query_arg('changed-phase-csp', count($user_ids), $redirect_url);
	}
	return $redirect_url;
}
add_filter('handle_bulk_actions-users', 'handle_bulk_change_phase_csp', 10, 3);

/*
Bulk action to change all selected users to course registration period phase
*/
function bulk_change_phase_crp($bulk_actions) {
	$bulk_actions['phase-crp'] = __('Change phase to course registration period', 'txtdomain');
	return $bulk_actions;
}
add_filter('bulk_actions-users', 'bulk_change_phase_crp');

function handle_bulk_change_phase_crp($redirect_url, $action, $user_ids) {
	if ($action == 'phase-crp') {
		foreach ($user_ids as $user_id) {
			update_user_meta(
				$user_id,
				'phase', 
				'crp'
			);
		}
		$redirect_url = add_query_arg('changed-phase-crp', count($user_ids), $redirect_url);
	}
	return $redirect_url;
}
add_filter('handle_bulk_actions-users', 'handle_bulk_change_phase_crp', 10, 3);

/*
Bulk action to change all selected users to special course registration period phase
*/
function bulk_change_phase_scrp($bulk_actions) {
	$bulk_actions['phase-scrp'] = __('Change phase to special course registration period', 'txtdomain');
	return $bulk_actions;
}
add_filter('bulk_actions-users', 'bulk_change_phase_scrp');

function handle_bulk_change_phase_scrp($redirect_url, $action, $user_ids) {
	if ($action == 'phase-scrp') {
		foreach ($user_ids as $user_id) {
			update_user_meta(
				$user_id,
				'phase', 
				'scrp'
			);
		}
		$redirect_url = add_query_arg('changed-phase-scrp', count($user_ids), $redirect_url);
	}
	return $redirect_url;
}
add_filter('handle_bulk_actions-users', 'handle_bulk_change_phase_scrp', 10, 3);

/*
Bulk action to change all selected users to class running period phase
*/
function bulk_change_phase_crup($bulk_actions) {
	$bulk_actions['phase-crup'] = __('Change phase to class running period', 'txtdomain');
	return $bulk_actions;
}
add_filter('bulk_actions-users', 'bulk_change_phase_crup');

function handle_bulk_change_phase_crup($redirect_url, $action, $user_ids) {
	if ($action == 'phase-crup') {
		foreach ($user_ids as $user_id) {
			update_user_meta(
				$user_id,
				'phase', 
				'crup'
			);
		}
		$redirect_url = add_query_arg('changed-phase-crup', count($user_ids), $redirect_url);
	}
	return $redirect_url;
}
add_filter('handle_bulk_actions-users', 'handle_bulk_change_phase_crup', 10, 3);

/*
Bulk action to change all selected users to grading period phase
*/
function bulk_change_phase_gp($bulk_actions) {
	$bulk_actions['phase-gp'] = __('Change phase to grading period', 'txtdomain');
	return $bulk_actions;
}
add_filter('bulk_actions-users', 'bulk_change_phase_gp');

function handle_bulk_change_phase_gp($redirect_url, $action, $user_ids) {
	if ($action == 'phase-gp') {
		foreach ($user_ids as $user_id) {
			update_user_meta(
				$user_id,
				'phase', 
				'gp'
			);
		}
		$redirect_url = add_query_arg('changed-phase-gp', count($user_ids), $redirect_url);
	}
	return $redirect_url;
}
add_filter('handle_bulk_actions-users', 'handle_bulk_change_phase_gp', 10, 3);

/*
Bulk action to change all selected users to post grading period phase
*/
function bulk_change_phase_pgp($bulk_actions) {
	$bulk_actions['phase-pgp'] = __('Change phase to post grading period', 'txtdomain');
	return $bulk_actions;
}
add_filter('bulk_actions-users', 'bulk_change_phase_pgp');

function handle_bulk_change_phase_pgp($redirect_url, $action, $user_ids) {
	if ($action == 'phase-pgp') {
		foreach ($user_ids as $uid) {
			/*
			Here we can pull all the classes and grades a user is assigned to and add them to their transcript,
			Basically we can do all of (6) here.
			*/

			//Here we want to: 
			//1. grab all the classes the student is currently enrolled in, and the grades for those classes, 
			//2. add them to their transcript, 
			//3. un-enroll them from those classes.

			//we have to loop through all posts and check if this student is enrolled
			$classes_query = array('post_type' => 'gradschoolzeroclass');
			$q = new WP_Query($classes_query);
			if($q->have_posts()){
				while($q->have_posts()){
					$q->the_post();
					$enrollment_key = str_replace(" ", "", strtolower(get_the_title())) . "_enrollment";
					$grade_key = str_replace(" ", "", strtolower(get_the_title())) . "_grade";
					$transcript_key = str_replace(" ", "", strtolower(get_the_title())) . "_transcript";
					$fgrade_key = str_replace(" ", "", strtolower(get_the_title())) . "_fgrade";
					
					$en = get_user_meta($uid, $enrollment_key, true);

					//check if student is enrolled
					if($en == 'e'){
						//the student is enrolled, get the grade for this class
						$gr = get_user_meta($uid, $grade_key, true);

						//add this class and grade to the transcript
						//We have to check how many times the user has taken this class
						$hm = 0;
						//loop through i : 0 -> 4 and check the user meta data for <<classTitle>_transcript_<attempt>> where <attempt> = i
						for($i = 1; $i < 5; $i++){
							if(get_user_meta($uid, $transcript_key."_".strval($i), true) == "taken"){
								//The student has taken this class before, add to the number of attempts
								$hm++;
							}
						}

						//add 1 to the number of attempts
						$a = $hm+1;
					
						if($hm < 5){
							//the student has taken this class less then five times, so we can add this attempt	
							$ret_uid = add_user_meta(
								$uid,
								$transcript_key."_".strval($a),
								"taken",
								true
							);
							$ret_uid = add_user_meta(
								$uid,
								$fgrade_key."_".strval($a),
								$gr,
								true
							);
	
							//finally we have to update the enrollment status of this student on this class
							$ret_uid = update_user_meta(
								$uid,
								$enrollment_key,
								'ne'
							);
							$ret_uid = update_user_meta(
								$uid,
								$grade_key,
								'na'
							);
						}else{
							//this is the students 5th+ attempt so we cannot add this class&grade to their transcript.
							//I'm not sure what to do here but definitely don't add this attempt.
						}
					}
				}
			}
			//change the phase to post grading period
			update_user_meta(
				$uid,
				'phase', 
				'pgp'
			);
		}
		$redirect_url = add_query_arg('changed-phase-pgp', count($user_ids), $redirect_url);
	}
	return $redirect_url;
}
add_filter('handle_bulk_actions-users', 'handle_bulk_change_phase_pgp', 10, 3);





/**
 * The field on the editing screens.
 *
 * @param $user WP_User user object
 */
function wporg_usermeta_form_field($user)
{
	//Add user phase period dropdown for both students and instructors
	if(isset($user->ID)){
		$uid = $user->ID;
		$phase = esc_attr(get_user_meta($uid, 'phase', true ));
		$status = esc_attr(get_user_meta($uid, 'status', true));
		$user_meta=get_userdata($uid);
		$user_roles=$user_meta->roles;
		$warn = esc_attr(get_user_meta($uid, 'warn', true));
	}else{
		$phase = 'csp';
		//$hr = 'nhr';
		$status = 'mat';
		$user_roles = array();
		$warn = 0;
	}

	echo '<h3>Warnings</h3>';
	echo '<table class="form-table">';
	echo '<tr>';
	
	echo '<th>';
	echo '<label for="warn">Warnings</label>'; 
	echo '</th>';
	
	echo '<td>';
	echo '<select name="warn" class="postbox">';
	echo'<option value="0" '; 
	selected($warn, '0'); 
	echo '>0</option>';

	echo'<option value="1" '; 
	selected($warn, '1'); 
	echo '>1</option>';

	echo'<option value="2" '; 
	selected($warn, '2'); 
	echo '>2</option>';

	echo'<option value="3" '; 
	selected($warn, '3'); 
	echo '>3</option>';

	echo'<option value="4" '; 
	selected($warn, '4'); 
	echo '>4</option>';

	echo'<option value="5" '; 
	selected($warn, '5'); 
	echo '>5</option>';
	echo '</select>';
	echo '</td>';
	
	echo '</tr>';
	echo '</table>';



	
	echo '<h3>Phase</h3>';
	echo '<table class="form-table">';
	echo '<tr>';
	
	echo '<th>';
	echo '<label for="phase">Phase</label>'; 
	echo '</th>';
	
	echo '<td>';
	echo '<select name="phase" class="postbox">';
	echo'<option value="csp" '; 
	selected($phase, 'csp'); 
	echo '>Class set-up period</option>';

	echo'<option value="crp" ';
	selected($phase, 'crp'); 
	echo '>Course registration period</option>';

	echo'<option value="scrp" ';
	selected($phase, 'scrp'); 
	echo '>Special course registration period</option>';

	echo'<option value="crup" ';
	selected($phase, 'crup'); 
	echo '>Class running period</option>';

	echo'<option value="gp" ';
	selected($phase, 'gp'); 
	echo '>Grading period</option>';
	
	echo'<option value="pgp" ';
	selected($phase, 'pgp'); 
	echo '>Post grading period</option>';
	echo '</select>';
	echo '</td>';
	
	echo '</tr>';
	echo '</table>';
	

	//add status dropdown for students and a different status dropdown for instructors
	echo '<h3>Status</h3>';
	echo '<table class="form-table">';
	echo '<tr>';

	echo '<th>';
	echo '<label for="status">Status</label>'; 
	echo '</th>';

	echo '<td>';
	echo '<select name="status" class="postbox">';
	if(in_array('student', $user_roles)){
		echo'<option value="mat" '; 
		selected($status, 'mat'); 
		echo '>Matriculated</option>';

		echo'<option value="sus" ';
		selected($status, 'sus'); 
		echo '>Suspended</option>';

		echo'<option value="exp" ';
		selected($status, 'exp'); 
		echo '>Expelled</option>';
	}else if(in_array('instructor', $user_roles)){
		echo'<option value="emp" '; 
		selected($status, 'emp'); 
		echo '>Employed</option>';

		echo'<option value="fir" ';
		selected($status, 'fir'); 
		echo '>Fired</option>';
	}else{
		echo'<option value="mat" '; 
		selected($status, 'mat'); 
		echo '>Matriculated</option>';

		echo'<option value="sus" ';
		selected($status, 'sus'); 
		echo '>Suspended</option>';

		echo'<option value="exp" ';
		selected($status, 'exp'); 
		echo '>Expelled</option>';
		
		echo'<option value="emp" '; 
		selected($status, 'emp'); 
		echo '>Employed</option>';

		echo'<option value="fir" ';
		selected($status, 'fir'); 
		echo '>Fired</option>';
	}
	echo '</select>';
	echo '</td>';

	echo '</tr>';
	echo '</table>';

	//Add enrollment status to classes for this student, also add grades for these classes if necessary
	echo '<h3>Student enrollment</h3>';
	echo '<table class="form-table">';
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		while($q->have_posts()){
			$q->the_post();
			$enrollment_key = str_replace(" ", "", strtolower(get_the_title())) . "_enrollment";
			$grade_key = str_replace(" ", "", strtolower(get_the_title())) . "_grade";
			
			//If this is a new user, set the default enrollment status to not-enrolled
			if(isset($uid)){
				$en = get_user_meta($uid, $enrollment_key, true);
				$gr = get_user_meta($uid, $grade_key, true);
			}
			else{
				$en = "ne";
				$gr = "na";
			}
			
			echo '<tr>';
			echo '<th>Enroll student in class: '.get_the_title().'</th>';
			echo '<th>Assign grade</th>';
			echo '</tr>';

			echo '<tr>';
				echo '<td>';
				echo '<select name="'.$enrollment_key.'" class="postbox">';

				echo '<option value="ne" '; 
				selected($en, 'ne');
				echo '>not-enrolled</option>';

				echo '<option value="e" '; 
				selected($en, 'e'); 
				echo '>enrolled</option>';
				
				echo '<option value="wl" '; 
				selected($en, 'wl'); 
				echo '>wait listed</option>';
				
				echo '</select>';
				echo '</td>';

				echo '<td>';
				echo '<select name="'.$grade_key.'" class="postbox">';

				echo '<option value="na" '; 
				selected($gr, 'na');
				echo '>N/A</option>';

				echo '<option value="ap" ';
				selected($gr, 'ap'); 
				echo '>A+</option>';

				echo '<option value="a" '; 
				selected($gr, 'a');
				echo '>A</option>';

				echo '<option value="am" '; 
				selected($gr, 'am');
				echo '>A-</option>';
				
				echo '<option value="bp" ';
				selected($gr, 'bp'); 
				echo '>B+</option>';

				echo '<option value="b" '; 
				selected($gr, 'b');
				echo '>B</option>';

				echo '<option value="bm" '; 
				selected($gr, 'bm');
				echo '>B-</option>';
				
				echo '<option value="cp" ';
				selected($gr, 'cp'); 
				echo '>C+</option>';

				echo '<option value="c" '; 
				selected($gr, 'c');
				echo '>C</option>';

				echo '<option value="cm" '; 
				selected($gr, 'cm');
				echo '>C-</option>';
				
				echo '<option value="d" ';
				selected($gr, 'd'); 
				echo '>D</option>';

				echo '<option value="f" '; 
				selected($gr, 'f');
				echo '>F</option>';

				echo '<option value="cr" '; 
				selected($gr, 'cr');
				echo '>CR</option>';
				
				echo '<option value="ncr" '; 
				selected($gr, 'ncr');
				echo '>NCR</option>';
				
				echo '<option value="w" '; 
				selected($gr, 'w');
				echo '>W</option>';
				
				echo '</select>';
				echo '</td>';
			echo '</tr>';
			
		}
	}
	echo '</table>';
	
	
	
	
	
	
	
	
	//add classes, grades, and attempts to a students transcript
	echo '<h3>Add to students transcript</h3>';
	echo '<table class="form-table">';	
	//we will use the class name_enrollment as the meta_key for enrollment value (e, ne) because the class names are UNIQUE.
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		while($q->have_posts()){
			$q->the_post();
			$transcript_key = str_replace(" ", "", strtolower(get_the_title())) . "_transcript";
			$fgrade_key = str_replace(" ", "", strtolower(get_the_title())) . "_fgrade";
			echo '<tr>';
			echo '<th>Add class to student transcript: '.get_the_title().'</th>';
			echo '<th>Add grade</th>';
			echo '</tr>';
			
			echo '<tr>';
			echo '<td>';
			echo '<select name="'.$transcript_key.'" class="postbox">';
			echo '<option value="dnac">do not add class</option>';
			echo '<option value="ac">add class</option>';
			echo '</select>';
			echo '</td>';
			
			echo '<td>';
			echo '<select name="'.$fgrade_key.'" class="postbox">';
			echo '<option value="na">N/A</option>';
			echo '<option value="ap">A+</option>';
			echo '<option value="a">A</option>';
			echo '<option value="am">A-</option>';
			echo '<option value="bp">B+</option>';
			echo '<option value="b">B</option>';
			echo '<option value="bm">B-</option>';
			echo '<option value="cp">C+</option>';			
			echo '<option value="c">C</option>';
			echo '<option value="cm">C-</option>';
			echo '<option value="d">D</option>';
			echo '<option value="f">F</option>';
			echo '<option value="cr">CR</option>';
			echo '<option value="ncr">NCR</option>';
			echo '<option value="w">W</option>';			
			echo '</select>';
			echo '</td>';
			echo '</tr>';
		}
	}
	echo '</table>';
	
	
	
	
	
	//display students transcript and grades for those classes, number of attempts
	//we will also calculate the gpa here and display it.
	$gp = 0.0;
	$numOfFactors = 0.0;
	echo '<h3>Transcript</h3>';
	echo '<table class="form-table">';
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		echo '<tr>';
		echo '<th>Class</th>';
		echo '<th>Grade</th>';
		echo '<th>Attempt #</th>';
		echo '<th>Remove</th>';
		echo '</tr>';
		while($q->have_posts()){
			$q->the_post();
			$transcript_key = str_replace(" ", "", strtolower(get_the_title())) . "_transcript";
			$fgrade_key = str_replace(" ", "", strtolower(get_the_title())) . "_fgrade";
			
			if(isset($uid)){
				for($i = 1; $i < 5; $i++){
					if(get_user_meta($uid, $transcript_key."_".strval($i), true) == "taken"){
						$gf = get_user_meta($uid, $fgrade_key."_".strval($i), true);
						$remove_key = str_replace(" ", "", strtolower(get_the_title())) . "_remove_".strval($i);

						//sum the grade points here are the factors
						//also set the GradeFinalPretty value here or else it will disply grades as "ap, am, a, cm, bm, ..."
						switch($gf){
							case 'na':
								$gf_p = 'N/A';
								break;
							case 'w':
								$gf_p = 'W';
								break;
							case 'ncr':
								$gf_p = 'NCR';
								break;
							case 'cr':
								$gf_p = 'CR';
								break;
							case 'f':
								$gp += 0.0;
								$numOfFactors += 1.0;
								$gf_p = 'F';
								break;
							case 'd':
								$gp += 1.0;
								$numOfFactors += 1.0;
								$gf_p = 'D';
								break;
							case 'cm':
								$gp += 1.7;
								$numOfFactors += 1.0;
								$gf_p = 'C-';
								break;
							case 'c':
								$gp += 2.0;
								$numOfFactors += 1.0;
								$gf_p = 'C';
								break;
							case 'cp':
								$gp += 2.3;
								$numOfFactors += 1.0;
								$gf_p = 'C+';
								break;
							case 'bm':
								$gp += 2.7;
								$numOfFactors += 1.0;
								$gf_p = 'B-';
								break;
							case 'b':
								$gp += 3.0;
								$numOfFactors += 1.0;
								$gf_p = 'B';
								break;
							case 'bp':
								$gp += 3.3;
								$numOfFactors += 1.0;
								$gf_p = 'B+';
								break;
							case 'am':
								$gp += 3.7;
								$numOfFactors += 1.0;
								$gf_p = 'A-';
								break;
							case 'a':
								$gp += 4.0;
								$numOfFactors += 1.0;
								$gf_p = 'A';
								break;
							case 'ap':
								$gp += 4.0;
								$numOfFactors += 1.0;
								$gf_p = 'A+';
								break;
							}
						
						echo '<tr>';
						echo '<td>'.get_the_title().'</td>';
						echo '<td>'.$gf_p.'</td>';
						echo '<td>'.$i.'</td>';
						
						//Add selector to remove this class
						echo '<td>';
						echo '<select name="'.$remove_key.'" class="postbox">';
						echo '<option value="drm">Keep this class</option>';
						echo '<option value="rm">Remove this class</option>';
						echo '</select>';
						echo '</td>';
						
						echo '</tr>';
					}
				}
			}else{
				//this is a new user so there is no transcript
			}			
		}
	}
	echo '</table>';
	//Print the gpa, and honor roll status
	if($numOfFactors > 0){
		$gpa = $gp/$numOfFactors;
		echo '<h3>GPA: '.sprintf("%.3f", $gpa).'</h3>';
		//display if student is on honor roll
		if($gpa > 3.5){
			echo '<h4>HONOR ROLL</h4>';
		}
	}else{
		echo '<h3>This student has no classes on their transcript that affect their gpa, or they have no classes on their transcript.</h3>';
	}


	//if the user is not a student (if they're an instructor or a newly added user, display this table)
	if(!in_array('student', $user_roles)){
		//Assign this instructor to classes
		echo '<h3>Instructor assignments</h3>';
		echo '<table class="form-table">';
		$classes_query = array('post_type' => 'gradschoolzeroclass');
		$q = new WP_Query($classes_query);
		if($q->have_posts()){
			while($q->have_posts()){
				$q->the_post();
				$assignment_key = str_replace(" ", "", strtolower(get_the_title())) . "_assignment";
				//If this is a new user, set the default enrollment status to not-enrolled
				if(isset($uid)){
					$as = get_user_meta($uid, $assignment_key, true);
				}
				else{
					$as = "na";
				}
				echo '<tr>';
				echo '<th>Assign instructor to class: '.get_the_title().'</th>';
				echo '</tr>';

				echo '<tr>';
					echo '<td>';
					echo '<select name="'.$assignment_key.'" class="postbox">';

					echo '<option value="na" '; 
					selected($as, 'na');
					echo '>not-assigned</option>';

					echo '<option value="a" '; 
					selected($as, 'a'); 
					echo '>assigned</option>';
					
					echo '</select>';
					echo '</td>';
				echo '</tr>';
			}
		}
		echo '</table>';
	}

}
  
/**
 * The save action.
 *
 * @param $user_id int the ID of the current user.
 *
 * @return bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function wporg_usermeta_form_field_update($user_id){
    // check that the current user have the capability to edit the $user_id
    if ( ! current_user_can('edit_user', $user_id)) return false;
	$arr = array();

	// create/update user meta warnings for the $user_id
	$ret_uid = update_user_meta(
		$user_id,
		'warn',
		$_POST['warn']
	);
	array_push($arr, $ret_uid);

    // create/update user meta for the $user_id
    $ret_uid = update_user_meta(
        $user_id,
        'phase',
        $_POST['phase']
    );
	array_push($arr, $ret_uid);
	
	// create/update user meta for the $user_id
    $ret_uid = update_user_meta(
        $user_id,
        'status',
        $_POST['status']
    );
	array_push($arr, $ret_uid);

	
	
	/*
	Save enrollment status and grades
	*/
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		while($q->have_posts()){
			$q->the_post();
			$enrollment_key = str_replace(" ", "", strtolower(get_the_title())) . "_enrollment";
			$grade_key = str_replace(" ", "", strtolower(get_the_title())) . "_grade";

			//Check if we are currently enrolling this student to the class or if this student is already enrolled
			if($_POST[$enrollment_key] == 'e'){
				//the class was taken or we're making it taken now
				//since this student is enrolled or we're enrolling them now, get the grade meta data
				//$gf = get_user_meta($user_id, $grade_key, true);
				//save the enrollment meta data
				$ret_uid = update_user_meta(
					$user_id,
					$enrollment_key,
					$_POST[$enrollment_key]
				);
				//push the user id that we just updated the meta data for
				array_push($arr, $ret_uid);
				//save the grade meta data
				$ret_uid = update_user_meta(
					$user_id,
					$grade_key,
					$_POST[$grade_key]
				);
				array_push($arr, $ret_uid);
			}else if($_POST[$enrollment_key] == 'wl'){
				//the student is waitlisted, or has been already waitlisted. Update the user meta data and destroy the grade
				$ret_uid = update_user_meta(
					$user_id,
					$enrollment_key,
					$_POST[$enrollment_key]
				);
				array_push($arr, $ret_uid);
				$ret_uid = delete_user_meta(
					$user_id, 
					$grade_key
				);
				array_push($arr, $ret_uid);		
			}else{
				//this student is either not-enrolled or waitlisted so they cannot have a grade
				//therefore destroy the previous enrollment and grades user meta data
				$ret_uid = update_user_meta(
					$user_id,
					$enrollment_key,
					'ne'
				);
				array_push($arr, $ret_uid);
				$ret_uid = update_user_meta(
					$user_id,
					$grade_key,
					'na'
				);
				array_push($arr, $ret_uid);
			}
		}
	}
	
	
	/*
	Save to transcript
	*/
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		while($q->have_posts()){
			$q->the_post();
			$transcript_key = str_replace(" ", "", strtolower(get_the_title())) . "_transcript";
			$fgrade_key = str_replace(" ", "", strtolower(get_the_title())) . "_fgrade";
			
			if($_POST[$transcript_key] == "ac"){
				//the registrar wants to add this class to the students transcript
				
				//Check if this student has taken this class before, if yes then how many? the student can only have 4 attempts.
				//assume the student has never taken the class before
				$hm = 0;
				//loop through i : 0 -> 4 and check the user meta data for <<classTitle>_transcript_<attempt>> where <attempt> = i
				for($i = 1; $i < 5; $i++){
					if(get_user_meta($user_id, $transcript_key."_".strval($i), true) == "taken"){
						//The student has taken this class before, add to the number of attempts
						$hm++;
					}
				}
				
				//add 1 to the number of attempts
				$a = $hm+1;
				
				if($hm < 5){
					//the student has taken this class less then five times, and this is not their 5th attempt, so we can add this attempt
					$ret_uid = add_user_meta(
						$user_id,
						$transcript_key."_".strval($a),
						"taken",
						true
					);
					array_push($arr, $ret_uid);
					$ret_uid = add_user_meta(
						$user_id,
						$fgrade_key."_".strval($a),
						$_POST[$fgrade_key],
						true
					);
					array_push($arr, $ret_uid);
				}else{
					//this is the students 5th+ attempt so we cannot add this class&grade to their transcript.
					//I'm not sure what to do here but definitely don't add this attempt.
				}

			}
		}
	}
	
	/*
	Remove from transcript
	*/
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		while($q->have_posts()){
			$q->the_post();
			$transcript_key = str_replace(" ", "", strtolower(get_the_title())) . "_transcript";
			$fgrade_key = str_replace(" ", "", strtolower(get_the_title())) . "_fgrade";
			$remove_key = str_replace(" ", "", strtolower(get_the_title())) . "_remove";
			
			//loop through all possible 4 attempts
			for($i = 1; $i < 5; $i++){
				//check if this attempt is the one the registrar selected to remove
				if(isset($_POST[$remove_key."_".strval($i)]) && $_POST[$remove_key."_".strval($i)] == 'rm'){
					//it is, so delete the transcript and fgrade user meta for this attempt
					$ret_uid = delete_user_meta(
						$user_id, 
						$transcript_key."_".strval($i)
					);
					array_push($arr, $ret_uid);
					$ret_uid = delete_user_meta(
						$user_id, 
						$fgrade_key."_".strval($i)
					);
					array_push($arr, $ret_uid);
				}
			}
		}
	}


	/*
	Save instructor assignments
	*/
	
	$classes_query = array('post_type' => 'gradschoolzeroclass');
	$q = new WP_Query($classes_query);
	if($q->have_posts()){
		while($q->have_posts()){
			$q->the_post();
			$assignment_key = str_replace(" ", "", strtolower(get_the_title())) . "_assignment";
			$ret_uid = update_user_meta(
				$user_id,
				$assignment_key,
				$_POST[$assignment_key]
			);
			//push the user id that we just updated the meta data for
			array_push($arr, $ret_uid);
		}
	}

	return $arr;
}

/*
The following are all the hooks that I used to make the user meta fields appear in the add new user screen and edit user screen.
*/
  
// Add the fields to user's own profile editing screen.
add_action('show_user_profile', 'wporg_usermeta_form_field');
  
// Add the fields to user profile editing screen.
add_action('edit_user_profile', 'wporg_usermeta_form_field');

add_action( "user_new_form", "wporg_usermeta_form_field" );
  
// Add the save action to user's own profile editing screen update.
//add_action('personal_options_update', 'wporg_usermeta_form_field_update');
  
// Add the save action to user profile editing screen update.
add_action('edit_user_profile_update', 'wporg_usermeta_form_field_update');

/*
These two are only required for when we want to display the user meta fields whilst adding a new user
*/
add_action('user_register', 'wporg_usermeta_form_field_update');
//add_action('profile_update', 'wporg_usermeta_form_field_update');




























/*


add_filter( 'wp_comment_reply', 'my_quick_edit_menu', 10, 2);
// Render our own comments quick edit menu
function my_quick_edit_menu($str, $input) {
    extract($input);
    $table_row = TRUE;
    if ( $mode == 'single' ) {
        $wp_list_table = _get_list_table('WP_Post_Comments_List_Table');
    } else {
        $wp_list_table = _get_list_table('WP_Comments_List_Table');
    }
 
    // Get editor string
    ob_start();
        $quicktags_settings = array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' );
    wp_editor( '', 'replycontent', array( 'media_buttons' => false, 'tinymce' => false, 'quicktags' => $quicktags_settings, 'tabindex' => 104 ) );
    $editorStr = ob_get_contents();
    ob_end_clean();
 
 
    // Get nonce string
    ob_start();     
    wp_nonce_field( "replyto-comment", "_ajax_nonce-replyto-comment", false );
        if ( current_user_can( "unfiltered_html" ) )
          wp_nonce_field( "unfiltered-html-comment", "_wp_unfiltered_html_comment", false );
    $nonceStr = ob_get_contents();
    ob_end_clean();
 
 
    $content = '<form method="get" action="">';
    if ( $table_row ) : 
        $content .= '<table style="display:none;"><tbody id="com-reply"><tr id="replyrow" style="display:none;"><td colspan="'.$wp_list_table->get_column_count().'" class="colspanchange">';
    else : 
        $content .= '<div id="com-reply" style="display:none;"><div id="replyrow" style="display:none;">';
    endif;
 
    $content .= '
            <div id="replyhead" style="display:none;"><h5>Reply to Comment</h5></div>
            <div id="addhead" style="display:none;"><h5>Add new Comment</h5></div>
            <div id="edithead" style="display:none;">';
             
    $content .= '   
                <div class="inside">
                <label for="author-name">Name</label>
                <input type="text" name="newcomment_author" size="50" value="" tabindex="101" id="author-name" />
                </div>
         
                <div class="inside">
                <label for="author-email">E-mail</label>
                <input type="text" name="newcomment_author_email" size="50" value="" tabindex="102" id="author-email" />
                </div>
         
                <div class="inside">
                <label for="author-url">URL</label>
                <input type="text" id="author-url" name="newcomment_author_url" class="code" size="103" value="" tabindex="103" />
                </div>
                <div style="clear:both;"></div>';
        $id = 100;
        // Add new quick edit fields    
        $content .= '
        <div class="inside">
        <label for="'.$id.'rat"></label>

        <input type="text" id="'.$id.'rat" name="'.$id.'rat" size="103" value="" tabindex="103" />
        </div>

        <div style="clear:both;"></div>
        </div>
        ';

        



    $content .= '
                <div class="inside">
                <label for="rat">Rating</label>'.
              
                '<fieldset>'.
                '<select id="rat" name="rat" class="postbox" tabindex="103">'.
                
                '<option value="1" '.
                '>1</option>'.
                
                '<option value="2" '.
                '>2</option>'.
              
                '<option value="3" '.
                '>3</option>'.
              
                '<option value="4" '.
                '>4</option>'.
              
                '<option value="5" '.
                '>5</option>'.
                
                '</select>'.
                '</fieldset>'.
                '</div>'.
                '<div style="clear:both;"></div>
            </div>
            ';






            

         
    // Add editor
    $content .= "<div id='replycontainer'>\n";    
    $content .= $editorStr;
    $content .= "</div>\n";   
             
    $content .= '           
            <p id="replysubmit" class="submit">
            <a href="#comments-form" class="cancel button-secondary alignleft" tabindex="106">Cancel</a>
            <a href="#comments-form" class="save button-primary alignright" tabindex="104">
            <span id="addbtn" style="display:none;">Add Comment</span>
            <span id="savebtn" style="display:none;">Update Comment</span>
            <span id="replybtn" style="display:none;">Submit Reply</span></a>
            <img class="waiting" style="display:none;" src="'.esc_url( admin_url( "images/wpspin_light.gif" ) ).'" alt="" />
            <span class="error" style="display:none;"></span>
            <br class="clear" />
            </p>';
             
        $content .= '
            <input type="hidden" name="user_ID" id="user_ID" value="'.get_current_user_id().'" />
            <input type="hidden" name="action" id="action" value="" />
            <input type="hidden" name="comment_ID" id="comment_ID" value="" />
            <input type="hidden" name="comment_post_ID" id="comment_post_ID" value="" />
            <input type="hidden" name="status" id="status" value="" />
            <input type="hidden" name="position" id="position" value="'.$position.'" />
            <input type="hidden" name="checkbox" id="checkbox" value="';
         
    if ($checkbox) $content .= '1'; else $content .=  '0';
    $content .= "\" />\n"; 
        $content .= '<input type="hidden" name="mode" id="mode" value="'.esc_attr($mode).'" />';
         
    $content .= $nonceStr;
    $content .="\n";
         
    if ( $table_row ) : 
        $content .= '</td></tr></tbody></table>';
    else : 
        $content .= '</div></div>';
    endif; 
    $content .= "\n</form>\n";
    return $content;
}


add_filter( 'comment_text', 'my_menu_data', 10, 2);
function my_menu_data($comment_text, $comment ) {
    ?>
        <div id="inline-xtra-<?php echo $comment->comment_ID; ?>" class="hidden">
        <div class="rat"><?php echo absint(get_comment_meta($comment->comment_ID, 'rat', true)); ?></div>
        </div>
        <?php
    return $comment_text;
}

// Add quick edit javascript to the page footer
add_action('admin_footer', 'my_quick_edit_javascript');
 
function my_quick_edit_javascript() {
?>
    <script type="text/javascript">
    function expandedOpen(id) {
        editRow = jQuery('#replyrow');
        rowData = jQuery('#inline-xtra-'+id);
        jQuery('#rat', editRow).val( jQuery('div.rat', rowData).text() );
    }   
    </script>
   <?php
}


add_filter( 'comment_row_actions', 'my_quick_edit_action', 10, 2); 
 
function my_quick_edit_action($actions, $comment ) {
    global $post;
    $actions['quickedit'] = '<a onclick="commentReply.close();if (typeof(expandedOpen) == \'function\') expandedOpen('.$comment->comment_ID.');commentReply.open( \''.$comment->comment_ID.'\',\''.$post->ID.'\',\'edit\' );return false;" class="vim-q" title="'.esc_attr__( 'Quick Edit' ).'" href="#">' . __( 'Quick&nbsp;Edit' ) . '</a>';
    return $actions;
}

function my_check_screen($screen) {
  if (defined('DOING_AJAX') || 
      isset($screen->id) && in_array($screen->id, array('edit-comments','post', 'page', 'gallery')) ) {
      add_filter( 'wp_comment_reply', 'comment_quick_edit', 10, 2);
      add_filter( 'comment_text', 'get_current_comment', 10, 2); 
      add_action('admin_footer', 'quick_edit_javascript');
      add_filter( 'comment_row_actions',  'quick_edit_action', 10, 2); 
  }
  return $screen;
}
*/