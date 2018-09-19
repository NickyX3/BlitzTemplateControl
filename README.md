# BlitzTemplateControl
Wrapper for Blitz template PHP extensions. 
Realize many support templates store type: original separate tpl files, single php file and Redis cache suport.
Also always preload all Blitz includes by this includes content.

## Required
- Blitz extension (https://github.com/alexeyrybak/blitz)
- phpredis extension (https://github.com/phpredis/phpredis) if use cached mode

## Usage
Just include class file, create templates, make your data and apply template

## Directory structure
### Separate TPL files mode
If you use separate templates files, сreate a folder "TemplatesTPL" in the same place as the class file. 
Name of this folder set in public property 'relative_tpath_separates_blitz' in class. 
Templates for each set must be in the folder with the name of this set.

- :file_folder: TemplatesTPL
  - :file_folder: SomeSetName
    - :page_facing_up: template_one.tpl
    - :page_facing_up: template_two.tpl

### Single PHP-file mode
If you use single PHP-file mode with all set templates in one PHP-file with '$templates' array, create a folder "TemplatesPHP"
in the same place as the class file. 
Name of this folder set in public property 'relative_tpath_singlefile_blitz' in class. 
Templates for each set must be in the one file with the name of this set + .tmpl.php

- :file_folder: TemplatesPHP
  - :page_facing_up: SomeSetName.tmpl.php


## Example
```php
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
```
## Redis Cache
Also you can cache templates in Redis, there will be fully expanded templates, in which all inclusions are replaced by this content. Templates are stored in the HSET with a key name  'Template:'.SomeSetName.':'.md5(FILENAME).

In this mode, every time the templates are loaded, the date of modification of the files or file is checked, if any templates in the file are fresh than the data in the Redis, then the cache is updated with new TTL. If the templates in the files have not changed, then only the update key TTL
