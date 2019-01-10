<?php CurrySearch::get_status(); ?>
<h1><?php echo esc_html__('CurrySearch Settings and Statistics', 'currysearch') ?></h1>

  <p><b><?php
if (null !== CurrySearch::get_port()) {
	  echo esc_html__('Status: Everything Ok!', 'currysearch');?>
</b></p>

<ul>
<li>ApiKey: <?php echo CurrySearch::get_public_api_key() ?></li>
      <li><?php printf(esc_html__('Time of Last Indexing: %s', 'currysearch'), CurrySearch::get_last_indexing()->format(__('Y-m-d H:i:s', 'currysearch'))) ?></li>
<li><?php printf(esc_html__('Detected Language: %s', 'currysearch'), CurrySearch::get_detected_language()) ?></li>
<li><?php printf(esc_html__('Number of indexed documents: %s', 'currysearch'), CurrySearch::get_indexed_documents()) ?></li>
<li><?php printf(esc_html__('Next scheduled reindexing: %s', 'currysearch'), date_i18n( __('Y-m-d H:i:s', 'currysearch'), wp_next_scheduled('currysearch_reindexing'))) ?></li>
<li><?php printf(esc_html__('Current Plan: %s', 'currysearch'), CurrySearch::get_current_plan()) ?></li>
  <?php if (!CurrySearch::current_plan_sufficient()) { ?>
<li>
  <b>
      <?php printf(esc_html__('Your current plan is not sufficient for your WordPress installation:', 'currysearch'))?>
    <a target='_blank' href='<?php echo CurrySearch::purchase_link() ?>'>
      <?php printf(esc_html__('Please select a fitting one!', 'currysearch'))?>
    </a>
  </b>
</li>
<li>
  <b> <?php printf(esc_html__('14 days test phase ', 'currysearch'))?>  |  <?php printf(esc_html__('automatic cancellation on deactivation', 'currysearch'))?>  |  <?php printf(esc_html__('cancel any time', 'currysearch'))?> </b>
          <?php }else {?>
<li><b><a target='_blank' href='<?php echo CurrySearch::purchase_link() ?>'><?php printf(esc_html__('Get CurrySearch Premium and a faster and better search experience. Test 14 days for free. Automatically cancels when plugin is deactivated.', 'currysearch'))?></a></b></li>
<?php } ?>
</ul>
<?php
} else {
	echo esc_html__('Error Occured! Reindexing Needed!', 'currysearch');
	echo '</b></p><p>';
	echo esc_html__('If this message still exists after re-indexing, please contact support@curry-software.com!', 'currysearch');
	echo '</p>';
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
