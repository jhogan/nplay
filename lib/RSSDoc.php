<?php
// vim: set et ts=4 sw=4 fdm=marker:
class RSS2Doc{
    var $_channel;
    var $_dom;
    var $_rssElement;
    public function __construct($title, $link, $description){
        $this->_dom = new DOMDocument();
        $dom =& $this->_dom;
        $dom->formatOutput = true;

        $this->_rssElement = $dom->createElement('rss');
        $rssElement =& $this->_rssElement;

        $dom->appendChild($rssElement);

        $attr =& $dom->createAttribute('version');
        $rssElement->appendChild($attr);

        $attrVal = $dom->createTextNode('2.0');
        $attr->appendChild($attrVal);

        $this->_channel = $dom->createElement('channel');
        $rssElement->appendChild($this->_channel);

        $titleElement = $dom->createElement('title');
        $linkElement = $dom->createElement('link');
        $descriptionElement = $dom->createElement('description');

        $titleText = $dom->createTextNode($title);
        $linkText = $dom->createTextNode($link);
        $descriptionText = $dom->createTextNode($description);

        $titleElement->appendChild($titleText);
        $linkElement->appendChild($linkText);
        $descriptionElement->appendChild($descriptionText);

        $this->_channel->appendChild($titleElement);
        $this->_channel->appendChild($linkElement);
        $this->_channel->appendChild($descriptionElement);

    }
    public function &AddItem($title, $link, $description){
        $channel =& $this->_channel;
        $dom =& $this->_dom;
        $item = $dom->createElement('item');

        $titleElement = $dom->createElement('title');
        $linkElement = $dom->createElement('link');
        $descriptionElement = $dom->createElement('description');

        $titleText = $dom->createTextNode($title);
        $linkText = $dom->createTextNode($link);
        $descriptionText = $dom->createTextNode($description);

        $titleElement->appendChild($titleText);
        $linkElement->appendChild($linkText);
        $descriptionElement->appendChild($descriptionText);

        $item->appendChild($titleElement);
        $item->appendChild($linkElement);
        $item->appendChild($descriptionElement);

        $channel->appendChild($item);
    }
    public function ToString(){
        return $this->_dom->saveXML();
    }
}
?>
