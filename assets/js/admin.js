/*!  - v1.0.0
 * https://github.com/PressMaximum/press-search#readme
 * Copyright (c) 2019; * Licensed GPL-2.0+ */
(function($) {
	$(document).ready(function() {
		function pressSearchInitSelect2() {
			$(".custom_select2").each(function() {
				$(this).select2({
					allowClear: true
				});
			});
		}
		function pressSearchCMB2GroupDependency() {
			$("[data-conditional-id]").each(function() {
				var parentNode = $(this).closest(".cmb-row");
				var closestNode = $(this).closest(".cmb-repeatable-grouping");
				var conditionalId = $(this).attr("data-conditional-id");
				var conditionalVal = $(this).attr("data-conditional-value");
				var target = $(closestNode).find("[id*=" + conditionalId + "]");
				var targetCurrentVal = target.val();

				if (targetCurrentVal !== conditionalVal) {
					$(parentNode).hide();
				}

				target.on("change", function() {
					var targetChangedVal = $(this).val();
					if (targetChangedVal == conditionalVal) {
						$(parentNode).slideDown("fast");
					} else {
						$(parentNode).slideUp("fast");
					}
				});
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
			});
		}

		pressSearchInitSelect2();
		pressSearchCMB2GroupDependency();
		pressSearchAnimatedSelect();
		pressSearchEditableInput();
		
		$(".cmb-repeatable-group").on("cmb2_add_row", function(event, newRow) {
			pressSearchCMB2GroupDependency();
		});
	});
})(jQuery);
