<?php
?>
<div class="form-group captcha-group">
    <div class="input-group">
        <span class="input-group-addon captchadd">
            <img src="<?php echo $capath; ?>captcha.php" id="captcha" />
        </span>
        <input class="form-control input" id="inputc" type="text" name="captcha" 
        placeholder="<?php echo $encodeExplorer->getString('enter_captcha'); ?>" />
        <span class="input-group-btn">
            <button class="btn btn-default btn" type="button" id="capreload">
                <i class="fa fa-refresh"></i>
            </button>
        </span>
    </div>
</div>
<script>
    $(function() {
        $('#capreload').click(function(){  
            $('#captcha').attr('src', '<?php echo $capath; ?>captcha.php?' + (new Date).getTime());
            $('#inputc').val('');
        });
    });
</script>