=== codeSnips ===
Contributors: obrienlabs
Donate link: http://obrienlabs.net/donate/
Tags: admin, code, snippet, snippets, archive, library, list, embed, shortcode, php, syntax, highlight, highlighting, custom, post, raw
Requires at least: 3.9
Tested up to: 4.9
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Quickly create and embed individual code snippet posts. Access the snippet post directly, view raw, or show all snippets in an archive list.

== Description ==

Own your snippets! No longer require the use of a cloud service to host your snippets for you when you can host them right on your own WordPress site! codeSnips allows you to quickly add and embed code snippets to your website with syntax highlighting available for 124 languages! codeSnips uses custom post types where you create a new snippet post, select the programming language, enter the code snippet and publish the post. You then can either access the snippet post directly for sharing, or embed it into another post using the shortcode. 

View all of your snippets in an archived list, or even on the snippet page itself - without embedding into a new post. You can access all your snippets using a customizable pretty URL permalink. For example: http://yoursite.com/snippets

You can also view the raw plain text snippet directly, which is ideal for copy & pasting. 

Features: 

*   Quickly add new snippets with syntax highlighting available in 124 programming languages. 
*   Plain text snippets are supported.
*   Syntax highlighting changes dynamically if you select a new code language from the dropdown while editing.
*   Embed the snippets using a shortcode that has many options.
*   You can embed multiple snippets per post.
*   Access a snippet post directly via URL.
*   Access a plain text raw view of the snippet directly via URL. Ideal for copying snippets.
*   Set a description for the snippet post that will display right on the snippet post page.
*   Change the URL slug for the list of snippets to suit your site.
*   Work with a particular code language a lot? Select a default snippet code language.
*   Each snippet also has a filename field that you can use as a reference for that snippet.
*   Uninstalling the plugin will clean up all data it has stored in the WordPress database.

The default list of snippets will display as a post archive list using your theme. You can customize the way this list looks by simply adding a file in your theme called "archive-snippets.php". Likewise, if you add a theme file called "single-snippets.php" it allows you to customize the way the direct snippet pageis rendered. There are examples located included with the plugin under the "templates" folder.

== Installation ==

**Install**

Installing codeSnips can be done from inside your WordPress admin panel by going to Plugins > Add New and searching for "codeSnips". 

1. You can also manually install it by downloading the plugin from wordpress.org/plugins
1. Upload the entire `codesnips` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Customize the plugin from the menu by selecting codeSnips > Settings. 

**Uninstall**

1. Deactivate the plugin from the Plugins menu
1. Select "codeSnips" from the list and select "Delete"
1. This will delete all files from the server and all settings from the WordPress database.

== Frequently Asked Questions ==

= How do I embed a snippet into my post? =

It's easy. Once you've created your snippet, you need to know the snippet ID. Easiest way is to go back to the "All Snippets" page in the Admin Dashboard. Then just copy and paste the shortcode that's next to your post. For example, you would copy `[snippet id=5]` to your post, and that's it!

= Can I have private or password protected snippets? =

You can! Just set the status of the post when you create your snippet. An important item to understand is that if your snippet is password protected or private, then it will not show when you use the shortcode. The snippet will also not be available in the `view raw` mode.

= What if I want to hide (or show) the snippet meta bar? =

The snippet meta bar is enabled by default. The meta bar contains the filename and the code language. If you want to disable the snippet bar globally, you can do so in the codeSnips Settings page. Likewise, if you want to disable it for just a single snippet embed at a time, you can add `meta="false"` to your shortcode. For example, `[snippet id="15" meta="false"]` would show the snippet ID 15 with the description but no meta bar.

= What if I want to show the snippet description? =

The descriptions are disabled by default. To enable them, you must type in a description in the snippet edit page, then check the checkbox for it to display on the single snippet post. To add a description for an embedded post, just add `desc="true"` to your shortcode. For example, `[snippet id=20 desc="true"]` would show the snippet ID 20 and it's description if it had one.

= How do I change the look of the snippet box? =

Just edit the frontend.css file located under CSS folder. You can remove the border, or even change the way the gutter and everything looks (without changing the built-in editor Themes)

= How many options are available using the shortcode? =

You can see all the documented options under the codeSnips > Settings menu, then select the Usage tab. 

= Can I help you translate the plugin? =

Yes please! As of 1.1 the POT file is available. If you translate it, let me know and I'll include it in a maintenenace release!

== Screenshots ==

1. Snippets embedded in a post.
2. List of all snippets in an archive list. The list can be customized to your theme.
3. The direct snippet post itself. Not embedded.
4. List of all snippets in wp-admin.
5. Editing an existing snippet post type. 
6. General settings page for codeSnips.
7. Editor specific settings for the Ace Editor.
8. Usage tips to help out.

== Changelog ==

= 1.2 = 
* Upgrades to the latest version of Ace Editor.
* Another attempt at fixing the height of the code box.
* General code cleanup.

= 1.1.6 =
* Fixes a bug where the snippet slug was reset to default on deactivate/reactivate.
* Update where all new snippets have the "Show Snippet Description" enabled by default.

= 1.1.5 =
* Fixes another small bug found with CPT post update messages.

= 1.1.4 =
* Fixed a small bug with the CPT post update messages.

= 1.1.3 =
* Added Jetpack Markdown capability in the snippet description. Fixed missing language settings, too.

= 1.1.2 =
* Some changes to the plugin's Settings page structure

= 1.1.1 =
* Removed some CSS from admin.css, which was altering items it didn't need to.

= 1.1 =
* Added internationalization options for the text fields. Interested in translating this plugin? Let me know!

= 1.0.2 =
* Further updates on how the custom post type sample URL is being displayed on the General Settings page.

= 1.0.1 =
* Found a small bug with the custom post type URL being displayed incorrectly on the General Settings page.

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.2 =
This upgrade brings an upgrade to the new Ace Editor, some minor style and code fixes.

= 1.1.6 =
New update where all new snippets will have "Show Snippet Description" enabled. Fixed a bug where the snippet slug was being reset to default.

= 1.1.5 =
Fixes another small bug found with CPT post update messages.

= 1.1.4 =
Fixed a small bug with the CPT post update messages.

= 1.1.3 =
Added Jetpack Markdown capability in the snippet description. Fixed missing language settings, too.

= 1.1.2 =
Some changes to the plugin's Settings page structure

= 1.1.1 =
Removed some CSS from admin.css, which was altering items it didn't need to.

= 1.1 =
Added internationalization options for the text fields. Interested in translating this plugin? Let me know!

= 1.0.2 =
Further updates on how the custom post type sample URL is being displayed on the General Settings page.

= 1.0.1 =
Found a small bug with the custom post type URL being displayed incorrectly on the General Settings page.

= 1.0 =
Initial. 
