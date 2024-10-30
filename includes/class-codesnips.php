<?php
/**
 * The main plugin class
 *
 * @package codeSnips
 * @author Pat O'Brien
 * @since 1.0
 *
 */

class codeSnips {

	public function __construct( $file ) {
		register_activation_hook( $file, array( &$this, "install" ) );
		register_deactivation_hook( $file, array( &$this, "deactivation" ) );
		
		$this->file = $file;
		$this->slug = get_option( "cs_rewrite_slug" );
		
		add_action( "plugins_loaded", array( &$this, "load_codesnips_textdomain" ) );
		
		add_action( "init", array( &$this, "register_post_type" ) );
		add_filter( "post_updated_messages", array( &$this, "cpt_messages" ) );
				
		add_action( "post_updated", array( &$this, "save_postdata" ) );
		
		add_action( "wp_enqueue_scripts", array( &$this, "load_dashicons" ) );
		
		add_shortcode( "snippet", array( &$this, "shortcode" ) );

		add_filter( "template_include", array( &$this, "custom_template" ), 1 );
		
		// Mimic the_content() formatting, but with the metabox wp_editor
		add_filter( "meta_content", "wptexturize" );
		add_filter( "meta_content", "convert_smilies" );
		add_filter( "meta_content", "convert_chars" );
		add_filter( "meta_content", "wpautop" );
		add_filter( "meta_content", "shortcode_unautop" );
		add_filter( "meta_content", "prepend_attachment" );
		add_filter( "meta_content", "do_shortcode" );

		// Add ability to access snippet URL by ID instead of title without changing permalinks
		add_filter( "rewrite_rules_array", array( &$this, "add_codeSnips_rewrite_rules" ) );
		
		// Raw snippet handling
		add_filter( "query_vars", array( &$this, "add_custom_query_var" ) );
		add_filter( "rewrite_rules_array", array( &$this, "add_codeSnips_raw_rewrite_rules" ) );
		add_action( "parse_query", array( &$this, "display_raw_snippet" ) );
		
		
		if( is_admin() ) {
			//add_action( "init", array( &$this, "remove_wp_editor" ), 10 ); // Removed it via CSS instead
			add_action( "admin_init", array( &$this, "upgrade_options" ) );
			add_action( "admin_enqueue_scripts", array( &$this, "admin_scripts" ) );

			add_action( "add_meta_boxes", array( &$this, "metabox" ) );

			add_action( "edit_form_after_title", array( &$this, "add_custom_shorturl" ) );
			
			add_filter( "manage_snippets_posts_columns", array( &$this, "admin_columnHead" ) );
			add_action( "manage_snippets_posts_custom_column", array( &$this, "admin_columnContent" ), 10, 2);
			add_filter( "manage_edit-snippets_sortable_columns", array( &$this, "admin_columnSortable" ) );
			
			add_action( "add_meta_boxes", array( &$this, "remove_wp_seo_meta_box" ), 100000 );
			add_filter( "manage_edit-snippets_columns", array( &$this, "remove_wp_seo_columns" ) );
		} else {
			add_action( "wp_enqueue_scripts", array( &$this, "frontEnd_scripts" ));
		}
	}
	
	public function install() {
		$this->register_post_type(); // Create custom post type
		flush_rewrite_rules(); // Clear the permalinks after the post type has been registered
		
		update_option( "cs_version", CODESNIPS_VER, true );
		
		// Since 1.1.6. Set the rewrite slug if it's not been set. This prevents the slug from being overwritten on deactivate/reactivate
		if ( !get_option( "cs_rewrite_slug" ) ) {
			update_option( "cs_rewrite_slug", "snippets", true );
			update_option( "cs_rewrite_slug_old", "snippets", true );
		}
	   
		update_option( "cs_global_show_metabar", "true", true );
		
		update_option( "cs_admin_theme", "chrome", true );
		update_option( "cs_frontend_theme", "chrome", true );
		update_option( "cs_default_language", "text", true );
		update_option( "cs_global_show_gutter", "true", true );
		update_option( "cs_global_show_php_inline", "true", true );
	}

	public function deactivation() {
		// Our post type will be automatically removed, so no need to unregister it
		// Clear the permalinks to remove the custom post type rules
		flush_rewrite_rules();
	}
	
	public function load_codesnips_textdomain() {
		load_plugin_textdomain( 'codesnips', FALSE, basename( dirname($this->file) ) . '/languages/' );
	}
	
	public function upgrade_options() {
		$cs_version = get_option( "cs_version" );
		
		// Set the plugin version
		if ( ( !$cs_version ) || ( $cs_version == "" ) ) {
			// Since 1.1.6, track plugin version
			update_option( "cs_version", CODESNIPS_VER, true );
		}		
	}	
	
