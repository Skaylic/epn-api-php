<?php

namespace Skay\Epn;

/**
 * Response
 */
class Response
{
   /**
    * @var array
    */
   protected $_data;

   function __construct(array $data)
   {
      $this->_data = $data;
   }

   /**
    * Исходные данные
    * @return array
    */
   public function getData($name = false)
   {
      if($name)return $this->_data[$name];
      return $this->_data;
   }
}
