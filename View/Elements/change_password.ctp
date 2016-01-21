<?php
/**
 * This needs to be used within the CakePHP <form> opening and closing tags (specifically FormHelper::create() and
 * FormHelper::end()). This is because the model parameter in FormHelper::create() determines the prefix for the
 * generation of the DOM input element IDs.
 *
 * It should be noted that the DOM input element IDs are suffixed by FormHelper::domIdSuffix().
 *
 * @author https://github.com/bretten
 */
?>
<?php
// Initialize DOM ids (calling them multiple times will suffix with an incremented int)
$inputId = "#" . $this->Form->domId($this->Form->domIdSuffix($input));
$confirmId = "#" . $this->Form->domId($this->Form->domIdSuffix($confirm_input));
$toggleId = "#" . $this->Form->domId($this->Form->domIdSuffix($toggle_input));

echo $this->Form->input($toggle_input, $toggle_input_options);
?>
<?php echo $this->Html->div(null, null, $container); ?>
<?php
echo $this->Form->input($input, $input_options);
echo $this->Form->input($confirm_input, $confirm_input_options);
?>
</div>
<script type="text/javascript">
    function toggleInputs(active) {
        $('<?php echo "{$inputId}, {$confirmId}"; ?>').prop('disabled', !active);
        $('<?php echo "#" . $container['id']; ?>').toggle(active);
    }
    $(document).ready(function () {
        toggleInputs($('<?php echo $toggleId; ?>').prop('checked'));

        $('<?php echo $toggleId; ?>').change(function () {
            toggleInputs($(this).prop('checked'));
        });
    });
</script>
