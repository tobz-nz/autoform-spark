Autoform is a library that takes the hassle out of creating html forms. It can generate entire forms based on a table or sql query in your database, with all validation code in place.
If your not basing your form an a DB table, that's fine too - you can build a form easily with a few simple lines.

# Some Basic Usage

	$this->load->spark('autoform/[version]');

	$this->autoform->table('my_table');
	// or you can use: $this->autoform->sql($query_result_obj);

	// remove some unwanted fields
	$this->autoform->remove(array('unwanted_field','another_unwanted_field'));

	// change some attributes of some fields
	$this->autoform->set('email', array('type'=>'email', 'class'=>'classname', 'required'=>'required'));
	$this->autoform->set('name', array('value'=>$name));

	// a new field thats not from the database
	$this->autoform->add(array('name'=>'new_field', 'value'=>'my Value', 'type'=>'select', 'options'=>array('option 1'=>'First option', 'option 2'=>'second option')));

	echo $this->autoform->generate('form/test');


	// or output the form fields separately
	echo $this->autoform->open('form/test', array('id'=>'form'), FALSE); // third parameter sets the form as multipart
	
	// if this parameter is left empty, all fields are returned in the order they were created. 
	echo $this->autoform->fields(array('email', 'name'));
	
	echo $this->autoform->field('id');

	// add and print at the same time
	echo $this->autoform->add(array('type'=>'text','name'=>'example'));
	
	echo $this->autoform->close();


Note: the value will always be overwritten to the value posted if the form fails validation.

Install autoform and look at ./sparks/autoform/[version]/docs/autoform.html for more in depth documentation.