/*
 ajax function for deleting CTL file
 */
$(function(){
    $(".requestRowSwitcher").each( function() {
        var element = $(this);
        $.ajax({
            url: "ajax/switcher.php",
            type: "POST",
            dataType : 'json',
            async: false,
            data: {
                lineNumber: element.find(".line").text(),
                cluster: element.find(".cluster").text()
            },
            beforeSend: function( data ) {
                element.find(".status").html( "<strong style='color: grey'>Sending</strong>" );
            },
            success: function( data, status ) {
                if (data.success) {
                    var color = 'green';
                    var status = 'Success';
                } else {
                    var color = 'red';
                    var status = 'Fail'
                }
                console.log(data.code);
                element.find(".status").html( "<strong style='color: " + color + "'>" + status + "</strong>" );
                element.find(".message").html( "<strong style='color: " + color + "'>" +  data.message + "</strong>" );
                element.find(".code").html( "<strong style='color: " + color + "'>" +  data.code + "</strong>" );
            },
            error: function( data ) {
                console.log(data);
                element.find(".status").html( "<strong style='color: red'>Error</strong>" );
                element.find(".message").html( "<strong style='color: red'>" + data.responseText + "</strong>" );
            }
        });
    });
});