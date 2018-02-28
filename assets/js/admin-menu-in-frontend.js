jQuery(function($) {
    var admin_menu_in_frontend = $('.admin-menu-in-frontend');
    var admin_menu_wrap = admin_menu_in_frontend.find('#adminmenuwrap');
    var admin_menu = admin_menu_in_frontend.find('#adminmenu');


    // Show admin menu
    admin_menu_in_frontend.show();


    // Add "folded" class to html tag
    if (admin_menu_vars.folded) {
        $('html').addClass('folded');
    }


    // Add wp-admin url to menu links
    admin_menu.find('a').each(function() {
        var href = $(this).attr('href');
        if (href.indexOf('//') == -1) {
            var new_href = admin_menu_vars.admin_url + href;
            $(this).attr('href', new_href);
        }
    });


    // Open submenu on hover
    admin_menu.find('.wp-has-submenu').hover(function() {
        $(this).addClass('opensub');
        $(this).find('.wp-submenu').addClass('sub-open');
    }, function() {
        $(this).removeClass('opensub');
        $(this).find('.wp-submenu').removeClass('sub-open');
    });


    // Change menu height
    var submenu_max_height = 0;
    admin_menu.find('.wp-submenu').each(function() {
        submenu_max_height = Math.max(submenu_max_height, $(this).outerHeight(true));
    });
    var menu_height = admin_menu.outerHeight(true);
    var new_menu_height = menu_height + submenu_max_height - 100;
    if (new_menu_height > menu_height) {
        admin_menu.height(new_menu_height);
    }
    $('body').css({'min-height': admin_menu_wrap.outerHeight() + 'px'});


    // Click to submenu-head and go to first url
    admin_menu.find('.wp-submenu-head').click(function() {
        window.location.href = $(this).parents('li').find('a').first().attr('href');
    });


    // Collapse admin menu
    if (!admin_menu_in_frontend.find('#collapse-menu .collapse-button-icon').length) {
        admin_menu_in_frontend.find('#collapse-menu').html('<button type="button" id="collapse-button"><span class="collapse-button-icon"></span><span class="collapse-button-label">Collapse menu</span></button>');
    }
    admin_menu_in_frontend.find('#collapse-button').click(function() {
        var collapse;
        var html_tag = $('html');
        if (html_tag.hasClass('folded')) {
            html_tag.removeClass('folded');
            collapse = '';
        } else {
            html_tag.addClass('folded');
            collapse = '1';
        }

        var data = {
            action: 'amf_save_collapse_admin_menu',
            security: admin_menu_vars.collapse_nonce,
            collapse_admin_menu: collapse
        };
        $.post(admin_menu_vars.ajax_url, data, function(response) {
            //console.log(response);
        });
    });


    admin_menu_in_frontend.find('#adminmenu').append('<li id="fixate-menu"><button type="button" id="fixate-button"><span class="fixate-button-icon"></span><span class="fixate-button-label">Fixate menu</span></button></li>');
    admin_menu_in_frontend.find('#fixate-button').click(function() {
        var fixate;
        var html_tag = $('html');
        if (html_tag.hasClass('fixate-admin-menu')) {
            html_tag.removeClass('fixate-admin-menu');
            fixate = '';
        } else {
            html_tag.addClass('fixate-admin-menu');
            fixate = '1';
        }

        var data = {
            action: 'amf_save_fixate_admin_menu',
            security: admin_menu_vars.fixate_nonce,
            fixate_admin_menu: fixate
        };
        $.post(admin_menu_vars.ajax_url, data, function(response) {
            //console.log(response);
        });
    });


    // Set admin menu position
    var scroll_top = $(window).scrollTop();
    var prev_scroll_top = scroll_top;
    var setAdminMenuPosition = function () {
        var menu_height = admin_menu.outerHeight(true);
        var margin_top = parseInt($('html').css('margin-top'));
        var window_height = $(window).height();
        var screen_height = window_height - margin_top;
        prev_scroll_top = scroll_top;
        scroll_top = $(window).scrollTop();
        var offset_top = admin_menu_in_frontend.offset().top;
        var offset_bottom = offset_top + menu_height;
        var rel_offset_top = offset_top - scroll_top;
        var rel_offset_bottom = offset_bottom - scroll_top;

        if (menu_height <= screen_height) {
            admin_menu_wrap.height(screen_height);
            admin_menu_in_frontend.removeClass('fixed-bottom').addClass('fixed-top');
            admin_menu_in_frontend.css({
                'top': margin_top + 'px',
                'bottom': 'initial'
            });
        } else {
            admin_menu_wrap.height('auto');

            if (!admin_menu_in_frontend.hasClass('fixed-top') && rel_offset_top > margin_top) {
                admin_menu_in_frontend.removeClass('fixed-bottom').addClass('fixed-top');
            } else if (!admin_menu_in_frontend.hasClass('fixed-bottom') && rel_offset_bottom < window_height) {
                admin_menu_in_frontend.removeClass('fixed-top').addClass('fixed-bottom');
            } else {
                if (scroll_top != prev_scroll_top) {
                    if (scroll_top > prev_scroll_top && admin_menu_in_frontend.hasClass('fixed-top')) {
                        admin_menu_in_frontend.removeClass('fixed-bottom').removeClass('fixed-top');
                        admin_menu_in_frontend.css({
                            'top': offset_top + 'px',
                            'bottom': 'initial'
                        });
                    }
                    if (scroll_top < prev_scroll_top && admin_menu_in_frontend.hasClass('fixed-bottom')) {
                        admin_menu_in_frontend.removeClass('fixed-bottom').removeClass('fixed-top');
                        admin_menu_in_frontend.css({
                            'top': offset_top + 'px',
                            'bottom': 'initial'
                        });
                    }
                }
            }
        }
    };
    $(window).load(setAdminMenuPosition);
    $(window).scroll(setAdminMenuPosition);
    $(window).resize(setAdminMenuPosition);


    // Hide admin menu
    var menu_position = 'left';
    var menu_hidden_width = 5;
    if ($('html').hasClass('amf-rtl')) {
        menu_position = 'right';
        menu_hidden_width = 10;
    }
    var animate_speed = 300;
    var show_admin_menu = function() {
        if (!$('html').hasClass('fixate-admin-menu')) {
            var animate_function = function () {
                admin_menu_in_frontend.addClass('amf-is-opened');
            };

            admin_menu_in_frontend.removeClass('amf-hidden');
            admin_menu_in_frontend.stop();
            if (menu_position == 'left') {
                admin_menu_in_frontend.animate({left: 0}, animate_speed, animate_function);
            } else {
                admin_menu_in_frontend.animate({right: 0}, animate_speed, animate_function);
            }
        }
    };
    var hide_admin_menu = function () {
        if (!$('html').hasClass('fixate-admin-menu')) {
            var animate_width = admin_menu_in_frontend.outerWidth(false) - menu_hidden_width;
            var animate_function = function () {
                admin_menu_in_frontend.addClass('amf-hidden');
            };

            admin_menu_in_frontend.removeClass('amf-is-opened');
            admin_menu_in_frontend.stop();
            if (menu_position == 'left') {
                admin_menu_in_frontend.stop().animate({left: -animate_width + 'px'}, animate_speed, animate_function);
            } else {
                admin_menu_in_frontend.stop().animate({right: -animate_width + 'px'}, animate_speed, animate_function);
            }
        }
    };
    hide_admin_menu();
    if ($('html').hasClass('fixate-admin-menu')) {
        admin_menu_in_frontend.removeClass('amf-hidden');
    }
    admin_menu_in_frontend.hover(
        function() {
            admin_menu_in_frontend.addClass('amf-hover');
            show_admin_menu();
        },
        function () {
            admin_menu_in_frontend.removeClass('amf-hover');
            if (admin_menu_in_frontend.hasClass('amf-is-opened')) {
                setTimeout(function () {
                    if (!admin_menu_in_frontend.hasClass('amf-hover')) {
                        hide_admin_menu();
                    }
                }, 100);
            } else {
                hide_admin_menu();
            }

        }
    );


    // Set cookie recent page url
    var set_cookie = function(name, value) {
        var d = new Date();
        d.setTime(d.getTime() + (30*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    };
    set_cookie('amf_recent_page_url', window.location.href);

});