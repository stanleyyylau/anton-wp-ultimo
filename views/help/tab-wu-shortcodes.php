<?php

$shortcode_list = array(
  'wu_pricing_table'  => __('Display our pricing table, leading to the signup process.', 'wp-ultimo'),
  'wu_paying_users'   => __('Simply display the number of paying users at the moment.', 'wp-ultimo'),
  'wu_plan_link'      => __('Use this with the <strong>plan_id</strong> and <strong>plan_freq</strong> attributes with a valid Plan ID and frequencies (1, 3 or 12), to display a link for custom pricing tables.', 'wp-ultimo'),
  'wu_templates_list' => __('You can use this shortcode to display the available templates in your sites\'s front-end. It accepts the attribute <strong>show_filters</strong> (which can be 1, if you want to display the filter options or 0, if you want to hide it).', 'wp-ultimo') . '' . __('The parameter <strong>templates</strong> can also be passed with a comma-separated list of template ids.', 'wp-ultimo'),
  'wu_user_meta user_id="" meta_name="first_name"'      => __('This shortcode is used to retrieve user meta information on the front-end. Useful for retrieving information collected during sign-up using custom fields. You can use the attributes user_id (optional, defaults to the owner the current site) and meta_name, which is the name of the meta info you want to get (defaults to user first_name).', 'wp-ultimo'),
);

?>

<h2><?php echo __('Available Shortcodes', 'wp-ultimo') ?></h2>
<p><?php echo __('Here is the list of shortcodes available in WP Ultimo now:<br>', 'wp-ultimo'); ?></p>
<p><?php 

foreach($shortcode_list as $shortcode => $desc) {
  echo "<h4>Shortcode <code>[$shortcode]</code></h4>$desc<br><br>";
}

?></p>