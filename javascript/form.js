/**
 * General Form Helper Javascript Class
 *
 * Copyright 2008, Mark Story.
 * 823 millwood rd. 
 * toronto, ontario M4G 1W3
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008, Mark Story.
 * @link http://mark-story.com
 * @version 
 * @author Mark Story <mark@mark-story.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
var FormHelper = new Class({
	Implements : Options,
	
	options : {
		'selector' : 'input[type=text], input[type=radio], input[type=password], textarea, select',
		'mode' : 'focus',
		'helpSelector' : '.help-text'
	},
	elements : [],
	
	initialize : function(options) {
		this.setOptions(options);
		
		$$(this.options.selector).each(function(item){
			var parent = item.getParent();
			this.elements.push(parent);
			if (this.options.mode == 'enter') {
				parent.addEvent('mouseenter', this.show.bindWithEvent(parent, this.options.helpSelector));
				parent.addEvent('mouseleave', this.hide.bindWithEvent(parent, this.options.helpSelector));
			} else {
				item.addEvent('focus', this.show.bindWithEvent(parent, this.options.helpSelector));
				item.addEvent('blur', this.hide.bindWithEvent(parent, this.options.helpSelector));
			}
		}, this);
		
	},
/**
 * Show an element
 *
 * Is used as a mouseEnter event. 
 */	
	show : function(e, helpSelector) {
		e = new Event(e);
		this.addClass('active');
		if (helpSelector) {
			var fieldHint = this.getChildren(helpSelector);
			if (fieldHint) {
				fieldHint.fade('in');
			}
		}
	},
/**
 * Hide an element
 *
 * Is used as a mouseLeave event.
 */
	hide : function(e, helpSelector) {
		e = new Event(e);
		this.removeClass('active');
		if (helpSelector) {
			var fieldHint = this.getChildren(helpSelector);
			if (fieldHint) {
				fieldHint.fade('out');
			}
		}
	}
	
});
