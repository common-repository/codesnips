<?php

// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Upon plugin deletion, delete options
delete_option("cs_rewrite_slug");
delete_option("cs_rewrite_slug_old");
delete_option("cs_global_show_metabar");

delete_option("cs_admin_theme");
delete_option("cs_frontend_theme");
delete_option("cs_default_language");
delete_option("cs_global_fontsize");
delete_option("cs_global_show_gutter");

// Clean up all post meta keys
delete_post_meta_by_key( '_snippet_lang' );
delete_post_meta_by_key( '_snippet_filename' );
delete_post_meta_by_key( '_snippet_description' );
delete_post_meta_by_key( '_snippet_show_description' );
delete_post_meta_by_key( '_snippet_hide_gutter' );
delete_post_meta_by_key( '_snippet_code' );

?>