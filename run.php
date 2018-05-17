<?php

namespace FEBuilder;
use \JSMin\JSMin;

define('JS_FILENAME', 'app.min.js');
define('CSS_FILENAME', 'app.min.css');
define('JS_PATH', 'js');
define('CSS_PATH', 'css');

$fileRep = array();

/**
 * On-the-fly CSS Compression
 * Copyright (c) 2009 and onwards, Manas Tungare.
 * Creative Commons Attribution, Share-Alike.
 *
 * In order to minimize the number and size of HTTP requests for CSS content,
 * this script combines multiple CSS files into a single file and compresses it.
 */
function cssBuild () {
	/* Add your CSS files to this array */
	$cssFiles = array(
		'reset.css',
		'font.css',
		'grid.css',
	);
	$output = "";
	foreach ($cssFiles as $cssFile) {
		$output .= file_get_contents(CSS_PATH.'/'.$cssFile);
	}
	// Remove comments
	$output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $output);
	// Remove space after colons
	$output = str_replace(': ', ':', $output);
	// Remove whitespace
	$output = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $output);
	file_put_contents(CSS_PATH.'/'.CSS_FILENAME, $output);
}

// https://stackoverflow.com/a/11000599/1590519
function jsBuild () {
	$jsFiles = array(
		'lib/3.1.1-jquery.min.js',
		'init.js',
	);
	$output = "";
	foreach ($jsFiles as $jsFile) {
		$output .= file_get_contents(JS_PATH.'/'.$jsFile);
	}
	require_once(realpath(dirname(__FILE__)).'/jsmin.php');
	$output = JSMin::minify($output);
	file_put_contents(JS_PATH.'/'.JS_FILENAME, $output);
}

function build() {
	echo "running build\n> ";
	cssBuild();
	jsBuild();
}

// https://stackoverflow.com/a/24784144/1590519
function getDirContents ($dir, $extFilter = array('js', 'css'), &$results = array()) {
	$files = scandir($dir);

	foreach ($files as $file) {
		$path = realpath($dir.DIRECTORY_SEPARATOR.$file);

		if (!is_dir($path)) {
			foreach ($extFilter as $ext) {
				if (
					pathinfo($path, PATHINFO_EXTENSION) == $ext &&
					basename($path) != JS_FILENAME &&
					basename($path) != CSS_FILENAME
				) {
					$results[] = $path;
				}
			}

		} else if ($file != "." && $file != "..") {
			getDirContents($path, $extFilter, $results);
		}
	}

	return $results;
}

function getFileRep () {
	global $fileRep;
	if (empty($fileRep)) {

		$filePaths = getDirContents(realpath(dirname(__FILE__)));
		foreach ($filePaths as $filePath) {
			$obj = (object)[];
			$obj->path = $filePath;
			$obj->modified = filemtime($filePath);
			$fileRep[] = $obj;
		}

	}
}

function run () {
	global $fileRep;

	$runningBuild = false;
	$runFilePaths = getDirContents(realpath(dirname(__FILE__)));
	$newFiles     = false;
	$delFiles     = false;

	foreach ($fileRep as $k => $file) {
		$fileFound = false;
		foreach ($runFilePaths as $runFilePath) {

			if ($file->path == $runFilePath) { // matched key.
				$fileFound = true;
				$modTime = filemtime($runFilePath);

				// if file was modificated in the meantime:
				if ($file->modified != $modTime) {

					$runningBuild = true;

					// and safe the modificated value in $fileRep:
					$fileRep[$k]->modified = $modTime;
				}

			}
		}

		if ($fileFound == false) { // file was deleted.
			$delFiles = true;

			$runningBuild = true;
		}
	}

	// check for new files:
	foreach ($runFilePaths as $runFilePath) {
		$fileFound = false;
		foreach ($fileRep as $k => $file) {
			if ($runFilePath == $file->path) { // matched key.
				$fileFound = true;
			}
		}
		if ($fileFound == false) { // new file was added.
			$newFiles = true;

			$runningBuild = true;
		}
	}

	// get a new $fileRep if new files added:
	if ($newFiles) {
		echo "new file added\n> ";
		$fileRep = array();
		getFileRep();
	}

	// get a new $fileRep if files deleted:
	if ($delFiles) {
		echo "file deleted\n> ";
		$fileRep = array();
		getFileRep();
	}

	if ($runningBuild) {
		build();
	}

}

function init () {
	getFileRep();

	// https://stackoverflow.com/a/11026188/1590519
	stream_set_blocking(STDIN, false);
	$time = microtime(true);
	$line = '';
	echo "> Type q to exit\n> ";

	while (true) {

		if (microtime(true) - $time > 0.25) { // run each 0.25s.
			run();
			$time = microtime(true);
		}

		$c = fgetc(STDIN);
		if ($c !== false) {
			if ($c != "\n") {
				$line .= $c;
			} else {
				if ($line == 'exit' || $line == 'quit' || $line == 'q') {
					break;
				} else if ($line == 'help') {
					echo "> Type q to exit\n";
				} else if ($line === '') {
					echo '> ';
				} else {
					echo "Unrecognized command.\n> ";
				}

				$line = '';
			}
		}
	}

}

init();
