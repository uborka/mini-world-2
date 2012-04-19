$.fn.defaultInputText = function(options){
	// default configuration properties
	var defaults = {
		text   : 'default input', //default text
		f_color: '#333',          //focus color
		b_color: '#aaa'           //blur color

	};
	//merge
	var options = $.extend(defaults, options);  

	//get element
	var elements = this;

	//clear
	if(elements.val() == ""){
		elements.css("color",options.b_color);
		elements.val(options.text);
	}

	//focus
	this.focus(function(event){
		if(elements.val() == options.text){
			elements.css("color",options.f_color);
			elements.val("");
		}
	});

	//blur
	this.blur(function(event){
		if(elements.val() == ""){
			elements.css("color",options.b_color);
			elements.val(options.text);
		}
	});
}
