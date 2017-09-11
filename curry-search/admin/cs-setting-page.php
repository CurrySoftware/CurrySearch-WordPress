<?php CurrySearch::get_status(); ?>
<h1><?php echo esc_html__('CurrySearch Settings and Statistics', 'currysearch') ?></h1>

  <p><b><?php
if (null !== CurrySearch::get_port()) {
	  echo esc_html__('Status: Everything Ok!', 'currysearch');?>
</b></p>

<ul>
<li>ApiKey: <?php echo CurrySearch::get_public_api_key() ?></li>
  <li><?php printf(esc_html__('Time of Last Indexing: %s', 'currysearch'), CurrySearch::get_last_indexing()->format('Y-m-d H:i:s')) ?></li>
<li><?php printf(esc_html__('Detected Language: %s', 'currysearch'), CurrySearch::get_detected_language()) ?></li>
<li><?php printf(esc_html__('Number of indexed documents: %s', 'currysearch'), CurrySearch::get_indexed_documents()) ?></li>
</ul>
<?php
} else {
	echo esc_html__('Reindexing Needed!', 'currysearch');
	echo '</b></p>';
}?>

<hr>
<form method='POST' action='options-general.php?page=currysearch'>
  <?php
	wp_nonce_field('settings_changed');
	echo '<input type="hidden" value="true" name="settings_changed" />';
	do_settings_sections('currysearch');
  	submit_button(esc_html__('Save Settings', 'currysearch'));
  ?>
</form>
<hr>

<h2><?php echo esc_html__('Actions', 'currysearch') ?></h2>
<form method="POST" action="options-general.php?page=currysearch">
  <?php

	wp_nonce_field('reindex');
	echo '<input type="hidden" value="true" name="reindex_requested" />';
	submit_button(esc_html__('Re-Index all Documents', 'currysearch'));
  ?>
</form>

<hr>
<h2><?php echo esc_html__('Support', 'currysearch') ?></h2>
<p><?php echo esc_html__('This plugin is currently in a beta phase. If you find an issue or have an idea of how to improve please let us know. Thank you!', 'currysearch') ?></p>
<a target="_blank" href="https://github.com/CurrySoftware/CurrySearch-WordPress/issues/new"><?php echo esc_html__('Report Issue on GitHub', 'currysearch') ?></a><br>
<a href="mailto:support@curry-software.com?subject=Issue with CurrySearch WordPress Plugin"><?php echo esc_html__('Report Issue by E-Mail', 'currysearch') ?></a>
