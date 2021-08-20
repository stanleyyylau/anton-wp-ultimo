<p>
  <label for="hidden">
    <input type="checkbox" <?php checked($plan->hidden); ?> name="hidden" id="hidden">
    <?php _e('Hidden Plan', 'wp-ultimo'); ?>
  </label>
</p>

<p class="description">
  <?php _e('Check this box if you want to hide this plan from the pricing tables and change plan options.', 'wp-ultimo'); ?>
</p>