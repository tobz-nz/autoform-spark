<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * Autoform
 *
 * A class for simplifying the process of creating
 * html forms Built for the Codeigniter Framework.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Attribution-ShareAlike 3.0 that is
 * bundled with this package in the files license.txt.  It is
 * also available through the world wide web at this URL:
 * http://creativecommons.org/licenses/by-sa/3.0/nz/
 *
 * @package   Autoform
 * @author    Toby Evans (@t0bz)
 * @copyright Copyright (c) 2011, Toby Evans.
 * @license   http://creativecommons.org/licenses/by-sa/3.0/nz/
 * @link      http://getsparks.org/packages/autoform/versions/HEAD/show
 * @since     Version 3.0
 * @filesource
 */
class Autoform {

	private $CI;
	public $fields;
	public $buttons = '';
	private $before = array();
	private $after = array();
	private $inline_errors = TRUE;
	private $field_types = array (
	'timestamp'=>'date',
	'checkbox'=>'checkbox',
	'radio'=>'radio',
	'hidden'=>'hidden',
	'file'=>'file',
	'text'=>'text',
	'blob'=>'textarea',
	'textarea'=>'textarea',
	'select'=>'select',
	'password'=>'password',
	'int'=>'number',
	'search'=>'search',
	'tel'=>'tel',
	'url'=>'url',
	'email'=>'email',
	'datetime'=>'datetime',
	'date'=>'date',
	'month'=>'month',
	'week'=>'week',
	'time'=>'time',
	'datetime-local'=>'datetime-local',
	'number'=>'number',
	'range'=>'range',
	'color'=>'color');
	
	function __construct() {
		$this->CI =& get_instance();
		// load required libraries & helpers
		$this->CI->load->library('form_validation');
		$this->CI->load->helper(array('form','url'));
		
		// remove <p>...</p> from error messages (spaces are trimmed later)
		$this->CI->form_validation->set_error_delimiters(' ',' ');

		// set fields as an empty object
		$this->fields = new stdClass();
		
		// set default buttons
		$this->buttons = form_button(array('type'=>'submit', 'name'=>'submit', 'content'=>'Submit'))."\n";
		
	}
	
	/**
	 * Add new field to form
	 * 
	 * @param Array $input
	 * @return Mixed
	 */
	public function add($input, $return_object=FALSE) {
		
		// setup default label
		$label->content = ucwords(preg_replace("/[_-]/",' ', $input['name']));
		$label->for = (isset($input['id']) ? $input['id'] : url_title($input['name']));
		if (isset($input['type'])&&$input['type']=='checkbox' || isset($input['type'])&&$input['type']=='radio') {
			$label->position = 'right'; // radio/checkbox labels default to the right
		}
		else {
			$label->position = 'left'; // everything else, labels default to the left
		}
		$label->extra = array();
		
		// set all field attributes
		foreach ($input as $key=>$value) {
			
			// add label
			if ($key=='label') {
				if ( ! is_array($value)) {
					$label->content = ($value); // set label text
				}
				else {
					// set all label attributes
					foreach ($value as $key2=>$value2) {
						switch ($key2) {
							case 'content':
								$label->content = form_prep($value2);
							break;
							case 'for':
								$label->for = $value2;
							break;
							case'position':
								$label->position = $value2;
							break;
							default:
								$label->extra[$key2] = form_prep($value2);
							break;
						}
					}
				}
			}
			elseif ($key=='type') {
				// use type as defined in $this->field_types array if it exists
				$field_obj->$key = element($value, $this->field_types, $value);
				// also set a smaller row number for text areas if a value has not been supplied
				if ($field_obj->$key=='textarea') {
					if (!isset($field_obj->rows)) $field_obj->rows = 5;
				}
			} 
			else {
				// add other field attributes
				$field_obj->$key = ($value);
			}
		}
		
		// remove ending [] from label for arrayed fields
		if ($label->content) {
			$label->content = preg_replace('/^(.{0,})(\[\])$/', '$1', $label->content);
		}
		
		// add label object to field object 
		$field_obj->label = $label;
		
		// add id if it was not included
		if ( ! isset($field_obj->id)) {
			// set id the same as name
			$field_obj->id = $field_obj->name;
			// remove ending [] in id for arrayed fields
			$field_obj->id = preg_replace('/^(.{0,})(\[\])$/', '$1', $field_obj->id);
		}
		// add value if it was not included
		if ( ! isset($field_obj->value)) $field_obj->value = false;
		
		// check if id is not explicitly set and if field with same id exists, make it uniue
		$id = $field_obj->id;
		if ( ! isset($input['id'])) {
			$this->unique_id($field_obj);
		}
		
		// add main field object
		$id = $field_obj->id;
		$this->fields->$id = $field_obj;
		
		// run validation first
		$this->validate($this->fields->$id);

		// set before/after text
		if (isset($before)) $this->set($id, array('before'=>$before));
		if (isset($after)) $this->set($id, array('after'=>$after));
		
		// return final string
		if ($return_object==FALSE) {
			
			// build field output and return it
			return $this->build($this->fields->$id);
		}
		else {
			return $this->fields->$id;
		}
	}

