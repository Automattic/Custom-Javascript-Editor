(function($){
	var WSU_JS = {
		modes: {
			'default': 'text/css',
			'less': 'text/x-less',
			'sass': 'text/x-scss'
		},
		ajaxSaveJS: function(){
            jQuery("#message").remove();
            jQuery('<div id="message" class="updated fade"><p><strong><div class="customcsspreloader"></div> Saving...</strong></p></div>').insertBefore("#jsform");
			var frm = $('#jsform');
            $.ajax({
				url: ajax_object.ajaxurl,
				type:'POST',
				data:frm.serialize()+"&update=Update&security="+ajax_object.ajax_nonce+"&action=ajax_custom_js_handle_save",
				success: function(data){ 
                    jQuery("#message").html("<p><strong>Stylesheet saved.</strong></p>");
                    jQuery("#message").delay(1000).fadeOut();
                },
				error: function(data){ 
                    jQuery("#message").html("<p><strong>There was an error saving.  Try using the 'Save Stylesheet' button.</strong></p>");
                    jQuery("#message").addClass("error");
                }
			});
		},
		init: function() {
			this.$textarea = $( '#cje-javascript' );
			this.editor = window.CodeMirror.fromTextArea( this.$textarea.get(0),{
				mode: 'javascript',
				extraKeys: {
					"Esc": function(cm) {
                        var fullscreen = cm.getOption("fullScreen");
					    cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                        if(!fullscreen)
                        {
                            jQuery("body").addClass("fullscreenmode");
                        }
                        else
                        {
                            jQuery("body").removeClass("fullscreenmode");
                        }
                    },
					"Ctrl-S": function(instance) { WSU_JS.ajaxSaveJS(); },
					"Cmd-S": function(instance) { WSU_JS.ajaxSaveJS(); }
				  },
                theme:theme,
				lineNumbers: true,
				tabSize: 4,
				indentWithTabs: true,
				lineWrapping: true
			});
			this.setEditorHeight();
            this.addListeners();
		},
		addListeners: function() {
			// nice sizing
			$( window ).on( 'resize', _.bind( _.debounce( this.setEditorHeight, 100 ), this ) );
			// keep textarea synced up
			this.editor.on( 'change', _.bind( function( editor ){
				this.$textarea.val( editor.getValue() );
			}, this ) );
		},
		setEditorHeight: function() {
			var height = $('html').height() - $( this.editor.getWrapperElement() ).offset().top;
			this.editor.setSize( null, height );
		},
		getMode: function() {
			var mode = '';
			if ( '' === mode || ! this.modes[ mode ] ) {
				mode = 'default';
			}
			return this.modes[ mode ];
		}
	};

	$( document ).ready( _.bind( WSU_JS.init, WSU_JS ) );
})(jQuery);
