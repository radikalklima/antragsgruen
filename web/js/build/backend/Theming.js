define(["require","exports"],function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.Theming=void 0;var n=function(i){var n=(this.$row=i).find(".uploadCol label .text");i.on("click",".imageChooserDd ul a",function(e){e.preventDefault();var t=$(e.currentTarget).find("img").attr("src");i.find("input[type=hidden]").val(t),0===i.find(".logoPreview img").length&&i.find(".logoPreview").prepend('<img src="" alt="">'),i.find(".logoPreview img").attr("src",t).removeClass("hidden"),n.text(n.data("title")),i.find("input[type=file]").val("")}),i.find("input[type=file]").on("change",function(){var e=i.find("input[type=file]").val().split("\\"),t=e[e.length-1];i.find("input[type=hidden]").val(""),i.find(".logoPreview img").addClass("hidden"),n.text(t)})},i=function(e){var i=this;this.$form=e,this.$form.find(".row_image").each(function(e,t){new n($(t))}),this.$form.on("click",".btnResetTheme",function(e){e.preventDefault();var t={title:$(e.currentTarget).data("confirm-title"),message:$(e.currentTarget).data("confirm-message"),inputType:"radio",inputOptions:[{text:$(e.currentTarget).data("name-classic"),value:"layout-classic"},{text:$(e.currentTarget).data("name-dbjr"),value:"layout-dbjr"}],callback:function(e){if(e){var t=$('<input type="hidden" name="defaults" value="1">').attr("value",e);i.$form.append('<input type="hidden" name="resetTheme" value="1">'),i.$form.append(t),i.$form.trigger("submit")}}};bootbox.prompt(t)})};t.Theming=i});
//# sourceMappingURL=Theming.js.map
