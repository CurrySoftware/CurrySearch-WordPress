=== CurrySearch - Better Search - Bessere Suche ===
Contributors: currysoftware
Tags: advanced search, search, better search, filter, taxonomy, autocomplete
Requires at least: 4.0
Stable tag: trunk
Tested up to: 5.1
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Description: CurrySearch is an better cloud-based search for WordPress. It returns relevance based results and supports custom post types and autocomplete.

== Description ==

CurrySearch is an better cloud-based search for WordPress. It returns relevance based results and supports custom post types and autocomplete.

This advanced search plugin provides following functionality:

* Search and find any post types including custom ones
* A error-tolerant query-autocompletion
* Advanced relevance based full-text search
* Detailed usage statistics

CurrySearch tries to blend in into your theme by using its search form.
As the computationaly heavy work of this plugin is done on the search servers in the CurrySearch System, it is also very lightweight.

[youtube https://www.youtube.com/watch?v=Yv4OIQfSuAQ]

= Speedy and Relevant Search Results =

Contrary to the built-in search of WordPress CurrySearch lists better and more relevant results further up in the result list.
To achieve this, both the content and the structure of your posts are analysed and an advanced ranking formula is devised for every query.

The CurrySearch System is optimised for advanced speed and thus answers queries in a matter of milliseconds. All computationally expensive operations are executed on our search system and thus your WordPress-Site does not need any extra resources.

= Error-Tolerant Autocomplete Search Queries =

Another feature, not built into WordPress is query autocomplete. Users know and appreciate that feature from major sites, especially when on mobile devices.

CurrySearch offers a fast and error-tolerant autocomplete.
Your content is fed into the system and it thus allows relevant results.

The user-interface integrates into your theme and is intuitive for the user.

= Usage Statistics =
We provide detailed usage statistics about the search functionality of you site.
This allows you to see what your users are looking for and react accordingly.

= Custom Post Types =
You can customize which post types should be indexed and retrievable.
The activated post types can be set in the CurrySearch settings page.

= Support for 16 Languages =

Following languages are supported:

* Arabic
* Danish
* Dutch
* English
* Finnish
* French
* German
* Hungarian
* Italian
* Portuguese
* Romanian
* Russian
* Spanish
* Swedish
* Tamil
* Turkish


== Installation ==
1. Activate the search plugin
2. Enable or Disable autocomplete and customize searched post types on Settings -> CurrySearch

== Frequently Asked Questions ==

= Is CurrySearch going to slow down my site? =

No. Evaluation and ranking of the search results is done on our servers. Your site mearly fetches a list of relevant post-ids per search query.

= I published a new post. But CurrySearch does not find it yet! =

CurrySearch re-indexes your content once a day.
But you also have the option to manually re-index for every change of content.
Go to Settings -> CurrySearch to do that that.

= Something is not working as expected! =

Please contact us or create an issue on the [CurrySearch GitHub Repository](https://github.com/CurrySoftware/CurrySearch-WordPress). We will be happy to help you!

= How long does CurrySearch take to index my content? =

If you have fewer than 1000 posts: in a matter of seconds.
If you have fewer than 10000 posts: in a matter of minutes.

= Which post types are indexed? =
By default 'post' and 'page' types are indexed. But you can set these yourself.
Go to Settings -> CurrySearch to do that.

== Screenshots ==

1. Advanced relevance sorted search results
2. Let CurrySearch autocomplete your search query
3. CurrySearch Settings and Statistics Page showing info and allowing reindexing
4. Select all post types that should be searchable

== Changelog ==

= 1.6 =
Fixed bug, where CurrySearch would intercept queries on admin pages.
Tested compatibility with WordPress 5.0.

= 1.5 =
Fixed Bug where CurrySearch would not index attachments.
Tested compatibility with WordPress 5.0.

= 1.4.2 =
Fixed Bug where CurrySearch would hook into some queries it was not supposed to hook into.

= 1.4.1 =
Fixed Bug where an invalid search form by the used theme resulted in a flawed search experience.

= 1.4 =
Restructure CurrySearch so it is easier to integrate and use.

= 1.3.1 =
Improved search results by also evaluating categories and tags

= 1.3 =
Introduced statistics at my.curry-search.com

= 1.2.2 =
Fixed bug where in some cases indexing would fail due to inconsistent data

= 1.2 =
Introduced daily reindexing and using autocomplete without using the widget

= 1.1 =
Adjusted to API-Changes

= 1.0.5 =
Fixed bug that occurred when using PHP 7.1

= 1.0.4 =
Fixed bug where indexing would fail if no content types are selected

= 1.0.3 =
Support for custom post types.

= 1.0.2 =
Support for multi-term autocompletion

= 1.0.1 =
added screenshots

= 1.0 =
First Stable Release.
Some bugs fixed in CurrySearch System.

= 0.1 =
Initial Release. This Plugin is currently in BETA.
