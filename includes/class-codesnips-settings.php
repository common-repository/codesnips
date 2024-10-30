<?php
/**
 * The plugin settings class
 *
 * @package codeSnips
 * @author Pat O'Brien
 * @since 1.0
 *
 */
 
class codeSnips_Settings {

	public function __construct( $file, $pluginBaseName ) {
        $this->file = $file;
		$this->pluginBaseName = $pluginBaseName;
		
		$this->page_slug = 'edit.php?post_type=snippets';
		$this->admin_slug = 'cs_settings';

		add_action('admin_menu', array( &$this, 'add_cs_settings_page' ) );
		add_action('admin_init' , array( &$this, 'setup_settings' ) );
		
		add_filter('plugin_action_links', array( &$this, 'cs_plugin_action_links'), 10, 2);
	}
	
	public function add_cs_settings_page() {
        add_submenu_page(
			$this->page_slug,							// parent slug
			'codeSnips Settings',						// page title
			'Settings',									// menu title
			'manage_options',							// capability 
			$this->admin_slug,							// admin page slug
			array( &$this , 'settings_content' )		// callback function
		);
    }
		
	public function setup_settings() {
		
		// codeSnips General Settings
		
		add_settings_section( 
			'cs_core_setting_section',										// Section ID
			__('Change Custom Snippet Archive settings:','codesnips'),		// Title
			array( &$this , 'cs_settings_section_callback' ),				// Callback
			'cs_general_settings'											// Which page should this section show on?
		);
		
		add_settings_field(
			'cs_rewrite_slug',							// ID of the field
			__('The URL slug to use:','codesnips'),		// Title
			array( &$this , 'change_url_slug' ),		// Callback function
			'cs_general_settings',						// Which page should this option show on
			'cs_core_setting_section'					// Section name to attach to
		);
		
		add_settings_field(
			'cs_global_show_metabar',									// ID of the field
			__('Show the snippet metabar:','codesnips'),				// Title
			array( &$this , 'cs_global_show_metabar_callback' ),		// Callback function
			'cs_general_settings',										// Which page should this option show on
			'cs_core_setting_section'									// Section name to attach to
		);
		
		// Editor settings
		
		add_settings_section( 
			'cs_editor_setting_section',								// ID
			__('Customize the Ace Editor','codesnips'),					// Title
			array( &$this , 'editor_settings_section_callback' ),		// Call back
			'cs_editor_settings'										// Settings page to show the section
		);
		
		add_settings_field(
			'cs_admin_theme',								// ID
			__('Admin page editor theme:','codesnips'),		// Title
			array( &$this , 'editor_admin_theme' ),			// Callback function
			'cs_editor_settings',							// Settings page to show the field
			'cs_editor_setting_section'						// Section name to attach to
		);	
		
		add_settings_field(
			'cs_frontend_theme',							// ID
			__('Front end editor theme:','codesnips'),		// Title
			array( &$this , 'editor_frontend_theme' ),		// Callback function
			'cs_editor_settings',							// Settings page to show the field
			'cs_editor_setting_section'						// Section name to attach to
		);
		
		add_settings_field(
			'cs_default_language',							// ID
			__('Default code language:','codesnips'),		// Title
			array( &$this , 'set_default_code_language' ),	// Callback function
			'cs_editor_settings',							// Settings page to show the field
			'cs_editor_setting_section'						// Section name to attach to
		);
		
		add_settings_field(
			'cs_global_fontsize',								// ID
			__('Font size in pixels:','codesnips'),				// Title
			array( &$this , 'cs_global_editor_fontsize' ),		// Callback function
			'cs_editor_settings',								// Settings page to show the field
			'cs_editor_setting_section'							// Section name to attach to
		);
		
		add_settings_field(
			'cs_global_show_gutter',								// ID
			__('Show the left side line numbers:','codesnips'),		// Title
			array( &$this , 'cs_global_show_gutter_callback' ),		// Callback function
			'cs_editor_settings',									// Settings page to show the field
			'cs_editor_setting_section'								// Section name to attach to
		);		
		
		// General section
		register_setting( 'cs_general_settings', 'cs_rewrite_slug' );
		register_setting( 'cs_general_settings', 'cs_global_show_metabar' );
		
		// Editor section
		register_setting( 'cs_editor_settings', 'cs_admin_theme' );
		register_setting( 'cs_editor_settings', 'cs_frontend_theme' );
		register_setting( 'cs_editor_settings', 'cs_default_language' );
		register_setting( 'cs_editor_settings', 'cs_global_fontsize' );
		register_setting( 'cs_editor_settings', 'cs_global_show_gutter' );
	}
	
