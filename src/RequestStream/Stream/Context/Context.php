<?php


namespace RequestStream\Stream\Context;

use RequestStream\Stream\StreamAbstract;

/**
 * Abstract core for control socket and stream
 */
class Context extends StreamAbstract  implements ContextInterface {
  
  /**
   * Construct
   */
  public function __construct()
  {
    
  }
  
  /**
   * Get default context
   *
   * @param array [$options]
   *    Default options for context
   *
   * @return resource
   *    Resource of context
   */
  public static function getDefault(array $options = array())
  {
    return stream_context_get_default($options);
  }
  
  /**
   * Get options from context
   *
   * @param resource [$streamOrContext]
   *
   * @return array
   */
  public function getOptions($streamOrContext = NULL)
  {
    if ($streamOrContext) {
      if (!is_resource($streamOrContext)) {
        throw new \InvalidArgumentException('First argument must be resource (Stream or Context resource).');
      }
      
      return stream_context_get_options($streamOrContext);
    }
    
    return stream_context_get_options($this->getResource());
  }
  
  /**
   * Get params from context
   *
   * @param resource [$streamOrContext]
   *
   * @return array
   */
  public function getParams($streamOrContext = NULL)
  {
    if ($$streamOrContext) {
      if (!is_resource($streamOrContext)) {
        throw new \InvalidArgumentException('First argument must be resource (Stream or Context resource).');
      }
      
      return stream_context_get_params($streamOrContext);
    }
    
    return stream_context_get_params($this->getResource());
  }
  
  
  
  /**
   * Create a new context
   *
   * @param array [$options]
   *    Options for context
   *
   * @param array [$params]
   *    Parameters for context
   *
   * @return resource
   *    Resource of context 
   */
  public function create(array $options = array(), array $params = array())
  {
    $this->resource = @stream_context_create($options, $params);
    
    if (!$this->resource) {
      throw new \RuntimeException('Can\'t create context.');
    }
    
    return $this->resource;
  }
  
  
  /**
   * Is create context
   *
   * @param bool [$autoload]
   *    Statuc autoloaded context
   *
   * @param array [$options]
   *    Options for create context
   *      - options
   *      - params
   * 
   * @return bool
   *    Status creating context
   */
  public function is($autoload = FALSE, array $options = array())
  {
    if (!$autoload) { return (bool) $this->resource; }
    
    try {
      if (is_resource($this->resource)) { return TRUE; }
      $options += array(
        'options' => array(),
        'params' => array()
      );
      
      $this->create($options['options'], $options['params']);
    }
    catch (\Exception $e) {
      return FALSE;
    }
    
    return (bool) $this->resource;
  }
  
  
  /**
   * Set params for context
   * 
   * @param mixed $wrapper
   *    Wrapper name or all wrappers options
   *
   * @param mixed [$paramName]
   *    
   */
  public function setOptions($wrapper, $paramName = NULL,  $paramValue = NULL)
  {
    if (is_array($wrapper)) {
      foreach ($wrapper as $wrapperName => $wrapperOptions) {
        if (!is_array($wrapperOptions)) {
          throw new \InvalidArgumentException(sprintf('Wrapper options must by array, <b>%s</b> given.', gettype($wrapperOptions)));
        }
        
        self::validateOptionsContext($wrapperName, $wrapperOptions);
      }
    }
    else {
      if (is_object($wrapper) && method_exists($wrapper, '__toString')) {
        $wrapper = (string) $wrapper;
      }
      
      if (!is_string($wrapper)) {
        throw new \InvalidArgumentException(sprintf('Wrapper name must be a string, %s given', gettype($wrapper)));
      }
      
      if (is_array($paramName)) {
        self::validateOptionsContext($wrapper, $paramName);
        $wrapper = array($wrapper => $paramName);
      }
      else if (is_string($paramName)) {
        self::validateOptionsContext($wrapper, array($paramName => $paramValue));
        $wrapper = array($wrapper => array($paramName => $paramValue));
      }
      else {
        throw new \InvalidArgumentException('Can\'t set options (Error: Can\'t validate options).');
      }
    }
    
    stream_context_set_option($this->getResource(), $wrapper);
  }
  
