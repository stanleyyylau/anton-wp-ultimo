<?php wp_enqueue_style('list-tables'); ?>

<div class="wu-billing-table">

<?php

/**
 * Display the table
 * @var WU_Transactions_List_Table
 */
$transactions_list->prepare_items();

$transactions_list->display();

$g = $subscription->get_gateway();

?>

</div>

<?php if (current_user_can('manage_network')) : ?>

<div class="wu-subscription-totals-items">
  
  <table class="wu-subscription-totals">
    <tbody>
      
      <tr>
        <td class="label"><?php _e('Total (Payments):', 'wp-ultimo'); ?></td>
        <td width="1%"></td>
        <td class="total"><span class="woocommerce-Price-amount amount"><?php echo wu_format_currency(WU_Transactions::get_transactions_total($user_id)); ?></span>
        </td>
      </tr>

      <tr>
        <td class="label refunded-total"><?php _e('Refunded:', 'wp-ultimo'); ?></td>
        <td width="1%"></td>
        <td class="total refunded-total"><span class="woocommerce-Price-amount amount">-<?php echo wu_format_currency(WU_Transactions::get_refunds_total($user_id)); ?></span></span>
        </td>
      </tr>

      <tr>
        <td class="label"><?php _e('Total:', 'wp-ultimo'); ?></td>
        <td width="1%"></td>
        <td class="total"><span class="woocommerce-Price-amount amount"><?php echo wu_format_currency(WU_Transactions::get_total_after_refunds($user_id)); ?></span>
        </td>
      </tr>

    </tbody>
  </table>

  <div class="clear"></div>

</div>

<div id="new-transaction">

  <div id="new-transaction-trigger" class="text-right">
    <button class="button button-primary wu-display-new-transaction-trigger"><?php _e('Add new Payment Manually', 'wp-ultimo'); ?></button>
  </div>

  <div id="new-transaction-form" class="wu-button-add-new row hidden">

    <div class="wu-col-sm-2">
      <button class="button wu-close-add-new-payment"><?php _e('Close', 'wp-ultimo'); ?></button>
    </div>

    <div class="wu-col-sm-10">
      <label for="payment-description" class="new-payment-header"><?php _e('New Payment', 'wp-ultimo'); ?></label>

      <div class="row">

        <div class="wu-col-sm-4" v-show="!send_payment">
        
          <input type="text" data-format="Y-m-d H:i:S" class="wu-datepicker" name="date" placeholder="<?php _e('Date (click to pick)', 'wp-ultimo'); ?>" class="wu-tooltip" title="<?php _e('Leave blank to log this transaction with the current time.', 'wp-ultimo'); ?>">

        </div>

        <div class="wu-col-sm-4" v-show="!send_payment">
          
          <input type="text" name="reference_id" placeholder="<?php _e('Reference', 'wp-ultimo'); ?>" class="wu-tooltip" title="<?php _e('Add a reference id to this payment. This is usually something the payment processor will provide you with.', 'wp-ultimo'); ?>">

        </div>

        <div v-bind:class="send_payment ? 'wu-col-sm-12' : 'wu-col-sm-4'">
          
          <input type="text" name="amount" placeholder="<?php printf(__('%s 0.00', 'wp-ultimo'), get_wu_currency_symbol()); ?>" class="wu-tooltip" title="<?php printf(__('Amount in %s', 'wp-ultimo'), get_wu_currency_symbol()); ?>">

        </div>

      </div>

      <textarea id="payment-description" rows="5" name="description" placeholder="<?php _e('Description', 'wp-ultimo'); ?>" class="wu-tooltip" title="<?php _e('Add a line description to this payment.', 'wp-ultimo'); ?>"></textarea>

      <?php wp_nonce_field('wu_transactions_form'); ?>

      <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

      <div class="">
        <label class="description">
          <?php _e('Keep in mind that this will *not* charge the integrated payment method. This is meant for bookkeeping purposes only.', 'wp-ultimo'); ?>
        </label>
      </div>

      <div class="text-right">
        <button class="button button-primary wu-add-new-transaction-trigger"><?php _e('Submit new Payment', 'wp-ultimo'); ?></button>
      </div>
    </div>

  </div>

</div>

<script type="text/javascript">
  (function($) {
    var new_payment = new Vue({
      el: '#new-transaction-form',
      data: {
        send_payment: false,
      },
      mounted: function() {
        // list.init();
      }
    });
  })(jQuery);
</script>

<?php endif; ?>