	public function settings_content() {
		
		if ( !isset( $_REQUEST['settings-updated'] ) )
          $_REQUEST['settings-updated'] = false; 
		?>
		
		<div class="wrap">

			<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
				<?php $this->cs_check_rewrite(); ?>
				<div class="updated fade"><p><strong><?php _e( 'codeSnips settings saved!', 'codesnips' ) ?></strong></p></div>
			<?php endif; ?>

			<h2><span class="dashicons dashicons-editor-code cs-icon"></span><?php echo esc_html( get_admin_page_title() ); ?> - v<?php echo CODESNIPS_VER; ?></h2>
			
			<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general_settings'; ?>
			
			<div class="codesnips-settings-wrapper">
				<div class="codesnips-settings-body">
			
					<h2 class="nav-tab-wrapper">
						<a href="?post_type=snippets&page=cs_settings&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General Settings', 'codesnips'); ?></a>
						<a href="?post_type=snippets&page=cs_settings&tab=editor_settings" class="nav-tab <?php echo $active_tab == 'editor_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Editor Settings', 'codesnips'); ?></a>
						<a href="?post_type=snippets&page=cs_settings&tab=usage" class="nav-tab <?php echo $active_tab == 'usage' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Usage Tips', 'codesnips'); ?></a>
					</h2>
			
					<form method="post" action="options.php">
						<?php
						if( $active_tab == 'general_settings' ) {
							settings_fields( 'cs_general_settings' );
							do_settings_sections( 'cs_general_settings' );
							
							submit_button();
						} elseif( $active_tab == 'editor_settings' ) {
							settings_fields( 'cs_editor_settings' );
							do_settings_sections( 'cs_editor_settings' );
							
							submit_button();
						} else {
							$this->show_usage_tips();
						}
						
						?>
					</form>
				</div> <!-- end codesnips-body -->
				<div class="codesnips-donate-wrapper">
					<div class="codesnips-donate-body">
						<a href="http://obrienlabs.net"><img src="<?php echo plugin_dir_url( $this->file ) . 'images/OBrienLabs-Logo-h75px.png' ?>" border="0"></a>
						Thank you for using codeSnips! 
                        <h2>Need support?</h2>
                        Visit the <a href="http://obrienlabs.net/codesnips-code-snippet-wordpress-plugin/" target="_blank">plugin homepage!</a> You can leave a comment with your question and I'll try to help you out!
                        <br><br>
                        <hr>
                        <h2>Please Donate!</h2>This plugin has taken a considerable amount of time and coffee to create. If you find this plugin valuable and would like to buy me a cup of coffee, please <a href="http://obrienlabs.net/donate" target="_blank">click here to donate</a>! Thanks!
					</div>
				</div> <!-- end codesnips-donate -->
			</div> <!-- end codesnips-wrapper -->
		
		</div>
			  
		<?php
	}
	
	// codeSnips General Settings
	
	public function cs_settings_section_callback() { 
		?>
		<p>
		<?php _e( 'Change Custom Snippet Archive specific settings.', 'codesnips'); ?>
		</p>
		<?php
	}

	public function change_url_slug() {
		
		$option = get_option('cs_rewrite_slug');
		$exampleURL = get_post_type_archive_link( 'snippets' );
				
		echo '
			<input type="text" id="cs_rewrite_slug" name="cs_rewrite_slug" autocomplete="off" value="'. $option .'">
			<label for="cs_rewrite_slug"><span class="description">' . __( 'Default: snippets<br>Change the URL for your snippets archive. For example:','codesnips') . ' <a href="'.$exampleURL.'" target="_blank">'.$exampleURL.'</a></span></label>
		';
	}
	
	public function cs_global_show_metabar_callback() {
		
		$option = get_option('cs_global_show_metabar');
				
		echo '
			<input type="checkbox" id="cs_global_show_metabar" name="cs_global_show_metabar" value="true" '. checked( $option, "true", false ) .' >
			<label for="cs_global_show_metabar"><span class="description">' . __( 'Default: checked (enabled)<br>Enable or disable the snippet meta bar for each snippet. This is a global change, but can be overridden using the shortcode.', 'codesnips') . '</span></label>
		';
	}
	
	// Editor settings
	
	public function editor_settings_section_callback() { 
		?>
		<p>
		<?php _e( 'For examples of available color themes, go to the Ace Editor demo at', 'codesnips'); ?>
		<a href="http://ace.ajax.org/build/kitchen-sink.html" target="_blank"> http://ace.ajax.org/build/kitchen-sink.html</a>.
		</p>
		<?php
	}

	public function editor_admin_theme() {
		
		$option = get_option('cs_admin_theme');
				
		echo '
			<select name="cs_admin_theme" id="cs_admin_theme">' . $this->theme_options($option) . '</select>
			<label for="cs_admin_theme"><span class="description">' . __( 'Default: chrome<br>Color theme for the code editor as it is displayed on the admin edit pages.' , 'codesnips') . '</span></label>
		';
	}
	
