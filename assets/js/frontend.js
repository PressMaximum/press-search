(function($) {
	$(document).ready(function() {
		pressSearchSetSuggestKeyword();
		pressSearchDetectClickOutsideSearchBox();

		function pressSearchSetSuggestKeyword() {
			$(document).on('click', '.live-search-results .suggest-keyword', function(){
				var keywords = $(this).text();
				var target = $(this).parent().siblings('input[name="s"]');
				target.val(keywords).trigger('input');
			});
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
			var parentWidth = parent.width();
			var parentHeight = parent.height();

			var suggestKeywords = PRESS_SEARCH_FRONTEND_JS.suggest_keywords
			if ( '' !== suggestKeywords ) {
				parent.css({'position': 'relative'});
				parent.find('.live-search-results').remove();
				$('<div class="live-search-results" id="' + resultBoxId + '">' + suggestKeywords + '</div>').css({ 'width': parentWidth +'px', 'top': parentHeight + 'px', 'left': 'auto' }).insertAfter( target );
			}
		}

		function pressSearchGetLiveSearchByKeyword( target, keywords ) {
			var parent = target.parent();
			var resultBoxId = "live-search-results-" + pressSearchGetUniqueID();
			var parentWidth = parent.width();
			var parentHeight = parent.height();

			$.ajax({
				url: PRESS_SEARCH_FRONTEND_JS.ajaxurl,
				type: "post",
				data: {
					action: "press_seach_do_live_search",
					s: keywords,
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
					$('<div class="live-search-results" id="' + resultBoxId + '">' + loading.join('') + '</div>').css({ 'width': parentWidth +'px', 'top': parentHeight + 'px', 'left': 'auto' }).insertAfter( target );
				},
				success: function(response) {
					if ( response.data.content ) {
						$( '#' + resultBoxId ).html( response.data.content );
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
				if ( keywords.length > 0 ) {
					pressSearchGetLiveSearchByKeyword( $this, keywords );
				} else {
					pressSearchGetSuggestKeyword( $(this) );
				}
				
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
