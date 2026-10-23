<?php

/**
 * FileManager Class for PHP Compatibility Checker
 *
 * Handles file system operations including PHP file discovery, temporary file
 * management, and exclusion pattern filtering.
 */

namespace EH\PHPCompatibilityChecker\Core;

/**
 * FileManager Class
 *
 * Manages file system operations for PHP compatibility scanning.
 */
class FileManager
{

	/**
	 * Get all PHP files from a directory, respecting exclusion patterns
	 *
	 * @param string $path             Directory path to scan for PHP files.
	 * @param array  $exclude_patterns Array of patterns to exclude from scanning.
	 * @return array Array of PHP file paths.
	 */
	public function get_php_files($path, $exclude_patterns = array())
	{
		if (! is_dir($path)) {
			return file_exists($path) && $this->is_php_file($path) ? array($path) : array();
		}

		$files    = array();
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($iterator as $file) {
			if (! $this->is_php_file($file->getPathname())) {
				continue;
			}

			if ($this->should_exclude_file($file->getPathname(), $path, $exclude_patterns)) {
				continue;
			}

			$files[] = $file->getPathname();
		}

		return $files;
	}

	/**
	 * Check if a file is a PHP file based on extension
	 *
	 * @param string $file_path Path to the file to check.
	 * @return bool True if the file is a PHP file, false otherwise.
	 */
	private function is_php_file($file_path)
	{
		$php_extensions = array('php', 'php3', 'php4', 'php5', 'phtml', 'inc');
		$extension      = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
		return in_array($extension, $php_extensions, true);
	}

	/**
	 * Check if a file should be excluded based on patterns
	 *
	 * @param string $file_path        Path to the file to check.
	 * @param string $base_path        Base directory path for relative path calculation.
	 * @param array  $exclude_patterns Array of patterns to exclude from scanning.
	 * @return bool True if the file should be excluded, false otherwise.
	 */
	private function should_exclude_file($file_path, $base_path, $exclude_patterns)
	{
		if (empty($exclude_patterns)) {
			return false;
		}

		$relative_path = str_replace('\\', '/', substr($file_path, strlen($base_path) + 1));

		foreach ($exclude_patterns as $pattern) {
			$pattern = str_replace('\\', '/', $pattern);
			if (fnmatch($pattern, $relative_path)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Create a temporary directory for plugin operations
	 *
	 * @return string Path to the temporary directory.
	 */
	public function create_temp_directory()
	{
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/phpcompat-temp';

		if (! file_exists($temp_dir)) {
			wp_mkdir_p($temp_dir);
		}

		return $temp_dir;
	}

	/**
	 * Create a temporary file with the given content
	 *
	 * @param string $content Content to write to the temporary file.
	 * @param string $prefix  Prefix for the temporary file name.
	 * @return string|false Path to the temporary file on success, false on failure.
	 */
	public function create_temp_file($content, $prefix = 'phpcompat_')
	{
		$temp_dir  = $this->create_temp_directory();
		$temp_file = $temp_dir . '/' . $prefix . uniqid() . '.tmp';

		if (file_put_contents($temp_file, $content) === false) {
			return false;
		}

		return $temp_file;
	}

	/**
	 * Clean up a temporary file
	 *
	 * @param string $file_path Path to the temporary file to clean up.
	 */
	public function cleanup_temp_file($file_path)
	{
		if ($file_path && file_exists($file_path)) {
			wp_delete_file($file_path);
		}
	}
}
