<?php

/**
 * Scanner Class for PHP Compatibility Checker
 *
 * Handles PHP compatibility scanning operations including system requirements,
 * batch processing, and command execution.
 */

namespace EH\PHPCompatibilityChecker\Core;

/**
 * Scanner Class
 *
 * Manages PHP compatibility scanning operations and batch processing.
 */
class Scanner
{

	/**
	 * Whether to use global phpcs installation
	 *
	 * @var bool
	 */
	private $use_global_phpcs = false;

	/**
	 * Temporary file reference for batch processing
	 *
	 * @var string|null
	 */
	private $temp_file = null;

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
	 * Check if system requirements are met for running scans
	 */
	public function check_system_requirements()
	{
		$plugin_dir = dirname(dirname(__DIR__));

		$status = array(
			'execEnabled'        => true,
			'phpBinary'          => null,
			'phpBinaryExists'    => false,
			'phpcsPath'          => null,
			'phpcsExists'        => false,
			'phpcsVersionCmd'    => null,
			'phpcsVersionOk'     => false,
			'phpcsVersionOutput' => '',
			'messages'           => array(),
			'ready'              => false,
		);

		$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
		if (in_array('exec', $disabled, true)) {
			$status['execEnabled'] = false;
			$status['messages'][]  = __('exec() is disabled in php.ini', 'eli-php-compatibility-scanner');
		}

		$command_builder           = new CommandBuilder($this->use_global_phpcs);
		$status['phpBinary']       = $command_builder->detect_php_binary();
		$status['phpBinaryExists'] = (bool) ($status['phpBinary'] && file_exists($status['phpBinary']));
		$status['phpcsPath']       = $command_builder->resolve_phpcs_path($plugin_dir);
		$status['phpcsExists']     = file_exists($status['phpcsPath']);

		if ($status['execEnabled'] && $status['phpBinaryExists'] && $status['phpcsExists']) {
			$version_result               = $this->test_phpcs_version($status['phpBinary'], $status['phpcsPath']);
			$status['phpcsVersionCmd']    = $version_result['cmd'];
			$status['phpcsVersionOk']     = $version_result['success'];
			$status['phpcsVersionOutput'] = $version_result['output'];

			if (! $version_result['success']) {
				$status['messages'][] = __('Unable to run phpcs --version with detected PHP binary.', 'eli-php-compatibility-scanner');
			}
		}

		$status['ready'] = ($status['execEnabled'] && $status['phpBinaryExists'] && $status['phpcsExists'] && $status['phpcsVersionOk']);

		return $status;
	}

	/**
	 * Run a single scan on the specified path
	 *
	 * @param string|null $scan_path   Path to scan for PHP files.
	 * @param string|null $php_version Target PHP version for compatibility check.
	 * @return string Scan results or error message.
	 */
	public function run($scan_path = null, $php_version = null)
	{
		if (! $scan_path || ! file_exists($scan_path)) {
			return "❌ No valid scan target selected.\n";
		}

		if (! $this->is_phpcs_available()) {
			return "❌ Error: phpcs command not found. Please install PHP CodeSniffer first.\n";
		}

		$php_version = $php_version ? $php_version : get_option('phpcompat_checker_php_version', '8.3');
		$command     = $this->build_command(array($scan_path), $php_version);

		$output      = array();
		$return_code = 0;
		exec($command, $output, $return_code);

		return implode("\n", $output);
	}

	/**
	 * Get scan progress information and initialize server-side state
	 *
	 * @param string $scan_path         Path to scan for PHP files.
	 * @param int    $batch_size        Number of files per batch.
	 * @param array  $exclude_patterns  Patterns to exclude from scanning.
	 * @return array Scan progress information.
	 */
	public function get_scan_progress($scan_path, $batch_size = 50, $exclude_patterns = array())
	{
		$file_manager = new FileManager();
		$files        = $file_manager->get_php_files($scan_path, $exclude_patterns);

		if (empty($files)) {
			return array(
				'total_files'   => 0,
				'total_batches' => 0,
				'scan_id'       => null,
				'message'       => 'No PHP files found to scan.',
			);
		}

		$scan_session = new ScanSession();
		$scan_id      = $scan_session->create_session($files, $batch_size, $exclude_patterns);

		return array(
			'total_files'       => count($files),
			'estimated_batches' => ceil(count($files) / $batch_size),
			'scan_id'           => $scan_id,
		);
	}

	/**
	 * Get batch files from stored scan session
	 *
	 * @param string $scan_id      Scan session ID.
	 * @param int    $batch_number Batch number to retrieve.
	 * @return array Batch information including files and metadata.
	 */
	public function get_batch_files_from_scan($scan_id, $batch_number)
	{
		$scan_session = new ScanSession();
		return $scan_session->get_batch($scan_id, $batch_number);
	}

