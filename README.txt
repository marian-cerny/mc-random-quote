<h1>AJAX Random Quote plugin</h1>

<p>by <a href="http://mariancerny.com/" title="Marian Cerny">Marian Cerny</a></p>

<p>This Wordpress plugin lets easily integrate a random quote section into your theme either by using the widget, or by creating your own markup. Quotes can be easily added and removed as posts (using custom post type). Supports loading new quotes with AJAX by clicking on the quote or using an interval.</p>

<h2>Usage</h2>

<p><strong>Widget</strong></p>

<p>Simply insert the widget into your theme in the admin interface.</p>

<p>The widget will automatically switch between your quotes in a 15 second interval. You can also reload the quote by clicking the quote heading.</p>

<p><strong>Manually retrieving quotes</strong></p>

<p>You can get a random quote from the database by simply calling <code>get_random_quote()</code> function. This will return an array with two values - 'author' and 'text'. You can then output them anywhere in your theme.</p>

