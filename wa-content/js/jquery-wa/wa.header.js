$(function () {
    $(window).resize(function() {
        var i = parseInt(($('#wa-applist ul').width() - 1) / 72);
        if (i-- < $('#wa-applist li[id!=""]').length) {
            if ( !$("#wa-moreapps").hasClass('uarr') && $('#wa-applist li:eq('+i+')').attr('id')) {
                if ($('#wa-applist li[id]:eq(' + (i - 1) + ')').length) {
                    $('#wa-moreapps').show().parent().insertAfter($('#wa-applist li[id]:eq(' + (i - 1) + ')'));
                } else {
                    $('#wa-moreapps').hide().parent().insertAfter($('#wa-applist li:last'));
                }
            }
        } else if ($('#wa-applist li:last').attr('id')) {
            $('#wa-moreapps').hide().parent().insertAfter($('#wa-applist li:last'));
        } else {
            if ($('#wa-moreapps').hasClass('uarr')) {
                $('#wa-header').css('height', '83px');
                $('#wa-moreapps').removeClass('uarr');
            }
            $('#wa-moreapps').hide();
        }

        /*
        if ($("#wa-applist ul>li").length * 75 > $('#wa-applist').width()) {
            $('#wa-moreapps').show();
        } else {
            $('#wa-moreapps').hide();
        }
        */
    }).resize();

    var lastScrollTopPosition = 0;
    $('.content').scroll(function() {
       var _lstp = $(this).scrollTop();
       if (_lstp > lastScrollTopPosition) {
           $('#wa-apps').removeClass('wa-apps-sticky');
       } else if (_lstp > $('#wa-apps').height()) {
           $('#wa-apps').addClass('wa-apps-sticky');
       }
       lastScrollTopPosition = _lstp;
    });

    var sortableApps = function () {
        $("#wa-applist ul").sortable({
            distance: 5,
            helper: 'clone',
            items: 'li[id]',
            opacity: 0.75,
            tolerance: 'pointer',
            stop: function () {
            var data = $(this).sortable("toArray");
            var apps = [];
            for (var i = 0; i < data.length; i++) {
                var id = data[i].replace(/wa-app-/, '');
                if (id) {
                    apps.push(id);
                }
            }
            var url = backend_url + "?module=settings&action=save";
            $.post(url, {name: 'apps', value: apps});
        }});
    };

    if ($.fn.sortable) {
        sortableApps();
    } else if (!$('#wa').hasClass('disable-sortable-header')) {

        var urls = [];
        if (!$.browser) {
            urls.push('wa-content/js/jquery/jquery-migrate-1.2.1.min.js');
        }
        if (!$.ui) {
            urls.push('wa-content/js/jquery-ui/jquery.ui.core.min.js');
            urls.push('wa-content/js/jquery-ui/jquery.ui.widget.min.js');
            urls.push('wa-content/js/jquery-ui/jquery.ui.mouse.min.js');
        } else if (!$.ui.mouse) {
            urls.push('wa-content/js/jquery-ui/jquery.ui.mouse.min.js');
        }
        urls.push('wa-content/js/jquery-ui/jquery.ui.sortable.min.js');

        var $script = $("#wa-header-js");
        var path = $script.attr('src').replace(/wa-content\/js\/jquery-wa\/wa.header.js.*$/, '');
        $.when.apply($, $.map(urls, function(file) {
            return $.ajax({
                cache: true,
                dataType: "script",
                url: path + file
            });
        })).done(sortableApps);

        // Determine user timezone when "Timezone: Auto" is saved in profile
        if ($script.data('determine-timezone') && !document.cookie.match(/\btz=/)) {
            var version = $script.attr('src').split('?', 2)[1];
            $.ajax({
                cache: true,
                dataType: "script",
                url: path + "wa-content/js/jquery-wa/wa.core.js?" + version,
                success: function() {
                    $.wa.determineTimezone(path);
                }
            });
        }
    }

/*
    $('#wa-header').on('mousemove', function () {
        if ($('#wa-moreapps').is(':visible') && !$('#wa-moreapps').hasClass('uarr')) {
            var self = this;
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            this.timeout = setTimeout(function () {
                if (!$('#wa-moreapps').hasClass('uarr')) {
                    $('#wa-moreapps').click();
                }
                self.timeout = null;
            }, 2000);
        }
    }).on('blur', function () {
        if (this.timeout) {
            clearTimeout(this.timeout)
            this.timeout = null;
        }
    }).on('mouseleave', function () {
        if (this.timeout) {
            clearTimeout(this.timeout)
            this.timeout = null;
        }
    });
*/

    var pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
    $(window).on("load", function() {
        if (pixelRatio > 1) {
            $('#wa-applist img').each(function() {
                if ($(this).data('src2')) {
                    $(this).attr('src', $(this).data('src2'));
                }
            });
        }
    });

    // $('#wa-moreapps').click(function() {
    //     if ($(this).hasClass('uarr'))
    //     {
    //         $('#wa-header').css('height', '83px');
    //         $('#wa-moreapps').removeClass('uarr');
    //         $('#wa-header').removeClass('wa-moreapps');
    //         $(window).resize();
    //     } else {
    //         if ($('#wa-applist li:last').attr('id')) {
    //             $('#wa-moreapps').parent().insertAfter($('#wa-applist li:last'));
    //         }
    //         $('#wa-header').css('height', 'auto');
    //         $('#wa-moreapps').addClass('uarr');
    //         $('#wa-header').addClass('wa-moreapps');
    //     }
    //     return false;
    // });

    $('#wa-mobile-hamburger a').click(function() {
        alert('wa2uidemo PREVIEW NOTICE: верхний фиксированный ряд превращается в меню приложений с возможностью скролла, а ниже показывается блок #wa-app-navigation, который обычно находится в сайдбаре приложения (хотя может быть и каким угодно, как, например, меню в ШС). простите, но в превью-версии это поведение вырезано.');
        return false;
    });

    $('#wa').on('click', 'a.wa-announcement-close', function () {
        var app_id = $(this).attr('rel');
        if ($(this).closest('.d-notification-block').length) {
            $(this).closest('.d-notification-block').remove();
            if (!$('.d-notification-wrapper').children().length) {
                $('.d-notification-wrapper').hide();
            }
        } else {
            $(this).next('p').remove();
            $(this).remove();
        }
        var url = backend_url + "?module=settings&action=save";
        $.post(url, {app_id: app_id, name: 'announcement_close', value: 'now()'});
        return false;
    });

    var updateCount = function () {
        $.ajax({
            url: backend_url + "?action=count",
            data: {'background_process': 1},
            success: function (response) {
                if (response && response.status == 'ok') {
                    // announcements
                    if (response.data.__announce) {
                        $('#wa-announcement').remove();
                        $('#wa-header').before(response.data.__announce);
                        delete response.data.__announce;
                    }

                    // applications
                    $('#wa-header a span.indicator').hide();
                    for (var app_id in response.data) {
                        var n = response.data[app_id];
                        if (n) {
                            var a = $("#wa-app-" + app_id + " a");
                            if (typeof(n) == 'object') {
                                a.attr('href', n.url);
                                n = n.count;
                            }
                            if (a.find('span.indicator').length) {
                                    if(n) {
                                        a.find('span.indicator').html(n).show();
                                    } else {
                                        a.find('span.indicator').remove();
                                    }
                            } else if(n) {
                                a.append('<span class="indicator">' + n + '</span>');
                            }
                        } else {
                            $("#wa-app-" + app_id + " a span.indicator").remove();
                        }
                    }
                    $(document).trigger('wa.appcount', response.data);
                }
                setTimeout(updateCount, 60000);
            },
            error: function () {
                setTimeout(updateCount, 60000);
            },
            dataType: "json",
            async: true
        });
    };

    // update counts immidiately if there are no cached counts; otherwise, update later
    if (!$('#wa-applist').is('.counts-cached')) {
        updateCount();
    } else {
        setTimeout(updateCount, 60000);
    }
});
