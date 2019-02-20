/*!  - v1.0.0
 * https://github.com/PressMaximum/press-search#readme
 * Copyright (c) 2019; * Licensed GPL-2.0+ */
(function($){
	$(document).ready(function(){
		function pressSearchInitSelect2() {
			$('.custom_select2').each(function () {
				$(this).select2({
					allowClear: true
				});
			});
		}
		pressSearchInitSelect2();
	});
})(jQuery);