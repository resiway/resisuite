<?php
/**
*    This file is part of the easyObject project.
*    http://www.cedricfrancoys.be/easyobject
*
*    Copyright (C) 2012  Cedric Francoys
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.

*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
namespace easyobject\orm;


/**
	This class holds the description of an object (and not the object itself)
*/
class Object {
	
	/**
	 * Complete object schema, containing all columns (including special ones as object id)
	 *
	 * @var array
	 * @access private
	 */
	private $schema;
    
    private $fields;

    private $values;
    
	/**
	 * Constructor
	 *
	 * @access public
	 */
	public final function __construct() {
        // schema is the concatenation of spcecial-columns and custom-defined columns
		$this->schema = array_merge(self::getSpecialColumns(), $this->getColumns());
		
        // make sure that a field 'name' is always defined 
		if( !isset($this->schema['name']) ) {
            // if no field 'name' is defined, fall back to 'id' field
            $this->schema['name'] = array( 'type' => 'alias', 'alias' => 'id' );
        }
        // set array holding fields names
        $this->fields = array_keys($this->schema);
        
        // set fields to default values, if any
		$this->setDefaults();
	}

	private final function setDefaults() {
        $this->values = [];
		if(method_exists($this, 'getDefaults')) {
			$defaults = $this->getDefaults();
			// get default values, set fields for default language, and mark fields as modified
			foreach($defaults as $field => $default_value) {
                if(isset($this->schema[$field]) && is_callable($default_value)) {
                    $this->values[$field] = call_user_func($default_value);
                }
            }
    	}
	}

	public final static function getSpecialColumns() {
		static $special_columns = array(
			'id'		=> array('type' => 'integer'),
			'creator'	=> array('type' => 'integer'),            
			'created'	=> array('type' => 'datetime'),
			'modifier'	=> array('type' => 'integer'),
			'modified'	=> array('type' => 'datetime'),
			'deleted'	=> array('type' => 'boolean'),		
			'state'		=> array('type' => 'string'),			
		);
		return $special_columns;
	}

	/**
	 * Gets object schema
	 *
	 * @access public
	 * @return array
	 */
	public final function getSchema() {
		return $this->schema;
	}

    /**
	* Returns all fields names 
	*
	*/
	public final function getFields() {
		return $this->fields;
	}	

    /**
    * returns values of static instance (default values)
    *
    */
	public final function getValues() {
		return $this->values;
	}	
    
	/**
	* Returns the user-defined part of the schema (i.e. fields list with types and other attributes)
	* This method must be overridden by children classes.
	*
	* @access public
	*/
	public static function getColumns() {
		return array();
	}

	/**
	* Returns the name of the database table related to this object
	* This method may be overridden by children classes
	*
	* @access public
	*/
	public function getTable() {
		return strtolower(str_replace('\\', '_', get_class($this)));
	}



}
