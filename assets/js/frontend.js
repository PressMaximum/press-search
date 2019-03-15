(function($) {
	$(document).ready(function() {
		pressSearchSetSuggestKeyword();
		pressSearchDetectClickOutsideSearchBox();
		pressSearchSearchAddSearchEngine();
		var resizeTimer, psTimeout;
		$(window).on('resize', function(e) {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function() {
				pressSearchSearchResultBoxesWidth( true );
			}, 250);
		});

		function pressSearchSearchAddSearchEngine() {
			if ( $('.ps_enable_live_search input[name="s"]').length > 0 ) {
				$('.ps_enable_live_search input[name="s"]').each( function() {
					var searchEngineSlug = 'engine_default';
					$('<input type="hidden" name="search_engine" value="'+searchEngineSlug+'" />').insertBefore( $(this) );
				});
			}
		}

		function pressSearchSetSuggestKeyword() {
			$(document).on('click', '.live-search-results .suggest-keyword', function(){
				var keywords = $(this).text();
				var target = $(this).parent().siblings('input[name="s"]');
				target.val(keywords).trigger('input');
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
					searchResult.remove();
				}
			});
		}
		
		function pressSearchGetSuggestKeyword( target ) {
			var resultBoxId = "live-search-results-" + pressSearchGetUniqueID();
			var parent = target.parent();
			var parentWidth = parent.outerWidth();
			var parentHeight = parent.outerHeight( true );

			var suggestKeywords = PRESS_SEARCH_FRONTEND_JS.suggest_keywords
			if ( '' !== suggestKeywords ) {
				parent.css({'position': 'relative'});
				parent.find('.live-search-results').remove();
				$('<div class="live-search-results" id="' + resultBoxId + '">' + suggestKeywords + '</div>').css({ 'width': parentWidth + 'px', 'top': parentHeight + 'px', 'left': 'auto' }).insertAfter( target );
				pressSearchSearchResultBoxWidth( target );
			}
		}

		function pressSearchGetLiveSearchByKeyword( target, keywords ) {
			var parent = target.parent();
			var resultBoxId = "live-search-results-" + pressSearchGetUniqueID();
			var parentWidth = parent.outerWidth();
			var parentHeight = parent.height();
			var engineSlug = 'engine_default';
			if ( parent.find('input[name="search_engine"]').length > 0 ) {
				engineSlug = parent.find('input[name="search_engine"]').val();
			}

			var processUrl = PRESS_SEARCH_FRONTEND_JS.ajaxurl;
			if ( 'undefined' !== typeof PRESS_SEARCH_FRONTEND_JS.ps_ajax_url && '' !== PRESS_SEARCH_FRONTEND_JS.ps_ajax_url ) {
				processUrl = PRESS_SEARCH_FRONTEND_JS.ps_ajax_url;
			}
			$.ajax({
				url: processUrl,
				type: "post",
				data: {
					action: "press_seach_do_live_search",
					s: keywords,
					engine: engineSlug,
					security: PRESS_SEARCH_FRONTEND_JS.security
				},
				beforeSend: function() {
					parent.css({'position': 'relative'});
					parent.find('.live-search-results').remove();
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
					$('<div class="live-search-results" id="' + resultBoxId + '">' + loading.join('') + '</div>').css({ 'width': parentWidth + 'px', 'top': parentHeight + 'px', 'left': 'auto' }).insertAfter( target );
				},
				success: function(response) {
					if ( response.data.content ) {
						$( '#' + resultBoxId ).html( response.data.content );
						pressSearchSearchResultBoxWidth( target );
					}
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
					if ( currentVal < 1 ) {
						pressSearchGetSuggestKeyword( $(this) );
					}
				});

			});

			$('.ps_enable_live_search input[name="s"]').on("input", function() {
				var $this = $(this);
				var keywords = $this.val();
				
				clearTimeout(psTimeout);
				psTimeout = setTimeout(function(){
					if ( keywords.length > 0 ) {
						pressSearchGetLiveSearchByKeyword( $this, keywords );
					} else {
						pressSearchGetSuggestKeyword( $(this) );
					}
				},300);
			});
		}

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
