define(["require","exports","../shared/DraftSavingEngine","../shared/AntragsgruenEditor"],function(t,e,r,d){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.MotionEditForm=void 0;var n=function(){function o(t){var n=this;this.$form=t,this.hasChanged=!1,$(".input-group.date").datetimepicker({locale:$("html").attr("lang"),format:"L"}),$(".wysiwyg-textarea").each(this.initWysiwyg.bind(this)),$(".form-group.plain-text").each(this.initPlainTextFormGroup.bind(this));var e=$("#draftHint"),i=e.data("motion-type"),a=e.data("motion-id");new r.DraftSavingEngine(t,e,"motion_"+i+"_"+a),t.on("submit",function(t){var e=!1;n.checkMultipleTagsError()&&(e=!0),e?t.preventDefault():$(window).off("beforeunload",o.onLeavePage)})}return o.prototype.checkMultipleTagsError=function(){var t=this.$form.find(".multipleTagsGroup");return 0!==t.length&&(0<t.find("input:checked").length?(t.removeClass("has-error"),!1):(t.addClass("has-error"),t.scrollintoview({top_offset:-50}),!0))},o.onLeavePage=function(){return __t("std","leave_changed_page")},o.prototype.initWysiwyg=function(t,e){var n=this,i=$(e).find(".texteditor"),a=new d.AntragsgruenEditor(i.attr("id"));i.parents("form").on("submit",function(){i.parent().find("textarea").val(a.getEditor().getData())}),a.getEditor().on("change",function(){n.hasChanged||(n.hasChanged=!0,$("body").hasClass("testing")||$(window).on("beforeunload",o.onLeavePage))})},o.prototype.initPlainTextFormGroup=function(t,e){var n=$(e),i=n.find("input.form-control");if(0!=n.data("max-len")){var a=n.data("max-len"),o=!1,r=n.find(".maxLenTooLong"),d=n.parents("form").first().find("button[type=submit]"),s=n.find(".maxLenHint .counter");a<0&&(o=!0,a*=-1),i.on("keyup change",function(){var t=i.val().length;s.text(t),a<t?(r.removeClass("hidden"),o||d.prop("disabled",!0)):(r.addClass("hidden"),o||d.prop("disabled",!1))}).trigger("change")}},o}();e.MotionEditForm=n});
//# sourceMappingURL=MotionEditForm.js.map
