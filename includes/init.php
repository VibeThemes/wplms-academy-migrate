<?php
/**
 * Initialization functions for WPLMS ACADEMY MIGRATION
 * @author      VibeThemes
 * @category    Admin
 * @package     Initialization
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPLMS_ACADEMY_INIT{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new WPLMS_ACADEMY_INIT();

        return self::$instance;
    }

    private function __construct(){
    	$theme = wp_get_theme(); // gets the current theme
        if ('Academy' == $theme->name || 'Academy' == $theme->parent_theme){
        	add_action( 'admin_notices',array($this,'migration_notice' ));
        	add_action('wp_ajax_migration_am_courses',array($this,'migration_am_courses'));

            add_action('wp_ajax_migration_am_course_to_wplms',array($this,'migration_am_course_to_wplms'));
        }
    }

    function migration_notice(){
    	$this->migration_status = get_option('wplms_academy_migration');
    	$check = 0;
        ?>
        <div class="welcome-panel" id="welcome_am_panel" style="padding-bottom:20px;width:96%">
            <h1>Please note: Woocommerce must be activated if using paid courses.</h1>
            <p>Please click on the button below to proceed to migration proccess</p>
            <form method="POST">
                <input name="am_click" type="submit" value="Click Here" class="button">
            </form>
        </div>
        <?php
        if(isset($_POST['am_click'])){
            $check = 1;
            ?> <style> #welcome_am_panel{display:none;} </style> <?php
        } 
        
        if(empty($this->migration_status) && $check){
        	?>
            <div id="migration_academy_courses" class="error notice ">
               <p id="am_message"><?php printf( __('Migrate academy coruses to WPLMS %s Begin Migration Now %s', 'wplms-am' ),'<a id="begin_wplms_academy_migration" class="button primary">','</a>'); ?>
                
               </p>
           <?php wp_nonce_field('security','security'); ?>
                <style>.wplms_am_progress .bar{-webkit-transition: width 0.5s ease-in-out;
    -moz-transition: width 1s ease-in-out;-o-transition: width 1s ease-in-out;transition: width 1s ease-in-out;}</style>
                <script>
                    jQuery(document).ready(function($){
                        $('#begin_wplms_academy_migration').on('click',function(){
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: ajaxurl,
                                data: { action: 'migration_am_courses', 
                                          security: $('#security').val(),
                                        },
                                cache: false,
                                success: function (json) {
                                    $('#migration_academy_courses').append('<div class="wplms_am_progress" style="width:100%;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');

                                    var x = 0;
                                    var width = 100*1/json.length;
                                    var number = width;
                                    var loopArray = function(arr) {
                                        am_ajaxcall(arr[x],function(){
                                            x++;
                                            if(x < arr.length) {
                                                loopArray(arr);   
                                            }
                                        }); 
                                    }
                                    
                                    // start 'loop'
                                    loopArray(json);

                                    function am_ajaxcall(obj,callback) {
                                        
                                        $.ajax({
                                            type: "POST",
                                            dataType: 'json',
                                            url: ajaxurl,
                                            data: {
                                                action:'migration_am_course_to_wplms', 
                                                security: $('#security').val(),
                                                id:obj.id,
                                            },
                                            cache: false,
                                            success: function (html) {
                                                number = number + width;
                                                $('.wplms_am_progress .bar').css('width',number+'%');
                                                if(number >= 100){
                                                    $('#migration_academy_courses').removeClass('error');
                                                    $('#migration_academy_courses').addClass('updated');
                                                    $('#am_message').html('<strong>'+x+' '+'<?php _e('Courses successfully migrated from Academy to WPLMS <p style="font-size:16px;color:#0073aa;">Please deactivate Academy theme and activate the WPLMS theme and plugins OR install WPLMS theme to check migrated courses in wplms</p>','wplms-am'); ?>'+'</strong>');
                                                }
                                            }
                                        });
                                        // do callback when ready
                                        callback();
                                    } 
                                }
                            });
                        });
                    });
                </script>
            </div>
            <?php
        }
    }

    function migration_am_courses(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-am');
            die();
        }

        global $wpdb;
        $courses = $wpdb->get_results("SELECT id,post_title FROM {$wpdb->posts} where post_type='course'");
        $json=array();
        foreach($courses as $course){
            $json[]=array('id'=>$course->id,'title'=>$course->post_title);
        }

        update_option('wplms_academy_migration',1);

        $this->migrate_units();

        print_r(json_encode($json));
        die();
    }

    function migration_am_course_to_wplms(){
    	if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'security') || !is_user_logged_in()){
            _e('Security check Failed. Contact Administrator.','wplms-am');
            die();
        }

        $this->migrate_course_settings($_POST['id']);
        $this->build_curriculum($_POST['id']);
    }

    function migrate_units(){
    	global $wpdb;
    	$wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'unit' WHERE post_type = 'lesson'");
    }

    function migrate_course_settings($course_id){
        $max_students = get_post_meta($course_id,'_course_capacity',true);
        if(!empty($max_students)){
            update_post_meta($course_id,'vibe_max_students',$max_students);
        }

        $vibe_students = get_post_meta($course_id,'_course_popularity',true);
        if(!empty($vibe_students)){
            update_post_meta($course_id,'vibe_students',$vibe_students);
        }

        $course_price = get_post_meta($course_id,'_course_status',true);
        if(!empty($course_price)){
            if($course_price == 'premium'){
                $course_product = get_post_meta($course_id,'_course_product',true);
                if(!empty($course_product)){
                    update_post_meta($course_id,'vibe_course_free','H');
                    update_post_meta($course_id,'vibe_product',$course_product);
                }
            }
            if($course_price == 'private'){
                update_post_meta($course_id,'vibe_course_free','H');
            }
            if($course_price == 'free'){
                update_post_meta($course_id,'vibe_course_free','S');
            }
        }

        $rating = get_post_meta($course_id,'_course_rating',true);
        if(!empty($rating)){
            //
        }
    }

    function build_curriculum($course_id){
        global $wpdb;
        $this->curriculum = array();
        $units = $wpdb->get_results("SELECT m.post_id as id FROM {$wpdb->postmeta} as m LEFT JOIN {$wpdb->posts} as p ON p.id = m.post_id WHERE m.meta_value = $course_id AND m.meta_key = '_lesson_course' ORDER BY p.menu_order ASC");

        update_post_meta($course_id,'vibe_course_curriculum',$this->curriculum);
    }
}

WPLMS_ACADEMY_INIT::init();
