=== Press Search ===
Contributors: pressmaximum, shrimp2t
Tags: search, live search, ajax search, better search
Requires at least: 5.1.0
Tested up to: 5.1.1
Requires PHP: 5.4
Stable tag: trunk
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A better search engine for WordPress. Quickly and accurately.

== Description ==

PressSEARCH replaces the default WordPress search engine with a more powerful search engine that gives search results relevant to the posts. 
That means the visitors will find the most accurate results.

PressSEARCH provide a powverfull AJAX live search that help visitors search and see the results quickly when they are typing like Google search.

PressSEARCH can search through every post types, e.g post, page, or any custom post type.
You can also control the search results by assigning a greater weight to the data fields.

PressSEARCH use search template to display the search results so it will work with your theme.

And PressSEARCH is packed with a lot of settings options, hook actions and filter that allow you to easily extend the plugin’s feature set.


###Notes:
> Do not PressSEARCH if your site have large amounts (hundreds of megabytes) of database space, if table `wp_posts` have more than 300,000 records using PressSEARCH may cause problems. 


###Features:
* Search results sorted in the order of relevance.
* Ajax live search. Using theme's search form.
* Fuzzy matching: match partial words, if complete words don’t match.
* Find documents matching either just one search term (OR query) or require all words to appear (AND query).
* Create custom excerpts that show where the hit was made, with the search terms highlighted.
* Highlight search terms in the search results.
* Search and inndex any custom post types.
* Search and index users, tags, categories, custom taxonomies, comments, and custom fields.
* Search and index the contents of shortcodes.
* Control the search results by assigning a greater weight to the data fields.
* Suggestion keywords.
* Custom stopwords.
* Custom synonyms.
* Search result throttling to improve performance on large databases.
* Advanced filtering to help hacking the search results the way you want.

### Pro features
* Advanced search reports.
* Search logs reports.
* Popular searches reports.
* No results searches reports.
* Redirect automatically to post, page if keywords like exactly post title.
* Redirect automatically to url, page if keywords like exactly setting keywords.

### Comming soon features
* Search for phrases with quotes, for example "search phrase".
* Support for WPML multi-language plugin and Polylang.
* Indexing attachment content (PDF, Office, Open Office).
* Search and index user profiles.
* Related posts base on post contents.
* Let the user choose between AND and OR searches, use + and – operator (AND and NOT).
* Support quoted searches (phrases).
* Support command searches, e.g: intitle: keyword, author: author name.
* Search in attachment and media library.


== Installation ==
* Download to your plugin directory or simply install via WordPress admin interface.
* Activate.
* Use.


== Frequently Asked Questions ==
= What are stop words? =
Each document database is full of useless words. 
All the little words that appear in just about every document are completely useless for information retrieval purposes. 
Basically, their inverted document frequency is really low, so they never have much power in matching. 
Also, removing those words helps to make the index smaller and searching faster.


== Screenshots ==



== Changelog ==

= 0.0.1 =
* Release

== Upgrade Notice ==
