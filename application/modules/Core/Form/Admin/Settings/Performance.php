<?php
/**
 * SocialEngine
 *
 * @category   Application_Core
 * @package    Core
 * @copyright  Copyright 2006-2010 Webligo Developments
 * @license    http://www.socialengine.com/license/
 * @version    $Id: Performance.php 9820 2012-11-15 05:03:31Z matthew $
 * @author     John
 */

/**
 * @category   Application_Core
 * @package    Core
 * @copyright  Copyright 2006-2010 Webligo Developments
 * @license    http://www.socialengine.com/license/
 */
class Core_Form_Admin_Settings_Performance extends Engine_Form
{
  public function init()
  {
  
    $description = $this->getTranslator()->translate(
        'For very large social networks, it may be necessary to enable caching to improve performance. If there is a noticable decrease in performance on your social network, consider enabling caching below (or upgrading your hardware). Caching takes some load off the database server by storing commonly retrieved data in temporary files (file-based caching) or memcached (memory-based caching). If you are not familiar with caching, we do not recommend that you change any of these settings. <br>');
		
	$settings = Engine_Api::_()->getApi('settings', 'core');
	
	if( $settings->getSetting('user.support.links', 0) == 1 ) {
	  $moreinfo = $this->getTranslator()->translate(
        'More Info: <a href="%1$s" target="_blank"> KB Article</a>');
	} else {
	  $moreinfo = $this->getTranslator()->translate( 
        '');
	}
	
    $description = vsprintf($description.$moreinfo, array(
      'http://support.socialengine.com/questions/182/Admin-Panel-Settings-Performance-Caching',
    ));
	
	// Decorators
    $this->loadDefaultDecorators();
	$this->getDecorator('Description')->setOption('escape', false);

    $this
      ->setTitle('Email All Members')
      ->setDescription($description);
  
    // Set form attributes
    $this->setTitle('Performance & Caching');
    $this->setDescription($description);

    // disable form if not in production mode
    $attribs = array();
    if (APPLICATION_ENV != 'production') {
      $attribs = array('disabled' => 'disabled', 'readonly' => 'readonly');
      $this->addError('Note: Caching is disabled when your site is in development mode. Your site must be in production mode to modify the settings below.');
    }

    $this->addElement('Radio', 'enable', array(
      'label' => 'Use Cache?',
      'description' => strtoupper(get_class($this) . '_enable_description'),
      'required' => true,
      'multiOptions' => array(
        1 => 'Yes, enable caching.',
        0 => 'No, do not enable caching.',
      ),
      'attribs' => $attribs,
    ));

    $this->addElement('Text', 'lifetime', array(
      'label' => 'Cache Lifetime',
      'description' => strtoupper(get_class($this) . '_lifetime_description'),
      'size' => 5,
      'maxlength' => 4,
      'required' => true,
      'allowEmpty' => false,
      'validators' => array(
        array('NotEmpty', true),
        array('Int'),
      ),
      'attribs' => $attribs,
    ));

    $typeDescription = $this->getTranslator()->translate(strtoupper(get_class($this) . '_type_description'));
    $typeDescription .= vsprintf(' See <a href="%1$s" target="_blank"> KB Article</a> and contact your hosting provider for assistance configuring memory-based caching.', array(
      'http://support.socialengine.com/php/customer/portal/articles/1639869-admin-panel---settings-&amp;gt;-performance-caching',
    ));
    $this->addElement('Radio', 'type', array(
      'label' => 'Caching Feature',
      'description' => $typeDescription,
      'required' => true,
      'allowEmpty' => false,
      'multiOptions' => array(
        'File'      => 'File-based',
        'Memcached' => 'Memcache',
        'Apc'       => Engine_Server_Php::isMinimum(Engine_Server_Php::PHP_VERSION_5_6) ? 'APCu' : 'APC',
        'Xcache'    => 'Xcache',
      ),//Zend_Cache::$standardBackends,
      'onclick' => 'updateFields();',
      'attribs' => $attribs,
    ));
    $this->type->getDecorator('Description')->setOption('escape', false);
    $this->type->setAttrib('escape', false);

    $this->addElement('Text', 'file_path', array(
      'label' => 'File-based Cache Directory',
      'description' => strtoupper(get_class($this) . '_file_path_description'),
      'attribs' => $attribs,
    ));

    $this->addElement('Checkbox', 'file_locking', array(
      'label' => 'File locking?',
      'attribs' => $attribs,
    ));

    $this->addElement('Text', 'memcache_host', array(
      'label' => 'Memcache Host',
      'description' => 'Can be a domain name, hostname, or an IP address (recommended)',
      'attribs' => $attribs,
    ));

    $this->addElement('Text', 'memcache_port', array(
      'label' => 'Memcache Port',
      'attribs' => $attribs,
    ));

    $this->addElement('Checkbox', 'memcache_compression', array(
      'label' => 'Memcache compression?',
      'title' => 'Title?',
      'description' => 'Compression will decrease the amount of memory used, however will increase processor usage.',
      'attribs' => $attribs,
    ));


    $this->addElement('Text', 'xcache_username', array(
      'label' => 'Xcache Username',
      'attribs' => $attribs,
    ));

    $this->addElement('Text', 'xcache_password', array(
      'label' => 'Xcache Password',
      'attribs' => $attribs,
    ));

    $this->addElement('Checkbox', 'flush', array(
      'label' => 'Flush cache?',
      'attribs' => $attribs,
    ));
    
    $this->addElement('Checkbox', 'translate_array', array(
      'label' => 'Convert Language Pack CSV files to a PHP Array? (Note: If this setting is already enabled then clicking on "Save Changes" button will regenerate PHP array based file based on the latest language phrases in csv files. This is useful for you in cases when you\'ve installed a new plugin or made some new phrase translations via Language Manager.)',
      'description' => 'Translation Performance',
    ));
    
    $this->addElement('Checkbox', 'gzip_html', array(
      'label' => 'Send HTML with Gzip compression?',
      'description' => 'HTML Compression',
    ));
    
    // init submit
    $this->addElement('Button', 'submit', array(
      'label' => 'Save Changes',
      'type' => 'submit',
      'ignore' => true,
      'attribs' => $attribs,
    ));

  }

