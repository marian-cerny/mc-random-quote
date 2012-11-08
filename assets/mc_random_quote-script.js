var quoteInterval = setInterval(function()
{
    getRandomQuote();
}
, 15000);

$(document).ready(function()
{	
	//next quote on clicking quotes heading
	$('.mc_random_quote_heading').click(function() {
			getRandomQuote();
			clearInterval(quoteInterval);
			quoteInterval = setInterval(function()
			{
				getRandomQuote();
			}
			, 15000);
	});
});



function getRandomQuote()
{
	$('.mc_random_quote_content').animate({ opacity: 0 }, 500, function()
		{
		$.ajax(
		{  
			url: phpvars.ajax_url,  
			type:'POST',  
			data: "action=get_quote",   
			success: function(html)
			{  
				//html = html.slice(0, -1)
				$(".mc_random_quote_content").html(html).animate({ opacity: 1 }, 500);
			}  
		});
		});
	
}