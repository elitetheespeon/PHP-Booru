function showHide(id)
{
	//Element.toggle(id);
    jQuery( "#" + id ).toggle();
}

function addFav(id)
{
	var HttpRequest;
	try
	{
		HttpRequest = new XMLHttpRequest(); 
	}
	catch(e)
	{
		try
		{
			HttpRequest = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e)
		{
			try
			{
				HttpRequest = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e)
			{
				alert("Your browser is to old or does not support Ajax.");
			}
		}
	}
	HttpRequest.onreadystatechange=function()
	{
		if(HttpRequest.readyState==4)
		{
			if(HttpRequest.responseText == "1")
			{
				notice("Post already in your favorites");
			}
			else if(HttpRequest.responseText == "2")
			{
				notice("You are not logged in");
			}
			else
			{
				notice("Post added to favorites");
			}
		}
	}
	HttpRequest.open("GET","/favorites/add/" + id,true);
	HttpRequest.send(null);
    setTimeout(function () {window.location.reload()}, 2000);
}
function notice(msg)
{
	$("notice").innerHTML=msg;
}
function vote(pid, comment_id, up_down)
{
	var HttpRequest;
	try
	{
		HttpRequest = new XMLHttpRequest(); 
	}
	catch(e)
	{
		try
		{
			HttpRequest = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e)
		{
			try
			{
				HttpRequest = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e)
			{
				alert("Your browser is to old or does not support Ajax.");
			}
		}
	}
	HttpRequest.onreadystatechange=function()
	{
		if(HttpRequest.readyState==4)
		{
			updateScore("sc" + comment_id,parseInt(HttpRequest.responseText));
		}
	}
	pid = encodeURI(pid);
	HttpRequest.open("GET", "/comment/vote/" + pid + "/" + comment_id + "/" + up_down,true);
	HttpRequest.send(null);
}
function post_vote(pid, up_down)
{
	var HttpRequest;
	try
	{
		HttpRequest = new XMLHttpRequest(); 
	}
	catch(e)
	{
		try
		{
			HttpRequest = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e)
		{
			try
			{
				HttpRequest = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e)
			{
				alert("Your browser is to old or does not support Ajax.");
			}
		}
	}
	HttpRequest.onreadystatechange=function()
	{
		if(HttpRequest.readyState==4)
		{
			updateScore("psc",parseInt(HttpRequest.responseText));
		}
	}
	pid = encodeURI(pid);
	HttpRequest.open("GET", "/post/vote/" + pid + "/" + up_down,true);
	HttpRequest.send(null);
}
function updateScore(id, score)
{
	$(id).innerHTML=score;
}
function spam(type, id)
{
	var HttpRequest;
	try
	{
		HttpRequest = new XMLHttpRequest(); 
	}
	catch(e)
	{
		try
		{
			HttpRequest = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e)
		{
			try
			{
				HttpRequest = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e)
			{
				alert("Your browser is to old or does not support Ajax.");
			}
		}
	}
	
	if(type == "comment")
	{
		HttpRequest.open('GET','/report/comment/' + id, true);
		HttpRequest.send(null);
		HttpRequest.onreadystatechange=function()
		{
			if(HttpRequest.readyState==4)
			{
				if(HttpRequest.responseText == "pass")
				{
					$('rc' + id).innerHTML='<b>Reported</b>';
					$('rcl' + id).innerHTML='';
					notice("Comment #" + id + " has been reported as spam");
				}
			}
		}
	}
}
function filterComments(post_id, comment_size)
{
	var cignored = []
	
	for (i in posts[post_id].comments) 
	{
		var hidden = false
		
		if (posts[post_id].comments[i].score < cthreshold) 
		{
			hidden = true
		}

		if (!hidden && users.include([post_id].comments[i].user)) 
		{
			hidden = true
		}
		
		if (hidden) 
		{
			showHide('c' + i)
			cignored.push('c' + i)
			posts[post_id].ignored[i] = i
		}
	}	

	if (cignored.length > 0) 
	{
		$('ci').innerHTML = ' (' + cignored.length + ' hidden)'
	}
}


