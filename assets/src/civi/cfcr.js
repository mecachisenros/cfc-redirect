CRM.$(function($) {
	$("#cfcr_post_or_page")
		.on("change", function(e) {
			var value = $(this).val();
			var opposite = value == "post" ? "page" : "post";

			if (!value) {
				$(".cfcr_post, .cfcr_page").hide();
			} else {
				$(".cfcr_" + value).show();
				$(".cfcr_" + opposite).hide();
			}
		})
		.trigger("change");
});