  public function populate($current_cache=array()) {
    
    $enabled = true;
    if (isset($current_cache['frontend']['core']['caching']))
      $enabled = $current_cache['frontend']['core']['caching'];
    $this->getElement('enable')->setValue($enabled);

    $backend = Engine_Cache::getDefaultBackend();
    if (isset($current_cache['backend'])) {
      $backend = array_keys($current_cache['backend']);
      $backend = $backend[0];
    }
    $this->getElement('type')->setValue($backend);

    $file_path = $current_cache['default_file_path'];
    if (isset($current_cache['backend']['File']['cache_dir']))
      $file_path = $current_cache['backend']['File']['cache_dir'];
    $this->getElement('file_path')->setValue( $file_path );

    $file_locking = 1;
    if (isset($current_cache['backend']['File']['file_locking']))
      $file_locking = $current_cache['backend']['File']['file_locking'];
    $this->getElement('file_locking')->setValue( $file_locking );

    if( isset($current_cache['frontend']['core']['lifetime']) ){
      $lifetime = $current_cache['frontend']['core']['lifetime'];
    } else {
      $lifetime = 300; // 5 minutes
    }
    if (isset($current_cache['frontend']['core']['options']['lifetime']))
      $lifetime = $current_cache['frontend']['core']['options']['lifetime'];
    $this->getElement('lifetime')->setValue($lifetime);

    $memcache_host = '127.0.0.1';
    $memcache_port = '11211';
    $memcache_compression = 0;
    if (isset($current_cache['backend']['Memcache']['servers'][0]['host']))
      $memcache_host = $current_cache['backend']['Memcached']['servers'][0]['host'];
    if (isset($current_cache["backend"]["Memcached"]["servers"][0]["port"]))
      $memcache_port = $current_cache["backend"]["Memcached"]["servers"][0]["port"];
    if (isset($current_cache["backend"]["Memcached"]["compression"]))
      $memcache_compression = $current_cache["backend"]["Memcached"]["compression"];
    $this->getElement('memcache_host')->setValue($memcache_host);
    $this->getElement('memcache_port')->setValue($memcache_port);
    $this->getElement('memcache_compression')->setValue($memcache_compression);

    // Set Existing Value for Translation Performance checkbox
    $db = Engine_Db_Table::getDefaultAdapter();
    $initialTranslateAdapter = $db->select()
      ->from('engine4_core_settings', 'value')
      ->where('`name` = ?', 'core.translate.adapter')
      ->query()
      ->fetchColumn();
    if($initialTranslateAdapter == 'array') { $translate_array = 1;}
    else{ $translate_array = 0; }
    $this->getElement('translate_array')->setValue($translate_array);
    
    // Set Value for HTML Compression
    $gzip = FALSE;
    if( isset( $current_cache['frontend']['core']['gzip'] ) ) {
      $gzip = $current_cache['frontend']['core']['gzip'];
    }
    
    $this->getElement('gzip_html')->setValue($gzip);
    
  }
}