	/**
	 * Remove field(s) from the form
	 * 
	 * @param Mixed $fields
	 * @return 
	 */  
	public function remove($fields) {
		if (is_array($fields)) {
			foreach ($fields as $field) {
				$id = $field;
				if (isset($this->fields->$id)) unset($this->fields->$id);
			}
		}
		else {
			// remove field
			if (isset($this->fields->$fields)) unset($this->fields->$fields);
		}
	}
	
	/**
	 * Set attributes on a field
	 * 
	 * @param String $field_id
	 * @param Array $attr [optional]
	 * @return Object
	 */
	public function set($field_id, $attr=array()) {
		if (isset($this->fields->$field_id)) {
			foreach ($attr as $key=>$value) {
				
				// set new label
				if ($key=='label') {
					$label = $this->fields->$field_id->label;
					// if label is a string
					if ( ! is_array($value)) {
						$label->content = ($value); // set label text
					}
					else {
						// else its an array
						foreach ($value as $key2=>$value2) {
							switch ($key2) {
								case 'content':
									$label->content = ($value2);
								break;
								case 'for':
									$label->for = $value2;
								break;
								case'position':
									$label->position = $value2;
								break;
								default:
									$label->extra[$key2] = form_prep($value2);
								break;
							}
						}
					}
					// apply new label
					$this->fields->$field_id->$key = $label;
				}
				elseif ($key=='after') {
					$this->after($field_id, $value);
				}
				elseif ($key=='before') {
					$this->before($field_id, $value);
				}
				else {
					// apply other attributes
					$this->fields->$field_id->$key = $value;
				}
			}
			return $this->fields->$field_id;
		}
	}
	
	/**
	 * Get one or more attributes from a field
	 * 
	 * @param String $field_id
	 * @param Mixed $params [optional]
	 * @param String $fallback [optional]
	 * @return Mixed
	 */
	public function get($field_id, $params=FALSE, $fallback='') {
		$get = array();
		if (is_array($params)) {
			foreach ($params as $param) {
				$get[$param] = $this->get($field_id, $param);
			}
			return $get;
		}
		else {
			if (isset($this->fields->$field_id)) {
				$field = $this->fields->$field_id;
				if (isset($field->$params) && $field->$params) {
					return $field->$params;
				}
				else {
					return $fallback;
				}
			}
		}
	}
	
