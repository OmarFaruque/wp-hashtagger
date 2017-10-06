<?php 
/*
* Ajax Functions
*/
if(!function_exists('check_tags')){
	function check_tags(){
	global $wpdb;
	$accessHas =  ( get_option( 'swcc_htg_tags_no_links', '0' ) == 0 ) ?  true : false;
	$linkCss = (get_option( 'swcc_htg_cssclass_notag', '' ))?get_option( 'swcc_htg_cssclass_notag', '' ):'';
	$nosembol = ( get_option( 'swcc_htg_display_nosymbols', '0' ) == 0 ) ?  0 : 1;
	$tooltip = ( get_option( 'swcc_htg_display_form_tooltip', '0' ) == 0 ) ?  0 : 1;
	$prefix = $wpdb->prefix;
	$term_rl = $prefix . 'term_relationships';
	$reg_tble = $prefix . 'tag_registration';
	if($wpdb->get_var("SHOW TABLES LIKE '$reg_tble'") != $reg_tble) {
		     //table not in database. Create new table
		     $charset_collate = $wpdb->get_charset_collate();
		     $sql = "CREATE TABLE $reg_tble (
		          id mediumint(10) NOT NULL AUTO_INCREMENT,
		          tag_name varchar(500) NOT NULL,
		          name varchar(500) NOT NULL,
		          email varchar(500) NOT NULL,
		          date timestamp NOT NULL,
		          UNIQUE KEY id (id)
		     ) $charset_collate;";
		     require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		     dbDelta( $sql );
	}

	$tag = get_term_by('name', $_POST['tag'], 'post_tag');
	$qury = $wpdb->get_row("SELECT COUNT(*) as `post_count` FROM `".$term_rl."` WHERE `term_taxonomy_id` = ".$tag->term_taxonomy_id."", OBJECT); // total count of post related tag
	$exReg = $wpdb->get_row("SELECT COUNT(*) as `r_tag` FROM $reg_tble WHERE `tag_name`='".$_POST['tag']."' AND `email`='".$_POST['l_mail']."'", OBJECT); //check if already registered
	$tag->post_count = ($qury->post_count > 0)?$qury->post_count:$tag->count;
	$tag->al_reg = $exReg->r_tag;
	$tag->css_class = $linkCss;
	$tag->symbol = $nosembol;
	$tag->tooltip = $tooltip;
	if($qury && $accessHas && $tag->post_count > 0){
		echo json_encode( $tag );	
	}

	die();
	}
	
	add_action("wp_ajax_nopriv_check_tags", "check_tags");
	add_action( 'wp_ajax_check_tags', 'check_tags' );

}
if(!function_exists('register_tags')){
	function register_tags(){
	global $wpdb;

	$prefix = $wpdb->prefix;
	$term_db = $prefix . 'tag_registration';
	$tag = $_POST['tag'];
	$currentUsr = get_currentuserinfo();
	$email = $currentUsr->user_email;
	$name 	= $currentUsr->display_name;
	

	$select = $wpdb->get_row("SELECT COUNT(*) as `total` FROM $term_db WHERE `tag_name`='".$tag."' AND `email`='".$email."'", OBJECT);
	if((int)$select->total <= 0 ){
		$query = "INSERT INTO $term_db (tag_name, name, email) VALUES ('%s', '%s', '%s')";
	    $insert = $wpdb->query($wpdb->prepare($query, $tag, $name, $email));
	}
	if($insert){
		echo 'success';
	}else{
		echo 'failed';
	}
	die();
	}
	add_action("wp_ajax_nopriv_register_tags", "register_tags");
	add_action( 'wp_ajax_register_tags', 'register_tags' );
}


if(!function_exists('sabai_hashtag_process')){
	function sabai_hashtag_process(){
	global $wp_hashtagger, $wpdb;
	$prefix = $wpdb->prefix;
	$relationTbl = $prefix . 'term_relationships';
	$content = $_POST['content'];
	$postid = $_POST['id'];
	$has = $wp_hashtagger->get_hashtags_from_content( strip_tags( $content ) );
	$advanced_nodelete = ( get_option( 'swcc_htg_advanced_nodelete', '0' ) == 0 ) ?  false : true;

	wp_set_post_tags( $postid, $has, $advanced_nodelete );

	die();
	}
	add_action("wp_ajax_nopriv_sabai_hashtag_process", "sabai_hashtag_process");
	add_action( 'wp_ajax_sabai_hashtag_process', 'sabai_hashtag_process' );
}


/*
* Hastag delete function
*/
if(!function_exists('hastag_delete')){
	function hastag_delete(){
	global $wp_hashtagger;
	$tag = $_POST['tag'];
	$email = $_POST['email'];
	$has = $wp_hashtagger->delete_hastag($tag, $email);
	if($has == 'success'){
		echo 'success';
	}else{
		echo 'failed';
	}
	die();
	}
	add_action("wp_ajax_nopriv_hastag_delete", "hastag_delete");
	add_action( 'wp_ajax_hastag_delete', 'hastag_delete' );
}



?>