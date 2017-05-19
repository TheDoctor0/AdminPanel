$(document).ready(function() {
    var form = $('#form');
    $("#change").click(function() {
		var newpassword = $("#newpassword").val();
		var oldpassword = $("#oldpassword").val();
		
        if(newpassword.length < 8) {
            $('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Nowe hasło musi mieć co najmniej 8 znaków!</div>');
            $("#result").get(0).scrollIntoView();
            return false; 
        }
        
		if(newpassword === oldpassword) {
            $('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Nowe hasło jest takie samo jak aktualne!</div>');
            $("#result").get(0).scrollIntoView();
            return false; 
        }
        
        $.ajax({
            type: form.attr('method'),
            url: form.data('change'),
            data: form.serialize(),
            cache: false,
            success: function(response) {
                $('#result').html(response);
            },
            async: true
        });
    });
	
	$("#save").click(function() {
		var changed = false;
		$(".editable").each(function(){
			var field = $(this);
			
			var value = field.val();
			var old_value = field.data('default');
			if (value != old_value)
				changed = true;
		});
		
		if(!changed) {
			$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Żadne pole nie zostało zmienione!</div>');
            $("#result").get(0).scrollIntoView();
            return false; 
		}
        $.ajax({
            type: form.attr('method'),
            url: form.data('save'),
            data: form.serialize(),
            cache: false,
            success: function(response) {
                $('#result').html(response);
            },
            async: true
        });
    });
});