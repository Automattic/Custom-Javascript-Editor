jQuery(document).ready(function($) {
	var $errorsdiv = $('#jslint_errors'),
	    $errors = $errorsdiv.find('.errors'),

	    // Run the linter
	    input = $('textarea').val(),
	    options = {'predef': ['jQuery']},
	    result = JSLINT(input, options),

	    // Get HTML output of errors
	    data = JSLINT.data(),
	    errors = JSLINT.error_report(data);

	// result is true if there are no errors
	if ( result ) {
		$errorsdiv.hide();
	} else {
		$errors.html(errors);
		$errorsdiv.show();
	}
});