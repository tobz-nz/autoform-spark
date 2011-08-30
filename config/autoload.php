<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


# Load the autoform library when the spark is loaded
$autoload['libraries'] = array('autoform');


# Load the array helper which is needed by autoform and doesn't seem to load in __constructor()
$autoload['helper'] = array('array');