	/**
	 * Add a string before a field
	 * $side can be start or end and sets the string at 
	 * that position of the existing string
	 * 
	 * @param String $field_id
	 * @param String $input
	 * @param String $side [optional]
	 * @return Object
	 */
	public function before($field_id, $input, $side='end') {

		if (isset($this->before[$field_id])) {
			// add to existing string
			if ($side=='start') {
				// add in fron of existing
				$this->before[$field_id] = $input . $this->before[$field_id];
			}
			else {
				// add after existing
				$this->before[$field_id] .= $input;
			}
		}
		else {
			// create new string
			if ($side=='start') {
				// add in front of string
				$this->before[$field_id] = $input . $this->before[$field_id];
			}
			else {
				// add to end of string
				$this->before[$field_id] = $input;
			}
		}
		
		// return the field object
		if (isset($this->fields->$field_id)) return $this->fields->$field_id;
	}
	
	/**
	 * Add a string after a field
	 * $side can be start or end and sets the string at 
	 * that position of the existing string
	 * 
	 * @param String $field_id
	 * @param String $input
	 * @param String $side [optional]
	 * @return Object
	 */
	public function after($field_id, $input, $side='end') {

		if (isset($this->after[$field_id])) {
			// add to existing string
			if ($side=='start') {
				// add in fron of existing
				$this->after[$field_id] = $input . $this->after[$field_id];
			}
			else {
				// add after existing
				$this->after[$field_id] .= $input;
			}
		}
		else {
			// create new string
			if ($side=='start') {
				// add in front of string
				$this->after[$field_id] = $input . $this->after[$field_id];
			}
			else {
				// add to end of string
				$this->after[$field_id] = $input;
			}
		}
		
		// return the field object
		if (isset($this->fields->$field_id)) return $this->fields->$field_id;
	}
	
	
	/**
	 * Validate all or a single field
	 * 
	 * @param Object $field
	 * @return Bool
	 */
	private function validate($fields) {
		
		$error = FALSE;
		
		if ( ! isset($fields->name)) {
			// validate all fields
			foreach ($fields as $field) {
				if ($this->validate($field) == FALSE) $error = TRUE;
			}
		}
		else {
			$field = $fields;
		}
		
		// re-populate the fields
		if(set_value($field->id)) $field->value = set_value($field->id); 
		
		// set class names
		if (form_error($field->name)) {
			if ($this->inline_errors==TRUE) {
				$field->label->content = trim(form_error($field->name));
			}
			// add error class to label
			if (isset($field->label->extra['class']) && !preg_match('/error/', $field->label->extra['class'])) { 
				$field->label->extra['class'] .= ' error';
			}
			elseif ( ! isset($field->label->extra['class'])) {
				$field->label->extra['class'] = 'error';
			}
			
			// add error class to field
			if (isset($field->class) && !preg_match('/error/', $field->class)) { 
				$field->class .= ' error';
			}
			else {
				$field->class = 'error';
			}
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Build up a single field from existing
	 * form field elements
	 * 
	 * @param String $field_id
	 * @return String
	 */
	public function field($field_id, $attr=FALSE) {
		if ( ! isset($this->fields->$field_id)) return false;
		if (is_array($attr)) $this->set($field_id, $attr); // set new atrtributes
		$this->validate($this->fields->$field_id); // validate
		return $this->build($this->fields->$field_id); // build
	}
	
	/**
	 * Ouput the label of a field
	 * 
	 * @param String $field_id
	 * @param String $fallback [optional]
	 * @param Bool $include_tag [optional]
	 * @return String
	 */
	function label($field_id, $fallback='', $include_tag=TRUE) {
		
		if (isset($this->fields->$field_id)) {
			
			$field = $this->fields->$field_id;
			$error=FALSE;
			$this->validate($field); // run validation
			// check if is error
			if (form_error($field_id)) {
				$content = trim(form_error($field_id));
				
				if ($include_tag==FALSE) {
					// wrap error in a span.error
					$content = '<span class="error">'.$content.'</span>';
				}
			}
			else {
				if ($fallback!='') {
					// use fallback??
					$content = $fallback;
				}
				else {
					// use exist label
					$content = $field->label->content;
				}
			}
			
			if ($include_tag==TRUE) {
				// return label
				return form_label($content, $field->id, $field->label->extra);
			}
			else {
				// return string
				return $content;
			}
			
		}
		
	}
	
	/**
	 * Generate field html
	 * 
	 * @param Object $field
	 * @return String
	 */
	private function build($field) {
		
		$output = '';

		// add "before" string to output
		if (isset($this->before[$field->id])) $output .= $this->before[$field->id]; 
		
		// set default type
		if ( ! isset($field->type)) {
			$field->type = 'text';
		}
		
		switch ($field->type) {
			case 'select':
			case 'dropdown':
				// add label
				if ($field->label->content!='') {      		
					switch ($field->label->position) {
						case 'wrap':
							$output .= form_label($field->label->content . $this->dropdown($field), $field->id, $field->label->extra);
						break;
						case 'right':
							$output .= $this->dropdown($field);
							$output .= ' '.form_label($field->label->content, $field->id, $field->label->extra);
						break;
						case 'left':
						default:
							$output .= form_label($field->label->content, $field->id, $field->label->extra).' ';
							$output .= $this->dropdown($field);
						break;
					}
				}
				else {
					$output .= $this->dropdown($field);
				}
			break;

			case 'checkbox':
			case 'radio':
				// generate field
				// add label
				if ($field->label->content!='') {
					switch ($field->label->position) {
						case 'wrap':
							$output .= form_label($field->label->content . $this->checked_input($field), $field->id, $field->label->extra);
						break;
						case 'right':
							$output .= $this->checked_input($field);
							$output .= ' '.form_label($field->label->content, $field->id, $field->label->extra);
						break;
						case 'left':
						default:
							$output .= form_label($field->label->content, $field->id, $field->label->extra).' ';
							$output .= $this->checked_input($field);
						break;
					}
				}
				else {
					$output .= $this->checked_input($field);
				}
			break;
			case 'textarea':
				// add label if field is not type hidden, or label->text is blank
				if ($field->label->content!='' && $field->type!='hidden') {
					switch ($field->label->position) {
						case 'wrap':
							$output .= form_label($field->label->content . $this->textarea($field), $field->id, $field->label->extra);
						break;
						case 'right':
							$output .= $this->textarea($field);
							$output .= ' '.form_label($field->label->content, $field->id, $field->label->extra);
						break;
						case 'left':
						default:
							$output .= form_label($field->label->content, $field->id, $field->label->extra).' ';
							$output .= $this->textarea($field);
						break;
					}
				}
				else {
					$output .= $this->textarea($field);
				}
			break;

			case 'button':
			case 'submit':
			case 'image':
				// remove the label
				unset($field->label);
				$output .= form_button($this->object_to_array($field));
			break;
			
			case 'text':
			case 'hidden':
			default:
				// add label if field is not type hidden, or label->text is blank
				if ($field->label->content!='' && $field->type!='hidden') {
					switch ($field->label->position) {
						case 'wrap':
							$output .= form_label($field->label->content . $this->input($field), $field->id, $field->label->extra);
						break;
						case 'right':
							$output .= $this->input($field);
							$output .= ' '.form_label($field->label->content, $field->id, $field->label->extra);
						break;
						case 'left':
						default:
							$output .= form_label($field->label->content, $field->id, $field->label->extra).' ';
							$output .= $this->input($field);
						break;
					}
				}
				else {
					$output .= $this->input($field);
				}
			break;
		}
		
		// add "after" string to output
		if (isset($this->after[$field->id])) $output .= $this->after[$field->id];
		
		return $output."\n";
		
	}
	
	/**
	 * Build up input field
	 * 
	 * @param Object $field
	 * @return String
	 */
	private function input($field) {
		
		$data = array('name'=>$field->name,'type'=>$field->type);
		$extra = array();
		
		// work out attributes 
		foreach ($field as $key=>$value) {
			if ($key!='name' && $key != 'value' && $key != 'label' && $key != 'type') {
				$extra[$key] = $value;
			}
		}
		if (!isset($field->value)) $field->value = '';

		// set value as posted value
		if ($this->CI->input->post($field->name)) $field->value = $this->CI->input->post($field->name);

		
		return form_input($data, htmlspecialchars($field->value, ENT_COMPAT, "UTF-8"), $this->stringify($extra));
	}
	
	/**
	 * Build a textarea
	 * 
	 * @param Object $field
	 * @return String
	 */
	private function textarea($field) {
		
		$data = array();
		
		// work out attributes 
		foreach ($field as $key=>$value) {
			if ($key != 'value' && $key != 'label' && $key != 'type') {
				$data[$key] = $value;
			}
		}
		if (!isset($field->value)) $field->value = '';

		// set value as posted value
		if ($this->CI->input->post($field->name)) $field->value = $this->CI->input->post($field->name);

		return form_textarea($data, $field->value);
	}
	
	/**
	 * Build a checkbox or radio field
	 * 
	 * @param Object $field
	 * @return String
	 */
	private function checked_input($field) {
		
		$data = array();
		if ( ! isset($field->value)) $field->value = 1; // set default value
		
		// add class to label
		if (isset($field->label->extra['class']) && !preg_match('/radio|checkbox/', $field->label->extra['class'])) { 
			$field->label->extra['class'] .= ' '.$field->type;
		}
		elseif ( ! isset($field->label->extra['class'])) {
			$field->label->extra['class'] = $field->type;
		}
		
		// add class to field
		if (isset($field->class) && !preg_match('/radio|checkbox/', $field->class)) { 
			$field->class .= ' '.$field->type;
		}
		elseif ( ! isset($field->class)) {
			$field->class = $field->type;
		}
		
		// work out attributes 
		foreach ($field as $key=>$value) {
			if ($key != 'value' && $key != 'label' && $key != 'type') {
				$data[$key] = $value;
			}
		}
		
		// set checked status
		$field->checked = (isset($_POST[$field->name]) && $this->CI->input->post($field->name) == $field->value ? true : false);
		
		// set checked status on posted array (fieldname[])
		$raw_name = preg_replace('/^(.{0,})(\[\])$/', '$1', $field->name); // remove ending []
		if (is_array($this->CI->input->post($raw_name))) {
			if (in_array($field->value, $this->CI->input->post($raw_name))) {
				$field->checked = true;
			}
		}
		
		$method = 'form_'.$field->type; // form_checkbox, form_radio
		return $method($data, $field->value, $field->checked);
	}
	
	/**
	 * Build a dropdown/select field
	 * 
	 * @param Object $field
	 * @return String
	 */
	private function dropdown($field) {
		
		$name = $field->name;
		$options = (isset($field->options) ? $field->options : array());
		$extra = array();
		
		foreach ($field as $key=>$value) {
			if ($key!='name' && $key!='options' && $key != 'value' && $key != 'label' && $key!='type') {
				$extra[$key] = $value;
			}
		}
		if (!isset($field->value)) $field->value = '';
		
		// set value as posted value
		if ($this->CI->input->post($field->name)) $field->value = $this->CI->input->post($field->name);
		
		return form_dropdown($name, $options, $field->value, $this->stringify($extra));
	}
	
	/**
	 * Open the form
	 * 
	 * @param String $action
	 * @param Array $attr [optional]
	 * @param Bool $multipart [optional]
	 * @return String
	 */
	public function open($action, $attr=array('method'=>'post'), $multipart=FALSE) {
		if ( ! isset($attr['method'])) $attr['method'] = 'post'; // set to POST as default
		if ($multipart==TRUE) {
			return form_open_multipart($action, $attr)."\n";
		}
		else {
			return form_open($action, $attr)."\n";
		}
	}
	
	/**
	 * Generate just the fields and
	 * return the finalized html
	 * 
	 * @return String 
	 */
	public function fields($field_order=FALSE) {
		
		$output = '';
		
		if (is_array($field_order)) {
			foreach ($field_order as $field_id) {
				if (!isset($this->fields->$field_id)) continue;// check field exists
				$output .= $this->build($this->fields->$field_id);
			}
		}
		else {
			// loop through fields
			foreach ($this->fields as $field) {
				// validate field
				$this->validate($field);
				// generate field
				$output .= $this->build($field);
			}
		}
		return $output;
	}
	
	/**
	 * Close the form
	 * 
	 * @param String $extra
	 * @return String
	 */
	public function close($extra='') {
		$this->clear(); // clear the form
		return form_close($extra)."\n";
	}
	
	/**
	 * Generate the entire form
	 * 
	 * @param String $action
	 * @param Array $attr
	 * @param Bool $multipart [optional]
	 * @return String
	 */
	public function generate($action, $attr=array(), $multipart=FALSE) {
		// open form
		$output = $this->open($action, $attr, $multipart);
		
		// add the fields
		$output .= $this->fields();
		
		// add buttons
		$output .= $this->buttons;
		
		// close form
		$output .= $this->close();
		
		return $output;
	}
	
	/**
	 * Merge an array intro a string of html attributes
	 * 
	 * @param Array $attributes
	 * @return String
	 */
	private function stringify($attributes) {
		if (is_string($attributes)) return $attributes;
		
		$output = '';
		foreach ($attributes as $attr=>$value) {
			$output .= $attr.'="'.$value.'" ';
		}
		return ' '.trim($output);
	}
	
	/**
	 * Make a fields id unique
	 * @param Object $field_obj
	 * @return Object
	 */
	private function unique_id(&$field_obj) {
		$id = $field_obj->id;
		if ($field_obj->id == $field_obj->name && isset($this->fields->$id) || isset($this->fields->$id)) {
			
			$i = 1;
			while (isset($this->fields->{$id.'_'.$i})) {
				$i++;
			}
		
			$field_obj->id = $id.'_'.$i;
		}
		return $field_obj;
	}

	/**
	 * build form from table name
	 * @return null
	 * @param String $table_name
	 */
	public function table($table_name) {
		$this->CI->load->database();
		$this->process_field_data($this->CI->db->field_data($table_name));
	}


	/**
	 * build form from query string or query object
	 * @return null
	 * @param Mixed $query
	 */
	public function sql($query) {
		$this->CI->load->database();
		if (is_string($query)) {
			$query = $this->CI->db->query($query);
		}
		$this->process_field_data($query->field_data(), $query);
	}


	/**
	 * process field data object into useable array
	 * @return null
	 * @param Object $fields
	 */
	private function process_field_data($field_data, $result = false) {
		foreach ($field_data as $field) {
			
			if ($result) {
				foreach ($result->result() as $field_value) {
					$name = $field->name; 
					$value = $field_value->$name;
				}
			}
			
			// build up array for new field
			$new_field = array (
			'name'=>url_title($field->name, 'underscore', TRUE),
			'type'=>$this->get_field_type($field->type, $field->primary_key),
			'value'=>( isset ($value)?$value:'')
			);
			
			// add the field
			$this->add($new_field);
		}
	}
	
	/**
	 * Gets input type from field type
	 * 
	 * @param String $type
	 * @param Bool $primary_key
	 * @return String
	 */
	private function get_field_type($type, $primary_key = false) {
		if ($primary_key) {
			return 'hidden';
		}
		else if ($type == 'text') {
			return 'textarea';
		}
		else if ( isset ($this->field_types[$type])) {
			return $this->field_types[$type];
		}
		else {
			return 'text';
		}
	}

	private function object_to_array($data) {
		if(is_array($data) || is_object($data)) {
			$result = array(); 
			foreach($data as $key => $value) { 
				$result[$key] = $this->object_to_array($value); 
			}
			return $result;
		}
		return $data;
	}
	
	/**
	 * Reset the entire form
	 * 
	 * @return 
	 */
	public function clear() {
		$this->fields = NULL;
		$this->buttons = form_button(array('type'=>'submit', 'name'=>'submit', 'content'=>'Submit'));
	}
	
}


/* End of file autoform.php */
/* Location: application/libraries/autoform.php */