jQuery(document).ready(function($) {
   
   $('a#any_hostname_all_link, a#any_hostname_dotcom_link').click(function(e) {

        e.preventDefault();
        appendHost($(this).text());
   });

   $('a#any_hostname_add_host_link').click(function(e) {
       
       e.preventDefault();
       
       var val = $('input#any_hostname_add_host_field').val();
       val = val.replace(/\./g, "\\.");
       
       appendHost(val);
       
       $('input#any_hostname_add_host_field').val(null);
   });
});

function appendHost(host) {
    $ = jQuery;
    
    var val = $.trim($('textarea#any_hostname_allowed_hosts').val());
    $('textarea#any_hostname_allowed_hosts').val(val + "\n" + host);
}