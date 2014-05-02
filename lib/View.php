<?php

/*
	These are constants I use for more readable references to bash messages
*/

/* MESSAGES */
	/* SUCCESS */
	define("WRITE_SUCCESSFUL", 0);
	define("READ_SUCCESSFUL", 1);

	/* FAILURE */
	define("UNRECOGNIZED_EXTENSION", 2);
	define("FILE_NOT_FOUND", 3);

	define("WRITE_FAILED", 4);
	define("READ_FAILED", 5);

	/* INFO */
	define("PREDIRECTIVE", 6);
	define("RESPONDS_WITH", 7);
	define("GOOD_MORNING", 8);
	define("LOOKING_FOR", 9);
	define("POLLING", 10);
	define("DIRECTORY_FOUND", 11);
	define("WATCHING", 12);
	define("FILE_MODIFIED", 13);
	define("POSTDIRECTIVE", 14);
	define("NEWLINE", 15);
	define("WRITE_SORT_OF_SUCCESSFUL", 16);

/*
	These are constants for coloring bash messages
*/

/* BASH COLORS */
define("RED", "\033[0;31m");
define("GREEN", "\033[0;32m");
define("YELLOW", "\033[1;33m");
define("BLUE", "\033[0;34m");
define("RESET", "\033[0m");

class View
{
	//an array of closures to run prior to compiling
	protected $predirectives = [];

	//an array of closures to run after compiling
	protected $postdirectives = [];

	//an array of message texts
	protected $messages = [];

	//whether to display messages
	protected $reporting = true;

	//file to save compilation to
	protected $output_file = 'compiled.html';

	//sources
	protected $sources;

	//directories to watch for changes
	protected $watched;

	//base template
	protected $template;

	//time compilation was started
	protected $start_time;

	//boolean, whether errors occured
	protected $errors;

	//basic constuctor, saves base template
	//message array and displays a general message via $this->shout()
	public function __construct($template)
	{
		$this->template = $template;

		$this->messages = [
			GREEN."Successfully compiled [".RESET."%filename%".GREEN."]!".RESET."\n\nSize: %bytes% bytes\nExecution Time: %time% ms",
			GREEN."Successfully imported file [".RESET."%filename%".GREEN."]\n",
			RED."ERROR -- Requested file [".RESET."%filename%".RED."] has an unregistered file extension\n",
			RED."ERROR -- Requested file [".RESET."%filename%".RED."] cannot be found\n",

			RED."ERROR -- Unable to write to file [".RESET."%filename%".RED."]. Compilation failed\n",
			RED."ERROR -- Unable to read file [".RESET."%filename%".RED."]\n",

			"Precompile: [%directive%]",
			YELLOW."%response%",
			"Hello! Welcome to ".YELLOW."Skeleton".RESET.", a tiny templating engine designed in PHP.\n\tThis software was designed by William Stein.\n\tYou can find more of his work here: [".BLUE."https://github.com/williamstein92".RESET."]\n\tThanks and enjoy!\n",
			"Detected reference to [%filename%]\nLocating file...",
			"Skeleton is watching for changes...",
			"Searching directory [%directory%]...",
			"\t".GREEN."Watching file [".RESET."%filename%".GREEN"]".RESET,
			YELLOW."Detected change in [".RESET."%filename%".YELLOW."]\n",
			"Postcompile: [%directive%]",
			"\n",
			YELLOW."Errors occured during compilation".RESET."\nPlease fix any errors and recompile\nFailed compilation saved to [%filename%]\n"
		];

		$this->shout(GOOD_MORNING, null);
	}

	//turns reporting on
	public function report()
	{
		$this->reporting = true;
	}

	//turns reporting off
	public function silent()
	{
		$this->reporting = false;
	}

	//display a message
	protected function shout($type, $msg)
	{
		if ($type >= 2 && $type < 6) $this->errors = true;

		if ( !$this->reporting) return;

		echo $this->buildMessage($type, $msg);
	}

	//build a message
	protected function buildMessage($type, $msg)
	{
		$text = $this->messages[$type];

		if (is_array($msg)) {
			foreach ($msg as $name => $replacement) {
				$text = preg_replace("/%$name%/", $replacement, $text);
			}
		}

		$text .= RESET."\n";

		return $text;
	}

	//determine whether path extension is recognized
	protected function pathIsRecognized($path)
	{
		$recognized = false;

		foreach($this->sources as $ext => $value) {
			if ($path == $ext) $recognized = true;
		}

		return $recognized;
	}

	//locate a required file
	protected function findFile($filename)
	{
		$full_path_file = false;
		$path_is_recognized = false;

		$path = pathinfo($filename, PATHINFO_EXTENSION);
		
		if ($this->pathIsRecognized($path)) {
			$path_is_recognized = true;
			$filename = str_replace('.'.$path, '', $filename);
		}

		$filename = implode(DIRECTORY_SEPARATOR, explode('.', $filename));
		$filename = ($path && $path_is_recognized) ? $filename.'.'.$path : $filename;

		if (pathinfo($filename, PATHINFO_EXTENSION) == null) $filename .= ".html";

		foreach ($this->sources as $extension => $path) {
			if (pathinfo($filename, PATHINFO_EXTENSION) === $extension) {
				if (is_array($path)) {
					foreach ($path as $p) {
						if (file_exists($path.$filename)) $full_path_file = $path.$filename;
					}
				} else {
					if (file_exists($path.$filename)) $full_path_file = $path.$filename;
				}
			}
		}

		return $full_path_file;
	}

	//directories to watch
	public function watch($directories)
	{
		$this->watched = $directories;
	}

