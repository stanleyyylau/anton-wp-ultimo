<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
     <input type="hidden" name="cmd" value="_xclick-subscriptions">
     <input type="hidden" name="business" value="<? echo $paypal; ?>">
     <input type="hidden" name="item_name" value="<? echo $sitename; ?> Monthly <?php echo $middlelevel ?> Membership <? echo $userid; ?>">
     <input type="hidden" name="item_number" value="monthly">
     <input type="hidden" name="no_note" value="1">
     <input type="hidden" name="currency_code" value="USD">
     <button type="submit" class="btn btn-success btn-block">Order via PayPal</button>
     <input type="hidden" name="a3" value="<? echo $jvprice; ?>">
     <input type="hidden" name="return" value="<? echo $domain; ?>/members/proreturn.php">
     <input type="hidden" name="p3" value="1">
     <input type="hidden" name="t3" value="M">
     <input type="hidden" name="src" value="1">
     <input type="hidden" name="sra" value="1">
     <input type="hidden" name="on0" value="User ID">
     <input type="hidden" name="os0" value="<? echo $userid; ?>">
     <input type="hidden" name="notify_url" value="<? echo $domain; ?>/members/jv_ipn.php">
</form>