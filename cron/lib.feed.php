<?php
// Get RSS/Atom infos
// Based on http://www.w3schools.com/rss/default.asp
// Based on http://atomenabled.org/developers/syndication/

// Basic objects
class Feed
{
	public $description = "";
	public $buildDate = "";
	public $url = "";
	public $title = "";
	public $date = "";
	public $image = null;
}

class FeedImage
{
	public $link = "";
	public $title = "";
	public $url = "";
}

class Item
{
	public $author = "";
	public $description = "";
	public $guid = "";
	public $url = "";
	public $date = "";
	public $title = "";
	public $enclosure = null;
}

class ItemEnclosure
{
	public $length = "";
	public $type = "";
	public $url = "";
}

// RSS/Atom Loader
class FeedLoader
{
	private $dom;
	private $feed;
	private $items;
	
	public function __construct()
	{
		$this->dom = new DomDocument();
		$this->feed = new Feed();
		$this->items = array();
	}
	
	// Load the feed
	// Returns: bool
	public function load($url)
	{
		// Load the URL
		if(!$this->dom->load($url))
			return false;
		
		// Load RSS
		$rss = $this->get_xml_element($this->dom, "rss");
		if($rss && $this->loadRss($rss))
			return true;
		
		// Load Atom
		$atom = $this->get_xml_element($this->dom, "feed");
		if($atom && $this->loadAtom($atom))
			return true;
		
		return false;
	}
	
	// Get the channel infos
	// Returns: Feed
	public function getFeed()
	{
		return $this->feed;
	}
	
	// Get the items
	// Returns: array(Item)
	public function getItems()
	{
		return $this->items;
	}
	
	////////////////////////////////////////////////////////////////////
	// Load the RSS feed
	// Returns: bool
	private function loadRss($parent)
	{
		// Get the channel
		$channel = $this->get_xml_element($parent, "channel");
		if(!$channel)
			return false;
		
		// Get the channel elements
		$this->feed->description = $this->get_xml_value($channel, "description", null);
		$this->feed->buildDate = $this->get_xml_value($channel, "lastBuildDate");
		$this->feed->url = $this->get_xml_value($channel, "link", null);
		$this->feed->title = $this->get_xml_value($channel, "title", null);
		$this->feed->date = $this->get_xml_value($channel, "pubDate");
		
		// Check required
		if(($this->feed->description === null) || ($this->feed->url === null) || ($this->feed->title === null))
			return false;
		
		// Check values
		$this->feed->date = $this->feed->date ? strtotime($this->feed->date) : time();
		$this->feed->buildDate = $this->feed->buildDate ? strtotime($this->feed->buildDate) : time();
		
		// Get image
		$image = $this->get_xml_element($channel, "image");
		if($image)
		{
			$tmp_image = new FeedImage();
			
			$tmp_image->link = $this->get_xml_value($image, "link", null);
			$tmp_image->title = $this->get_xml_value($image, "title", null);
			$tmp_image->url = $this->get_xml_value($image, "url", null);
			
			// Check required
			if(($tmp_image->link !== null) && ($tmp_image->title !== null) && ($tmp_image->url !== null))
				$this->feed->image = $tmp_image;
		}
		
		// Load the items
		$items = $channel->getElementsByTagName("item");
		foreach($items as $item)
		{
			$tmp_item = new Item();
			
			// Get the item elements
			$tmp_item->author = $this->get_xml_value($item, "author");
			$tmp_item->description = $this->get_xml_value($item, "description", null);
			$tmp_item->guid = $this->get_xml_value($item, "guid");
			$tmp_item->url = $this->get_xml_value($item, "link", null);
			$tmp_item->date = $this->get_xml_value($item, "pubDate");
			$tmp_item->title = $this->get_xml_value($item, "title", null);
			
			// Check required
			if(($tmp_item->description === null) || ($tmp_item->url === null) || ($tmp_item->title === null))
				continue;
			
			// Check values
			$tmp_item->date = $tmp_item->date ? strtotime($tmp_item->date) : time();
			
			// Get enclosure
			$enclosure = $this->get_xml_element($item, "enclosure");
			if($enclosure)
			{
				$tmp_enclosure = new ItemEnclosure();
				
				$tmp_enclosure->length = $enclosure->getAttribute("length");
				$tmp_enclosure->type = $enclosure->getAttribute("type");
				$tmp_enclosure->url = $enclosure->getAttribute("url");
				
				// Check required
				if($tmp_enclosure->length && $tmp_enclosure->type && $tmp_enclosure->url)
					$tmp_item->enclosure = $tmp_enclosure;
			}
			
			array_push($this->items, $tmp_item);
		}
		
		return true;
	}
	
