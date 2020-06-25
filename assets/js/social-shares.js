/**
*	jQuery.catchSocialShare()
*	These functions hook into social network's sharing processes.
*
*   Copyright © 2020 Gregory Saive, Using Blockchain Ltd, All Rights Reserved.
*
*	Permission is hereby granted, free of charge, to any person obtaining a copy
*	of this software and associated documentation files (the "Software"), to deal
*	in the Software without restriction, including without limitation the rights
*	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
*	copies of the Software, and to permit persons to whom the Software is
*	furnished to do so, subject to the following conditions:
*
*	The above copyright notice and this permission notice shall be included in
*	all copies or substantial portions of the Software.
*
*	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
*	THE SOFTWARE.
*
*	@author 	Gregory Saive <greg@ubc.digital>
* @copyright  2020 ubc.digital
**/
jQuery(function($) {
	var sharing = false;

	console.log("myCRED Community Plugin by Using Blockchain Ltd");

	$( '.share-wrapper' ).on( 'click', '.addthis_button_share', function(){

		if ( sharing === true ) return false;
		sharing = true;

		var button      = $(this);
		var post_id     = button.data( 'pid' );
		var platform    = button.data( 'platform' );

		$.ajax({
			type : "POST",
			data : {
				action    : 'mycred-community-social-share',
				token     : myCREDCommunitySocialShare.token,
				postid    : post_id,
				platform  : platform
			},
			dataType   : "JSON",
			url        : myCREDCommunitySocialShare.ajaxurl,
			beforeSend : function() {

				button.attr( 'disabled', 'disabled' );

			},
			success    : function( response ) {

				if ( response.success === undefined || ( response.success === true ) )
					location.reload();

				else {

					if ( response.success ) {
						console.log("SHARE SUCCESS: ", response);
					}

					else {

						button.removeAttr( 'disabled' );

						if ( response.data != '' )
							alert( response.data );

					}

				}

				console.log( response );

			},
			complete : function(){

				sharing = false;

			}
		});
	});
});
