var AccountEdit=function(){function e(){var e=$("#userPwd").data("min-len");$(".accountDeleteForm input[name=accountDeleteConfirm]").change(function(){$(this).prop("checked")?$(".accountDeleteForm button[name=accountDelete]").prop("disabled",!1):$(".accountDeleteForm button[name=accountDelete]").prop("disabled",!0)}).trigger("change");var t=$(".emailExistingRow");if(1==t.length){var n=$(".emailChangeRow");n.addClass("hidden"),$(".requestEmailChange").click(function(e){e.preventDefault(),n.removeClass("hidden"),t.addClass("hidden"),n.find("input").focus()})}$(".userAccountForm").submit(function(t){var n=$("#userPwd").val(),a=$("#userPwd2").val();""==n&&""==a||(n.length<e?(t.preventDefault(),bootbox.alert(__t("std","pw_x_chars").replace(/%NUM%/,e))):n!=a&&(t.preventDefault(),bootbox.alert(__t("std","pw_no_match"))))})}return e}();new AccountEdit;
//# sourceMappingURL=AccountEdit.js.map