	// Load the Atom feed
	// Returns: bool
	private function loadAtom($parent)
	{
		// Get the channel elements
		$this->feed->description = $this->get_xml_value($parent, "subtitle");
		$this->feed->buildDate = $this->get_xml_value($parent, "updated");
		$this->feed->title = $this->get_xml_value($parent, "title", null);
		$this->feed->date = $this->get_xml_value($parent, "updated", null);
		
		// Get the url
		$links = $this->get_xml_elements($parent, "link");
		foreach($links as $link)
		{
			if($link->getAttribute("rel") == "alternate")
			{
				$this->feed->url = $link->getAttribute("href");
				break;
			}
		}
		if(!$this->feed->url)
			$this->feed->url =  $this->get_xml_value($parent, "id", null);
		
		// Check required
		if(($this->feed->date === null) || ($this->feed->url === null) || ($this->feed->title === null))
			return false;
		
		// Check values
		$this->feed->date = $this->feed->date ? strtotime($this->feed->date) : time();
		$this->feed->buildDate = $this->feed->buildDate ? strtotime($this->feed->buildDate) : time();
		
		// Load the items
		$items = $this->get_xml_elements($parent, "entry");
		foreach($items as $item)
		{
			$tmp_item = new Item();
			
			// Get the item elements
			$tmp_item->guid = $this->get_xml_value($item, "id", null);
			$tmp_item->date = $this->get_xml_value($item, "published", null);
			$tmp_item->title = $this->get_xml_value($item, "title", null);
			
			// Get the description
			$tmp_item->description = $this->get_xml_value($item, "content");
			if(!$tmp_item->description)
				$tmp_item->description = $this->get_xml_value($item, "summary");
			
			// Get the author
			$author = $this->get_xml_element($item, "author");
			if($author && ($author_name = $this->get_xml_value($author, "name")))
				$tmp_item->author = $author_name;
			
			// Get the url
			$links = $this->get_xml_elements($item, "link");
			foreach($links as $link)
			{
				if($link->getAttribute("rel") == "alternate")
				{
					$tmp_item->url = $link->getAttribute("href");
					break;
				}
			}
			
			// Check required
			if(($tmp_item->date === null) || ($tmp_item->guid === null) || ($tmp_item->title === null))
				continue;
			
			// Check values
			$tmp_item->date = $tmp_item->date ? strtotime($tmp_item->date) : time();
			
			// Get enclosure
			foreach($links as $link)
			{
				if($link->getAttribute("rel") == "enclosure")
				{
					$tmp_enclosure = new ItemEnclosure();
					
					$tmp_enclosure->length = $link->getAttribute("length");
					$tmp_enclosure->type = $link->getAttribute("type");
					$tmp_enclosure->url = $link->getAttribute("href");
					
					// Check required
					if($tmp_enclosure->length && $tmp_enclosure->type && $tmp_enclosure->url)
						$tmp_item->enclosure = $tmp_enclosure;
					
					break;
				}
			}
			
			array_push($this->items, $tmp_item);
		}
		
		return true;
	}
	
	////////////////////////////////////////////////////////////////////
	// Get all XML elements
	// Returns: array(DomElement)
	private function get_xml_elements($parent, $elements_name)
	{
		$result = array();
		
		if(!$parent)
			return $result;
		
		// Get the elements
		$elements = $parent->getElementsByTagName($elements_name);
		if(!$elements || !$elements->length)
			return $result;
		
		// Get the child elements
		foreach($elements as $element)
			if($element->parentNode == $parent)
				array_push($result, $element);
		
		return $result;
	}
	
	// Get a single XML element
	// Returns: DomElement
	private function get_xml_element($parent, $element_name)
	{
		$elements = $this->get_xml_elements($parent, $element_name);
		return count($elements) ? $elements[0] : null;
	}
	
	// Get a single XML element value
	// Returns: string
	private function get_xml_value($parent, $element_name, $default_value = "")
	{
		$element = $this->get_xml_element($parent, $element_name);
		return $element ? $element->nodeValue : $default_value;
	}
}
?>