  /**
   * Set params
   *
   * @param array $params
   *
   * @return bool
   */
  public function setParams(array $params)
  {
    return stream_context_set_params($this->getResource(), $params);
  }

  
  /**
   * Validate options for context
   *
   * @param string @wrapper
   *    Wrapper name
   * 
   * @param array $options
   *    Options for context
   */
  final public function validateOptionsContext($wrapper, array $options)
  {
    $allowedOptions = self::getAllowedOptionsContext($wrapper);
    // Validate wrapper name
    if (!$allowedOptions) {
      throw new \InvalidArgumentException(sprintf('Can\'t validate wrapper options. Undefined wrappers <b>%s</b>', $wrapper));
    }
    
    // Validate options for wrapper
    foreach ($options as $key => $value) {
      // Validate allowed options
      if (!isset($allowedOptions[$key])) {
        throw new \InvalidArgumentException(sprintf('Undefined key for context. Key: <b>%s</b>', $key));
      }
      
      $type = $allowedOptions[$key];
      
      if (is_array($type)) { 
        if (!in_array($value, $type)) {
          throw new \InvalidArgumentException(sprintf('Key %s must be value of array: <b>%s</b>', $key, implode('</b>, <b>', $type)));
        }
      }
      else if ($type == 'mixed') {
        // Not used mixed values
      }
      else {
        // Validate of type
        switch ($type) {
          case 'integer':
            $status = is_int($value) || (is_numeric($value) && strpos($value, '.') !== FALSE);
            break;
          
          case 'float':
            $status = is_float($value) || is_numeric($value);
            break;
          
          case 'string':
            $status = is_string($value) || is_numeric($value) || (is_object($value) && method_exists($value, '__toString'));
            break;
          
          default:
            throw new \RuntimeException(sprintf('Undefined type variable: <b>%s</b>', $type));
        }
        
        if (!$status) {
          throw new \InvalidArgumentException(sprintf('Can\'t use type <b>%s</b> in key <b>%s</b>. This key must be <b>%s</b>.', gettype($value), $key, $type));
        }
      }
    }
  }
  
  
  /**
   * Get default allowed options for context
   * @param string @wrapper
   *    Optional, get a options for wrapper name
   *
   * @return array
   */
  final public static function getAllowedOptionsContext($wrapper = NULL)
  {
    $wrappers = array(
      // Options for HTTP Context
      'http' => array(
        'method' => array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'TRACE', 'LINK', 'UNLINK', 'CONNECT'),
        'header' => 'string',
        'user_agent' => 'string',
        'content' => 'string',
        'proxy' => 'string',
        'request_fulluri' => 'boolean',
        'follow_location' => 'integer',
        'max_redirects' => 'integer',
        'protocol_version' => 'float',
        'timeout' => 'float',
        'ignore_errors' => 'boolean'
      ),
      // Options for FTP Context
      'ftp' => array(
        'overwrite' => 'boolean',
        'resume_pos' => 'integer',
        'proxy' => 'string'
      ),
      // SSL
      'ssl' => array(
        'verify_peer' => 'boolean',
        'allow_self_signed' => 'boolean',
        'cafile' => 'string',
        'capath' => 'string',
        'local_cert' => 'string',
        'passphrase' => 'string',
        'CN_match' => 'string',
        'verify_depth' => 'integer',
        'ciphers' => 'string',
        'capture_peer_cert' => 'boolean',
        'capture_peer_cert_chain' => 'boolean',
        'SNI_enabled' => 'boolean',
        'SNI_server_name' => 'string'
      ),
      // CURL
      'curl' => array(
        'method' => array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'TRACE', 'LINK', 'UNLINK', 'CONNECT'),
        'header' => 'string',
        'user_agent' => 'string',
        'content' => 'string',
        'proxy' => 'string',
        'max_redirects' => 'integer',
        'curl_verify_ssl_host' => 'boolean',
        'curl_verify_ssl_peer' => 'boolean'
      ),
      // Phar
      'phar' => array(
        'compress' => 'int',
        'metadata' => 'mixed'
      ),
      // Socket
      'socket' => array(
        'bindto' => 'string'
      )
    );
    
    return $wrapper ? (isset($wrappers[$wrapper]) ? $wrappers[$wrapper] : FALSE) : $wrappers;
  }
}