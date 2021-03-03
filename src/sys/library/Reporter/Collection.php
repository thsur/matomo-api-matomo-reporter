<?php

namespace Reporter;

/**
 * Basic collection object.
 *
 * Usage:
 * ------
 * 
 * $collection->get('acme_social')->get('twitter')->get('client_id')
 *
 * OR
 *
 * $collection->acme_social->twitter->client_id 
 */
class Collection extends \ArrayObject {

    /**
     * Initialize and turn all levels of the given
     * array into a collection object.
     * 
     * @param Array $data
     */
    public function __construct(array $data) {

        parent::__construct($data, \ArrayObject::ARRAY_AS_PROPS);

        // Turn all arrays into Collections

        $iterator = new \RecursiveIteratorIterator(

            new \RecursiveArrayIterator($this), \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $key => $value) {
            
            if (is_array($value)) {

                $iterator->getInnerIterator()->offsetSet($key, new static($value));
            }
        }
    }

    /**
     * Get a property.
     * 
     * @param  Mixed $name 
     * @return Mixed
     */
    public function get($name) {

        return $this->offsetGet($name);
    }

    /**
     * Set a property.
     * 
     * @param Mixed $name  
     * @param Mixed $value 
     */
    public function set($name, $value) {

        $this->offsetSet($name, $value);
    }
    
    /**
     * Whether or not a property exists.
     * 
     * @param  Mixed   $name 
     * @return Boolean 
     */
    public function has($name) {

        return $this->offsetExists($name);
    }
}