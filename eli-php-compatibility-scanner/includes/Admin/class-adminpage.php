<?php

/**
 * AdminPage Class for PHP Compatibility Checker
 *
 * Handles the rendering of the admin interface for the PHP compatibility checker,
 * including scan options, target selection, and results display.
 */

namespace EH\PHPCompatibilityChecker\Admin;

/**
 * AdminPage Class
 *
 * Renders the admin interface for PHP compatibility scanning.
 */
class AdminPage
{

	/**
	 * Render the admin page with all UI elements
	 */
	public function render()
	{
?>
		<div class="wrap">
			<h1><?php echo esc_html(__('PHP Compatibility Scanner', 'eli-php-compatibility-scanner')); ?></h1>
			<p><?php echo esc_html(__('Check your site plugins and themes for PHP version compatibility using PHPCompatibility.', 'eli-php-compatibility-scanner')); ?></p>

			<?php

				?>
			<div class="phpcompat-layout">
				<div class="phpcompat-col-main">
					<!-- How to Use Section -->
			<div class="phpcompat-how-to-use">
				<h2 class="phpcompat-subheading">
					<span class="phpcompat-toggle-icon">▼</span>
					<?php echo esc_html(__('How to Use', 'eli-php-compatibility-scanner')); ?>
				</h2>
				<div class="phpcompat-how-to-use-content">
					<ol>
						<li><strong>Select Targets:</strong> Choose which plugins and themes to scan by checking the boxes below.</li>
						<li><strong>Configure Options:</strong> Set your preferred PHP version, batch size, and report mode.</li>
						<li><strong>Start Scan:</strong> Click Start Scan to begin the compatibility check.</li>
						<li><strong>Review Results:</strong> Results will appear below each target with error counts and details.</li>
						<li><strong>Interpret Status:</strong>
							<span class="phpcompat-status-example">✅ No issues found</span> |
							<span class="phpcompat-status-example">❌ X errors found</span> |
							<span class="phpcompat-status-example">⚠️ Y warnings found</span>
						</li>
					</ol>
				</div>
			</div>

			<h2 class="phpcompat-subheading"><?php echo esc_html(__('System check', 'eli-php-compatibility-scanner')); ?></h2>
			<pre id="phpcompat-system-check" class="phpcompat-output"><?php echo esc_html(__('Checking environment', 'eli-php-compatibility-scanner')); ?></pre>

			<?php

				?>
			<div id="phpcompat-system-help" class="phpcompat-system-help" hidden>
				<p><?php echo esc_html(__('This site cannot run local scans. The checks above show what is missing.', 'eli-php-compatibility-scanner')); ?></p>
				<p>
					<?php
					printf(
						/* translators: %s: link to the support forum. */
						esc_html__("%1\$s That's usually a setup issue rather than a limitation. Post the checks above on the %2\$s and we'll help where we can.", 'eli-php-compatibility-scanner'),
						'<strong>' . esc_html__('On a dev environment and sure this should run?', 'eli-php-compatibility-scanner') . '</strong>',
						'<a href="https://wordpress.org/support/plugin/eli-php-compatibility-scanner/" target="_blank" rel="noopener noreferrer">' . esc_html__('support forum', 'eli-php-compatibility-scanner') . '</a>'
					);
					?>
				</p>
				<p>
					<?php
					printf(
						/* translators: %s: link to the paid edition. */
						esc_html__('Otherwise, scan from the cloud with %s, which does not need exec() on your server and can be used on live websites.', 'eli-php-compatibility-scanner'),
						Promo::pro_link('system-check')
					);
					?>
				</p>
			</div>

			<!-- Scan Options & Overview -->
			<div class="phpcompat-sticky-controls is-checking" aria-busy="true">
				<div class="phpcompat-sticky-header">
					<h2 class="phpcompat-subheading"><?php echo esc_html(__('Scan Options', 'eli-php-compatibility-scanner')); ?></h2>
					<button type="button" id="phpcompat-pin-overview" class="phpcompat-pin-btn" title="<?php echo esc_attr(__('Pin this section to stay visible while scrolling', 'eli-php-compatibility-scanner')); ?>">
						<span class="dashicons dashicons-admin-post phpcompat-pin-icon"></span>
					</button>
				</div>

				<?php

					?>
				<div class="phpcompat-options-skeleton" aria-hidden="true">
					<?php

						?>
					<?php if (has_action('phpcompat_checker_scan_mode_controls')) : ?>
						<span class="phpcompat-skeleton-bar phpcompat-skeleton-bar--mode"></span>
					<?php endif; ?>
					<span class="phpcompat-skeleton-bar phpcompat-skeleton-bar--select"></span>
					<span class="phpcompat-skeleton-bar phpcompat-skeleton-bar--select"></span>
					<span class="phpcompat-skeleton-bar phpcompat-skeleton-bar--toggle"></span>
					<span class="phpcompat-skeleton-bar phpcompat-skeleton-bar--button"></span>
				</div>
				<p class="phpcompat-options-checking-note"><?php echo esc_html(__('Checking your environment before scan options become available…', 'eli-php-compatibility-scanner')); ?></p>

				<div class="phpcompat-options">
					<?php
					/**
					 * Scan-mode controls.
					 *
					 * This plugin scans locally and renders nothing here. An
					 * add-on that can scan some other way renders its own mode
					 * toggle on this action, along with the hidden
					 * #phpcompat-scan-mode input the client reads. With no
					 * add-on there is no mode to choose, so no control is shown.
					 */
					do_action('phpcompat_checker_scan_mode_controls');
					?>

					<div class="phpcompat-option-group">
						<label for="php-version"><?php echo esc_html(__('Target PHP Version:', 'eli-php-compatibility-scanner')); ?></label>
						<select id="php-version" name="php-version">
							<option value="7.4">PHP 7.4</option>
							<option value="8.0">PHP 8.0</option>
							<option value="8.1">PHP 8.1</option>
							<option value="8.2">PHP 8.2</option>
							<option value="8.3" selected>PHP 8.3</option>
							<option value="8.4">PHP 8.4</option>
						</select>
						<span class="phpcompat-help-tip" title="<?php echo esc_attr(__('Select the PHP version you want to check compatibility against. The scan will identify code that may not work in this version.', 'eli-php-compatibility-scanner')); ?>">?</span>
					</div>

					<div class="phpcompat-option-group">
						<label for="batch-size"><?php echo esc_html(__('Batch Size:', 'eli-php-compatibility-scanner')); ?></label>
						<select id="batch-size" name="batch-size">
							<option value="10">10 files</option>
							<option value="25">25 files</option>
							<option value="50" selected>50 files</option>
							<option value="75">75 files</option>
							<option value="100">100 files</option>
						</select>
						<span class="phpcompat-help-tip" title="<?php echo esc_attr(__('Number of files to process in each batch. Smaller batches use less memory but take longer to complete.', 'eli-php-compatibility-scanner')); ?>">?</span>
					</div>

					<div class="phpcompat-option-group">
						<label for="skip-vendor">
							<input type="checkbox" id="skip-vendor" name="skip-vendor" value="1" checked>
							<span class="toggle-label"><?php echo esc_html(__('Skip Vendor Directory', 'eli-php-compatibility-scanner')); ?></span>
							<span class="phpcompat-help-tip" title="<?php echo esc_attr(__('Skip scanning files in vendor directories. This excludes third-party dependencies and can significantly speed up scans.', 'eli-php-compatibility-scanner')); ?>">?</span>
						</label>
					</div>

					<div class="phpcompat-option-group">
						<button id="phpcompat-start-scan" class="button button-primary" disabled><?php echo esc_html(__('Start Scan', 'eli-php-compatibility-scanner')); ?></button>
						<button id="phpcompat-stop-scan" class="button button-secondary" style="display: none;"><?php echo esc_html(__('Stop Scan', 'eli-php-compatibility-scanner')); ?></button>
						<span id="phpcompat-scan-status" class="phpcompat-status"></span>
					</div>
				</div>

				<h3 class="phpcompat-subheading phpcompat-overview-heading"><?php echo esc_html(__('Scan Overview', 'eli-php-compatibility-scanner')); ?></h3>
				<div id="phpcompat-overview-list" class="phpcompat-overview-list">
					<span class="phpcompat-overview-empty"><?php echo esc_html(__('No targets selected', 'eli-php-compatibility-scanner')); ?></span>
				</div>
			</div>

			<h2 class="phpcompat-subheading"><?php echo esc_html(__('Targets', 'eli-php-compatibility-scanner')); ?></h2>
					<div class="phpcompat-target-actions">
						<button type="button" id="phpcompat-select-all" class="button"><?php echo esc_html(__('Select All', 'eli-php-compatibility-scanner')); ?></button>
						<button type="button" id="phpcompat-select-active" class="button"><?php echo esc_html(__('Select Active', 'eli-php-compatibility-scanner')); ?></button>
					</div>
					<div id="phpcompat-targets" class="phpcompat-targets"></div>
				</div>

				<div class="phpcompat-col-rail">
					<?php Promo::render(); ?>
				</div>
			</div>
		</div>
<?php
	}
}
