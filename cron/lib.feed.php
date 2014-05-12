<?php
// Get RSS/Atom infos
// Based on http://www.w3schools.com/rss/default.asp

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
	public $comments = "";
	public $description = "";
	public $guid = "";
	public $url = "";
	public $date = "";
	public $source = "";
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
		$atom = $this->get_xml_element($this->dom, "atom");
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
		$this->feed->description = $this->get_xml_value($channel, "description");
		$this->feed->buildDate = $this->get_xml_value($channel, "lastBuildDate");
		$this->feed->url = $this->get_xml_value($channel, "link");
		$this->feed->title = $this->get_xml_value($channel, "title");
		$this->feed->date = $this->get_xml_value($channel, "pubDate");
		
		// Check required
		if(!$this->feed->description || !$this->feed->url || !$this->feed->title)
			return false;
		
		// Check values
		$this->feed->date = $this->feed->date ? strtotime($this->feed->date) : time();
		$this->feed->buildDate = $this->feed->buildDate ? strtotime($this->feed->buildDate) : time();
		
		// Get image
		$image = $this->get_xml_element($channel, "image");
		if($image)
		{
			$tmp_image = new FeedImage();
			
			$tmp_image->link = $this->get_xml_value($image, "link");
			$tmp_image->title = $this->get_xml_value($image, "title");
			$tmp_image->url = $this->get_xml_value($image, "url");
			
			// Check required
			if($tmp_image->link && $tmp_image->title && $tmp_image->url)
				$this->feed->image = $tmp_image;
		}
		
		// Load the items
		$items = $channel->getElementsByTagName("item");
		foreach($items as $item)
		{
			$tmp_item = new Item();
			
			// Get the item elements
			$tmp_item->author = $this->get_xml_value($item, "link");
			$tmp_item->comments = $this->get_xml_value($item, "comments");
			$tmp_item->description = $this->get_xml_value($item, "description");
			$tmp_item->guid = $this->get_xml_value($item, "guid");
			$tmp_item->url = $this->get_xml_value($item, "link");
			$tmp_item->date = $this->get_xml_value($item, "pubDate");
			$tmp_item->source = $this->get_xml_value($item, "source");
			$tmp_item->title = $this->get_xml_value($item, "title");
			
			// Check required
			if(!$tmp_item->description || !$tmp_item->url || !$tmp_item->title)
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
		// TODO
		return false;
	}
	
	////////////////////////////////////////////////////////////////////
	// Get a single XML element
	// Returns: DomElement
	private function get_xml_element($parent, $element_name)
	{
		if(!$parent)
			return null;
		
		// Get the elements
		$elements = $parent->getElementsByTagName($element_name);
		if(!$elements || !$elements->length)
			return "";
		
		// Get the first child value
		foreach($elements as $element)
			if($element->parentNode == $parent)
				return $element;
		
		return null;
	}
	
	// Get a single XML element value
	// Returns: string
	private function get_xml_value($parent, $element_name)
	{
		$element = $this->get_xml_element($parent, $element_name);
		return $element ? $element->nodeValue : "";
	}
}
?>
