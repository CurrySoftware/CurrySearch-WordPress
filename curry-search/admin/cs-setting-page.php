<h1><?php esc_html__('CurrySearch Settings and Statistics', 'curry-search') ?></h1>

<p>ApiKey: <?php CurrySearch::get_public_api_key() ?></p>
<p><?php printf(esc__html('Last Indexing: %s', 'curry-search'), CurrySearch::get_last_indexing()) ?></p>
<p><?php printf(esc__html('Detected Language: %s', 'curry-search'), CurrySearch::get_detected_language()) ?></p>
<p><?php printf(esc__html('Number of indexed documents: %s', 'curry-search'), CurrySearch::get_indexed_documents()) ?></p>


<h1><?php esc_html__('Actions', 'curry-search') ?></h1>

<!-- Reindex -->
<!-- Change Language -->

<hr>

<p><?php esc_html__('This plugin is currently in a beta phase. If you find an issue or have an idea of how to improve please let us know. Thank you!', 'curry-search') ?></p>
<a href="mailto:support@curry-software.com?subject=Issue with CurrySearch WordPress Plugin"><?php esc_html__('Report Issue', 'curry-search') ?></a>
