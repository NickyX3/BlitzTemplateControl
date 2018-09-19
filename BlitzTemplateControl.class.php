<?php
	/**
	 * Blitz Template Engine Wrapper
	 * Required Blitz extension (https://github.com/alexeyrybak/blitz)
	 * Required phpredis extension (https://github.com/phpredis/phpredis) if use cached mode
	 * 
	 * version 0.6
	 * Use for store templates in one file with php array or original separate files in folder.
	 * 
	 * @author Nic Latyshev
	 */
	class BlitzTemplateControl {
		/** main template array */
		public	$templates 		= array();
		/** templates path */
		public	$templates_path			= '';
		/** base template path mode */
		public	$templates_path_main	= '';
		/** base template path for mobile */
		public	$templates_path_mobile	= '';
		/** use mobile flag */
		public	$usemobile		= false;
		/** input class name for templates set */
		public	$classname		= '';
		public	$custom_path	= '';
		/** use Blitz extension */
		/*  Don't set to false, eval mode deprecated and danger, compatibility feature and will be removed */ 
		public	$useblitz		= true;
		/** separate files mode, like original Blitz */
		public	$use_tpl_files	= false;
		/** use cache flag */
		public	$usecache		= false;
		/** redis cache db number */
		public	$cache_db		= 4;
		/** cache time, seconds */
		public	$cache_time		= 600;
		/** relative path to templates in php array mode without Blitz (all templates in one file ClassName.tmpl.php with php-array EVAL mode) */
		public	$relative_tpath_singlefile_eval		= '/Templates/';
		/** relative path to templates in php array mode with Blitz (all templates in one file ClassName.tmpl.php with php-array BLITZ mode) */
		public	$relative_tpath_singlefile_blitz	= '/TemplatesPHP/';
		/** relative path to templates in separate files mode with Blitz (all templates in separate files in folder ClassName) */
		public	$relative_tpath_separates_blitz		= '/TemplatesTPL/';
		/** files extension for separate files Blitz mode */
		public	$tpl_ext		= 'tpl';
		/** Blitz object */
		public	$blitzObj		= null;
		
		/**
		 * This is constructor, init some vars and load templates for input class name
		 *
		 * @param 	string 		$classname			ClassName for select templates set
		 * @param 	boolean 	$use_tpl			Use tpl mode (separate files in folder)
		 * @param 	boolean 	$use_cache			Use redis cache
		 * @param 	string 		$templates_path		Custom templates path
		 */
		public function __construct( $classname, $use_tpl=false, $use_cache=false, $templates_path='' ) {
			setlocale(LC_ALL,'ru_RU.UTF-8');
			
			// ClassName set
			$this->classname = $classname;
			if ( $templates_path != '' ) {
				$this->custom_path = $templates_path;
			}
			
			// TPL mode
			if ( $use_tpl === true ) {
				$this->use_tpl_files = true;
			}
			
			// Cached mode
			if ( $use_cache === true ) {
				$this->usecache = true;
			}
			
			// Load Templates
			if ( $this->classname != '' || $this->custom_path != '' ) {
				$this->reloadTemplates();
			}
		}
		
		/**
		 * Reload templates method
		 *
		 * Use after set some vars, example after set "usemobile" for reload templates from mobile folder
		 */
		public function reloadTemplates() {
			$this->getTemplatesPath( $this->custom_path );
			$this->loadTemplates( $this->classname );
		}
		
		/**
		* Apply wrapper 
		*
		* @param	string	$templatename	Template name
		* @param	array	$data			Data for template
		*
		* @return string
		*/
		public function apply ( $templatename, $data=array() ) {
			// Blitz mode check
			if ( $this->useblitz ) {
				// use Blitz'ом
				return $this->apply_blitz ( $templatename, $data );
			} else {
				// use eval
				return $this->apply_eval ( $templatename, $data );
			}
		}
		
		/**
		 * Apply Blitz
		 *
		 * @param 	string 	$templatename
		 * @param 	array 	$data
		 *
		 * @return string
		 */
		public function apply_blitz ( $templatename, $data=array() ) {
			if ( empty($data) ) {
				$data = (array) $data;
			}
			if ( !is_object($this->blitzObj) ) {
				$this->blitzObj = new Blitz();
			}
			set_error_handler(create_function('$c, $m, $f, $l', 'throw new MyException($m, $c, $f, $l);'), E_ERROR|E_WARNING);
			try {
				$this->blitzObj->load($this->templates[$templatename]);
				$result = $this->blitzObj->parse($data);
			} catch ( MyException $exc) { }
			restore_error_handler();
			return $result;
		}
		
		/**
		 * Apply EVAL mode, don't use it! Deprecated feature for compatibility
		 *
		 * @param 	string 	$templatename
		 * @param 	array 	$data
		 *
		 * @return string
		 */
		public function apply_eval ( $templatename, $data=array() ) {
			if ( is_object($data) ) {
				$data = (array) $data;
			}
			if ( is_array($data) ) {
				$data = Utils::array_addslashes($data);
				extract($data);
			}
			$tmpl = $this->templates[$templatename];
			eval('$result="'.$tmpl.'";');
			$result = stripslashes($result);
			return $result;
		}
		
		/**
		 * Метод вычисляет путь к шаблонам
		 *
		 * @param	string	$templates_path
		 * @return	string
		 */
		private function getTemplatesPath ( $templates_path='' ) {
			// if set custom path
			if ( $templates_path != '' ) {
				$this->templates_path_main = realpath($templates_path);
				if ( $this->templates_path_main ) {
					$this->templates_path_main = $this->templates_path_main.'/';
				} else {
					// if you want use custom exception for this
					echo 'Custom Path '.$templates_path.' NOT Exists!';
				}
			} else {
				// templates dir relative to this class file!
				$this->templates_path_main = realpath(__DIR__);
				// use Blitz check
				if ( $this->useblitz ) {
					// use TPL mode cheack
					if ( $this->use_tpl_files ) {
						// tpl mode load from separate files
						$this->templates_path_main = $this->templates_path_main.$this->relative_tpath_separates_blitz;
					} else {
						// php-array mode from one php file with templates array
						$this->templates_path_main = $this->templates_path_main.$this->relative_tpath_singlefile_blitz;
					}
				} else {
					// php-array mode from one php file with templates array in eval mode
					$this->templates_path_main = $this->templates_path_main.$this->relative_tpath_singlefile_eval;
				}
			}
				
			$this->templates_path = $this->templates_path_main;
				
			if ( $this->usemobile && $this->use_tpl_files === false ) {
				$this->templates_path_mobile = $this->templates_path_main.'Mobile/';
				$this->templates_path = $this->templates_path_mobile;
			}
			return $this->templates_path;
		}
		
		/**
		 * Load templates wrapper by ClassName
		 *
		 * @param string $classname
		 */
		private function loadTemplates ( $classname ) {
			// tpl mode check
			if ( $this->use_tpl_files === true ) {
				// mobile check
				if ( $this->usemobile ) {
					$templates_folder = $this->templates_path_main.$classname.'_Mobile/';
				} else {
					$templates_folder = $this->templates_path_main.$classname.'/';
				}
				// load templates from files in folder
				$this->templates = $this->loadCacheOrFile( $templates_folder );
			} else {
				// file name with php array templates
				$templates_file = $this->templates_path.$classname.'.tmpl.php';
				// load template from single file
				$this->templates = $this->loadCacheOrFile( $templates_file );
			}
			// вернем если необходимо
			return $this->templates;
		}
		
		/**
		 * Get files list
		 *
		 * @param 	string 	$dirname	Path to templates folder
		 * @return 	boolean|array
		 */
		private function getFilesList ( $dirname ) {
			// init
			$tpl_files = false;
			// is dir check
			if ( is_dir($dirname) ) {
				// scan for files
				$files = scandir ( $dirname );
				// check files count (except . & ..)
				if ( is_array($files) && count($files) > 2 ) {
					// init
					$tpl_files = array();
					// iterate
					foreach ( $files as $filename ) {
						// exclude "." и ".."
						if ( $filename != '.' && $filename != '..') {
							// дернем pathinfo
							$pathinfo = pathinfo($filename);
						}
						// check file extension
						if ( $pathinfo['extension'] && $pathinfo['extension'] == $this->tpl_ext ) {
							// template name = file name without extension
							$template_name = $pathinfo['filename'];
							// full path to file
							$fullpath_to_tpl = $dirname.$filename;
							// full paths array
							$tpl_files['templates'][$template_name]	= $fullpath_to_tpl;
							// use cache check, for check mtimes
							if ( $this->usecache ) {
								// file stat
								$filestat = stat( $fullpath_to_tpl );
								// mtimes array, used for find updated files and regenerate cache if need
								$tpl_files['mtime'][$template_name]		= $filestat['mtime'];
							}
						}
					}
				}
			}
			// return
			return $tpl_files;
		}
		
		/**
		 * TPL mode (separate files), get last modifiaation time for template file
		 *
		 * @param 	string 	$filename	Path to file or folder
		 * @return 	number
		 */
		private function checkTemplatesModified ( $filename ) {
			// tpl mode check
			if ( $this->use_tpl_files === true ) {
				// get files list
				$tpl_files = $this->getFilesList( $filename );
				// mtimes check
				if ( is_array($tpl_files['mtime']) ) {
					// get maximum mtime
					$file_time	= max($tpl_files['mtime']);
				} else {
					$file_time	= 0;
				}
			} else {
				// check file
				if ( file_exists( $filename ) ) {
					// get stat
					$filestat	= stat( $filename );
					$file_time	=  $filestat['mtime'];
				} else {
					$file_time	= 0;
				}
			}
			// return
			return $file_time;
		}
		
		/**
		 * Load Templates method, from single php file or from separate templates in folder or Redis cache
		 *
		 * @param	string	$filename	File name single php or path to folder with tpl files
		 */
		private function loadCacheOrFile ( $filename ) {
			if ( $this->usecache ) {
				// modifacation time (file or folder)
				$file_time = $this->checkTemplatesModified( $filename );
				// Кешер
				$cacher = new Redis();											// Create Redis Object
				$cacher->select($this->cache_db);								// Switch Redis DB
				$cache_key = 'Template:'.$this->classname.':'.md5($filename);	// Redis Key
				// get ttl redis key
				$ttl = $cacher->ttl($cache_key);
				// key is alive check
				if ( $ttl > 0 ) {
					$whencached = time() - ( $this->cache_time - $ttl );		// Get when set in cache
				} else {
					$whencached = 0;											// Not in cache
				}
				// if file(s) modified time > cached template or not cached check 
				if ( $file_time > $whencached || $ttl < 0 ) {
					// reload from file(s), because file is fresh!
					if ( $this->use_tpl_files === true ) {
						$this->templates = $this->loadFromFolder( $filename );	// load from tpl files
					} else {
						$this->templates = $this->loadFromFile( $filename );	// load from php file
					}
					$this->load_includes_all();									// regenerate all includes in template (Blitz mode check inside)
					$cacher->hMset($cache_key,$this->templates);				// set cache in Redis all included
					$cacher->expire($cache_key,$this->cache_time);				// set ttl fro resid key
				} else {
					// Cache exist or cache is fresh that file(s)
					$this->templates = $cacher->hGetAll($cache_key);			// get templates from cache
					$cacher->expire($cache_key,$this->cache_time);				// extends ttl
				}
			} else {
				// no cache mode
				if ( $this->use_tpl_files === true ) {
					$this->templates = $this->loadFromFolder( $filename );		// load from tpl files
				} else {
					$this->templates = $this->loadFromFile( $filename );		// load from php file
				}
				$this->load_includes_all();										// regenerate all includes in template (Blitz mode check inside)
			}
			// return, if need
			return $this->templates;
		}
		
		/**
		 * Load templates from single file
		 *
		 * @param string $filename
		 */
		private function loadFromFile ( $filename ) {
			$templates = false;
			// check file exist
			if ( file_exists($filename) ) {
				// just include, php file with php array mode
				include $filename;
				// check array $templates
				if ( is_array($templates) ) {
					// deprecated eval mode!
					if ( $this->useblitz === false ) {
						$templates = self::array_addslashes($templates);
					}
				} else {
					// no templates array
					// if you want use custom exception for this
					echo 'Templates file for class: '.$this->classname.' no have templates!';
				}
			} else {
				// no file with templates
				// if you want use custom exception for this
				echo 'Templates file for class: '.$this->classname.' not found! File: '.$filename;
			}
			return $templates;
		}
		
		/**
		 * Load templates from separates .tpl files in folder
		 *
		 * @param 	string 	$dirname
		 * @return 	boolean|array
		 */
		private function loadFromFolder ( $dirname ) {
			$templates = false;
			// get file list
			if ( $files_list = $this->getFilesList ( $dirname ) ) {
				// get templates "template_name => file_name"
				$files	= $files_list['templates'];
				// have files
				if ( count($files) > 0 ) {
					// iterate
					foreach ( $files as $template_name=>$filename ) {
						// выдергиваем содержимое файлов
						$templates[$template_name] = file_get_contents( $filename );
					}
					// deprecated eval mode!
					if ( $this->useblitz === false) {
						$templates = self::array_addslashes($templates);
					}
				} else {
					// if you want use custom exception for this
					echo 'No templates files in folder '.$dirname.' for class: '.$this->classname.'!';
				}
			} else {
				// if you want use custom exception for this
				echo 'No templates folder for class: '.$this->classname.'!';
			}
			return $templates;
		}
		
		
		/**
		 * Reload and replace all Blitz includes in loaded templates
		 * Work with $this->templates just in place
		 *
		 * @param 	array 	$templates
		 * @return 	array
		 */
		public function load_includes_all () {
			// blitz mode check
			if ( $this->useblitz ) {
				// Here we replate all blitz includes like {{ include("some.tpl") }} to content of "some.tpl".
				// I know, this is not very optimal from the point of view of optimality, because in fact there is a recursion inside, 
				// but we do not know what template will be called, so for reliability we'll run everything. 
				// Especially since when using a cache this will have to be done only when the templates are reloaded
				if ( is_array($this->templates) ) {
					foreach ( array_keys($this->templates) as $template_name ) {
						$this->templates[$template_name] = $this->load_includes($template_name);
					}
					return $this->templates;
				}
			}
			return $this->templates;
		}
		
		/**
		 * The method of loading the Blitz template into the specified template
		 * The name of the template is passed, the loader checks to see if the contents of the template are in the template array.
		 * Then it calls the replay by the callback, in which it calculates the name of the desired template.
		 * And if it is, it is inserted into this place, and changing the template itself
		 * If the template with this name does not exist in the template array, it remains as it is 
		 * (theoretically gives the blitz to load the template from the file)
		 *
		 * @param	string	$template_name
		 * @return	mixed|boolean
		 */
		private function load_includes ( $template_name ) {
			// check template not empty
			if ( $this->templates[$template_name] != '' ) {
				// load into local var
				$template_content = $this->templates[$template_name];
				// callback replase
				$template_content = preg_replace_callback("/\{\{ include\([\"']{1}([^\"']+)[\"']{1}\) \}\}/",array($this,'replace_inlude'),$template_content);
				// reload replaced into templates
				$this->templates[$template_name] = $template_content;
				// return for callback if need
				return $template_content;
			} else {
				return false;
			}
		}
		
		/**
		 * Callback replate method
		 * @param	array		$match
		 * @return 	multitype
		 */
		private function replace_inlude( $match ) {
			// get matches
			list( $all, $template ) = $match;
			// if .tpl substing exist(.tpl used for compatibility with Blitz include)
			if ( mb_stripos($template, '.tpl') ) {
				// remove .tpl, get clean template name
				$template_name = str_replace('.tpl', '', $template);
			} else {
				// get as is
				$template_name = $template;
			}
			// check target template exist
			if ( isset($this->templates[$template_name]) ) {
				// recursed load includes into target template 
				$included_template = $this->load_includes($template_name);
				if ( $included_template ) {
					$this->templates[$template_name] = $included_template;
				}
				// return for recursion
				return $included_template;
			} else {
				// return as is, no target template 
				return $all;
			}
		}
		
		/**
		 * Recursive add_slashes utils
		 */
		public static function array_addslashes( $variable ) {
			if ( is_string( $variable ) ) {
				$variable = trim($variable);
				return addslashes( $variable );
			}
			if ( is_array( $variable ) ) {
				foreach( $variable as $i=>$value ) {
					$variable[$i] = self::array_addslashes( $value );
				}
			}
			return $variable ;
		}
	}
?>