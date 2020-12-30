(function($) {
        
        $(function() {
                
                
                if( appsumo.active_form != 'redeem' ) {
                        $('#appsumo_'+appsumo.active_form+'_form_div').show();
                }
        
                $('.appsumo_btn_switch_to_login').click( function() {

                        $('#appsumo_registration_form_div').hide();
                        $('#appsumo_login_form_div').show();

                });

                $('.appsumo_btn_switch_to_registration').click( function() {

                        $('#appsumo_login_form_div').hide();
                        $('#appsumo_registration_form_div').show();

                });
        
        
        });

}(jQuery));