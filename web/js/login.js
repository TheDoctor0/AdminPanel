$(document).ready(function () {
    $(document).keypress(function (e) {
        if (e.which === 13) {
            login($('#login_form'));
        }
    });

    $("#login_button").click(function(){
        login($('#login_form'));
    }); 
});

function login(form) {
	if($("#login").val().length < 5) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Login musi mieć co najmniej 5 znaków!</div>');
		$("#result").get(0).scrollIntoView();
		return false; 
    }

    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize(),
        cache: false,
		async: true,
        beforeSend: function () {
            $(".btn btn-primary").val('Logowanie...');
        },
        success: function (response) {
            if (response.indexOf("Błąd") !== -1) {
                $('#result').html(response);
                $(".wrapper").effect("shake");
                $(".btn btn-primary").val('Zaloguj');
            } else {
                if (response.indexOf("Administracyjny") !== -1)
                {
                    $(".register").hide();
                    $(".login").html("<span class='glyphicon glyphicon-user'></span> Panel Administracyjny");
                    $(".login").attr("onclick", "loadpage('/admin')");
                } else
                {
                    $(".register").hide();
                    $(".login").html("<span class='glyphicon glyphicon-user'></span> Profil Użytkownika");
                    $(".login").attr("onclick", "loadpage('/user')");
                }
                $('.container').html(response);
            }
        }
    });
}