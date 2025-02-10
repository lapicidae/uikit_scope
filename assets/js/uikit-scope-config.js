/**
 * ResourceChecker class for validating resources via an API.
 */
class ResourceChecker {
	/**
	 * @param {Object} config - Configuration options for ResourceChecker.
	 */
	constructor(config = {}) {
		this.msgTitleWarn = config.msgTitleWarn || 'Warning!';
		this.msgTitleErr = config.msgTitleErr || 'Error!';
		this.msgTitleSucc = config.msgTitleSucc || 'Success!';
		this.msgEmpty = config.msgEmpty || 'Please enter at least one resource.';
		this.msgSuccess = config.msgSuccess || 'All resources are available!';
		this.buttonLoading = config.buttonLoading || 'Loading';
		this.apiSourcePage = config.apiSourcePage || 'index.php';
		this.debugMode = config.debugMode !== undefined ? config.debugMode : true;

		this.selectors = {
			radioAll: config.radioAll || 'input:radio[id*="-source"]',
			radioUser: config.radioUser || 'input:radio[id*="-source-user"]',
			resourceInputs: config.resourceInputs || 'input[data-id^="source-resource"]',
			resourceInputsValidate: config.resourceInputsValidate || 'input[data-resource-validate="true"]',
			checkButton: config.checkButton || '#checkResources',
			alertContainer: config.alertContainer || '#resource-alert',
			form: config.form || 'form',
			labelFor: config.labelFor || 'label[for="'
		};

		this.init();
	}

	/**
	 * Logs debug messages if debug mode is enabled.
	 * @param {...any} args - Messages to log.
	 */
	debugLog(...args) {
		if (this.debugMode && console) {
			console.log(...args);
		}
	}

	/**
	 * Initializes event listeners and UI state.
	 */
	init() {
		this.debugLog('Initializing ResourceChecker...');
		this.toggleFields();

		$(document).on('change', this.selectors.radioAll, () => this.toggleFields());
		$(document).on('click', this.selectors.checkButton, () => this.validateResources());
		$(document).on('submit', this.selectors.form, (e) => this.validateForm(e));
	}

	/**
	 * Toggles resource input fields based on the radio button state.
	 */
	toggleFields() {
		const isUserSelected = $(this.selectors.radioUser).is(':checked');

		this.debugLog('User radio button selected:', isUserSelected);

		const $resourceInputs = $(this.selectors.resourceInputs);
		const $checkButton = $(this.selectors.checkButton);
		const $alertContainer = $(this.selectors.alertContainer);

		$resourceInputs.prop('disabled', !isUserSelected);
		$checkButton.prop('disabled', !isUserSelected);

		if (isUserSelected) {
			this.debugLog('Enabling resource inputs');
		} else {
			this.debugLog('Disabling resource inputs');
			$resourceInputs.val('');
			$alertContainer.empty();
		}
	}

	/**
	 * Checks if a resource is valid via an API request.
	 * @param {string} resource - The resource to validate.
	 * @returns {Promise<boolean>} - Resolves to true if the resource is valid, otherwise false.
	 */
	async checkResource(resource) {
		if (!resource.trim()) {
			this.debugLog('Skipping empty resource');
			return true;
		}

		this.debugLog(`Checking resource: ${resource}`);

		try {
			const requestData = {
				page: this.apiSourcePage,
				'rex-api-call': 'resource_validator',
				rsc: resource,
				...(this.debugMode && { debug: 'true' })
			};

			const response = await $.ajax({
				page: this.apiSourcePage,
				type: 'GET',
				data: requestData,
				dataType: 'json'
			});

			this.debugLog('API response:', response);
			return response.available === true;
		} catch (error) {
			this.debugLog('API error:', error);
			return false;
		}
	}

	/**
	 * Validates all entered resources by checking their availability.
	 */
	async validateResources() {
		const $alert = $(this.selectors.alertContainer);
		const $btn = $(this.selectors.checkButton);
		const $form = $(this.selectors.form);
		const originalBtnText = $btn.html();
		const loadingText = this.buttonLoading;

		this.debugLog('Validating resources...');

		$btn.html(`${loadingText} <span class="dot-area" style="display:inline-block; width:10px;"></span>`);
		$btn.css('width', $btn.outerWidth()).prop('disabled', true);
		$form.css('cursor', 'wait');
		$alert.empty();

		let dotCount = 0;
		const intervalId = setInterval(() => {
			$btn.find('.dot-area').text('.'.repeat(dotCount = (dotCount + 1) % 4));
		}, 500);

		let allValid = true;
		const invalidRes = [];
		let anyResource = false;
		const promises = [];

		$(this.selectors.resourceInputsValidate).not(':disabled').filter(':visible').each((_, input) => {
			const $input = $(input);

			if ($input.is('input')) {
				const resource = $input.val().trim();
				if (!resource) return;

				anyResource = true;
				const inputId = $input.attr('id');
				const label = $(`${this.selectors.labelFor}${inputId}"]`).text().trim() || '';

				promises.push(this.checkResource(resource).then(isValid => {
					if (!isValid) {
						allValid = false;
						invalidRes.push(`<strong>${label}:</strong> ${resource}`);
					}
				}));
			} else {
				this.debugLog("Skipping non-input element:", $input);
			}
		});

		if (!anyResource) {
			this.debugLog('No resources entered.');
			this.displayAlert(this.msgEmpty, 'warning', this.msgTitleWarn);
			clearInterval(intervalId);
			$btn.html(originalBtnText).prop('disabled', false).css('width', '');
			$form.css('cursor', 'default');
			return;
		}

		await Promise.all(promises);

		clearInterval(intervalId);
		$btn.html(originalBtnText).prop('disabled', false).css('width', '');
		$form.css('cursor', 'default');

		this.debugLog('Validation result:', allValid ? 'All resources valid' : 'Some resources invalid');

		this.displayAlert(
			allValid ? this.msgSuccess : invalidRes.join("<br>"),
			allValid ? 'success' : 'danger',
			allValid ? this.msgTitleSucc : this.msgTitleErr
		);
	}

	/**
	 * Handles form submission and prevents it if validation fails.
	 * @param {Event} e - The form submission event.
	 */
	validateForm(e) {
		this.debugLog('Form submission triggered');
		$(this.selectors.alertContainer).empty();

		if ($(this.selectors.radioUser).prop('checked') && !this.validateFields()) {
			e.preventDefault();
			this.debugLog('Form validation failed: No resources entered');
			this.displayAlert(this.msgEmpty, 'warning', this.msgTitleWarn);
		}
	}

	/**
	 * Checks if at least one resource field is filled.
	 * @returns {boolean} - True if at least one field is filled, otherwise false.
	 */
	validateFields() {
		return $(this.selectors.resourceInputsValidate).filter(':visible').filter((_, el) => $(el).val().trim()).length > 0;
	}

	/**
	 * Displays an alert message in the alert container.
	 * @param {string} message - The message to display.
	 * @param {string} type - The alert type (e.g., 'success', 'danger', 'warning').
	 * @param {string} [title] - The optional alert title.
	 */
	displayAlert(message, type, title) {
		const alertDiv = title
			? $('<div class="alert alert-' + type + '" role="alert"><h4>' + title + '</h4>' + message + '</div>')
			: $('<div class="alert alert-' + type + '" role="alert">' + message + '</div>');

		$(this.selectors.alertContainer).removeAttr('hidden').empty().prepend(alertDiv);
	}
}
