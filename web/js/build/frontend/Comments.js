define(["require","exports"],function(e,t){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=function(){return function(e){this.$widget=e,e.on("click",".replyButton",function(t){var n=$(t.currentTarget).data("reply-to"),i=e.find(".replyTo"+n);i.hasClass("hidden")?(i.removeClass("hidden"),i.find("textarea").focus()):i.addClass("hidden")}),e.on("change",".commentNotifications .notisActive",function(e){var t=$(e.currentTarget);t.prop("checked")?t.parents(".commentNotifications").find(".selectlist").removeClass("hidden"):t.parents(".commentNotifications").find(".selectlist").addClass("hidden")}),e.find(".commentNotifications .notisActive").trigger("change")}}();t.Comments=n});
//# sourceMappingURL=Comments.js.map