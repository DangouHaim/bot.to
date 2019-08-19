var ajaxurl = CASE27.ajax_url;
(function($){

	function attachEventHandler(selector, event, handler) {
		var tresholdLimit = 25;
		var current = 0;
		var attached = 0;
		
		$(selector).on(event, handler);
		attached = $(selector).length;
		
		var interval = function() {
			current++;
			if(current > tresholdLimit) {
				
				clearInterval(timer);

			} else {
				if($(selector).length > attached) {

					$(selector).on(event, handler);
					attached = $(selector).length;
					
				}
			}
		}

		var timer = setInterval(interval, 500);
	}
	var _ = attachEventHandler;
	
	function getYoutubeVideoId(url) {
        var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        var match = url.match(regExp);

        if (match && match[2].length == 11) {
            return match[2];
        } else {
            return 'error';
        }
	}
	
    function getVideoSection(data) {
        if(data.toLowerCase().includes("youtube.com") || data.toLowerCase().includes("youtu.be")) {
            var videoId = getYoutubeVideoId(data);
            //var iframeMarkup = '<iframe width="320" height="240" src="//www.youtube.com/embed/' 
			//	+ videoId + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>';
			var iframeMarkup = '<iframe frameborder="0" allowfullscreen="1" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" title="YouTube video player" width="320" height="240" src="https://www.youtube.com/embed/'+videoId+'?autoplay=1&controls=1&amp;modestbranding=1&amp;rel=0&amp;showinfo=0&amp;loop=0&amp;fs=0&amp;hl=ru&amp;iv_load_policy=3&amp;enablejsapi=1&amp;widgetid=1"></iframe>';

            var result = iframeMarkup;
        } else {
            var result = '<video style="background: #000;" controls="">'
				+ '<source src="' + data + '" type="video/mp4" autoplay>'
				+ 'Your browser does not support the video.'
			+ '</video>';
		}
		return result;
	}

	function videoHandler() {
		_(".watch-video", "click", function() {
			$("#play-video-modal .modal-content").html(  getVideoSection( $(this).data("video") )  );
		});
	}

	function removeVideo() {
		$("#play-video-modal").click(function(){
			$("#play-video-modal .modal-content").html("");
		});
	}

	var page = -1;
	function loadMoreHandler() {
		$(".load-more").click(function(e) {
			e.preventDefault();

			var _this = $(this);
			var _target = $("*[data-target-type='" + _this.data("target") + "']");
			_this.hide();

			if(page < 0) {
				page = _target.data("page") + 1;
			}

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				data: {
					action: "renderGridViewAjax",
					query: "?type=" + _this.data("type") + "&pg=" + page,
					atts: _target.data("atts")
				},
				success: function(data) {
					if(data.trim() != "") {
						_target.parent().parent().append(data);
						page++;
						_this.show();
					}
				}
			});
		});
	}

	function CreateAdSubmitHandler()
	{
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			dataType: 'json',
			data: {
				action: "getBotLinksAjax",
				job_id: $("#submit-job-form").data("id")
			},
			success: function(data) {
				for (var key in data) {
					$("#form-section-bot-links input").trigger("click");
					var item = $("#form-section-bot-links [data-repeater-list='botLinks']").last();
					item.find("select").last().val(data[key][0]);
					item.find("span .selection").last().find("span > span").first().text(data[key][1]);
					item.find("input[type='text']").last().val(data[key][2]);
				}
			}
		});

		$("#submit-job-form").submit(function() {
			var botLinks = [];
			$("#form-section-bot-links input[name^='botLinks']").each(function() {
				var link = [];
				link.push($(this).parent().find("select").val());
				link.push($(this).parent().find("select option:selected").text());
				link.push($(this).val());
				botLinks.push(link);
			});

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				data: {
					action: "saveBotLinksAjax",
					botLinks: botLinks
				},
				success: function(data) {
					
				}
			});
		});
	}

	function matchHeightHandler() {
		$(".sbf-container .sbf-title").matchHeight();
	}

	$(window).ready(function() {
		matchHeightHandler();
		loadMoreHandler();
	    videoHandler();
		removeVideo();
	});

	$(window).load(function(){
		CreateAdSubmitHandler();
	});

	$(document).on("scroll", function(){
	    
	});
	
	$(document).change(function() {
	    
	});
	
})(jQuery);