	public function register_post_type() {
 
		$labels = array(
			"name"					=> __( "Code Snippets" , "codesnips" ),
			"singular_name"			=> __( "Code Snippet" , "codesnips" ),
			"add_new"				=> __( "Add New" , "codesnips" ),
			"add_new_item"			=> __( "Add New Snippet" , "codesnips" ),
			"edit_item"				=> __( "Edit Snippet" , "codesnips" ),
			"new_item"				=> __( "New Snippet" , "codesnips" ),
			"all_items"				=> __( "All Snippets" , "codesnips" ),
			"view_item"				=> __( "View Snippet" , "codesnips" ),
			"search_items"			=> __( "Search Snippets" , "codesnips" ),
			"not_found"				=> __( "No snippets found" , "codesnips" ),
			"not_found_in_trash"	=> __( "No snippets found in the Trash" , "codesnips" ), 
			"parent_item_colon"		=> "",
			"menu_name"				=> "codeSnips"
		);
		
		$args = array(
			"labels"				=>	$labels,
			"description"			=>	"Code Snippets",
			"public"				=>	true,
			"menu_position"			=>	30, // After Comments
			"menu_icon"				=>	"dashicons-editor-code",
			"has_archive"			=>	true,
			"show_in_nav_menus"		=>	true,
			"rewrite"				=>	array( "slug" => $this->slug ),
			"supports"				=>	array( "title", "editor", "publicize", "wpcom-markdown" ) // Jetpack Markdown
		);
		
		register_post_type( "snippets", $args ); 

	}
	
