<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//http://a408.g.akamai.net/f/408/1284/24h/image.netflix.com/NetFlix_Assets/boxshots/small/
$plugin_info = array(
						'pi_name'			=> 'NetFlix',
						'pi_version'		=> '1.2',
						'pi_author'			=> 'Paul Burdick',
						'pi_author_url'		=> 'http://www.expressionengine.com/',
						'pi_description'	=> 'Retrieves Queue of Specified NetFlix Account',
						'pi_usage'			=> Netflix::usage()
					);
					

/**
 * Netfllix Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			ExpressionEngine Dev Team
 * @copyright		Copyright (c) 2004 - 2009, EllisLab, Inc.
 * @link			http://expressionengine.com/downloads/details/netflix/
 */

Class Netflix {

	var $cache_name		= 'netflix_cache';									// Name of cache directory
	var $cache_refresh	= 1440;												// Period between cache refreshes (in minutes)
	var $cache_data		= '';												// Data from cache file
	var $cache_path		= '';												// Path to cache file.
	var $cache_tpath	= '';												// Path to cache file's time file.
	
	var $netflix_url	= 'http://www.netflix.com/Queue';					// URL for retrieving Queue
	var $netflix_id		= '';												// ID for NetFlix account
	
	var $items			= array();											// Items Data
	var $return_data	= ''; 												// Data sent back to Template parser


	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */

    function Netflix()
    {
    	$this->EE =& get_instance();

    	// -------------------------------
    	//  Set Parameters 
    	// -------------------------------
    	
    	$this->cache_refresh 	= ( ! $this->EE->TMPL->fetch_param('refresh'))	? $this->cache_refresh : $this->EE->TMPL->fetch_param('refresh');
    	$this->netflix_id 		= ( ! $this->EE->TMPL->fetch_param('id'))			? '' : $this->EE->TMPL->fetch_param('id');
    	$which					= ( ! $this->EE->TMPL->fetch_param('which'))		? 'queue' : $this->EE->TMPL->fetch_param('which');
    	$limit					= ( ! $this->EE->TMPL->fetch_param('limit'))		? 500 : $this->EE->TMPL->fetch_param('limit');
    	$template				= $this->EE->TMPL->tagdata;
    	
    	if ($this->netflix_id == '')
    	{
    		return $this->return_data;
    	}
    	
    	// -------------------------------
    	//  Check and Retrive Cache
    	// -------------------------------
    	
    	$this->cache_path	= APPPATH.'cache/'.$this->cache_name.'/'.md5($this->netflix_id);
        $this->cache_tpath	= $this->cache_path.'_t';
        
        // -------------------------------
        // Retrieve NetFlix Data
        // -------------------------------
        
    	$this->netflix_cache();
    	
    	if ($this->cache_data == '')
    	{
    		return;
    	}
    	
    	// Remove line breaks and tabs
    	$this->cache_data = preg_replace("/(\r\n)|(\r)|(\n)|(\t)/", ' ', $this->cache_data);
    	
    	// -------------------------------
    	//  Which data do we parse out?
    	// -------------------------------
    	
    	if ($which == 'queue')
    	{
    		$this->parse_data('queue');
    	}
    	elseif ($which == 'out')
    	{
    		$this->parse_data('out');
		}
		
		// Likely got no information
		if (count($this->items) == 0)
		{
			return;
		}
		
		// -----------------------------------
		//  Parse Template
		// -----------------------------------
		
		if (preg_match("/".LD."items".RD."(.*?)".LD.SLASH.'items'.RD."/s", $template, $matches))
        {
        	$items_data = '';
        	
        	$limit = ($limit < count($this->items)) ? $limit : count($this->items);
        	
        	for($i=1; $i < $limit+1; $i++)
        	{
        		$temp_data = $matches['1'];
        		
        		foreach($this->items[$i] as $key => $value)
        		{
        			$temp_data = str_replace(LD.$key.RD,($key == 'rating') ? $value : $this->EE->regex->xml_convert($value),$temp_data);
        		}
        		
        		$items_data .= $temp_data;
        	}
        	
        	// --------------------------------
        	// Backspace Parameter
        	// --------------------------------
        	
        	if ($this->EE->TMPL->fetch_param('backspace'))
			{            		
				$items_data = substr(trim($items_data), 0, - $this->EE->TMPL->fetch_param('backspace'));
			}
			
        	$template = str_replace($matches['0'],$items_data,$template);
      	}
      	
      	// Total Items returned
      	$template = str_replace(LD.'total'.RD,$limit,$template);
		
		$this->return_data = &$template;		
	}
	// END


/**
* Netflix cache
*
* Check for expired cache
*
* @access   public
* @return   mixed
*/

    function netflix_cache()
    {
        
        // --------------------------
        // Check Cache
        // --------------------------
        
        if ( ! is_dir(APPPATH.'cache/'.$this->cache_name))
        {
        	if ( ! @mkdir(APPPATH.'cache/'.$this->cache_name, 0777))
        	{
        		return false;
        	}
        }
        
        @chmod(APPPATH.'cache/'.$this->cache_name, 0777);
        
        	
        if ( ! file_exists($this->cache_path) ||  ! file_exists($this->cache_tpath))
        {
        	$this->retrieve_data();
        }
        else
        {
        	$fp = @fopen($this->cache_tpath, 'r+b');
        	$sp = @fopen($this->cache_path, 'r+b');
        	
        	@chmod($this->cache_path, 0777);
        	@chmod($this->cache_tpath, 0777);
        	
        	if ( ! is_resource($fp) ||  ! is_resource($sp))
        	{
        		return false;
        	}
        	else
        	{
        		flock($fp, LOCK_SH);
        		$timestamp = trim(@fread($fp, filesize($this->cache_tpath)));
        		flock($fp, LOCK_UN);
        		fclose($fp);
        		
        		if (time() > ($timestamp + ($this->cache_refresh * 60)))
        		{
           			$this->retrieve_data();  
        		}
        		else
        		{
        			flock($sp, LOCK_SH);
        			$this->cache_data = trim(@fread($sp, filesize($this->cache_path)));
        			flock($sp, LOCK_UN);
        			fclose($sp);
        		}
        	}
        }	
        
		return true;
        
    }
    // END

/**
* Function Name
*
* Retreive NetFlix HTML File
*
* @access   public
* @return   string
*/

    function retrieve_data()
    {	
    	$cookies = "Cookie: NetflixShopperId={$this->netflix_id};\r\n";
			
    	$target = parse_url($this->netflix_url);
			
		$fp = @fsockopen($target['host'], 80, $errno, $errstr, 15);
			
		if (! is_resource($fp))
		{
			return false;
		}
			
		// Send the request
		fputs ($fp,"GET " . $this->netflix_url . " HTTP/1.0\r\n" ); 
		fputs ($fp,"Host: " . $target['host'] . "\r\n" ); 
		fputs ($fp, $cookies);
		fputs ($fp,"User-Agent: NetflixCheck/1.0\r\n\r\n");
			
		$getting_headers = true;
			
		while ( ! feof($fp))
		{
			$line = fgets($fp, 4096);
			
			if ($getting_headers == false)
			{
				$this->cache_data .= $line;
			}
			elseif (trim($line) == '')
			{
				$getting_headers = false;
			}
		}
	
		@fclose($fp); 
    	
    	// -----------------------------------
    	// Put into cache file
    	// -----------------------------------
    		
    	if ($this->cache_data != '')
    	{	
			// Write Cache Time File
    		if ($fp = @fopen($this->cache_tpath, 'wb'))
    		{
    			flock($fp, LOCK_SH);
        		fwrite($fp, time());
        		flock($fp, LOCK_UN);
        		fclose($fp);
    		}
    			
    		// Write Cache File
    		if ($fp = @fopen($this->cache_path, 'wb'))
    		{
    			flock($fp, LOCK_SH);
        		fwrite($fp, $this->cache_data);
        		flock($fp, LOCK_UN);
        		fclose($fp);
    		}
    			
    		@chmod($this->cache_path, 0777);
        	@chmod($this->cache_tpath, 0777);
    			
			return true;
			
		}   
		
		return false;
    }
    // END
	
/**
* Parse date
*
* @access   public
* @param    string
* @return   void
*/

    function parse_data($which='queue')
    {
    	$tag = ($which == 'queue') ? 'bd' : 'or';
    	
    	if (preg_match("|var\s+STARBAR\_IMG\_ROOT\s+\=\s+\'(.*?)\'\;|", $this->cache_data, $base_match))
    	{
    		$star_base = trim($base_match['1']);
    	}
    	else
    	{
    		$star_base = 'http://cdn.nflximg.com/us/pages/widget/';
    	}
    	
		if (preg_match_all("/\<tr class\=".$tag."\>(.+?)\<\/tr\>/", $this->cache_data, $matches))
		{
			for($i=0; $i < count($matches['0']); $i++)
			{
				if (preg_match_all("/\<td.+?\>(.+?)\<\/td\>/", $matches['1'][$i], $matches2))
				{
					//print_r($matches2);
				
					for($t=1; $t < count($matches2['1']); $t++)
					{
						switch($t)
						{
							case '1':
								// Link, title, and item_id									
								if (preg_match("/\<a href\=\"((.+?)movieid=(.+?))\"\>(.+?)\<\/a\>/", $matches2['1'][$t], $matches3))
								{
									$this->items[$i+1]['link']		=  $matches3['1'];
									$this->items[$i+1]['movie_id'] 	=  $matches3['3'];
									$this->items[$i+1]['title']		=  $matches3['4'];								
								}
								else
								{
									continue;
								}
							break;
							case '2':
								// Rating
								
								if (preg_match("|StarbarInsert\((.*?)\)\;|", $matches2['1'][$t], $rmatch))
								{
									// <script><!-- StarbarInsert(952839,1,3.0,3.0,0,0,0,1,0,0,0); // --></script>
									// http://cdn.nflximg.com/us/pages/widget/stars_1_32.gif
									
									$star_params = explode(',', $rmatch['1']);
									
									$this->items[$i+1]['rating'] = '<img src="'.$star_base.'stars_1_'.($star_params['2']*10).'.gif" />';								
								}
								else
								{
									$this->items[$i+1]['rating'] = $matches2['1'][$t];
								}
								
															
							break;
							case '3':
								// MPAA
								
								$this->items[$i+1]['mpaa'] = $matches2['1'][$t];
								
							break;
							case '4':
								// Genre
								$this->items[$i+1]['genre'] = $matches2['1'][$t];
							break;
						}
					}
				}			
			}			
		}
	}
    // END
    
    
    
	// ----------------------------------------
	//  Plugin Usage
	// ----------------------------------------

	function usage()
	{
		ob_start(); 
		?>

		STEP ONE:
		Become a memember of NetFlix, and then look in your browser cookies to discover the 'NetflixShopperId' cookies value for '.netflix.com'.

		STEP TWO:
		Insert plugin tag into your template.

		PARAMETERS: 
		The tag has three parameters:

		1. id - The NetflixShopperId for the NetFlix account.  Look in your cookies for this value

		2. limit - Number of items to display. Default is to display all items returned.

		3. refresh - How often to update the cache file in minutes. The default is to update the cache file once a day.

		4. which - Which list of items to retrieve.  There are two options 'queue' (items in the queue) or 'out' (items currently out).  Default is queue.

		Example tag:  {exp:netflix id="P1152183453382851739665891469694171" limit="8" refresh="720"}

		SINGLE VARIABLES:

		{total} - Total items returned

		PAIR VARIABLES:

		Only one pair variable, {items}, is available, and it is for the items returned from the NetFlix queue.

		This pair variable has the following single variables:

		{movie_id} - NetFlix id for DVD 
		{title} - Title of DVD
		{link} - URL to details about DVD
		{genre} - Genre of DVD
		{rating} - NetFlix's Rating of DVD
		{mpaa}	- MPAA Rating of Film

		EXAMPLE:

		{exp:netflix id="P1152183453382851739665891469694171"}
		<ul>
		{items}
		<li><a href="{link}">{title}</a> in {genre} (Rating: {rating})</li>
		{/items}
		</ul>
		{/exp:netflix}


		NEAT-O:  Using the NetFlix movie id you can link to images for the DVDs.  Example:

		<img src="http://a408.g.akamai.net/f/408/1284/24h/image.netflix.com/NetFlix_Assets/boxshots/small/{movie_id}.jpg" width="65" height="90" />

		I have found that some DVDs when part of a series of discs may not have an image available this way.  In these cases NetFlix sends an "Image Not Available" image.


		Version 1.1
		====================
		- NetFlix modified their pages, plugin was updated to fix problems because of this.
		- Added MPAA Rating as {mpaa} variable.

		Version 1.2
		====================
		- Updated plugin to be 2.0 compatible


		<?php
		$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file pi.netflix.php */
/* Location: ./system/expressionengine/third_party/netflix/pi.netflix.php */