(function() {
	if ($('#message-editor').length > 0){
		var quill = new Quill("#message-editor",{theme:"snow"});
		$("#mailForm").submit(function(event) {
		   $('<input />').attr('type', 'hidden')
	          .attr('name', "message")
	          .attr('value', $("#mailForm").find('#message-editor .ql-editor').html())
	          .appendTo('#mailForm');

	          var r = confirm("Are you sure you want to submit the form?");
				if (!r) {
					event.preventDefault();
					return;
				}
		    return true;
  		});
	}
})();