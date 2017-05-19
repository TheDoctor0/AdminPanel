$(document).ajaxStart(function () {
    $('.loader').delay(1000).show(0);
}).ajaxStop(function () {
    $('.loader').stop(true, true).hide();
});

$(document).ready(function () {
    $(".nav a").on("click", function () {
        $(".nav").find(".active").removeClass("active");
        $(this).parent().addClass("active");
    });
});

function loadpage(path, active) {
	$(".container").load(path);
	setTimeout(loadfirst, 250);
	
	if(typeof active !== 'undefined')
	{
		var object = path.replace("/", ".");
		if (object === ".") {
			object += "index";
		}
		$(".nav").find(".active").removeClass("active");
		$(object).parent().addClass("active");
	}
}

function loaduser(path) {
    $(".col-md-9").load(path);
}

function loadfirst(){
	$("#first").trigger("click");
}

function logout() {
    $(".login").html("<span class='glyphicon glyphicon-user'></span> Logowanie");
    $(".login").attr("onclick", "loadpage('/login')");
    $(".register").show();
}


