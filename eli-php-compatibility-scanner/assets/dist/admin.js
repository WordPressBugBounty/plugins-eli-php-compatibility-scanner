(function(jQuery) {
  "use strict";
  /**
     * PHP Compatibility Checker - Main Entry Point
     * 
     * This file initializes the application using the new class-based architecture.
     * It replaces the old monolithic admin.js file with a clean, modular approach.
     */
  /* global PHPCompatChecker */  let app = null;
  jQuery(function($) {
    const config = typeof window !== "undefined" && window.PHPCompatChecker ? window.PHPCompatChecker : null;
    if (!config || !config.ajaxUrl || !config.nonce) {
      console.error("PHP Compatibility Checker: Missing required configuration");
      return;
    }
    initializeApp(config);
  });
  /**
     * Initialize the main application
     */  async function initializeApp(config) {
    try {
      const {App: App} = await Promise.resolve().then(function() {
        return App$1;
      });
      app = new App(config);
      if (typeof window !== "undefined") {
        window.PHPCompatCheckerApp = app;
      }
      await app.init();
      console.log("PHP Compatibility Checker application initialized successfully");
    } catch (error) {
      console.error("Failed to initialize PHP Compatibility Checker application:", error);
      showInitializationError(error);
    }
  }
  /**
     * Show initialization error in the UI
     */  function showInitializationError(error) {
    const $status = jQuery("#phpcompat-scan-status");
    const $button = jQuery("#phpcompat-start-scan");
    if ($status.length) {
      $status.html(`❌ Failed to initialize: ${error.message}`);
    }
    if ($button.length) {
      $button.prop("disabled", true);
    }
  }
  /**
     * Utility function to get the app instance (for debugging)
     */  function getApp() {
    return app;
  }
  /**
     * Utility function to check if app is ready
     */  function isAppReady() {
    return app && app.isReady();
  }
  if (typeof module !== "undefined" && module.exports) {
    module.exports = {
      initializeApp: initializeApp,
      getApp: getApp,
      isAppReady: isAppReady
    };
  }
  /**
     * App - Main application class that orchestrates all components
     */  class App {
    constructor(config) {
      this.config = config;
      this.ajaxClient = null;
      this.optionsManager = null;
      this.targetsManager = null;
      this.systemChecker = null;
      this.scanModes = null;
      this.scanManager = null;
      this.uiManager = null;
      this.isInitialized = false;
    }
    /**
         * Initialize the application
         */    async init() {
      try {
        if (!this.validateConfig()) {
          throw new Error("Invalid configuration");
        }
        await this.initializeComponents();
        this.isInitialized = true;
        console.log("PHP Compatibility Checker initialized successfully");
      } catch (error) {
        console.error("Failed to initialize application:", error);
        this.showInitializationError(error);
      }
    }
    /**
         * Validate configuration
         */    validateConfig() {
      return this.config && this.config.ajaxUrl && this.config.nonce;
    }
    /**
         * Initialize all application components
         */    async initializeComponents() {
      this.ajaxClient = new ((await Promise.resolve().then(function() {
        return AjaxClient$1;
      })).AjaxClient)(this.config);
      this.optionsManager = new ((await Promise.resolve().then(function() {
        return OptionsManager$1;
      })).OptionsManager)(this.ajaxClient);
      this.targetsManager = new ((await Promise.resolve().then(function() {
        return TargetsManager$1;
      })).TargetsManager)(this.ajaxClient);
      this.systemChecker = new ((await Promise.resolve().then(function() {
        return SystemChecker$1;
      })).SystemChecker)(this.ajaxClient);
      this.scanModes = new ((await Promise.resolve().then(function() {
        return ScanModeRegistry$1;
      })).ScanModeRegistry);
      this.scanManager = new ((await Promise.resolve().then(function() {
        return ScanManager$1;
      })).ScanManager)(this.ajaxClient, this.optionsManager, this.targetsManager, this.systemChecker, this.scanModes);
      this.uiManager = new ((await Promise.resolve().then(function() {
        return UIManager$1;
      })).UIManager);
      const {Modal: Modal} = await Promise.resolve().then(function() {
        return Modal$1;
      });
      if (typeof window !== "undefined") {
        window.PHPCompatModal = Modal;
        window.PHPCompatScanModes = this.scanModes;
        jQuery(document).trigger("phpcompat:modes-ready");
      }
      await this.optionsManager.init();
      await this.targetsManager.init();
      await this.systemChecker.init();
      this.scanManager.init();
    }
    /**
         * Show initialization error
         */    showInitializationError(error) {
      const errorMessage = `Failed to initialize PHP Compatibility Checker: ${error.message}`;
      console.error(errorMessage);
      jQuery(".phpcompat-sticky-controls").removeClass("is-checking").attr("aria-busy", "false");
      const $status = jQuery("#phpcompat-scan-status");
      if ($status.length) {
        $status.html(`❌ ${errorMessage}`);
      }
      const $button = jQuery("#phpcompat-start-scan");
      if ($button.length) {
        $button.prop("disabled", true);
      }
    }
    /**
         * Get AjaxClient instance
         */    getAjaxClient() {
      return this.ajaxClient;
    }
    /**
         * Get OptionsManager instance
         */    getOptionsManager() {
      return this.optionsManager;
    }
    /**
         * Get TargetsManager instance
         */    getTargetsManager() {
      return this.targetsManager;
    }
    /**
         * Get SystemChecker instance
         */    getSystemChecker() {
      return this.systemChecker;
    }
    /**
         * Get ScanManager instance
         */    getScanManager() {
      return this.scanManager;
    }
    /**
         * Get UIManager instance
         */    getUIManager() {
      return this.uiManager;
    }
    /**
         * Check if application is initialized
         */    isReady() {
      return this.isInitialized;
    }
    /**
         * Get application status
         */    getStatus() {
      if (!this.isInitialized) {
        return "Not initialized";
      }
      const systemReady = this.systemChecker ? this.systemChecker.isReady() : false;
      const scanning = this.scanManager ? this.scanManager.isCurrentlyScanning() : false;
      if (scanning) {
        return "Scanning in progress";
      } else if (systemReady) {
        return "Ready for scanning";
      } else {
        return "System not ready";
      }
    }
    /**
         * Refresh application state
         */    async refresh() {
      try {
        if (this.systemChecker) {
          await this.systemChecker.runPreflightCheck();
        }
        if (this.targetsManager) {
          await this.targetsManager.loadTargets();
        }
      } catch (error) {
        console.error("Failed to refresh application:", error);
      }
    }
    /**
         * Reset application state
         */    reset() {
      try {
        if (this.uiManager) {
          this.uiManager.clearScanResults();
        }
        if (this.targetsManager) {
          this.targetsManager.uncheckAllTargets();
        }
      } catch (error) {
        console.error("Failed to reset application:", error);
      }
    }
    /**
         * Destroy application and cleanup
         */    destroy() {
      try {
        if (this.scanManager && this.scanManager.isCurrentlyScanning()) {
          this.scanManager.stopScan();
        }
        this.isInitialized = false;
        console.log("PHP Compatibility Checker destroyed");
      } catch (error) {
        console.error("Error during application destruction:", error);
      }
    }
  }
  var App$1 =  Object.freeze({
    __proto__: null,
    App: App
  });
  /**
     * AjaxClient - Handles all AJAX communication with the WordPress backend
     */  class AjaxClient {
    constructor(config) {
      this.ajaxUrl = config.ajaxUrl;
      this.nonce = config.nonce;
    }
    /**
         * Make a POST request to the WordPress AJAX endpoint
         */    async post(action, data = {}) {
      const requestData = {
        action: action,
        nonce: this.nonce,
        ...data
      };
      try {
        const response = await fetch(this.ajaxUrl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams(requestData)
        });
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        return result;
      } catch (error) {
        console.error("AJAX request failed:", error);
        throw error;
      }
    }
    /**
         * Load saved options from the backend
         */    async loadOptions() {
      return this.post("phpcompat_checker_load_options");
    }
    /**
         * Save options to the backend
         */    async saveOptions(options) {
      return this.post("phpcompat_checker_save_options", {
        report_mode: "detailed",
        ...options
      });
    }
    /**
         * Get system preflight check results
         */    async preflightCheck() {
      return this.post("phpcompat_checker_preflight");
    }
    /**
         * Get available scan targets (plugins and themes)
         */    async getTargets() {
      return this.post("phpcompat_checker_targets");
    }
    /**
         * Get scan progress information
         */    async getProgress(type, slug, batchSize, skipVendor) {
      return this.post("phpcompat_checker_progress", {
        type: type,
        slug: slug,
        batch_size: batchSize,
        skip_vendor: skipVendor ? "1" : "0"
      });
    }
    /**
         * Process a scan batch
         */    async processBatch(scanId, batchNumber) {
      return this.post("phpcompat_checker_process_batch", {
        scan_id: scanId,
        batch_number: batchNumber
      });
    }
    /**
         * Stop an ongoing scan
         */    async stopScan() {
      return this.post("phpcompat_checker_stop_scan");
    }
  }
  var AjaxClient$1 =  Object.freeze({
    __proto__: null,
    AjaxClient: AjaxClient
  });
  /**
     * OptionsManager - Handles plugin options loading, saving, and management
     */  class OptionsManager {
    constructor(ajaxClient) {
      this.ajaxClient = ajaxClient;
      this.options = {
        batch_size: "50",
        php_version: "8.3",
        skip_vendor: true,
        scan_mode: "local"
      };
    }
    /**
         * Initialize options manager
         */    async init() {
      await this.loadOptions();
      this.bindEvents();
    }
    /**
         * Load saved options from the backend
         */    async loadOptions() {
      try {
        const response = await this.ajaxClient.loadOptions();
        if (response && response.success && response.data) {
          this.options = {
            batch_size: response.data.batch_size || "50",
            php_version: response.data.php_version || "8.3",
            skip_vendor: response.data.skip_vendor !== "0",
            scan_mode: response.data.scan_mode || "local"
          };
          this.updateUI();
        }
      } catch (error) {
        console.error("Failed to load options:", error);
      }
    }
    /**
         * Save options to the backend
         */    async saveOptions() {
      try {
        const options = {
          batch_size: this.options.batch_size,
          php_version: this.options.php_version,
          skip_vendor: this.options.skip_vendor ? "1" : "0",
          scan_mode: this.options.scan_mode
        };
        await this.ajaxClient.saveOptions(options);
      } catch (error) {
        console.error("Failed to save options:", error);
      }
    }
    /**
         * Update the UI to reflect current options
         */    updateUI() {
      const $batchSize = jQuery("#batch-size");
      const $phpVersion = jQuery("#php-version");
      const $skipVendor = jQuery("#skip-vendor");
      if ($batchSize.length) $batchSize.val(this.options.batch_size);
      if ($phpVersion.length) $phpVersion.val(this.options.php_version);
      if ($skipVendor.length) $skipVendor.prop("checked", this.options.skip_vendor);
    }
    /**
         * Bind event handlers for option changes
         */    bindEvents() {
      jQuery("#batch-size").on("change", e => {
        this.options.batch_size = e.target.value;
        this.saveOptions();
      });
      jQuery("#php-version").on("change", e => {
        this.options.php_version = e.target.value;
        this.saveOptions();
      });
      jQuery("#skip-vendor").on("change", e => {
        this.options.skip_vendor = e.target.checked;
        this.saveOptions();
      });
    }
    /**
         * Get current options
         */    getOptions() {
      return {
        ...this.options
      };
    }
    /**
         * Get batch size
         */    getBatchSize() {
      return parseInt(this.options.batch_size, 10) || 50;
    }
    /**
         * Get PHP version
         */    getPhpVersion() {
      return this.options.php_version;
    }
    /**
         * Get skip vendor setting
         */    getSkipVendor() {
      return this.options.skip_vendor;
    }
    /**
         * Get the persisted scan mode. The core only ever stores 'local'.
         */    getScanMode() {
      return this.options.scan_mode || "local";
    }
    /**
         * Persist the scan mode. The mode buttons/hidden input are owned by
         * ScanManager; this only stores the value server-side alongside the other
         * options so it survives a reload the same way they do.
         */    setScanMode(mode) {
      if (mode === this.options.scan_mode) {
        return;
      }
      this.options.scan_mode = mode;
      this.saveOptions();
    }
  }
  var OptionsManager$1 =  Object.freeze({
    __proto__: null,
    OptionsManager: OptionsManager
  });
  /**
     * TargetsManager - Handles loading and managing scan targets (plugins and themes)
     */  class TargetsManager {
    constructor(ajaxClient) {
      this.ajaxClient = ajaxClient;
      this.targets = {
        plugins: [],
        themes: []
      };
    }
    /**
         * Initialize targets manager
         */    async init() {
      await this.loadTargets();
      this.bindEvents();
      this.updateOverviewDisplay();
    }
    /**
         * Load available scan targets from the backend
         */    async loadTargets() {
      try {
        const $targets = jQuery("#phpcompat-targets");
        $targets.html('<div class="phpcompat-loader">Loading targets...</div>');
        const response = await this.ajaxClient.getTargets();
        if (response && response.success && response.data) {
          this.targets = {
            plugins: response.data.plugins || [],
            themes: response.data.themes || []
          };
          this.renderTargets();
        }
      } catch (error) {
        console.error("Failed to load targets:", error);
        jQuery("#phpcompat-targets").html('<div class="phpcompat-error">Failed to load targets</div>');
      }
    }
    /**
         * Render targets in the UI
         */    renderTargets() {
      const $targets = jQuery("#phpcompat-targets");
      let html = "";
      if (this.targets.plugins.length > 0) {
        html += '<div class="phpcompat-target-group"><strong>Plugins</strong></div>';
        this.targets.plugins.forEach(plugin => {
          html += this.renderTargetItem(plugin, "plugin");
        });
      }
      if (this.targets.themes.length > 0) {
        html += '<div class="phpcompat-target-group"><strong>Themes</strong></div>';
        this.targets.themes.forEach(theme => {
          html += this.renderTargetItem(theme, "theme");
        });
      }
      $targets.html(html);
    }
    /**
         * Render a single target item
         */    renderTargetItem(item, type) {
      const escapedSlug = item.slug.replace(/"/g, "&quot;");
      const escapedName = jQuery("<div/>").text(item.name).html();
      const activeBadge = item.active ? ' <span class="phpcompat-target-active">Active</span>' : "";
      const activeAttr = item.active ? ' data-active="1"' : "";
      return `<label class="phpcompat-target-item" data-name="${escapedName.replace(/"/g, "&quot;")}"${activeAttr}>\n            <input type="checkbox" class="phpcompat-target" data-type="${type}" data-slug="${escapedSlug}"> \n            ${escapedName}${activeBadge}\n        </label>`;
    }
    /**
         * Bind event handlers for target interactions
         */    bindEvents() {
      jQuery(document).on("change", ".phpcompat-target", () => {
        this.updateOverviewDisplay();
      });
      jQuery("#phpcompat-select-all").on("click", () => {
        const $all = jQuery(".phpcompat-target");
        const allChecked = $all.length > 0 && $all.length === $all.filter(":checked").length;
        $all.prop("checked", !allChecked);
        this.updateOverviewDisplay();
      });
      jQuery("#phpcompat-select-active").on("click", () => {
        jQuery(".phpcompat-target").prop("checked", false);
        jQuery('.phpcompat-target-item[data-active="1"]').find(".phpcompat-target").prop("checked", true);
        this.updateOverviewDisplay();
      });
      jQuery(document).on("click", ".phpcompat-overview-tag", function() {
        const slug = jQuery(this).data("slug");
        const type = jQuery(this).data("type");
        const $target = jQuery(`.phpcompat-target[data-slug="${slug}"][data-type="${type}"]`).closest(".phpcompat-target-item");
        if (!$target.length) return;
        const adminBarHeight = jQuery("#wpadminbar").outerHeight() || 32;
        const $sticky = jQuery(".phpcompat-sticky-controls");
        const offset = adminBarHeight + ($sticky.hasClass("pinned") ? $sticky.outerHeight() + 10 : 10);
        jQuery("html, body").animate({
          scrollTop: $target.offset().top - offset
        }, 400);
        $target.addClass("phpcompat-highlight");
        setTimeout(() => $target.removeClass("phpcompat-highlight"), 1500);
      });
    }
    /**
         * Build a unique key for a target
         */    _targetKey(type, slug) {
      return `${type}--${slug}`;
    }
    /**
         * Update the unified overview display.
         * Shows every selected target as a tag whose appearance reflects its scan state.
         * Previously completed scan results are preserved across re-renders.
         */    updateOverviewDisplay() {
      const $list = jQuery("#phpcompat-overview-list");
      const $selected = jQuery(".phpcompat-target:checked");
      if ($selected.length === 0) {
        const $tags = $list.find(".phpcompat-overview-tag");
        if ($tags.filter(".resuming").length === 0) {
          $list.html('<span class="phpcompat-overview-empty">No targets selected</span>');
        } else {
          $tags.not(".resuming").remove();
        }
        return;
      }
      const existing = {};
      $list.find(".phpcompat-overview-tag").each(function() {
        existing[jQuery(this).data("key")] = jQuery(this);
      });
      let html = "";
      const newKeys = new Set;
      $selected.each((_, el) => {
        const $el = jQuery(el);
        const type = $el.data("type");
        const slug = $el.data("slug");
        const key = `${type}--${slug}`;
        const name = $el.closest(".phpcompat-target-item").data("name") || $el.closest(".phpcompat-target-item").text().trim();
        newKeys.add(key);
        if (existing[key]) {
          return;
        }
        html += `<span class="phpcompat-overview-tag pending" data-key="${key}" data-type="${type}" data-slug="${slug}">` + `<span class="phpcompat-overview-tag-name">${name}</span>` + "</span>";
      });
      for (const key in existing) {
        if (!newKeys.has(key) && !existing[key].hasClass("resuming")) {
          existing[key].remove();
        }
      }
      if (html) {
        $list.find(".phpcompat-overview-empty").remove();
        $list.append(html);
      }
      if ($list.find(".phpcompat-overview-tag").length === 0) {
        $list.html('<span class="phpcompat-overview-empty">No targets selected</span>');
      }
    }
    /**
         * Mark a target as currently scanning (blue + spinner)
         */    setTargetScanning(type, slug) {
      const key = this._targetKey(type, slug);
      const $tag = jQuery(`#phpcompat-overview-list .phpcompat-overview-tag[data-key="${key}"]`);
      if (!$tag.length) return;
      $tag.removeClass("pending success errors warnings resuming failed").addClass("scanning");
      const name = $tag.find(".phpcompat-overview-tag-name").text();
      $tag.html(`<span class="phpcompat-overview-tag-name">${name}</span>` + '<span class="phpcompat-spinner"></span>');
    }
    /**
         * Mark a target scan as complete.
         * @param {string} type - 'plugin' or 'theme'
         * @param {string} slug
         * @param {number} totalErrors
         * @param {number} totalWarnings
         */    setTargetComplete(type, slug, totalErrors, totalWarnings) {
      const key = this._targetKey(type, slug);
      const $tag = jQuery(`#phpcompat-overview-list .phpcompat-overview-tag[data-key="${key}"]`);
      if (!$tag.length) return;
      $tag.removeClass("pending scanning resuming failed");
      const name = $tag.find(".phpcompat-overview-tag-name").text();
      let stateClass, icon, detail;
      if (totalErrors > 0) {
        stateClass = "errors";
        icon = "❌";
        detail = `${totalErrors} error${totalErrors > 1 ? "s" : ""}`;
        if (totalWarnings > 0) {
          detail += `, ${totalWarnings} warning${totalWarnings > 1 ? "s" : ""}`;
        }
      } else if (totalWarnings > 0) {
        stateClass = "warnings";
        icon = "⚠️";
        detail = `${totalWarnings} warning${totalWarnings > 1 ? "s" : ""}`;
      } else {
        stateClass = "success";
        icon = "✅";
        detail = "No issues";
      }
      $tag.addClass(stateClass);
      $tag.html(`<span class="phpcompat-overview-tag-name">${name}</span>` + `<span class="phpcompat-overview-tag-detail">${icon} ${detail}</span>`);
    }
    /**
         * Mark a target scan as failed (did not complete). Clears the scanning
         * spinner so the tag doesn't spin forever on an AJAX/batch failure.
         * @param {string} type - 'plugin' or 'theme'
         * @param {string} slug
         * @param {string} [detail] - short reason shown in the tag
         */    setTargetFailed(type, slug, detail = "Scan failed") {
      const key = this._targetKey(type, slug);
      const $tag = jQuery(`#phpcompat-overview-list .phpcompat-overview-tag[data-key="${key}"]`);
      if (!$tag.length) return;
      $tag.removeClass("pending scanning success errors warnings resuming").addClass("failed");
      const name = $tag.find(".phpcompat-overview-tag-name").text();
      $tag.html(`<span class="phpcompat-overview-tag-name">${name}</span>` + `<span class="phpcompat-overview-tag-detail">⚠️ ${detail}</span>`);
    }
    /**
         * Mark a target as resuming a scan that survived a page reload
         * (#137). Unlike the other state setters, a resumed target may not be a
         * checked box on this load, so its overview tag might not exist yet —
         * create it when absent so the "Resuming…" badge always shows.
         *
         * name comes from the server-side scan registry, so it's injected via
         * .text() (never HTML) to stay XSS-safe.
         * @param {string} type - 'plugin' or 'theme'
         * @param {string} slug
         * @param {string} name - display name from the registry entry
         */    setTargetResuming(type, slug, name) {
      const key = this._targetKey(type, slug);
      const $list = jQuery("#phpcompat-overview-list");
      let $tag = $list.find(`.phpcompat-overview-tag[data-key="${key}"]`);
      if (!$tag.length) {
        $list.find(".phpcompat-overview-empty").remove();
        $tag = jQuery('<span class="phpcompat-overview-tag"></span>').attr("data-key", key).attr("data-type", type).attr("data-slug", slug).appendTo($list);
      }
      const displayName = name || $tag.find(".phpcompat-overview-tag-name").text();
      $tag.removeClass("pending scanning success errors warnings failed").addClass("resuming");
      $tag.empty();
      jQuery('<span class="phpcompat-overview-tag-name"></span>').text(displayName).appendTo($tag);
      jQuery('<span class="phpcompat-overview-tag-detail">Resuming…</span>').appendTo($tag);
      jQuery('<span class="phpcompat-spinner"></span>').appendTo($tag);
    }
    /**
         * Get selected targets
         */    getSelectedTargets() {
      const $items = jQuery(".phpcompat-target:checked");
      return $items.toArray().map(item => {
        const $item = jQuery(item);
        const $label = $item.closest(".phpcompat-target-item");
        return {
          element: item,
          type: $item.data("type"),
          slug: $item.data("slug"),
          name: $label.data("name") || $label.text().trim()
        };
      });
    }
    /**
         * Check if any targets are selected
         */    hasSelectedTargets() {
      return jQuery(".phpcompat-target:checked").length > 0;
    }
    /**
         * Uncheck all selected targets
         */    uncheckAllTargets() {
      jQuery(".phpcompat-target:checked").prop("checked", false);
      this.updateOverviewDisplay();
    }
  }
  var TargetsManager$1 =  Object.freeze({
    __proto__: null,
    TargetsManager: TargetsManager
  });
  /**
     * SystemChecker - Handles system preflight checks and validation
     */  class SystemChecker {
    constructor(ajaxClient) {
      this.ajaxClient = ajaxClient;
      this.isSystemReady = false;
    }
    /**
         * Initialize system checker
         */    async init() {
      await this.runPreflightCheck();
    }
    /**
         * Run system preflight check
         */    async runPreflightCheck() {
      try {
        const $sys = jQuery("#phpcompat-system-check");
        const $button = jQuery("#phpcompat-start-scan");
        const $status = jQuery("#phpcompat-scan-status");
        const $help = jQuery("#phpcompat-system-help");
        const response = await this.ajaxClient.preflightCheck();
        const ok = response && response.success;
        const data = response && response.data ? response.data : {};
        const lines = [];
        lines.push(`${data.execEnabled ? "✅" : "❌"} exec() ${data.execEnabled ? "enabled" : "disabled"}`);
        lines.push(`${data.phpBinaryExists ? "✅" : "❌"} PHP binary: ${data.phpBinary || "(unknown)"}`);
        lines.push(`${data.phpcsExists ? "✅" : "❌"} phpcs: ${data.phpcsPath || "(unknown)"}`);
        if (data.phpcsVersionOutput) {
          lines.push(`Version: ${data.phpcsVersionOutput}`);
        }
        if (data.messages && data.messages.length) {
          lines.push(...data.messages);
        }
        $sys.text(lines.join("\n"));
        if (ok) {
          $button.prop("disabled", false);
          $help.prop("hidden", true);
          this.isSystemReady = true;
        } else {
          $button.prop("disabled", true);
          $status.text("System check failed");
          $help.prop("hidden", false);
          this.isSystemReady = false;
        }
      } catch (error) {
        console.error("System check failed:", error);
        jQuery("#phpcompat-system-check").text("System check failed to run");
        jQuery("#phpcompat-start-scan").prop("disabled", true);
        jQuery("#phpcompat-system-help").prop("hidden", false);
        this.isSystemReady = false;
      } finally {
        this.revealScanOptions();
      }
    }
    /**
         * Swap the Scan Options skeleton for the real controls. Runs on both the
         * success and failure paths: a failed check still needs its options
         * visible, so the user can see what was configured alongside the help
         * block explaining why scanning is unavailable.
         */    revealScanOptions() {
      jQuery(".phpcompat-sticky-controls").removeClass("is-checking").attr("aria-busy", "false");
    }
    /**
         * Check if system is ready for scanning
         */    isReady() {
      return this.isSystemReady;
    }
    /**
         * Get system status message
         */    getStatusMessage() {
      if (this.isSystemReady) {
        return "System ready for scanning";
      } else {
        return "System check failed";
      }
    }
  }
  var SystemChecker$1 =  Object.freeze({
    __proto__: null,
    SystemChecker: SystemChecker
  });
  /**
     * ScanModeRegistry - Extension point for alternative scan modes.
     *
     * The core plugin scans locally and registers nothing here. An add-on that
     * can scan some other way registers a handler under its own id, and
     * ScanManager dispatches to it when that mode is selected.
     *
     * A handler is any object exposing:
     *
     *     run(ctx) -> Promise
     *
     * where ctx is { targets, phpVersion, targetsManager, shouldContinue,
     * skipVendor }. The handler owns everything about its own transport; the
     * core knows only that it returns a promise and that shouldContinue() goes
     * false when the user stops the scan.
     *
     * Exposed as window.PHPCompatScanModes by App.initializeComponents(), which
     * then fires `phpcompat:modes-ready` on document so an add-on bundle that
     * loaded first can register once the registry exists.
     */  class ScanModeRegistry {
    constructor() {
      this.modes = new Map;
    }
    /**
         * Register a handler under an id.
         *
         * @param {string} id      Mode identifier, e.g. the value stored in the
         *                         scan-mode input.
         * @param {Object} handler Object exposing run(ctx).
         */    register(id, handler) {
      if (!id || !handler || typeof handler.run !== "function") {
        return;
      }
      this.modes.set(id, handler);
    }
    /**
         * @param {string} id Mode identifier.
         * @returns {boolean}
         */    has(id) {
      return this.modes.has(id);
    }
    /**
         * @param {string} id Mode identifier.
         * @returns {Object|null}
         */    get(id) {
      return this.modes.get(id) || null;
    }
    /**
         * Registered mode ids, for diagnostics and tests.
         *
         * @returns {string[]}
         */    ids() {
      return Array.from(this.modes.keys());
    }
  }
  var ScanModeRegistry$1 =  Object.freeze({
    __proto__: null,
    ScanModeRegistry: ScanModeRegistry
  });
  /**
     * Modal - Minimal reusable admin modal (free + pro).
     *
     * Static API, DOM built lazily on first show():
     *   Modal.show({ title, message, content, tone, actions, dismissible })
     *   Modal.hide()
     *
     * tone: 'success' | 'error' | 'info'
     *
     * `message` renders as plain text. `content` takes a pre-built DOM fragment
     * (jQuery object, Element, or DocumentFragment) for rich bodies with nested
     * elements, and wins over `message` when both are given. Never pass an HTML
     * string built from server data — build nodes and .text() the values instead.
     *
     * show() returns a Promise resolving to the chosen action's `value`, or null
     * when the modal is dismissed (Escape, backdrop click, or the default OK
     * button). With no `actions` the behaviour is the legacy one: a single OK
     * button that closes.
     *
     * Exposed as window.PHPCompatModal by App.initializeComponents() so add-on
     * bundles (separate, unbuilt IIFEs) can reuse it.
     */  const TONES = [ "success", "error", "info" ];
  class Modal {
    /**
         * Show the modal. Repeated calls reuse the same DOM nodes.
         *
         * `actions` entries: { label, value, tone?: 'primary'|'secondary', disabled?: boolean }.
         * Each button carries `data-action-value` so callers can toggle its state
         * (e.g. enable a confirm button once a checkbox in `content` is ticked).
         *
         * `dismissible: false` suppresses Escape, backdrop-click, and the default
         * OK button's close, and keeps Tab cycling inside the dialog. With
         * `actions` supplied the only exits are those buttons; without them the
         * default OK button still renders but no longer closes, so pair
         * `dismissible: false` with `actions`.
         *
         * @param {{title?: string, message?: string, content?: (object|Element|DocumentFragment), tone?: string, actions?: object[], dismissible?: boolean}} options
         * @return {Promise<*>} The chosen action's `value`, or null on dismissal.
         */
    static show(options = {}) {
      const tone = TONES.includes(options.tone) ? options.tone : "info";
      const $overlay = Modal.ensureDom();
      Modal.settle(null);
      Modal.dismissible = options.dismissible !== false;
      $overlay.find(".phpcompat-modal").removeClass(TONES.map(t => `phpcompat-modal--${t}`).join(" ")).addClass(`phpcompat-modal--${tone}`);
      $overlay.find(".phpcompat-modal-title").text(options.title || "");
      const $body = $overlay.find(".phpcompat-modal-message").empty();
      if (options.content && typeof options.content !== "string") {
        $body.append(options.content);
      } else {
        $body.text(options.message || "");
      }
      Modal.renderActions($overlay, options.actions);
      const promise = new Promise(resolve => {
        Modal.pendingResolve = resolve;
      });
      Modal.previousFocus = document.activeElement;
      $overlay.show();
      const $buttons = $overlay.find(".phpcompat-modal-actions button");
      const $focusable = $buttons.not(":disabled").first();
      ($focusable.length ? $focusable : $buttons.first()).trigger("focus");
      return promise;
    }
    /**
         * Rebuild the actions row for this show(). With no `actions` the legacy
         * single OK button (carrying .phpcompat-modal-close) is rendered, so the
         * delegated close handler keeps working unchanged.
         *
         * @param {object}   $overlay
         * @param {object[]} [actions]
         */    static renderActions($overlay, actions) {
      const $row = $overlay.find(".phpcompat-modal-actions").empty();
      if (!Array.isArray(actions) || !actions.length) {
        $row.append(jQuery("<button/>", {
          type: "button",
          class: "button button-primary phpcompat-modal-close",
          text: "OK"
        }));
        return;
      }
      actions.forEach(action => {
        const isPrimary = action.tone !== "secondary";
        const $button = jQuery("<button/>", {
          type: "button",
          class: `button ${isPrimary ? "button-primary" : "button-secondary"} phpcompat-modal-action`,
          text: action.label || ""
        });
        $button.attr("data-action-value", String(action.value));
        if (action.disabled) {
          $button.prop("disabled", true);
        }
        $button.on("click", () => Modal.settle(action.value, true));
        $row.append($button);
      });
    }
    /**
         * Resolve the pending show() promise, optionally hiding first.
         *
         * The resolver is cleared before hide() runs so hide()'s own settle(null)
         * can't beat an action's value to the resolution.
         *
         * @param {*}       value
         * @param {boolean} [close] Hide the modal as part of settling.
         */    static settle(value, close = false) {
      const resolve = Modal.pendingResolve;
      Modal.pendingResolve = null;
      if (close) {
        Modal.hide();
      }
      if (resolve) {
        resolve(value);
      }
    }
    /**
         * Hide the modal and restore focus to the element that had it. A pending
         * show() promise resolves to null — dismissal chose nothing.
         */    static hide() {
      const $overlay = jQuery("#phpcompat-modal-overlay");
      if (!$overlay.length) {
        return;
      }
      $overlay.hide();
      if (Modal.previousFocus && typeof Modal.previousFocus.focus === "function") {
        Modal.previousFocus.focus();
      }
      Modal.previousFocus = null;
      Modal.settle(null);
    }
    /**
         * Keep Tab cycling inside the dialog. Called only while the modal is
         * non-dismissible, where letting focus reach the page behind would let the
         * user act on a screen the dialog is meant to be blocking.
         *
         * @param {object} $overlay
         * @param {object} event    The keydown event.
         */    static trapTab($overlay, event) {
      const focusable = $overlay.find("a[href], button, input, select, textarea, [tabindex]").filter(":visible").not(":disabled").not('[tabindex="-1"]').toArray();
      if (!focusable.length) {
        return;
      }
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      const active = document.activeElement;
      if (focusable.indexOf(active) === -1) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
        return;
      }
      if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
      }
    }
    /**
         * Build the overlay/dialog DOM once and bind close handlers.
         */    static ensureDom() {
      let $overlay = jQuery("#phpcompat-modal-overlay");
      if ($overlay.length) {
        return $overlay;
      }
      $overlay = jQuery("<div/>", {
        id: "phpcompat-modal-overlay",
        class: "phpcompat-modal-overlay"
      }).hide();
      const $dialog = jQuery("<div/>", {
        class: "phpcompat-modal",
        role: "dialog",
        "aria-modal": "true",
        "aria-labelledby": "phpcompat-modal-title"
      });
      $dialog.append(jQuery("<h2/>", {
        id: "phpcompat-modal-title",
        class: "phpcompat-modal-title"
      }));
      $dialog.append(jQuery("<div/>", {
        class: "phpcompat-modal-message"
      }));
      $dialog.append(jQuery("<div/>", {
        class: "phpcompat-modal-actions"
      }));
      $overlay.append($dialog).appendTo(document.body);
      $overlay.on("click", event => {
        if (event.target === $overlay.get(0) && Modal.dismissible) {
          Modal.hide();
        }
      });
      $overlay.on("click", ".phpcompat-modal-close", () => {
        if (Modal.dismissible) {
          Modal.hide();
        }
      });
      jQuery(document).on("keydown.phpcompatModal", event => {
        if (!$overlay.is(":visible")) {
          return;
        }
        if (event.key === "Escape" && Modal.dismissible) {
          Modal.hide();
          return;
        }
        if (event.key === "Tab" && !Modal.dismissible) {
          Modal.trapTab($overlay, event);
        }
      });
      return $overlay;
    }
  }
  Modal.previousFocus = null;
  Modal.pendingResolve = null;
  Modal.dismissible = true;
  var Modal$1 =  Object.freeze({
    __proto__: null,
    Modal: Modal
  });
  /**
     * ScanManager - Handles the scanning process, batch processing, and scan coordination
     */  class ScanManager {
    constructor(ajaxClient, optionsManager, targetsManager, systemChecker, scanModes) {
      this.ajaxClient = ajaxClient;
      this.optionsManager = optionsManager;
      this.targetsManager = targetsManager;
      this.systemChecker = systemChecker;
      this.scanModes = scanModes;
      this.isScanning = false;
      this.currentScanId = null;
      this.runningMode = null;
    }
    /**
         * Initialize scan manager
         */    init() {
      this.bindEvents();
      this.restoreScanMode();
    }
    /**
         * Apply the Scan Mode the user last chose, persisted server-side via
         * OptionsManager alongside the other scan options (loaded before this runs
         * in the App bootstrap). A stored non-local mode is honored only when a
         * handler for it is registered — a site that lost its add-on since the
         * last visit falls back to local rather than selecting a mode nothing can
         * run. Passes persist=false so re-applying the loaded value doesn't
         * trigger a save.
         */    restoreScanMode() {
      const stored = this.optionsManager.getScanMode();
      if (stored === "local") {
        return;
      }
      if (!this.scanModes || !this.scanModes.has(stored)) {
        return;
      }
      if (!jQuery(`.phpcompat-mode-btn[data-mode="${stored}"]`).length) {
        return;
      }
      this.setScanMode(stored, false);
    }
    /**
         * Bind event handlers for scan controls
         */    bindEvents() {
      jQuery("#phpcompat-start-scan").on("click", () => {
        this.startScan();
      });
      jQuery("#phpcompat-stop-scan").on("click", () => {
        this.stopScan();
      });
      jQuery("#phpcompat-scan-mode").on("change", () => {
        const isRemote = this.getScanMode() !== "local";
        jQuery("#batch-size").prop("disabled", isRemote);
        this.updateStartButtonState();
      });
      jQuery(".phpcompat-mode-btn").on("click", event => {
        const $btn = jQuery(event.currentTarget);
        if ($btn.prop("disabled") || this.isScanning) {
          return;
        }
        this.setScanMode($btn.data("mode"));
      });
    }
    /**
         * Set the scan mode: sync the hidden input, the segmented buttons'
         * active state, and fire the change handlers.
         */    setScanMode(mode, persist = true) {
      const $input = jQuery("#phpcompat-scan-mode");
      if ($input.val() === mode) {
        return;
      }
      $input.val(mode).trigger("change");
      jQuery(".phpcompat-mode-btn").each(function() {
        const isActive = jQuery(this).data("mode") === mode;
        jQuery(this).toggleClass("is-active", isActive).attr("aria-pressed", String(isActive));
      });
      if (persist) {
        this.optionsManager.setScanMode(mode);
      }
    }
    /**
         * Lock/unlock mode switching while a scan runs. The hidden input keeps
         * its value; only the buttons get disabled.
         */    setModeLocked(locked) {
      jQuery(".phpcompat-mode-btn").prop("disabled", locked);
    }
    /**
         * Get the currently selected scan mode
         */    getScanMode() {
      return jQuery("#phpcompat-scan-mode").val() || "local";
    }
    /**
         * Enable/disable the Start Scan button based on mode + readiness
         */    updateStartButtonState() {
      const mode = this.getScanMode();
      const ready = mode === "local" ? !!this.systemChecker && this.systemChecker.isReady() : !!this.scanModes && this.scanModes.has(mode);
      jQuery("#phpcompat-start-scan").prop("disabled", !ready);
    }
    /**
         * Start the scanning process
         */    async startScan() {
      if (this.isScanning) {
        return;
      }
      const $button = jQuery("#phpcompat-start-scan");
      const $stopButton = jQuery("#phpcompat-stop-scan");
      const $status = jQuery("#phpcompat-scan-status");
      const mode = this.getScanMode();
      if (mode !== "local") {
        const handler = this.scanModes ? this.scanModes.get(mode) : null;
        if (!handler) {
          $status.text("That scan mode is unavailable");
          return;
        }
        if (!this.targetsManager.hasSelectedTargets()) {
          $status.text("Select at least one target");
          return;
        }
        this.isScanning = true;
        this.runningMode = mode;
        this.setModeLocked(true);
        $status.text("Running…");
        $button.prop("disabled", true);
        $stopButton.show().prop("disabled", false);
        try {
          await handler.run({
            targets: this.targetsManager.getSelectedTargets(),
            phpVersion: this.optionsManager.getPhpVersion(),
            targetsManager: this.targetsManager,
            shouldContinue: () => this.isScanning,
            skipVendor: this.optionsManager.getSkipVendor()
          });
        } catch (error) {
          console.error("Scan failed:", error);
          $status.text("Scan failed");
        } finally {
          this.isScanning = false;
          this.runningMode = null;
          this.setModeLocked(false);
          this.updateStartButtonState();
          $stopButton.hide();
        }
        return;
      }
      if (!this.targetsManager.hasSelectedTargets()) {
        $status.text("Select at least one target");
        return;
      }
      this.isScanning = true;
      this.runningMode = "local";
      this.setModeLocked(true);
      $status.text("Running…");
      $button.prop("disabled", true);
      $stopButton.show().prop("disabled", false);
      try {
        const selectedTargets = this.targetsManager.getSelectedTargets();
        await this.processTargets(selectedTargets);
      } catch (error) {
        console.error("Scan failed:", error);
        $status.text("Scan failed");
        Modal.show({
          title: "Scan failed",
          message: "The scan hit an unexpected error. Check the browser console for details and try again.",
          tone: "error"
        });
      } finally {
        this.isScanning = false;
        this.runningMode = null;
        this.setModeLocked(false);
        this.updateStartButtonState();
        $stopButton.hide();
      }
    }
    /**
         * Stop the current scan
         */    async stopScan() {
      if (!this.isScanning) {
        return;
      }
      const $button = jQuery("#phpcompat-start-scan");
      const $stopButton = jQuery("#phpcompat-stop-scan");
      const $status = jQuery("#phpcompat-scan-status");
      if (this.runningMode && this.runningMode !== "local") {
        this.isScanning = false;
        $stopButton.hide().prop("disabled", false).text("Stop Scan");
        this.updateStartButtonState();
        $status.text("Polling stopped. The scan keeps running on the server.");
        return;
      }
      $stopButton.prop("disabled", true).text("Stopping...");
      try {
        const response = await this.ajaxClient.stopScan();
        if (response && response.success) {
          $button.prop("disabled", false).text("Start Scan");
          $stopButton.prop("disabled", false).text("Stop Scan").hide();
          $status.html(`✅ ${response.data.message || "Scan stopped"}`);
          jQuery(".phpcompat-progress").remove();
          jQuery(".phpcompat-batch-results").remove();
        }
      } catch (error) {
        console.error("Failed to stop scan:", error);
        $stopButton.prop("disabled", false).text("Stop Scan");
        $status.html("❌ Failed to stop scan");
      }
      this.isScanning = false;
    }
    /**
         * Process all selected targets
         */    async processTargets(targets) {
      let anyFailed = false;
      for (let i = 0; i < targets.length; i++) {
        if (!this.isScanning) {
          break;
        }
        const target = targets[i];
        try {
          await this.processTarget(target);
        } catch (error) {
          console.error(`Failed to process target ${target.name}:`, error);
          anyFailed = true;
        }
      }
      if (this.isScanning) {
        const $status = jQuery("#phpcompat-scan-status");
        $status.text(anyFailed ? "Some scans failed" : "Done");
        Modal.show(anyFailed ? {
          title: "Scan finished with failures",
          message: "Some targets could not be scanned — check the results below each target for details.",
          tone: "error"
        } : {
          title: "Scan complete",
          message: "All selected targets were scanned. Results are shown below each target.",
          tone: "success"
        });
      }
    }
    /**
         * Process a single target
         */    async processTarget(target) {
      const $cb = jQuery(target.element);
      const $label = $cb.closest(".phpcompat-target-item");
      const $status = jQuery("#phpcompat-scan-status");
      const containerId = `pcp-result-${target.type}-${target.slug.replace(/[^a-z0-9_-]/gi, "_")}`;
      let $container = jQuery(`#${containerId}`);
      if (!$container.length) {
        $container = jQuery("<div/>", {
          id: containerId,
          class: "phpcompat-output main_output",
          css: {
            marginTop: "8px"
          }
        });
        $cb.closest(".phpcompat-target-item").after($container);
      } else {
        $container.text("");
      }
      let $flag = $label.find(".phpcompat-flag");
      if (!$flag.length) {
        $flag = jQuery("<span/>", {
          class: "phpcompat-flag",
          css: {
            marginLeft: "6px"
          }
        }).appendTo($label);
      }
      $flag.html('<span class="spinner is-active"></span>');
      $status.text(`Scanning ${target.name}...`);
      this.targetsManager.setTargetScanning(target.type, target.slug);
      const batchSize = this.optionsManager.getBatchSize();
      const skipVendor = this.optionsManager.getSkipVendor();
      const progressRes = await this.ajaxClient.getProgress(target.type, target.slug, batchSize, skipVendor);
      if (progressRes && progressRes.success && progressRes.data) {
        const progress = progressRes.data;
        if (progress.total_files > 0) {
          $container.html(this.createProgressSection(progress));
          await this.processBatches(progress, $container, $flag, target);
        } else {
          $container.html('<div class="phpcompat-progress"><strong>No PHP files found to scan</strong><br>The selected target does not contain any PHP files.</div>');
          this.targetsManager.setTargetComplete(target.type, target.slug, 0, 0);
        }
      } else {
        const message = progressRes?.data?.message || "Scan failed";
        $flag.html('❌ <span class="phpcompat-status-count">Scan failed</span>');
        $container.html(`<div class="phpcompat-progress"><strong>Scan failed:</strong> ${message}</div>`);
        this.targetsManager.setTargetFailed(target.type, target.slug);
      }
    }
    /**
         * Create progress section HTML
         */    createProgressSection(progress) {
      return `<div class="phpcompat-progress">\n            <strong>Scan Progress:</strong>\n            Total files: ${progress.total_files}\n            Estimated batches: ${progress.estimated_batches}\n            <div class="phpcompat-progress-bar"><div class="phpcompat-progress-fill" style="width: 0%"></div></div>\n            <div class="phpcompat-progress-text">Preparing to scan...</div>\n        </div>`;
    }
    /**
         * Process all batches for a target
         */    async processBatches(progress, $container, $flag, target) {
      const totalBatches = progress.estimated_batches;
      const scanId = progress.scan_id;
      const allResults = [];
      for (let currentBatch = 1; currentBatch <= totalBatches; currentBatch++) {
        if (!this.isScanning) {
          break;
        }
        this.updateProgressBar($container, currentBatch, totalBatches);
        try {
          const response = await this.ajaxClient.processBatch(scanId, currentBatch);
          if (response && response.success) {
            const batchData = response.data;
            allResults.push(batchData);
            this.showProgressiveResults($container, batchData);
          } else {
            throw new Error(response?.data?.message || "Batch failed");
          }
        } catch (error) {
          console.error(`Batch ${currentBatch} failed:`, error);
          $flag.html('❌ <span class="phpcompat-status-count">Batch failed</span>');
          $container.html(`Batch ${currentBatch} failed: ${error.message}`);
          this.targetsManager.setTargetFailed(target.type, target.slug);
          return;
        }
        if (currentBatch < totalBatches) {
          await new Promise(resolve => setTimeout(resolve, 100));
        }
      }
      this.finalizeScan($container, $flag, allResults, progress, target);
    }
    /**
         * Update progress bar
         */    updateProgressBar($container, currentBatch, totalBatches) {
      const progressPercent = Math.round((currentBatch - 1) / totalBatches * 100);
      const $progressFill = $container.find(".phpcompat-progress-fill");
      const $progressText = $container.find(".phpcompat-progress-text");
      if ($progressFill.length) {
        $progressFill.css("width", `${progressPercent}%`);
      }
      if ($progressText.length) {
        $progressText.text(`Processing batch ${currentBatch} of ${totalBatches}...`);
      }
    }
    /**
         * Show progressive results during scanning
         */    showProgressiveResults($container, batchData) {
      const batchOutput = `<div class="phpcompat-batch">\n            <strong>Batch ${batchData.batch_number}:</strong>\n            ${batchData.output && batchData.output.length > 0 ? '<pre class="phpcompat-batch-output"></pre>' : "<em>No issues found in this batch</em>"}\n        </div>`;
      let $existingResults = $container.find(".phpcompat-batch-results");
      if ($existingResults.length === 0) {
        $container.append('<div class="phpcompat-batch-results"></div>');
        $existingResults = $container.find(".phpcompat-batch-results");
      }
      $existingResults.append(batchOutput);
      if (batchData.output && batchData.output.length > 0) {
        $existingResults.find(".phpcompat-batch-output").last().text(batchData.output);
      }
    }
    /**
         * Finalize scan and show complete results
         */    finalizeScan($container, $flag, allResults, progress, target) {
      const totalErrors = allResults.reduce((sum, batch) => sum + (parseInt(batch.errors, 10) || 0), 0);
      const totalWarnings = allResults.reduce((sum, batch) => sum + (parseInt(batch.warnings, 10) || 0), 0);
      const totalIssues = totalErrors + totalWarnings;
      const phpVersion = this.optionsManager.getPhpVersion();
      if (totalIssues > 0) {
        let statusText = "";
        if (totalErrors > 0 && totalWarnings > 0) {
          statusText = `${totalIssues} issue${totalIssues > 1 ? "s" : ""} (${totalErrors} error${totalErrors > 1 ? "s" : ""}, ${totalWarnings} warning${totalWarnings > 1 ? "s" : ""}) - PHP ${phpVersion}`;
        } else if (totalErrors > 0) {
          statusText = `${totalErrors} error${totalErrors > 1 ? "s" : ""} - PHP ${phpVersion}`;
        } else {
          statusText = `${totalWarnings} warning${totalWarnings > 1 ? "s" : ""} - PHP ${phpVersion}`;
        }
        if (totalErrors > 0) {
          $flag.html(`❌ <span class="phpcompat-status-count">${statusText}</span>`);
        } else {
          $flag.html(`⚠️ <span class="phpcompat-status-count">${statusText}</span>`);
        }
      } else {
        $flag.html(`✅ <span class="phpcompat-status-count">No issues found - PHP ${phpVersion}</span>`);
      }
      this.showFinalResults($container, allResults, progress, phpVersion);
      this.targetsManager.setTargetComplete(target.type, target.slug, totalErrors, totalWarnings);
    }
    /**
         * Show final organized results
         */    showFinalResults($container, allResults, progress, phpVersion) {
      const leftColumnHtml = `<div class="phpcompat-output-left">\n            <div class="phpcompat-progress">\n                <strong>Scan Progress:</strong>\n                Total files: ${progress.total_files}\n                Estimated batches: ${progress.estimated_batches}\n                <div class="phpcompat-progress-bar"><div class="phpcompat-progress-fill" style="width: 100%"></div></div>\n                <div class="phpcompat-progress-text">Scan Complete!</div>\n            </div>\n            <div class="phpcompat-summary">\n                <strong>Scan Complete!</strong>\n                Total files scanned: ${progress.total_files}\n                Batches processed: ${allResults.length}\n                Target PHP version: ${phpVersion}\n            </div>\n        </div>`;
      let rightColumnHtml = "";
      if (allResults.length > 0) {
        rightColumnHtml = `<div class="phpcompat-output-right">\n                <div class="phpcompat-batch-results">\n                    <div class="phpcompat-batch-results-toggle">\n                        <span>Batch Results (${allResults.length} batches)</span>\n                        <span class="toggle-icon">▼</span>\n                    </div>\n                    <div class="phpcompat-batch-results-content">`;
        allResults.forEach((batchData, index) => {
          rightColumnHtml += `<div class="phpcompat-batch">\n                    <strong>Batch ${batchData.batch_number}:</strong>\n                    ${batchData.output && batchData.output.length > 0 ? `<pre class="phpcompat-batch-output" data-batch="${index}"></pre>` : "<em>No issues found in this batch</em>"}\n                </div>`;
        });
        rightColumnHtml += "</div></div></div>";
      }
      $container.find(".phpcompat-progress").remove();
      $container.find(".phpcompat-batch-results").remove();
      $container.html(leftColumnHtml + rightColumnHtml);
      allResults.forEach((batchData, index) => {
        if (batchData.output && batchData.output.length > 0) {
          $container.find(`.phpcompat-batch-output[data-batch="${index}"]`).text(batchData.output);
        }
      });
      const $toggleButton = $container.find(".phpcompat-batch-results-toggle");
      $toggleButton.on("click", function(e) {
        e.preventDefault();
        const $batchResults = jQuery(this).closest(".phpcompat-batch-results");
        const $toggleIcon = jQuery(this).find(".toggle-icon");
        $batchResults.toggleClass("collapsed");
        if ($batchResults.hasClass("collapsed")) {
          $toggleIcon.text("▶");
        } else {
          $toggleIcon.text("▼");
        }
      });
      const $progressFillFinal = $container.find(".phpcompat-progress-fill");
      if ($progressFillFinal.length) {
        $progressFillFinal.css("width", "100%");
      }
    }
    /**
         * Check if currently scanning
         */    isCurrentlyScanning() {
      return this.isScanning;
    }
  }
  var ScanManager$1 =  Object.freeze({
    __proto__: null,
    ScanManager: ScanManager
  });
  /**
     * UIManager - Handles UI interactions, enhancements, and general UI management
     */  class UIManager {
    constructor() {
      this.init();
    }
    /**
         * Initialize UI manager
         */    init() {
      this.bindEvents();
      this.initializeUI();
    }
    /**
         * Bind general UI event handlers
         */    bindEvents() {
      jQuery(document).on("click", ".phpcompat-how-to-use h2", function() {
        jQuery(this).closest(".phpcompat-how-to-use").toggleClass("collapsed");
      });
      jQuery(document).on("click", "#phpcompat-pin-overview", function() {
        jQuery(".phpcompat-sticky-controls").toggleClass("pinned");
        jQuery(this).toggleClass("active");
      });
    }
    /**
         * Initialize UI elements
         */    initializeUI() {
      jQuery("#phpcompat-stop-scan").hide();
      this.enhanceFormElements();
      this.enhanceProgressBars();
    }
    /**
         * Enhance form elements with better UX
         */    enhanceFormElements() {
      jQuery(".phpcompat-form-group input, .phpcompat-form-group select").on("focus", function() {
        jQuery(this).closest(".phpcompat-form-group").addClass("focused");
      }).on("blur", function() {
        jQuery(this).closest(".phpcompat-form-group").removeClass("focused");
      });
      jQuery(".phpcompat-target-item").on("mouseenter", function() {
        jQuery(this).addClass("hovered");
      }).on("mouseleave", function() {
        jQuery(this).removeClass("hovered");
      });
    }
    /**
         * Enhance progress bars with animations
         */    enhanceProgressBars() {
      jQuery(".phpcompat-progress-fill").css("transition", "width 0.3s ease");
    }
    /**
         * Show loading state for an element
         */    showLoading($element, message = "Loading...") {
      $element.prop("disabled", true);
      const originalText = $element.text();
      $element.data("original-text", originalText);
      $element.text(message);
      return originalText;
    }
    /**
         * Hide loading state for an element
         */    hideLoading($element) {
      $element.prop("disabled", false);
      const originalText = $element.data("original-text");
      if (originalText) {
        $element.text(originalText);
      }
    }
    /**
         * Show success message
         */    showSuccess(message, duration = 3e3) {
      this.showMessage(message, "success", duration);
    }
    /**
         * Show error message
         */    showError(message, duration = 5e3) {
      this.showMessage(message, "error", duration);
    }
    /**
         * Show warning message
         */    showWarning(message, duration = 4e3) {
      this.showMessage(message, "warning", duration);
    }
    /**
         * Show info message
         */    showInfo(message, duration = 3e3) {
      this.showMessage(message, "info", duration);
    }
    /**
         * Show a message with specified type and duration
         */    showMessage(message, type = "info", duration = 3e3) {
      const $messageContainer = jQuery("#phpcompat-messages");
      if (!$messageContainer.length) {
        jQuery('<div id="phpcompat-messages"></div>').insertAfter("#phpcompat-scan-status");
      }
      const messageHtml = `<div class="phpcompat-message phpcompat-message-${type}">\n            <span class="message-text">${message}</span>\n            <button class="message-close">&times;</button>\n        </div>`;
      const $message = jQuery(messageHtml);
      $messageContainer.append($message);
      setTimeout(() => {
        $message.fadeOut(300, function() {
          jQuery(this).remove();
        });
      }, duration);
      $message.find(".message-close").on("click", function() {
        $message.fadeOut(300, function() {
          jQuery(this).remove();
        });
      });
    }
    /**
         * Update status display
         */    updateStatus(message, type = "info") {
      const $status = jQuery("#phpcompat-scan-status");
      const icon = this.getStatusIcon(type);
      $status.html(`${icon} ${message}`);
    }
    /**
         * Get status icon based on type
         */    getStatusIcon(type) {
      switch (type) {
       case "success":
        return "✅";

       case "error":
        return "❌";

       case "warning":
        return "⚠️";

       case "info":
       default:
        return "ℹ️";
      }
    }
    /**
         * Toggle element visibility with animation
         */    toggleElement($element, show = true, duration = 300) {
      if (show) {
        $element.slideDown(duration);
      } else {
        $element.slideUp(duration);
      }
    }
    /**
         * Fade in element
         */    fadeInElement($element, duration = 300) {
      $element.fadeIn(duration);
    }
    /**
         * Fade out element
         */    fadeOutElement($element, duration = 300) {
      $element.fadeOut(duration);
    }
    /**
         * Scroll to element smoothly
         */    scrollToElement($element, offset = 0) {
      jQuery("html, body").animate({
        scrollTop: $element.offset().top - offset
      }, 500);
    }
    /**
         * Highlight element temporarily
         */    highlightElement($element, duration = 2e3) {
      $element.addClass("highlighted");
      setTimeout(() => {
        $element.removeClass("highlighted");
      }, duration);
    }
    /**
         * Disable form during processing
         */    disableForm() {
      jQuery(".phpcompat-form-group input, .phpcompat-form-group select, .phpcompat-form-group button").prop("disabled", true);
    }
    /**
         * Enable form after processing
         */    enableForm() {
      jQuery(".phpcompat-form-group input, .phpcompat-form-group select, .phpcompat-form-group button").prop("disabled", false);
    }
    /**
         * Reset form to default state
         */    resetForm() {
      jQuery('.phpcompat-form-group input[type="text"], .phpcompat-form-group input[type="number"]').val("");
      jQuery(".phpcompat-form-group select").prop("selectedIndex", 0);
      jQuery('.phpcompat-form-group input[type="checkbox"]').prop("checked", false);
    }
    /**
         * Clear all scan results
         */    clearScanResults() {
      jQuery(".phpcompat-output").remove();
      jQuery(".phpcompat-progress").remove();
      jQuery(".phpcompat-batch-results").remove();
      jQuery(".phpcompat-flag").remove();
    }
    /**
         * Update progress indicator
         */    updateProgress(percent, text = "") {
      const $progressFill = jQuery(".phpcompat-progress-fill");
      const $progressText = jQuery(".phpcompat-progress-text");
      if ($progressFill.length) {
        $progressFill.css("width", `${percent}%`);
      }
      if ($progressText.length && text) {
        $progressText.text(text);
      }
    }
  }
  var UIManager$1 =  Object.freeze({
    __proto__: null,
    UIManager: UIManager
  });
})(jQuery);
