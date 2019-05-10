var Note = Class.create()
Note.zindex = 0
Note.counter = -1
Note.all = []
Note.display = true
Note.scaled = false

Note.show = function() {
	for (var i=0; i<Note.all.length; ++i) {
		Note.all[i].bodyHide()
		Note.all[i].elements.box.style.display = "block"
	}
}

Note.hide = function() {
	for (var i=0; i<Note.all.length; ++i) {
		Note.all[i].bodyHide()
		Note.all[i].elements.box.style.display = "none"
	}
}

Note.find = function(id) {
	for (var i = 0; i<Note.all.length; ++i) {
		if (Note.all[i].id == id) {
			return Note.all[i]
		}
	}

	return null
}

Note.toggle = function() {
	if (Note.display) {
		Note.hide()
		Note.display = false
	} else {
		Note.show()
		Note.display = true
	}
}

Note.updateNoteCount = function() {
	if (Note.all.length > 0) {
		var label = Note.all.length == 1 && " annotation" || " annotations";
		$("note-count").children[0].text = Note.all.length + label;
		$("note-count").children[0].href = "/history/page_notes/" + Note.post_id;
		$('note-count').style.display = "block";
	}
};

Note.create = function() {
	var template = $("note-template").cloneNode(true).children;
	var image = $("image");
	
	var noteBox = template[0];
	noteBox.id = "note-box-" + Note.counter;
	noteBox.children[1].id = "note-tilt-" + Note.counter;
	noteBox.children[0].id = "note-corner-" + Note.counter;
	
	var noteBody = template[1];
	noteBody.id = "note-body-" + Note.counter;
	
	var noteContainer = $("note-container");
	noteContainer.appendChild(noteBox);
	noteContainer.appendChild(noteBody);
	
	noteBox.style.top = (image.clientHeight / 2 - noteBox.clientHeight/2) + "px";
	noteBox.style.left = (image.clientWidth / 2 - noteBox.clientWidth/2) + "px";
	
	Note.all.push(new Note(Note.counter, true));
	Note.counter -= 1;
};