	public function cpt_messages( $messages ) {
		$post			= get_post();
		$post_type		= get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		$messages['snippets'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Snippet updated.', 'codesnips' ),
			2  => __( 'Custom field updated.', 'codesnips' ),
			3  => __( 'Custom field deleted.', 'codesnips' ),
			4  => __( 'Snippet updated.', 'codesnips' ),
				/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Snippet restored to revision from %s', 'codesnips' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Snippet published.', 'codesnips' ),
			7  => __( 'Snippet saved.', 'codesnips' ),
			8  => __( 'Snippet submitted.', 'codesnips' ),
			9  => sprintf(
				__( 'Snippet scheduled for: <strong>%1$s</strong>.', 'codesnips' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'codesnips' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Snippet draft updated.', 'codesnips' )
		);

		if ( ( $post_type_object->publicly_queryable ) && ( get_post_type( $post ) == "snippets") ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View Snippet', 'codesnips' ) );
			$messages[ $post_type ][1] .= $view_link;
			$messages[ $post_type ][6] .= $view_link;
			$messages[ $post_type ][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview Snippet', 'codesnips' ) );
			$messages[ $post_type ][8]  .= $preview_link;
			$messages[ $post_type ][10] .= $preview_link;
		}

		return $messages;
	}
	
	// Create the columns headers
	public function admin_columnHead( $columns ) {
		$columns["shortcode"] = "Shortcode";
		$columns["lang"] = "Language";
		$columns["filename"] = "Filename";
		return $columns;
	}

	// Fill the column with content
	public function admin_columnContent( $column_name, $post_ID ) {
		if ( "shortcode" == $column_name ) {
			$snippetShortcode = "[snippet id=". $post_ID ."]";
			echo $snippetShortcode;
		} elseif ( "lang" == $column_name ) {
			$snippetLang = get_post_meta( $post_ID, "_snippet_lang", true );
			echo $snippetLang;
		} elseif ( "filename" == $column_name ) {
			$snippetFilename = get_post_meta( $post_ID, "_snippet_filename", true );
			echo $snippetFilename;
		} else {
			return;
		}
	}

	// Make the column sortable
	function admin_columnSortable( $columns ) {
		$columns["shortcode"] = "Shortcode";
		$columns["lang"] = "Language";
		$columns["filename"] = "Filename";
		//unset($columns["date"]); // To make a column "un-sortable" remove it from the array
		return $columns;
	}
	
	public function load_dashicons() {
		wp_enqueue_style( "dashicons" );
	}
	
	public function load_AceEditor() {
		wp_register_script( "ace_editor", esc_url( "https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js" ) );
		wp_enqueue_script( "ace_editor" );
	}

	public function admin_scripts() {
		$this->load_AceEditor();
		
		wp_register_style( 'cs_adminCSS', plugins_url( 'css/admin.css', $this->file ) );
		wp_enqueue_style( "cs_adminCSS" );
	}

	public function frontEnd_scripts() {
		$this->load_AceEditor();
		wp_register_style( 'cs_frontEndCSS', plugins_url( 'css/frontend.css', $this->file ) );
		wp_enqueue_style( "cs_frontEndCSS" );
	}

	
	// Admin post area //
	
	// Remove the default WP editor
	public function remove_wp_editor() { 
		// Was seeing quirky issues when adding links to the metabox wp_editor. 
		// Default editor is hidden via CSS in admin.css
		remove_post_type_support( "snippets", "editor" );
	}
	
	// Add a meta box
	public function metabox() {
		// Only display on the snippets CPT admin page
		add_meta_box( "snippetsbox", "Enter Code Snippet", array( $this, "metabox_content" ), "snippets", "normal", "high" );
	}

	// Show the ACE editor in the metabox. Some save help from: http://wordpress.stackexchange.com/a/49906/12920
	public function metabox_content($post) {

		wp_nonce_field(plugin_basename( __FILE__ ), "cs_snippets_wpnonce");
		
		$snippetLang = get_post_meta($post->ID, "_snippet_lang", true);
		if (!$snippetLang) { 
			$snippetLang = get_option( "cs_default_language" ); // Select the default language if this is a new post
		}
		
		$snippetFileName = get_post_meta( $post->ID, "_snippet_filename", true );
		$snippetDescription = get_post_meta( $post->ID, "_snippet_description", true );
		
		// Since 1.1.6. Enable show description by default. 
		// If the post meta exists already, then the add_post_meta will be ignored by WP.
		add_post_meta( $post->ID, "_snippet_show_description", "true", true );
		$snippetShowDescription = get_post_meta( $post->ID, "_snippet_show_description", true );   
		
		$snippetHideGutter = get_post_meta( $post->ID, "_snippet_hide_gutter", true );
		$snippetCode = get_post_meta( $post->ID, "_snippet_code", true );
		
		$editorTheme = get_option( "cs_admin_theme" );

		?>
		<div class="editorOptions">
			<label for="fileName">File Name</label>
			<input id="fileName" name="snippet_filename" rows="1" cols="50" value="<?php echo $snippetFileName; ?>">
			<br><br>
			<label for="language">Language</label>
			<select id="language" name="snippet_lang"><?php echo $this->set_language_options( $snippetLang ); ?></select>
			<br><br>
			<label for="snippet_description">Snippet Description:</label>
			<br>
			<?php wp_editor( $snippetDescription, 'snippet_description', array(
				'wpautop'		=>	true,
				'media_buttons'	=>	false,
				'textarea_name'	=>	'snippet_description',
				'textarea_rows'	=>	10,
				'teeny'			=>		true
			) ); ?>
			<br>
			<br>Snippet display page options. See Usage Tips in the Settings for more information:
			<br>
			<input type="checkbox" id="snippet_show_description" name="snippet_show_description" value="true" <?php checked( $snippetShowDescription, "true", true ); ?>><label for="snippet_show_description">Show description on direct snippet page permalink.</label>
			<br>
			<input type="checkbox" id="snippet_hide_gutter" name="snippet_hide_gutter" value="true" <?php checked( $snippetHideGutter, "true", true ); ?>><label for="snippet_hide_gutter">Hide line numbers on direct snippet page permalink.</label>
			<br><br>
		</div>
		
		<label for="editor">Enter Code Snippet:</label>
		<div id="editor"><?php if ( $snippetCode ) { echo $snippetCode; } ?></div>
		<textarea id="snippet" name="snippet_code" rows="1" cols="1" style="display:none;"></textarea>
			
		<script>
			// Disable resizing and moving the metabox
			jQuery( "#snippetsbox h3.hndle" ).each( function( e ){
				jQuery( this ).attr( "class", "hndlle" );
			});
			
			// Setup the editor
			var editor = ace.edit( "editor" );
			editor.setOption( "minLines", 5 );
			editor.setOption( "maxLines", "Infinity" );
			editor.setTheme( "ace/theme/<?php echo $editorTheme; ?>" );
			var lang = "<?php echo $snippetLang; ?>";
			if ( lang == "php" ) {
				editor.getSession().setMode( { path:'ace/mode/php', inline:true } ); // Change code language on the fly
			} else {
				editor.getSession().setMode( "ace/mode/<?php echo $snippetLang; ?>" ); // Load at post load to get accurate language syntax highlighting
			}
			editor.setShowPrintMargin( false );
			editor.getSession().setUseWrapMode( false );
			
			jQuery( "textarea#snippet" ).val( editor.getValue() ); // Load snippet content into textarea at load in case the user does not click into the textarea, we maintain the snippet.
			
			jQuery( "select#language" ).on( "change", function() {
				if ( this.value == "php" ) {
					editor.getSession().setMode( { path:'ace/mode/php', inline:true } ); // Change code language on the fly
				} else {
					editor.getSession().setMode( "ace/mode/" + this.value ); // Change code language on the fly
				}
			});
			
			jQuery( "#editor textarea" ).on( "blur", function() {
				jQuery( "textarea#snippet" ).val( editor.getValue() ); // If the user clicks into the textarea, and clicks out, update the snippet textarea
			});
			
			editor.resize(); // All loaded, resize to fit.
					
		</script>
		<?php
	}
	
	
	// Set the selected code language
	public function set_language_options($selected) {
		$html = '
			<optgroup label="Common">
				<option value="css" ' . selected( 'css' , $selected , false ) . '>CSS</option>
				<option value="html" ' . selected( 'html' , $selected , false ) . '>HTML</option>
				<option value="javascript" ' . selected( 'javascript' , $selected , false ) . '>JavaScript</option>
				<option value="php" ' . selected( 'php' , $selected , false ) . '>PHP</option>
				<option value="python" ' . selected( 'python' , $selected , false ) . '>Python</option>
				<option value="text" ' . selected( 'text' , $selected , false ) . '>Text</option>
			</optgroup>
			<optgroup label="All">
				<option value="abap" ' . selected( 'abap' , $selected , false ) . '>ABAP</option>
				<option value="abc" ' . selected( 'abc' , $selected , false ) . '>ABC</option>
				<option value="actionscript" ' . selected( 'actionscript' , $selected , false ) . '>ActionScript</option>
				<option value="ada" ' . selected( 'ada' , $selected , false ) . '>ADA</option>
				<option value="apache_conf" ' . selected( 'apache_conf' , $selected , false ) . '>Apache Conf</option>
				<option value="asciidoc" ' . selected( 'asciidoc' , $selected , false ) . '>AsciiDoc</option>
				<option value="assembly_x86" ' . selected( 'assembly_x86' , $selected , false ) . '>Assembly x86</option>
				<option value="autohotkey" ' . selected( 'autohotkey' , $selected , false ) . '>AutoHotKey</option>
				<option value="batchfile" ' . selected( 'batchfile' , $selected , false ) . '>BatchFile</option>
				<option value="c_cpp" ' . selected( 'c_cpp' , $selected , false ) . '>C and C++</option>
				<option value="c9search" ' . selected( 'c9search' , $selected , false ) . '>C9Search</option>
				<option value="cirru" ' . selected( 'cirru' , $selected , false ) . '>Cirru</option>
				<option value="clojure" ' . selected( 'clojure' , $selected , false ) . '>Clojure</option>
				<option value="cobol" ' . selected( 'cobol' , $selected , false ) . '>Cobol</option>
				<option value="coffee" ' . selected( 'coffee' , $selected , false ) . '>CoffeeScript</option>
				<option value="coldfusion" ' . selected( 'coldfusion' , $selected , false ) . '>ColdFusion</option>
				<option value="csharp" ' . selected( 'csharp' , $selected , false ) . '>C#</option>
				<option value="curly" ' . selected( 'curly' , $selected , false ) . '>Curly</option>
				<option value="d" ' . selected( 'd' , $selected , false ) . '>D</option>
				<option value="dart" ' . selected( 'dart' , $selected , false ) . '>Dart</option>
				<option value="diff" ' . selected( 'diff' , $selected , false ) . '>Diff</option>
				<option value="dockerfile" ' . selected( 'dockerfile' , $selected , false ) . '>Dockerfile</option>
				<option value="dot" ' . selected( 'dot' , $selected , false ) . '>Dot</option>
				<option value="dummy" ' . selected( 'dummy' , $selected , false ) . '>Dummy</option>
				<option value="dummysyntax" ' . selected( 'dummysyntax' , $selected , false ) . '>DummySyntax</option>
				<option value="eiffel" ' . selected( 'eiffel' , $selected , false ) . '>Eiffel</option>
				<option value="ejs" ' . selected( 'ejs' , $selected , false ) . '>EJS</option>
				<option value="elixir" ' . selected( 'elixir' , $selected , false ) . '>Elixir</option>
				<option value="elm" ' . selected( 'elm' , $selected , false ) . '>Elm</option>
				<option value="erlang" ' . selected( 'erlang' , $selected , false ) . '>Erlang</option>
				<option value="forth" ' . selected( 'forth' , $selected , false ) . '>Forth</option>
				<option value="ftl" ' . selected( 'ftl' , $selected , false ) . '>FreeMarker</option>
				<option value="gcode" ' . selected( 'gcode' , $selected , false ) . '>Gcode</option>
				<option value="gherkin" ' . selected( 'gherkin' , $selected , false ) . '>Gherkin</option>
				<option value="gitignore" ' . selected( 'gitignore' , $selected , false ) . '>Gitignore</option>
				<option value="glsl" ' . selected( 'glsl' , $selected , false ) . '>Glsl</option>
				<option value="golang" ' . selected( 'golang' , $selected , false ) . '>Go</option>
				<option value="groovy" ' . selected( 'groovy' , $selected , false ) . '>Groovy</option>
				<option value="haml" ' . selected( 'haml' , $selected , false ) . '>HAML</option>
				<option value="handlebars" ' . selected( 'handlebars' , $selected , false ) . '>Handlebars</option>
				<option value="haskell" ' . selected( 'haskell' , $selected , false ) . '>Haskell</option>
				<option value="haxe" ' . selected( 'haxe' , $selected , false ) . '>haXe</option>
				<option value="html_ruby" ' . selected( 'html_ruby' , $selected , false ) . '>HTML (Ruby)</option>
				<option value="ini" ' . selected( 'ini' , $selected , false ) . '>INI</option>
				<option value="io" ' . selected( 'io' , $selected , false ) . '>Io</option>
				<option value="jack" ' . selected( 'jack' , $selected , false ) . '>Jack</option>
				<option value="jade" ' . selected( 'jade' , $selected , false ) . '>Jade</option>
				<option value="java" ' . selected( 'java' , $selected , false ) . '>Java</option>
				<option value="json" ' . selected( 'json' , $selected , false ) . '>JSON</option>
				<option value="jsoniq" ' . selected( 'jsoniq' , $selected , false ) . '>JSONiq</option>
				<option value="jsp" ' . selected( 'jsp' , $selected , false ) . '>JSP</option>
				<option value="jsx" ' . selected( 'jsx' , $selected , false ) . '>JSX</option>
				<option value="julia" ' . selected( 'julia' , $selected , false ) . '>Julia</option>
				<option value="latex" ' . selected( 'latex' , $selected , false ) . '>LaTeX</option>
				<option value="lean" ' . selected( 'lean' , $selected , false ) . '>Lean</option>
				<option value="less" ' . selected( 'less' , $selected , false ) . '>LESS</option>
				<option value="liquid" ' . selected( 'liquid' , $selected , false ) . '>Liquid</option>
				<option value="lisp" ' . selected( 'lisp' , $selected , false ) . '>Lisp</option>
				<option value="livescript" ' . selected( 'livescript' , $selected , false ) . '>LiveScript</option>
				<option value="logiql" ' . selected( 'logiql' , $selected , false ) . '>LogiQL</option>
				<option value="lsl" ' . selected( 'lsl' , $selected , false ) . '>LSL</option>
				<option value="lua" ' . selected( 'lua' , $selected , false ) . '>Lua</option>
				<option value="luapage" ' . selected( 'luapage' , $selected , false ) . '>LuaPage</option>
				<option value="lucene" ' . selected( 'lucene' , $selected , false ) . '>Lucene</option>
				<option value="makefile" ' . selected( 'makefile' , $selected , false ) . '>Makefile</option>
				<option value="markdown" ' . selected( 'markdown' , $selected , false ) . '>Markdown</option>
				<option value="mask" ' . selected( 'mask' , $selected , false ) . '>Mask</option>
				<option value="matlab" ' . selected( 'matlab' , $selected , false ) . '>MATLAB</option>
				<option value="maze" ' . selected( 'maze' , $selected , false ) . '>Maze</option>
				<option value="mel" ' . selected( 'mel' , $selected , false ) . '>MEL</option>
				<option value="mushcode" ' . selected( 'mushcode' , $selected , false ) . '>MUSHCode</option>
				<option value="mysql" ' . selected( 'mysql' , $selected , false ) . '>MySQL</option>
				<option value="nix" ' . selected( 'nix' , $selected , false ) . '>Nix</option>
				<option value="objectivec" ' . selected( 'objectivec' , $selected , false ) . '>Objective-C</option>
				<option value="ocaml" ' . selected( 'ocaml' , $selected , false ) . '>OCaml</option>
				<option value="pascal" ' . selected( 'pascal' , $selected , false ) . '>Pascal</option>
				<option value="perl" ' . selected( 'perl' , $selected , false ) . '>Perl</option>
				<option value="pgsql" ' . selected( 'pgsql' , $selected , false ) . '>pgSQL</option>
				<option value="powershell" ' . selected( 'powershell' , $selected , false ) . '>Powershell</option>
				<option value="praat" ' . selected( 'praat' , $selected , false ) . '>Praat</option>
				<option value="prolog" ' . selected( 'prolog' , $selected , false ) . '>Prolog</option>
				<option value="properties" ' . selected( 'properties' , $selected , false ) . '>Properties</option>
				<option value="protobuf" ' . selected( 'protobuf' , $selected , false ) . '>Protobuf</option>
				<option value="r" ' . selected( 'r' , $selected , false ) . '>R</option>
				<option value="rdoc" ' . selected( 'rdoc' , $selected , false ) . '>RDoc</option>
				<option value="rhtml" ' . selected( 'rhtml' , $selected , false ) . '>RHTML</option>
				<option value="ruby" ' . selected( 'ruby' , $selected , false ) . '>Ruby</option>
				<option value="rust" ' . selected( 'rust' , $selected , false ) . '>Rust</option>
				<option value="sass" ' . selected( 'sass' , $selected , false ) . '>SASS</option>
				<option value="scad" ' . selected( 'scad' , $selected , false ) . '>SCAD</option>
				<option value="scala" ' . selected( 'scala' , $selected , false ) . '>Scala</option>
				<option value="scheme" ' . selected( 'scheme' , $selected , false ) . '>Scheme</option>
				<option value="scss" ' . selected( 'scss' , $selected , false ) . '>SCSS</option>
				<option value="sh" ' . selected( 'sh' , $selected , false ) . '>SH</option>
				<option value="sjs" ' . selected( 'sjs' , $selected , false ) . '>SJS</option>
				<option value="smarty" ' . selected( 'smarty' , $selected , false ) . '>Smarty</option>
				<option value="snippets" ' . selected( 'snippets' , $selected , false ) . '>snippets</option>
				<option value="soy_template" ' . selected( 'soy_template' , $selected , false ) . '>Soy Template</option>
				<option value="space" ' . selected( 'space' , $selected , false ) . '>Space</option>
				<option value="sql" ' . selected( 'sql' , $selected , false ) . '>SQL</option>
				<option value="sqlserver" ' . selected( 'sqlserver' , $selected , false ) . '>SQLServer</option>
				<option value="stylus" ' . selected( 'stylus' , $selected , false ) . '>Stylus</option>
				<option value="svg" ' . selected( 'svg' , $selected , false ) . '>SVG</option>
				<option value="tcl" ' . selected( 'tcl' , $selected , false ) . '>Tcl</option>
				<option value="tex" ' . selected( 'tex' , $selected , false ) . '>Tex</option>
				<option value="textile" ' . selected( 'textile' , $selected , false ) . '>Textile</option>
				<option value="toml" ' . selected( 'toml' , $selected , false ) . '>Toml</option>
				<option value="twig" ' . selected( 'twig' , $selected , false ) . '>Twig</option>
				<option value="typescript" ' . selected( 'typescript' , $selected , false ) . '>Typescript</option>
				<option value="vala" ' . selected( 'vala' , $selected , false ) . '>Vala</option>
				<option value="vbscript" ' . selected( 'vbscript' , $selected , false ) . '>VBScript</option>
				<option value="velocity" ' . selected( 'velocity' , $selected , false ) . '>Velocity</option>
				<option value="verilog" ' . selected( 'verilog' , $selected , false ) . '>Verilog</option>
				<option value="vhdl" ' . selected( 'vhdl' , $selected , false ) . '>VHDL</option>
				<option value="xml" ' . selected( 'xml' , $selected , false ) . '>XML</option>
				<option value="xquery" ' . selected( 'xquery' , $selected , false ) . '>XQuery</option>
				<option value="yaml" ' . selected( 'yaml' , $selected , false ) . '>YAML</option>
				<option value="django" ' . selected( 'django' , $selected , false ) . '>Django</option>
			</optgroup>
		';
		return $html;
	}

	// Save post data
	public function save_postdata( $post_id ) {
		// Ignore autosaves
		if ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE) 
			return;
		
		// If this is just a revision, don't do anything
		if ( wp_is_post_revision( $post_id ) )
			return;

		// Check nonce authorization
		if ( ( isset( $_POST["cs_snippets_wpnonce"] ) ) && ( !wp_verify_nonce( $_POST["cs_snippets_wpnonce"], plugin_basename( __FILE__ ) ) ) )
			return;

		// Check permissions
		if ( ( isset( $_POST["post_type"] ) ) && ( "page" == $_POST["post_type"] ) ) {
			if (!current_user_can( "edit_page", $post_id ) ) {
				return;
			}	
		} else {
			if ( !current_user_can( "edit_post", $post_id ) ) {
				return;
			}
		}

		// Authenticated. Save the data
		if ( isset( $_POST["snippet_lang"] ) && $_POST["snippet_lang"] == "true" ) {
			update_post_meta( $post_id, "_snippet_lang", $_POST["snippet_lang"] );
		}	
		if ( isset( $_POST["snippet_filename"] ) && $_POST["snippet_filename"] == "true" ) {
			update_post_meta( $post_id, "_snippet_filename", $_POST["snippet_filename"] );
		}
		if ( isset( $_POST["snippet_description"] ) && $_POST["snippet_description"] == "true" ) {
			update_post_meta( $post_id, "_snippet_description", $_POST["snippet_description"] );
		}
		// Updated 1.1.6: If checkbox is unchecked, then it's not present in $_POST, set database to false. 
		if ( isset( $_POST["snippet_show_description"] ) && $_POST["snippet_show_description"] == "true" ) {
			update_post_meta( $post_id, "_snippet_show_description", $_POST["snippet_show_description"] );
		} else {
			update_post_meta( $post_id, "_snippet_show_description", "false" );
		}
		if ( isset( $_POST["snippet_hide_gutter"] ) && $_POST["snippet_hide_gutter"] == "true" ) {
			update_post_meta( $post_id, "_snippet_hide_gutter", $_POST["snippet_hide_gutter"] );
		} else {
			update_post_meta( $post_id, "_snippet_hide_gutter", "false" );
		}
		if ( isset( $_POST["snippet_code"] ) && $_POST["snippet_code"] == "true" ) {			
			$newContent = "[snippet id='" . $post_id . "' ";
			
			if ( $_POST["snippet_show_description"] == "true" ) {
				$newContent .= " desc='true' ";
			}
			
			if ( $_POST["snippet_hide_gutter"] == "true" ) {
				// Hide the gutter
				$newContent .= " gutter='false' ";
			}

			$newContent .= "]";
			$post = array(
				"ID"			=> $post_id,
				"post_content"	=> $newContent
			);
			// Since 1.1.6, use post_updated instead of save_post
			remove_action( "post_updated", array( $this, "save_postdata" ) ); // Prevent infinite loop
			wp_update_post( $post ); // Delete and replace any post content with the shortcode for themes to show it easily. This CPT won't have standard content
			add_action( "post_updated", array( $this, "save_postdata" ) );

			update_post_meta( $post_id, "_snippet_code", esc_textarea( $_POST["snippet_code"] ) );
		}
	}
	
	public function custom_template($template_path) {
		if ( get_post_type() == "snippets" ) {
			if ( is_archive() ) {
				if ( $theme_file = locate_template( array( "archive-snippets.php" ) ) ) {
					$template_path = $theme_file;
				}
			} elseif (is_single()) {
				if ( $theme_file = locate_template( array( "single-snippets.php" ) ) ) {
					$template_path = $theme_file;
				}
			}
		}
		return $template_path;
	}
	
	// Remove WordPress Yoast SEO metabox on snippet post
	public function remove_wp_seo_meta_box() {
		remove_meta_box( 'wpseo_meta', 'snippets', 'normal' );
	}
	// Remove the Yoast SEO columns on the snippets page
	public function remove_wp_seo_columns( $columns ) {
		unset( $columns['wpseo-score'] );
		unset( $columns['wpseo-title'] );
		unset( $columns['wpseo-metadesc'] );
		unset( $columns['wpseo-focuskw'] );
		return $columns;
	}
	
	// Customize the slug URL so that you can access http://yoursite.com/snippets/ID without altering permalinks
	public function add_codeSnips_rewrite_rules($aRules) {
		$aNewRules = array($this->slug . '/([0-9]{1,})/?$' => 'index.php?p=$matches[1]');
		$aRules = $aNewRules + $aRules;
		return $aRules;
	}
	
	// Display the custom URL ID
	public function add_custom_shorturl() {
		global $post_id, $pagenow;
		if ( ( get_post_type( $post_id ) == 'snippets' ) && ( ! in_array( $pagenow, array( 'post-new.php' ) ) ) ){
			$html = '
				<div id="edit-slug-box" class="codeSnips-custom-url">
					<strong>codeSnips shortlink:</strong> <a href=" ' . site_url( $this->slug .'/'. $post_id ) . ' ">' . site_url( $this->slug .'/'. $post_id ) . '</a>
				</div>
			';
			
			echo $html;
		}
	}
	
	// Add the raw view custom query var. Permalinks needs to be refreshed when setting this.
	public function add_custom_query_var( $vars ){
		$vars[] = "raw";
		return $vars;
	}
	
	// Allow a prettier raw URL
	public function add_codeSnips_raw_rewrite_rules( $aRules ) {
		$aNewRules = array( $this->slug . '/raw/([^/]+)/?$' => $this->slug . '/?raw=$matches[1]' );
		$aRules = $aNewRules + $aRules;
		return $aRules;
	}
 	
	// Display the raw snippet
	public function display_raw_snippet( $id ) {
		// If ?raw= is set in the URL
		if (get_query_var( 'raw' )) {
			$snippetID = get_query_var( 'raw' );
			
			if ( post_password_required( $snippetID ) ) {
				// Is the post password protected?
				status_header( 403 );	// Return forbidden
				header( 'Content-Type:text/plain' );
				__( 'This snippet is password protected and cannot be viewed in raw mode.', 'codesnips');
				die();
			} elseif ( get_post_status( $snippetID ) == 'private' ) {
				// Private posts get a 404 by default in WordPress, so return a 404 on raw snippets.
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
			} else {
				// Check for snippet
				$snippetCode = get_post_meta( $snippetID, "_snippet_code", true );
				
				if ( $snippetCode ) {
					// Valid snippet
					status_header( 200 );	
					header( 'Content-Type:text/plain' );
					$output = preg_replace_callback( "/(&#[0-9]+;)/", function( $m ) { return mb_convert_encoding( $m[1], "UTF-8", "HTML-ENTITIES"); }, $snippetCode ); 
					echo html_entity_decode( $output );
					die();

				} else {
					// Bad snippet ID. Return theme's 404
					global $wp_query;
					$wp_query->set_404();
					status_header( 404 );
				}
			}
		}
	}
	
	
	// Front end //
	
	// Shortcode
	public function shortcode( $atts, $content = "" ) {
		$atts = shortcode_atts( 
		array ( 
				'id' => '',
				'desc' => '',
				'meta' => '',
				'gutter' => '',
				'fontsize' => '',
			), $atts );
		
		if ( post_password_required( $atts["id"] ) ) {
			// Password protected snippets, show nothing
			return;
		} 
		if ( get_post_status( $atts["id"] ) == 'private' ) {
			// Private snippets, show nothing
			return;
		}
		
		$snippetLang = get_post_meta( $atts["id"], "_snippet_lang", true );
		$snippetFileName = get_post_meta( $atts["id"], "_snippet_filename", true );
		//$snippetFileName = $snippetFileName . " contains ";
		$snippetDescription = get_post_meta( $atts["id"], "_snippet_description", true );
		$snippetCode = get_post_meta( $atts["id"], "_snippet_code", true );
		$editorID = "editor_" . $atts["id"] . "_" . rand(1, 10000);
		$showGlobalMetabar = get_option( 'cs_global_show_metabar' );
		$snippetMetabarID = "snippetInfo_" . $atts["id"] . "_" . rand(1, 10000);
		$showGlobalGutter = get_option( 'cs_global_show_gutter' );
		$globalFontSize = get_option( 'cs_global_fontsize' );
		
		$editorTheme = get_option( "cs_frontend_theme" );
		
		$html = '<!-- codeSnips Shortcode Begin -->';
			
		// Only show snippet description if the shortcode contains desc="true"
		if ( isset( $atts["desc"] ) ) {
			if ( class_exists( 'WPCom_Markdown' ) ) {
				// Apply Jetpack markdown to the meta box description text
				// Credit http://www.blogercise.com/add-jetpack-markdown-support-to-plugins/
				$snippetDescription = WPCom_Markdown::get_instance()->transform( $snippetDescription, array( 'id' => false, 'unslash' => false ) );
			}
			
			$html .= '
				<div class="snippetDescription">
					' . apply_filters( 'meta_content', $snippetDescription ) . '
				</div>
			';
		}
		
		$html .= '
			<div class="cs_editor" id="'.$editorID.'">'. $snippetCode .'</div>
		';
		
		// Meta bar handling
		if ( isset( $atts["meta"] ) && $atts["meta"] == "false" ) {
			// Override at shortcode level, show no metabar for this snippet
			$html .= '
				<div class="snippetMetabar_empty"></div>
			';
		} elseif ( ( $showGlobalMetabar == "true" ) || ( $atts["meta"] == "true" ) ) {
			// Show the meta bar
			$html .= '
				<div class="snippetMetabar_outer">
					<div class="snippetMetabar_left"><span class="snippetFileIcon dashicons dashicons-media-default"></span><b>'.$snippetFileName.'</b><span id="'.$snippetMetabarID.'_totalLines"></span></div>
					<div class="snippetMetabar_right"><a href=" ' . site_url( $this->slug .'/raw/'. $atts["id"] ) . ' ">' . __( "view raw", "codesnips") . '</a></div>
				</div>
				<div class="clear"></div>
			';
		} else {
			// Globally disabled so do nothing.
			$html .= '
				<div class="snippetMetabar_empty"></div>
			';
		}
		
		$html .= '
			<script>
				var '.$editorID.' = ace.edit( "'.$editorID.'" );
				'.$editorID.'.setOption( "minLines", 5 );
				'.$editorID.'.setOption( "maxLines", "Infinity" );
				'.$editorID.'.setTheme( "ace/theme/'. $editorTheme .'" );
		';
		
		// Always use inline mode for PHP. 
		if ( $snippetLang == "php" ) {
			$html .= $editorID.'.getSession().setMode( { path:"ace/mode/php", inline:true } ); // Load at post load to get accurate language syntax highlighting
			';
		} else {
			$html .= $editorID.'.getSession().setMode( "ace/mode/'. $snippetLang .'" ); // Load at post load to get accurate language syntax highlighting
			';
		}
		
		$html .= '
				'.$editorID.'.setShowPrintMargin(false);
				'.$editorID.'.setReadOnly(true);
				'.$editorID.'.setHighlightActiveLine(false);
		';
		
		// Gutter handling
		if ( isset( $atts["gutter"] ) && $atts["gutter"] == "false" ) {
			// Override at shortcode level, show no gutter
			$html .= $editorID.'.renderer.setShowGutter(false); 
			';
		} elseif ( ( $showGlobalGutter == "true" ) || ( $atts["gutter"] == "true" ) ) {
			// Show the gutter
			$html .= $editorID.'.renderer.setShowGutter( true ); 
			';
		} else {
			// Globally disabled
			$html .= $editorID.'.renderer.setShowGutter( false ); 
			';
		}
		
		// Font size handling
		if ( isset( $atts["fontsize"] ) ) {
			// Override at shortcode level, set the font size
			$html .= $editorID.'.setFontSize( ' . $atts["fontsize"] . ' )
			';
		} elseif ( $globalFontSize ) {
			// Globally set
			$html .= $editorID.'.setFontSize( ' . $globalFontSize . ' )
			';
		} else {
			$globalFontSize = "12";
			$html .= '';
		}
		
		$html .= '
				// Count and display the lines
				var '.$snippetMetabarID.'_totalLines = '.$editorID.'.session.getLength();
				if ( '.$snippetMetabarID.'_totalLines == 1 ) {
					var html = " contains " + '.$snippetMetabarID.'_totalLines + " line"
				} else {
					var html = " contains " + '.$snippetMetabarID.'_totalLines + " lines"
				}
				//jQuery( "#'.$snippetMetabarID.'_totalLines" ).html( html );
				
				// Final resize for good measure
				'.$editorID.'.resize();
				
			</script>
			
			<!-- codeSnips Shortcode End -->
		';
		
		return $html;
	}
	
}

?>