	//where files to build from can be found
	public function compileFrom($sources)
	{
		$this->sources = $sources;
	}

	//where to save the end result
	public function compileTo($path)
	{
		$this->output_file = $path;
	}

	//add a predirective
	public function beforeCompiling($name, $closure)
	{
		$this->predirectives[$name] = $closure;
	}

	//add a postdirective
	public function afterCompiling($name, $closure)
	{
		$this->postdirectives[$name] = $closure;
	}

	//compiles the source
	public function compile()
	{
		$this->start_time = $this->getTime();
		$this->errors = false;

		foreach ($this->predirectives as $name => $directive) {
			$this->shout(PREDIRECTIVE, [
				"directive" => $name
			]);

			$this->shout(RESPONDS_WITH, [
				"response" => $directive()
			]);
		}

		$template = $this->findFile($this->template);

		$HTML = $this->getRequired($template);

		$HTML = $this->build($HTML);

		$this->output($HTML);

		foreach ($this->postdirectives as $name => $directive) {
			$this->shout(POSTDIRECTIVE, [
				"directive" => $name,
			]);

			$this->shout(RESPONDS_WITH, [
				"response" => $directive($HTML),
			]);
		}
	}

	//polls sources for changes
	public function poll()
	{

		$directories = [];
		$files = [];

		foreach ($this->watched as $file) {
			$this->shout(DIRECTORY_FOUND, [
				"directory" => $file,
			]);

			$directories[] = $file;
		}

		//loop through directories referenced by $this->watched
		//and find files to watch
		for ($i = 0; $i < count($directories); $i++) {
			$directory = $directories[$i];

			if ($handle = opendir($directory)) {
				while(($file = readdir($handle)) !== false) {
					if ($file !== '..' && $file !== '.') {
						if (is_dir($directory.$file)) {
							$this->shout(DIRECTORY_FOUND, [
								"directory" => $directory.$file.DIRECTORY_SEPARATOR,
							]);

							$directories[] = $directory.$file.DIRECTORY_SEPARATOR;
						} else {
							$this->shout(WATCHING, [
								"filename" => $directory.$file,
							]);

							$files[$directory.$file] = filemtime($directory.$file);
						}
					}
				}

				closedir($handle);
			}
		}

		$this->shout(NEWLINE, null);
		$this->shout(POLLING, null);

		//poll for changes by monitoring
		//the files' modified dates
		while (1) {
			foreach ($files as $file => $time) {
				if (file_exists($file)) {
					if (filemtime($file) > $time) {
						$this->shout(FILE_MODIFIED, [
							"filename" => $file,
						]);

						$this->compile();

						$this->shout(POLLING, null);

						$files[$file] = filemtime($file);
					}
				} else {
					$this->shout(FILE_NOT_FOUND, [
						"filename" => $file,
					]);

					unset($files[$file]);
				}
			}

			sleep(1);
		}
	}

	//pattern to detect a require call
	protected function requirePattern()
	{
		return '/@require\(\'(.*)\'\)/';
	}

	//pattern to detect a require call
	protected function requireTabsPattern()
	{
		return '/\t*@require\(\'(.*)\'\)/';
	}

	//recursively settle all require calls
	//and build the final file
	protected function build($template)
	{
		$require_pattern = $this->requirePattern();
		$require_tabs_pattern = $this->requireTabsPattern();

		$matches = [];
		$number_of_tabs = [];
		$tabs = '';

		if (preg_match($require_pattern, $template, $matches)) {
			preg_match($require_tabs_pattern, $template, $number_of_tabs);
			$number_of_tabs = strspn($number_of_tabs[0], "\t");

			for ($i = 0; $i < $number_of_tabs; $i++) $tabs .= "\t";

			$matches[0] = preg_replace($require_pattern, '$1', $matches[0]);

			$this->shout(LOOKING_FOR, [
				'filename' => $matches[0]
			]);

			$file = $this->findFile($matches[0]);
			$file = trim($this->getRequired($file));

			$lines = explode(PHP_EOL, $file);

			foreach ($lines as $key => $line) {
				if ($key > 0) $lines[$key] = $tabs.$line;
			}

			$file = implode(PHP_EOL, $lines);

			$HTML = preg_replace($require_pattern, $file, $template, 1);

			return $this->build($HTML);
		} else {
			return $template;
		}
	}

	//get the contents of a required file
	protected function getRequired($filename)
	{
		if ( !file_exists($filename)) {
			$this->shout(FILE_NOT_FOUND, [
				"filename" => $filename,
			]);

			return;
		}

		$contents = file_get_contents($filename);

		if ($contents) {
			$this->shout(READ_SUCCESSFUL, [
				"filename" => $filename,
			]);

			return $contents;
		} else {
			$this->shout(READ_FAILED, [
				"filename" => $filename,
			]);
		}
	}

	//save the final file
	protected function output($HTML)
	{
		$bytes = file_put_contents($this->output_file, $HTML);

		if ($bytes) {
			if ($this->errors) {
				$this->shout(WRITE_SORT_OF_SUCCESSFUL, [
					"filename" => $this->output_file,
				]);
			} else {
				$this->shout(WRITE_SUCCESSFUL, [
					"bytes" => $bytes,
					"filename" => $this->output_file,
					"time" => max($this->getTime() - $this->start_time, 0),
				]);
			}
		} else {
			$this->shout(WRITE_FAILED, [
				"filename" => $this->output_file,
			]);
		}
	}

	//wrapper for getting unix epoch
	protected function getTime()
	{
		return microtime();
	}
}
