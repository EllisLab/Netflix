# Netflix

STEP ONE:
Become a memember of NetFlix, and then look in your browser cookies to discover the 'NetflixShopperId' cookies value for '.netflix.com'.

STEP TWO:
Insert plugin tag into your template.

## PARAMETERS

The tag has three parameters:

1. `id` - The NetflixShopperId for the NetFlix account. Look in your cookies for this value
2. `limit` - Number of items to display. Default is to display all items returned.
3. `refresh` - How often to update the cache file in minutes. The default is to update the cache file once a day.
4. `which` - Which list of items to retrieve. There are two options 'queue' (items in the queue) or 'out' (items currently out). Default is queue.

Example tag: `{exp:netflix id="P1152183453382851739665891469694171" limit="8" refresh="720"}`.

## SINGLE VARIABLES

`{total}` - Total items returned

## PAIR VARIABLES

Only one pair variable, {items}, is available, and it is for the items returned from the NetFlix queue.

This pair variable has the following single variables:

- `{movie_id}` - NetFlix id for DVD
- `{title}` - Title of DVD
- `{link}` - URL to details about DVD
- `{genre}` - Genre of DVD
- `{rating}` - NetFlix's Rating of DVD
- `{mpaa}` - MPAA Rating of Film

EXAMPLE:

    {exp:netflix id="P1152183453382851739665891469694171"}
    	<ul>
    	{items}
    		<li><a href="{link}">{title}</a> in {genre} (Rating: {rating})</li>
    	{/items}
    	</ul>
    {/exp:netflix}

NEAT-O: Using the NetFlix movie id you can link to images for the DVDs. Example:

    <img src="http://a408.g.akamai.net/f/408/1284/24h/image.netflix.com/NetFlix_Assets/boxshots/small/{movie_id}.jpg" width="65" height="90" />

I have found that some DVDs when part of a series of discs may not have an image available this way. In these cases NetFlix sends an "Image Not Available" image.


Version 1.1
====================
- NetFlix modified their pages, plugin was updated to fix problems because of this.
- Added MPAA Rating as {mpaa} variable.

Version 1.2
====================
- Updated plugin to be 2.0 compatible

