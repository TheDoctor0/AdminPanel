$(document).ready(function() {
    var form = $('#form');
    form.submit(function(event){
        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            success: function(response) {
                $('#result').html(response);
				$("#result").get(0).scrollIntoView();
            },
            async: true
        });
        event.preventDefault();
    });
});