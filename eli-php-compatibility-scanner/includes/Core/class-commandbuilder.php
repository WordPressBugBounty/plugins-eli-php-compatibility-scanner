<?php

/**
 * CommandBuilder Class for PHP Compatibility Checker
 *
 * Handles building and constructing PHPCS commands for PHP compatibility scanning,
 * including PHP binary detection and path resolution.
 */

namespace EH\PHPCompatibilityChecker\Core;

/**
 * CommandBuilder Class
 *
 * Builds PHPCS commands for PHP compatibility scanning operations.
 */
class CommandBuilder
{

	/**
	 * Whether to use global phpcs installation
	 *
	 * @var bool
	 */
	private $use_global_phpcs;

	/**
	 * Reference to temporary file for batch processing
	 *
	 * @var string|null
	 */
	private $temp_file_ref;

	/**
	 * Constructor
	 *
	 * @param bool $use_global_phpcs Whether to use global phpcs installation.
	 */
	public function __construct($use_global_phpcs = false)
	{
		$this->use_global_phpcs = (bool) $use_global_phpcs;
	}

	/**
	 * Build phpcs command for scanning files
	 *
	 * @param array       $paths         Array of file paths to scan.
	 * @param string      $php_version   Target PHP version for compatibility check.
	 * @param string|null $temp_file_ref Reference to store temporary file path.
	 * @return string Built command string.
	 */
	public function build($paths, $php_version, &$temp_file_ref = null)
	{
		$plugin_dir = dirname(dirname(__DIR__));

		$php_binary = $this->detect_php_binary();
		$phpcs_path = $this->resolve_phpcs_path($plugin_dir);

		$is_windows = stripos(PHP_OS, 'WIN') === 0;
		$php_q      = $is_windows ? '"' . str_replace('"', '""', $php_binary) . '"' : escapeshellarg($php_binary);
		$pcs_q      = $is_windows ? '"' . str_replace('"', '""', $phpcs_path) . '"' : escapeshellarg($phpcs_path);

		$parts = array(
			$php_q,
			$pcs_q,
			'--extensions=php',
			'--standard=PHPCompatibility',
			'--runtime-set testVersion ' . escapeshellarg($php_version),
			'--no-cache',
		);

		if (count($paths) > 1) {
			$file_manager      = new FileManager();
			$file_list_content = implode("\n", $paths);
			$temp_file         = $file_manager->create_temp_file($file_list_content, 'filelist_');

			if ($temp_file) {
				$temp_file_ref       = $temp_file;
				$this->temp_file_ref = $temp_file;
				$parts[]             = '--file-list=' . ($is_windows ? '"' . str_replace('"', '""', $temp_file) . '"' : escapeshellarg($temp_file));
			} else {

				foreach ($paths as $file_path) {
					$parts[] = escapeshellarg($file_path);
				}
			}
		} else {

			$parts[] = escapeshellarg($paths[0]);
		}

		return implode(' ', $parts);
	}

	/**
	 * Detect PHP binary path
	 *
	 * @return string PHP binary path.
	 */
	public function detect_php_binary()
	{
		$is_windows = stripos(PHP_OS, 'WIN') === 0;
		$php_exe    = 'php' . ($is_windows ? '.exe' : '');

		$php = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : '';

		if ($php && preg_match('/php-(fpm|cgi)(\.exe)?$/i', basename($php))) {
			$cli = $this->find_sibling_cli_php($php);
			if ($cli) {
				$php = $cli;
			}
		}

		if ($php && file_exists($php) && ! $this->looks_like_php_cli($php)) {
			$php = '';
		}

		if (! $php || ! file_exists($php)) {
			$phpbindir = defined('PHP_BINDIR') ? PHP_BINDIR : '';
			if ($phpbindir) {
				$candidate = rtrim($phpbindir, '\\/') . DIRECTORY_SEPARATOR . $php_exe;
				if (file_exists($candidate)) {
					$php = $candidate;
				}
			}
		}

		if ($php && preg_match('/php-cgi(\.exe)?$/i', $php)) {
			$sibling = dirname($php) . DIRECTORY_SEPARATOR . $php_exe;
			if (file_exists($sibling)) {
				$php = $sibling;
			}
		}

		if (! $php) {

			$path = getenv('PATH');
			if ($path) {
				$segments = preg_split('/;|:/', $path);
				foreach ($segments as $dir) {
					$dir = trim($dir);
					if ('' === $dir) {
						continue;
					}
					$candidate = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $php_exe;
					if (file_exists($candidate)) {
						$php = $candidate;
						break;
					}
				}
			}
		}

		$php = $php ? $php : 'php';

		return $php;
	}

	/**
	 * Check whether a binary path looks like a PHP CLI executable.
	 *
	 * Rejects non-PHP executables that may be returned by PHP_BINARY under
	 * Apache mod_php (e.g. httpd.exe, php8apache2_4.dll, php-win.exe).
	 *
	 * @param string $path Binary path to check.
	 * @return bool True if the path looks like a PHP CLI binary.
	 */
	private function looks_like_php_cli($path)
	{
		$basename = strtolower(basename($path));

		if (strpos($basename, 'php') !== 0) {
			return false;
		}

		if (preg_match('/\.(dll|so|dylib)$/i', $basename)) {
			return false;
		}

		if (preg_match('/^(httpd|apache|nginx)/i', $basename)) {
			return false;
		}

		if (preg_match('/^php-?win(\.exe)?$/i', $basename)) {
			return false;
		}

		if (preg_match('/^php-?(fpm|cgi)/i', $basename)) {
			return false;
		}

		return true;
	}

	/**
	 * Find the CLI php binary that ships alongside a php-fpm/php-cgi binary.
	 *
	 * FPM/CGI binaries live in the same PHP install as the CLI binary, but not
	 * always the same directory — on LocalWP/macOS php-fpm is in bin/darwin/sbin
	 * while the CLI php is in bin/darwin/bin. Check the sibling directory and the
	 * parallel bin/ directory.
	 *
	 * @param string $path Path to a php-fpm or php-cgi binary.
	 * @return string CLI php path, or empty string if none found.
	 */
	private function find_sibling_cli_php($path)
	{
		$is_windows = stripos(PHP_OS, 'WIN') === 0;
		$php_exe    = 'php' . ($is_windows ? '.exe' : '');
		$dir        = dirname($path);

		$candidates = array(

			$dir . DIRECTORY_SEPARATOR . $php_exe,

			dirname($dir) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $php_exe,
		);

		foreach ($candidates as $candidate) {
			if (file_exists($candidate) && $this->looks_like_php_cli($candidate)) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Resolve phpcs script path
	 *
	 * Prefers the direct squizlabs bin script over the Composer proxy (vendor/bin/phpcs)
	 * to avoid "namespace must be first statement" errors on Windows when the proxy
	 * file has a BOM or other bytes before the opening tag.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return string PHPCS script path.
	 */
	public function resolve_phpcs_path($plugin_dir)
	{
		$direct = $plugin_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'squizlabs' . DIRECTORY_SEPARATOR . 'php_codesniffer' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpcs';
		$proxy  = $plugin_dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpcs';

		if (file_exists($direct)) {
			return $direct;
		}
		if (file_exists($proxy)) {
			return $proxy;
		}

		return $direct;
	}
}
