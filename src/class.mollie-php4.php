<?php
/*
  =======================================================================
   File:        class.mollie-php4.php
   Description: See sendsms.php, sendpremiumsms.php or sendmms.php for
                functionality.
   Created:     16-01-2005
   Author:      Mollie B.V.
   Version:     v 2.3-PHP4 17-06-2009 (long messages)

   More information? Go to www.mollie.nl
  ========================================================================
   Possible returns:
  ========================================================================
   10 - succesfully sent
   20 - no 'username' given
   21 - no 'password' given
   22 - no or incorrect 'originator' given
   23 - no 'recipients' given
   24 - no 'message' given
   25 - no juiste 'recipients' given
   26 - no juiste 'originator' given
   27 - no juiste 'message' given
   29 - wrong parameter(s)
   30 - incorrect 'username' or 'password'
   31 - not enough credits
   98 - gateway unreachable
   99 - unknown error
  ========================================================================
   Possible returns (Premium SMS):
  ========================================================================
   40 - invalid shortcode
   41 - invalid mid (MO Message ID)
   42 - mid not found
   43 - invalid tariff
   44 - invalid member (of subscription)
   45 - combination of username and keyword/shortcode not found
   46 - member of subscription reached maximum number of messages
  ========================================================================
*/

  class mollie {
    var $username          = null;
    var $password          = null;

    var $gateway           = 1;
    var $originator        = null;
    var $recipients        = array();
    var $reference         = null;
    var $type              = 'normal';

    var $premium_shortcode = null;
    var $premium_keyword   = null;
    var $premium_tariff    = null;
    var $premium_mid       = null;
    var $premium_member    = null;

    var $success           = false;
    var $successcount      = 0;
    var $resultcode        = null;
    var $resultmessage     = null;

    function setGateway($gateway) {
      $this->gateway = $gateway;
    }
    
    function setType($type) {
      if (in_array($type, array('normal', 'long'))) {
        $this->type = $type;
      }
    }

    function setLogin($username, $password) {
      $this->username = $username;
      $this->password = $password;
    }

    function setOriginator($originator) {
      $this->originator = $originator;
    }

    function addRecipients($recipient) {
      array_push($this->recipients, $recipient);
    }

    function setReference ($reference) {
      $this->reference = $reference;
    }

    public function setPremium ($shortcode, $keyword, $tariff, $mid = null, $member = false) {
      $this->premium_shortcode = $shortcode;
      $this->premium_keyword   = $keyword;
      $this->premium_tariff    = sprintf("%03s",$tariff);
      $this->premium_mid       = $mid;
      $this->premium_member    = ($member === true) ? 'true' : 'false';
    }

    public function getSuccess() {
      return $this->success;
    }

    public function getSuccessCount() {
      return $this->successcount;
    }

    public function getResultCode() {
      return $this->resultcode;
    }

    public function getResultMessage() {
      return $this->resultmessage;
    }

    function sendSMS($message) {
      $recipients = implode(',', $this->recipients);

      $result = $this->sendToHost('www.mollie.nl', '/xml/sms/',
                     'gateway=' . urlencode($this->gateway) .
                     '&username=' . urlencode($this->username) .
                     '&password=' . urlencode($this->password) .
                     '&originator=' . urlencode($this->originator) .
                     '&recipients=' . urlencode($recipients) .
                     '&type=' . urlencode($this->type) .
                     '&message=' . urlencode($message) .
                     (($this->reference !== null)         ? '&reference=' . urlencode($this->reference) : '') .
                     (($this->premium_shortcode !== null) ? '&shortcode=' . urlencode($this->premium_shortcode) : '') .
                     (($this->premium_keyword !== null)   ? '&keyword=' . urlencode($this->premium_keyword) : '') .
                     (($this->premium_tariff !== null)    ? '&tariff=' . urlencode($this->premium_tariff) : '') .
                     (($this->premium_mid !== null)       ? '&mid=' . urlencode($this->premium_mid) : '') .
                     (($this->premium_member !== null)    ? '&member=' . urlencode($this->premium_member) : '')
                     );

      $this->recipients = array();

      list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
      $this->XMLtoResult($xml);
    }

    function sendMMS($subject, $message, $attachment = null) {
      $recipients = implode(',', $this->recipients);

      $result = $this->sendToHost('www.mollie.nl', '/xml/mms/',
                     'gateway=' . urlencode($this->gateway) .
                     '&username=' . urlencode($this->username) .
                     '&password=' . urlencode($this->password) .
                     '&originator=' . urlencode($this->originator) .
                     '&recipients=' . urlencode($recipients) .
                     '&subject=' . urlencode($subject) .
                     '&message=' . urlencode($message) .
                     (($attachment !== null)      ? '&attachment[1]=' . urlencode($attachment) : '') .
                     (($this->reference !== null) ? '&reference=' . urlencode($this->reference) : '')
                  );

      $this->recipients = array();

      list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
      $this->XMLtoResult($xml);
    }

    function sendToHost($host,$path,$data) {
      $fp = @fsockopen($host,80);
      if ($fp) {
        @fputs($fp, "POST $path HTTP/1.0\n");
        @fputs($fp, "Host: $host\n");
        @fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
        @fputs($fp, "Content-length: " . strlen($data) . "\n");
        @fputs($fp, "Connection: close\n\n");
        @fputs($fp, $data);
        
        $buf = null;
        while (!feof($fp))
        $buf .= fgets($fp,128);
        fclose($fp);
      }
      return $buf;
    }

    function XMLtoResult ($xml) {
      $data = XML_unserialize($xml);

      $this->success       = ($data['response']['item']['success'] == 'true');
      $this->successcount  = $data['response']['item']['recipients'];
      $this->resultcode    = $data['response']['item']['resultcode'];
      $this->resultmessage = $data['response']['item']['resultmessage'];
    }

  }