Note.prototype = {
	// Necessary because addEventListener/removeEventListener don't play nice with
	// different instantiations of the same method.
	bind: function(method_name) {
		if (!this.bound_methods) {
			this.bound_methods = new Object()
		}

		if (!this.bound_methods[method_name]) {
			this.bound_methods[method_name] = this[method_name].bindAsEventListener(this)
		}

		return this.bound_methods[method_name]
	},

	initialize: function(id, is_new) {
		this.id = id
		this.is_new = is_new

		// get the elements
		this.elements = {
			box:		$('note-box-' + this.id),
			corner:		$('note-corner-' + this.id),
			tilt:		$('note-tilt-' + this.id),
			body:		$('note-body-' + this.id),
			image:		$('image')
		}
		
		var scale = 1
		if(Note.scaled && is_new){
			var image = document.getElementById("image");
			scale = image.naturalWidth/image.clientWidth;
		}

		// store the data
		this.old = {
			left:		this.elements.box.offsetLeft*scale,
			top:		this.elements.box.offsetTop*scale,
			width:		this.elements.box.clientWidth*scale,
			height:		this.elements.box.clientHeight*scale,
			angle:		0,
			body:		this.elements.body.innerHTML
		}
		
		if(!is_new){
			this.old.angle = 
				parseFloat(this.elements.box.style.transform.match(/[-+]?([0-9]*\.[0-9]+|[0-9]+)/)[0])
		}

		this.angle = this.old.angle;
		
		// reposition the box to be relative to the image

		this.elements.box.style.top = this.elements.box.offsetTop + "px"
		this.elements.box.style.left = this.elements.box.offsetLeft + "px"

		// attach the event listeners
		Event.observe(this.elements.box, "mousedown", this.bind("dragStart"), true)
		Event.observe(this.elements.box, "mouseout", this.bind("bodyHideTimer"), true)
		Event.observe(this.elements.box, "mouseover", this.bind("bodyShow"), true)
		Event.observe(this.elements.corner, "mousedown", this.bind("resizeStart"), true)
		Event.observe(this.elements.tilt, "mousedown", this.bind("rotateStart"), true)
		Event.observe(this.elements.body, "mouseover", this.bind("bodyShow"), true)
		Event.observe(this.elements.body, "mouseout", this.bind("bodyHideTimer"), true)
		Event.observe(this.elements.body, "click", this.bind("showEditBox"), true)
	},

	textValue: function() {
		return this.old.body.replace(/(?:^\s+|\s+$)/, '')
	},

	hideEditBox: function(e) {
		var editBox = $('edit-box')
		
		Event.stopObserving('note-save', 'click', this.bind("save"), true)
		Event.stopObserving('note-cancel', 'click', this.bind("cancel"), true)
		Event.stopObserving('note-remove', 'click', this.bind("remove"), true)
		Event.stopObserving('note-history', 'click', this.bind("history"), true)

		editBox.style.visibility = "hidden"
	},

	showEditBox: function(e) {
		var editBox = $('edit-box')
		var editBoxText = $('edit-box-text')
		var body = this.elements.body

		this.hideEditBox(e)

		editBox.style.top = body.style.top
		editBox.style.left = body.style.left
		editBox.style.visibility = "visible"
		editBoxText.value = this.textValue()

		Event.observe('note-save', 'click', this.bind("save"), true)
		Event.observe('note-cancel', 'click', this.bind("cancel"), true)
		Event.observe('note-remove', 'click', this.bind("remove"), true)
		Event.observe('note-history', 'click', this.bind("history"), true)
	},

	bodyShow: function(e) {
		if (this.dragging)
			return

		if (this.hideTimer) {
			clearTimeout(this.hideTimer)
			this.hideTimer = null
		}

		// hide the other notes
		if (Note.all) {
			for (var i=0; i<Note.all.length; ++i) {
				if (Note.all[i].id != this.id) {
					Note.all[i].bodyHide()
				}
			}
		}
		
		var rad = this.angle*Math.PI/180
		var leftRelative = -this.elements.box.clientWidth/2
		var topRelative  = this.elements.box.clientHeight+5
		var sin = Math.sin(rad)
		var cos = Math.cos(rad)
		var left = 0
		var top  = 0 
		
		if(rad > 0){
			leftRelative = -leftRelative
			left = -this.elements.body.clientWidth
		}
		
		left = left + cos*leftRelative - sin*topRelative + 
				this.elements.box.offsetLeft + this.elements.box.clientWidth/2
		top = sin*leftRelative + cos*topRelative + this.elements.box.offsetTop
		
		this.elements.box.style.zIndex = ++Note.zindex
		this.elements.body.style.zIndex = Note.zindex
		this.elements.body.style.top = top + "px"
		this.elements.body.style.left = left + "px"
		this.elements.body.style.visibility = "visible"
	},

	bodyHideTimer: function(e) {
		this.hideTimer = setTimeout(this.bind("bodyHide"), 250)
	},

	bodyHide: function(e) {
		this.elements.body.style.visibility = "hidden"
	},

	rotateStart: function(e) {
		Event.stopObserving(document.documentElement, 'mousemove', this.bind("drag"), true)
		Event.stopObserving(document.documentElement, 'mouseup', this.bind("dragStop"), true)
		Event.observe(document.documentElement, 'mousemove', this.bind("rotate"), true)
		Event.observe(document.documentElement, 'mouseup', this.bind("rotateStop"), true)
		document.onselectstart = function() {return false}

		this.cursorStartX = Event.pointerX(e)/(this.elements.box.clientHeight/42)
		this.angleStart = this.angle
		this.dragging = true

		this.bodyHide()
	},

	rotateStop: function(e) {
		Event.stopObserving(document.documentElement, 'mousemove', this.bind("rotate"), true)
		Event.stopObserving(document.documentElement, 'mouseup', this.bind("rotateStop"), true)
		document.onselectstart = function() {return true}
		
		var rot = this.angleStart - Event.pointerX(e)/(this.elements.box.clientHeight/42) + this.cursorStartX
		rot = rot > 45 && 45 || rot
		rot = rot < -45 && -45 || rot
		this.angle = rot
		
		this.cursorStartX = null
		this.angleStart = null
		this.dragging = false

		if(this.is_new){
			this.old.angle = this.angle
		}

		this.bodyShow()
	},

	rotate: function(e) {
		var rot = this.angleStart - Event.pointerX(e)/(this.elements.box.clientHeight/42) + this.cursorStartX
		rot = rot > 45 && 45 || rot
		rot = rot < -45 && -45 || rot

		this.elements.box.style.transform = "rotate(" + rot + "deg)"
		
		Event.stop(e)
	},

	dragStart: function(e) {
		Event.observe(document.documentElement, 'mousemove', this.bind("drag"), true)
		Event.observe(document.documentElement, 'mouseup', this.bind("dragStop"), true)
		document.onselectstart = function() {return false}

		this.cursorStartX = Event.pointerX(e)
		this.cursorStartY = Event.pointerY(e)
		this.boxStartX = this.elements.box.offsetLeft
		this.boxStartY = this.elements.box.offsetTop
		this.dragging = true

		this.bodyHide()
	},

	dragStop: function(e) {
		Event.stopObserving(document.documentElement, 'mousemove', this.bind("drag"), true)
		Event.stopObserving(document.documentElement, 'mouseup', this.bind("dragStop"), true)
		document.onselectstart = function() {return true}

		this.cursorStartX = null
		this.cursorStartY = null
		this.boxStartX = null
		this.boxStartY = null
		this.dragging = false

		if(this.is_new){
			var image = document.getElementById("image");
			var scale = image.naturalWidth/image.clientWidth; 
			this.old.top = this.elements.box.offsetTop*scale;
			this.old.left = this.elements.box.offsetLeft*scale;
		}

		this.bodyShow()
	},

	drag: function(e) {
		var left = this.boxStartX + Event.pointerX(e) - this.cursorStartX
		var top = this.boxStartY + Event.pointerY(e) - this.cursorStartY
		var bound

		bound = 5
		if (left < bound)
			left = bound

		bound = this.elements.image.clientWidth - this.elements.box.clientWidth - 5
		if (left > bound)
			left = bound

		bound = 5
		if (top < bound)
			top = bound

		bound = this.elements.image.clientHeight - this.elements.box.clientHeight - 5
		if (top > bound)
			top = bound

		this.elements.box.style.left = left + 'px'
		this.elements.box.style.top = top + 'px'

		Event.stop(e)
	},

	resizeStart: function(e) {
		this.cursorStartX = Event.pointerX(e)
		this.cursorStartY = Event.pointerY(e)
		this.boxStartWidth = this.elements.box.clientWidth
		this.boxStartHeight = this.elements.box.clientHeight
		this.boxStartX = this.elements.box.offsetLeft
		this.boxStartY = this.elements.box.offsetTop
		this.dragging = true

		Event.stopObserving(document.documentElement, 'mousemove', this.bind("drag"), true)
		Event.stopObserving(document.documentElement, 'mouseup', this.bind("dragStop"), true)
		Event.observe(document.documentElement, 'mousemove', this.bind("resize"), true)
		Event.observe(document.documentElement, 'mouseup', this.bind("resizeStop"), true)

		this.bodyHide()
	},

	resizeStop: function(e) {
		Event.stopObserving(document.documentElement, 'mousemove', this.bind("resize"), true)
		Event.stopObserving(document.documentElement, 'mouseup', this.bind("resizeStop"), true)

		this.boxCursorStartX = null
		this.boxCursorStartY = null
		this.boxStartWidth = null
		this.boxStartHeight = null
		this.boxStartX = null
		this.boxStartY = null
		this.dragging = false

		if(this.is_new){
			var image = document.getElementById("image");
			var scale = image.naturalWidth/image.clientWidth; 
			this.old.width = this.elements.box.clientWidth*scale;
			this.old.height = this.elements.box.clientHeight*scale;
		}
	},

	resize: function(e) {
		var w = this.boxStartWidth + Event.pointerX(e) - this.cursorStartX
		var h = this.boxStartHeight + Event.pointerY(e) - this.cursorStartY
		var bound

		if (w < 30)
			w = 30

		bound = this.elements.image.clientWidth - this.boxStartX - 5
		if (w > bound)
			w = bound

		if (h < 30)
			h = 30

		bound = this.elements.image.clientHeight - this.boxStartY - 5
		if (h > bound)
			h = bound

		this.elements.box.style.width = w + "px"
		this.elements.box.style.height = h + "px"
		this.elements.box.style.left = this.boxStartX + "px"
		this.elements.box.style.top = this.boxStartY + "px"
	},

	save: function(e) {
		var scale = 1
		if(Note.scaled){
			var image = document.getElementById("image");
			scale = image.naturalWidth/image.clientWidth;
		}
		
		this.old.left = this.elements.box.offsetLeft*scale
		this.old.top = this.elements.box.offsetTop*scale
		this.old.width = this.elements.box.clientWidth*scale
		this.old.height = this.elements.box.clientHeight*scale
		this.old.angle = this.angle
		this.old.body = $('edit-box-text').value
		this.elements.body.innerHTML = this.textValue()

		this.hideEditBox(e)
		this.bodyHide()

		var params = jQuery.param({
			"note[x]" : this.old.left,
			"note[y]" : this.old.top,
			"note[width]" : this.old.width,
			"note[height]" : this.old.height,
			"note[angle]" : this.old.angle,
			"note[post_id]" : Note.post_id
		}) + encodeURI("&note[body]=") + encodeURIComponent(this.old.body);

		notice("Saving note...")
		new Ajax.Request('/note/save/' + this.id + '/', {
			asynchronous: true,
			method: 'get',
			parameters: params,
			onComplete: function(req) {
				if (req.status == 200) {
					notice("Note saved")
					//var response = eval(req.responseText)
					var response = req.responseText.split(":");
					if (response[1] < 0) {
						var n = Note.find(response[1])
						n.id = response[0]
						n.elements.box.id = 'note-box-' + n.id
						n.elements.body.id = 'note-body-' + n.id
						n.elements.corner.id = 'note-corner-' + n.id
						n.elements.tilt.id = 'note-tilt-' + n.id
						n.is_new = false;
					}
				} else {
					notice("Error: " + req.responseText)
					//alert(req.status);
				}
			}
		})

		Event.stop(e)
	},

	cancel: function(e) {
		this.hideEditBox(e)
		this.bodyHide()
		
		var scale = 1
		if(Note.scaled){
			var image = document.getElementById("image");
			scale = image.clientWidth/image.naturalWidth;
		}
		
		this.elements.box.style.top = this.old.top*scale + "px"
		this.elements.box.style.left = this.old.left*scale + "px"
		this.elements.box.style.width = this.old.width*scale + "px"
		this.elements.box.style.height = this.old.height*scale + "px"
		this.elements.box.style.transform = "rotate(" + this.old.angle + "deg)"
		this.elements.body.innerHTML = this.old.body

		Event.stop(e)
	},

	removeCleanup: function() {
		Element.remove(this.elements.box)
		Element.remove(this.elements.body)

		var allTemp = []
		for (i=0; i<Note.all.length; ++i) {
			if (Note.all[i].id != this.id) {
				allTemp.push(Note.all[i])
			}
		}

		Note.all = allTemp
		Note.updateNoteCount()
	},

	remove: function(e) {
		this.hideEditBox(e)
		this.bodyHide()
		if (this.is_new) {
			this.removeCleanup()
			notice("Note removed")
		} else {
			notice("Removing note...")

			new Ajax.Request('/remove/note/' + Note.post_id + "/" + this.id, {
				asynchronous: true,
				method: 'get',
				onComplete: function(req) {
					if (req.status == 403) {
						notice("Access denied")
					} else if (req.status == 500) {
						notice("Error: " + req.responseText)
					} else {
						Note.find(parseInt(req.responseText)).removeCleanup()
						notice("Note removed")
					}
				}
			})
		}

		Event.stop(e)
	},

	history: function(e) {
		this.hideEditBox(e)

		if (this.is_new) {
			notice("This note has no history")
		} else {
			document.location='/history/note/' + this.id + "/" + Note.post_id;
		}
	}
}
