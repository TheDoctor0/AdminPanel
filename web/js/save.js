$(document).ready(function () {
    var form = $('#form');

    form.submit(function (event) {
        event.preventDefault();

        var changed = false;
		var forbidden = false;
        $("input").each(function () {
            var input = $(this);

            var new_value = input.val();
            var old_value = input.data('default');

            if (new_value != old_value)
                changed = true;
        });
		
		$("textarea").each(function () {
            var input = $(this);

            var new_value = input.val();
            var old_value = input.data('default');

            if (new_value != old_value)
                changed = true;
        });

        $("select").each(function () {
            var input = $(this);

            var new_value = input.val();
            var old_value = input.data('default');

            if (new_value != old_value)
                changed = true;
			
			if ((new_value >= 20 && old_value < 20) || (new_value < 20 && old_value >= 20))
				forbidden = true;
        });
		
		if(forbidden){
			$('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Nie możesz zmienić serwera z CS 1.6 na CS:GO i odwrotnie!</div>');
            $("#result").get(0).scrollIntoView();
            return false;
		}

        if (!changed) {
            $('#result').html('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-exclamation-sign"></span> <strong>Błąd:</strong> Żadne pole nie zostało zmienione!</div>');
            $("#result").get(0).scrollIntoView();
            return false;
        }

        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            success: function (response) {
                $('#result').html(response);
                $("#result").get(0).scrollIntoView();
            },
            async: true
        });
    });

    $("#delete_category").click(function ()
    {
        var form = $('#form');

        $.ajax({
            type: form.attr('method'),
            url: "/categorydelete",
            data: form.serialize(),
            cache: false,
            success: function (response) {
                $(".container").load(response);
            },
            async: true
        });
    });

    $("#delete_group").click(function ()
    {
        var form = $('#form');

        $.ajax({
            type: form.attr('method'),
            url: "/groupdelete",
            data: form.serialize(),
            cache: false,
            success: function (response) {
                $(".container").load(response);
            },
            async: true
        });
    });

    $("#delete_admin").click(function ()
    {
        var form = $('#form');

        $.ajax({
            type: form.attr('method'),
            url: "/adminsdelete",
            data: form.serialize(),
            cache: false,
            success: function (response) {
                $(".container").load(response);
            },
            async: true
        });
    });
	
	$("#extend_admin").click(function ()
    {
        var form = $('#form');

        $.ajax({
            type: form.attr('method'),
            url: "/adminsextend",
            data: form.serialize(),
            cache: false,
            success: function (response) {
                $('#result').html('<div class="alert alert-success"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><span class="glyphicon glyphicon-ok-sign"></span> <strong>Sukces:</strong> Usługa admina została przedłużona o 30 dni.</div>');
                $("#result").get(0).scrollIntoView();
            },
            async: true
        });
    });
});


