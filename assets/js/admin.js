/*!  - v1.0.0
 * https://github.com/PressMaximum/press-search#readme
 * Copyright (c) 2019; * Licensed GPL-2.0+ */
jQuery(document).ready(function( $ ) {
	function pressSearchInitSelect2() {
		$(".custom_select2").each(function() {
			$(this).select2({
				allowClear: true
			});
		});
	}

	function pressSearchMultipleDependency( dom ) {
		var parentNode = dom.closest(".cmb-row");
		var conditional = dom.attr("data-multi-conditional"); //conditionalId
		conditional = conditional.split('|');
		
		var closestNode = dom.closest(".cmb-repeatable-grouping").length > 0 ? dom.closest(".cmb-repeatable-grouping") : dom.closest(".cmb-field-list");
		var numberConditon = conditional.length;
		
		var countTrue = 0;
		if ( Array.isArray(conditional) && numberConditon > 0) {
			for( var i=0; i<numberConditon; i++) {
				var condition = conditional[i];
				var explodeCondition = condition.split('=');
				var conditionalId = ( 'undefined' !== typeof explodeCondition[0] ) ? explodeCondition[0] : '';
				var conditionalValue = ( 'undefined' !== typeof explodeCondition[1] ) ? explodeCondition[1] : '';

				var target = $(closestNode).find("[id*=" + conditionalId + "]");
				var targetCurrentVal = target.val();
				if ( target.is('input[type="checkbox"]') || target.is('input[type="radio"]') ) {
					if ( target.prop( "checked" ) ) {
						countTrue++;
					}
				} else {
					if ( targetCurrentVal == conditionalValue ) {
						countTrue++;
					}
				}

				if ( countTrue == numberConditon ) {
					parentNode.stop().slideDown("fast");
				} else {
					parentNode.stop().slideUp("fast");
				}

				target.on('change', function(){
					pressSearchMultipleDependency( dom );
				});
			}
		}
	}

	function pressSearchSingleDependency(dom) {
		var parentNode = dom.closest(".cmb-row");
		var closestNode = dom.closest(".cmb-repeatable-grouping").length > 0 ? dom.closest(".cmb-repeatable-grouping") : dom.closest(".cmb-field-list");
		var conditionalId = dom.attr("data-conditional-id");
		var conditionalVal = dom.attr("data-conditional-value");
		var target = $(closestNode).find("[id*=" + conditionalId + "]");
		var isCheckableInput = false;
		var isCheckableInputChecked = false;
		if ( target.is('input[type="checkbox"]') || target.is('input[type="radio"]') ) {
			isCheckableInput = true;
			if ( target.prop( "checked" ) ) {
				isCheckableInputChecked = true;
			}
		}
		var targetCurrentVal = target.val();

		if ( isCheckableInput ) {
			if ( ! isCheckableInputChecked ) {
				$(parentNode).hide();
			}
		} else if (targetCurrentVal !== conditionalVal) {
			$(parentNode).hide();
		}

		target.on("change", function() {
			var targetChangedVal = $(this).val();
			if ( target.is('input[type="checkbox"]') || target.is('input[type="radio"]') ) {
				if ( target.prop( "checked" ) ) {
					$(parentNode).slideDown("fast");
				} else {
					$(parentNode).slideUp("fast");
				}
			} else {
				if (targetChangedVal == conditionalVal ) {
					$(parentNode).slideDown("fast");
				} else {
					$(parentNode).slideUp("fast");
				}
			}
		});
	}
	function pressSearchCMB2GroupDependency() {
		$("[data-multi-conditional]").each(function() {
			pressSearchMultipleDependency( $(this) );
		});
		$("[data-conditional-id]").each(function() {
			pressSearchSingleDependency( $(this) );
		});
	}

	function pressSearchAnimatedSelect() {
		$(document).on('click', '.animate-selected-field .select-add-val', function(){
			var closestNode = $(this).closest('.animate-selected-field');
			var singleSelect = closestNode.find('.single-select-box');
			var multipleSelect = closestNode.find('.animate_select');
			var multipleSelectVal = multipleSelect.val();
			var selectedValueNode = closestNode.find('.selected-values');
			var singleSelectVal = singleSelect.val();
			var singleSelectedOption = singleSelect.find('option:selected');
			var singleSelectedText = singleSelectedOption.text();

			if ( null == multipleSelectVal || ! ( Array.isArray( multipleSelectVal ) && multipleSelectVal.length > 0 ) ) {
				multipleSelectVal = [];
			}

			if ( '' !== singleSelectVal ) {
				var displayItem = '<span class="selected-value-item"  data-option_value="' +singleSelectVal+ '">' +singleSelectedText+ '<span class="dashicons dashicons-no-alt remove-val"></span></span>';
				selectedValueNode.append(displayItem);
				singleSelectedOption.remove();
				singleSelect.val('');
				multipleSelectVal.push(singleSelectVal);
				multipleSelect.val(multipleSelectVal);
			}
		});

		$(document).on('click', '.animate-selected-field .remove-val', function(){
			var closestNode = $(this).closest('.animate-selected-field');
			var parentNode = $(this).parent();
			var parentOptionVal = parentNode.attr('data-option_value');
			var parentOptionText = parentNode.text();
			var singleSelectNode = closestNode.find('.single-select-box');
			var multipleSelect = closestNode.find('.animate_select');
			var multipleSelectVal = multipleSelect.val();

			parentNode.remove();
			var createOptionNode = $('<option>', { value: parentOptionVal, text: parentOptionText } );
			singleSelectNode.append(createOptionNode);

			if ( Array.isArray( multipleSelectVal ) && multipleSelectVal.length > 0 ) {
				multipleSelectVal.splice($.inArray(parentOptionVal, multipleSelectVal), 1);
				if ( multipleSelectVal.length > 0 ) {
					multipleSelect.val(multipleSelectVal);
				} else {
					multipleSelect.val('');
					return false;
				}
			}
		});

		// Reset input when in group duplicated
		$(".cmb-repeatable-group").on("cmb2_add_row", function(event, newRow) {
			if ( $(newRow).find('.animate-selected-field').length > 0 ) {
				$(newRow).find('.animate-selected-field').each(function(){
					var $thatGroup = $(this);
					$thatGroup.find('.selected-values').each(function(){
						$(this).html('');
					});
					var selectMultiNode = $thatGroup.find('.animate_select');
					var selectSingleNode = $thatGroup.find('.single-select-box');
					var selectMultiOptions = selectMultiNode.html();
					var selectSingleOptionNone = selectSingleNode.find('option[value=""]');
					selectSingleNode.html(selectSingleOptionNone);
					if( null !== selectMultiOptions ) {
						selectSingleNode.html(selectMultiOptions);
						if( null !== selectSingleOptionNone ) {
							selectSingleNode.prepend(selectSingleOptionNone);
						}
					}
				});
			}
		});
	}

	function pressSearchEditableInput() {
		$(document).on('click', '.field-editable-input .do-an-action', function(){
			var that = $(this);
			var closest = that.closest( '.field-editable-input' );
			var inputField = closest.find('.custom_editable_input');
			var titleNode = closest.find('.display-title');
			if ( that.hasClass('action-edit') ) { // Action edit.
				that.removeClass('action-edit').addClass('action-done');
				inputField.attr('type', 'input').focus();
				titleNode.hide();
				that.html('').append('<span class="dashicons dashicons-editor-spellcheck action-done"></span>');
			} else { // Action done.
				that.removeClass('action-done').addClass('action-edit');
				inputField.attr('type', 'hidden');
				titleNode.show();
				that.html('').append('<span class="dashicons dashicons-edit action-edit"></span>');

				var inputVal = inputField.val();
				if ( '' == inputVal ) {
					inputVal = 'Engine name';
					inputField.val(inputVal);
				}
				titleNode.text(inputVal);
			}
		});

		$(".cmb-repeatable-group").on("cmb2_add_row", function(event, newRow) {
			var groupTitle = $(newRow).find('.cmbhandle-title');
			if( groupTitle.length > 0 ) { 
				if ( $(newRow).find( '.field-editable-input' ).length > 0 ) {
					$(newRow).find( '.field-editable-input' ).each(function() {
						$(this).find( '.display-title' ).text( groupTitle.text() );
						$(this).find( '.custom_editable_input' ).val( groupTitle.text() );
					});
				}
			}

			if ( $(newRow).find('.unique_engine_slug').length > 0 ) {
				var uniqueID = pressSearchUniqueID();
				var slugEngine = 'engine_' + uniqueID;
				var engineSlugInput = $(newRow).find('.unique_engine_slug');
				engineSlugInput.val(slugEngine);
			}
		});
	}

	function pressSearchUniqueID(){
		function chr4() {
			return Math.random()
				.toString(16)
				.slice(-4);
		}
		let date = new Date();
		return (
			chr4() +
			chr4() + 
			"_" + date.getTime()
		);
	}

	function pressSearchUpdateIndexProgress() {
		var statisticWrapper = $(document).find( '.index-progress-wrap');
		var isAjaxIndexing = $(document).find('.ps-ajax-loading').length;
		if ( statisticWrapper.length > 0 && ! isAjaxIndexing ) {
			$.ajax({
				url : Press_Search_Js.ajaxurl,
				type : 'GET',
				data : {
					action : 'get_indexing_progress',
					security : Press_Search_Js.security
				},
				success : function( response ) {
					if ( 'undefined' !== typeof response.data.progress_report && '' !== response.data.progress_report ) {
						statisticWrapper.html(response.data.progress_report);
					}
				}
			});
			setTimeout( pressSearchUpdateIndexProgress, 30 * 1000 );
		}
	}

	function pressSearchSendAjaxDataIndexing(dom, ajax_action) {
		///var loadingEl = dom.find( '.ps-ajax-loading updating-message' );
		$.ajax({
			url : Press_Search_Js.ajaxurl,
			type : 'post',
			data : {
				action : ajax_action,
				security : Press_Search_Js.security
			},
			beforeSend: function() {
				dom.addClass( 'updating-message disabled' );
				// if ( loadingEl.length < 1 ) {
				// 	dom.append('<span class="dashicons dashicons-update ps-ajax-loading"></span>');
				// }
			},
			success : function( response ) {
				console.log('response: ', response);
				if ( 'undefined' !== typeof response && 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.progress_report && '' !== response.data.progress_report ) {
					var statisticWrapper = $(document).find( '.index-progress-wrap');
					if ( statisticWrapper.length > 0 ) {
						statisticWrapper.html(response.data.progress_report);
					}
				}
				if ( response.data.recall_ajax ) {
					pressSearchSendAjaxDataIndexing(dom, ajax_action);
				} else {
					// loadingEl.remove();
					dom.removeClass( 'updating-message disabled' );
					if ( 'build_unindexed_data_ajax' === ajax_action ) {
						dom.addClass('prevent-click');
					}
				}
			}
		});
	}

	function pressSearchAutoAjaxIndexing() {
		var bodyTag = $('body');
		if ( bodyTag.hasClass('current_tab_engines') && bodyTag.is(':not(.prevent_ajax_background_indexing)') ) {
			var dom = bodyTag.find('.index-progess-buttons #build_data_unindexed');
			if ( dom.length > 0 && ! dom.hasClass( 'prevent-click' ) ) {
				pressSearchSendAjaxDataIndexing( dom, 'build_unindexed_data_ajax');
			}
		}
	}

	function pressSearchAjaxBuildIndexing() {
		$(document).on('click', '.index-progess-buttons #build_data_unindexed', function(){
			var dom = $(this);
			if ( ! dom.hasClass( 'prevent-click' ) ) {
				pressSearchSendAjaxDataIndexing(dom, 'build_unindexed_data_ajax');
			}
		});

		$(document).on('click', '.index-progess-buttons #build_data_index', function(){
			var dom = $(this);
			if ( ! dom.hasClass( 'prevent-click' ) ) {
				pressSearchAjaxResetReindexCount();
				pressSearchSendAjaxDataIndexing(dom, 'build_the_index_data_ajax');
			}
			
		});
	}

	function pressSearchAjaxResetReindexCount() {
		$.ajax({
			url : Press_Search_Js.ajaxurl,
			type : 'GET',
			data : {
				action : 'reset_reindex_count',
				security : Press_Search_Js.security
			},
			success : function( response ) {
				console.log('response: ', response);
			}
		});
	}

	function pressSearchMaybeSendAjaxReportRequest() {
		var isPreventReport = $(document).find('body').hasClass('engines_prevent_ajax_report');
		if ( ! isPreventReport ) {
			setTimeout( pressSearchUpdateIndexProgress, 30 * 1000 );
		}
	}

	function pressSearchCMB2GroupInitDependency() {
		$(".cmb-repeatable-group").on("cmb2_add_row", function(event, newRow) {
			pressSearchCMB2GroupDependency();
		});
	}

	function pressSeachChooseReportSearchEngine() {
		$(document).on('change', '#report-search-engine', function(){
			var current = $(this).find('option:selected');
			var src = current.attr('data-src');
			window.location.href = src;
		});
	}

	function pressSearchReportDatePicker() {
		$("#report-date-from").datepicker({
			numberOfMonths: 1,
			maxDate : "-1D",
			minDate : "-60D",
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 2,
			onSelect: function (selected) {
				var date = new Date(selected);
				date.setDate(date.getDate() + 1);
				$("#report-date-to").datepicker("option", "minDate", date);
			}
		});
		$("#report-date-to").datepicker({
			numberOfMonths: 1,
			maxDate : "+0D",
			minDate : "-60D",
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 2,
			onSelect: function (selected) {
				var date = new Date(selected);
				date.setDate(date.getDate() - 1);
				$("#report-date-from").datepicker("option", "maxDate", date);
			}
		});
		$(document).on('click', '#report-custom-date', function(){
			var from = $("#report-date-from").val();
			var to = $("#report-date-to").val();
			var date_range = from + 'to' + to;
			var url = $(this).attr('data-src');
			url = url.replace('custom_date', date_range);
			window.location.href = url;
		});
	}


	function pressSearchRenderReportChart() {
		if ( null !== document.getElementById("detail-report-chart") ) {
			var context = document.getElementById("detail-report-chart").getContext('2d');
			var reportChart = new Chart(context, {
				type: 'bar',
				data: Press_Search_Js.chart_reports,
				options: {
					responsive: true,
					title: {
						display: true,
						text: Press_Search_Js.chart_title
					},
					tooltips: {
						mode: 'index',
						intersect: false,
					},
					hover: {
						mode: 'nearest',
						intersect: true
					},
				}
			});
		}
	}

	pressSearchInitSelect2();
	pressSearchCMB2GroupDependency();
	pressSearchAnimatedSelect();
	pressSearchEditableInput();
	pressSearchAjaxBuildIndexing();
	pressSearchMaybeSendAjaxReportRequest();
	pressSearchCMB2GroupInitDependency();
	pressSearchAutoAjaxIndexing();
	pressSeachChooseReportSearchEngine();
	pressSearchReportDatePicker();
	pressSearchRenderReportChart();
});