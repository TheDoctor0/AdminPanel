﻿$(document).ready(function() {
    $(".list-group a").on("click", function() {
		$(".list-group").find(".active").removeClass("active");
		$(this).addClass("active");
	});
});