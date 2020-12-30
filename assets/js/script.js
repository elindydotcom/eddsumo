
(function($) {
        
    $(function() {
            
        $('#appsumo_active').change(function() {

            if( $(this).prop('checked')) {
                $('#appsumo_active_fields').show();
            } else {
                $('#appsumo_active_fields').hide();
            }

        });
        
        
        
        $('#edd_variable_pricing').on('change', function() {
                
                if( $(this).is(':checked') ) {
                        $('#appsumo_regular_price_settings').hide();
                        
                        
                } else {
                        $('#appsumo_regular_price_settings').show();
                }
                
        })
        
        $( 'body' ).on( 'change', '.appsumo-edd-enabled select', function () {
                
                
                var fields = $(this).closest('.edd-custom-price-option-section').find('.appsumo-edd-codes input');
                
                if( fields.length == 0 ) {
                        fields = $(this).closest('#appsumo_regular_price_settings').find('.appsumo-edd-codes input');
                }
                
                console.log( fields );
                
                if( $(this).val() == 'yes') {
                        fields.prop('disabled', false);
                } else {
                        fields.prop('disabled', true);
                }
                
                
        });
        
        
        
        function set_variable_price_landing_page_link() {
                
                
                
                var row = $('.edd_variable_prices_wrapper.edd_repeatable_row').last();
                
                var row = $('.edd_variable_prices_wrapper.edd_repeatable_row').last();
                        
                        console.log( row );
                        var id = row.data('key');
                        
                        var anchor = row.find('.appsumo_vp_landing_page_link a');
                        
                        var link = anchor.attr('href');
                        var new_link = link.replace(/pid-\d+$/, 'pid-' + id );
                        
                        if( link == new_link ) {
                                new_link = link + '-pid-' + id;
                        }
                        
                        
                        anchor.attr( 'href', new_link );
                        anchor.html( new_link );
        }
        
        
        
        
        $( 'body' ).on( 'click', '.submit .edd_add_repeatable', function () {
                setTimeout( function() {
                        set_variable_price_landing_page_link();
                }, 300 );
        });
            

                
        
        
        


    });

    



}(jQuery));
