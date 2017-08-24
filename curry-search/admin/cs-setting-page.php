<?php CurrySearch::get_status(); ?>
<h1><?php echo esc_html__('CurrySearch Settings and Statistics', 'curry-search') ?></h1>

  <p><b><?php
if (null !== CurrySearch::get_port()) {
	  echo esc_html__('Status: Everything Ok!', 'curry-search');?>
</b></p>

<ul>
<li>ApiKey: <?php echo CurrySearch::get_public_api_key() ?></li>
  <li><?php printf(esc_html__('Time of Last Indexing: %s', 'curry-search'), CurrySearch::get_last_indexing()->format('Y-m-d H:i:s')) ?></li>
<li><?php printf(esc_html__('Detected Language: %s', 'curry-search'), CurrySearch::get_detected_language()) ?></li>
<li><?php printf(esc_html__('Number of indexed documents: %s', 'curry-search'), CurrySearch::get_indexed_documents()) ?></li>
</ul>
<?php
} else {
	echo esc_html__('Reindexing Needed!', 'curry-search');
	echo '</b></p>';
}?>

<hr>

<h2><?php echo esc_html__('Actions', 'curry-search') ?></h2>
<form method="POST" action="options-general.php?page=curry-search">
  <?php

	wp_nonce_field('reindex');
	echo '<input type="hidden" value="true" name="reindex_requested" />';
	submit_button(esc_html__('Re-Index all Documents', 'curry-search'));
  ?>
</form>

<hr>
<h2><?php echo esc_html__('Support', 'curry-search') ?></h2>
<p><?php echo esc_html__('This plugin is currently in a beta phase. If you find an issue or have an idea of how to improve please let us know. Thank you!', 'curry-search') ?></p>
<a target="_blank" href="https://github.com/CurrySoftware/CurrySearch-WordPress/issues/new"><?php echo esc_html__('Report Issue on GitHub', 'curry-search') ?></a><br>
<a href="mailto:support@curry-software.com?subject=Issue with CurrySearch WordPress Plugin"><?php echo esc_html__('Report Issue by E-Mail', 'curry-search') ?></a>
