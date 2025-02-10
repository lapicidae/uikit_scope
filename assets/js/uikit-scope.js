// jQuery (native JavaScript is located below) //
$(document).ready(function () {
	setTimeout(function () {
		// function to initialize components within their respective scopes
		function initializeComponent(selector, initializer) {
			$(selector).each(function () {
				// find the closest 'uk-scope' container
				const $parentScope = $(this).closest('.uk-scope');
				if ($parentScope.length) {
					// create a new container within the scope
					const $container = $('<div></div>');
					$parentScope.append($container);

					// initialize the component with the created container
					initializer($(this), $container);
				}
			});
		}

		// initialize Tooltip components
		initializeComponent('[uk-tooltip]', function ($element, $container) {
			UIkit.tooltip($element[0], { container: $container[0] });
		});

		// initialize Modal components
		initializeComponent('[uk-modal]', function ($element, $container) {
			UIkit.modal($element[0], { container: $container[0] });
		});

		// initialize Lightbox components
		initializeComponent('[uk-lightbox]', function ($element, $container) {
			UIkit.lightbox($element[0], { container: $container[0] });
		});
	}, 0);	// delay of 0 ms to ensure that the script is executed after all others
});



// Native JavaScript //
// document.addEventListener('DOMContentLoaded', () => {
// 	setTimeout(function () {
// 		// general function to initialise components
// 		const initializeComponent = (selector, ComponentConstructor) => {
// 			document.querySelectorAll(selector).forEach(element => {
// 				// find the closest 'uk-scope' container
// 				const parentScope = element.closest('.uk-scope');
// 				if (parentScope) {
// 					// create a new container within the scope
// 					const container = document.createElement('div');
// 					parentScope.appendChild(container);

// 					// initialize the component with the created container
// 					ComponentConstructor(element, { container });
// 				}
// 			});
// 		};

// 		// initialize Tooltip components
// 		initializeComponent('[uk-tooltip]', (el, options) => UIkit.tooltip(el, options));

// 		// initialize Modal components
// 		initializeComponent('[uk-modal]', (el, options) => UIkit.modal(el, options));

// 		// initialize Lightbox components
// 		initializeComponent('[uk-lightbox]', (el, options) => UIkit.lightbox(el, options));
// 	}, 0);	// delay of 0 ms to ensure that the script is executed after all others
// });
