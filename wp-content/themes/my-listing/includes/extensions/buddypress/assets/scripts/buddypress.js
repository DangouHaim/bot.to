/**
 * Handles "Listings" tab in BuddyPress member page.
 *
 * @since 1.0
 */
jQuery( function( $ ) {
    var el = $('#c27-bp-listings-wrapper'),
        contents = el.find('.c27-bp-listings-grid'),
        loader = el.find('.listings-loading'),
        pagination = el.find('.c27-bp-listings-pagination'),
        authid = el.data('authid'),
        page = 0;

    function getListings() {
        contents.hide();
        pagination.hide();
        loader.show();

        $.ajax( {
            url: CASE27.ajax_url + '?action=get_listings_by_author&security=' + CASE27.ajax_nonce,
            type: 'POST',
            dataType: 'json',
            data: {
                auth_id: authid,
                page: page,
                per_page: 9,
            },
            success: function( response ) {
                contents.html( response.html );
                pagination.html( response.pagination );
                loader.hide();
                contents.fadeIn(150);
                pagination.fadeIn(150);

                setTimeout( function() {
                    if ( typeof $('.c27-bp-listings-grid').data('isotope') !== 'undefined' ) {
                        $('.c27-bp-listings-grid').isotope('destroy');
                    }

                    if ( response.found_posts ) {
                        $('.c27-bp-listings-grid').isotope( { itemSelector: '.grid-item' } );
                    }

                    $('.lf-background-carousel').owlCarousel( { margin: 20, items: 1, loop: true } );
                    $('[data-toggle="tooltip"]').tooltip( { trigger: 'hover' } );
                }, 10 );
            },
        } );
    }

    pagination.on( 'click', 'a', function(e) {
        e.preventDefault();
        page = parseInt( $(this).data('page'), 10 ) - 1;
        getListings();
    } );

    getListings();
} );