###################################################################################
#
# XML Library, by Keith Devens, version 1.2b
# http://keithdevens.com/software/phpxml
#
# This code is Open Source, released under terms similar to the Artistic License.
# Read the license at http://keithdevens.com/software/license
#
###################################################################################

###################################################################################
# XML_unserialize: takes raw XML as a parameter (a string)
# and returns an equivalent PHP data structure
###################################################################################
function & XML_unserialize(&$xml){
  $xml_parser = &new XML();
  $data = &$xml_parser->parse($xml);
  $xml_parser->destruct();
  return $data;
}
###################################################################################
# XML_serialize: serializes any PHP data structure into XML
# Takes one parameter: the data to serialize. Must be an array.
###################################################################################
function & XML_serialize(&$data, $level = 0, $prior_key = NULL){
  if($level == 0){ ob_start(); echo '<?xml version="1.0"',"\n"; }
  while(list($key, $value) = each($data))
    if(!strpos($key, ' attr')) #if it's not an attribute
      #we don't treat attributes by themselves, so for an empty element
      # that has attributes you still need to set the element to NULL

      if(is_array($value) and array_key_exists(0, $value)){
        XML_serialize($value, $level, $key);
      }
      else{
        $tag = $prior_key ? $prior_key : $key;
        echo str_repeat("\t", $level),'<',$tag;
        if(array_key_exists("$key attr", $data)){ #if there's an attribute for this element
          while(list($attr_name, $attr_value) = each($data["$key attr"]))
            echo ' ',$attr_name,'="',htmlspecialchars($attr_value),'"';
          reset($data["$key attr"]);
        }

        if(is_null($value)) echo " />\n";
        elseif(!is_array($value)) echo '>',htmlspecialchars($value),"</$tag>\n";
        else echo ">\n",XML_serialize($value, $level+1),str_repeat("\t", $level),"</$tag>\n";
      }
  reset($data);
  if($level == 0){ $str = &ob_get_contents(); ob_end_clean(); return $str; }
}
###################################################################################
# XML class: utility class to be used with PHP's XML handling functions
###################################################################################
class XML{
  var $parser;   #a reference to the XML parser
  var $document; #the entire XML structure built up so far
  var $parent;   #a pointer to the current parent - the parent will be an array
  var $stack;    #a stack of the most recent parent at each nesting level
  var $last_opened_tag; #keeps track of the last tag opened.

  function XML(){
     $this->parser = &xml_parser_create();
    xml_parser_set_option(&$this->parser, XML_OPTION_CASE_FOLDING, false);
    xml_set_object(&$this->parser, &$this);
    xml_set_element_handler(&$this->parser, 'open','close');
    xml_set_character_data_handler(&$this->parser, 'data');
  }
  function destruct(){ xml_parser_free(&$this->parser); }
  function & parse(&$data){
    $this->document = array();
    $this->stack    = array();
    $this->parent   = &$this->document;
    return xml_parse(&$this->parser, &$data, true) ? $this->document : NULL;
  }
  function open(&$parser, $tag, $attributes){
    $this->data = ''; #stores temporary cdata
    $this->last_opened_tag = $tag;
    if(is_array($this->parent) and array_key_exists($tag,$this->parent)){ #if you've seen this tag before
      if(is_array($this->parent[$tag]) and array_key_exists(0,$this->parent[$tag])){ #if the keys are numeric
        #this is the third or later instance of $tag we've come across
        $key = count_numeric_items($this->parent[$tag]);
      }else{
        #this is the second instance of $tag that we've seen. shift around
        if(array_key_exists("$tag attr",$this->parent)){
          $arr = array('0 attr'=>&$this->parent["$tag attr"], &$this->parent[$tag]);
          unset($this->parent["$tag attr"]);
        }else{
          $arr = array(&$this->parent[$tag]);
        }
        $this->parent[$tag] = &$arr;
        $key = 1;
      }
      $this->parent = &$this->parent[$tag];
    }else{
      $key = $tag;
    }
    if($attributes) $this->parent["$key attr"] = $attributes;
    $this->parent  = &$this->parent[$key];
    $this->stack[] = &$this->parent;
  }
  function data(&$parser, $data){
    if($this->last_opened_tag != NULL) #you don't need to store whitespace in between tags
      $this->data .= $data;
  }
  function close(&$parser, $tag){
    if($this->last_opened_tag == $tag){
      $this->parent = $this->data;
      $this->last_opened_tag = NULL;
    }
    array_pop($this->stack);
    if($this->stack) $this->parent = &$this->stack[count($this->stack)-1];
  }
}
function count_numeric_items(&$array){
  return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
}
?>
