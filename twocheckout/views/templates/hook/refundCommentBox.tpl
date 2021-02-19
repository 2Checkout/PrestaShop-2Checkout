<script>
    {literal}
    $(document).ready(() => {
        var tco_refund_comment_box = '{/literal}{$tco_refund_comment_box}{literal}';
        var tco_refund_max_length_str = '{/literal}{$tco_refund_max_length_str}{literal}';
        var tco_refund_max_length = '{/literal}{$tco_refund_max_length}{literal}';

        $(document).on('click', '#desc-order-standard_refund', function () {
            if ($('#tco-refund-comment').length == 0) {
                var newRefundCommentBox = '<p class="text"><label>'+tco_refund_max_length_str+'</label> \
                    <textarea id="tco-refund-comment" class="textarea-autosize" name="tco-refund-comment" \
                        placeholder="'+tco_refund_comment_box+'" style="overflow: hidden; overflow-wrap: break-word; \
                        resize:none;height: 65px; max-width:40%;" maxlength="'+tco_refund_max_length+'"></textarea></p>';
                $('input[name=cancelProduct]').parents('.standard_refund_fields').prepend(newRefundCommentBox);
            }
        });
    });
    {/literal}
</script>
