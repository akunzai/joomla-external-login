#!/usr/bin/env php
<?php

/**
 * Create Joomla! extension zip from manifest file
 * 
 * @see https://docs.joomla.org/Manifest_files
 * @author      Charley Wu <akunzai@gmail.com>
 */

if ($argc < 3) {
	echo "Usage: $argv[0] manifest.xml archive.zip\n";
	exit;
}
$file = $argv[1];
if (!file_exists($file)) {
	exit("Failed to open $file.");
}
$builder = new JoomlaArchiveBuilder($file);
$zipPath = $argv[2];
$builder->build($zipPath);

class JoomlaArchiveBuilder
{
	private string $file;
	private string $type;
	private SimpleXMLElement $xml;
	private ZipArchive $zip;
	private string $prefix;
	public function __construct(string $file)
	{
		$this->xml = simplexml_load_file($file);
		if (!$this->xml) {
			throw new Exception("Failed to load $file.");
		}
		$this->type = $this->xml->attributes()['type'];
		$this->file = $file;
		$this->prefix = dirname($file);
		$this->zip = new ZipArchive;
	}

	public function build($zipFile)
	{
		$this->zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$this->addFile($this->file);
		$this->addFiles();
		$this->addLanguages();
		$this->addMediaFiles();
		$this->zip->close();
	}

	private function addLanguages()
	{
		if (empty($this->xml->languages)) return;
		foreach ($this->xml->languages->language as $language) {
			$this->addFile($language, $this->xml->languages->attributes()->folder);
		}
		if ($this->type == 'component' && !empty($this->xml->administration)) {
			if (empty($this->xml->administration->languages)) return;
			foreach ($this->xml->administration->languages->language as $language) {
				$this->addFile($language, $this->xml->administration->languages->attributes()->folder);
			}
		}
	}

	private function addMediaFiles()
	{
		if ($this->type == 'package') return;
		if (empty($this->xml->media)) return;
		foreach ($this->xml->media->folder as $folder) {
			$path = $this->joinPaths($this->prefix, $this->xml->media->attributes()->folder, $folder);
			$this->addDirectory($path);
		}
		foreach ($this->xml->media->filename as $filename) {
			$this->addFile($filename, $this->xml->media->attributes()->folder);
		}
	}

	private function addFiles()
	{
		if (empty($this->xml->files)) return;
		if ($this->type == 'package') {
			foreach ($this->xml->files->file as $file) {
				$this->addFile($file, $this->xml->files->attributes()->folder);
			}
			return;
		}

		foreach ($this->xml->files->folder as $folder) {
			$path = $this->joinPaths($this->prefix, $this->xml->files->attributes()->folder, $folder);
			$this->addDirectory($path);
		}
		foreach ($this->xml->files->filename as $filename) {
			$this->addFile($filename, $this->xml->files->attributes()->folder);
		}
		if ($this->type == 'component' && !empty($this->xml->administration)) {
			if (empty($this->xml->administration->files)) return;
			foreach ($this->xml->administration->files->folder as $folder) {
				$path = $this->joinPaths($this->prefix, $this->xml->administration->files->attributes()->folder, $folder);
				$this->addDirectory($path);
			}
			foreach ($this->xml->administration->files->filename as $filename) {
				$this->addFile($filename, $this->xml->administration->files->attributes()->folder);
			}
		}
	}

	private function addFile($file, $folder = null)
	{
		$path = (str_starts_with($file, $this->prefix)) ? $file : $this->joinPaths($this->prefix, $folder, $file);
		$entry = str_replace($this->prefix, "", $path);
		echo "adding: $path\t$entry\n";
		$this->zip->addFile($path, $entry);
	}

	private function addDirectory($path)
	{
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
		foreach ($files as $file) {
			$filename = $file->getFilename();
			if (str_starts_with($filename, '.')) continue;
			if ($file->isDir()) {
				$this->zip->addEmptyDir(str_replace($this->prefix, '', $file));
				continue;
			}
			if ($file->isFile()) {
				$entry = str_replace($this->prefix, "", $file);
				echo "adding: $path\t$entry\n";
				$this->zip->addFile($file, $entry);
				continue;
			}
		}
	}

	private function joinPaths(...$paths)
	{
		if (sizeof($paths) === 0) return '';
		$prefix = ($paths[0] === DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
		$processed = array_map(function ($part) {
			return rtrim($part, DIRECTORY_SEPARATOR);
		}, array_filter($paths, function ($path) {
			return !empty($path);
		}));
		return $prefix . implode(DIRECTORY_SEPARATOR, $processed);
	}
}
