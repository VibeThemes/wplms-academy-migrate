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
        	echo '####';
        }
    }
}

WPLMS_ACADEMY_INIT::init();
