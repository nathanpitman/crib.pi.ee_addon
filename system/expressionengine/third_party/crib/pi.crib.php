<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name'			=> 'Crib',
	'pi_version'		=> '0.1',
	'pi_author'			=> 'Nathan Pitman',
	'pi_author_url'		=> 'http://ninefour.co.uk/labs/',
	'pi_description'	=> 'Cribs content from any URL or a number of URLs and inserts it as a channel entry.',
	'pi_usage'			=> Crib::usage()
);

/**
 * Crib Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Nathan Pitman
 * @copyright		Copyright (c) 2011, Nine Four Ltd.
 * @link			http://ninefour.co.uk/labs/crib/
 */

class Crib {

	var $return_data;
	
	/**
	 * Constructor
	 *
	 */
	function Crib($str = '')
	{
		$this->EE =& get_instance();
		
		// Get params from the template
		$params = array();
		$params['url_list_path'] = $this->EE->TMPL->fetch_param('url_list_path');
		$params['channel_id'] = $this->EE->TMPL->fetch_param('channel_id');
		$params['category_ids'] = $this->EE->TMPL->fetch_param('category_ids');
		
		$category_ids_array = array();
		$category_ids = explode("|", $params['category_ids']);
		foreach($category_ids AS $category_id) {
			$category_ids_array[] = $category_id;
		}
		
		// Defaults
		$entry = array();
		$entry['title'] = '';
		$entry['entry_date'] = time();
		$entry['field_id_23'] = '';
		$entry['field_ft_23'] = 'xhtml';
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$url_list = file_get_contents($params['url_list_path']);
		$urls = preg_split("/\n/", $url_list);
		
		// remove duplicates
		$urls = array_unique($urls);
		
		$count = 0;
		
		foreach($urls AS $url) {
		
			if ($this->verify_ext($url)) {
				// Get file content
				$content = file_get_contents($url);
	    		$data = $this->parse($content);
	    		
	    		// inject category data
	    		$data['category'] = $category_ids_array;

				// insert the entry into the DB
				$this->EE->load->library('api');
				$this->EE->api->instantiate('channel_entries');
		
				$imported_ids[] = array();
		
				if ($this->EE->api_channel_entries->submit_new_entry($params['channel_id'], $data) === FALSE) {
					show_error('An Error Occurred Creating the Entry for URL "'.$url.'"');
				} else {
    				$imported_ids[] = $this->EE->api_channel_entries->entry_id;
    				$count++;
				} 
			}
		
		}

		$this->return_data = "<p>Imported ".$count." entries!</p>";
		
	}
	
	private function verify_ext($url) {
	
		$valid_exts = array("html","htm","php","jsp","asp","aspx");
		
		$path = parse_url($url, PHP_URL_PATH);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
	
		if (in_array($ext, $valid_exts)) {
			return TRUE;
		} else {
			return FALSE;
		}
	
	}
	
	private function isValidTimestamp($variable)
	{
		if (is_int($variable)) { 
		    if (strlen($variable) >= 10) { // it's a timestamp 
		        return TRUE;
		    } else { // it's normal integer 
		    	return FALSE;
		    }
		} else {
			return FALSE;
		}
	}
	
	private function parse($content) {
	
		// parse returns an array of data from a page, this consists of:
		// title
		// body
		// date
		
		$data = array();
		$data['title'] = '';
		$data['entry_date'] = '1256953732';
		$data['field_id_23'] = '';
		$data['field_ft_23'] = 'xhtml';
		
		$match = preg_split('/<div class="clsRightColumn">/', $content); 
		$match = preg_split('/<\/div>/', $match[1]); 
        
        // Remove the unwanted heading
        $match = preg_replace('/<h1 class="clsPageNumber">(.*?)<\/h1>/', '', $match[0]); 
		
		// Split on remaining H1
		$match = preg_split('/<h1>/', $match);
		$match = preg_split('/<\/h1>/', $match[1]);
		
        $data['title'] = trim($match[0]);
        $data['field_id_23'] = trim($match[1]);
	
		// Find the date if it's in the last para
		$date_string = strip_tags(trim($match[1]));
		$date_string = substr($date_string, -8, 8);
		
		list($day, $month, $year) = explode('/', $date_string); 
		$us_format_date = $month.'/'.$day.'/'.$year; 
		$unixdatetime = strtotime($us_format_date);

		if ($this->isValidTimestamp($unixdatetime)) {
			$data['entry_date'] = $unixdatetime;
		}
	
		return $data;
	
	}

	// --------------------------------------------------------------------
	
	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string
	 */
	function usage()
	{
		ob_start(); 
		?>
			This plugin cribs content from one or more URLs provided in a plain text file and inserts the collected data as channel entries.

			To use this plugin, wrap anything you want to be processed by it between these tag pairs:

			{exp:xml_encode}

			text you want processed

			{/exp:xml_encode}

			Note: Because quotes are converted into &quot; by this plugin, you cannot use
			ExpressionEngine conditionals inside of this plugin tag.

			If you have existing entities in the text that you do not wish to be converted, you may use
			the parameter protect_entities="yes", e.g.:

			{exp:xml_encode}Text &amp; Entities{/exp:xml_encode}

			results in: Text &amp;amp; Entities

			{exp:xml_encode protect_entities="yes"}Text &amp; Entities{/exp:xml_encode}

			results in: Text &amp; Entities
	
			Version 1.3
		******************
		- Updated plugin to be 2.0 compatible

		<?php
		$buffer = ob_get_contents();
	
		ob_end_clean(); 

		return $buffer;
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file pi.crib.php */
/* Location: ./system/expressionengine/plugins/pi.crib.php */