function addEmojiPicker(params) {
    $(params.control).find(".ow_attachments.clearfix").prepend(
        "<div class='ow_emoji_button' id='dialogEmojiBtn'></div>"
    ).find('#dialogEmojiBtn').click(function (e) {
        if ($(this).parents(".ow_chat_dialog").hasClass("ow_chat_dialog_active")) {
            e.stopPropagation();
        }
        var mainTab = "#main_tab_contact_" + params.opponentId;
        var textAreaId = "#main_tab_contact_" + params.opponentId + " #dialogTextarea";

        $(textAreaId).emojiPicker({
            width: '300px',
            height: '200px',
            container: $(mainTab),
            upper: 'top',
            button: false
        });

        $(textAreaId).emojiPicker('toggle');
    });
}