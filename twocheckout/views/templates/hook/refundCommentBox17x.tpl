<script>
    {literal}
    $(document).ready(function() {
        var tco_refund_comment_box = '{/literal}{$tco_refund_comment_box}{literal}';
        var tco_refund_max_length_str = '{/literal}{$tco_refund_max_length_str}{literal}';
        var tco_refund_max_length = '{/literal}{$tco_refund_max_length}{literal}';

        $(document).on('click', '.return-product-display', function () {
            if ($('#tcoRefundCommBox').length == 0) {

                var newRefundCommentBox = '<div class="restock-products col-md-4 offset-4" id="tcoRefundCommBox"> \
                    <div class="form-group"> \
                    <label for="tco-refund-comment">'+tco_refund_comment_box+' ('+tco_refund_max_length_str
                    +')</label> \
                <textarea id="tco-refund-comment" class="form-control" name="tco-refund-comment" rows="3" \
                maxlength="'+tco_refund_max_length+'"></textarea> \
                    </div>\
                    </div>';

                $('.refund-checkboxes-container').parent().append(newRefundCommentBox);
            }
        });
    });
    {/literal}
</script>
