jQuery(document).ready(function ($) {
    $('.acw-menu-item').on('click', function () {
        $(this).find('.acw-submenu-item').slideToggle('acw-hide')
    })
});
