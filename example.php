<?php 

include_once 'BlitzTemplateControl.class.php';

$data = [
	'authors' => [
		0 => ['fname'=>'Lev','lastname'=>'Tolstoi'],
		1 => ['fname'=>'Vladimir','lastname'=>'Nabokov'],
	],
]; 

/* Use templates in one single file with php-array */
$tmplcontrol_in_single_file = new BlitzTemplateControl('SomeClass');

echo $tmplcontrol_in_single_file->apply('authors',$data);

/* Use templates in separate files in folder */
$tmplcontrol_in_tpl_files = new BlitzTemplateControl('SomeClass',true);

echo $tmplcontrol_in_tpl_files->apply('authors',$data);

?>