function readCookie(name) 
{
	var nameEq = name + "="
	var ca = document.cookie.split(";")

	for (var i = 0; i < ca.length; ++i) 
	{
		var c = ca[i]

		while (c.charAt(0) == " ") 
		{
			c = c.substring(1, c.length)
		}

		if (c.indexOf(nameEq) == 0) 
		{
			return decodeURIComponent(decodeURI(c.substring(nameEq.length, c.length)))
		}
	}

	return ""
}



function showHideIgnored(post_id,id)
{
	var j = 0
	if(id == 'ci')
	{	
		for(i in posts[post_id].ignored)
		{
			j++
			showHide('c' + i)
		}
		if($('ci').innerHTML != " (" + j + " hidden)")
		{
			$('ci').innerHTML = " (" + j + " hidden)"
		}
		else
		{
			$('ci').innerHTML = " (0 hidden)"
		}
	}
	else if(id == 'pi')
	{
		for(i in pignored)
		{
			j++
			showHide('p' + i)
		}
		if($('pi').innerHTML != "(" + j + " post" + (j == 1 ? '' : 's') + " hidden)" && j > 0)
		{
			$('pi').innerHTML = "(" + j + " post" + (j == 1 ? '' : 's') + " hidden)"
		}
		else if($('pi').innerHTML == "(" + j + " post" + (j == 1 ? '' : 's') + " hidden)" && j > 0)
		{
			$('pi').innerHTML = "(0 posts hidden)"
		}
		else
		{
			$('pi').innerHTML = ""
		}
	}
}

function filterPosts(posts) 
{
	var tags = readCookie("tag_blacklist").split(/[, ]|%2520+/g) || ''
	var ttags = Array()
	var g = 0;        
	var users = readCookie("user_blacklist").split(/[, ]|%2520+/g)
	var threshold = -99
	tags.each(function(j){
	
		if(j != "" && j != " "){
			ttags[g] = j.toLowerCase();
			++g;
		}
	})
	tags = ttags;
	var ignored = []
	for (i in posts)  
	{
		var hidden = false
		if (posts[i].score < threshold) {
			hidden = true
		}

		if (!hidden && posts[i].user != "" && users.include(posts[i].user)) {
			hidden = true
		}

		if (!hidden) {
			if (tags.include(posts[i].rating.toLowerCase())) {
				hidden = true
			}
		}

		if (!hidden) 
		{
			tags.each(function(j) {
				if (posts[i].tags.include(j)) {
					hidden = true
				}
			})
		}

		if (hidden) 
		{
			showHide('p' + i)
			Element.hide('p' + i);
			ignored.push('p' + i)
			pignored[i] = i
		}
	}

	if (ignored.length > 0) {
		$('pi').innerHTML="(" + ignored.length + " post" + (ignored.length == 1 ? '' : 's') + " hidden)"
	}
}


function filterCommentList(comment_size) 
{
	var tags = readCookie("tag_blacklist").split(/[, ]|%20+/g)
	var threshold = parseInt(readCookie("post_threshold")) || 0
	var ttags = Array()
	var g = 0;
	tags.each(function(j){
		if(j != "" && j != " "){
			ttags[g] = j.toLowerCase();
			++g;
		}
	})
	tags = ttags;
	var cignored = []
	var j = 0;
	var lastid = 0;
	var auto_hidden = false;
	for (i in posts.comments) 
	{
	
		var hidden = false
		if(lastid != posts.comments[i].post_id)
		{
			auto_hidden = false
			if(posts.rating[posts.comments[i].post_id] != "")
			{
				if (tags.include(posts.rating[posts.comments[i].post_id].toLowerCase())) {
					auto_hidden = true
				}
				if (!auto_hidden) 
				{
					tags.each(function(j) {
						if (posts.tags[posts.comments[i].post_id].include(j)) {
							auto_hidden = true
						}
					})
				}
				if(!auto_hidden)
				{
					if(posts.score[posts.comments[i].post_id] < threshold)
					{
						auto_hidden = true					
					}				
				}
			}
			if(posts.totalcount[lastid] <= j)
			{
				Element.hide('p' + lastid);
				phidden[lastid] = true;
			}
			else
			{
				phidden[lastid] = false;
			}
			j = 0;
			lastid = posts.comments[i].post_id;
		}
		if (!auto_hidden && posts.comments[i].score < cthreshold) 
		{
			hidden = true
		}

		if (!auto_hidden && !hidden && users.include(posts.comments[i].user)) 
		{
			hidden = true
		}

		if (hidden || auto_hidden) 
		{
			Element.hide('c' + i)
			cignored.push('c' + i)
			posts.ignored[i] = i
			++j;
		}
	}	
	if(posts.totalcount[lastid] <= j)
	{
		Element.hide('p' + lastid);
		phidden[lastid] = true;
	}
	else
	{
		phidden[lastid] = false;
	}
	if (cignored.length > 0) 
	{
		$('ci').innerHTML = '(' + cignored.length + ' hidden)'
	}
}