	public function editor_frontend_theme() {
		
		$option = get_option('cs_frontend_theme');

		echo '
			<select name="cs_frontend_theme" id="cs_frontend_theme">' . $this->theme_options($option) . '</select>
			<label for="cs_frontend_theme"><span class="description">' . __( 'Default: chrome<br>Color theme for the code as it is displayed to users on the front end.' , 'codesnips') . '</span></label>
		';
	}
	
	public function set_default_code_language() {
		global $cs;
		
		$option = get_option('cs_default_language');
		
		?>
		<select id="cs_default_language" name="cs_default_language"><?php echo $cs->set_language_options($option); ?></select>
		<label for="cs_default_language"><span class="description"><?php echo __( 'Default: text<br>Select the default code language for when you create new snippets.' , 'codesnips'); ?></span></label>
		<?php
	}
	
	public function cs_global_editor_fontsize() {
		
		$option = get_option('cs_global_fontsize');
				
		echo '
			<input id="cs_global_fontsize" name="cs_global_fontsize" rows="1" cols="20" value="'. $option .'">
			<label for="cs_global_fontsize"><span class="description">' . __( 'Font Size in Pixels. Numbers only. Default: 12 (or leave box empty)<br>This is a global change, but can be overridden using the shortcode.', 'codesnips') . '</span></label>
		';
	}
	
	public function cs_global_show_gutter_callback() {
		
		$option = get_option('cs_global_show_gutter');
				
		echo '
			<input type="checkbox" id="cs_global_show_gutter" name="cs_global_show_gutter" value="true" '. checked( $option, "true", false ) .' >
			<label for="cs_global_show_gutter"><span class="description">' . __( 'Default: checked (enabled)<br>Enable or disable the line numbers on the left side of the snippet (also called the gutter). This is a global change, but can be overridden using the shortcode.', 'codesnips') . '</span></label>
		';
	}

	// Usage tips menu //
	
	public function show_usage_tips() {
		$html = "<h3>Some usage tips on how to use codeSnips</h3>";
		$html .= "
			The simplest way to embed a snippet into your post is to use the shortcode with the snippet ID. For example: <code>[snippet id=X]</code> where X is the ID of the snippet. 
			<br><br>
			You can get the ID of your snippet from the <b>All Snippets</b> menu page. It will be located next to the snippet you have created.
			<br><br>
			
			<h3>A note about snippet privacy:</h3>
			If you wish to have private snippets or password protected snippets, that's no problem. Simply change the status of the snippet post when you publish it. 
			<br><br>
			If your snippet post is set to private or password protected, it will not display when using the shortcode. Likewise, it will not appear in the 'view raw' portion if it is private or password protected. All snippets must be in a public status in order to be available.
			<br><br>
			
			<h3>Shortcode settings:</h3>
			<li><code>id=X</code> — <code>X</code> represents the snippet post ID
			<li><code>desc=true/false</code> — This will show the snippet description above the snippet. This is useful to give a description on the snippet's individual page. 
			<li><code>meta=true/false</code> — This is the meta bar option. The meta bar is below the snippet and contains info like the filename, the date, the code language and how many lines the code is. This is set to <code>true</code> by default.
			<li><code>gutter=true/false</code> — This option is to show or hide the left column containing the line numbers. This is set to <code>true</code> by default.
			<li><code>fontsize=X</code> — <code>X</code> represents the number in pixels you want the font size to be. This is set to <code>12</code> by default.
			<br><br>
			
			<h3>The defaults:</h3>
			<li>The snippet meta bar will show by default.
			<li>The snippet description will show by default.
			<li>The snippet line numbers (gutter) will show by default.
			<br><br>
			
			<h3>Some examples:</h3>
			<li><code>[snippet id=5]</code> will show snippet ID 5 and all default options.
			<br>
			<li><code>[snippet id=5 fontsize=14]</code> will show snippet ID 5, in font size 14px.
			<br>
			<li><code>[snippet id=5 desc=true]</code> will show snippet ID 5, with the description you've provided on that snippet page, and will show the meta bar. 
			<br>
			<li><code>[snippet id=5 desc=true meta=false]</code> will show snippet ID 5, with the description you've provided on that snippet page, and will not show the meta bar. 
			<br>
			<li><code>[snippet id=5 desc=true meta=false gutter=false]</code> will show snippet ID 5, with the description you've provided on that snippet page, will not show the meta bar and will now show line numbers.
		";
		
		echo $html;
	}
	
	// Supporting functions
	
