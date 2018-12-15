define(["require","exports","sortablejs"],function(e,a,l){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),new(function(){function e(){var a=$("#typeSupportType");a.on("changed.fu.selectlist",function(){var e=a.find("input").val();a.find('li[data-value="'+e+'"]').data("has-supporters")?($("#typeMinSupportersRow").removeClass("hidden"),$("#typeAllowMoreSupporters").removeClass("hidden")):($("#typeMinSupportersRow").addClass("hidden"),$("#typeAllowMoreSupporters").addClass("hidden"))}).trigger("changed.fu.selectlist"),$(".deleteTypeOpener button").on("click",function(){$(".deleteTypeForm").removeClass("hidden"),$(".deleteTypeOpener").addClass("hidden")}),$('[data-toggle="tooltip"]').tooltip(),this.initSectionList(),this.initDeadlines()}return e.prototype.initDeadlines=function(){$("#deadlineFormTypeComplex input").change(function(e){$(e.currentTarget).prop("checked")?($(".deadlineTypeSimple").addClass("hidden"),$(".deadlineTypeComplex").removeClass("hidden")):($(".deadlineTypeSimple").removeClass("hidden"),$(".deadlineTypeComplex").addClass("hidden"))}).trigger("change"),$(".datetimepicker").each(function(e,a){$(a).datetimepicker({locale:$(a).find("input").data("locale")})});var i=function(e){var t=e.find(".datetimepickerFrom"),n=e.find(".datetimepickerTo");t.datetimepicker({locale:t.find("input").data("locale")}),n.datetimepicker({locale:n.find("input").data("locale"),useCurrent:!1});var a=function(){var e,a;e=t.data("DateTimePicker").date(),a=n.data("DateTimePicker").date(),e&&a&&a.isBefore(e)?(t.addClass("has-error"),n.addClass("has-error")):(t.removeClass("has-error"),n.removeClass("has-error"))};t.on("dp.change",a),n.on("dp.change",a)};$(".deadlineEntry").each(function(e,a){i($(a))}),$(".deadlineHolder").each(function(e,a){var t=$(a),n=function(){var e=$(".deadlineRowTemplate").html();e=e.replace(/TEMPLATE/g,t.data("type"));var a=$(e);t.find(".deadlineList").append(a),i(a)};t.find(".deadlineAdder").click(n),t.on("click",".delRow",function(e){$(e.currentTarget).parents(".deadlineEntry").remove()}),0===t.find(".deadlineList").children().length&&n()})},e.prototype.initSectionList=function(){var i=$("#sectionsList"),d=0;i.data("sortable",l.create(i[0],{handle:".drag-handle",animation:150})),i.on("click","a.remover",function(e){e.preventDefault();var a=$(this).parents("li").first(),t=a.data("id");bootbox.confirm(__t("admin","deleteMotionSectionConfirm"),function(e){e&&($(".adminTypeForm").append('<input type="hidden" name="sectionsTodelete[]" value="'+t+'">'),a.remove())})}),i.on("change",".sectionType",function(){var e=$(this).parents("li").first(),a=parseInt($(this).val());e.removeClass("title textHtml textSimple image tabularData"),0===a?e.addClass("title"):1===a?e.addClass("textSimple"):2===a?e.addClass("textHtml"):3===a?e.addClass("image"):4===a&&(e.addClass("tabularData"),0==e.find(".tabularDataRow ul > li").length&&e.find(".tabularDataRow .addRow").click().click().click())}),i.find(".sectionType").trigger("change"),i.on("change",".maxLenSet",function(){var e=$(this).parents("li").first();$(this).prop("checked")?e.addClass("maxLenSet").removeClass("no-maxLenSet"):e.addClass("no-maxLenSet").removeClass("maxLenSet")}),i.find(".maxLenSet").trigger("change"),$(".sectionAdder").on("click",function(e){e.preventDefault();var a=$("#sectionTemplate").html();a=a.replace(/#NEW#/g,"new"+d);var t=$(a);i.append(t),d+=1,i.find(".sectionType").trigger("change"),i.find(".maxLenSet").trigger("change");var n=t.find(".tabularDataRow ul");n.data("sortable",l.create(n[0],{handle:".drag-data-handle",animation:150}))});var r=0;i.on("click",".tabularDataRow .addRow",function(e){e.preventDefault();var a=$(this),t=a.parent().find("ul"),n=$(a.data("template").replace(/#NEWDATA#/g,"new"+r));r+=1,n.removeClass("no0").addClass("no"+t.children().length),t.append(n),n.find("input").focus()}),i.on("click",".tabularDataRow .delRow",function(e){var a=$(this);e.preventDefault(),bootbox.confirm(__t("admin","deleteDataConfirm"),function(e){e&&a.parents("li").first().remove()})}),i.find(".tabularDataRow ul").each(function(){$(this).data("sortable",l.create(this,{handle:".drag-data-handle",animation:150}))})},e}())});
//# sourceMappingURL=MotionTypeEdit.js.map
