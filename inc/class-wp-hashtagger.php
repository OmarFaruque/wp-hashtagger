<?php

/**
 * The hashtagger core plugin class
 */


 // If this file is called directly, abort
 if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * The core plugin class
 */
if ( !class_exists( 'WP_Hashtagger' ) ) { 

  class WP_Hashtagger {
    
    public $plugin_name;
    public $plugin_slug;
    public $version;
    private $settings;
    private $wp_url;
    private $my_url;
    private $dc_url;
    private $_file;
		private $admin_handle;
    protected $regex_general;
    protected $regex_notag;
    protected $regex_users;
    protected $regex_cash;
    protected $regnonce;
    
    
    /**
		 * here we go
		 */
    public function __construct( $file ) {
      
      $this->_file = $file;
      $this->plugin_name = 'wp-hashtagger';
      $this->plugin_slug = 'wp-hashtagger';
      $this->version = '1.0.1';
      $this->get_settings();
      $this->init();
      
    } 
    
    
    /**
		 * do plugin init
		 */
    private function init() {
      
      $this->wp_url = 'https://wordpress.org/plugins/' . $this->plugin_slug;
      $this->my_url = 'http://larasoftbd.com/free-wordpress-plugins/' . $this->plugin_slug;
      $this->dc_url = 'http://larasoftbd.com/docs/' . $this->plugin_slug;
      
      $this->regex_general = '/(^|[\s!\.:;\?(>])#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
      $this->regex_notag = '/(^|[\s!\.:;\?(>])\+#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
      
      if ( true === $this->settings['tags_allow_numeric'] ) {
        
        // Allow Tags to start with numbers
        $this->regex_general = '/(^|[\s!\.:;\?(>])#([\p{L}0-9][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
        $this->regex_notag = '/(^|[\s!\.:;\?(>])\+#([\p{L}0-9][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
        
      }
      
      $this->regex_users = '/(^|[\s!\.:;\?(>])\@([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
      $this->regex_cash = '/(^|[\s!\.:;\?(>])\$([A-Z][A-Z\-]+)(?=[^<>]*(?:<|$))/u';
      
      $this->regnonce = 'hashtagger_regenerate';
      
      add_action( 'init', array( $this, 'add_text_domain' ) );
      add_action( 'init', array( $this, 'create_taxonomy_timestamp' ) );
      //add_action( 'init', array( $this, 'people_init' ) );
      //add_action('init', array( $this, 'tags_support_all' ));
      add_action('pre_get_posts', array( $this, 'tags_support_query' ));

      add_action( 'save_post', array( $this, 'generate_tags' ), 19 );

      add_action( 'comment_post', array( $this, 'process_comments_post' ), 10, 2 );
      
      
      // *** For Plugin User Submitted Posts https://wordpress.org/plugins/user-submitted-posts/ (since v 3.2)
      //     had to use filter usp_new_post insetad of action usp_insert_after because tags are created AFTER usp_insert_after
      add_filter( 'usp_new_post', array( $this, 'process_content_for_user_submitted_posts' ), 9999 );
      
      // *** For Barley - Inline Editing Plugin for WordPress (since v 3.2)
      //     had to override their save function...
      add_action( 'wp_ajax_barley_update_post',  array( $this, 'process_content_for_barely' ), 0 );
    
      //add_filter( 'comment_text', 'comment_proccess_hash', 10, 3 );
      //add_action( 'preprocess_comment', array($this, 'comment_proccess_hash'), 9999);
      add_action( 'wp_enqueue_scripts', array($this, 'add_script'));
      add_action( 'admin_enqueue_scripts', array($this, 'admin_add_script'));
      add_action( 'wp_head', array($this, 'add_internal_script'));
      add_action( 'wp_footer', array($this, 'add_internal_script_footer'));

      if($this->settings['display_buddypress_button']){
        add_action('bp_after_activity_entry_comments', array($this, 'laras_after_activity_entry_comments'), 10, 0);
      }

      add_action( 'bp_activity_comment_posted', array($this, 'hastagFromBuddypressComments'), 10, 3 );

      add_filter( 'bp_activity_comment_content', array($this,'filter_bp_activity_comment_content'), 10, 1 ); //LS
      

      if ( ! is_admin() ) {
        
        add_filter( 'the_content', array( $this, 'process_content' ), 9999 );
        
        if ( $this->settings['sectiontype_title'] ) {
          
          add_filter( 'the_title', array( $this, 'process_title' ), 9999 );
          
        }
        
        if ( $this->settings['sectiontype_excerpt'] ) {
          
          add_filter( 'the_excerpt', array( $this, 'process_excerpt' ), 9999 );
          
        }
        
      } else {
        
        add_action( 'admin_head', array( $this, 'admin_css' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'wp_ajax_hashtagger_regenerate', array( &$this, 'admin_hashtagger_regenerate' ) );
      
      }
      
      add_filter( 'plugin_action_links_' . plugin_basename( $this->_file ), array( $this, 'add_settings_links' ) ); 
      
    }
    
    
    //LaraSoft
    function process_comments_post($comment_ID, $comment_approved){
      if( 1 === $comment_approved ){
        $hash = $this->get_hashtags_from_content( strip_tags( $_POST['comment'] ) );
        add_comment_meta( $comment_ID, 'wp_hastag', $hash );
      }
    }


    /*
    * Filter buddypress comment
    */
    function hastagFromBuddypressComments($comment_id, $r, $activity){
     
      $content = $r['content'];
      $hash = $this->get_hashtags_from_content( strip_tags( $content ) );
      wp_set_post_tags( $comment_id, $hash, $this->settings['advanced_nodelete'] );
     
    }

    /*
    * Filter buddypress comment content
    */
    function filter_bp_activity_comment_content($r_content){
        $hash = $this->get_hashtags_from_content( strip_tags( $r_content ) );
        $content = str_replace( '##', '#', preg_replace_callback( $this->regex_notag, array( $this, 'make_link_notag' ), preg_replace_callback( $this->regex_general, array( $this, 'make_link_tag' ), $r_content ) ) );
      return $content;
    }

    /**
		 * add text domain
		 */
    function add_text_domain() {  
    
      load_plugin_textdomain( 'hashtagger' );
      
    }
    
    /*
    * Add Extra column to TAxonomony
    */
    function create_taxonomy_timestamp(){
      global $wpdb;
      $prefix = $wpdb->prefix;
      $term_rl = $prefix . 'term_relationships';
      //table not in database. Create new table
      $row = $wpdb->get_row(  "SELECT * FROM $term_rl", OBJECT );
      if(!isset($row->time_stamp)){
         $wpdb->query("ALTER TABLE $term_rl ADD time_stamp TIMESTAMP NOT NULL");
      }

    }


    
    /**
		 * get all settings
		 */
    private function get_settings() {
      
      $this->settings = array();
      $this->settings['posttype_page'] = ( get_option( 'swcc_htg_posttype_page', '1' ) == 1 ) ?  true : false;
      $this->settings['posttype_custom'] = ( get_option( 'swcc_htg_posttype_custom', '1' ) == 1 ) ?  true : false;
      $this->settings['sectiontype_title'] = ( get_option( 'swcc_htg_sectiontype_title', '0' ) == 1 ) ?  true : false;
      $this->settings['sectiontype_excerpt'] = ( get_option( 'swcc_htg_sectiontype_excerpt', '0' ) == 1 ) ?  true : false;
      $this->settings['advanced_nodelete'] = ( get_option( 'swcc_htg_advanced_nodelete', '0' ) == 0 ) ?  false : true;
      $this->settings['advanced_email_notif'] = ( get_option( 'swcc_htg_advanced_email_notif', '0' ) == 0 ) ?  false : true;

      
      $this->settings['usernames'] = get_option( 'swcc_htg_usernames', 'NONE' );
      
      if ( ! in_array( $this->settings['usernames'], array( 'NONE', 'PROFILE', 'WEBSITE-SAME', 'WEBSITE-NEW' ) ) ) {
        
        $this->settings['usernames'] = 'NONE';
        
      }
      
      $this->settings['cashtags'] = get_option( 'swcc_htg_cashtags', 'NONE' );
      
      if ( ! in_array( $this->settings['cashtags'], array( 'NONE', 'MARKETWATCH-SAME', 'MARKETWATCH-NEW', 'GOOGLE-SAME', 'GOOGLE-NEW', 'YAHOO-SAME', 'YAHOO-NEW' ) ) ) {
        
        $this->settings['cashtags'] = 'NONE';
        
      }
      
      $this->settings['usernamesnick'] = ( get_option( 'swcc_htg_usernamesnick', '0' ) == 0 ) ?  false : true;
      $this->settings['cssclass'] = get_option( 'swcc_htg_cssclass_notag', '' ); 
      $this->settings['cssclass_notag'] = get_option( 'swcc_htg_cssclass_notag', '' );
      $this->settings['usernamescssclass'] = get_option( 'swcc_htg_usernamescssclass', '' );
      $this->settings['cashtagcssclass'] = get_option( 'swcc_htg_cashtagcssclass', '' );
      $this->settings['display_nosymbols'] = ( get_option( 'swcc_htg_display_nosymbols', '0' ) == 0 ) ?  false : true;
      $this->settings['display_tooltip'] = ( get_option( 'swcc_htg_display_form_tooltip', '0' ) == 0 ) ?  false : true;
      $this->settings['display_buddypress_button'] = ( get_option( 'swcc_htg_button_buddypress_activity', '0' ) == 0 ) ?  false : true;
      
      $this->settings['tags_allow_numeric'] = ( get_option( 'swcc_htg_tags_allow_numeric', '0' ) == 0 ) ?  false : true;
      $this->settings['tags_no_links'] = ( get_option( 'swcc_htg_tags_no_links', '0' ) == 0 ) ?  false : true;
      $tagbase = get_option( 'tag_base' );
      
      if ( $tagbase != '' ) {
        $tagbase = trailingslashit( $tagbase );
      }
      
      $this->settings['tagbase'] = $tagbase;
      
    }
    

// ADD JS FILE    
function add_script(){
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'wp-hashtagger-qtip', plugins_url() . '/wp-hashtagger/assets/front/jquery.qtip.min.js', array('jquery'), '9302017', false );
        wp_enqueue_script( 'wp-hashtagger-js', plugins_url() . '/wp-hashtagger/assets/front/script.js', array('jquery'), '9162017', false );
        wp_localize_script( 'wp-hashtagger-js', 'hastagger_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        


        //CSS
        wp_enqueue_style( 'jQueryUicss', 'http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), '919201701', 'screen' );
        wp_enqueue_style( 'css-hastag', plugins_url() . '/wp-hashtagger/assets/front/style.css', array(), '919201702', 'screen' );
        wp_enqueue_style( 'css-qtip', plugins_url() . '/wp-hashtagger/assets/front/jquery.qtip.min.css', array(), '9201702', 'screen' );
}

function admin_add_script(){
  wp_enqueue_script( 'wp-ahashtagger-js', plugins_url() . '/wp-hashtagger/assets/admin/whastagger.js', array('jquery'), '9182017', false );
  wp_localize_script( 'wpahashtagger-js', 'admin_hastagger_ajax', array( 'admin_ajaxurl' => admin_url( 'admin-ajax.php' ) ) );  
}



/*
*  LaraSoft Add internal Script
*/
function add_internal_script(){
  $output = '
  <script type="text/javascript">
    var base_url = "'.get_home_url().'";
    var current_user_id = '.get_current_user_id().';
    var active_email = '.$this->settings['advanced_email_notif'].';
  </script>
  ';
  echo $output;
}


/*
* All hastags
*/
function get_all_terms(){
  $terms = get_terms( 'post_tag', array('hide_empty' => 0));
  $empty_terms = array();
  foreach($terms as $ter) array_push($empty_terms, '"#'.$ter->slug.'"');
  return $empty_terms;
}


/*
* Delete HasTag //LaraSoft
*/
function delete_hastag($tag, $email){
  global $wpdb;
  $prefix = $wpdb->prefix;
  $term_db = $prefix . 'tag_registration';
  $qry = $wpdb->query( 
     $wpdb->prepare( 
        "DELETE FROM $term_db
         WHERE `tag_name` = %s AND `email` = %s",
            $tag, $email
        )
  );
  if($qry){
    return 'success';
  }
}

/*
* Frontend Footer Action
*/
function add_internal_script_footer(){ 
  /*if($_POST){
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
  }*/

  $output ='
  <script type="text/javascript">
  jQuery(function($){
    var availableTags = ['.implode(',', $this->get_all_terms()).'];
    function split( val ) {
      return val.split( / \s*/ );
    }
    function extractLast( term ) {
      return split( term ).pop();
    }
   
    $( "textarea, input#registEmailinput" )
      // dont navigate away from the field on tab when selecting an item
      .on( "keydown", function( event ) {

        if ( event.keyCode === $.ui.keyCode.TAB &&
            $( this ).autocomplete( "instance" ).menu.active ) {
          event.preventDefault();
        }
      })
      .autocomplete({
        minLength: 0,
        source: function( request, response ) {
          
        var last = extractLast(request.term);
        if (last.match("^#")) {
            response( $.ui.autocomplete.filter(
              availableTags, extractLast( request.term ) 
            ));
        }

        },
        position: {  collision: "flip"  },
      
        focus: function() {
          // prevent value inserted on focus
          return false;
        },
        select: function( event, ui ) {
          var terms = split( this.value );
          // remove the current input
          terms.pop();
          // add the selected item
          terms.push( ui.item.value );
          // add placeholder to get the comma-and-space at the end
          terms.push( "" );
          this.value = terms.join( " " );
          return false;
        }
        
      });
  
    }); // End Document Ready
  </script>
  ';
  echo $output;
}


//LaraSoft
function lawzone_all_post_types(){
      global $wpdb;
      $table = $wpdb->prefix . 'posts';
      $post_types = $wpdb->get_results( "SELECT `post_type` FROM ".$table." GROUP BY `post_type`", OBJECT );
      $alltypes = array();
      foreach($post_types as $pst) array_push($alltypes, $pst->post_type);

      $removs = array('attachment', 'nav_menu_item');
      $posts = array_diff($alltypes, $removs);
      return $posts;
}


/*
* Email Send 
*/
function email_new_aricle($post_id){
  global $wpdb;
  $prefix = $wpdb->prefix;
  $term_db = $prefix . 'tag_registration';

  $tags = wp_get_post_tags($post_id);
  $alltgs = array();
  foreach($tags as $t) array_push($alltgs, $t->slug);
  $alltgs = join("', '", $alltgs);

  $selectall = $wpdb->get_results("SELECT * FROM $term_db WHERE `tag_name` IN ('".$alltgs."') ", OBJECT);
  $exemail = array();
  foreach($selectall as $m) array_push($exemail, $m->email);
  $getmLists = get_post_meta( $post_id, 'wp-hash-notification', true ); 
  $getmListss = unserialize($getmLists);
  $getmListArray = ($getmLists)?$getmListss:array();

  $newMail = array_diff($exemail, $getmListArray);
  $excerpt = get_post_field('post_excerpt', $post_id);
  $content = get_post_field('post_content', $post_id);


  $headers[] = 'Content-type: text/html;charset=utf-8';
  $headers[] = 'From: '.get_bloginfo( 'name' ).' <'.get_bloginfo( 'admin_email' ).'>';
  $message = '<h2 style="margin-bottom:15px;">' . get_the_title( $post_id ) . '</h2>';
  $message .= ($excerpt)?$excerpt:$content;
  $message .= ' <a class="moretag" href="'. get_permalink($post_id) . '"> Read the full article...</a>';
  $newlist = array();
  foreach($newMail as $mail):
  $send = wp_mail( $mail, get_the_title( $post_id ), $message, $headers);
  if($send) array_push($newlist, $mail);
  endforeach;
  $newMeta = array_merge($getmListArray, $newlist );
  
  update_post_meta( $post_id, 'wp-hash-notification', serialize($newMeta) );

}



/*
* Front End buddypress loop action
*/
function laras_after_activity_entry_comments(){
  global $bp, $wpdb;
  $prefix = $wpdb->prefix;
  $acvTbl = $prefix . 'bp_activity';
  $id = bp_get_activity_id();
  $qry = "SELECT `content` FROM $acvTbl WHERE `secondary_item_id` = ".$id." AND `type` = 'activity_comment'";
  $results = $wpdb->get_results($qry, OBJECT);
  $commentArray = array();
  foreach($results as $r) array_push($commentArray, $this->get_hashtags_from_content( strip_tags( $r->content ) ));
  $explode = implode(',', $commentArray);
  
  $content = bp_get_activity_comment_content();
  $activity_id = $bp->bp_activity_get_activity_id();
  $hash = $this->get_hashtags_from_content( strip_tags( bp_get_activity_content_body() ) );
  $finalHash =''; 
  $finalHash .= ($hash)?$hash . ',':''; 
  $finalHash .= ($explode)?$explode:'';
  $finalHash = array_filter(array_unique(array_map('trim', explode(',', $finalHash))));

  echo '<div style="margin-bottom:10px;" class="col-md-12 col-sm-12">';
  foreach($finalHash as $sh){ 
    $this->make_link_button($sh); 

  }
  echo '</div>';

  
  
  
}

  // add tag support to pages
  /*function tags_support_all() {
    $post_types = $this->lawzone_all_post_types();

  register_taxonomy(
        'post_tag',
        $post_types,
        array(
            'label' => __( 'Tags' ),
            'rewrite' => array( 'slug' => 'tag' ),
            'hierarchical' => false
        )
    );
  }*/
  function tags_support_query($wp_query) {
    if ($wp_query->get('tag')) $wp_query->set('post_type', 'any');
  }

 

    
    /**
		 * helper function to extract the hashtags from content and to add them as tags to the post
     * since v 3.0 option to not delete unused tags
		 */
    function generate_tags( $postid ) {

      
      $post_type = get_post_type( $postid );
      $custom = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
      
      if ( ( 'post' == $post_type ) || ( 'page' == $post_type && $this->settings['posttype_page'] ) || ( in_array( $post_type, $custom ) && $this->settings['posttype_custom'] ) ) {
        
        $content = get_post_field('post_content', $postid);
        
        if ( $this->settings['sectiontype_title'] ) {
          
          $content = $content . ' ' . get_post_field('post_title', $postid);
          
        }
        
        if ( $this->settings['sectiontype_excerpt'] ) {
          
          $content = $content . ' ' . get_post_field('post_excerpt', $postid);
          
        }
        
        wp_set_post_tags( $postid, $this->get_hashtags_from_content( strip_tags( $content ) ), $this->settings['advanced_nodelete'] );
        
        $this->email_new_aricle($postid);

      }
      
    }
    
    
    /**
		 * helper function to get an array of hashtags from a given content - used by generate_tags()
		 */
    function get_hashtags_from_content( $content ) {
      
      preg_match_all( $this->regex_general, $content, $matches );
      return implode( ', ', $matches[2] );
      
    }
    
    
    /**
		 * process content
     *
     * @uses make_link_tag()
     * @uses make_link_notag()
     * @uses make_link_usernames()
     * @uses make_link_cashtags()
		 */
    function work( $content ) { 
    
      if ( ! $this->settings['tags_no_links'] ) {

        $content = str_replace( '##', '#', preg_replace_callback( $this->regex_notag, array( $this, 'make_link_notag' ), preg_replace_callback( $this->regex_general, array( $this, 'make_link_tag' ), $content ) ) );
        
      }
      
      if ( $this->settings['usernames'] != 'NONE' ) {
        
        $content = str_replace( '@@', '@', preg_replace_callback( $this->regex_users, array( $this, 'make_link_usernames' ), $content ) );
        
      }
      
      if ( $this->settings['cashtags'] != 'NONE' ) {
        
        $content = str_replace( '$$', '$', preg_replace_callback( $this->regex_cash, array( $this, 'make_link_cashtags' ), $content ) );
        
      }
      
      return $content;
      
    }
    
    //LaraSoft

    /*function comment_proccess_hash($comment_text, $comment = null){
       $comment_text = str_ireplace(' rel="nofollow"', '', $comment_text );
       return $comment_text;
    }*/
    /**
		 * replace hashtags with links when displaying content
     * post type depending since v 3.0
		 */
    function process_content( $content ) {
      
      global $post;
      
      $post_type = get_post_type();
      $custom = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
      
      if ( ( 'post' == $post_type ) 
        || ( 'page' == $post_type && $this->settings['posttype_page'] ) 
        || ( in_array( $post_type, $custom ) && $this->settings['posttype_custom'] ) ) {
        
        $content = $this->work( $content );
        
      }
      
      return $content;
      
    }
    
    
    /**
		 * process title 
     *
     * @uses process_content()
     * @since 3.0
		 */
    function process_title( $title, $id = null ) {
      return $this->process_content( $title );
    }
    
    
    /**
		 * process excerpt
     *
     * @uses process_content()
     * @since 3.0
		 */
    function process_excerpt( $excerpt ) {
      
      return $this->process_content( $excerpt );
      
    }
    
    
    /**
     * create link for a #hashtag
		 * callback function for preg_replace_callback
     *
     * @uses make_link()
     */
    function make_link_tag( $match ) {
      
      return $this->make_link( $match, true );
      
    }
    
    
    /**
     * create link for a +#hashtag
		 * callback function for preg_replace_callback
     *
     * @uses make_link()
     */
    function make_link_notag( $match ) {
      
      return $this->make_link( $match, false );
      
    }
    
    
    /**
     * create link for a @username
		 * callback function for preg_replace_callback
     *
     * @uses make_link_users()
     */
    function make_link_usernames( $match ) {
      
      return $this->make_link_users( $match, $this->settings['usernames'] );
      
    }
    
    
    /**
     * create link for a $cashtag
		 * callback function for preg_replace_callback
     *
     * @uses make_link_cash()
     */
    function make_link_cashtags( $match ) {
      
      return $this->make_link_cash( $match, $this->settings['cashtags'] );
      
    }
    

    function make_link_button($text){
       $terms = get_term_by('name', $text, 'post_tag');
       global $wpdb;
       $prefix = $wpdb->prefix;
       $term_rl = $prefix . 'term_relationships';
       $qury = $wpdb->get_row("SELECT COUNT(*) as `post_count` FROM `".$term_rl."` WHERE `term_taxonomy_id` = ".$terms->term_taxonomy_id." AND `time_stamp` < DATE_SUB(NOW(),INTERVAL 15 MINUTE)", OBJECT); //
       $totalPost = $qury->post_count;
       $symbol = '';
       if ( ! $this->settings['display_nosymbols'] ) {
            
           $symbol .= '&num;';
            
       } else {
            
         $symbol .= '';
            
       }

      $termid = $terms->term_id;
      $content_h = '';
      if((int)$totalPost > 0){
        $content_h .= '<a class="btn btn-primary btn-hastagger mkLsJs" href="' . get_tag_link( $termid ) . '">'.$symbol . $text .'('.(int)$totalPost.')</a>';
        echo $content_h;
      }

    }
    
    /**
     * helper function to generate tag link
     * used by make_link_tag and make_link_notag
     */
    private function make_link( $match, $mktag ) {
      global $post;
      if ( $match[2] != strip_tags( $match[2] )  ) {
        
        $content = $match[0];
        
      } else {
        
        $terms = get_term_by('name', $match[2], 'post_tag');

        //$terms = get_terms( array( 'taxonomy' => 'post_tag', 'name' => $match[2] ) );

        global $wpdb;
        $prefix = $wpdb->prefix;
        $term_rl = $prefix . 'term_relationships';
       

        $qury = $wpdb->get_row("SELECT COUNT(*) as `post_count` FROM `".$term_rl."` WHERE `term_taxonomy_id` = ".$terms->term_taxonomy_id."", OBJECT); // total count of post related tag


        $totalPost = $qury->post_count;
        $postClass = ((int)$totalPost > 0)?'hastag_c_1 mkLsJs':'hastag_c_0';
        if ( ! $terms ) {
          $content = $match[0];
        } else {
          $term = $terms;
          $termid = $term->term_id;
          $slug = $term->slug;
          

          if ( $mktag ) {
            $css = $this->settings['cssclass'];
          } else {
            $css = $this->settings['cssclass_notag'];
          }


          $css .= ' '.$postClass;
          $css = ' class="' . $css . '"';
            
          
          if ( ! $this->settings['display_nosymbols'] ) {
            
            $symbol = '#';
            
          } else {
            
            $symbol = '';
            
          }
          //echo 'totallPost: ' . $totalPost . '<br/>';
          if((int)$totalPost > 0 || $post->ID){
          $content = $match[1] . '<a data-name="'.$match[2].'"' . $css . ' href="' . get_tag_link( $termid ) . '">' . $symbol . $match[2] . '('.(int)$totalPost.')</a>';
          }else{
            $content = $match[1] . '<span' . $css . '>' . $symbol . $match[2] . '</span>';  
          }

          
        }
        
      }
      return $content;
      
    }

    
    /**
     * helper function to generate user link
     * used by make_link_usernames
     */
    private function make_link_users( $match, $link ) {
      
      $user = false;
      $username = $match[2];
      
      if ( ! $this->settings['usernamesnick'] ) {
        
        // get by login name - default
        $user = get_user_by( 'login', $username );
        
      } else {
        
        // get by nickname
        $users = get_users( array( 'meta_key' => 'nickname', 'meta_value' => $username ) );
        
        if ( count( $users ) == 1 ) {
          
          // should result in one user
          $user = $users[0];
          
        }
      }
      
      if ( !$user ) {
        
        $content = $match[0];
        
      } else {
        
        if ( $link != 'PROFILE' ) {
          
          $linkto = $user->user_url;
          
        } else {
          
          $linkto = '';
          
        }
        
        if ( $linkto == '' ) {
          
          $linkto = get_author_posts_url( $user->ID );
          
        }
        
        if ( $link == 'WEBSITE-NEW' ) {
          
          $target = ' target="_blank"';
          
        } else {
          
          $target = '';
          
        }
        
        $css = $this->settings['usernamescssclass'];
        
        if ( $css != '' ) {
          
          $css = ' class="' . $css . '"';
          
        }
        
        if ( ! $this->settings['display_nosymbols'] ) {
          
          $symbol = '@';
          
        } else {
          
          $symbol = '';
          
        }
        
        $content = $match[1] . '<a' . $css . ' href="' . $linkto . '"'. $target . '>' . $symbol . $match[2] . '</a>';
        
      }
      
      return $content;
      
    }
    
    
    /**
     * helper function to generate cashtag link
     * used by make_link_cashtags
     */
    private function make_link_cash( $match, $link ) {
      
      // preventively
      $content = $match[0];
      
      if ( $link == 'MARKETWATCH-SAME' || $link == 'MARKETWATCH-NEW' ) {
        
        $linkto = 'http://www.marketwatch.com/investing/Stock/' . $match[2];
        
      } elseif ( $link == 'GOOGLE-SAME' || $link == 'GOOGLE-NEW' ) {
        
        $linkto = 'https://www.google.com/finance?q=' . $match[2];
        
      } else {
        
        $linkto = 'http://finance.yahoo.com/q?s=' . $match[2];
        
      }
      
      if ( $link == 'MARKETWATCH-NEW' || $link == 'GOOGLE-NEW' || $link == 'YAHOO-NEW' ) {
        
        $target = ' target="_blank"';
        
      } else {
        
        $target = '';
        
      }
      
      $css = $this->settings['cashtagcssclass'];
      
      if ( $css != '' ) {
        
        $css = ' class="' . $css . '"';
        
      }
      
      if ( ! $this->settings['display_nosymbols'] ) {
      
        $symbol = '$';
        
      } else {
        
        $symbol = '';
        
      }
        
      $content = $match[1] . '<a' . $css . ' href="' . $linkto . '"'. $target . '>' . $symbol . $match[2] . '</a>';

      return $content;

    }
    
    
    /**
		 * process content for Plugin User Submitted Posts https://wordpress.org/plugins/user-submitted-posts/
     *
     * @since 3.2 
     */
    function process_content_for_user_submitted_posts( $new_user_post ) {
      
      $this->generate_tags( $new_user_post['id'] );
      

      return $new_user_post;
    



    }
    
    
    /**
		 * process content for Barley - Inline Editing Plugin for WordPress
     *
     * @since 3.2 
     */
    function process_content_for_barely() {
      // this function overrides barley_update_post in functions_posts.php of the Barely plugin
      
      // -- Taken from Barely
      $json            = array();
      $json['success'] = false;
      $columns         = array(
                        'the_title'   => 'post_title',
                        'the_content' => 'post_content');

      // Only proceed if we have a post_id
      if ( isset($_POST['p']) && ! empty($_POST['p']) ) {
        
        $k               = trim(urldecode($_POST['k']));
        $v               = trim(urldecode($_POST['v']));
        $pid             = trim(urldecode($_POST['p']));

        // Strip trailing BR tag in FireFox
        if ( $k === 'the_title' ) {
          
          $v = preg_replace('/(.*)<br[^>]*>/i', '$1', $v);
          
        }

        // For the_title and the_content only
        if (array_key_exists($k, $columns)) {
          
            $res = wp_update_post( array(
                'ID'         => $pid,
                $columns[$k] => $v
            ));
        }

        // Save an Advanced Custom Field
        if ( strpos($k, 'field_') !== false ) {
          
          $res = update_field($k,$v,$pid);
          
        }

        // Save a WordPress Custom Field
        if ( strpos($k, 'field_') === false && !array_key_exists($k, $columns) ) {
          
          $res = update_post_meta($pid,$k,$v);
          
        }
        
        // -- ** added for hashtagger **
        if ( $k === 'the_content' ) {
          
          $this->generate_tags( $pid );
          
        }
        // -- ** added for hashtagger **

        // Good? No? Yes?
        $json['success'] = ($res > 0) ? true : false;

      } 

      header('Content-Type: application/json');
      print json_encode($json);
      exit();
      // -- Taken from Barely
      
    }

    
    /**
     * uninstall plugin
     */
    function uninstall() {
      
      if( is_multisite() ) {
        
        $this->uninstall_network();
        
      } else {
        
        $this->uninstall_single();
        
      }
      
    }
    
    
    /**
     * uninstall network wide
     */
    function uninstall_network() {
      
      global $wpdb;
      
      $activeblog = $wpdb->blogid;
      $blogids = $wpdb->get_col( esc_sql( 'SELECT blog_id FROM ' . $wpdb->blogs ) );
      
      foreach ($blogids as $blogid) {
        
        switch_to_blog( $blogid );
        $this->uninstall_single();
        
      }
      
      switch_to_blog( $activeblog );
      
    }
    
    /**
     * uninstall single blog
     */
    function uninstall_single() {
      
      foreach ( $this->settings as $key => $value) {
        
        if ( $key != 'tagbase' ) {
          
          delete_option( 'swcc_htg_' . $key );
          
        }
        
      }
      
    }
    
    
    /**
     * add links to plugins table
     */
    function add_settings_links( $links ) {

      return array_merge( $links, array( '<a class="dashicons dashicons-admin-tools" href="' . admin_url( 'options-general.php?page=wp-hashtaggersettings' ) . '" title="' . __( 'hashtagger settings', 'hashtagger' ) . '"></a>', '<a class="dashicons dashicons-star-filled" href="https://wordpress.org/support/plugin/' . $this->plugin_slug . '/reviews/" title="' . __( 'Please rate plugin', 'hashtagger' ) . '"></a>' ) );

    }

   
    /**
		 * init backend
		 */
    function admin_menu() {
      
      $this->admin_handle = add_options_page( 'wp-hashtagger ' . __( 'Settings' ), '#wp-hashtagger', 'manage_options', 'wp-hashtaggersettings', array( $this, 'admin_page' ) );
      
    }
    
		/**
		 * add admin css
		 */
		function admin_css() {

			if ( get_current_screen()->id == $this->admin_handle ) {
        
        ?>
        <style type="text/css">
        .wrap {
          width: 99%;
          float: left;
          margin: 0;
        }
        #pp-plugin-info-hashtagger {
          min-height: 48px;
          line-height: 40px;
          vertical-align: middle;
          padding-left: 51px;
          background-image: url(<?php echo plugins_url( 'assets/hastag.png', $this->_file ); ?>);
          background-repeat: no-repeat;
          background-position: left center;
          background-size: 46px;
          margin-right: 17px;
        }
          #pp-plugin-info-hashtagger span {
            float: right; padding-left: 50px;
          }
          #pp-plugin-info-hashtagger .dashicons {
            vertical-align: middle; 
            color:#9e9e9e;
          }
          #pp-plugin-info-hashtagger + .postbox {
            margin-top: 20px
          }
          #pp-plugin-settings-hashtagger input[type="text"], #pp-plugin-settings-hashtagger select { 
                border-width: 1px;
                padding: 5px;
                border-style: solid;
                border-radius: 0px;
                height: auto !important;
          }
          #pp-plugin-settings-hashtagger input[type="text"]:not(:focus), #pp-plugin-settings-hashtagger select:not(:focus) { 
            box-shadow: box-shadow: 1px 2px 3px 0px #999
          }
          #pp-plugin-settings-hashtagger input[type="checkbox"], #hashtagger_ajax_area input[type="checkbox"] {
              display: none;
          }
          #pp-plugin-settings-hashtagger input[type="checkbox"] + label.check, #hashtagger_ajax_area input[type="checkbox"] + label.check {
            display: inline-block;
            border: 1px solid #DDD;
            box-shadow: 0px 0px 5px 0px #999;
            border-style: solid;
            border-radius: 5px;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            font-family: dashicons;
            font-size: 2em;
            margin-right: 10px;
          }
          h2#wp-hastagger a.nav-tab:hover,
          h2#wp-hastagger a.nav-tab.nav-tab-active {
            background: #FF5722;
            color: #fff;
            border-color: #FF5722;
        }
        h2#wp-hastagger{border-bottom-color: #FF5722; }
        h2#wp-hastagger a.nav-tab{
          float: left;
          border-color: #607D8B;
          background: #607D8B;
          color: #fff;
        }
        form#pp-plugin-settings-hashtagger table tr td, form#pp-plugin-settings-hashtagger table tr th {
          border-bottom: 1px solid #ddd;
        }
          #pp-plugin-settings-hashtagger input[type="checkbox"]:disabled + label.check, #hashtagger_ajax_area input[type="checkbox"]:disabled + label.check {
            background-color: #DDD;
          }
          #pp-plugin-settings-hashtagger input[type="checkbox"] + label.check:before, #hashtagger_ajax_area input[type="checkbox"] + label.check:before {
            content: "";  
          }
          #pp-plugin-settings-hashtagger input[type="checkbox"]:checked + label.check:before, #hashtagger_ajax_area input[type="checkbox"]:checked + label.check:before {
            content: "\f147";
          }
        </style>
        <?php
      }
    }
    
    
    /**
     * show admin page
     */
    function admin_page() {
      
      $url = admin_url( 'options-general.php?page=' . $_GET['page'] . '&tab=' );
      $current_tab = 'general';
      
      if ( isset( $_GET['tab'] ) ) {
        
        $current_tab = $_GET['tab'];
        
      }
      
      if ( ! in_array( $current_tab, array('general', 'tags', 'usernames', 'cashtags', 'advanced', 'posttype', 'sectiontype', 'css', 'display', 'regenerate') ) ) {
        
        $current_tab = 'general';
        
      }
      ?>
      <div class="wrap">
        <h1 id="pp-plugin-info-hashtagger"><?php echo $this->plugin_name; ?><span><!--<a class="dashicons dashicons-star-filled" href="https://wordpress.org/support/plugin/<?php echo $this->plugin_slug; ?>/reviews/" title="<?php _e( 'Please rate plugin', 'hashtagger' ); ?>"></a> <a class="dashicons dashicons-wordpress" href="<?php echo $this->wp_url; ?>/" title="<?php _e( 'wordpress.org plugin directory', 'hashtagger' ); ?>"></a>--> <a class="dashicons dashicons-admin-home" href="http://larasoftbd.com/" title="<?php _e( 'Author homepage', 'hashtagger' );?>"></a> <a class="dashicons dashicons-googleplus" href="http://plus.google.com/+LaraSoftOmarFaruque" title="<?php _e( 'Authors Google+ Page', 'wp-hashtagger' ); ?>"></a> <a class="dashicons dashicons-facebook-alt" href="https://www.facebook.com/LaraSoft-438450959878922/" title="<?php _e( 'Authors facebook Page', 'hashtagger' ); ?>"></a> <!--<a class="dashicons dashicons-book-alt" href="<?php echo $this->dc_url; ?>" title="<?php _e( 'Plugin Doc', 'hashtagger' ); ?>"></a> <a class="dashicons dashicons-editor-help" href="http://wordpress.org/support/plugin/<?php echo $this->plugin_slug; ?>/" title="<?php _e( 'Support', 'hashtagger'); ?>"></a> <a class="dashicons dashicons-admin-comments" href="http://larasoftbd.com/contact/" title="<?php _e( 'Contact Author', 'hashtagger' ); ?>"></a>--></span></h1>
        <h2 class="nav-tab-wrapper" id="wp-hastagger">
          <a href="<?php echo $url . 'general'; ?>" class="nav-tab<?php if ( 'general' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Information' ); ?></a>
          <a href="<?php echo $url . 'tags'; ?>" class="nav-tab<?php if ( 'tags' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Tags' ); ?></a>
          <a href="<?php echo $url . 'usernames'; ?>" class="nav-tab<?php if ( 'usernames' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Usernames' ); ?></a>
          <a href="<?php echo $url . 'cashtags'; ?>" class="nav-tab<?php if ( 'cashtags' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Cashtags' ); ?></a>
          <a href="<?php echo $url . 'advanced'; ?>" class="nav-tab<?php if ( 'advanced' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Advanced' ); ?></a>
          <a href="<?php echo $url . 'posttype'; ?>" class="nav-tab<?php if ( 'posttype' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Post Types', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'sectiontype'; ?>" class="nav-tab<?php if ( 'sectiontype' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Section Types', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'css'; ?>" class="nav-tab<?php if ( 'css' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'CSS Style', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'display'; ?>" class="nav-tab<?php if ( 'display' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Display', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'regenerate'; ?>" class="nav-tab<?php if ( 'regenerate' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Regnerate', 'hashtagger' ); ?></a>
        </h2>
        <?php if ( 'regenerate' == $current_tab ) { ?>
          <?php $objects = $this->get_objects(); ?>
          <div class="postbox">
            <div class="inside">
              <p><strong><?php _e( 'Process all existing objects using the current settings', 'hashtagger' ); ?></strong></p><hr />
              <?php 
                if ( count( $objects ) == 0 ) {
                  echo '<p>' . __( 'No objects to process!', 'hashtagger' ) . '</p>';
                } else {
                  echo '<div id="hashtagger_ajax_area"><p><span class="form-invalid">' . __( 'JavaScript must be enabled to use this feature.' ) . '</span></p></div>';
                  add_action( 'admin_print_footer_scripts', array( $this, 'add_regenerate_js' ) );
                }
              ?>
            </div>
          </div>
        <?php } else { ?>
          <form method="post" action="options.php" id="pp-plugin-settings-hashtagger">
            <div class="postbox">
              <div class="inside">
                <?php if ( 'general' == $current_tab ) { ?>
                  <p><strong>#wp-hashtag <?php _e( 'Permalinks' ); ?></strong></p>
                  <hr />
                  <p>#wp-hashtags <?php _e( 'currently link to', 'hashtagger'); ?> <code style="white-space: nowrap"><?php echo $this->tag_base_url() . '[hashtag]'; ?></code>.</p>
                  <p><?php printf( __( 'The <b>Tag base</b> for the Archive URL can be changed on %s page', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_general_tagbase"></a>', '<a href="'. admin_url( 'options-permalink.php' ) .'">' . __( 'Permalink Settings' ) . '</a>' ); ?>.</p>
                <?php } else {
                  settings_fields( 'hashtagger_settings_' . $current_tab );   
                  do_settings_sections( 'hashtagger_settings_section_' . $current_tab );
                  submit_button(); 
                } ?>
              </div>
            </div>
          </form>
        <?php } ?>
      </div>
      <?php
    }
    
    




    /**
     * get tag base URL
     */
    private function tag_base_url() {
      
      return trailingslashit( get_site_url() ) . trailingslashit( $this->settings['tagbase'] );
      
    }
    
    
    /**
     * init admin
     */
    function admin_init() {
      
      add_settings_section( 'hashtagger-settings-tags', '', array( $this, 'admin_section_tags_title' ), 'hashtagger_settings_section_tags' );
      register_setting( 'hashtagger_settings_tags', 'swcc_htg_tags_allow_numeric' );
      register_setting( 'hashtagger_settings_tags', 'swcc_htg_tags_no_links' );
      add_settings_field( 'swcc_htg_settings_tags_numeric', __( 'Allow numeric', 'hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_tags_allow_numeric"></a>' , array( $this, 'admin_tags_allow_numeric' ), 'hashtagger_settings_section_tags', 'hashtagger-settings-tags', array( 'label_for' => 'swcc_htg_tags_allow_numeric' ) );
      add_settings_field( 'swcc_htg_settings_tags_nolinks', __( 'No link creation', 'hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_tags_no_links"></a>' , array( $this, 'admin_tags_no_links' ), 'hashtagger_settings_section_tags', 'hashtagger-settings-tags', array( 'label_for' => 'swcc_htg_tags_no_links' ) );
      
      add_settings_section( 'hashtagger-settings-usernames', '', array( $this, 'admin_section_usernames_title' ), 'hashtagger_settings_section_usernames' );
      register_setting( 'hashtagger_settings_usernames', 'swcc_htg_usernames' ) ;
      register_setting( 'hashtagger_settings_usernames', 'swcc_htg_usernamesnick' );
      add_settings_field( 'swcc_htg_settings_usernames', __( 'Link @usernames', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_usernames_usernames"></a>', array( $this, 'admin_usernames' ), 'hashtagger_settings_section_usernames', 'hashtagger-settings-usernames', array( 'label_for' => 'swcc_htg_usernames' ) );
      add_settings_field( 'swcc_htg_settings_usernamesnick', __( '@nicknames', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_usernames_nicknames"></a>', array( $this, 'admin_usernamesnick' ), 'hashtagger_settings_section_usernames', 'hashtagger-settings-usernames', array( 'label_for' => 'swcc_htg_usernamesnick' ) );    
      
      add_settings_section( 'hashtagger-settings-cashtags', '', array( $this, 'admin_section_cashtag_title' ), 'hashtagger_settings_section_cashtags' );
      register_setting( 'hashtagger_settings_cashtags', 'swcc_htg_cashtags' ) ;
      add_settings_field( 'swcc_htg_settings_cashtags', __( '$cashtags', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_cashtags"></a>', array( $this, 'admin_cashtags' ), 'hashtagger_settings_section_cashtags', 'hashtagger-settings-cashtags', array( 'label_for' => 'swcc_htg_cashtags' ) );
      
      add_settings_section( 'hashtagger-settings-advanced', '', array( $this, 'admin_section_advanced_title' ), 'hashtagger_settings_section_advanced' );

      register_setting( 'hashtagger_settings_advanced', 'swcc_htg_advanced_nodelete' );
      add_settings_field( 'swcc_htg_settings_advanced_nodelete', __( 'Do not delete unused Tags', 'hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_advanced_nodelete"></a>' , array( $this, 'admin_advanced_nodelete' ), 'hashtagger_settings_section_advanced', 'hashtagger-settings-advanced', array( 'label_for' => 'swcc_htg_advanced_nodelete' ) );

      register_setting( 'hashtagger_settings_advanced', 'swcc_htg_advanced_email_notif' );
      add_settings_field( 'swcc_htg_settings_advanced_email_notif', __( 'Send Email when new article with #hashtag', 'hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_advanced_email_notif"></a>' , array( $this, 'admin_advanced_email_notif' ), 'hashtagger_settings_section_advanced', 'hashtagger-settings-advanced', array( 'label_for' => 'swcc_htg_advanced_email_notif' ) );
      



      add_settings_section( 'hashtagger-settings-posttype', '', array( $this, 'admin_section_posttype_title' ), 'hashtagger_settings_section_posttype' );
      register_setting( 'hashtagger_settings_posttype', 'swcc_htg_posttype_page' );
      register_setting( 'hashtagger_settings_posttype', 'swcc_htg_posttype_custom' );
      add_settings_field( 'swcc_htg_settings_posttype_post', __( 'Posts' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_posttypes_posts"></a>', array( $this, 'admin_posttype_post' ), 'hashtagger_settings_section_posttype', 'hashtagger-settings-posttype', array( 'label_for' => 'swcc_htg_posttype_post' ) );
      add_settings_field( 'swcc_htg_settings_posttype_page', __( 'Pages' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_posttypes_pages"></a>', array( $this, 'admin_posttype_page' ), 'hashtagger_settings_section_posttype', 'hashtagger-settings-posttype', array( 'label_for' => 'swcc_htg_posttype_page' ) );
      add_settings_field( 'swcc_htg_settings_posttype_custom', __( 'Custom Post Types', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_posttypes_custom"></a>', array( $this, 'admin_posttype_custom' ), 'hashtagger_settings_section_posttype', 'hashtagger-settings-posttype', array( 'label_for' => 'swcc_htg_posttype_custom' ) );
      
      add_settings_section( 'hashtagger-settings-sectiontype', '', array( $this, 'admin_section_sectiontype_title' ), 'hashtagger_settings_section_sectiontype' );
      register_setting( 'hashtagger_settings_sectiontype', 'swcc_htg_sectiontype_title' );
      register_setting( 'hashtagger_settings_sectiontype', 'swcc_htg_sectiontype_excerpt' );
      add_settings_field( 'swcc_htg_sectiontype_title', __( 'Title' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_sectiontypes_title"></a>', array( $this, 'admin_sectiontype_title' ), 'hashtagger_settings_section_sectiontype', 'hashtagger-settings-sectiontype', array( 'label_for' => 'swcc_htg_sectiontype_title' ) );
      add_settings_field( 'swcc_htg_sectiontype_excerpt', __( 'Excerpt' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_sectiontypes_excerpt"></a>', array( $this, 'admin_sectiontype_excerpt' ), 'hashtagger_settings_section_sectiontype', 'hashtagger-settings-sectiontype', array( 'label_for' => 'swcc_htg_sectiontype_excerpt' ) );
      add_settings_field( 'swcc_htg_sectiontype_content', __( 'Content' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_sectiontypes_content"></a>', array( $this, 'admin_sectiontype_content' ), 'hashtagger_settings_section_sectiontype', 'hashtagger-settings-sectiontype', array( 'label_for' => 'swcc_htg_sectiontype_content' ) );
      
      add_settings_section( 'hashtagger-settings-css', '', array( $this, 'admin_section_css_title' ), 'hashtagger_settings_section_css' );
      register_setting( 'hashtagger_settings_css', 'swcc_htg_cssclass', array( $this, 'admin_cssclass_validate' ) );
      register_setting( 'hashtagger_settings_css', 'swcc_htg_cssclass_notag', array( $this, 'admin_cssclass_validate' ) );
      register_setting( 'hashtagger_settings_css', 'swcc_htg_usernamescssclass', array( $this, 'admin_cssclass_validate' ) );
      register_setting( 'hashtagger_settings_css', 'swcc_htg_cashtagcssclass', array( $this, 'admin_cssclass_validate' ) );

      add_settings_field( 'swcc_htg_settings_cssclass_notag', __( 'CSS class name(s) for +#hashtag links', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_css_hashtaglinks"></a>', array( $this, 'admin_cssclass_notag' ), 'hashtagger_settings_section_css', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_cssclass_notag' ) );
      add_settings_field( 'swcc_htg_settings_usernamescssclass', __( 'CSS class name(s) for @usernames', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_css_usernames"></a>', array( $this, 'admin_usernamescssclass' ), 'hashtagger_settings_section_css', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_usernamescssclass' ) );
      add_settings_field( 'swcc_htg_settings_cashtagcssclass', __( 'CSS class name(s) for $cashtags', 'hashtagger' ) . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_css_usernames"></a>', array( $this, 'admin_cashtagcssclass' ), 'hashtagger_settings_section_css', 'hashtagger-settings-css', array( 'label_for' => 'swcc_htg_cashtagcssclass' ) );
      
      add_settings_section( 'hashtagger-settings-display', '', array( $this, 'admin_section_display_title' ), 'hashtagger_settings_section_display' );
      register_setting( 'hashtagger_settings_display', 'swcc_htg_display_nosymbols' );
      add_settings_field( 'swcc_htg_display_nosymbols', __( 'Remove symbols from links', 'wp-hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_display"></a>' , array( $this, 'admin_display_nosymbols' ), 'hashtagger_settings_section_display', 'hashtagger-settings-display', array( 'label_for' => 'swcc_htg_display_nosymbols' ) );
      register_setting( 'hashtagger_settings_display', 'swcc_htg_display_form_tooltip' );
      add_settings_field( 'swcc_htg_display_form_tooltip', __( 'Tooltip $hash Registration', 'wp-hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_display"></a>' , array( $this, 'admin_display_tooltip' ), 'hashtagger_settings_section_display', 'hashtagger-settings-display', array( 'label_for' => 'swcc_htg_display_form_tooltip' ) );
      register_setting( 'hashtagger_settings_display', 'swcc_htg_button_buddypress_activity' );
      add_settings_field( 'swcc_htg_button_buddypress_activity', __( 'Buddypress Activity #Hash Button', 'wp-hashtagger') . ' <a class="dashicons dashicons-editor-help" href="' . $this->dc_url . '/#settings_display"></a>' , array( $this, 'admin_display_buddypress_button' ), 'hashtagger_settings_section_display', 'hashtagger-settings-display', array( 'label_for' => 'swcc_htg_button_buddypress_activity' ) ); 
    }
    
    
    /**
     * echo title for tags settings section
     */
    function admin_section_tags_title() {
      
      echo '<p><strong>' . __( 'Handling of #hashtags', 'hashtagger' ) . ':</strong></p><hr />';
      
    }
    
    
    /**
     * handle the settings field : allow numeric
     */
    function admin_tags_allow_numeric() {
      
      echo '<input type="checkbox" name="swcc_htg_tags_allow_numeric" id="swcc_htg_tags_allow_numeric" value="1"' . ( ( $this->settings['tags_allow_numeric'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_tags_allow_numeric" class="check"></label>' . __( 'Enable tags starting with numbers', 'hashtagger' ) . '<br /><span class="dashicons dashicons-warning"></span><strong>' . __( 'Please note that this is not commonly used. Twitter, Instagram, YouTube, Pinterest, Google+ and other platforms do not support hashtags starting with or using only numbers.', 'hashtagger');
      
    }
    
    
    /**
     * handle the settings field : no link creation
     */
    function admin_tags_no_links() {
      
      echo '<input type="checkbox" name="swcc_htg_tags_no_links" id="swcc_htg_tags_no_links" value="1"' . ( ( $this->settings['tags_no_links'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_tags_no_links" class="check"></label>' . __( 'Only create tags from #hashtags, but do not show links.', 'hashtagger' );
      
    }
    
    
    /**
     * echo title for usernames settings section
     */
    function admin_section_usernames_title() {
    
    echo '<p><strong>' . __( 'Handling of @usernames', 'hashtagger' ) . ':</strong></p><hr />';

    }
    
    
    /**
     * handle the settings field : user names
     */
    function admin_usernames() {
      
      $curvalue = $this->settings['usernames'];
      echo '<select name="swcc_htg_usernames" id="swcc_htg_usernames">';
      echo '<option value="NONE"' . ( ( $curvalue == 'NONE' ) ? ' selected="selected"' : '' ) . '>' . __('Ignore @usernames', 'hashtagger' ) . '</option>';
      echo '<option value="PROFILE"' . ( ( $curvalue == 'PROFILE' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users profile page', 'hashtagger' ) . '</option>';
      echo '<option value="WEBSITE-SAME"' . ( ( $curvalue == 'WEBSITE-SAME' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users website in same browser tab', 'hashtagger') . '</option>';
      echo '<option value="WEBSITE-NEW"' . ( ( $curvalue == 'WEBSITE-NEW' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link @usernames to users website in new browser tab', 'hashtagger') . '</option>';
      echo '</select>';
      
    }
    
    
    /**
     * handle the settings field : use nicknames instead of usernames
     */
    function admin_usernamesnick() {
      
      echo '<input type="checkbox" name="swcc_htg_usernamesnick" id="swcc_htg_usernamesnick" value="1"' . ( ( $this->settings['usernamesnick'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_usernamesnick" class="check"></label>' . __( 'Use @nicknames instead of @usernames', 'hashtagger' ) . '<br /><br/><div class="dashicons dashicons-shield"></div><strong>' . __( 'Highly recommended to enhance WordPress security!', 'wp-hashtagger');
    
    }
    
    
    /**
     * echo title for cashtags settings section
     */
    function admin_section_cashtag_title() {
      
      echo '<p><strong>' . __( 'Handling of $cashtags', 'hashtagger' ) . ':</strong></p><hr />';
      
    }
    
    
    /**
     * handle the settings field : cashtags
     */
    function admin_cashtags() {
      
      $curvalue = $this->settings['cashtags'];
      echo '<select name="swcc_htg_cashtags" id="swcc_htg_cashtags">';
      echo '<option value="NONE"' . ( ( $curvalue == 'NONE' ) ? ' selected="selected"' : '' ) . '>' . __('Ignore $cashtags', 'hashtagger' ) . '</option>';
      echo '<option value="MARKETWATCH-SAME"' . ( ( $curvalue == 'MARKETWATCH-SAME' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link $cashtags to MarketWatch in same browser tab', 'hashtagger' ) . '</option>';
      echo '<option value="MARKETWATCH-NEW"' . ( ( $curvalue == 'MARKETWATCH-NEW' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link $cashtags to MarketWatch in new browser tab', 'hashtagger' ) . '</option>';
      echo '<option value="GOOGLE-SAME"' . ( ( $curvalue == 'GOOGLE-SAME' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link $cashtags to Google Finance in same browser tab', 'hashtagger') . '</option>';
      echo '<option value="GOOGLE-NEW"' . ( ( $curvalue == 'GOOGLE-NEW' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link $cashtags to Google Finance in new browser tab', 'hashtagger') . '</option>';
      echo '<option value="YAHOO-SAME"' . ( ( $curvalue == 'YAHOO-SAME' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link $cashtags to Yahoo Finance in same browser tab', 'hashtagger') . '</option>';
      echo '<option value="YAHOO-NEW"' . ( ( $curvalue == 'YAHOO-NEW' ) ? ' selected="selected"' : '' ) . '>' . __( 'Link $cashtags to Yahoo Finance in new browser tab', 'hashtagger') . '</option>';
      echo '</select>';
      
    }

        
    /**
     * echo title for advanced settings section
     */
    function admin_section_advanced_title() {
    
      echo '<p><strong>' . __( 'Handling of existing Tags', 'hashtagger' ) . ':</strong></p><hr />';

    }
    
    
    /**
     * handle the settings field : no delete
     */
    function admin_advanced_nodelete() {
      echo '<input type="checkbox" name="swcc_htg_advanced_nodelete" id="swcc_htg_advanced_nodelete" value="1"' . ( ( $this->settings['advanced_nodelete'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_advanced_nodelete" class="check"></label>';
      
    }

    function admin_advanced_email_notif() {
      
      echo '<input type="checkbox" name="swcc_htg_advanced_email_notif" id="swcc_htg_advanced_email_notif" value="1"' . ( ( $this->settings['advanced_email_notif'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_advanced_email_notif" class="check"></label>';
      
    }
    
    
      
    /**
     * echo title for posttype settings section
     */
    function admin_section_posttype_title() {
      
      echo '<p><strong>' . __( 'Post Types to process', 'hashtagger' ) . ':</strong></p><hr />';
      
    }
    
    
    /**
     * handle the settings field : posttype 'post' - this is only a dummy, maybe for future use, currently posts are always on
     */
    function admin_posttype_post() {
      
      echo '<input type="checkbox" name="swcc_htg_posttype_post" id="swcc_htg_posttype_post" value="1" checked="checked" disabled="disabled" /><label for="swcc_htg_posttype_post" class="check"></label>';
    
    }
    
    
    /**
     * handle the settings field : posttype 'page'
     */
    function admin_posttype_page() {
      
      echo '<input type="checkbox" name="swcc_htg_posttype_page" id="swcc_htg_posttype_page" value="1"' . ( ( $this->settings['posttype_page'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_posttype_page" class="check"></label>';
      
    }
    
    
    /**
     * handle the settings field : posttype 'custom post types'
     */
    function admin_posttype_custom() {

      echo '<input type="checkbox" name="swcc_htg_posttype_custom" id="swcc_htg_posttype_custom" value="1"' . ( ( $this->settings['posttype_custom'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_posttype_custom" class="check"></label>';

    }
    

    /**
     * echo title for sectiontype settings section
     */
    function admin_section_sectiontype_title() {
      
      echo '<p><strong>' . __( 'Section Types to process', 'hashtagger' ) . ':</strong></p><hr />';
      
    }
    
    
    /**
     * handle the settings field : sectiontype 'title'
     */
    function admin_sectiontype_title() {
      
      echo '<input type="checkbox" name="swcc_htg_sectiontype_title" id="swcc_htg_sectiontype_title" value="1"' . ( ( $this->settings['sectiontype_title'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_sectiontype_title" class="check"></label>';
      
    }
    
    
    /**
     * handle the settings field : sectiontype 'excerpt'
     */
    function admin_sectiontype_excerpt() {
      
      echo '<input type="checkbox" name="swcc_htg_sectiontype_excerpt" id="swcc_htg_sectiontype_excerpt" value="1"' . ( ( $this->settings['sectiontype_excerpt'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_sectiontype_excerpt" class="check"></label>';
      
    }
    
    
    /**
     * handle the settings field : sectiontype 'content' ' - this is only a dummy, maybe for future use, currently content is always on
     */
    function admin_sectiontype_content() {
      
      echo '<input type="checkbox" name="swcc_htg_sectiontype_content" id="swcc_htg_sectiontype_content" value="1" checked="checked" disabled="disabled" /><label for="swcc_htg_sectiontype_content" class="check"></label>';
    
    }
    
    
    /**
     * echo title for css settings section
     */
    function admin_section_css_title() {
      
      echo '<p><strong>' . __( 'CSS Classes to style links', 'hashtagger' ) .':</strong></p><hr />';
    
    }
    
    
    /**
     * handle the settings field : css class
     */
    function admin_cssclass() {
      
      echo '<input class="regular-text" type="text" name="swcc_htg_cssclass" id="swcc_htg_cssclass" value="' . $this->settings['cssclass'] . '" />';
    
    }

    
    /**
     * handle the settings field : css class notag
     */
    function admin_cssclass_notag() {
      
      echo '<input class="regular-text" type="text" name="swcc_htg_cssclass_notag" id="swcc_htg_cssclass_notag" value="' . $this->settings['cssclass_notag'] . '" />';
    
    }
    
    
    /**
     * handle the settings field : css class for usernames
     */
    function admin_usernamescssclass() {
    
      echo '<input class="regular-text" type="text" name="swcc_htg_usernamescssclass" id="swcc_htg_usernamescssclass" value="' . $this->settings['usernamescssclass'] . '" />';
    
    }
    
    
    /**
     * handle the settings field : css class for cashtags
     */
    function admin_cashtagcssclass() {
      
      echo '<input class="regular-text" type="text" name="swcc_htg_cashtagcssclass" id="swcc_htg_cashtagcssclass" value="' . $this->settings['cashtagcssclass'] . '" />';
    
    }
    
    
    /**
     * validate input : css class
     */
    function admin_cssclass_validate( $input ) {
      
      $classes = explode(' ', $input);
      $css = '';
      
      foreach( $classes as $class ) {
        
        $css = $css . sanitize_html_class( $class ) . ' ';
        
      }
      
      return rtrim( $css );
      
    }


    /**
     * echo title for display settings section
     */
    function admin_section_display_title() {
      
      echo '<p><strong>' . __( 'Link display', 'hashtagger' ) . ':</strong></p><hr />';
    
    }
    
    
    /**
     * handle the settings field : do not display symbols
     */
    function admin_display_nosymbols() {
      
      echo '<input type="checkbox" name="swcc_htg_display_nosymbols" id="swcc_htg_display_nosymbols" value="1"' . ( ( $this->settings['display_nosymbols'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_display_nosymbols" class="check"></label>';
    
    }
    

    /**
     * handle the settings field : display tooltip registration form (LaraSoft)
    */
    function admin_display_tooltip() {
      
      echo '<input type="checkbox" name="swcc_htg_display_form_tooltip" id="swcc_htg_display_form_tooltip" value="1"' . ( ( $this->settings['display_tooltip'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_display_form_tooltip" class="check"></label>';
    
    }

    function admin_display_buddypress_button(){
      echo '<input type="checkbox" name="swcc_htg_button_buddypress_activity" id="swcc_htg_button_buddypress_activity" value="1"' . ( ( $this->settings['display_buddypress_button'] == true ) ?  'checked="checked"' : '' ) . ' /><label for="swcc_htg_button_buddypress_activity" class="check"></label>';
    }
    
    
    /**
     * get an array of all objects to process depending on settings
     */
    function get_objects() {
      
      $post_types = array();
      
      if ( $this->settings['posttype_custom'] ) {
        
        $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
        
      }
      
      if ( $this->settings['posttype_page'] ) {
        
        $post_types[] = 'page';
        
      }
      
      $post_types[] = 'post';
      
      return get_posts( array( 'post_type' => $post_types, 'posts_per_page' => -1 ) );
      
    }


    /*
    * get all comment id
    */
    function comment_ids(){
      global $wpdb;
      $prex = $wpdb->prefix;
      $table = $prex . 'bp_activity';
      $qry = "SELECT `id` FROM $table";
      $result = $wpdb->get_results($qry);
      return $result;
    }

    /**
     * add JavaScript to regenerate all objets to footer
     */
    function add_regenerate_js() {
      
      $objects = $this->get_objects();
      $comments_id = $this->comment_ids();
      $c_ids = array();
      foreach( $objects as $object ) {
        $ids[] = $object->ID;
      }
      foreach($comments_id as $cid) array_push($c_ids, $cid->id);

     

      $arratM = array_merge($ids, $c_ids);

      $ids = implode( ',', $arratM );

      
      ?>
        <script type='text/javascript'>
          var object_ids = [<?php echo $ids; ?>];
          var objects = <?php echo count( $arratM ); ?>;
          var counter = 0;
          var abort = false;
          jQuery( '#hashtagger_ajax_area' ).html( '<p><input type="checkbox" name="hashtagger_regenerate_confirmation" id="hashtagger_regenerate_confirmation" value="ok" /><label for="hashtagger_regenerate_confirmation" class="check"></label><span id="hashtagger_regenerate_confirmation_hint"><?php _e( 'Please confirm to regenerate', 'hashtagger' ); ?> <a class="dashicons dashicons-editor-help" href="<?php echo $this->dc_url; ?>/#settings_regeneration"></a></span></p><p><input type="button" name="sumbit_regnerate" id="sumbit_regnerate" class="button button-primary button-large" value="<?php _e( 'Process all objects', 'hashtagger' ); ?> (<?php echo count( $arratM ); ?>)"  /></p>' );
          jQuery( '#sumbit_regnerate' ).click( function() { 
            if ( jQuery( '#hashtagger_regenerate_confirmation' ).prop( 'checked' ) ) {
              jQuery( '#hashtagger_ajax_area' ).html( '<p><?php _e( 'Please be patient while objects are processed. Do not close or leave this page.', 'hashtagger' ); ?></p><p><div style="width: 100%; height: 40px; border: 2px solid #222; border-radius: 5px; background-color: #FFF"><div id="hashtagger_regnerate_progressbar" style="width: 0; height: 100%; background-image: url(<?php echo plugins_url( 'progress.png', __FILE__ ); ?>); background-repeat: repeat-x" ></div></div></p><p id="hashtagger_abort_area"><input type="button" name="cancel_regnerate" id="cancel_regnerate" class="button button-secondary button-large" value="<?php _e( 'Abort regeneration', 'hashtagger' ); ?>" /></p>' );
              jQuery( '#cancel_regnerate' ).click( function() { 
                abort = true;
                jQuery( '#hashtagger_abort_area' ).html( '<strong><?php _e( 'Aborting process...', 'hashtagger' ); ?></strong>' );
              });
              regenerate_object();
            } else {
              jQuery( '#hashtagger_regenerate_confirmation_hint' ).addClass( 'form-invalid' );
            }
          });
          function regenerate_object() {
            var object_id = object_ids[0];
            jQuery.ajax( { 
              type: 'POST', 
              url: ajaxurl, 
              data: { 'action': 'hashtagger_regenerate', 'id': object_id }, 
              success: function(response) {  
                //console.log(response);
                counter++;
                jQuery( '#hashtagger_regnerate_progressbar' ).width( ( counter * 100 / objects ) + '%' );
                if ( abort ) {
                  abortstring = '<?php _e( 'Process aborted. {COUNTER} of {OBJECTS} objects have been processed.', 'hashtagger'); ?>';
                  abortstring = abortstring.replace( '{COUNTER}', counter );
                  abortstring = abortstring.replace( '{OBJECTS}', objects );
                  jQuery( '#hashtagger_abort_area' ).html( abortstring );
                } else {
                  object_ids.shift();
                  if ( object_ids.length > 0 ) {
                    regenerate_object();
                  } else {
                    donestring = '<?php _e( 'All done. {OBJECTS} objects have been processed.', 'hashtagger' ); ?>';
                    donestring = donestring.replace( '{OBJECTS}', objects );
                    jQuery( '#hashtagger_abort_area' ).html( donestring );
                  }
                }
              }
            } );
          }
        </script>
      <?php
      
    }

    
    /*
    /* Generage Tags from buddypress comments
    */
    function generate_tags_from_buddypress_activity($id){
      //$activity_get = bp_activity_get_specific( array( 'activity_ids' => $id ) )

      global $wpdb;
      $prex = $wpdb->prefix;
      $table = $prex . 'bp_activity';
      $qry = "SELECT `content` FROM $table WHERE `id`=".$id."";
      $result = $wpdb->get_row($qry, OBJECT);
      
      $content = $result->content;
      if($content != ''){
        wp_set_post_tags( $id, $this->get_hashtags_from_content( strip_tags( $content ) ), $this->settings['advanced_nodelete'] );
      }
    }

    /**
     * handle ajax call for one object to regenerate
     *
     * @uses generate_tags()
     */
    function admin_hashtagger_regenerate() {
      
      $id = (int)$_REQUEST['id'];
      $this->generate_tags( $id );
      $this->generate_tags_from_buddypress_activity($id);

      echo $id;
      die();
      
    }

  }
  
}