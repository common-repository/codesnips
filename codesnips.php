<?php
   /*
   Plugin Name: codeSnips
   Plugin URI: http://obrienlabs.net
   Description: Create individual code snippet posts and then display them as an individual page, show the entire archive list or embed them into your post.
   Version: 1.2
   Author: Pat O'Brien
   Author URI: http://obrienlabs.net
   License: GPL2
   Text Domain: codesnips
   Domain Path: /languages
   */

require_once( 'includes/class-codesnips.php' );
require_once( 'includes/class-codesnips-settings.php' );

DEFINE ("CODESNIPS_VER", "1.2");

$cs = new codeSnips(__FILE__);
$cs_settings = new codeSnips_Settings(__FILE__, plugin_basename(__FILE__));

?>
