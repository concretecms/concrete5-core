var ConcreteDashboard=function(){setupResultMessages=function(){if($("#ccm-dashboard-result-message").length>0){if($(".ccm-pane").length>0){var a=$(".ccm-pane").parent().attr("class"),b=$(".ccm-pane").parent().parent().attr("class"),c=$("#ccm-dashboard-result-message").html();$("#ccm-dashboard-result-message").html('<div class="'+b+'"><div class="'+a+'">'+c+"</div></div>").fadeIn(400)}}else $("#ccm-dashboard-result-message").fadeIn(200)};var a=function(){var b=$("a[data-bookmark-action=add-favorite]"),c=$("a[data-bookmark-action=remove-favorite]"),d=!1;if(b.length)var d=CCM_DISPATCHER_FILENAME+"/ccm/system/panels/dashboard/add_favorite",e=b;else if(c.length)var d=CCM_DISPATCHER_FILENAME+"/ccm/system/panels/dashboard/remove_favorite",e=c;d&&e.on("click",function(b){b.preventDefault(),$.concreteAjax({dataType:"json",type:"GET",data:{cID:$(this).attr("data-page-id"),ccm_token:$(this).attr("data-token")},url:d,success:function(b){"remove"==b.action?(e.attr("data-bookmark-action","add-favorite"),e.html('<i class="fa fa-lg fa-bookmark-o"></i>')):(e.attr("data-bookmark-action","remove-favorite"),e.html('<i class="fa fa-lg fa-bookmark"></i>')),e.off("click"),a()}})})},b=function(){$("table.ccm-search-results-table tr[data-details-url]").each(function(){$(this).hover(function(){$(this).addClass("ccm-search-select-hover")},function(){$(this).removeClass("ccm-search-select-hover")}).on("click",function(){window.location.href=$(this).data("details-url")})}),$("div.ccm-details-panel[data-details-url]").each(function(){$(this).on("click",function(){window.location.href=$(this).data("details-url")})})},c=function(){0==$("#ccm-tooltip-holder").length&&$("<div />").attr("id","ccm-tooltip-holder").attr("class","ccm-ui").prependTo(document.body),$(".launch-tooltip").tooltip({container:"#ccm-tooltip-holder"})},d=function(){$(".dialog-launch").dialog(),$("div#ccm-dashboard-page").on("click","[data-dialog]",function(){var a=$(this).attr("data-dialog-width");a||(a=320);var b=$(this).attr("data-dialog-height");if(b||(b="auto"),$(this).attr("data-dialog-title"))var c=$(this).attr("data-dialog-title");else var c=$(this).text();var d="div[data-dialog-wrapper="+$(this).attr("data-dialog")+"]";jQuery.fn.dialog.open({element:d,modal:!0,width:a,title:c,height:b})})},e=function(){$("select[data-select=bootstrap]").bootstrapSelectToButton()};return{start:function(f){c(),setupResultMessages(),d(),e(),b(),a()}}}();