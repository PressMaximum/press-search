(function($) {
	$(document).ready(function() {
		pressSearchSetSuggestKeyword();
		pressSearchDetectClickOutsideSearchBox();
		pressSearchSearchAddSearchEngine();
		var resizeTimer;
		$(window).on('resize', function(e) {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function() {
				pressSearchSearchResultBoxesWidth( true );
			}, 250);
		});

		function pressSearchSearchAddSearchEngine() {
			if ( $('.ps_enable_live_search input[name="s"]').length > 0 ) {
				var searchEngineSlug = 'engine_default';
				if ( 'engine_default' !== searchEngineSlug ) {
					$('.ps_enable_live_search input[name="s"]').each( function() {
						$('<input type="hidden" name="ps_engine" value="'+searchEngineSlug+'" />').insertBefore( $(this) );
					});
				}
			}
		}

		function pressSearchSetSuggestKeyword() {
			$(document).on('click', '.live-search-results .suggest-keyword', function(){
				var keywords = $(this).text();
				var target = $(this).parent().siblings('input[name="s"]');
				target.val(keywords).trigger('keyup');
			});
		}

		function pressSearchSearchResultBoxesWidth( resize ) {
			if ( $('.ps_enable_live_search input[name="s"]').length > 0 ) {
				$('.ps_enable_live_search input[name="s"]').each( function() {
					pressSearchSearchResultBoxWidth( $(this), resize );
				});
			}
		}

		function pressSearchSearchResultBoxWidth( target, resize ) {
			var $this = target;
			var inputWidth = $this.outerWidth();
			var searchResultBox = $this.siblings('.live-search-results');
			if ( searchResultBox.length > 0 ) {
				if ( 'undefined' !== typeof resize && resize ) {
					searchResultBox.css({'width': inputWidth + 'px'});
				}
				if ( inputWidth < 400 && searchResultBox.length > 0 ) {
					searchResultBox.addClass('box-small-width');
				} else {
					if ( searchResultBox.hasClass( 'box-small-width' ) ) {
						searchResultBox.removeClass('box-small-width');
					}
				}
			}
		}

		function pressSearchDetectClickOutsideSearchBox() {
			$(document).on('click', function(e){
				var closetsNode = $(e.target).closest('.live-search-results');
				var inputNode = $(e.target).closest('input[name="s"]');
				if ( inputNode.length < 1 && closetsNode.length < 1 ) {
					var searchResult = $('.live-search-results');
					var searchInput = searchResult.siblings('input[name="s"]');
					searchResult.hide();
				}
			});
		}
		
		function pressSearchGetSuggestKeyword( target ) {
			var resultBoxId = "live-search-results-" + pressSearchGetUniqueID();
			var parent = target.parent();
			var parentWidth = parent.outerWidth();
			var parentHeight = parent.outerHeight( true );
			var targetHeight = target.outerHeight( true );

			var resultHeight = parentHeight;
			if ( targetHeight > parentHeight ) {
				resultHeight = targetHeight;
			}
			var suggestKeywords = PRESS_SEARCH_FRONTEND_JS.suggest_keywords
			if ( '' !== suggestKeywords ) {
				parent.css({'position': 'relative', 'display': 'inline-block'});
				parent.find('.live-search-results').remove();
				$('<div class="live-search-results" id="' + resultBoxId + '">' + suggestKeywords + '</div>').css({ 'width': parentWidth + 'px', 'top': resultHeight + 'px', 'left': 'auto' }).insertAfter( target );
				pressSearchSearchResultBoxWidth( target );
			}
		}

		function pressSearchGetLiveSearchByKeyword( target, keywords ) {
			var hasBoxResult = false;
			var alreadyBoxResult = target.siblings('.live-search-results');
			if ( alreadyBoxResult.length > 0 ) {
				hasBoxResult = true;
			}
			var parent = target.parent();
			var resultBoxId = "live-search-results-" + pressSearchGetUniqueID();
			var parentWidth = parent.outerWidth();
			var parentHeight = parent.outerHeight(true);
			var targetHeight = target.outerHeight(true);
			var resultBoxHeight = parentHeight;
			if ( targetHeight > parentHeight ) {
				resultBoxHeight = targetHeight;
			}
			var ajaxData = {
				action: "press_seach_do_live_search",
				s: keywords
			};
			var engineSlug = 'engine_default';
			if ( parent.find('input[name="ps_engine"]').length > 0 ) {
				engineSlug = parent.find('input[name="ps_engine"]').val();
				ajaxData['engine'] = engineSlug;
			}

			var processUrl = PRESS_SEARCH_FRONTEND_JS.ajaxurl;
			if ( 'undefined' !== typeof PRESS_SEARCH_FRONTEND_JS.ps_ajax_url && '' !== PRESS_SEARCH_FRONTEND_JS.ps_ajax_url ) {
				processUrl = PRESS_SEARCH_FRONTEND_JS.ps_ajax_url;
			}
			var start = new Date().getTime();

			if ( window.ps_xhr ) {
				window.ps_xhr.abort();
				window.ps_xhr = false;
			}

			window.ps_xhr =	$.ajax({
				url: processUrl,
				type: "GET",
				cache: true,
				dataType: "json",
				data: ajaxData,
				beforeSend: function() {
					parent.css({'position': 'relative', 'display': 'inline-block'});
					var loading = [
						'<div class="ps-ajax-loading">',
							'<div class="ribble">',
								'<div class="blobb square fast"></div>',
								'<div class="blobb square fast"></div>',
								'<div class="blobb square fast"></div>',
								'<div class="blobb square fast"></div>',
							'</div>',
						'</div>'
					];
					if ( ! hasBoxResult ) {
						parent.find('.live-search-results').remove();
						$('<div class="live-search-results" id="' + resultBoxId + '">' + loading.join('') + '</div>').css({ 'width': parentWidth + 'px', 'top': resultBoxHeight + 'px', 'left': 'auto' }).insertAfter( target );
					}
				},
				success: function(response) {
					console.log('response: ', response);
					if ( response.data.content ) {
						if ( ! hasBoxResult ) {
							alreadyBoxResult = $( '#' + resultBoxId );
						}
						alreadyBoxResult.html( response.data.content );
						alreadyBoxResult.show();
						pressSearchSearchResultBoxWidth( target );
					}
					var end = new Date().getTime();
					console.log('seconds passed:', (end - start)/1000);
				}
			}); 
		}

		$('input[name="s"]').each( function(){
			var $this = $(this);
			$this.attr('autocomplete', 'off');//nope
			$this.attr('autocorrect', 'off');
			$this.attr('autocapitalize', 'none');
			$this.attr('spellcheck', false);
		});

		if ($('.ps_enable_live_search input[name="s"]').length > 0) {
			$('.ps_enable_live_search input[name="s"]').each( function(){
				var $this = $(this);
				$this.focusin(function(){
					var currentVal = $(this).val();
					if ( $(this).siblings( '.live-search-results' ).length > 0 && $(this).siblings( '.live-search-results' ).find( '.live-search-item' ).length > 0 ) {
						$(this).siblings( '.live-search-results' ).slideDown( 'fast' );
					} else if ( currentVal < 1 ) {
						pressSearchGetSuggestKeyword( $(this) );
					}
				});

			});

			
			var currentFocus = -1;
			$('.ps_enable_live_search input[name="s"]').on("keyup", function( e ) {
				
				var $this = $(this);
				var resultBox = $this.siblings( '.live-search-results' );
				var resultBoxID = $this.siblings( '.live-search-results' ).attr('id');
				var keywords = $this.val();

				var checkValidFocusItem = function( allItems ) {
					if ( currentFocus > allItems.length ) {
						currentFocus = 0;
					}
					if ( currentFocus < 0 ) {
						currentFocus = allItems.length - 1;
					}
				};

				if ( keywords.length > 0 ) {
					if ( 40 == e.which || 38 == e.which || 13 == e.which ) {
						var liveSearchItems = resultBox.find('.live-search-item');
						liveSearchItems.eq(0).addClass('hightlight');

						if ( 40 == e.keyCode ) {
							currentFocus++;
							checkValidFocusItem( liveSearchItems );
						} else if ( 38 == e.keyCode ) {
							currentFocus--;
							checkValidFocusItem( liveSearchItems );
						} else if ( 13 == e.keyCode ) {
							e.preventDefault();
							var aTag = liveSearchItems.eq(currentFocus).find('.item-title-link');
							if ( aTag.length > 0 ) {
								var redirectURL = aTag.attr('href');
								if ( '' !== redirectURL ) {
									window.location.href = redirectURL;
								}
							}
						}
						var focusItems = liveSearchItems.eq(currentFocus);
						liveSearchItems.removeClass('hightlight');
						focusItems.addClass('hightlight');
						resultBox.scrollToElementInScrollable( liveSearchItems[currentFocus] );
							
					} else {
						pressSearchGetLiveSearchByKeyword( $this, keywords );
					}
				} else {
					pressSearchGetSuggestKeyword( $(this) );
				}
			});
		}

		$.fn.scrollToElementInScrollable = function(elem) {
			var parentscrollTop = $(this).scrollTop();
			var parentOffset = $(this).offset();
			var childOffset = $(elem).offset();
			var parentOffsetTop = parentOffset.top;
			if ( 'undefined' !== typeof childOffset && 'undefined' !== typeof childOffset.top ) {
				$(this).scrollTop( parentscrollTop - parentOffsetTop + childOffset.top );
			}
			return this; 
		};

		function pressSearchGetUniqueID() {
			function chr4() {
				return Math.random()
					.toString(16)
					.slice(-4);
			}
			let date = new Date();
			return chr4() + chr4() + "_" + date.getTime();
		}
	});
})(jQuery);
