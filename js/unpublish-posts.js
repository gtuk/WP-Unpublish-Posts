jQuery(document).ready( function($) {
    $('.edit-unpublish').click( function() {
        if ( $('#gtuk-unpublish').is(':hidden') ) {
            $('#gtuk-unpublish').slideDown('fast');
            $('.edit-unpublish').hide();
        }
    });

    $('.gtuk-cancel-unpublish').click( function() {
        $('#gtuk-unpublish').slideUp('fast');
        $('.edit-unpublish').show();
    });
});