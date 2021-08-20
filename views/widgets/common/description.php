<label class="screen-reader-text" for="excerpt">
  <?php echo $title; ?>
</label>
                
<p><?php echo $description; ?></p>

<textarea rows="1" cols="40" name="description" id="excerpt"><?php echo $object->description; ?></textarea>