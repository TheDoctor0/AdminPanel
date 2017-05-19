function logout() {
    $(".login").html("<span class='glyphicon glyphicon-user'></span> Logowanie");
    $(".login").attr("onclick", "loadpage('/login')");
	$(".register").show();
}