	/**
	 * Scan a batch of files
	 *
	 * @param array  $files       Array of PHP files to scan.
	 * @param string $php_version Target PHP version for compatibility check.
	 * @return array Scan results including output and error counts.
	 */
	public function scan_batch($files, $php_version)
	{
		if (empty($files)) {
			return array(
				'files'    => array(),
				'output'   => '✓ No files to scan in this batch.',
				'errors'   => 0,
				'warnings' => 0,
			);
		}

		$command = $this->build_command($files, $php_version);

		$output      = array();
		$return_code = 0;
		exec($command, $output, $return_code);

		if ($this->temp_file && file_exists($this->temp_file)) {
			wp_delete_file($this->temp_file);
			$this->temp_file = null;
		}

		$output_text  = implode("\n", $output);
		$error_counts = $this->parse_error_counts($output_text);

		$output_text = $output_text ? $output_text : '✓ No PHP compatibility issues found in this batch.';

		return array(
			'output'   => $output_text,
			'errors'   => $error_counts['errors'],
			'warnings' => $error_counts['warnings'],
		);
	}

	/**
	 * Scan directory in batches (legacy method)
	 *
	 * @param string $scan_path Path to scan for PHP files.
	 * @return array Batch scan results.
	 */
	public function scan_in_batches($scan_path)
	{
		$file_manager = new FileManager();
		$files        = $file_manager->get_php_files($scan_path);

		if (empty($files)) {
			return array('message' => 'No PHP files found to scan.');
		}

		$batch_size  = get_option('phpcompat_checker_batch_size', 50);
		$php_version = get_option('phpcompat_checker_php_version', '8.3');

		$batches = array_chunk($files, $batch_size);
		$results = array();

		foreach ($batches as $index => $batch) {
			$batch_result = $this->scan_batch($batch, $php_version);
			$results[]    = array(
				'batch'       => $index + 1,
				'files_count' => count($batch),
				'output'      => $batch_result['output'],
				'errors'      => $batch_result['errors'],
				'warnings'    => $batch_result['warnings'],
			);
		}

		return array('batches' => $results);
	}

	/**
	 * Test phpcs version command
	 *
	 * @param string $php_binary PHP binary path.
	 * @param string $phpcs_path PHPCS path.
	 * @return array Test results including command, success status, and output.
	 */
	private function test_phpcs_version($php_binary, $phpcs_path)
	{
		$is_windows = stripos(PHP_OS, 'WIN') === 0;
		$php_q      = $is_windows ? '"' . str_replace('"', '""', $php_binary) . '"' : escapeshellarg($php_binary);
		$pcs_q      = $is_windows ? '"' . str_replace('"', '""', $phpcs_path) . '"' : escapeshellarg($phpcs_path);
		$cmd        = $php_q . ' ' . $pcs_q . ' --version';

		$output      = array();
		$return_code = 0;
		@exec($cmd, $output, $return_code);

		$filtered = array_values(
			array_filter(
				$output,
				function ($line) {
					$line = trim((string) $line);
					return '' !== $line && ! preg_match('/^(X-Powered-By:|Content-type:)/i', $line);
				}
			)
		);

		return array(
			'cmd'     => $cmd,
			'success' => (0 === $return_code),
			'output'  => implode("\n", $filtered),
		);
	}

	/**
	 * Build phpcs command for scanning files
	 *
	 * @param array  $paths       Array of file paths to scan.
	 * @param string $php_version Target PHP version for compatibility check.
	 * @return string Built command string.
	 */
	private function build_command($paths, $php_version)
	{
		$command_builder = new CommandBuilder($this->use_global_phpcs);
		$command         = $command_builder->build($paths, $php_version, $this->temp_file);
		return $command;
	}

	/**
	 * Check if phpcs is available
	 *
	 * @return bool True if phpcs is available, false otherwise.
	 */
	private function is_phpcs_available()
	{
		$plugin_dir      = dirname(dirname(__DIR__));
		$command_builder = new CommandBuilder($this->use_global_phpcs);
		$phpcs_path      = $command_builder->resolve_phpcs_path($plugin_dir);
		return file_exists($phpcs_path);
	}

	/**
	 * Parse error and warning counts from phpcs output
	 *
	 * @param string $output PHPCS output text.
	 * @return array Parsed error and warning counts.
	 */
	private function parse_error_counts($output)
	{
		$errors   = 0;
		$warnings = 0;

		$lines = explode("\n", $output);
		foreach ($lines as $line) {
			if (preg_match('/FOUND (\d+) ERRORS? AND (\d+) WARNINGS?/i', $line, $matches)) {
				$errors   += (int) $matches[1];
				$warnings += (int) $matches[2];
			} elseif (preg_match('/FOUND (\d+) ERRORS?/i', $line, $matches)) {
				$errors += (int) $matches[1];
			} elseif (preg_match('/FOUND (\d+) WARNINGS?/i', $line, $matches)) {
				$warnings += (int) $matches[1];
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}
}
