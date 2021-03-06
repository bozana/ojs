/**
 * @defgroup plugins_pubIds_urn_js
 */
/**
 * @file plugins/pubIds/urn/js/URNSettingsFormHandler.js
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNSettingsFormHandler.js
 * @ingroup plugins_pubIds_urn_js
 *
 * @brief Handle the URN Settings form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.plugins.pubIds.urn =
			$.pkp.plugins.pubIds.urn ||
			{ js: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.plugins.pubIds.urn.js.URNSettingsFormHandler =
			function($form, options) {

		this.parent($form, options);

		//$('[name="urnSuffix"], [id^="enable"][id$="URN"]', $form).click(
			//	this.callbackWrapper(this.updatePatternFormElementStatus_));
		//ping our handler to set the form's initial state.
		//this.callbackWrapper(this.updatePatternFormElementStatus_());

	};
	$.pkp.classes.Helper.inherits(
			$.pkp.plugins.pubIds.urn.js.URNSettingsFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	/**
	 * Callback to replace the element's content.
	 *
	 * @private
	 *
	 * @param {HTMLElement} sourceElement The element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {Object} ui The tabs ui data.
	 */
	$.pkp.plugins.pubIds.urn.js.URNSettingsFormHandler.prototype.
			updatePatternFormElementStatus_ =
			function(sourceElement, event, ui) {
		var $element = this.getHtmlElement(), pattern, $journalContentChoices;
		if ($('[id^="urnSuffix"]').filter(':checked').val() == 'pattern') {
			$journalContentChoices = $element.find(':checkbox');
			pattern = new RegExp('enable(.*)URN');
			$journalContentChoices.each(function() {
				var patternCheckResult = pattern.exec($(this).attr('name')),
						$correspondingTextField = $element.find('[id*="' +
						patternCheckResult[1] + 'SuffixPattern"]').
						filter(':text');

				if (patternCheckResult !== null &&
						patternCheckResult[1] !== 'undefined') {
					if ($(this).is(':checked')) {
						$correspondingTextField.removeAttr('disabled');
					} else {
						$correspondingTextField.attr('disabled', 'disabled');
					}
				}
			});
		} else {
			$element.find('[id*="SuffixPattern"]').filter(':text').
					attr('disabled', 'disabled');
		}
	};
	
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
