jQuery(document).ready(function($) {
	var f0 = $('textarea#f0').val().split("\n"),
	f1 = $('textarea#f1').val().split("\n"),
	f2 = $('textarea#f2').val().split("\n"),
	f3 = diff3_dig_in(f1, f0, f2);

	$('#merge').click(function() {
		if (f1 && f2 && f3) {
			CJECodeMirror.setValue(f3);
			return false;
		}
	});
});

function diff3_dig_in(f1, f0, f2) {
	var merger = Diff.diff3_merge(f1, f0, f2, false);
	var lines = [];
	for (var i = 0; i < merger.length; i++) {
		var item = merger[i];
		if (item.ok) {
			lines = lines.concat(item.ok);
		} else {
			var c = Diff.diff_comm(item.conflict.a, item.conflict.b);
			for (var j = 0; j < c.length; j++) {
				var inner = c[j];
				if (inner.common) {
					lines = lines.concat(inner.common);
				} else {
					lines = lines.concat(["\n<<<<<<<<< Local\n"], inner.file1,
						["\n=========\n"], inner.file2,
						["\n>>>>>>>>> Remote\n"]);
				}
			}
		}
	}
	return lines.join("\n");
}
