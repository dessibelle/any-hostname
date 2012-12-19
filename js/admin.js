jQuery(document).ready(function($) {

   /* Example patterns */
   $('a#any_hostname_all_link, a#any_hostname_dotcom_link').click(function(e) {

        e.preventDefault();
        appendHost($(this).text());
   });

   /* Append host to textarea */
   $('a#any_hostname_add_host_link').click(function(e) {

       e.preventDefault();

       var val = $('input#any_hostname_add_host_field').val();
       val = val.replace(/\./g, "\\.");

       appendHost(val);

       $('input#any_hostname_add_host_field').val(null);
   });

   /* Display warning if current host is not in list */
   $('input#submit').closest('form').submit(function(e) {

       var patterns = $.trim($('textarea#any_hostname_allowed_hosts').val()).split("\n");
       var current_host = window.location.host;

       var idx;
       var current_host_ok = false;
       for (idx in patterns) {
           var p = patterns[idx];
           var m = current_host.match(new RegExp(p));

           current_host_ok = current_host_ok || (m == current_host);
       }

       if (!current_host_ok) {
           var host_warning = $('#any_hostname_host_warning').text();
           return confirm(host_warning);
       }

       return true;
   });
});

function appendHost(host) {
    $ = jQuery;

    var val = $.trim($('textarea#any_hostname_allowed_hosts').val());
    $('textarea#any_hostname_allowed_hosts').val(val + "\n" + host);
}
