=== hashtagger ===
Contributors: petersplugins, smartware.cc
Tags: hashtag, hashtags, tag, tags, tag archive, archive, social, twitter, facebook
Requires at least: 1.0
Tested up to: 4.8
Stable tag: 3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use #hashtags, @usernames and $cashtags in your posts. #hashtags are automatically added as post tags. Highly customizable!

== Description ==

This Plugin allows you to use #hashtags, @usernames and $cashtags in your posts

= Usage =

Simply use #hashtags, @usernames (if activated) and $cashtags (if activated) in your posts and pages (if activated). Activate plugin, config it and forget.

= #hashtags =

This plugin uses the [WordPress Tag system](http://codex.wordpress.org/Posts_Tags_Screen) to field your post under the desired tags. When saving a post each [#hashtag](http://en.wikipedia.org/wiki/Hashtag) is added as a "normal" tag (without leading hash) to the post, so it is fully compatible with existing tags. When showing a post all #hashtags are automatically converted to links (if activated) leading to the corresponding tag archive page (link creating can be disabled to use this plugin only for automated tag creation). It is not necessary to generally adapt existing posts, because their tags stay unchanged. But keep in mind that on saving a post / custom post all existing tags are **removed** and replaced by the tags found in your post (this behavior is configurable)!

= @usernames =

The usage of @usernames can be activated optionally. @usernames can link either to the Users Profile Page or to the Users Website. If the username does not exist the text remains unchanged and no link is created. Optionally @nicknames can be used instead of @usernames. This is **recommended to enhance security** ([read more](http://larasoftbd.com/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/)). Although using @nicknames is the better option, the default is @usernames for compatibility to Plugin versions prior 2.1.

= $cashtags =

The usage of $cashtags can be activated optionally. $cashtags link to the concerning stock symbol at MarketWatch, Google Finance or Yahoo Finance. Examples: [GOOG on MarketWatch](http://www.marketwatch.com/investing/Stock/GOOG), [GOOG on Google Finance](https://www.google.com/finance?q=GOOG), [GOOG on Yahoo Finance](http://finance.yahoo.com/q?s=GOOG). **Notice**: stock symbols can not be validated, using a invalid stock symbol will cause an not found error on the target site.

= How to use =

Just type anywhere in a post

**#hashtag** 
This adds "hashtag" as tag to the current post and on links to tag archive page for "hashtag" when showing the post. And show number of all post with relatded #hastag like #hastag(number).


**##hashtag**
Use duplicate ##hashes to tell the plugin that this word should not be converted into a tag. Duplicate hashes are replaced by a single hash when showing the post.

**@username**
This creates a link either to the Profile Page or the Website of User "username" (@username feature has to be activated).

**@@username**
Use @@username to avoid link creation. When showing the post this is displayed as "@username" without link (@username feature has to be activated).

**$cashtag**
This creates a link to the concerning stock symbol at MarketWatch, Google Finance or Yahoo Finance ($cashtag feature has to be activated).

**$$chashtag**
Use $$cashtag to avoid link creation. When showing the post this is displayed as "$cashtag" without link ($cashtag feature has to be activated).


= Need more information? =

See [Plugin Homepage](http://larasoftbd.com/free-wordpress-plugins/wp-hashtagger/) and [Plugin Doc](http://larasoftbd.com/docs/wp-hashtagger/)

= WordPress Security =

**It is highly recommended to use @nicknames instead of @usernames!** Please read [this article](http://larasoftbd.com/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/)!

= Post Types and Section Types =

It is possible to use #hashtags, @usernames and $cashtags on Posts, on Pages, on comment, on buddypress and on Custom Post Types within Content, Title and Excerpt. Activate only the Post Types and Section Types you want to be processed to avoid unnecessary processing for best performance.

= Formatting links =

Additional CSS Class(es) to add to the #hashtag and @username links can be configured on the plugins setting page.

= Display of links in front end =

Optionally all symbols (#, @, $) can be removed from the links generated in front end.

= Do you like the hashtagger Plugin? =

Thanks, I appreciate that. You don’t need to make a donation. No money, no beer, no coffee. Please, just [tell the world that you like what I’m doing](http://larasoftbd.com/make-a-donation/)! And that’s all.


= Mail Registration for new article for registered tag's =
For registration mail you have to active tooltip. We use qtip for tooltip. User can registration there mail for each new article againest tag. When site admin write new article with hastag, user can get email notification. 

= Widget =
For registration email & view existing registered tag use widget from widget section 'wp-hastager'.


== For developers ==

= Theme function =

Use `do_hashtagger( $content )` in your theme files to process #hashtags and @usernames in $content.

== Frequently Asked Questions ==

= What characters can a hashtag include? =

The hashtag detection follows the rules for hashtags on Twitter, Facebook and Google+. The minimum length for a hashtag is 2 characters. A hashtag must not start with a number (this can be changed optionally). A hashtag not only ends at a space but also at punctuation marks and other special characters. A hashtag may contain underscores.

= Does this also work for pages? =

Yes and No. Yes - the plugin can add the tags also for pages. No - WordPress does not show the tags section for pages and also pages are not listed on tag archives. 

This plugin does not change this behavior of WordPress because there already exist several plugins that add the tag functionality for pages. Please use one of them if you want to tag your pages.

= Does this also work for pages? =
Yes plugin work for buddypress. 

= How to change the Tag base? =

The Tag base for the Tag Archive Page URL (e.g. example.com/**tag**/anytag) can be set on the 'Permalink Settings' page under 'Tag base' in your WP admin.

= Where does @username link to? =

This can be set on hashtagger Settings Page. @username links can either link to the Users Profile Page or to the Users Webiste (Users Profile Page if no Webpage is set). When linking to Users Website the link can be opened in a new window if desired.

= Why should I use @nicknames instead of @usernames =

This is important to enhance WordPress security. Please read [this article](http://petersplugins.com/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames).

= Can I use #hashtags and/or @usernames in Excerpts? =

Yes, just activate this feature.

= Can I use #hashtags and/or @usernames in Titles? =

Yes, just activate this feature.

= Can I regenerate all existing Posts after changing Settings? =

Yes, you can regenerate all affected objects (Post, Pages, Custom Posts) using the current settings.

== Screenshots ==

1. hashtagger Settings General Section: This section shows the current Tag base setting
2. hashtagger Settings Tags Section: Options to allow hashtags starting with numbers and disable link creation (screenshot is outdated)
3. hashtagger Settings Usernames Section: Handling of @usernames respectively @nicknames can be changed here
4. wp-hashtagger Settings Cashtags Section: Handling of $cashtags can be changed here
5. wp-hashtagger Settings Advanced Section: Option to not delete tags that are not found in content – may be useful if already a lot of posts with tags exist - but please note that with this setting activated tags will not be removed if you delete a hashtag
6. wp-hashtagger Settings Post Types Section: In this section the Post Types to handle can be defined - activate only the Post Types for which you want to use hashtags to make sure posts are nor processed unnecessarily for better performance
7. wp-hashtagger Settings Section Types Section: In this section the Section Types to handle can be defined - only activate the Section Types you want to use for best performance
8. wp-hashtagger Settings CSS Style Section: Add CSS Classes to use for the generated links in Front End
9. wp-hashtagger Settings Display Section: Option to not display symbols in front of generated links in Front End
10. wp-hashtagger Settings Regenerate Section: Regenerate all existing Objects with the current settings
11. Wp-hashtagger setting Tooltip #hash Registration: Check this section for showing tooltip for email registration on each #hastag. And user get email notification when new articale with using registered hastag. 

== Changelog ==
