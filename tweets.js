// PONGSOCKET TWEET ARCHIVE
// JavaScript features
// Requires jQuery

var searchPlaceholder = "Search for tweets..."

$(document).ready(function(){
	$("#search input").focus(function(){
		if(this.className == "empty"){
			this.value = ""
			$(this).removeClass("empty")
		}
	}).blur(function(){
		if(this.value == ""){
			$(this).addClass("empty")
			this.value = searchPlaceholder
		}
	})
	if($("#search input").val() == "" || $("#search input").val() == searchPlaceholder){
		$("#search input").addClass("empty").val(searchPlaceholder)
	}
	
	// Hover associations
	$("a.picl").each(function(){
		var cn = this.className.split(" ")
		for(var c in cn){
			if(m = cn[c].match(/picl-([0-9]+)/)){ this.n = m[1]; break }
		}
		if(this.n && $(this).parents(".tweet").length > 0){
			$(this).hover(function(){
				$(this).parents(".tweet").children("a.pic-" + this.n).addClass("hoverin")
			}, function(){
				$(this).parents(".tweet").children("a.pic-" + this.n).removeClass("hoverin")
			})
		}
	})
	$("a.pic").each(function(){
		var cn = this.className.split(" ")
		for(var c in cn){
			if(m = cn[c].match(/pic-([0-9]+)/)){ this.n = m[1]; break }
		}
		if(this.n && $(this).parents(".tweet").length > 0){
			$(this).hover(function(){
				$(this).parents(".tweet").children(".text").children("a.picl-" + this.n).addClass("hoverin")
			}, function(){
				$(this).parents(".tweet").children(".text").children("a.picl-" + this.n).removeClass("hoverin")
			})
		}
	})
	
	// Appendix: If people are using IE7 or below, they get some layout-enhancing fixes...
	if($.browser.msie && $.browser.version < 8){
		var daysShown = ($("#days").length > 0)
		if(daysShown){
			var m = $("#days").get(0).className.match(/days-([0-9]{2})/)
			if(m){
				var daysSpacing = 3 // Pixels
				var daysInMonth = parseInt(m[1])
				var w = ($("#days").width() - daysSpacing*(daysInMonth-1)) / daysInMonth, maxHeight = 0
				$("#days .d").css({ display: "block", float: "left", width: w + "px", "margin-right": "3px" })
				$("#days .d")[$("#days .d").length-1].style.marginRight = 0
				$("#days .d").each(function(){ if($(this).height() > maxHeight){ maxHeight = $(this).height() } })
				$("#days .d").each(function(){ $(this).css("margin-top", (maxHeight - $(this).height()) + "px") })
			}
		}
		function IEresize(){
			if(daysShown){
				var w = ($("#days").width() - daysSpacing*(daysInMonth-1)) / daysInMonth
				$("#days .d").css("width", w + "px")
				$("#days .d .n").css({ width: w + "px", right: "auto" }) // Helping text center correctly
			}
			if($.browser.version >= 7){
				$("#months li:not(.home, .fav, .search, .meta)").css({ width: $("#months").width() + "px", "margin-bottom" : "-3px" })
			}
		}
		IEresize()
		$(window).resize(IEresize)
	}
})

// Using our OAuth key to activate subtle @anywhere features
if(window.twttr && window.twttr.anywhere){
	twttr.anywhere(function(T){
		T(".tweet a.user").hovercards({
			username: function(node){ return node.href.match(/[a-zA-Z0-9_]+$/)[0] }
		})
		T(".tweet a.rt, #author h2 a").hovercards({ 
			username: function(node){ return node.parentNode.href.match(/[a-zA-Z0-9_]+$/)[0] }
		})
	})
}