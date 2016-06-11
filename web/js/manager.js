/*global browser: true, regexp: true */
/*global $, jQuery, alert, console */
/*jslint regexp: true*/

(function ($) {
    "use strict";

    var createInstance = function () {
        var firstPanel = "#panelPurpose",
            $form = $("form.siteCreate"),
            $activePanel = null,
            getRadioValue = function (fieldsetClass, defaultVal) {
                var $input = $("fieldset." + fieldsetClass).find("input:checked");
                if ($input.length > 0) {
                    return $input.val();
                } else {
                    return defaultVal;
                }
            },
            getWizardState = function () {
                return {
                    wording: getRadioValue('wording', 1),
                    singleMotion: getRadioValue('singleMotion', 0),
                    motionsInitiatedBy: getRadioValue('motionWho', 1),
                    motionsDeadlineExists: getRadioValue('motionDeadline', 0),
                    motionsDeadline: $form.find("fieldset.motionDeadline .date input").val(),
                    motionScreening: getRadioValue('motionScreening', 1),
                    needsSupporters: getRadioValue('needsSupporters', 0),
                    minSupporters: $form.find("input.minSupporters").val(),
                    hasAmendments: getRadioValue('hasAmendments', 1),
                    amendSinglePara: getRadioValue('amendSinglePara', 0),
                    amendmentInitiatedBy: getRadioValue('amendmentWho', 1),
                    amendmentDeadlineExists: getRadioValue('amendmentDeadline', 0),
                    amendmentDeadline: $form.find("fieldset.amendmentDeadline .date input").val(),
                    amendScreening: getRadioValue('amendScreening', 1),
                    hasComments: getRadioValue('hasComments', 1),
                    hasAgenda: getRadioValue('hasAgenda', 0),
                    openNow: getRadioValue('openNow', 0),
                    title: $("#siteTitle").val(),
                    organization: $("#siteOrganization").val(),
                    subdomain: $("#siteSubdomain").val(),
                    contact: $("#siteContact").val()
                };
            },
            showPanel = function ($panel) {
                data = getWizardState();
                console.log(data);

                var step = $panel.data("tab");
                $form.find(".wizard .steps li").removeClass("active");
                $form.find(".wizard .steps ." + step).addClass("active");

                if ($activePanel) {
                    $activePanel.removeClass("active").addClass("inactive");
                }
                $panel.addClass("active").removeClass("inactive");
                $activePanel = $panel;

                try {
                    var isCorrect = (window.location.hash == "#" + $panel.attr("id"));
                    if ((window.location.hash == "" || window.location.hash == "#") && "#" + $panel.attr("id") == firstPanel) {
                        isCorrect = true;
                    }
                    if (!isCorrect) {
                        console.log("change");
                        window.location.hash = "#" + $panel.attr("id").substring(5);
                    }
                } catch (e) {
                    console.log(e);
                }
            },
            getNextPanel = function ($currPanel) {
                data = getWizardState();

                switch ($currPanel.attr("id")) {
                    case 'panelPurpose':
                        return $("#panelSingleMotion");
                    case 'panelSingleMotion':
                        if (data.singleMotion == 1) {
                            return $("#panelHasAmendments");
                        } else {
                            return $("#panelMotionWho");
                        }
                    case 'panelMotionWho':
                        if (data.motionsInitiatedBy == 1) { // MOTION_INITIATED_ADMINS
                            return $("#panelHasAmendments");
                        } else {
                            return $("#panelMotionDeadline");
                        }
                    case 'panelMotionDeadline':
                        return $("#panelMotionScreening");
                    case 'panelMotionScreening':
                        return $("#panelNeedsSupporters");
                    case 'panelNeedsSupporters':
                        return $("#panelHasAmendments");
                    case 'panelHasAmendments':
                        if (data.hasAmendments == 1) {
                            return $("#panelAmendSinglePara");
                        } else {
                            return $("#panelComments");
                        }
                    case 'panelAmendSinglePara':
                        return $("#panelAmendWho");
                    case 'panelAmendWho':
                        if (data.amendmentInitiatedBy == 1) { // MOTION_INITIATED_ADMINS
                            return $("#panelComments");
                        } else {
                            return $("#panelAmendDeadline");
                        }
                    case 'panelAmendDeadline':
                        return $("#panelAmendScreening");
                    case 'panelAmendScreening':
                        return $("#panelComments");
                    case 'panelComments':
                        if (data.singleMotion == 1) {
                            return $("#panelOpenNow");
                        } else {
                            return $("#panelAgenda");
                        }
                    case 'panelAgenda':
                        return $("#panelOpenNow");
                    case 'panelOpenNow':
                        return $("#panelSiteData")
                }
            },
            data = getWizardState;

        $form.find("input").change(function () {
            data = getWizardState();
        });
        $form.find(".radio-label input").change(function () {
            var $fieldset = $(this).parents("fieldset").first();
            $fieldset.find(".radio-label").removeClass("active");
            var $active = $fieldset.find(".radio-label input:checked");
            $active.parents(".radio-label").first().addClass("active");
        }).trigger("change");

        $form.find("fieldset.wording input").change(function () {
            var wording = $form.find("fieldset.wording input:checked").data("wording-name");
            $form.removeClass("wording_motion").removeClass("wording_manifesto").addClass("wording_" + wording);
        }).trigger("change");

        $form.find(".input-group.date").each(function () {
            var $this = $(this);
            $this.datetimepicker({
                locale: $this.find("input").data('locale')
            });
        });
        $form.find(".date.motionsDeadline").on("dp.change", function () {
            $("input.motionsDeadlineExists").prop("checked", true).change();
        });
        $form.find(".date.amendmentDeadline").on("dp.change", function () {
            $("input.amendDeadlineExists").prop("checked", true).change();
        });
        $form.find("input.minSupporters").change(function () {
            $("input.needsSupporters").prop("checked", true).change();
        });
        $form.find("#siteSubdomain").on("keyup change", function () {
            var $this = $(this),
                subdomain = $this.val(),
                $group = $this.parents(".subdomainRow").first(),
                requesturl = $this.data("query-url").replace(/SUBDOMAIN/, subdomain),
                $err = $group.find(".subdomainError");

            if (subdomain == "") {
                $err.addClass("hidden");
                $group.removeClass("has-error").removeClass("has-success");
                return;
            }
            $.get(requesturl, function (ret) {
                if (ret['available']) {
                    $err.addClass("hidden");
                    $group.removeClass("has-error");
                    $form.find("button[type=submit]").prop("disabled", false);
                    if (ret['subdomain'] == $this.val()) {
                        $group.addClass("has-success");
                    }
                } else {
                    $err.removeClass("hidden");
                    $err.html($err.data("template").replace(/%SUBDOMAIN%/, ret['subdomain']));
                    $group.removeClass("has-success");
                    if (ret['subdomain'] == $this.val()) {
                        $form.find("button[type=submit]").prop("disabled", true);
                        $group.addClass("has-error");
                    }
                }
            });
        });
        $form.find("#siteTitle").on("keyup change", function () {
            if ($(this).val().length >= 5) {
                $(this).parents(".form-group").first().addClass("has-success");
            } else {
                $(this).parents(".form-group").first().removeClass("has-success");
            }
        });
        $form.find("#siteOrganization").on("keyup change", function () {
            if ($(this).val().length >= 5) {
                $(this).parents(".form-group").first().addClass("has-success");
            } else {
                $(this).parents(".form-group").first().removeClass("has-success");
            }
        });

        $form.find(".navigation .btn-next").click(function (ev) {
            if ($(this).attr("type") == "submit") {
                return;
            }
            ev.preventDefault();
            showPanel(getNextPanel($activePanel));
        });
        $form.find(".navigation .btn-prev").click(function (ev) {
            ev.preventDefault();
            if (window.location.hash != "") {
                window.history.back();
            }
        });
        $form.submit(function (ev) {

        });

        $(window).on("hashchange", function (ev) {
            ev.preventDefault();
            var hash;
            if (window.location.hash.substring(1) == 0) {
                hash = firstPanel;
            } else {
                hash = "#panel" + window.location.hash.substring(1);
            }
            var $panel = $(hash);
            if ($panel.length > 0) {
                showPanel($panel);
            }
        });

        $form.find(".step-pane").addClass("inactive");
        showPanel($(firstPanel));
    };

    var siteConfig = function () {
        var rebuildVisibility = function () {
            var transport = $("[name=\"mailService[transport]\"]").val(),
                auth = $("[name=\"mailService[smtpAuthType]\"]").val();

            $('.emailOption').hide();
            if (transport == 'sendmail') {
                // Nothing to do
            } else if (transport == 'mandrill') {
                $('.emailOption.mandrillApiKey').show();
            } else if (transport == 'mailgun') {
                $('.emailOption.mailgunApiKey').show();
                $('.emailOption.mailgunDomain').show();
            } else if (transport == 'smtp') {
                $('.emailOption.smtpHost').show();
                $('.emailOption.smtpPort').show();
                $('.emailOption.smtpAuthType').show();
                if (auth != 'none') {
                    $('.emailOption.smtpUsername').show();
                    $('.emailOption.smtpPassword').show();
                }
            }
        };
        $("#smtpAuthType").on("changed.fu.selectlist", rebuildVisibility);
        $("#emailTransport").on("changed.fu.selectlist", rebuildVisibility).trigger("changed.fu.selectlist");
    };

    var antragsgruenInit = function () {
        $('#sqlPassword').on('keyup', function () {
            $('#sqlPasswordNone').prop('checked', false);
        });
        $('#sqlPasswordNone').on('change', function () {
            if ($(this).prop('checked')) {
                $('#sqlPassword').val('').attr('placeholder', '');
            }
        });
        $('.testDBcaller').click(function () {
            var $pending = $('.testDBRpending'),
                $success = $('.testDBsuccess'),
                $error = $('.testDBerror'),
                $createTables = $('.createTables'),
                csrf = $('input[name=_csrf]').val(),
                url = $(this).data('url'),
                params = {
                    'sqlType': $("input[name=sqlType]").val(),
                    'sqlHost': $("input[name=sqlHost]").val(),
                    'sqlUsername': $("input[name=sqlUsername]").val(),
                    'sqlPassword': $("input[name=sqlPassword]").val(),
                    'sqlDB': $("input[name=sqlDB]").val(),
                    '_csrf': csrf
                };
            if ($("input[name=sqlPasswordNone]").prop("checked")) {
                params['sqlPasswordNone'] = 1;
            }
            $pending.removeClass('hidden');
            $error.addClass('hidden');
            $success.addClass('hidden');

            $.post(url, params, function (ret) {
                if (ret['success']) {
                    $success.removeClass('hidden');
                    if (ret['alreadyCreated']) {
                        $createTables.addClass('alreadyCreated');
                    } else {
                        $createTables.removeClass('alreadyCreated');
                    }
                } else {
                    $error.removeClass('hidden');
                    $error.find('.result').text(ret['error']);
                    $createTables.removeClass('alreadyCreated');
                }
                $pending.addClass('hidden');
            });
        });
    };

    $.SiteManager = {
        "createInstance": createInstance,
        "siteConfig": siteConfig,
        "antragsgruenInit": antragsgruenInit
    };

}(jQuery));