	public function cs_check_rewrite() {
		// Only rewrite if we have to. Bit of a hack, but it works.
		$optionSlugOld = get_option('cs_rewrite_slug_old');
		$optionSlug = get_option('cs_rewrite_slug');

		if ($optionSlugOld != $optionSlug) {
			// New slug detected
			flush_rewrite_rules();
			update_option("cs_rewrite_slug_old", $optionSlug, true);
			?>
			<div class="updated fade"><p><strong><?php _e( 'codeSnips URL slug updated!', 'codesnips'); ?></strong></p></div>
			<?php
		}
	}
	
	private function theme_options( $selected = '') {

		$html = '
				<optgroup label="Bright">
		            <option value="chrome" ' . selected( 'chrome' , $selected , false ) . '>Chrome</option>
		            <option value="clouds" ' . selected( 'clouds' , $selected , false ) . '>Clouds</option>
		            <option value="crimson_editor" ' . selected( 'crimson_editor' , $selected , false ) . '>Crimson Editor</option>
		            <option value="dawn" ' . selected( 'dawn' , $selected , false ) . '>Dawn</option>
		            <option value="dreamweaver" ' . selected( 'dreamweaver' , $selected , false ) . '>Dreamweaver</option>
		            <option value="eclipse" ' . selected( 'eclipse' , $selected , false ) . '>Eclipse</option>
		            <option value="github" ' . selected( 'github' , $selected , false ) . '>GitHub</option>
		            <option value="solarized_light" ' . selected( 'solarized_light' , $selected , false ) . '>Solarized Light</option>
		            <option value="textmate" ' . selected( 'textmate' , $selected , false ) . '>TextMate</option>
		            <option value="tomorrow" ' . selected( 'tomorrow' , $selected , false ) . '>Tomorrow</option>
		            <option value="xcode" ' . selected( 'xcode' , $selected , false ) . '>XCode</option>
		          </optgroup>
		          <optgroup label="Dark">
		            <option value="ambiance" ' . selected( 'ambiance' , $selected , false ) . '>Ambiance</option>
		            <option value="chaos" ' . selected( 'chaos' , $selected , false ) . '>Chaos</option>
		            <option value="clouds_midnight" ' . selected( 'clouds_midnight' , $selected , false ) . '>Clouds Midnight</option>
		            <option value="cobalt" ' . selected( 'cobalt' , $selected , false ) . '>Cobalt</option>
		            <option value="idle_fingers" ' . selected( 'idle_fingers' , $selected , false ) . '>idleFingers</option>
		            <option value="kr_theme" ' . selected( 'kr_theme' , $selected , false ) . '>krTheme</option>
		            <option value="merbivore" ' . selected( 'merbivore' , $selected , false ) . '>Merbivore</option>
		            <option value="merbivore_soft" ' . selected( 'merbivore_soft' , $selected , false ) . '>Merbivore Soft</option>
		            <option value="mono_industrial" ' . selected( 'mono_industrial' , $selected , false ) . '>Mono Industrial</option>
		            <option value="monokai" ' . selected( 'monokai' , $selected , false ) . '>Monokai</option>
		            <option value="pastel_on_dark" ' . selected( 'pastel_on_dark' , $selected , false ) . '>Pastel on dark</option>
		            <option value="solarized_dark" ' . selected( 'solarized_dark' , $selected , false ) . '>Solarized Dark</option>
		            <option value="twilight" ' . selected( 'twilight' , $selected , false ) . '>Twilight</option>
		            <option value="tomorrow_night" ' . selected( 'tomorrow_night' , $selected , false ) . '>Tomorrow Night</option>
		            <option value="tomorrow_night_blue" ' . selected( 'tomorrow_night_blue' , $selected , false ) . '>Tomorrow Night Blue</option>
		            <option value="tomorrow_night_bright" ' . selected( 'tomorrow_night_bright' , $selected , false ) . '>Tomorrow Night Bright</option>
		            <option value="tomorrow_night_eighties" ' . selected( 'tomorrow_night_eighties' , $selected , false ) . '>Tomorrow Night 80s</option>
		            <option value="vibrant_ink" ' . selected( 'vibrant_ink' , $selected , false ) . '>Vibrant Ink</option>
		          </optgroup>';

    	return $html;
	}
		
	// Add settings link to the plugin page. 
	function cs_plugin_action_links($links, $file) {
		static $this_plugin;

		if (!$this_plugin) {
			//$this_plugin = plugin_basename(__FILE__);
			$this_plugin = $this->pluginBaseName;
		}
		
		if ($file == $this_plugin) {
			// The "page" query string value must be equal to the slug
			// of the Settings admin page we defined earlier, which in
			// this case equals "myplugin-settings".
			$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/'.$this->page_slug.'&page='.$this->admin_slug.'">Settings</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}
	
}

?>
