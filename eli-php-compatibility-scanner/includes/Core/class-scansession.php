<?php

/**
 * ScanSession Class for PHP Compatibility Checker
 *
 * Handles scan session management including creation, batch retrieval, and cleanup.
 */

namespace EH\PHPCompatibilityChecker\Core;

/**
 * ScanSession Class
 *
 * Manages PHP compatibility scan sessions, including file batching and cleanup.
 */
class ScanSession
{

	/**
	 * Create a new scan session and store it server-side
	 *
	 * @param array $files            Array of PHP files to scan.
	 * @param int   $batch_size       Number of files per batch.
	 * @param array $exclude_patterns Patterns to exclude from scanning.
	 * @return string Scan session ID.
	 */
	public function create_session($files, $batch_size, $exclude_patterns = array())
	{
		$scan_id = 'phpcompat_scan_' . uniqid();

		$session_data = array(
			'files'            => $files,
			'batch_size'       => $batch_size,
			'exclude_patterns' => $exclude_patterns,
			'created_at'       => time(),
			'total_files'      => count($files),
			'total_batches'    => ceil(count($files) / $batch_size),
		);

		update_option($scan_id, $session_data);

		$this->cleanup_old_sessions();

		return $scan_id;
	}

	/**
	 * Get batch files from a stored scan session
	 *
	 * @param string $scan_id      Scan session ID.
	 * @param int    $batch_number Batch number to retrieve.
	 * @return array Batch information including files and metadata.
	 */
	public function get_batch($scan_id, $batch_number)
	{
		$session_data = get_option($scan_id);

		if (! $session_data || ! isset($session_data['files'])) {
			return array(
				'files'         => array(),
				'batch_number'  => $batch_number,
				'total_batches' => 0,
				'message'       => 'Scan session not found or expired.',
			);
		}

		$files         = $session_data['files'];
		$batch_size    = $session_data['batch_size'];
		$total_batches = $session_data['total_batches'];

		if ($batch_number < 1 || $batch_number > $total_batches) {
			return array(
				'files'         => array(),
				'batch_number'  => $batch_number,
				'total_batches' => $total_batches,
				'message'       => 'Invalid batch number.',
			);
		}

		$start_index = ($batch_number - 1) * $batch_size;
		$batch_files = array_slice($files, $start_index, $batch_size);

		return array(
			'files'         => $batch_files,
			'batch_number'  => $batch_number,
			'total_batches' => $total_batches,
			'is_last_batch' => ($batch_number >= $total_batches),
		);
	}

	/**
	 * Delete a scan session
	 *
	 * @param string $scan_id Scan session ID to delete.
	 */
	public function delete_session($scan_id)
	{
		delete_option($scan_id);
	}

	/**
	 * Clean up old scan sessions (older than 1 hour)
	 */
	private function cleanup_old_sessions()
	{
		global $wpdb;

		$cutoff_time = time() - 3600;

		$options = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				'phpcompat_scan_%'
			)
		);

		foreach ($options as $option) {
			$data = maybe_unserialize($option->option_value);
			if (is_array($data) && isset($data['created_at']) && $data['created_at'] < $cutoff_time) {
				delete_option($option->option_name);
			}
		}
	}
}
