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

		var boxPostionTop, boxPositionLeft, isBoxInViewport;
		$(window).on('scroll', function(e) {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function() {
				if ( $('.live-search-results.box-showing').length > 0 ) {
					$('.live-search-results.box-showing').each( function() {
						var targetID = $(this).attr('id');
						var target = $('[data-ps_uniqid="'+targetID+'"]');
						pressSeachReCalcResultBoxPosition( target );
						
					});
				}
			}, 0);
		});

		function pressSearchSearchAddSearchEngine() {
			if ( $('.ps_enable_live_search input[name="s"]').length > 0 ) {
				var searchEngineSlug = Press_Search_Frontend_Js.form_search_engine;
				if ( 'engine_default' !== searchEngineSlug ) {
					$('.ps_enable_live_search input[name="s"]').each( function() {
						$('<input type="hidden" name="ps_engine" value="'+searchEngineSlug+'" />').insertBefore( $(this) );
					});
				}
			}
		}

		function pressSearchSetSuggestKeyword() {
			$(document).on('click', '.live-search-results .suggest-keyword', function(){
				var closest = $(this).closest( '.live-search-results' );
				var inputId = closest.attr('id');
				var keywords = $(this).text();
				var target = $('input[name="s"][data-ps_uniqid="'+inputId+'"]');
				target.val(keywords).trigger('keyup').focus();
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
			var searchResultBox = $( '#' +$this.attr( 'data-ps_uniqid' ) );
			if ( searchResultBox.length > 0 ) {
				if ( 'undefined' !== typeof resize && resize ) {
					searchResultBox.css({'width': inputWidth + 'px'});
				}
				if ( inputWidth < 400 && searchResultBox.length > 0 ) {
					searchResultBox.addClass('box-small-width');
					if ( window.innerWidth <= 320 ) {
						var formTag = $this.closest('form');
						var formWidth = formTag.outerWidth();
						if ( inputWidth < formWidth ) {
							searchResultBox.css({'width': formWidth + 'px'});
						}
					}
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
					searchResult.removeClass('box-showing').hide();
				}
			});
		}
		
		function pressSearchGetSuggestKeyword( target ) {
			var resultBoxId = target.attr('data-ps_uniqid');
			var suggestKeywords = Press_Search_Frontend_Js.suggest_keywords
			if ( '' !== suggestKeywords ) {
				var boxResult = $('#'+resultBoxId);
				boxResult.find('.ajax-result-content').html( suggestKeywords );
				boxResult.addClass('box-showing').slideDown('fast');
				if ( suggestKeywords.indexOf('group-posttype') != -1 ) {
					boxResult.find('.ajax-box-arrow.box-up-arrow').addClass('accent-bg-color');
				}
				pressSearchSearchResultBoxWidth( target );
				if ( 'undefined' !== typeof Press_Search_Frontend_Js.box_result_flexible_position && 'yes' == Press_Search_Frontend_Js.box_result_flexible_position ) {
					pressSeachReCalcResultBoxPosition( target );
				}
			}
		}

		function pressSearchSendInsertLogs( loggingArgs ) {
			var processUrl = Press_Search_Frontend_Js.ajaxurl;
			if ( 'undefined' !== typeof Press_Search_Frontend_Js.ps_ajax_url && '' !== Press_Search_Frontend_Js.ps_ajax_url ) {
				processUrl = Press_Search_Frontend_Js.ps_ajax_url;
			}
			$.ajax({
				url: processUrl,
				type: "GET",
				cache: true,
				dataType: "json",
				data: {
					action: 'press_search_ajax_insert_log',
					logging_args: loggingArgs,
				},
				success: function(response) {
					
				}
			});
		}

		function pressSeachReCalcResultBoxPosition( target ) {
			
			var $this = target;
			var elPosition = 'absolute';
			var uniqid = $this.attr( 'data-ps_uniqid' );
			var targetOffset = $this.offset();
			var targetOffsetLeft = targetOffset.left;
			var targetOuterHeight = $this.outerHeight();
			var targetOffsetTop = targetOffset.top + targetOuterHeight + 5; // Plus 5px offset.
			var targetWidth = $this.outerWidth();
			var inViewport = $this.isInViewport();
			var zIndex = 0;
			if ( inViewport ) {
				zIndex = 9999999;
			}
			
			// Calc flexible position.
			if ( 'undefined' !== typeof Press_Search_Frontend_Js.box_result_flexible_position && 'yes' == Press_Search_Frontend_Js.box_result_flexible_position ) {
				var boxResultWrap = $('#' + uniqid);
				var boxResult = $('.ajax-result-content', boxResultWrap);
				var boxResultHeight = boxResult.height();
				var targetTop = targetOffset.top - $(window).scrollTop();

				if ( ( targetTop + boxResultHeight + targetOuterHeight + 15 + 5 ) >= $( window ).height() ) {
					targetOffsetTop = targetOffset.top - ( boxResult.height() + 15 + 5 ); // 15 is box arrow height, 5 is plus 5px offset.
					boxResultWrap.addClass('reverse-position');
					$('.ajax-box-arrow.box-up-arrow', boxResultWrap).addClass( 'ps-display-none' );
					$('.ajax-box-arrow.box-down-arrow', boxResultWrap).removeClass( 'ps-display-none' );
				} else {
					boxResultWrap.removeClass('reverse-position');
					$('.ajax-box-arrow.box-up-arrow', boxResultWrap).removeClass( 'ps-display-none' );
					$('.ajax-box-arrow.box-down-arrow', boxResultWrap).addClass( 'ps-display-none' );
				}
				$('#'+ uniqid).css({
					'position': elPosition, 
					'width': targetWidth + 'px', 
					'top': targetOffsetTop + 'px', 
					'left': targetOffsetLeft + 'px', 
					'z-index': zIndex
				});
				return true;
			}

			
			if ( boxPostionTop !== targetOffsetTop || boxPositionLeft !== targetOffsetLeft || isBoxInViewport !== inViewport ) {
				var targetParents = target.parents();
				targetParents.each( function(){
					var elPos = $(this).css('position');
					if ( 'fixed' == elPos ) {
						elPosition = 'fixed';
						var targetPos = target.position();
						targetOffsetTop = targetPos.top + targetOuterHeight;
						boxPositionLeft = targetPos.left;
						return false;
					}
				});

				$('#'+ uniqid).css({
					'position': elPosition, 
					'width': targetWidth + 'px', 
					'top': targetOffsetTop + 'px', 
					'left': targetOffsetLeft + 'px', 
					'z-index': zIndex
				});
				boxPostionTop = targetOffsetTop;
				boxPositionLeft = targetOffsetLeft;
				isBoxInViewport = inViewport;
			}
		}

		function pressSearchGetLiveSearchByKeyword( target, keywords ) {
			var boxResultId = target.attr('data-ps_uniqid');
			var alreadyBoxResult = $('#' + boxResultId);
			var parent = target.parent();
			var ajaxData = {
				action: "press_seach_do_live_search",
				s: keywords
			};
			var engineSlug = 'engine_default';
			if ( parent.find('input[name="ps_engine"]').length > 0 ) {
				engineSlug = parent.find('input[name="ps_engine"]').val();
				ajaxData['ps_engine'] = engineSlug;
			}
			var processUrl = Press_Search_Frontend_Js.ajaxurl;
			if ( 'undefined' !== typeof Press_Search_Frontend_Js.ps_ajax_url && '' !== Press_Search_Frontend_Js.ps_ajax_url ) {
				processUrl = Press_Search_Frontend_Js.ps_ajax_url;
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
					var loading = pressSearchRenderLoadingItem();
					alreadyBoxResult.addClass('box-showing').show().find('.ajax-result-content').html( loading );
					if ( 'undefined' !== typeof Press_Search_Frontend_Js.box_result_flexible_position && 'yes' == Press_Search_Frontend_Js.box_result_flexible_position ) {
						pressSeachReCalcResultBoxPosition( target );
					}
				},
				success: function(response) {
					if ( 'undefined' !== typeof response.data ) {
						if ( 'undefined' !== typeof response.data.content ) {
							var htmlContent = response.data.content;
							if ( htmlContent.indexOf('group-posttype') != -1 ) {
								alreadyBoxResult.find('.ajax-box-arrow.box-up-arrow').addClass('accent-bg-color');
							} else {
								alreadyBoxResult.find('.ajax-box-arrow.box-up-arrow').removeClass('accent-bg-color');
							}
							alreadyBoxResult.find('.ajax-result-content').html( htmlContent );
							alreadyBoxResult.addClass('box-showing').show();
							pressSearchSearchResultBoxWidth( target );
							if ( 'undefined' !== typeof Press_Search_Frontend_Js.box_result_flexible_position && 'yes' == Press_Search_Frontend_Js.box_result_flexible_position ) {
								pressSeachReCalcResultBoxPosition( target );
							}
						}
						if ( 'undefined' !== typeof response.data.logging_args ) {
							pressSearchSendInsertLogs( response.data.logging_args );
						}
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
			$('.ps_enable_live_search input[name="s"]').each( function() {
				var $this = $(this);
				var uniqid = 'live-search-' + pressSearchGetUniqueID();
				$this.attr( 'data-ps_uniqid', uniqid );
				var resultBox = $('<div class="live-search-results" id="' + uniqid + '"><div class="ajax-box-arrow box-up-arrow"></div><div class="ajax-result-content"></div><div class="ajax-box-arrow box-down-arrow ps-display-none"></div></div>').css({ 'position': 'absolute', 'display': 'none' });
				$('body').append( resultBox );
			});

			$(document).on('focusin', '.ps_enable_live_search input[name="s"]', function(){
				var currentVal = $(this).val();
				var boxResultId = $(this).attr('data-ps_uniqid');
				$('.live-search-results').removeClass('box-showing').hide();
				if ( $('#'+boxResultId).length > 0 && $('#'+boxResultId).find( '.live-search-item' ).length > 0 ) {
					$('#'+boxResultId).addClass('box-showing').slideDown( 'fast' );
				} else if ( currentVal < 1 ) {
					pressSearchGetSuggestKeyword( $(this) );
				}

				// Calc box position after has content.
				pressSeachReCalcResultBoxPosition( $(this) );
				$(this).one("animationend webkitAnimationEnd oAnimationEnd MSAnimationEnd, transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){ 
					pressSeachReCalcResultBoxPosition( $(this) );
				});
			});

			
			var currentFocus = -1;
			var ajaxTimer;
			$(document).on('keyup', '.ps_enable_live_search input[name="s"]', function( e ) {
				var $this = $(this);
				var boxId = $this.attr('data-ps_uniqid');
				var resultBox = $( '#' + boxId ).find('.ajax-result-content');
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
						clearTimeout( ajaxTimer );
						var minChar = Press_Search_Frontend_Js.ajax_min_char;
						var delayTime = Press_Search_Frontend_Js.ajax_delay_time;
						if ( keywords.length >= minChar ) {
							ajaxTimer = setTimeout( function() {
								pressSearchGetLiveSearchByKeyword( $this, keywords );
							}, delayTime );
						}
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

		$.fn.isInViewport = function() {
			var outerHeight = $(this).outerHeight();
			var elementTop = $(this).offset().top - outerHeight;
			var elementBottom = elementTop + outerHeight;
		
			var viewportTop = $(window).scrollTop();
			var viewportBottom = viewportTop + $(window).height();
		
			return elementBottom > viewportTop && elementTop < viewportBottom;
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

		function pressSearchRenderLoadingItem() {
			var loadingItem = [
				'<div class="ps-ajax-loading-item">',
					'<div class="ph-item">',
						'<div class="ph-col-4 col-loading-picture">',
							'<div class="ph-picture loading-picture"></div>',
						'</div>',
						'<div style="justify-content: center;">',
							'<div class="ph-row" style="justify-content: center;">',
								'<div class="ph-col-6"></div>',
								'<div class="ph-col-6 empty"></div>',
								'<div class="ph-col-8"></div>',
								'<div class="ph-col-4 empty"></div>',
								'<div class="ph-col-12"></div>',
							'</div>',
						'</div>',
					'</div>',
					'<div class="ph-item">',
						'<div class="ph-col-4 col-loading-picture">',
							'<div class="ph-picture loading-picture"></div>',
						'</div>',
						'<div style="justify-content: center;">',
							'<div class="ph-row" style="justify-content: center;">',
								'<div class="ph-col-6"></div>',
								'<div class="ph-col-6 empty"></div>',
								'<div class="ph-col-8"></div>',
								'<div class="ph-col-4 empty"></div>',
								'<div class="ph-col-12"></div>',
							'</div>',
						'</div>',
					'</div>',
					'<div class="ph-item">',
						'<div class="ph-col-4 col-loading-picture">',
							'<div class="ph-picture loading-picture"></div>',
						'</div>',
						'<div style="justify-content: center;">',
							'<div class="ph-row" style="justify-content: center;">',
								'<div class="ph-col-6"></div>',
								'<div class="ph-col-6 empty"></div>',
								'<div class="ph-col-8"></div>',
								'<div class="ph-col-4 empty"></div>',
								'<div class="ph-col-12"></div>',
							'</div>',
						'</div>',
					'</div>',
				'</div>'
			];
			return loadingItem.join('');
		}

		function pressSearchCubeLoading() {
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
			return loading.join('');
		}
	});
})(jQuery);
