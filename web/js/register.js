$(document).ready(function () {
    $(document).keypress(function (e) {
        if (e.which === 13) {
            register($('#register_form'));
        }
    });

     $("#register_button").click(function(){
        register($('#register_form'));
    }); 
});

function register(form) {
	var login = $("#login").val();
    var pass1 = $("#password").val();
    var pass2 = $("#password2").val();
	var email = $("#email").val();
	
	if(login.length < 5) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Login musi mieć co najmniej 5 znaków!</div>');
		$("#result").get(0).scrollIntoView();
        return false; 
    }
	
    if(pass1 !== pass2) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Wpisane hasła nie są identyczne!</div>');
		$("#result").get(0).scrollIntoView();
        return false; 
    }
	
	if(pass1.length < 8) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Hasło musi mieć co najmniej 8 znaków!</div>');
		$("#result").get(0).scrollIntoView();
        return false; 
    }
	
	if(email.length < 1) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Uzupełnij pole z emailem!</div>');
		$("#result").get(0).scrollIntoView();
        return false; 
    }
	
	if(!validateEmail(email)) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> To nie jest prawidłowy email!</div>');
		$("#result").get(0).scrollIntoView();
        return false; 
    }
	
    if(pass1.indexOf(login) !== -1) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Hasło nie może zawierać loginu!</div>');
		$("#result").get(0).scrollIntoView();
        return false;
    }
	
	if(login.indexOf(pass1) !== -1) {
		$(".wrapper").effect("shake");
		$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Login nie może zawierać hasła!</div>');
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
        	$(".btn btn-primary").val('Rejestruję...');
    	},
        success: function(response) {
            if(response.indexOf("Błąd") !== -1) {
				$(".wrapper").effect("shake");
				$(".btn btn-primary").val('Zarejestruj się');
                $('#result').html(response);
				$("#result").get(0).scrollIntoView();
            }
            else {
                $('#result').html(response);
            }
        },
    });
};

function validateEmail(email) 
{
  var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}