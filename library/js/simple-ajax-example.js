jQuery(document).ready(function($) {
 
    // We'll pass this variable to the PHP function example_ajax_request
    var fruit = 'Banana';
     
    // This does the ajax request
    $.ajax({
        url: example_ajax_obj.ajaxurl,
        data: {
            'action': 'example_ajax_request',
            'fruit' : fruit,
            'nonce' : example_ajax_obj.nonce
        },
        success:function(data) {
            // This outputs the result of the ajax request
            console.log(data);
        },
        error: function(errorThrown){
            console.log(errorThrown);
        }
    });  
              
});