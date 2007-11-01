=== Inline Tag Thing ===
Contributors: christined
Tags: tags, tagging
Requires at least: 2.3
Tested up to: 2.3b3
Stable tag: trunk

Inline Tag Thing allows you to add and remove tags on posts, inline, using magical AJAX powers.

== Description ==

Inline Tag Thing allows you to easily remove tags from posts,  and add new tags to a post.  It provides a text box and button which allows adding one or more tags (For multiple tags, enter a comma separated list),  and existing tags have a - link which will remove a tag from a post.

To perform these manipulations, you need to have the permissions to edit the underlying post.  If you don't have edit permissions,  the box will not display.

== Screenshots ==

1. This is a screenshot of the tag entry box.

== Installation ==
1. Drop the plugin file into your wordpress plugins folder.  It will work either in a subfolder,  or in the base plugin directory.
1. Enable the plugin.

== Advanced Usage ==

There are two advanced options.  To change these options, you'll need to set a couple of flags in the plugin file.

Changing $automagicEmbed to false will stop automagically including the inline tag adder at the end of your content,  which allows you to use the ITT_ShowWidget() template tag to display the inline tag adder in a location of your choosing.

Changing $showExistingTags to true will add a dropdown list containing the tags which are currently in use which you can add by choosing a tag and clicking the + button.

Also,  there's a CSS class, itt_tagBox which you can use to override the display of the inline tag box.