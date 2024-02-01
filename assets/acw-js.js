jQuery(document).ready(function ($) {
    $('.acw-menu-item').on('click', function () {
        $(this).find('.acw-submenu-item').slideToggle('acw-hide')
    });

    $('.acw_username input').on('focus', function () {
        let email = $('.acw_email input').val();
        let username = email.substring(0, email.lastIndexOf("@"));
        $(this).val(username);
    });
});