function showHideCommentListIgnored(id)
{
	var j = 0;
	var lastpid = 0;
	for(i in posts.ignored)
	{
		++j
		showHide('c' + i)
		if(phidden[posts.comments[i].post_id] && lastpid != posts.comments[i].post_id)
		{
			Element.toggle("p" + posts.comments[i].post_id);
			lastpid = posts.comments[i].post_id;
		}
	}
	if($('ci').innerHTML != "(" + j + " hidden)")
	{
		$('ci').innerHTML = "(" + j + " hidden)"
	}
	else
	{
		$('ci').innerHTML = "(0 hidden)"
	}
}


function update_image_src(current_date,creation_date,image_path)
{
	try
	{
		HttpRequest = new XMLHttpRequest(); 
	}
	catch(e)
	{
		try
		{
			HttpRequest = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(e)
		{
			try
			{
				HttpRequest = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(e)
			{
				alert("Your browser is to old or does not support Ajax.");
			}
		}
	}
	if(current_date == creation_date)
	{
		alert("bsdf");
		document.getElementById('image').src='images/' + image_path;
	}
	else
	{
		alert("asdf");
		HttpRequest.onreadystatechange=function()
		{
			if(HttpRequest.readyState == 4)
			{
				var tmp = HttpRequest.reponseText;
				var domains = tmp.split('<split-here>');
				var i = Math.floor(Math.random()*domains.length);
				var domain = domains[i];
				document.getElementById('image').src=domain+'/'+image_path;
				alert(document.getElementById('image').src);
			}
		}
		HttpRequest.open("GET","/public/domains.php",true);
		HttpRequest.send(null);
	}
}

//resize dat shit


function fit_to_screen()
{
    img = document.getElementById("image");
    img.removeAttribute("style");
    var winX = window.innerWidth + "px";
    var winY = window.innerHeight + "px";
    var vbar = false;
    if (document.body.scrollHeight > document.body.clientHeight) // vertical scrollbar
    {
            img.style.height = winY;
            vbar = true;
    }
    if (document.body.scrollWidth > document.body.clientWidth) // horizontal scrollbar
    {
            if (vbar) // both scrollbars
            {
                    if ((document.body.scrollHeight - document.body.clientHeight) > (document.body.scrollWidth - document.body.clientWidth)) // let's see which one is bigger
                    {
                            img.removeAttribute("style");
                            img.style.height = winY;
                    }
                    else
                    {
                            img.removeAttribute("style");
                            img.style.width = winX;
                    }
            }
            else
            {
                    img.removeAttribute("style");
                    img.style.width = winX;
            }
    }
    if (window.Note) {
      for (var i=0; i<window.Note.all.length; ++i) {
        Note.prototype.all[i].adjustScale()
      }
    }
}

//switch between original size and fullscreen
var rescaled = false;

function rescale()
{
    if (rescaled)
    {
            rescaled = false;
            img.removeAttribute("style");
    }
    else
    {
            rescaled = true;
            fit_to_screen();
    }
}