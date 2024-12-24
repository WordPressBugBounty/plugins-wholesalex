/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@tannin/compile/index.js":
/*!***********************************************!*\
  !*** ./node_modules/@tannin/compile/index.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ compile)
/* harmony export */ });
/* harmony import */ var _tannin_postfix__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @tannin/postfix */ "./node_modules/@tannin/postfix/index.js");
/* harmony import */ var _tannin_evaluate__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @tannin/evaluate */ "./node_modules/@tannin/evaluate/index.js");



/**
 * Given a C expression, returns a function which can be called to evaluate its
 * result.
 *
 * @example
 *
 * ```js
 * import compile from '@tannin/compile';
 *
 * const evaluate = compile( 'n > 1' );
 *
 * evaluate( { n: 2 } );
 * // ⇒ true
 * ```
 *
 * @param {string} expression C expression.
 *
 * @return {(variables?:{[variable:string]:*})=>*} Compiled evaluator.
 */
function compile( expression ) {
	var terms = (0,_tannin_postfix__WEBPACK_IMPORTED_MODULE_0__["default"])( expression );

	return function( variables ) {
		return (0,_tannin_evaluate__WEBPACK_IMPORTED_MODULE_1__["default"])( terms, variables );
	};
}


/***/ }),

/***/ "./node_modules/@tannin/evaluate/index.js":
/*!************************************************!*\
  !*** ./node_modules/@tannin/evaluate/index.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ evaluate)
/* harmony export */ });
/**
 * Operator callback functions.
 *
 * @type {Object}
 */
var OPERATORS = {
	'!': function( a ) {
		return ! a;
	},
	'*': function( a, b ) {
		return a * b;
	},
	'/': function( a, b ) {
		return a / b;
	},
	'%': function( a, b ) {
		return a % b;
	},
	'+': function( a, b ) {
		return a + b;
	},
	'-': function( a, b ) {
		return a - b;
	},
	'<': function( a, b ) {
		return a < b;
	},
	'<=': function( a, b ) {
		return a <= b;
	},
	'>': function( a, b ) {
		return a > b;
	},
	'>=': function( a, b ) {
		return a >= b;
	},
	'==': function( a, b ) {
		return a === b;
	},
	'!=': function( a, b ) {
		return a !== b;
	},
	'&&': function( a, b ) {
		return a && b;
	},
	'||': function( a, b ) {
		return a || b;
	},
	'?:': function( a, b, c ) {
		if ( a ) {
			throw b;
		}

		return c;
	},
};

/**
 * Given an array of postfix terms and operand variables, returns the result of
 * the postfix evaluation.
 *
 * @example
 *
 * ```js
 * import evaluate from '@tannin/evaluate';
 *
 * // 3 + 4 * 5 / 6 ⇒ '3 4 5 * 6 / +'
 * const terms = [ '3', '4', '5', '*', '6', '/', '+' ];
 *
 * evaluate( terms, {} );
 * // ⇒ 6.333333333333334
 * ```
 *
 * @param {string[]} postfix   Postfix terms.
 * @param {Object}   variables Operand variables.
 *
 * @return {*} Result of evaluation.
 */
function evaluate( postfix, variables ) {
	var stack = [],
		i, j, args, getOperatorResult, term, value;

	for ( i = 0; i < postfix.length; i++ ) {
		term = postfix[ i ];

		getOperatorResult = OPERATORS[ term ];
		if ( getOperatorResult ) {
			// Pop from stack by number of function arguments.
			j = getOperatorResult.length;
			args = Array( j );
			while ( j-- ) {
				args[ j ] = stack.pop();
			}

			try {
				value = getOperatorResult.apply( null, args );
			} catch ( earlyReturn ) {
				return earlyReturn;
			}
		} else if ( variables.hasOwnProperty( term ) ) {
			value = variables[ term ];
		} else {
			value = +term;
		}

		stack.push( value );
	}

	return stack[ 0 ];
}


/***/ }),

/***/ "./node_modules/@tannin/plural-forms/index.js":
/*!****************************************************!*\
  !*** ./node_modules/@tannin/plural-forms/index.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ pluralForms)
/* harmony export */ });
/* harmony import */ var _tannin_compile__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @tannin/compile */ "./node_modules/@tannin/compile/index.js");


/**
 * Given a C expression, returns a function which, when called with a value,
 * evaluates the result with the value assumed to be the "n" variable of the
 * expression. The result will be coerced to its numeric equivalent.
 *
 * @param {string} expression C expression.
 *
 * @return {Function} Evaluator function.
 */
function pluralForms( expression ) {
	var evaluate = (0,_tannin_compile__WEBPACK_IMPORTED_MODULE_0__["default"])( expression );

	return function( n ) {
		return +evaluate( { n: n } );
	};
}


/***/ }),

/***/ "./node_modules/@tannin/postfix/index.js":
/*!***********************************************!*\
  !*** ./node_modules/@tannin/postfix/index.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ postfix)
/* harmony export */ });
var PRECEDENCE, OPENERS, TERMINATORS, PATTERN;

/**
 * Operator precedence mapping.
 *
 * @type {Object}
 */
PRECEDENCE = {
	'(': 9,
	'!': 8,
	'*': 7,
	'/': 7,
	'%': 7,
	'+': 6,
	'-': 6,
	'<': 5,
	'<=': 5,
	'>': 5,
	'>=': 5,
	'==': 4,
	'!=': 4,
	'&&': 3,
	'||': 2,
	'?': 1,
	'?:': 1,
};

/**
 * Characters which signal pair opening, to be terminated by terminators.
 *
 * @type {string[]}
 */
OPENERS = [ '(', '?' ];

/**
 * Characters which signal pair termination, the value an array with the
 * opener as its first member. The second member is an optional operator
 * replacement to push to the stack.
 *
 * @type {string[]}
 */
TERMINATORS = {
	')': [ '(' ],
	':': [ '?', '?:' ],
};

/**
 * Pattern matching operators and openers.
 *
 * @type {RegExp}
 */
PATTERN = /<=|>=|==|!=|&&|\|\||\?:|\(|!|\*|\/|%|\+|-|<|>|\?|\)|:/;

/**
 * Given a C expression, returns the equivalent postfix (Reverse Polish)
 * notation terms as an array.
 *
 * If a postfix string is desired, simply `.join( ' ' )` the result.
 *
 * @example
 *
 * ```js
 * import postfix from '@tannin/postfix';
 *
 * postfix( 'n > 1' );
 * // ⇒ [ 'n', '1', '>' ]
 * ```
 *
 * @param {string} expression C expression.
 *
 * @return {string[]} Postfix terms.
 */
function postfix( expression ) {
	var terms = [],
		stack = [],
		match, operator, term, element;

	while ( ( match = expression.match( PATTERN ) ) ) {
		operator = match[ 0 ];

		// Term is the string preceding the operator match. It may contain
		// whitespace, and may be empty (if operator is at beginning).
		term = expression.substr( 0, match.index ).trim();
		if ( term ) {
			terms.push( term );
		}

		while ( ( element = stack.pop() ) ) {
			if ( TERMINATORS[ operator ] ) {
				if ( TERMINATORS[ operator ][ 0 ] === element ) {
					// Substitution works here under assumption that because
					// the assigned operator will no longer be a terminator, it
					// will be pushed to the stack during the condition below.
					operator = TERMINATORS[ operator ][ 1 ] || operator;
					break;
				}
			} else if ( OPENERS.indexOf( element ) >= 0 || PRECEDENCE[ element ] < PRECEDENCE[ operator ] ) {
				// Push to stack if either an opener or when pop reveals an
				// element of lower precedence.
				stack.push( element );
				break;
			}

			// For each popped from stack, push to terms.
			terms.push( element );
		}

		if ( ! TERMINATORS[ operator ] ) {
			stack.push( operator );
		}

		// Slice matched fragment from expression to continue match.
		expression = expression.substr( match.index + operator.length );
	}

	// Push remainder of operand, if exists, to terms.
	expression = expression.trim();
	if ( expression ) {
		terms.push( expression );
	}

	// Pop remaining items from stack into terms.
	return terms.concat( stack.reverse() );
}


/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createAddHook.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createAddHook.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _validateNamespace_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./validateNamespace.js */ "./node_modules/@wordpress/hooks/build-module/validateNamespace.js");
/* harmony import */ var _validateHookName_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./validateHookName.js */ "./node_modules/@wordpress/hooks/build-module/validateHookName.js");
/**
 * Internal dependencies
 */



/**
 * @callback AddHook
 *
 * Adds the hook to the appropriate hooks container.
 *
 * @param {string}               hookName      Name of hook to add
 * @param {string}               namespace     The unique namespace identifying the callback in the form `vendor/plugin/function`.
 * @param {import('.').Callback} callback      Function to call when the hook is run
 * @param {number}               [priority=10] Priority of this hook
 */

/**
 * Returns a function which, when invoked, will add a hook.
 *
 * @param {import('.').Hooks}    hooks    Hooks instance.
 * @param {import('.').StoreKey} storeKey
 *
 * @return {AddHook} Function that adds a new hook.
 */
function createAddHook(hooks, storeKey) {
  return function addHook(hookName, namespace, callback, priority = 10) {
    const hooksStore = hooks[storeKey];
    if (!(0,_validateHookName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(hookName)) {
      return;
    }
    if (!(0,_validateNamespace_js__WEBPACK_IMPORTED_MODULE_0__["default"])(namespace)) {
      return;
    }
    if ('function' !== typeof callback) {
      // eslint-disable-next-line no-console
      console.error('The hook callback must be a function.');
      return;
    }

    // Validate numeric priority
    if ('number' !== typeof priority) {
      // eslint-disable-next-line no-console
      console.error('If specified, the hook priority must be a number.');
      return;
    }
    const handler = {
      callback,
      priority,
      namespace
    };
    if (hooksStore[hookName]) {
      // Find the correct insert index of the new hook.
      const handlers = hooksStore[hookName].handlers;

      /** @type {number} */
      let i;
      for (i = handlers.length; i > 0; i--) {
        if (priority >= handlers[i - 1].priority) {
          break;
        }
      }
      if (i === handlers.length) {
        // If append, operate via direct assignment.
        handlers[i] = handler;
      } else {
        // Otherwise, insert before index via splice.
        handlers.splice(i, 0, handler);
      }

      // We may also be currently executing this hook.  If the callback
      // we're adding would come after the current callback, there's no
      // problem; otherwise we need to increase the execution index of
      // any other runs by 1 to account for the added element.
      hooksStore.__current.forEach(hookInfo => {
        if (hookInfo.name === hookName && hookInfo.currentIndex >= i) {
          hookInfo.currentIndex++;
        }
      });
    } else {
      // This is the first hook of its type.
      hooksStore[hookName] = {
        handlers: [handler],
        runs: 0
      };
    }
    if (hookName !== 'hookAdded') {
      hooks.doAction('hookAdded', hookName, namespace, callback, priority);
    }
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createAddHook);
//# sourceMappingURL=createAddHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createCurrentHook.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createCurrentHook.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Returns a function which, when invoked, will return the name of the
 * currently running hook, or `null` if no hook of the given type is currently
 * running.
 *
 * @param {import('.').Hooks}    hooks    Hooks instance.
 * @param {import('.').StoreKey} storeKey
 *
 * @return {() => string | null} Function that returns the current hook name or null.
 */
function createCurrentHook(hooks, storeKey) {
  return function currentHook() {
    var _hooksStore$__current;
    const hooksStore = hooks[storeKey];
    return (_hooksStore$__current = hooksStore.__current[hooksStore.__current.length - 1]?.name) !== null && _hooksStore$__current !== void 0 ? _hooksStore$__current : null;
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createCurrentHook);
//# sourceMappingURL=createCurrentHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createDidHook.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createDidHook.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _validateHookName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./validateHookName.js */ "./node_modules/@wordpress/hooks/build-module/validateHookName.js");
/**
 * Internal dependencies
 */


/**
 * @callback DidHook
 *
 * Returns the number of times an action has been fired.
 *
 * @param {string} hookName The hook name to check.
 *
 * @return {number | undefined} The number of times the hook has run.
 */

/**
 * Returns a function which, when invoked, will return the number of times a
 * hook has been called.
 *
 * @param {import('.').Hooks}    hooks    Hooks instance.
 * @param {import('.').StoreKey} storeKey
 *
 * @return {DidHook} Function that returns a hook's call count.
 */
function createDidHook(hooks, storeKey) {
  return function didHook(hookName) {
    const hooksStore = hooks[storeKey];
    if (!(0,_validateHookName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(hookName)) {
      return;
    }
    return hooksStore[hookName] && hooksStore[hookName].runs ? hooksStore[hookName].runs : 0;
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createDidHook);
//# sourceMappingURL=createDidHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createDoingHook.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createDoingHook.js ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * @callback DoingHook
 * Returns whether a hook is currently being executed.
 *
 * @param {string} [hookName] The name of the hook to check for.  If
 *                            omitted, will check for any hook being executed.
 *
 * @return {boolean} Whether the hook is being executed.
 */

/**
 * Returns a function which, when invoked, will return whether a hook is
 * currently being executed.
 *
 * @param {import('.').Hooks}    hooks    Hooks instance.
 * @param {import('.').StoreKey} storeKey
 *
 * @return {DoingHook} Function that returns whether a hook is currently
 *                     being executed.
 */
function createDoingHook(hooks, storeKey) {
  return function doingHook(hookName) {
    const hooksStore = hooks[storeKey];

    // If the hookName was not passed, check for any current hook.
    if ('undefined' === typeof hookName) {
      return 'undefined' !== typeof hooksStore.__current[0];
    }

    // Return the __current hook.
    return hooksStore.__current[0] ? hookName === hooksStore.__current[0].name : false;
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createDoingHook);
//# sourceMappingURL=createDoingHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createHasHook.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createHasHook.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * @callback HasHook
 *
 * Returns whether any handlers are attached for the given hookName and optional namespace.
 *
 * @param {string} hookName    The name of the hook to check for.
 * @param {string} [namespace] Optional. The unique namespace identifying the callback
 *                             in the form `vendor/plugin/function`.
 *
 * @return {boolean} Whether there are handlers that are attached to the given hook.
 */
/**
 * Returns a function which, when invoked, will return whether any handlers are
 * attached to a particular hook.
 *
 * @param {import('.').Hooks}    hooks    Hooks instance.
 * @param {import('.').StoreKey} storeKey
 *
 * @return {HasHook} Function that returns whether any handlers are
 *                   attached to a particular hook and optional namespace.
 */
function createHasHook(hooks, storeKey) {
  return function hasHook(hookName, namespace) {
    const hooksStore = hooks[storeKey];

    // Use the namespace if provided.
    if ('undefined' !== typeof namespace) {
      return hookName in hooksStore && hooksStore[hookName].handlers.some(hook => hook.namespace === namespace);
    }
    return hookName in hooksStore;
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createHasHook);
//# sourceMappingURL=createHasHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createHooks.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createHooks.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _Hooks: () => (/* binding */ _Hooks),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createAddHook__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./createAddHook */ "./node_modules/@wordpress/hooks/build-module/createAddHook.js");
/* harmony import */ var _createRemoveHook__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./createRemoveHook */ "./node_modules/@wordpress/hooks/build-module/createRemoveHook.js");
/* harmony import */ var _createHasHook__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./createHasHook */ "./node_modules/@wordpress/hooks/build-module/createHasHook.js");
/* harmony import */ var _createRunHook__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./createRunHook */ "./node_modules/@wordpress/hooks/build-module/createRunHook.js");
/* harmony import */ var _createCurrentHook__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./createCurrentHook */ "./node_modules/@wordpress/hooks/build-module/createCurrentHook.js");
/* harmony import */ var _createDoingHook__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./createDoingHook */ "./node_modules/@wordpress/hooks/build-module/createDoingHook.js");
/* harmony import */ var _createDidHook__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./createDidHook */ "./node_modules/@wordpress/hooks/build-module/createDidHook.js");
/**
 * Internal dependencies
 */








/**
 * Internal class for constructing hooks. Use `createHooks()` function
 *
 * Note, it is necessary to expose this class to make its type public.
 *
 * @private
 */
class _Hooks {
  constructor() {
    /** @type {import('.').Store} actions */
    this.actions = Object.create(null);
    this.actions.__current = [];

    /** @type {import('.').Store} filters */
    this.filters = Object.create(null);
    this.filters.__current = [];
    this.addAction = (0,_createAddHook__WEBPACK_IMPORTED_MODULE_0__["default"])(this, 'actions');
    this.addFilter = (0,_createAddHook__WEBPACK_IMPORTED_MODULE_0__["default"])(this, 'filters');
    this.removeAction = (0,_createRemoveHook__WEBPACK_IMPORTED_MODULE_1__["default"])(this, 'actions');
    this.removeFilter = (0,_createRemoveHook__WEBPACK_IMPORTED_MODULE_1__["default"])(this, 'filters');
    this.hasAction = (0,_createHasHook__WEBPACK_IMPORTED_MODULE_2__["default"])(this, 'actions');
    this.hasFilter = (0,_createHasHook__WEBPACK_IMPORTED_MODULE_2__["default"])(this, 'filters');
    this.removeAllActions = (0,_createRemoveHook__WEBPACK_IMPORTED_MODULE_1__["default"])(this, 'actions', true);
    this.removeAllFilters = (0,_createRemoveHook__WEBPACK_IMPORTED_MODULE_1__["default"])(this, 'filters', true);
    this.doAction = (0,_createRunHook__WEBPACK_IMPORTED_MODULE_3__["default"])(this, 'actions');
    this.applyFilters = (0,_createRunHook__WEBPACK_IMPORTED_MODULE_3__["default"])(this, 'filters', true);
    this.currentAction = (0,_createCurrentHook__WEBPACK_IMPORTED_MODULE_4__["default"])(this, 'actions');
    this.currentFilter = (0,_createCurrentHook__WEBPACK_IMPORTED_MODULE_4__["default"])(this, 'filters');
    this.doingAction = (0,_createDoingHook__WEBPACK_IMPORTED_MODULE_5__["default"])(this, 'actions');
    this.doingFilter = (0,_createDoingHook__WEBPACK_IMPORTED_MODULE_5__["default"])(this, 'filters');
    this.didAction = (0,_createDidHook__WEBPACK_IMPORTED_MODULE_6__["default"])(this, 'actions');
    this.didFilter = (0,_createDidHook__WEBPACK_IMPORTED_MODULE_6__["default"])(this, 'filters');
  }
}

/** @typedef {_Hooks} Hooks */

/**
 * Returns an instance of the hooks object.
 *
 * @return {Hooks} A Hooks instance.
 */
function createHooks() {
  return new _Hooks();
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createHooks);
//# sourceMappingURL=createHooks.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createRemoveHook.js":
/*!************************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createRemoveHook.js ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _validateNamespace_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./validateNamespace.js */ "./node_modules/@wordpress/hooks/build-module/validateNamespace.js");
/* harmony import */ var _validateHookName_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./validateHookName.js */ "./node_modules/@wordpress/hooks/build-module/validateHookName.js");
/**
 * Internal dependencies
 */



/**
 * @callback RemoveHook
 * Removes the specified callback (or all callbacks) from the hook with a given hookName
 * and namespace.
 *
 * @param {string} hookName  The name of the hook to modify.
 * @param {string} namespace The unique namespace identifying the callback in the
 *                           form `vendor/plugin/function`.
 *
 * @return {number | undefined} The number of callbacks removed.
 */

/**
 * Returns a function which, when invoked, will remove a specified hook or all
 * hooks by the given name.
 *
 * @param {import('.').Hooks}    hooks             Hooks instance.
 * @param {import('.').StoreKey} storeKey
 * @param {boolean}              [removeAll=false] Whether to remove all callbacks for a hookName,
 *                                                 without regard to namespace. Used to create
 *                                                 `removeAll*` functions.
 *
 * @return {RemoveHook} Function that removes hooks.
 */
function createRemoveHook(hooks, storeKey, removeAll = false) {
  return function removeHook(hookName, namespace) {
    const hooksStore = hooks[storeKey];
    if (!(0,_validateHookName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(hookName)) {
      return;
    }
    if (!removeAll && !(0,_validateNamespace_js__WEBPACK_IMPORTED_MODULE_0__["default"])(namespace)) {
      return;
    }

    // Bail if no hooks exist by this name.
    if (!hooksStore[hookName]) {
      return 0;
    }
    let handlersRemoved = 0;
    if (removeAll) {
      handlersRemoved = hooksStore[hookName].handlers.length;
      hooksStore[hookName] = {
        runs: hooksStore[hookName].runs,
        handlers: []
      };
    } else {
      // Try to find the specified callback to remove.
      const handlers = hooksStore[hookName].handlers;
      for (let i = handlers.length - 1; i >= 0; i--) {
        if (handlers[i].namespace === namespace) {
          handlers.splice(i, 1);
          handlersRemoved++;
          // This callback may also be part of a hook that is
          // currently executing.  If the callback we're removing
          // comes after the current callback, there's no problem;
          // otherwise we need to decrease the execution index of any
          // other runs by 1 to account for the removed element.
          hooksStore.__current.forEach(hookInfo => {
            if (hookInfo.name === hookName && hookInfo.currentIndex >= i) {
              hookInfo.currentIndex--;
            }
          });
        }
      }
    }
    if (hookName !== 'hookRemoved') {
      hooks.doAction('hookRemoved', hookName, namespace);
    }
    return handlersRemoved;
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createRemoveHook);
//# sourceMappingURL=createRemoveHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/createRunHook.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/createRunHook.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Returns a function which, when invoked, will execute all callbacks
 * registered to a hook of the specified type, optionally returning the final
 * value of the call chain.
 *
 * @param {import('.').Hooks}    hooks                  Hooks instance.
 * @param {import('.').StoreKey} storeKey
 * @param {boolean}              [returnFirstArg=false] Whether each hook callback is expected to
 *                                                      return its first argument.
 *
 * @return {(hookName:string, ...args: unknown[]) => undefined|unknown} Function that runs hook callbacks.
 */
function createRunHook(hooks, storeKey, returnFirstArg = false) {
  return function runHooks(hookName, ...args) {
    const hooksStore = hooks[storeKey];
    if (!hooksStore[hookName]) {
      hooksStore[hookName] = {
        handlers: [],
        runs: 0
      };
    }
    hooksStore[hookName].runs++;
    const handlers = hooksStore[hookName].handlers;

    // The following code is stripped from production builds.
    if (true) {
      // Handle any 'all' hooks registered.
      if ('hookAdded' !== hookName && hooksStore.all) {
        handlers.push(...hooksStore.all.handlers);
      }
    }
    if (!handlers || !handlers.length) {
      return returnFirstArg ? args[0] : undefined;
    }
    const hookInfo = {
      name: hookName,
      currentIndex: 0
    };
    hooksStore.__current.push(hookInfo);
    while (hookInfo.currentIndex < handlers.length) {
      const handler = handlers[hookInfo.currentIndex];
      const result = handler.callback.apply(null, args);
      if (returnFirstArg) {
        args[0] = result;
      }
      hookInfo.currentIndex++;
    }
    hooksStore.__current.pop();
    if (returnFirstArg) {
      return args[0];
    }
    return undefined;
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (createRunHook);
//# sourceMappingURL=createRunHook.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/index.js":
/*!*************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/index.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   actions: () => (/* binding */ actions),
/* harmony export */   addAction: () => (/* binding */ addAction),
/* harmony export */   addFilter: () => (/* binding */ addFilter),
/* harmony export */   applyFilters: () => (/* binding */ applyFilters),
/* harmony export */   createHooks: () => (/* reexport safe */ _createHooks__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   currentAction: () => (/* binding */ currentAction),
/* harmony export */   currentFilter: () => (/* binding */ currentFilter),
/* harmony export */   defaultHooks: () => (/* binding */ defaultHooks),
/* harmony export */   didAction: () => (/* binding */ didAction),
/* harmony export */   didFilter: () => (/* binding */ didFilter),
/* harmony export */   doAction: () => (/* binding */ doAction),
/* harmony export */   doingAction: () => (/* binding */ doingAction),
/* harmony export */   doingFilter: () => (/* binding */ doingFilter),
/* harmony export */   filters: () => (/* binding */ filters),
/* harmony export */   hasAction: () => (/* binding */ hasAction),
/* harmony export */   hasFilter: () => (/* binding */ hasFilter),
/* harmony export */   removeAction: () => (/* binding */ removeAction),
/* harmony export */   removeAllActions: () => (/* binding */ removeAllActions),
/* harmony export */   removeAllFilters: () => (/* binding */ removeAllFilters),
/* harmony export */   removeFilter: () => (/* binding */ removeFilter)
/* harmony export */ });
/* harmony import */ var _createHooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./createHooks */ "./node_modules/@wordpress/hooks/build-module/createHooks.js");
/**
 * Internal dependencies
 */


/** @typedef {(...args: any[])=>any} Callback */

/**
 * @typedef Handler
 * @property {Callback} callback  The callback
 * @property {string}   namespace The namespace
 * @property {number}   priority  The namespace
 */

/**
 * @typedef Hook
 * @property {Handler[]} handlers Array of handlers
 * @property {number}    runs     Run counter
 */

/**
 * @typedef Current
 * @property {string} name         Hook name
 * @property {number} currentIndex The index
 */

/**
 * @typedef {Record<string, Hook> & {__current: Current[]}} Store
 */

/**
 * @typedef {'actions' | 'filters'} StoreKey
 */

/**
 * @typedef {import('./createHooks').Hooks} Hooks
 */

const defaultHooks = (0,_createHooks__WEBPACK_IMPORTED_MODULE_0__["default"])();
const {
  addAction,
  addFilter,
  removeAction,
  removeFilter,
  hasAction,
  hasFilter,
  removeAllActions,
  removeAllFilters,
  doAction,
  applyFilters,
  currentAction,
  currentFilter,
  doingAction,
  doingFilter,
  didAction,
  didFilter,
  actions,
  filters
} = defaultHooks;

//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/validateHookName.js":
/*!************************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/validateHookName.js ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Validate a hookName string.
 *
 * @param {string} hookName The hook name to validate. Should be a non empty string containing
 *                          only numbers, letters, dashes, periods and underscores. Also,
 *                          the hook name cannot begin with `__`.
 *
 * @return {boolean} Whether the hook name is valid.
 */
function validateHookName(hookName) {
  if ('string' !== typeof hookName || '' === hookName) {
    // eslint-disable-next-line no-console
    console.error('The hook name must be a non-empty string.');
    return false;
  }
  if (/^__/.test(hookName)) {
    // eslint-disable-next-line no-console
    console.error('The hook name cannot begin with `__`.');
    return false;
  }
  if (!/^[a-zA-Z][a-zA-Z0-9_.-]*$/.test(hookName)) {
    // eslint-disable-next-line no-console
    console.error('The hook name can only contain numbers, letters, dashes, periods and underscores.');
    return false;
  }
  return true;
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (validateHookName);
//# sourceMappingURL=validateHookName.js.map

/***/ }),

/***/ "./node_modules/@wordpress/hooks/build-module/validateNamespace.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@wordpress/hooks/build-module/validateNamespace.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Validate a namespace string.
 *
 * @param {string} namespace The namespace to validate - should take the form
 *                           `vendor/plugin/function`.
 *
 * @return {boolean} Whether the namespace is valid.
 */
function validateNamespace(namespace) {
  if ('string' !== typeof namespace || '' === namespace) {
    // eslint-disable-next-line no-console
    console.error('The namespace must be a non-empty string.');
    return false;
  }
  if (!/^[a-zA-Z][a-zA-Z0-9_.\-\/]*$/.test(namespace)) {
    // eslint-disable-next-line no-console
    console.error('The namespace can only contain numbers, letters, dashes, periods, underscores and slashes.');
    return false;
  }
  return true;
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (validateNamespace);
//# sourceMappingURL=validateNamespace.js.map

/***/ }),

/***/ "./node_modules/@wordpress/i18n/build-module/create-i18n.js":
/*!******************************************************************!*\
  !*** ./node_modules/@wordpress/i18n/build-module/create-i18n.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createI18n: () => (/* binding */ createI18n)
/* harmony export */ });
/* harmony import */ var tannin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tannin */ "./node_modules/tannin/index.js");
/**
 * External dependencies
 */


/**
 * @typedef {Record<string,any>} LocaleData
 */

/**
 * Default locale data to use for Tannin domain when not otherwise provided.
 * Assumes an English plural forms expression.
 *
 * @type {LocaleData}
 */
const DEFAULT_LOCALE_DATA = {
  '': {
    /** @param {number} n */
    plural_forms(n) {
      return n === 1 ? 0 : 1;
    }
  }
};

/*
 * Regular expression that matches i18n hooks like `i18n.gettext`, `i18n.ngettext`,
 * `i18n.gettext_domain` or `i18n.ngettext_with_context` or `i18n.has_translation`.
 */
const I18N_HOOK_REGEXP = /^i18n\.(n?gettext|has_translation)(_|$)/;

/**
 * @typedef {(domain?: string) => LocaleData} GetLocaleData
 *
 * Returns locale data by domain in a
 * Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 */
/**
 * @typedef {(data?: LocaleData, domain?: string) => void} SetLocaleData
 *
 * Merges locale data into the Tannin instance by domain. Note that this
 * function will overwrite the domain configuration. Accepts data in a
 * Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 */
/**
 * @typedef {(data?: LocaleData, domain?: string) => void} AddLocaleData
 *
 * Merges locale data into the Tannin instance by domain. Note that this
 * function will also merge the domain configuration. Accepts data in a
 * Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 */
/**
 * @typedef {(data?: LocaleData, domain?: string) => void} ResetLocaleData
 *
 * Resets all current Tannin instance locale data and sets the specified
 * locale data for the domain. Accepts data in a Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 */
/** @typedef {() => void} SubscribeCallback */
/** @typedef {() => void} UnsubscribeCallback */
/**
 * @typedef {(callback: SubscribeCallback) => UnsubscribeCallback} Subscribe
 *
 * Subscribes to changes of locale data
 */
/**
 * @typedef {(domain?: string) => string} GetFilterDomain
 * Retrieve the domain to use when calling domain-specific filters.
 */
/**
 * @typedef {(text: string, domain?: string) => string} __
 *
 * Retrieve the translation of text.
 *
 * @see https://developer.wordpress.org/reference/functions/__/
 */
/**
 * @typedef {(text: string, context: string, domain?: string) => string} _x
 *
 * Retrieve translated string with gettext context.
 *
 * @see https://developer.wordpress.org/reference/functions/_x/
 */
/**
 * @typedef {(single: string, plural: string, number: number, domain?: string) => string} _n
 *
 * Translates and retrieves the singular or plural form based on the supplied
 * number.
 *
 * @see https://developer.wordpress.org/reference/functions/_n/
 */
/**
 * @typedef {(single: string, plural: string, number: number, context: string, domain?: string) => string} _nx
 *
 * Translates and retrieves the singular or plural form based on the supplied
 * number, with gettext context.
 *
 * @see https://developer.wordpress.org/reference/functions/_nx/
 */
/**
 * @typedef {() => boolean} IsRtl
 *
 * Check if current locale is RTL.
 *
 * **RTL (Right To Left)** is a locale property indicating that text is written from right to left.
 * For example, the `he` locale (for Hebrew) specifies right-to-left. Arabic (ar) is another common
 * language written RTL. The opposite of RTL, LTR (Left To Right) is used in other languages,
 * including English (`en`, `en-US`, `en-GB`, etc.), Spanish (`es`), and French (`fr`).
 */
/**
 * @typedef {(single: string, context?: string, domain?: string) => boolean} HasTranslation
 *
 * Check if there is a translation for a given string in singular form.
 */
/** @typedef {import('@wordpress/hooks').Hooks} Hooks */

/**
 * An i18n instance
 *
 * @typedef I18n
 * @property {GetLocaleData}   getLocaleData   Returns locale data by domain in a Jed-formatted JSON object shape.
 * @property {SetLocaleData}   setLocaleData   Merges locale data into the Tannin instance by domain. Note that this
 *                                             function will overwrite the domain configuration. Accepts data in a
 *                                             Jed-formatted JSON object shape.
 * @property {AddLocaleData}   addLocaleData   Merges locale data into the Tannin instance by domain. Note that this
 *                                             function will also merge the domain configuration. Accepts data in a
 *                                             Jed-formatted JSON object shape.
 * @property {ResetLocaleData} resetLocaleData Resets all current Tannin instance locale data and sets the specified
 *                                             locale data for the domain. Accepts data in a Jed-formatted JSON object shape.
 * @property {Subscribe}       subscribe       Subscribes to changes of Tannin locale data.
 * @property {__}              __              Retrieve the translation of text.
 * @property {_x}              _x              Retrieve translated string with gettext context.
 * @property {_n}              _n              Translates and retrieves the singular or plural form based on the supplied
 *                                             number.
 * @property {_nx}             _nx             Translates and retrieves the singular or plural form based on the supplied
 *                                             number, with gettext context.
 * @property {IsRtl}           isRTL           Check if current locale is RTL.
 * @property {HasTranslation}  hasTranslation  Check if there is a translation for a given string.
 */

/**
 * Create an i18n instance
 *
 * @param {LocaleData} [initialData]   Locale data configuration.
 * @param {string}     [initialDomain] Domain for which configuration applies.
 * @param {Hooks}      [hooks]         Hooks implementation.
 *
 * @return {I18n} I18n instance.
 */
const createI18n = (initialData, initialDomain, hooks) => {
  /**
   * The underlying instance of Tannin to which exported functions interface.
   *
   * @type {Tannin}
   */
  const tannin = new tannin__WEBPACK_IMPORTED_MODULE_0__["default"]({});
  const listeners = new Set();
  const notifyListeners = () => {
    listeners.forEach(listener => listener());
  };

  /**
   * Subscribe to changes of locale data.
   *
   * @param {SubscribeCallback} callback Subscription callback.
   * @return {UnsubscribeCallback} Unsubscribe callback.
   */
  const subscribe = callback => {
    listeners.add(callback);
    return () => listeners.delete(callback);
  };

  /** @type {GetLocaleData} */
  const getLocaleData = (domain = 'default') => tannin.data[domain];

  /**
   * @param {LocaleData} [data]
   * @param {string}     [domain]
   */
  const doSetLocaleData = (data, domain = 'default') => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data
    };

    // Populate default domain configuration (supported locale date which omits
    // a plural forms expression).
    tannin.data[domain][''] = {
      ...DEFAULT_LOCALE_DATA[''],
      ...tannin.data[domain]?.['']
    };

    // Clean up cached plural forms functions cache as it might be updated.
    delete tannin.pluralForms[domain];
  };

  /** @type {SetLocaleData} */
  const setLocaleData = (data, domain) => {
    doSetLocaleData(data, domain);
    notifyListeners();
  };

  /** @type {AddLocaleData} */
  const addLocaleData = (data, domain = 'default') => {
    tannin.data[domain] = {
      ...tannin.data[domain],
      ...data,
      // Populate default domain configuration (supported locale date which omits
      // a plural forms expression).
      '': {
        ...DEFAULT_LOCALE_DATA[''],
        ...tannin.data[domain]?.[''],
        ...data?.['']
      }
    };

    // Clean up cached plural forms functions cache as it might be updated.
    delete tannin.pluralForms[domain];
    notifyListeners();
  };

  /** @type {ResetLocaleData} */
  const resetLocaleData = (data, domain) => {
    // Reset all current Tannin locale data.
    tannin.data = {};

    // Reset cached plural forms functions cache.
    tannin.pluralForms = {};
    setLocaleData(data, domain);
  };

  /**
   * Wrapper for Tannin's `dcnpgettext`. Populates default locale data if not
   * otherwise previously assigned.
   *
   * @param {string|undefined} domain   Domain to retrieve the translated text.
   * @param {string|undefined} context  Context information for the translators.
   * @param {string}           single   Text to translate if non-plural. Used as
   *                                    fallback return value on a caught error.
   * @param {string}           [plural] The text to be used if the number is
   *                                    plural.
   * @param {number}           [number] The number to compare against to use
   *                                    either the singular or plural form.
   *
   * @return {string} The translated string.
   */
  const dcnpgettext = (domain = 'default', context, single, plural, number) => {
    if (!tannin.data[domain]) {
      // Use `doSetLocaleData` to set silently, without notifying listeners.
      doSetLocaleData(undefined, domain);
    }
    return tannin.dcnpgettext(domain, context, single, plural, number);
  };

  /** @type {GetFilterDomain} */
  const getFilterDomain = (domain = 'default') => domain;

  /** @type {__} */
  const __ = (text, domain) => {
    let translation = dcnpgettext(domain, undefined, text);
    if (!hooks) {
      return translation;
    }

    /**
     * Filters text with its translation.
     *
     * @param {string} translation Translated text.
     * @param {string} text        Text to translate.
     * @param {string} domain      Text domain. Unique identifier for retrieving translated strings.
     */
    translation = /** @type {string} */
    /** @type {*} */hooks.applyFilters('i18n.gettext', translation, text, domain);
    return /** @type {string} */(
      /** @type {*} */hooks.applyFilters('i18n.gettext_' + getFilterDomain(domain), translation, text, domain)
    );
  };

  /** @type {_x} */
  const _x = (text, context, domain) => {
    let translation = dcnpgettext(domain, context, text);
    if (!hooks) {
      return translation;
    }

    /**
     * Filters text with its translation based on context information.
     *
     * @param {string} translation Translated text.
     * @param {string} text        Text to translate.
     * @param {string} context     Context information for the translators.
     * @param {string} domain      Text domain. Unique identifier for retrieving translated strings.
     */
    translation = /** @type {string} */
    /** @type {*} */hooks.applyFilters('i18n.gettext_with_context', translation, text, context, domain);
    return /** @type {string} */(
      /** @type {*} */hooks.applyFilters('i18n.gettext_with_context_' + getFilterDomain(domain), translation, text, context, domain)
    );
  };

  /** @type {_n} */
  const _n = (single, plural, number, domain) => {
    let translation = dcnpgettext(domain, undefined, single, plural, number);
    if (!hooks) {
      return translation;
    }

    /**
     * Filters the singular or plural form of a string.
     *
     * @param {string} translation Translated text.
     * @param {string} single      The text to be used if the number is singular.
     * @param {string} plural      The text to be used if the number is plural.
     * @param {string} number      The number to compare against to use either the singular or plural form.
     * @param {string} domain      Text domain. Unique identifier for retrieving translated strings.
     */
    translation = /** @type {string} */
    /** @type {*} */hooks.applyFilters('i18n.ngettext', translation, single, plural, number, domain);
    return /** @type {string} */(
      /** @type {*} */hooks.applyFilters('i18n.ngettext_' + getFilterDomain(domain), translation, single, plural, number, domain)
    );
  };

  /** @type {_nx} */
  const _nx = (single, plural, number, context, domain) => {
    let translation = dcnpgettext(domain, context, single, plural, number);
    if (!hooks) {
      return translation;
    }

    /**
     * Filters the singular or plural form of a string with gettext context.
     *
     * @param {string} translation Translated text.
     * @param {string} single      The text to be used if the number is singular.
     * @param {string} plural      The text to be used if the number is plural.
     * @param {string} number      The number to compare against to use either the singular or plural form.
     * @param {string} context     Context information for the translators.
     * @param {string} domain      Text domain. Unique identifier for retrieving translated strings.
     */
    translation = /** @type {string} */
    /** @type {*} */hooks.applyFilters('i18n.ngettext_with_context', translation, single, plural, number, context, domain);
    return /** @type {string} */(
      /** @type {*} */hooks.applyFilters('i18n.ngettext_with_context_' + getFilterDomain(domain), translation, single, plural, number, context, domain)
    );
  };

  /** @type {IsRtl} */
  const isRTL = () => {
    return 'rtl' === _x('ltr', 'text direction');
  };

  /** @type {HasTranslation} */
  const hasTranslation = (single, context, domain) => {
    const key = context ? context + '\u0004' + single : single;
    let result = !!tannin.data?.[domain !== null && domain !== void 0 ? domain : 'default']?.[key];
    if (hooks) {
      /**
       * Filters the presence of a translation in the locale data.
       *
       * @param {boolean} hasTranslation Whether the translation is present or not..
       * @param {string}  single         The singular form of the translated text (used as key in locale data)
       * @param {string}  context        Context information for the translators.
       * @param {string}  domain         Text domain. Unique identifier for retrieving translated strings.
       */
      result = /** @type { boolean } */
      /** @type {*} */hooks.applyFilters('i18n.has_translation', result, single, context, domain);
      result = /** @type { boolean } */
      /** @type {*} */hooks.applyFilters('i18n.has_translation_' + getFilterDomain(domain), result, single, context, domain);
    }
    return result;
  };
  if (initialData) {
    setLocaleData(initialData, initialDomain);
  }
  if (hooks) {
    /**
     * @param {string} hookName
     */
    const onHookAddedOrRemoved = hookName => {
      if (I18N_HOOK_REGEXP.test(hookName)) {
        notifyListeners();
      }
    };
    hooks.addAction('hookAdded', 'core/i18n', onHookAddedOrRemoved);
    hooks.addAction('hookRemoved', 'core/i18n', onHookAddedOrRemoved);
  }
  return {
    getLocaleData,
    setLocaleData,
    addLocaleData,
    resetLocaleData,
    subscribe,
    __,
    _x,
    _n,
    _nx,
    isRTL,
    hasTranslation
  };
};
//# sourceMappingURL=create-i18n.js.map

/***/ }),

/***/ "./node_modules/@wordpress/i18n/build-module/default-i18n.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@wordpress/i18n/build-module/default-i18n.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   __: () => (/* binding */ __),
/* harmony export */   _n: () => (/* binding */ _n),
/* harmony export */   _nx: () => (/* binding */ _nx),
/* harmony export */   _x: () => (/* binding */ _x),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   getLocaleData: () => (/* binding */ getLocaleData),
/* harmony export */   hasTranslation: () => (/* binding */ hasTranslation),
/* harmony export */   isRTL: () => (/* binding */ isRTL),
/* harmony export */   resetLocaleData: () => (/* binding */ resetLocaleData),
/* harmony export */   setLocaleData: () => (/* binding */ setLocaleData),
/* harmony export */   subscribe: () => (/* binding */ subscribe)
/* harmony export */ });
/* harmony import */ var _create_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./create-i18n */ "./node_modules/@wordpress/i18n/build-module/create-i18n.js");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/hooks */ "./node_modules/@wordpress/hooks/build-module/index.js");
/**
 * Internal dependencies
 */


/**
 * WordPress dependencies
 */

const i18n = (0,_create_i18n__WEBPACK_IMPORTED_MODULE_0__.createI18n)(undefined, undefined, _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__.defaultHooks);

/**
 * Default, singleton instance of `I18n`.
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (i18n);

/*
 * Comments in this file are duplicated from ./i18n due to
 * https://github.com/WordPress/gutenberg/pull/20318#issuecomment-590837722
 */

/**
 * @typedef {import('./create-i18n').LocaleData} LocaleData
 * @typedef {import('./create-i18n').SubscribeCallback} SubscribeCallback
 * @typedef {import('./create-i18n').UnsubscribeCallback} UnsubscribeCallback
 */

/**
 * Returns locale data by domain in a Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 *
 * @param {string} [domain] Domain for which to get the data.
 * @return {LocaleData} Locale data.
 */
const getLocaleData = i18n.getLocaleData.bind(i18n);

/**
 * Merges locale data into the Tannin instance by domain. Accepts data in a
 * Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 *
 * @param {LocaleData} [data]   Locale data configuration.
 * @param {string}     [domain] Domain for which configuration applies.
 */
const setLocaleData = i18n.setLocaleData.bind(i18n);

/**
 * Resets all current Tannin instance locale data and sets the specified
 * locale data for the domain. Accepts data in a Jed-formatted JSON object shape.
 *
 * @see http://messageformat.github.io/Jed/
 *
 * @param {LocaleData} [data]   Locale data configuration.
 * @param {string}     [domain] Domain for which configuration applies.
 */
const resetLocaleData = i18n.resetLocaleData.bind(i18n);

/**
 * Subscribes to changes of locale data
 *
 * @param {SubscribeCallback} callback Subscription callback
 * @return {UnsubscribeCallback} Unsubscribe callback
 */
const subscribe = i18n.subscribe.bind(i18n);

/**
 * Retrieve the translation of text.
 *
 * @see https://developer.wordpress.org/reference/functions/__/
 *
 * @param {string} text     Text to translate.
 * @param {string} [domain] Domain to retrieve the translated text.
 *
 * @return {string} Translated text.
 */
const __ = i18n.__.bind(i18n);

/**
 * Retrieve translated string with gettext context.
 *
 * @see https://developer.wordpress.org/reference/functions/_x/
 *
 * @param {string} text     Text to translate.
 * @param {string} context  Context information for the translators.
 * @param {string} [domain] Domain to retrieve the translated text.
 *
 * @return {string} Translated context string without pipe.
 */
const _x = i18n._x.bind(i18n);

/**
 * Translates and retrieves the singular or plural form based on the supplied
 * number.
 *
 * @see https://developer.wordpress.org/reference/functions/_n/
 *
 * @param {string} single   The text to be used if the number is singular.
 * @param {string} plural   The text to be used if the number is plural.
 * @param {number} number   The number to compare against to use either the
 *                          singular or plural form.
 * @param {string} [domain] Domain to retrieve the translated text.
 *
 * @return {string} The translated singular or plural form.
 */
const _n = i18n._n.bind(i18n);

/**
 * Translates and retrieves the singular or plural form based on the supplied
 * number, with gettext context.
 *
 * @see https://developer.wordpress.org/reference/functions/_nx/
 *
 * @param {string} single   The text to be used if the number is singular.
 * @param {string} plural   The text to be used if the number is plural.
 * @param {number} number   The number to compare against to use either the
 *                          singular or plural form.
 * @param {string} context  Context information for the translators.
 * @param {string} [domain] Domain to retrieve the translated text.
 *
 * @return {string} The translated singular or plural form.
 */
const _nx = i18n._nx.bind(i18n);

/**
 * Check if current locale is RTL.
 *
 * **RTL (Right To Left)** is a locale property indicating that text is written from right to left.
 * For example, the `he` locale (for Hebrew) specifies right-to-left. Arabic (ar) is another common
 * language written RTL. The opposite of RTL, LTR (Left To Right) is used in other languages,
 * including English (`en`, `en-US`, `en-GB`, etc.), Spanish (`es`), and French (`fr`).
 *
 * @return {boolean} Whether locale is RTL.
 */
const isRTL = i18n.isRTL.bind(i18n);

/**
 * Check if there is a translation for a given string (in singular form).
 *
 * @param {string} single    Singular form of the string to look up.
 * @param {string} [context] Context information for the translators.
 * @param {string} [domain]  Domain to retrieve the translated text.
 * @return {boolean} Whether the translation exists or not.
 */
const hasTranslation = i18n.hasTranslation.bind(i18n);
//# sourceMappingURL=default-i18n.js.map

/***/ }),

/***/ "./node_modules/@wordpress/i18n/build-module/index.js":
/*!************************************************************!*\
  !*** ./node_modules/@wordpress/i18n/build-module/index.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   __: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.__),
/* harmony export */   _n: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__._n),
/* harmony export */   _nx: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__._nx),
/* harmony export */   _x: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__._x),
/* harmony export */   createI18n: () => (/* reexport safe */ _create_i18n__WEBPACK_IMPORTED_MODULE_1__.createI18n),
/* harmony export */   defaultI18n: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   getLocaleData: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.getLocaleData),
/* harmony export */   hasTranslation: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.hasTranslation),
/* harmony export */   isRTL: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.isRTL),
/* harmony export */   resetLocaleData: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.resetLocaleData),
/* harmony export */   setLocaleData: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.setLocaleData),
/* harmony export */   sprintf: () => (/* reexport safe */ _sprintf__WEBPACK_IMPORTED_MODULE_0__.sprintf),
/* harmony export */   subscribe: () => (/* reexport safe */ _default_i18n__WEBPACK_IMPORTED_MODULE_2__.subscribe)
/* harmony export */ });
/* harmony import */ var _sprintf__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./sprintf */ "./node_modules/@wordpress/i18n/build-module/sprintf.js");
/* harmony import */ var _create_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./create-i18n */ "./node_modules/@wordpress/i18n/build-module/create-i18n.js");
/* harmony import */ var _default_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./default-i18n */ "./node_modules/@wordpress/i18n/build-module/default-i18n.js");



//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@wordpress/i18n/build-module/sprintf.js":
/*!**************************************************************!*\
  !*** ./node_modules/@wordpress/i18n/build-module/sprintf.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   sprintf: () => (/* binding */ sprintf)
/* harmony export */ });
/* harmony import */ var memize__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! memize */ "./node_modules/memize/dist/index.js");
/* harmony import */ var sprintf_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! sprintf-js */ "./node_modules/@wordpress/i18n/node_modules/sprintf-js/src/sprintf.js");
/* harmony import */ var sprintf_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(sprintf_js__WEBPACK_IMPORTED_MODULE_1__);
/**
 * External dependencies
 */



/**
 * Log to console, once per message; or more precisely, per referentially equal
 * argument set. Because Jed throws errors, we log these to the console instead
 * to avoid crashing the application.
 *
 * @param {...*} args Arguments to pass to `console.error`
 */
const logErrorOnce = (0,memize__WEBPACK_IMPORTED_MODULE_0__["default"])(console.error); // eslint-disable-line no-console

/**
 * Returns a formatted string. If an error occurs in applying the format, the
 * original format string is returned.
 *
 * @param {string} format The format of the string to generate.
 * @param {...*}   args   Arguments to apply to the format.
 *
 * @see https://www.npmjs.com/package/sprintf-js
 *
 * @return {string} The formatted string.
 */
function sprintf(format, ...args) {
  try {
    return sprintf_js__WEBPACK_IMPORTED_MODULE_1___default().sprintf(format, ...args);
  } catch (error) {
    if (error instanceof Error) {
      logErrorOnce('sprintf error: \n\n' + error.toString());
    }
    return format;
  }
}
//# sourceMappingURL=sprintf.js.map

/***/ }),

/***/ "./node_modules/@wordpress/i18n/node_modules/sprintf-js/src/sprintf.js":
/*!*****************************************************************************!*\
  !*** ./node_modules/@wordpress/i18n/node_modules/sprintf-js/src/sprintf.js ***!
  \*****************************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_RESULT__;/* global window, exports, define */

!function() {
    'use strict'

    var re = {
        not_string: /[^s]/,
        not_bool: /[^t]/,
        not_type: /[^T]/,
        not_primitive: /[^v]/,
        number: /[diefg]/,
        numeric_arg: /[bcdiefguxX]/,
        json: /[j]/,
        not_json: /[^j]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijostTuvxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[+-]/
    }

    function sprintf(key) {
        // `arguments` is not an array, but should be fine for this call
        return sprintf_format(sprintf_parse(key), arguments)
    }

    function vsprintf(fmt, argv) {
        return sprintf.apply(null, [fmt].concat(argv || []))
    }

    function sprintf_format(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, arg, output = '', i, k, ph, pad, pad_character, pad_length, is_positive, sign
        for (i = 0; i < tree_length; i++) {
            if (typeof parse_tree[i] === 'string') {
                output += parse_tree[i]
            }
            else if (typeof parse_tree[i] === 'object') {
                ph = parse_tree[i] // convenience purposes only
                if (ph.keys) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < ph.keys.length; k++) {
                        if (arg == undefined) {
                            throw new Error(sprintf('[sprintf] Cannot access property "%s" of undefined value "%s"', ph.keys[k], ph.keys[k-1]))
                        }
                        arg = arg[ph.keys[k]]
                    }
                }
                else if (ph.param_no) { // positional argument (explicit)
                    arg = argv[ph.param_no]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (re.not_type.test(ph.type) && re.not_primitive.test(ph.type) && arg instanceof Function) {
                    arg = arg()
                }

                if (re.numeric_arg.test(ph.type) && (typeof arg !== 'number' && isNaN(arg))) {
                    throw new TypeError(sprintf('[sprintf] expecting number but found %T', arg))
                }

                if (re.number.test(ph.type)) {
                    is_positive = arg >= 0
                }

                switch (ph.type) {
                    case 'b':
                        arg = parseInt(arg, 10).toString(2)
                        break
                    case 'c':
                        arg = String.fromCharCode(parseInt(arg, 10))
                        break
                    case 'd':
                    case 'i':
                        arg = parseInt(arg, 10)
                        break
                    case 'j':
                        arg = JSON.stringify(arg, null, ph.width ? parseInt(ph.width) : 0)
                        break
                    case 'e':
                        arg = ph.precision ? parseFloat(arg).toExponential(ph.precision) : parseFloat(arg).toExponential()
                        break
                    case 'f':
                        arg = ph.precision ? parseFloat(arg).toFixed(ph.precision) : parseFloat(arg)
                        break
                    case 'g':
                        arg = ph.precision ? String(Number(arg.toPrecision(ph.precision))) : parseFloat(arg)
                        break
                    case 'o':
                        arg = (parseInt(arg, 10) >>> 0).toString(8)
                        break
                    case 's':
                        arg = String(arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 't':
                        arg = String(!!arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'T':
                        arg = Object.prototype.toString.call(arg).slice(8, -1).toLowerCase()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'u':
                        arg = parseInt(arg, 10) >>> 0
                        break
                    case 'v':
                        arg = arg.valueOf()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'x':
                        arg = (parseInt(arg, 10) >>> 0).toString(16)
                        break
                    case 'X':
                        arg = (parseInt(arg, 10) >>> 0).toString(16).toUpperCase()
                        break
                }
                if (re.json.test(ph.type)) {
                    output += arg
                }
                else {
                    if (re.number.test(ph.type) && (!is_positive || ph.sign)) {
                        sign = is_positive ? '+' : '-'
                        arg = arg.toString().replace(re.sign, '')
                    }
                    else {
                        sign = ''
                    }
                    pad_character = ph.pad_char ? ph.pad_char === '0' ? '0' : ph.pad_char.charAt(1) : ' '
                    pad_length = ph.width - (sign + arg).length
                    pad = ph.width ? (pad_length > 0 ? pad_character.repeat(pad_length) : '') : ''
                    output += ph.align ? sign + arg + pad : (pad_character === '0' ? sign + pad + arg : pad + sign + arg)
                }
            }
        }
        return output
    }

    var sprintf_cache = Object.create(null)

    function sprintf_parse(fmt) {
        if (sprintf_cache[fmt]) {
            return sprintf_cache[fmt]
        }

        var _fmt = fmt, match, parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree.push(match[0])
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree.push('%')
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list.push(field_match[1])
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else {
                                throw new SyntaxError('[sprintf] failed to parse named argument key')
                            }
                        }
                    }
                    else {
                        throw new SyntaxError('[sprintf] failed to parse named argument key')
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error('[sprintf] mixing positional and named placeholders is not (yet) supported')
                }

                parse_tree.push(
                    {
                        placeholder: match[0],
                        param_no:    match[1],
                        keys:        match[2],
                        sign:        match[3],
                        pad_char:    match[4],
                        align:       match[5],
                        width:       match[6],
                        precision:   match[7],
                        type:        match[8]
                    }
                )
            }
            else {
                throw new SyntaxError('[sprintf] unexpected placeholder')
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return sprintf_cache[fmt] = parse_tree
    }

    /**
     * export to either browser or node.js
     */
    /* eslint-disable quote-props */
    if (true) {
        exports.sprintf = sprintf
        exports.vsprintf = vsprintf
    }
    if (typeof window !== 'undefined') {
        window['sprintf'] = sprintf
        window['vsprintf'] = vsprintf

        if (true) {
            !(__WEBPACK_AMD_DEFINE_RESULT__ = (function() {
                return {
                    'sprintf': sprintf,
                    'vsprintf': vsprintf
                }
            }).call(exports, __webpack_require__, exports, module),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__))
        }
    }
    /* eslint-enable quote-props */
}(); // eslint-disable-line


/***/ }),

/***/ "./reactjs/src/components/Button_New.js":
/*!**********************************************!*\
  !*** ./reactjs/src/components/Button_New.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");


const Button_New = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((_ref, ref) => {
  let {
    label = '',
    padding = '',
    iconName = '',
    iconColor = '',
    iconPosition = 'before',
    iconGap = '',
    iconAnimation = '',
    iconRotation,
    background = 'transparent',
    onClick = () => {},
    customClass = '',
    disable = false,
    onlyText = false,
    color = '',
    borderColor = '',
    dataTarget = '',
    buttonLink = '',
    sameTab = false,
    smallButton = false,
    style
  } = _ref;
  const Icon = _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"][iconName] || null;
  const IconPosition = iconPosition === 'before' || iconPosition === 'right';
  const [isActive, setIsActive] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const handleClick = e => {
    e.preventDefault();
    if (!disable) {
      const button = e.currentTarget;
      setIsActive(!isActive);
      button.style.transform = 'scale(0.95)';
      setTimeout(() => button.style.transform = 'scale(1)', 150);
      buttonLink ? sameTab ? window.location.href = buttonLink : window.open(buttonLink, '_blank') : onClick(e);
    }
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: ref,
    className: `${onlyText ? 'wsx-btn-text' : 'wsx-btn'} ${background ? `wsx-bg-${background}` : ''} ${color ? `wsx-color-${color}` : ''} ${Icon ? 'wsx-btn-icon' : ''} ${iconGap ? `wsx-gap-${iconGap}` : ''} ${disable ? 'disable' : ''} ${borderColor ? `wsx-border-default wsx-bc-${borderColor}` : ''} ${smallButton ? 'wsx-btn-sm' : ''} ${customClass}`,
    style: {
      padding,
      ...style
    },
    onClick: handleClick,
    "data-target": dataTarget
  }, IconPosition && Icon && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-icon ${iconAnimation ? `wsx-anim-${iconAnimation}` : ''} ${iconColor ? `wsx-color-${iconColor}` : ''}`,
    style: {
      transition: 'transform var(--transition-md)',
      transform: iconRotation && isActive ? iconRotation == 'full' ? 'rotate(180deg)' : iconRotation == 'half' ? 'rotate(90deg)' : 'rotate(0deg)' : 'rotate(0deg)'
    }
  }, Icon), label, !IconPosition && Icon && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-icon ${iconAnimation ? `wsx-anim-${iconAnimation}` : ''} ${iconColor ? `wsx-color-${iconColor}` : ''}`,
    style: {
      transition: 'transform var(--transition-md)',
      transform: iconRotation && isActive ? iconRotation == 'full' ? 'rotate(180deg)' : iconRotation == 'half' ? 'rotate(90deg)' : 'rotate(0deg)' : 'rotate(0deg)'
    }
  }, Icon));
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Button_New);

/***/ }),

/***/ "./reactjs/src/components/Input.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Input.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");





const Input = props => {
  let {
    label,
    labelColor,
    labelClass,
    isLabelHide,
    type = 'text',
    name,
    value,
    required,
    tooltip,
    tooltipPosition,
    help,
    helpClass,
    inputClass,
    className = '',
    onChange,
    onClick,
    onBlur,
    isDisable,
    placeholder,
    id,
    iconClass,
    smart_tags,
    requiredColor
  } = props;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-wrapper ${className}`
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-label wsx-font-medium ${tooltip ? 'wsx-d-flex' : ''} ${labelColor && `wsx-color-${labelColor}`} ${labelClass}`
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("span", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_3__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-input-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("input", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, props, {
    // ref={ref}
    // key={`${name}_${Date.now().toString()}`}
    id: id || name,
    name: name,
    type: type,
    defaultValue: value,
    onChange: onChange
    // onClick={onClick}
    // onBlur={onBlur}
    ,
    disabled: isDisable ? true : false,
    placeholder: placeholder,
    required: required,
    className: `wsx-input ${inputClass}`
  })), iconClass && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("span", {
    className: iconClass
  }), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-help wsx-help-message ${helpClass}`
  }, help), smart_tags && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-help-message"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-smart-tags-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-smart-tags-heading"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-smart-tags-heading-text"
  }, wholesalex_overview?.i18n?.smart_tags || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Available Smart Tags:', 'wholesalex'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-smart-tags"
  }, Object.keys(smart_tags).map(tag => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("code", null, tag, " : ", smart_tags[tag]);
  }))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Input);

/***/ }),

/***/ "./reactjs/src/components/LoadingSpinner.js":
/*!**************************************************!*\
  !*** ./reactjs/src/components/LoadingSpinner.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const LoadingSpinner = () => {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex_circular_loading__wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex_loading_spinner"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    viewBox: "25 25 50 50",
    className: "move_circular"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("circle", {
    cx: "50",
    cy: "50",
    r: "20",
    fill: "none",
    className: "wholesalex_circular_loading_icon"
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LoadingSpinner);

/***/ }),

/***/ "./reactjs/src/components/MultiSelect.js":
/*!***********************************************!*\
  !*** ./reactjs/src/components/MultiSelect.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");




const MultiSelect = _ref => {
  let {
    name,
    value,
    options,
    placeholder,
    customClass,
    onMultiSelectChangeHandler,
    isDisable,
    tooltip,
    isAjax,
    requestFor
  } = _ref;
  const [showList, setShowList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [selectedOptions, setSelectedOptions] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(value);
  const [optionList, setOptionList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(options);
  const [searchValue, setSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const myRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const onSearch = key => {
    let selectedOptionValues = [];
    if (selectedOptions.length > 0) {
      selectedOptionValues = selectedOptions.map(option => option.value);
    }
    const searchResult = options.filter(option => {
      const {
        name,
        value
      } = option;
      return name.toLowerCase().includes(key.toLowerCase()) && selectedOptionValues.indexOf(value) === -1;
    });
    return searchResult;
  };
  const onInputChangeHandler = e => {
    setShowList(false);
    setSearchValue(e.target.value);
    setOptionList(onSearch(e.target.value));
    setShowList(true);
  };
  const optionClickHandler = option => {
    setShowList(false);
    setSelectedOptions([...selectedOptions, option]);
    setSearchValue("");
    setOptionList(onSearch(""));
    onMultiSelectChangeHandler(name, [...selectedOptions, option]);
  };
  const onDeleteOption = indexToBeDeleted => {
    setShowList(false);
    const selectedOptionAfterDeleted = selectedOptions.filter((value, index) => {
      return index !== indexToBeDeleted;
    });
    setSelectedOptions(selectedOptionAfterDeleted);
    setOptionList(onSearch(""));
    onMultiSelectChangeHandler(name, selectedOptionAfterDeleted);
    setShowList(true);
  };
  const handleClickOutside = e => {
    if (myRef?.current && !myRef.current.contains(e.target)) {
      setShowList(false);
    }
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setSelectedOptions(value);
  }, [value]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setOptionList(options);
    onMultiSelectChangeHandler(name, selectedOptions);
  }, [options]);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-multiselect-wrapper ${isDisable ? 'locked' : ''}`,
    key: `wsx-multiselect-${name}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-inputs"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-input-wrapper"
  }, selectedOptions.length > 0 && selectedOptions.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      key: `wsx-multiselect-opt-${name}-${option.value}-${index}`,
      className: "wsx-selected-option"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      tabIndex: -1,
      className: "wsx-icon-cross wsx-lh-0",
      onClick: () => onDeleteOption(index)
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_3__["default"], {
      content: option.name,
      position: "top",
      onlyText: true
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "multiselect-option-name"
    }, option.name)));
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-multiselect-option-wrapper ${selectedOptions.length && selectedOptions.length != 0 ? '' : 'wsx-w-full'}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    key: `wsx-input-${name}`,
    disabled: isDisable ? true : false,
    id: name,
    tabIndex: 0,
    autoComplete: "off",
    value: searchValue,
    className: `wsx-input ${customClass}`,
    placeholder: selectedOptions.length > 0 ? "" : placeholder,
    onChange: e => onInputChangeHandler(e),
    onClick: e => onInputChangeHandler(e)
  })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: myRef,
    key: `wsx-${name}`
  }, showList && optionList.length > 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-card wsx-multiselect-options wsx-scrollbar",
    key: `wsx-opt-${name}`
  }, optionList.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-multiselect-option",
      key: `wsx-opt-${name}-${option.value}-${index}`,
      onClick: () => optionClickHandler(option)
    }, option.name);
  })), showList && optionList.length === 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: `wsx-${name}-not-found`,
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No Data Found!', 'wholesalex')))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MultiSelect);

/***/ }),

/***/ "./reactjs/src/components/OutsideClick.js":
/*!************************************************!*\
  !*** ./reactjs/src/components/OutsideClick.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const handleOutsideClick = (ref, callback) => {
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const handleClickOutside = event => {
      if (ref.current && !ref.current.contains(event.target)) {
        callback(event);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [ref]);
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (handleOutsideClick);

/***/ }),

/***/ "./reactjs/src/components/Panel.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Panel.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const Panel = _ref => {
  let {
    title,
    panelCloseIcon,
    panelOpenIcon,
    className,
    children
  } = _ref;
  const [status, setStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  if (!panelCloseIcon) {
    panelCloseIcon = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "16",
      height: "16",
      viewBox: "0 0 16 16",
      fill: "none"
    }, " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M12.6667 5.66666L8.00004 10.3333L3.33337 5.66666",
      stroke: "#6C6E77",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    }));
  }
  if (!panelOpenIcon) {
    panelOpenIcon = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "16",
      height: "16",
      viewBox: "0 0 16 16",
      fill: "none"
    }, " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M3.33335 10.3333L8.00002 5.66668L12.6667 10.3333",
      stroke: "#6C6E77",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    }), " ");
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-component-panel ${className} ${status ? 'wsx-component-panel__open' : ''}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-component-panel__title wsx-flex_space-between_center",
    onClick: () => setStatus(!status)
  }, title, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: `wsx-component-panen__icon`
  }, status ? panelOpenIcon : panelCloseIcon)), status && children && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-component-panel__body"
  }, children));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Panel);

/***/ }),

/***/ "./reactjs/src/components/PopupModal.js":
/*!**********************************************!*\
  !*** ./reactjs/src/components/PopupModal.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");



const PopupModal = _ref => {
  let {
    renderContent,
    onClose,
    className = '',
    wrapperClass = ''
  } = _ref;
  const [isOpen, setIsOpen] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setIsOpen(true);
  }, []);
  const handleClose = () => {
    setIsOpen(false);
    setTimeout(onClose, 300);
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-modal-wrapper ${className} ${isOpen ? 'open' : 'close'}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-popup-content-wrapper ${wrapperClass}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-popup-content wsx-scrollbar"
  }, renderContent()), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon-cross",
    onClick: handleClose
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PopupModal);

/***/ }),

/***/ "./reactjs/src/components/Section.js":
/*!*******************************************!*\
  !*** ./reactjs/src/components/Section.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const Section = _ref => {
  let {
    title,
    children,
    className
  } = _ref;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-component-section ${className}`
  }, title && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-component-section__title"
  }, title), children && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-component-section__body"
  }, children));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Section);

/***/ }),

/***/ "./reactjs/src/components/Select.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Select.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");




const Select = props => {
  let {
    options,
    label,
    labelClass,
    labelColor,
    selectClass,
    name,
    defaultValue,
    value,
    setValue,
    tooltip,
    tooltipPosition,
    help,
    onChange,
    helpClass,
    className = '',
    isLabelHide,
    required,
    requiredColor,
    ...rest
  } = props;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-wrapper ${className}`
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-label ${tooltip ? 'wsx-d-flex' : ''} ${labelColor && `wsx-color-${labelColor}`} ${labelClass}`
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("span", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_3__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-select-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("select", (0,_babel_runtime_helpers_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
    name: name,
    value: value,
    onChange: onChange
  }, rest, {
    className: `wsx-select ${selectClass}`
  }), Object.keys(options).map((option, k) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("option", {
    value: option
  }, " ", options[option]))), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-select-help wsx-help-message ${helpClass}`
  }, help)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Select);

/***/ }),

/***/ "./reactjs/src/components/Slider.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Slider.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");



const Slider = props => {
  let {
    label,
    name,
    isLabelHide,
    isLabelSide,
    labelClass,
    defaultValue,
    isDisable,
    required,
    value,
    onChange,
    help,
    helpClass,
    desc,
    className,
    isLock,
    tooltip,
    tooltipPosition,
    sliderClass,
    activeBackground,
    inputBackground,
    thumbColor,
    labelColor,
    requiredColor,
    contentClass
  } = props;
  if (!name) {
    name = `wsx-slider-${Date.now().toString()}`;
  }
  const [thumbLeft, setThumbLeft] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('2px');
  const trackRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);

  // useLayoutEffect(() => {
  //     if (trackRef.current) {
  //         const trackWidth = trackRef.current.offsetWidth;
  //         setThumbLeft(value && value != 'no' ? `${trackWidth / 2 == 20 ? 22 : trackWidth / 2}px` : '2px');
  //     }
  // }, [value]);

  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-slider-wrapper ${className}`
  }, !isLabelHide && !isLabelSide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-label ${tooltip ? 'wsx-d-flex wsx-item-center' : ''} ${labelColor && `wsx-color-${labelColor}`} ${labelClass}`
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-slider-content ${contentClass}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-item-center wsx-gap-8"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    htmlFor: name,
    className: `wsx-slider ${value && value != 'no' && 'active'} ${sliderClass}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: trackRef,
    className: "wsx-slider-track wsx-relative",
    style: {
      backgroundColor: value && value != 'no' ? activeBackground || inputBackground || '#6C6CFF' : inputBackground || '#E2E4E9'
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    name: name,
    id: name,
    type: "checkbox",
    checked: value && value != 'no' ? true : false,
    onChange: onChange,
    className: "wsx-slider-input",
    disabled: isDisable ? true : false
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-slider-thumb",
    style: {
      // left: thumbLeft,
      backgroundColor: thumbColor || '#ffffff'
    }
  })), !isLabelHide && isLabelSide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-label wsx-mb-0 ${tooltip ? 'wsx-d-flex wsx-item-center' : ''} ${labelColor && `wsx-color-${labelColor}`} ${labelClass}`,
    style: {
      marginLeft: '8px'
    }
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help))), isLock && (!value || 'no' === value) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-lh-0"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].lock)), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-slider-help wsx-help-message ${helpClass}`
  }, help)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Slider);

/***/ }),

/***/ "./reactjs/src/components/Tier.js":
/*!****************************************!*\
  !*** ./reactjs/src/components/Tier.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _pages_dynamic_rules_MultiSelect__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../pages/dynamic_rules/MultiSelect */ "./reactjs/src/pages/dynamic_rules/MultiSelect.js");
/* harmony import */ var _Input__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Input */ "./reactjs/src/components/Input.js");
/* harmony import */ var _Select__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Select */ "./reactjs/src/components/Select.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _Button_New__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Button_New */ "./reactjs/src/components/Button_New.js");








const Tier = _ref => {
  let {
    fields,
    tier,
    setTier,
    tierName,
    tierFieldClass = '',
    index,
    setPopupStatus,
    src,
    labelClass,
    deleteSpaceLeft = '0px'
  } = _ref;
  const inputData = (fieldData, fieldName) => {
    let flag = tier[tierName] && tier[tierName]['tiers'] && tier[tierName]['tiers'][index] && tier[tierName]['tiers'][index][fieldName];
    const defValue = flag ? tier[tierName]['tiers'][index][fieldName] : fieldData['default'];
    const onChangeHandler = e => {
      let tier_value = e.target.value;
      if (fieldData['type'] === 'number' && tier_value != '' && tier_value < 0) {
        tier_value = 1;
      }
      let parent = tier[tierName] ? tier[tierName] : {
        tiers: []
      };
      let copy = parent['tiers'];
      if (!copy[index]) {
        copy.push({});
      }
      copy[index][e.target.getAttribute('data-name')] = tier_value;
      parent['tiers'] = copy;
      setTier({
        ...tier,
        [tierName]: parent
      });
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Input__WEBPACK_IMPORTED_MODULE_2__["default"], {
      label: index === 0 ? fieldData.label : '',
      id: `${fieldName}_${index}`,
      "data-name": fieldName,
      type: fieldData['type'],
      name: `${fieldName}_${index}`,
      value: defValue,
      placeholder: fieldData.placeholder,
      onChange: onChangeHandler,
      inputClass: "wsx-bg-base1",
      labelClass: labelClass
    });
  };
  const selectData = (fieldData, fieldName) => {
    let flag = tier[tierName] && tier[tierName]['tiers'] && tier[tierName]['tiers'][index] && tier[tierName]['tiers'][index][fieldName];
    const defValue = flag ? tier[tierName]['tiers'][index][fieldName] : fieldData['default'];
    const onChangeHandler = e => {
      const isLock = e?.target?.value?.startsWith('pro_') ? true : false;
      if (isLock && setPopupStatus) {
        setPopupStatus(true);
      } else {
        let parent = tier[tierName] ? tier[tierName] : {
          tiers: []
        };
        let copy = parent['tiers'];
        if (!copy[index]) {
          copy.push({});
        }
        copy[index][e.target.getAttribute('data-name')] = e.target.value;
        parent['tiers'] = copy;
        setTier({
          ...tier,
          [tierName]: parent
        });
      }
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Select__WEBPACK_IMPORTED_MODULE_3__["default"], {
      "data-name": fieldName,
      label: index == 0 ? fieldData.label : '',
      options: fieldData.options,
      name: `${fieldName}_${index}`,
      value: defValue,
      onChange: onChangeHandler,
      defaultValue: defValue,
      labelClass: labelClass,
      selectClass: "wsx-ellipsis"
    });
  };
  const dependencyCheck = deps => {
    if (!deps) {
      return true;
    }
    let _temp = tier[tierName] && tier[tierName]['tiers'] && tier[tierName]['tiers'][index] ? tier[tierName]['tiers'][index] : {};
    let _flag = true;
    deps.map((dep, i) => {
      if (_temp[dep['key']] !== dep['value']) {
        _flag = false;
      }
    });
    return _flag;
  };
  const multiselectData = (fieldData, fieldName) => {
    let flag = tier[tierName] && tier[tierName]['tiers'] && tier[tierName]['tiers'][index] && tier[tierName]['tiers'][index][fieldName];
    const defValue = flag ? tier[tierName]['tiers'][index][fieldName] : fieldData['default'];
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_pages_dynamic_rules_MultiSelect__WEBPACK_IMPORTED_MODULE_1__["default"], {
      name: fieldName,
      value: defValue,
      options: fieldData['options'],
      placeholder: fieldData.placeholder,
      onMultiSelectChangeHandler: (fieldName, selectedValues) => {
        let parent = tier[tierName] ? tier[tierName] : {
          tiers: []
        };
        let copy = parent['tiers'];
        if (!copy[index]) {
          copy.push({});
        }
        copy[index][fieldName] = [...selectedValues];
        parent['tiers'] = copy;
        setTier({
          ...tier,
          [tierName]: parent
        });
      },
      isAjax: fieldData?.is_ajax,
      ajaxAction: fieldData?.ajax_action,
      ajaxSearch: fieldData?.ajax_search
    });
  };
  const getTierLastIndex = () => {
    return tier && tier[tierName] && tier[tierName]['tiers'] && tier[tierName]['tiers'].length != 0 ? tier[tierName]['tiers'].length - 1 : 0;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-tiers-fields",
    key: `wsx-tier-fields-${tierName}-${index}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-tier-wrapper"
  }, Object.keys(fields).map((fieldName, i) => {
    switch (fields[fieldName]['type']) {
      case 'number':
      case 'text':
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
          key: `tier_field_${i}`,
          className: `tier-field ${tierFieldClass}`
        }, inputData(fields[fieldName], fieldName));
      case 'select':
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
          key: `tier_field_${i}`,
          className: `tier-field ${tierFieldClass}`
        }, selectData(fields[fieldName], fieldName));
      case 'multiselect':
        return dependencyCheck(fields[fieldName]['depends_on']) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
          key: `tier_field_${i}`,
          className: `tier-field multiselect ${tierFieldClass}`
        }, multiselectData(fields[fieldName], fieldName));
      case 'filter':
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
          className: "wsx-filter-group"
        }, fields[fieldName] && Object.keys(fields[fieldName]).map((filterFieldName, i) => {
          switch (fields[fieldName][filterFieldName]['type']) {
            case 'select':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                key: `tier_field_${i}`,
                className: `tier-field ${tierFieldClass}`
              }, selectData(fields[fieldName][filterFieldName], filterFieldName));
            case 'multiselect':
              return dependencyCheck(fields[fieldName][filterFieldName]['depends_on']) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                key: `tier_field_${i}`,
                className: `tier-field multiselect ${tierFieldClass}`
              }, multiselectData(fields[fieldName][filterFieldName], filterFieldName));
          }
        }));
      default:
        break;
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_5__["default"], {
    content: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Delete Condition.', 'wholesalex'),
    direction: "top",
    spaceLeft: deleteSpaceLeft
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    key: `wsx-tier-field-delete-${tierName}_${index}`,
    className: `wsx-tier-delete ${getTierLastIndex() < 1 ? 'disable' : ''}`,
    onClick: e => {
      let parent = {
        ...tier
      };
      let copy = [...parent[tierName]['tiers']];
      copy = copy.filter((row, r) => {
        return index != r;
      });
      parent[tierName]['tiers'] = copy;
      setTier(parent);
    }
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_4__["default"].delete_24))), index == getTierLastIndex() && (tierName === 'quantity_based' || tierName === 'conditions') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_7__["default"], {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Add New Condition', 'wholesalex'),
    iconName: "plus_20",
    background: "tertiary",
    customClass: "wsx-mt-24",
    onClick: e => {
      let parent = {
        ...tier
      };
      if (!parent[tierName]) {
        parent[tierName] = {
          'tiers': []
        };
      }
      let copy = [...parent[tierName]['tiers']];
      copy.push({
        _id: Date.now().toString(),
        src: src ? src : 'dynamic_rule'
      });
      parent[tierName]['tiers'] = copy;
      setTier(parent);
    }
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tier);

/***/ }),

/***/ "./reactjs/src/components/Toast.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Toast.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../context/toastContent */ "./reactjs/src/context/toastContent.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");



const Toast = _ref => {
  let {
    position,
    delay,
    type,
    ...rest
  } = _ref;
  const {
    state,
    dispatch
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_toastContent__WEBPACK_IMPORTED_MODULE_1__.ToastContext);
  const [deleteID, setDeleteID] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const sleep = ms => {
    return new Promise(resolve => setTimeout(resolve, ms));
  };
  const deleteMessage = async id => {
    setDeleteID(id);
    await sleep(200);
    dispatch({
      type: 'DELETE_MESSAGE',
      payload: id
    });
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const interval = setInterval(() => {
      if (delay && state.length > 0) {
        deleteMessage(state[0].id);
      }
    }, delay);
    return () => clearInterval(interval);
  }, [state, delay]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (state.length > 3) {
      deleteMessage(state[0].id);
    }
  }, [state]);
  const visibleToasts = state.slice(-3);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-toast-wrapper"
  }, visibleToasts.length > 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-toast-container"
  }, visibleToasts.map(_message => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: `wsx-toast-${_message.id}`,
    className: `wsx-toast-message-wrapper wsx-${_message.type} wsx-bg-${_message.type == 'success' ? 'positive' : 'negative'} ${position} ${deleteID === _message.id ? 'wsx-delete-toast' : ''}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-item-center wsx-gap-12"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-lh-0"
  }, _message.type == 'success' ? _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].smile : _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].sad), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-toast-message"
  }, _message.message)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-lh-0 wsx-toast-close",
    onClick: e => deleteMessage(_message.id)
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross)))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Toast);

/***/ }),

/***/ "./reactjs/src/components/ToggleGroupControl.js":
/*!******************************************************!*\
  !*** ./reactjs/src/components/ToggleGroupControl.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const ToogleGroupControl = _ref => {
  let {
    title,
    options,
    className,
    value,
    onChange
  } = _ref;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-component-toggle-group-control ${className}`
  }, title && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-component-toogle-group-control__title"
  }, " ", title, " "), options && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-component-toggle-group-control-options wsx-flex_space-between_center"
  }, Object.keys(options).map(option => {
    return option == value ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: `wsx-component-toggle-group-control-options__option`,
      "data-active-item": true,
      "aria-label": options[option],
      "data-wsx-value": option,
      onClick: () => onChange(option)
    }, options[option]) : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: `wsx-component-toggle-group-control-options__option`,
      "aria-label": options[option],
      "data-wsx-value": option,
      onClick: () => onChange(option)
    }, options[option]);
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ToogleGroupControl);

/***/ }),

/***/ "./reactjs/src/components/Tooltip.js":
/*!*******************************************!*\
  !*** ./reactjs/src/components/Tooltip.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_1__);


const Tooltip = props => {
  const [active, setActive] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [position, setPosition] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    top: 0,
    left: 0
  });
  const [adjustedDirection, setAdjustedDirection] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(props.direction || "top");
  const parentRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const tooltipRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const calculatePosition = () => {
    if (tooltipRef.current && parentRef.current) {
      const tooltipRect = tooltipRef.current.getBoundingClientRect();
      const parentRect = parentRef.current.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      let top = parentRect.top + window.scrollY - (tooltipRect.height + 8 || 0);
      let left = parentRect.left + window.scrollX - ((tooltipRect.width - parentRect.width) / 2 || 0);
      let newDirection = props.direction || "top";
      if (newDirection === "top") {
        top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
        left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
      } else if (newDirection === "bottom") {
        top = parentRect.bottom + window.scrollY + 8;
        left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
      } else if (newDirection === "left") {
        top = parentRect.top + window.scrollY + (parentRect.height - tooltipRect.height) / 2;
        left = parentRect.left + window.scrollX - (tooltipRect.width + 8);
      } else if (newDirection === "right") {
        top = parentRect.top + window.scrollY + (parentRect.height - tooltipRect.height) / 2;
        left = parentRect.right + window.scrollX + 8;
      }
      if (tooltipRect.bottom + 0 > viewportHeight && newDirection === "bottom") {
        newDirection = "top";
        top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
      } else if (tooltipRect.top < tooltipRect.height + 70 && newDirection === "top") {
        newDirection = "bottom";
        top = parentRect.bottom + window.scrollY + 8;
        if (tooltipRect.width + parentRect.right > viewportWidth) {
          newDirection = "left";
          top = parentRect.top + window.scrollY + (parentRect.height - tooltipRect.height) / 2;
          left = parentRect.left + window.scrollX - (tooltipRect.width + 8);
        }
      }
      if (tooltipRect.width + left + 70 > viewportWidth && newDirection === "right") {
        if (tooltipRect.left > tooltipRect.width + 8) {
          newDirection = "left";
          left = parentRect.left + window.scrollX - (tooltipRect.width + 8);
        } else if (tooltipRect.top < tooltipRect.height + 70) {
          newDirection = "bottom";
          top = parentRect.bottom + window.scrollY + 8;
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        } else {
          newDirection = "top";
          top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        }
      } else if (parentRect.left < tooltipRect.width + 8 && newDirection === "left") {
        if (tooltipRect.width + parentRect.right + 70 < viewportWidth) {
          newDirection = "right";
          left = parentRect.right + window.scrollX + 8;
        } else if (tooltipRect.top < tooltipRect.height + 70) {
          newDirection = "bottom";
          top = parentRect.bottom + window.scrollY + 8;
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        } else {
          newDirection = "top";
          top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        }
      }
      setAdjustedDirection(newDirection);
      setPosition({
        top,
        left
      });
    }
  };
  const showToolTip = () => {
    setActive(true);
  };
  const hideToolTip = () => {
    setActive(false);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    calculatePosition();
  }, [active]);
  const tooltipContent = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: tooltipRef,
    className: `wsx-tooltip-content wsx-font-regular ${props.tooltipContentClass} ${adjustedDirection}`,
    style: {
      position: "absolute",
      zIndex: active ? 999999 : -999999,
      top: active ? position.top : 0,
      left: active ? position.left : 0,
      visibility: active ? "visible" : "hidden",
      opacity: active ? 1 : 0,
      transition: "opacity var(--transition-md) ease-in-out"
    }
  }, props.type === "element" ? props.content : props.content.replace(/{.*}/, ""));
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: parentRef,
    className: "wsx-tooltip",
    onMouseEnter: showToolTip,
    onMouseLeave: hideToolTip,
    style: {
      margin: `${props.spaceTop || "0"} ${props.spaceRight || "0"} ${props.spaceBottom || "0"} ${props.spaceLeft || "0"}`
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `${!props.onlyText && `wsx-lh-0 wsx-icon-wrapper ${props.parentColor ? `wsx-color-${props.parentColor}` : 'wsx-color-secondary'}`} wsx-w-fit`
  }, props.children)), /*#__PURE__*/react_dom__WEBPACK_IMPORTED_MODULE_1___default().createPortal(tooltipContent, document.body));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tooltip);

/***/ }),

/***/ "./reactjs/src/context/RegistrationFormContext.js":
/*!********************************************************!*\
  !*** ./reactjs/src/context/RegistrationFormContext.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   RegistrationFormContext: () => (/* binding */ RegistrationFormContext),
/* harmony export */   RegistrationFormContextProvider: () => (/* binding */ RegistrationFormContextProvider)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");


const RegistrationFormContext = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)();
const RegistrationFormContextProvider = props => {
  const isWooUsername = wholesalex_overview.whx_form_builder_is_woo_username;
  const getFormHeader = (isShowFormTitle, isHideDescription, title, desc, styles) => {
    return {
      isShowFormTitle: isShowFormTitle,
      isHideDescription: isHideDescription,
      title: title,
      description: desc,
      styles: styles
    };
  };
  const createHeadingStyle = (size, weight, transform, padding, color) => {
    return {
      size: size,
      weight: weight,
      transform: transform,
      padding: padding,
      color: color
    };
  };
  const setHeaderStyles = (titleColor, descColor) => ({
    title: createHeadingStyle(24, 500, '', '', titleColor),
    description: createHeadingStyle(14, 400, '', '', descColor)
  });
  const initialState = {
    registrationFormHeader: getFormHeader(true, false, `Sign Up for New Account`, `Let's Get started with WholesaleX`, setHeaderStyles('343A46', '#343A46')),
    loginFormHeader: getFormHeader(true, false, `Log in with wholesalex`, `Let's Get started with WholesaleX`, setHeaderStyles('343A46', '#343A46')),
    settings: {
      inputVariation: 'variation_1',
      isShowLoginForm: false
    },
    registrationFields: [...(isWooUsername === 'no' ? [{
      "id": "regi_3",
      "type": "row",
      "columns": [{
        "status": true,
        "type": "text",
        "label": "Username",
        "name": "user_login",
        "isLabelHide": false,
        "placeholder": "",
        "columnPosition": "left",
        "parent": "regi_3",
        "required": true,
        "conditions": {
          "status": "show",
          "relation": "all",
          "tiers": [{
            "_id": Date.now().toString(),
            "condition": "",
            "field": "",
            "value": "",
            "src": "registration_form"
          }]
        }
      }],
      "isMultiColumn": false
    }] : []), {
      "id": "regi_1",
      "type": "row",
      "columns": [{
        "status": true,
        "type": "email",
        "label": "Email",
        "name": "user_email",
        "isLabelHide": false,
        "placeholder": "",
        "columnPosition": "left",
        "parent": "regi_1",
        'required': true,
        conditions: {
          status: 'show',
          relation: 'all',
          tiers: [{
            _id: Date.now().toString(),
            condition: '',
            field: '',
            value: '',
            src: 'registration_form'
          }]
        }
      }],
      "isMultiColumn": false
    }, {
      "id": "regi_2",
      "type": "row",
      "columns": [{
        "status": true,
        "type": "password",
        "label": "Password",
        "name": "user_pass",
        "isLabelHide": false,
        "placeholder": "",
        "columnPosition": "left",
        "parent": "regi_2",
        'required': true,
        conditions: {
          status: 'show',
          relation: 'all',
          tiers: [{
            _id: Date.now().toString(),
            condition: '',
            field: '',
            value: '',
            src: 'registration_form'
          }]
        }
      }],
      "isMultiColumn": false
    }],
    loginFields: [{
      "id": "login_row_1",
      "type": "row",
      "columns": [{
        "status": true,
        "type": "text",
        "label": "Username or email address",
        "name": "username",
        "isLabelHide": false,
        "placeholder": "",
        "columnPosition": "left",
        "parent": "login_row_1",
        'required': true
      }],
      "isMultiColumn": false
    }, {
      "id": "login_row_2",
      "type": "row",
      "columns": [{
        "status": true,
        "type": "password",
        "label": "Password",
        "name": "password",
        "isLabelHide": false,
        "placeholder": "",
        "columnPosition": "left",
        "parent": "login_row_2",
        'required': true
      }],
      "isMultiColumn": false
    }, {
      "id": "login_row_3",
      "type": "row",
      "columns": [{
        "type": "checkbox",
        "label": "",
        "name": "rememberme",
        "isLabelHide": true,
        "columnPosition": "left",
        "option": [{
          "name": "Remember me",
          "value": "rememberme"
        }],
        "parent": "row_3438998",
        "excludeRoles": []
      }],
      "isMultiColumn": false
    }],
    registrationFormButton: {
      title: 'Register'
    },
    loginFormButton: {
      title: 'Log in'
    },
    style: {}
  };
  const getVariationStates = (_initialState, variation) => {
    return _initialState;
  };
  const setPremade = (state, premade) => {
    const setFieldStyles = (labelColor, textColor, bgColor, borderColor, placeholderColor) => ({
      label: labelColor,
      text: textColor,
      background: bgColor,
      border: borderColor,
      placeholder: placeholderColor
    });
    const setButtonStyles = (textColor, bgColor, borderColor, hoverText, hoverBG, hoverBorder) => ({
      normal: {
        text: textColor,
        background: bgColor,
        border: borderColor
      },
      hover: {
        text: hoverText,
        background: hoverBG,
        border: hoverBorder
      }
    });
    const setContainerStyles = (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder) => ({
      main: {
        background: mainBgColor,
        border: mainBorder
      },
      signIn: {
        background: signInBgColor,
        border: signInBorder
      },
      signUp: {
        background: signUpBgColor,
        border: signUpBorder
      }
    });
    const setTypographyStyles = function () {
      let labelSize = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 14;
      let labelWeight = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 500;
      let labelTransform = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
      let inputSize = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 14;
      let inputWeight = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 400;
      let inputTransform = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : '';
      let buttonSize = arguments.length > 6 && arguments[6] !== undefined ? arguments[6] : 14;
      let buttonWeight = arguments.length > 7 && arguments[7] !== undefined ? arguments[7] : 50;
      let buttonTransform = arguments.length > 8 && arguments[8] !== undefined ? arguments[8] : '';
      return {
        field: {
          label: {
            size: labelSize,
            weight: labelWeight,
            transform: labelTransform
          },
          input: {
            size: inputSize,
            weight: inputWeight,
            transform: inputTransform
          }
        },
        button: {
          size: buttonSize,
          weight: buttonWeight,
          transform: buttonTransform
        }
      };
    };
    const setSizeSpacingStyles = (width, border, borderRadius, padding, align, separator) => ({
      width,
      border,
      borderRadius,
      padding,
      align,
      separator
    });
    let _style = {};
    switch (premade) {
      case 'premade_1':
        state.settings.inputVariation = 'variation_1';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#343A46', '#343A46');
        state.loginFormHeader.styles = setHeaderStyles('#343A46', '#343A46');
        _style = {
          color: {
            field: {
              signIn: {
                normal: setFieldStyles('#343A46', '#343A46', '#FFF', '#E9E9F0', '#6C6E77'),
                active: setFieldStyles('#343A46', '#343A46', '#FFF', '#6C6CFF', '#6C6E77'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '#FFF', '#FF6C6C', '#6C6E77')
              },
              signUp: {
                normal: setFieldStyles('#343A46', '#343A46', '#FFF', '#E9E9F0', '#6C6E77'),
                active: setFieldStyles('#343A46', '#343A46', '#FFF', '#6C6CFF', '#6C6E77'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '#FFF', '#FF6C6C', '#6C6E77')
              }
            },
            button: {
              signIn: setButtonStyles('#fff', '#6C6CFF', '', '#fff', '#1a1ac3', ''),
              signUp: setButtonStyles('#fff', '#6C6CFF', '', '#fff', '#1a1ac3', '')
            },
            container: setContainerStyles('#FFF', '#E9E9F0', '#FFF', '', '#FFF', '')
          },
          // (labelSize, labelWeight, inputSize, inputWeight, buttonSize, buttonWeight)
          typography: setTypographyStyles(14, 500, '', 14, 400, '', 14, 500, ''),
          sizeSpacing: {
            input: setSizeSpacingStyles(395, 1, 2, 16),
            button: setSizeSpacingStyles(50, 0, 2, 13, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 1, 16, 0, '', 1),
              signIn: setSizeSpacingStyles('', 0, 16, 54, '', ''),
              signUp: setSizeSpacingStyles('', 0, 16, 54, '', '')
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_2':
        state.settings.inputVariation = 'variation_2';
        state.registrationFormHeader.isHideDescription = true;
        state.loginFormHeader.isHideDescription = true;
        state.registrationFormHeader.styles = setHeaderStyles('#343A46', '#343A46');
        state.loginFormHeader.styles = setHeaderStyles('#fff', '#fff');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#fff', '#fff', '#343A46', '#C8C8D6', '#E9E9F0'),
                active: setFieldStyles('#fff', '#fff', '#343A46', '#6C6CFF', '#E9E9F0'),
                warning: setFieldStyles('#fff', '#FF6C6C', 'transparent', '#FF6C6C', '#E9E9F0')
              },
              signUp: {
                normal: setFieldStyles('#343A46', '#343A46', '#FFF', '#C8C8D6', '#6C6E77'),
                active: setFieldStyles('#343A46', '#343A46', '#FFF', '#6C6CFF', '#6C6E77'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '#FFF', '#FF6C6C', '#6C6E77')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signIn: setButtonStyles('#343A46', '#E9E9F0', '#E9E9F0', '#fff', '#6C6CFF', '#6C6CFF'),
              signUp: setButtonStyles('#fff', '#343A46', '#343A46', '#fff', '#6C6CFF', '#6C6CFF')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('', '', '#343A46', '#343A46', '', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles(14, 500, '', 14, 400, '', 14, 500, ''),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(395, 1, 2, 15, 'left'),
            button: setSizeSpacingStyles(100, 0, 2, 13, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 0, 0, 0, '', 0),
              signIn: setSizeSpacingStyles('', 0, 0, 44, '', 0),
              signUp: setSizeSpacingStyles('', 0, 0, 44, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_3':
        state.settings.inputVariation = 'variation_4';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#fff', '#E9E9F0');
        state.loginFormHeader.styles = setHeaderStyles('#6C6CFF', '#6C6E77');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#6C6CFF', '#6C6CFF', '#d6d6ff', '#6C6CFF', '#fff'),
                active: setFieldStyles('#6C6CFF', '#6C6CFF', '#d6d6ff', '#6C6CFF', '#fff'),
                warning: setFieldStyles('#6C6CFF', '#FF6C6C', 'rgba(255, 108, 108, 0.24)', '#FF6C6C', '#fff')
              },
              signUp: {
                normal: setFieldStyles('#fff', '#fff', '#8484ff', '#E9E9F0', '##C8C8FF'),
                active: setFieldStyles('#fff', '#fff', '#8484ff', '#E9E9F0', '##C8C8FF'),
                warning: setFieldStyles('#fff', '#FF6C6C', 'rgba(255, 108, 108, 0.24)', '#FF6C6C', '##C8C8FF')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#6C6CFF', '#fff', '#fff', '#fff', '#343A46', '#343A46'),
              signIn: setButtonStyles('#fff', '#6C6CFF', '#6C6CFF', '#fff', '#343A46', '#343A46')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('', '#6C6CFF', '#fff', '', '#6C6CFF', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles(14, 500, '', 14, 400, '', 16, 500, ''),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(395, 1, 2, 16, 'left'),
            button: setSizeSpacingStyles(100, 0, 2, 16, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 1, 12, 0, '', 0),
              signIn: setSizeSpacingStyles('', 0, 12, 54, '', 0),
              signUp: setSizeSpacingStyles('', 0, 0, 54, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_4':
        state.settings.inputVariation = 'variation_8';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#343A46', '#6C6E77');
        state.loginFormHeader.styles = setHeaderStyles('#343A46', '#6C6E77');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#343A46', '#343A46', '#fff', '#C8C8D6', '#C8C8D6'),
                active: setFieldStyles('#343A46', '#343A46', '', '#6C6CFF', '#C8C8D6'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '', '#FF6C6C', '#C8C8D6')
              },
              signUp: {
                normal: setFieldStyles('#343A46', '#343A46', '#fff', '#C8C8D6', '#C8C8D6'),
                active: setFieldStyles('#343A46', '#343A46', '', '#6C6CFF', '#C8C8D6'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '', '#FF6C6C', '#C8C8D6')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#fff', '#343A46', '', '#fff', '#6C6CFF', ''),
              signIn: setButtonStyles('#fff', '#343A46', '', '#fff', '#6C6CFF', '')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('', '#6C6CFF', '', '', '', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles(14, 400, '', 14, 400, '', 14, 500, ''),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(345, 1, 0, 7, 'left'),
            button: setSizeSpacingStyles(50, 0, 2, 13, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 1, 12, 0, '', 0),
              signIn: setSizeSpacingStyles('', 0, 0, 54, '', 0),
              signUp: setSizeSpacingStyles('', 0, 0, 54, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_5':
        state.settings.inputVariation = 'variation_6';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#343A46', '#6C6E77');
        state.loginFormHeader.styles = setHeaderStyles('#fff', '#fff');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#fff', '#fff', '#7878ff', '#fff', '#E9E9F0'),
                active: setFieldStyles('#fff', '#fff', '#7878ff', '#0000ff', '#E9E9F0'),
                warning: setFieldStyles('#fff', '#FF6C6C', '#7878ff', '#FF6C6C', '#E9E9F0')
              },
              signUp: {
                normal: setFieldStyles('#343A46', '#343A46', '#fff', '#E9E9F0', ''),
                active: setFieldStyles('#343A46', '#343A46', '#fff', '#6C6CFF', ''),
                warning: setFieldStyles('#343A46', '#FF6C6C', '#fff', '#FF6C6C', '')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#fff', '#343A46', '', '#fff', '#6C6CFF', ''),
              signIn: setButtonStyles('#6C6CFF', '#fff', '', '#fff', '#343A46', '')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('', '', '#6C6CFF', '', '', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles(14, 400, '', 14, 400, '', 14, 500, ''),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(390, 1, 2, 14, 'left'),
            button: setSizeSpacingStyles(60, 0, 2, 13, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 1, 12, 16, '', 0),
              signIn: setSizeSpacingStyles(566, 0, 8, 44, '', 0),
              signUp: setSizeSpacingStyles(673, 0, 0, 44, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_6':
        state.settings.inputVariation = 'variation_1';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#E9E9F0', '#C8C8D6');
        state.loginFormHeader.styles = setHeaderStyles('#343A46', '#656565');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#141516', '#141516', 'rgb(237 237 237)', '#656565', '#656565'),
                active: setFieldStyles('#141516', '#141516', 'rgb(237 237 237)', '#656565', '#656565'),
                warning: setFieldStyles('#141516', '#FF6C6C', 'rgba(20, 21, 22, 0.08)', '#FF6C6C', '#656565')
              },
              signUp: {
                normal: setFieldStyles('#E9E9F0', '#fff', '#141516', '#C8C8D6', '#6C6E77'),
                active: setFieldStyles('#E9E9F0', '#fff', '#141516', '#C8C8D6', '#6C6E77'),
                warning: setFieldStyles('#E9E9F0', '#FF6C6C', '#141516', '#FF6C6C', '#6C6E77')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#141516', '#E9E9F0', '', '#141516', '#656565', ''),
              signIn: setButtonStyles('#fff', '#141516', '', '#fff', '#656565', '')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('rgba(20, 21, 22, 0.08)', '#141516', '', '', '#141516', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles(),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(395, 1, 2, 13, 'left'),
            button: setSizeSpacingStyles(100, 0, 2, 11, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 2, 20, 16, '', 0),
              signIn: setSizeSpacingStyles('', 0, 8, 54, '', 0),
              signUp: setSizeSpacingStyles(650, 0, 0, 54, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_7':
        state.settings.inputVariation = 'variation_5';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#E9E9F0', '#E9E9F0');
        state.loginFormHeader.styles = setHeaderStyles('#E9E9F0', '#E9E9F0');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#fff', '#fff', '#296ddb', '#E9E9F0', '#9DC2FF'),
                active: setFieldStyles('#fff', '#fff', '#296ddb', '#E9E9F0', '#9DC2FF'),
                warning: setFieldStyles('#fff', '#FF6C6C', 'rgba(255, 108, 108, 0.24)', '#FF6C6C', '#9DC2FF')
              },
              signUp: {
                normal: setFieldStyles('#fff', '#fff', '#296ddb', '#E9E9F0', '#9DC2FF'),
                active: setFieldStyles('#fff', '#fff', '#296ddb', '#E9E9F0', '#9DC2FF'),
                warning: setFieldStyles('#fff', '#FF6C6C', 'rgba(255, 108, 108, 0.24)', '#FF6C6C', '#9DC2FF')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#fff', 'rgba(255, 255, 255, 0.26)', '#fff', '#fff', '#0051D4', '#fff'),
              signIn: setButtonStyles('#fff', 'rgba(255, 255, 255, 0.26)', '#fff', '#fff', '#0051D4', '#fff')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('#FFF', '#0051D4', '#0051D4', '#fff', '#0051D4', '#fff')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles('', '', '', '', '', '', '16'),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(395, 1, 2, 12, 'left'),
            button: setSizeSpacingStyles(100, 1, 2, 14, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 2, 20, 8, '', 0),
              signIn: setSizeSpacingStyles('', 7, 12, 54, '', 0),
              signUp: setSizeSpacingStyles('', 7, 12, 54, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_8':
        state.settings.inputVariation = 'variation_1';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#fff', '#ADADAD');
        state.loginFormHeader.styles = setHeaderStyles('#131313', '#555');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#131313', '#131313', '#E3E3E3', '#fff', '#767676'),
                active: setFieldStyles('#131313', '#131313', '#E3E3E3', '#FEAD01', '#767676'),
                warning: setFieldStyles('#131313', '#FF6C6C', 'rgba(255, 108, 108, 0.24)', '#FF6C6C', '#767676')
              },
              signUp: {
                normal: setFieldStyles('#fff', '#FFF', '#2F2F2F', '#131313', '#767676'),
                active: setFieldStyles('#fff', '#fff', '#2F2F2F', '#FEAD01', '#767676'),
                warning: setFieldStyles('#fff', '#FF6C6C', 'rgba(255, 108, 108, 0.24)', '#FF6C6C', '#767676')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#131313', '#FEAD01', '', '#131313', '#E9E9F0', ''),
              signIn: setButtonStyles('#fff', '#FEAD01', '', '#fff', '#141516', '')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('', '', '', '', '#131313', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles('', '', '', '', '', '', '16'),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(395, 1, 4, 13, 'left'),
            button: setSizeSpacingStyles(100, 0, 2, 10, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 0, 20, 16, '', 0),
              signIn: setSizeSpacingStyles('', 0, 0, 54, '', 0),
              signUp: setSizeSpacingStyles('670', 0, 8, 54, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      case 'premade_9':
        state.settings.inputVariation = 'variation_1';
        state.registrationFormHeader.isHideDescription = false;
        state.loginFormHeader.isHideDescription = false;
        state.registrationFormHeader.styles = setHeaderStyles('#343A46', '#343A46');
        state.loginFormHeader.styles = setHeaderStyles('#343A46', '#343A46');
        _style = {
          color: {
            field: {
              signIn: {
                // (labelColor, textColor, bgColor, borderColor, placeholderColor)
                normal: setFieldStyles('#343A46', '#343A46', '#FFF', '#E9E9F0', '#6C6E77'),
                active: setFieldStyles('#343A46', '#343A46', '#FFF', '#6C6CFF', '#6C6E77'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '#FFF', '#FF6C6C', '#6C6E77')
              },
              signUp: {
                normal: setFieldStyles('#343A46', '#343A46', '#FFF', '#E9E9F0', '#6C6E77'),
                active: setFieldStyles('#343A46', '#343A46', '#FFF', '#6C6CFF', '#6C6E77'),
                warning: setFieldStyles('#343A46', '#FF6C6C', '#FFF', '#FF6C6C', '#6C6E77')
              }
            },
            button: {
              // (textColor, bgColor, borderColor, hoverText,hoverBG,hoverBorder)
              signUp: setButtonStyles('#fff', '#6C6CFF', '', '#fff', '#1a1ac3', ''),
              signIn: setButtonStyles('#fff', '#6C6CFF', '', '#fff', '#1a1ac3', '')
            },
            // (mainBgColor, mainBorder, signInBgColor, signInBorder, signUpBgColor, signUpBorder,)
            container: setContainerStyles('#FFF', '#E9E9F0', '#FFF', '', '#FFF', '')
          },
          // (labelSize, labelWeight, labelTransform, inputSize, inputWeight, inputTransform, buttonSize, buttonWeight, buttonTransform) 
          typography: setTypographyStyles(14, 500, '', 14, 400, '', 14, 500, ''),
          sizeSpacing: {
            // (width, border, borderRadius, padding, align, separator)
            input: setSizeSpacingStyles(416, 1, 2, 16, 'left'),
            button: setSizeSpacingStyles(100, 0, 2, 13, 'left'),
            container: {
              main: setSizeSpacingStyles(1200, 1, 16, 0, '', 1),
              signIn: setSizeSpacingStyles('', 0, 0, 54, '', 0),
              signUp: setSizeSpacingStyles('', 0, 0, 54, '', 0)
            }
          }
        };
        state['style'] = _style;
        break;
      default:
        break;
    }
    return state;
  };
  const rowId = Date.now().toString().slice(6);
  let rowField = {
    id: 'row_' + rowId,
    type: 'row',
    columns: [],
    isMultiColumn: false
  };
  const getFieldColumn = _ref => {
    let {
      type,
      label = 'Field Label',
      name = '',
      options = [],
      isCustomField = false
    } = _ref;
    let fieldObject = {
      id: Date.now().toString(),
      type: type,
      label: label,
      name: isCustomField ? name + '_' + Date.now().toString() : name,
      isLabelHide: false,
      placeholder: label,
      columnPosition: 'left',
      custom_field: isCustomField,
      status: true,
      conditions: {
        status: 'show',
        relation: 'all',
        tiers: [{
          _id: Date.now().toString(),
          condition: '',
          field: '',
          value: '',
          src: 'regregistration_formi'
        }]
      }
    };
    if (name == 'wholesalex_registration_role') {
      options = wholesalex_overview.whx_form_builder_roles;
      options.unshift({
        name: 'Select Role',
        value: ''
      });
    }
    if (name == 'user_confirm_pass' || name === 'user_confirm_pass' || name === 'user_confirm_email' || name === 'password' || name === 'user_email' || name == 'user_pass' || name == 'wholesalex_registration_role') {
      fieldObject['required'] = true;
    }
    switch (type) {
      case 'text':
      case 'number':
      case 'tel':
      case 'password':
      case 'textarea':
      case 'email':
      case 'date':
      case 'url':
      case 'file':
        return fieldObject;
      case 'select':
      case 'radio':
      case 'checkbox':
        fieldObject['option'] = options.length ? options : [{
          name: 'Value 1',
          value: 'val1'
        }, {
          name: 'Value 2',
          value: 'val2'
        }, {
          name: 'Value 3',
          value: 'val3'
        }];
        return fieldObject;
      case 'termCondition':
        fieldObject['default_text'] = "I agree to the Terms and Conditions {Privacy Policy}";
        return fieldObject;
      case 'privacy_policy_text':
        return fieldObject;
      default:
        return fieldObject;
    }
  };

  //case empty
  //type: newField, column
  const reducer = (state, action) => {
    const {
      type,
      field,
      index
    } = action;
    let _state = {
      ...state
    };
    let tempState = [...state['registrationFields']];
    let _row = {};
    let _column = {};
    let _parent = '';
    let _parentIndex = '';
    let _propertyName = '';
    let _propertyValue = '';
    let _columnIndex = '';
    let _styles = {};
    switch (type) {
      case 'newFieldRow':
        _column = getFieldColumn({
          type: field?.type,
          label: field?.label,
          name: field?.name,
          isCustomField: field?.isCustomField
        });
        rowField['columns'].push({
          ..._column,
          parent: 'row_' + rowId
        });
        tempState.push(rowField);
        _state['registrationFields'] = tempState;
        break;
      case 'makeOneColumn':
        _row = tempState[index];
        _row['isMultiColumn'] = false;
        tempState[index] = {
          ..._row
        };
        if (_row['columns'].length == 2) {
          _column = getFieldColumn({
            type: _row['columns'][1]?.type,
            label: _row['columns'][1]?.label,
            name: _row['columns'][1]?.name
          });
          rowField['columns'].push({
            ..._column,
            parent: 'row_' + rowId
          });
          tempState[index]['columns'].pop();
          tempState.splice(index + 1, 0, rowField);
        }
        _state['registrationFields'] = tempState;
        break;
      case 'makeTwoColumn':
        _row = tempState[index];
        _row['isMultiColumn'] = true;
        tempState[index] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'insertFieldOnColumn':
        _row = tempState[index];
        _column = getFieldColumn({
          type: field?.type,
          label: field?.label,
          name: field?.name,
          isCustomField: field?.isCustomField
        });
        _row['columns'].push({
          ..._column,
          parent: _row['id']
        });
        tempState[index] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'insertFieldOnLeftColumn':
        _row = tempState[index];
        _column = getFieldColumn({
          type: field?.type,
          label: field?.label,
          name: field?.name,
          isCustomField: field?.isCustomField
        });
        _row['columns'].push({
          ..._column,
          parent: _row['id'],
          columnPosition: 'left'
        });
        tempState[index] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'insertFieldOnRightColumn':
        _row = tempState[index];
        _column = getFieldColumn({
          type: field?.type,
          label: field?.label,
          name: field?.name,
          isCustomField: field?.isCustomField
        });
        _row['columns'].push({
          ..._column,
          parent: _row['id'],
          columnPosition: 'right'
        });
        tempState[index] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'deleteRow':
        _row = tempState[index];
        tempState = tempState.filter((row, idx) => idx != index);
        _state['registrationFields'] = tempState;
        break;
      case 'deleteLeftColumn':
        _parentIndex = tempState.findIndex(row => row.id == field.parent);
        _row = tempState[_parentIndex];
        _row['columns'] = _row['columns'].filter((_f, idx) => _f?.columnPosition != 'left');
        tempState[_parentIndex] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'deleteRightColumn':
        _parentIndex = tempState.findIndex(row => row.id == field.parent);
        _row = tempState[_parentIndex];
        _row['columns'] = _row['columns'].filter((_f, idx) => _f?.columnPosition != 'right');
        tempState[_parentIndex] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'updateFullState':
        tempState = action.updatedState;
        _state['registrationFields'] = tempState;
        break;
      case 'updateColumns':
        _row = tempState[index];
        if (_row['columns'] && _row['columns'].length == 2) {
          _row['columns'] = action.updatedColumns;
          _row['columns'][0].columnPosition = 'left';
          _row['columns'][1].columnPosition = 'right';
          tempState[index] = _row;
          _state['registrationFields'] = tempState;
        }
        break;
      case 'updateField':
        _parent = action.parent;
        _propertyName = action.property;
        _propertyValue = action.value;
        _columnIndex = action.columnIndex;
        _row = tempState[index];
        _row['columns'][_columnIndex][_propertyName] = _propertyValue;
        tempState[index] = _row;
        _state['registrationFields'] = tempState;
        break;
      case 'toogleRegistrationFormTitle':
        _state['registrationFormHeader']['isShowFormTitle'] = action.value;
      case 'toogleFormTitle':
        _state['settings']['isShowFormTitle'] = action.value;
        break;
      case 'toogleRegistrationFormDescriptionStatus':
        _state['registrationFormHeader']['isHideDescription'] = action.value;
        break;
      case 'updateRegistrationFormTitle':
        _state['registrationFormHeader']['title'] = action.value;
        break;
      case 'updateRegistrationFormDescription':
        _state['registrationFormHeader']['description'] = action.value;
        break;
      case 'toogleLoginFormTitle':
        _state['loginFormHeader']['isShowFormTitle'] = action.value;
        break;
      case 'toogleLoginFormDescriptionStatus':
        _state['loginFormHeader']['isHideDescription'] = action.value;
        break;
      case 'updateLoginFormTitle':
        _state['loginFormHeader']['title'] = action.value;
        break;
      case 'updateLoginFormDescription':
        _state['loginFormHeader']['description'] = action.value;
        break;
      case 'updateInputVariation':
        _state['settings']['inputVariation'] = action.value;
        _state['styles'] = getVariationStates(state.styles, action.value);
        break;
      case 'toogleShowLoginFormStatus':
        _state['settings']['isShowLoginForm'] = action.value;
        break;
      case 'updateStyle':
        _styles = _state['styles'];
        if (action.styles.elementState) {
          _styles[action.styles.type][action.styles.element][action.styles.elementState][action.styles.property] = action.styles.value;
        } else {
          _styles[action.styles.type][action.styles.element][action.styles.property] = action.styles.value;
        }
        _state['styles'] = _styles;
        break;
      case 'updateFormHeadingStyle':
        if (action.formType == 'Registration') {
          _state['registrationFormHeader']['styles'][action.styleFor][action.property] = action.value;
        } else if (action.formType == 'Login') {
          _state['loginFormHeader']['styles'][action.styleFor][action.property] = action.value;
        }
        break;
      case 'init':
        _state = {
          ...initialState,
          ...action['value']
        };
        break;
      case 'updateRegistrationButtonText':
        _state['registrationFormButton']['title'] = action.value;
        break;
      case 'updateLoginButtonText':
        _state['loginFormButton']['title'] = action.value;
        break;
      case 'updateFieldCondition':
        _row = _state['registrationFields'][action.parentIndex];
        _row['columns'][action.columnIndex]['conditions'] = action.value['conditions'];
        _state['registrationFields'][action.parentIndex] = _row;
        break;
      case 'setPremade':
        _state = setPremade(state, action.premade);
        break;
      case 'setStyle':
        if (action.stateName && action.loginOrSignUp) {
          _state['style'][action.sectionName][action.panelName][action.loginOrSignUp][action.stateName][action.property] = action.value;
        } else if (action.loginOrSignUp && !action.stateName) {
          _state['style'][action.sectionName][action.panelName][action.loginOrSignUp][action.property] = action.value;
        } else if (!action.loginOrSignUp && !action.stateName) {
          _state['style'][action.sectionName][action.panelName][action.property] = action.value;
        }
        break;
      default:
        break;
    }
    return _state;
  };
  const [registrationForm, setRegistrationForm] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useReducer)(reducer, setPremade(initialState, 'premade_1'));
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(RegistrationFormContext.Provider, {
    value: {
      registrationForm,
      setRegistrationForm
    }
  }, props.children);
};

/***/ }),

/***/ "./reactjs/src/context/toastContent.js":
/*!*********************************************!*\
  !*** ./reactjs/src/context/toastContent.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ToastContext: () => (/* binding */ ToastContext),
/* harmony export */   ToastContextProvider: () => (/* binding */ ToastContextProvider)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const ToastContextProvider = props => {
  const [state, dispatch] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useReducer)((state, action) => {
    switch (action.type) {
      case "ADD_MESSAGE":
        return [...state, action.payload];
      case "DELETE_MESSAGE":
        return state.length > 0 && state.filter(message => message.id !== action.payload);
      default:
        return state;
    }
  }, []);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(ToastContext.Provider, {
    value: {
      state,
      dispatch
    }
  }, props.children);
};
const ToastContext = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)();

/***/ }),

/***/ "./reactjs/src/pages/dynamic_rules/MultiSelect.js":
/*!********************************************************!*\
  !*** ./reactjs/src/pages/dynamic_rules/MultiSelect.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _components_Tooltip__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/Tooltip */ "./reactjs/src/components/Tooltip.js");




const MultiSelect = _ref => {
  let {
    name,
    value,
    options,
    placeholder,
    customClass,
    onMultiSelectChangeHandler,
    isDisable,
    tooltip,
    isAjax,
    ajaxAction,
    ajaxSearch,
    dependsValue
  } = _ref;
  const [showList, setShowList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [selectedOptions, setSelectedOptions] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(value);
  const [optionList, setOptionList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [searchValue, setSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const [isSearching, setIsSearching] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [tempSearchValue, setTempSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [allOptions, setAllOptions] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const myRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const onInputChangeHandler = e => {
    setShowList(true);
    setTempSearchValue(e.target.value);
  };
  const selectOption = option => {
    setSelectedOptions([...selectedOptions, option]);
    const searchResult = optionList.filter(op => {
      const {
        name,
        value
      } = op;
      return value.toString().toLowerCase() !== option.value.toString().toLowerCase();
    });
    setOptionList(searchResult);
    setTempSearchValue('');
    onMultiSelectChangeHandler(name, [...selectedOptions, option]);
    setShowList(false);
  };
  const deleteOption = option => {
    const selectedOptionAfterDeleted = selectedOptions.filter(op => {
      const {
        name,
        value
      } = op;
      return value.toString().toLowerCase() !== option.value.toString().toLowerCase();
    });
    setSelectedOptions(selectedOptionAfterDeleted);
    setTempSearchValue('');
    onMultiSelectChangeHandler(name, selectedOptionAfterDeleted);
  };
  const handleClickOutside = e => {
    if (!myRef.current.contains(e.target)) {
      setShowList(false);
    }
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);
  const performAjaxSearch = async signal => {
    setIsSearching(true);
    let attr = {
      type: 'get',
      action: 'dynamic_rule_action',
      nonce: wholesalex.nonce,
      query: searchValue,
      ajax_action: ajaxAction
    };
    try {
      const res = await wp.apiFetch({
        path: '/wholesalex/v1/dynamic_rule_action',
        method: 'POST',
        data: attr,
        signal: signal
      });
      if (res.status) {
        let selectedOptionValues = [];
        if (selectedOptions.length > 0) {
          selectedOptionValues = selectedOptions.map(option => option.value);
        }
        const searchResult = res?.data?.filter(option => {
          const {
            name,
            value
          } = option;
          return selectedOptionValues.indexOf(value) === -1;
        });
        searchResult.sort((a, b) => a.name.length - b.name.length);
        setOptionList(searchResult);
        setIsSearching(false);
      } else {
        setIsSearching(false);
      }
    } catch (error) {
      if (error.name === 'AbortError') {
        // Request was cancelled
        return;
      }
    }
  };
  const getOptions = async signal => {
    setIsSearching(true);
    let attr = {
      type: 'get',
      action: 'dynamic_rule_action',
      nonce: wholesalex.nonce,
      ajax_action: ajaxAction
    };
    if (dependsValue) {
      attr['depends'] = dependsValue;
    }
    try {
      const res = await wp.apiFetch({
        path: '/wholesalex/v1/dynamic_rule_action',
        method: 'POST',
        data: attr,
        signal: signal
      });
      if (res.status) {
        let selectedOptionValues = [];
        if (selectedOptions.length > 0) {
          selectedOptionValues = selectedOptions.map(option => option.value);
        }
        const searchResult = res?.data?.filter(option => {
          const {
            name,
            value
          } = option;
          return selectedOptionValues.indexOf(value) === -1;
        });
        searchResult.sort((a, b) => a.name.length - b.name.length);
        setAllOptions(res.data);
        setOptionList(searchResult);
        setIsSearching(false);
      } else {
        setIsSearching(false);
      }
    } catch (error) {
      if (error.name === 'AbortError') {
        // Request was cancelled
        return;
      }
    }
  };
  const performNonAjaxSearch = () => {
    let selectedOptionValues = [];
    if (selectedOptions.length > 0) {
      selectedOptionValues = selectedOptions.map(option => option.value);
    }
    const searchResult = allOptions.filter(option => {
      const {
        name,
        value
      } = option;
      return name.toLowerCase().includes(searchValue.toLowerCase()) && selectedOptionValues.indexOf(value) === -1;
    });
    setOptionList(searchResult);
  };
  const abortController = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    abortController.current = new AbortController();
    const {
      signal
    } = abortController.current;
    if (!ajaxSearch && isAjax) {
      getOptions(signal);
    }
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (ajaxSearch) {
      abortController.current = new AbortController();
      const {
        signal
      } = abortController.current;
      if (searchValue.length >= 2) {
        performAjaxSearch(signal);
      }
      return () => {
        if (abortController.current) {
          abortController.current.abort("Duplicate");
        }
      };
    } else {
      performNonAjaxSearch();
    }
  }, [searchValue, setSearchValue]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (ajaxSearch) {
      if (tempSearchValue.length > 1) {
        const delay = setTimeout(() => setSearchValue(tempSearchValue), 500);
        return () => clearTimeout(delay);
      }
    } else {
      setSearchValue(tempSearchValue);
    }
  }, [tempSearchValue]);

  //Remove Product title extra quote from esc_attr 
  const decodeHtmlEntities = str => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-multiselect-wrapper ${isDisable ? 'locked' : ''}`,
    key: `wsx-multiselect-${name}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-inputs"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-input-wrapper"
  }, selectedOptions.length > 0 && selectedOptions.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      key: `wsx-multiselect-opt-${name}-${option.value}-${index}`,
      className: "wsx-selected-option"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      tabIndex: -1,
      className: "wsx-icon-cross wsx-lh-0",
      onClick: () => deleteOption(option)
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_3__["default"], {
      content: option.name,
      position: "top",
      onlyText: true
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "multiselect-option-name"
    }, decodeHtmlEntities(option.name))));
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-multiselect-option-wrapper ${selectedOptions.length && selectedOptions.length != 0 ? '' : 'wsx-w-full'}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    key: `wsx-input${name}`,
    disabled: isDisable ? true : false,
    id: name,
    tabIndex: 0,
    autoComplete: "off",
    value: tempSearchValue,
    className: customClass,
    placeholder: selectedOptions.length > 0 ? "" : placeholder,
    onChange: e => onInputChangeHandler(e),
    onClick: e => onInputChangeHandler(e)
  })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: myRef,
    key: `wsx-${name}`
  }, showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, !isSearching && optionList.length > 0 && tempSearchValue.length > 1 && showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-card wsx-multiselect-options wsx-scrollbar",
    key: `wsx-opt-${name}`
  }, optionList.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-multiselect-option",
      key: `wsx-opt-${name}-${option.value}-${index}`,
      onClick: () => selectOption(option)
    }, decodeHtmlEntities(option.name));
  })), !isSearching && tempSearchValue.length > 1 && showList && optionList.length === 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: `wsx-${name}-not-found`,
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No Data Found! Please try with another keyword.', 'wholesalex'))), tempSearchValue.length < 2 && showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: `wsx-${name}-not-found`,
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enter 2 or more character to search.', 'wholesalex'))), isSearching && showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: `wsx-${name}-not-found`,
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Searching...', 'wholesalex'))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MultiSelect);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/ChooseInputStyle.js":
/*!*************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/ChooseInputStyle.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");


const {
  useState,
  useEffect,
  useRef
} = wp.element;
const ChooseInputStyle = () => {
  const {
    registrationForm,
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_1__.RegistrationFormContext);
  const options = ['variation_1', 'variation_2', 'variation_3', 'variation_4', 'variation_5', 'variation_6', 'variation_7', 'variation_8'];
  const myRef = useRef();
  const [isOpen, setOpen] = useState(false);
  const getImageUrl = option => {
    return wholesalex.url + 'assets/img/input-field-variations/' + option + '.svg';
  };
  const handleClickOutside = e => {
    if (!myRef.current.contains(e.target)) {
      setOpen(false);
    }
  };
  useEffect(() => {
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [handleClickOutside]);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: myRef,
    className: `wholesalex-choose-input-field-wrap`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-choose-input-field-label"
  }, wholesalex_overview?.i18n.whx_form_builder_choose_input_style && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", null, wholesalex_overview?.i18n?.choose_input_style)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-choose-input-field-popup`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    tabIndex: 0,
    className: (isOpen ? "isOpen " : '') + " wsx-chose-input-wrap",
    onClick: () => setOpen(!isOpen)
  }, registrationForm?.settings?.inputVariation?.replace('_', ' ')?.replace('variation', 'Style'), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-choose-input-selected"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-choose-input-selected-img"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
    src: getImageUrl(registrationForm.settings.inputVariation),
    alt: registrationForm.settings.inputVariation
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-choose-input-dropdown-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("i", {
    className: 'dashicons dashicons-arrow-' + (isOpen ? 'up-alt2' : 'down-alt2')
  })))), isOpen && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("ul", null, options.map((item, k) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("li", {
    key: k,
    className: `${registrationForm.settings.inputVariation == item ? 'wsx-selected' : ''}`,
    onClick: () => {
      setOpen(!isOpen);
      setRegistrationForm({
        type: 'updateInputVariation',
        value: item
      });
    },
    value: item
  }, 'Style ', k + 1, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
    src: getImageUrl(item),
    alt: item
  })))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ChooseInputStyle);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/ColorPanenDropdown.js":
/*!***************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/ColorPanenDropdown.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);



function ColorPanelDropdown(_ref) {
  let {
    label,
    getColor,
    value,
    onChange,
    setColor,
    colorPickerRef,
    className,
    placement = 'left-start'
  } = _ref;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Dropdown, {
    className: className ? className : 'wholesalex-color-picker-dropdown',
    ref: colorPickerRef ? colorPickerRef : /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createRef)(),
    contentClassName: "wsx-color-picker-content",
    popoverProps: {
      placement: placement,
      offset: 36,
      shift: true
    },
    renderToggle: _ref2 => {
      let {
        onToggle,
        isOpen
      } = _ref2;
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: `wholesalex-form-builder-select-color wsx-flex_space-between_center ${isOpen ? 'is-open' : ''}`,
        onClick: onToggle
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "select-color-details"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ColorIndicator, {
        colorValue: value,
        className: "wholesalex-form-builder__color"
      }), label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: "select-color-label wsx-font-12-normal"
      }, label)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: "selected-color-code wsx-font-12-normal"
      }, value));
    },
    renderContent: _ref3 => {
      let {
        isOpen,
        onToggle
      } = _ref3;
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "wholesalex-form-builder-select-color__dropdown"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ColorPicker, {
        onChange: onChange
      }));
    }
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ColorPanelDropdown);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/ColumnField.js":
/*!********************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/ColumnField.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");
/* harmony import */ var _FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FormBuilderDropdown */ "./reactjs/src/pages/registration_form_builder/FormBuilderDropdown.js");
/* harmony import */ var _components_Slider__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/Slider */ "./reactjs/src/components/Slider.js");
/* harmony import */ var _components_MultiSelect__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../components/MultiSelect */ "./reactjs/src/components/MultiSelect.js");
/* harmony import */ var _components_Input__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../components/Input */ "./reactjs/src/components/Input.js");
/* harmony import */ var _Utils__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Utils */ "./reactjs/src/pages/registration_form_builder/Utils.js");
/* harmony import */ var _components_Tier__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../components/Tier */ "./reactjs/src/components/Tier.js");
/* harmony import */ var _components_PopupModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../components/PopupModal */ "./reactjs/src/components/PopupModal.js");
/* harmony import */ var _components_Tooltip__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../components/Tooltip */ "./reactjs/src/components/Tooltip.js");











const ColumnField = _ref => {
  let {
    showDelete,
    column,
    parentIndex,
    colRef,
    columnIndex,
    handleColumnDragEnter,
    handleColumnDragStart,
    isRowActive,
    settingRef,
    setUpgradeProPopupStatus
  } = _ref;
  const {
    registrationForm,
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_1__.RegistrationFormContext);
  const [isDragable, makeDragable] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [fieldSettingActiveTab, setFieldSettingActiveTab] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('setting');
  const [activeField, setActiveField] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(-1);
  const [showVideo, setShowVideo] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const icons = {
    drag: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "18",
      height: "18",
      viewBox: "0 0 18 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M4.14341 6.85839L2 9.0018L4.14341 11.1452M6.85839 4.14341L9.0018 2L11.1452 4.14341M11.1452 13.9316L9.0018 16.075L6.85839 13.9316M13.9316 6.85839L16.075 9.0018L13.9316 11.1452M2.78592 9.0018H15.2177M9.0018 2.71447V15.2891",
      stroke: "#343A46",
      "stroke-width": "1.3",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    })),
    delete: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "18",
      height: "18",
      viewBox: "0 0 18 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M6.76247 2.42324C6.86385 2.03514 7.18817 1.76758 7.5572 1.76758H10.4429C10.812 1.76758 11.1363 2.03514 11.2377 2.42324L11.4855 3.37197C11.5869 3.76006 11.9112 4.02762 12.2802 4.02762H14.1659C14.5082 4.02762 14.7858 4.33118 14.7858 4.70564C14.7858 5.08009 14.5082 5.38365 14.1659 5.38365H3.83425C3.49189 5.38365 3.21436 5.08009 3.21436 4.70564C3.21436 4.33118 3.49189 4.02762 3.83425 4.02762H5.7199C6.08893 4.02762 6.41325 3.76006 6.51463 3.37197L6.76247 2.42324Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M4.04089 6.28767H8.5868V13.0678C8.5868 13.3174 8.77182 13.5198 9.00007 13.5198C9.22832 13.5198 9.41333 13.3174 9.41333 13.0678V6.28767H13.9593L12.9144 14.6681C12.8027 15.564 12.1032 16.2319 11.2765 16.2319H6.72363C5.89697 16.2319 5.19742 15.564 5.08573 14.6681L4.04089 6.28767Z",
      fill: "#343A46"
    })),
    setting: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "18",
      height: "18",
      viewBox: "0 0 18 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("g", {
      "clip-path": "url(#clip0_134_402)"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      "fill-rule": "evenodd",
      "clip-rule": "evenodd",
      d: "M14.4645 11.9704C14.4204 11.7271 14.4502 11.4762 14.55 11.25C14.6451 11.0282 14.8029 10.839 15.0042 10.7057C15.2054 10.5725 15.4412 10.501 15.6825 10.5H15.75C16.1478 10.5 16.5294 10.342 16.8107 10.0607C17.092 9.77936 17.25 9.39782 17.25 9C17.25 8.60218 17.092 8.22064 16.8107 7.93934C16.5294 7.65804 16.1478 7.5 15.75 7.5H15.6225C15.3812 7.49904 15.1454 7.42753 14.9442 7.29427C14.7429 7.16101 14.5851 6.97183 14.49 6.75V6.69C14.3902 6.46379 14.3604 6.21285 14.4045 5.96956C14.4486 5.72626 14.5646 5.50176 14.7375 5.325L14.7825 5.28C14.922 5.14069 15.0326 4.97526 15.1081 4.79316C15.1836 4.61106 15.2224 4.41587 15.2224 4.21875C15.2224 4.02163 15.1836 3.82644 15.1081 3.64434C15.0326 3.46224 14.922 3.29681 14.7825 3.1575C14.6432 3.01804 14.4778 2.9074 14.2957 2.83191C14.1136 2.75642 13.9184 2.71757 13.7213 2.71757C13.5241 2.71757 13.3289 2.75642 13.1468 2.83191C12.9647 2.9074 12.7993 3.01804 12.66 3.1575L12.615 3.2025C12.4382 3.3754 12.2137 3.49139 11.9704 3.5355C11.7271 3.57962 11.4762 3.54984 11.25 3.45C11.0282 3.35493 10.839 3.19707 10.7057 2.99585C10.5725 2.79463 10.501 2.55884 10.5 2.3175V2.25C10.5 1.85218 10.342 1.47064 10.0607 1.18934C9.77936 0.908035 9.39782 0.75 9 0.75C8.60218 0.75 8.22064 0.908035 7.93934 1.18934C7.65804 1.47064 7.5 1.85218 7.5 2.25V2.3775C7.49904 2.61884 7.42753 2.85463 7.29427 3.05585C7.16101 3.25707 6.97183 3.41493 6.75 3.51H6.69C6.46379 3.60984 6.21285 3.63962 5.96956 3.5955C5.72626 3.55139 5.50176 3.4354 5.325 3.2625L5.28 3.2175C5.14069 3.07804 4.97526 2.9674 4.79316 2.89191C4.61106 2.81642 4.41587 2.77757 4.21875 2.77757C4.02163 2.77757 3.82644 2.81642 3.64434 2.89191C3.46224 2.9674 3.29681 3.07804 3.1575 3.2175C3.01804 3.35681 2.9074 3.52224 2.83191 3.70434C2.75642 3.88644 2.71757 4.08163 2.71757 4.27875C2.71757 4.47587 2.75642 4.67106 2.83191 4.85316C2.9074 5.03526 3.01804 5.20069 3.1575 5.34L3.2025 5.385C3.3754 5.56176 3.49139 5.78626 3.5355 6.02956C3.57962 6.27285 3.54984 6.52379 3.45 6.75C3.36429 6.98305 3.21045 7.18493 3.00847 7.32938C2.8065 7.47384 2.56575 7.55419 2.3175 7.56H2.25C1.85218 7.56 1.47064 7.71804 1.18934 7.99934C0.908035 8.28064 0.75 8.66218 0.75 9.06C0.75 9.45782 0.908035 9.83936 1.18934 10.1207C1.47064 10.402 1.85218 10.56 2.25 10.56H2.3775C2.61884 10.561 2.85463 10.6325 3.05585 10.7657C3.25707 10.899 3.41493 11.0882 3.51 11.31C3.60984 11.5362 3.63962 11.7871 3.5955 12.0304C3.55139 12.2737 3.4354 12.4982 3.2625 12.675L3.2175 12.72C3.07804 12.8593 2.9674 13.0247 2.89191 13.2068C2.81642 13.3889 2.77757 13.5841 2.77757 13.7812C2.77757 13.9784 2.81642 14.1736 2.89191 14.3557C2.9674 14.5378 3.07804 14.7032 3.2175 14.8425C3.35681 14.982 3.52224 15.0926 3.70434 15.1681C3.88644 15.2436 4.08163 15.2824 4.27875 15.2824C4.47587 15.2824 4.67106 15.2436 4.85316 15.1681C5.03526 15.0926 5.20069 14.982 5.34 14.8425L5.385 14.7975C5.56176 14.6246 5.78626 14.5086 6.02956 14.4645C6.27285 14.4204 6.52379 14.4502 6.75 14.55C6.98305 14.6357 7.18493 14.7896 7.32938 14.9915C7.47384 15.1935 7.55419 15.4343 7.56 15.6825V15.75C7.56 16.1478 7.71804 16.5294 7.99934 16.8107C8.28064 17.092 8.66218 17.25 9.06 17.25C9.45782 17.25 9.83936 17.092 10.1207 16.8107C10.402 16.5294 10.56 16.1478 10.56 15.75V15.6225C10.561 15.3812 10.6325 15.1454 10.7657 14.9442C10.899 14.7429 11.0882 14.5851 11.31 14.49C11.5362 14.3902 11.7871 14.3604 12.0304 14.4045C12.2737 14.4486 12.4982 14.5646 12.675 14.7375L12.72 14.7825C12.8593 14.922 13.0247 15.0326 13.2068 15.1081C13.3889 15.1836 13.5841 15.2224 13.7812 15.2224C13.9784 15.2224 14.1736 15.1836 14.3557 15.1081C14.5378 15.0326 14.7032 14.922 14.8425 14.7825C14.982 14.6432 15.0926 14.4778 15.1681 14.2957C15.2436 14.1136 15.2824 13.9184 15.2824 13.7213C15.2824 13.5241 15.2436 13.3289 15.1681 13.1468C15.0926 12.9647 14.982 12.7993 14.8425 12.66L14.7975 12.615C14.6246 12.4382 14.5086 12.2137 14.4645 11.9704ZM11.25 9C11.25 10.2426 10.2426 11.25 9 11.25C7.75736 11.25 6.75 10.2426 6.75 9C6.75 7.75736 7.75736 6.75 9 6.75C10.2426 6.75 11.25 7.75736 11.25 9Z",
      fill: "#343A46"
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("defs", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("clipPath", {
      id: "clip0_134_402"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("rect", {
      width: "18",
      height: "18",
      fill: "white"
    }))))
  };
  const optionsGenerator = options => {
    let options_data = [];
    const options_string = options.split("\n");
    options_string.forEach(option => {
      let [value, label] = option.split("|");
      if (!(label === "" || label === undefined)) {
        value = value.trim();
        label = label.trim();
        options_data.push({
          name: label,
          value: value
        });
      }
    });
    return options_data;
  };
  const optionStringGenerator = option => {
    let optionString = "";
    for (let i = 0; i < option.length; i++) {
      optionString += option[i].value + "|" + option[i].name + "\n";
    }
    return optionString;
  };
  const fieldSetting = setStatus => {
    const conditionFields = {
      'field': {
        'type': 'select',
        'label': wholesalex_overview.i18n.whx_form_builder_field,
        'options': {
          ...{
            '': wholesalex_overview.i18n.whx_form_builder_select_field
          },
          ...(0,_Utils__WEBPACK_IMPORTED_MODULE_6__.getRegistrationFormFieldOptions)(registrationForm, column.name)
        },
        'default': ''
      },
      'condition': {
        'type': 'select',
        'label': wholesalex_overview.i18n.whx_form_builder_condition,
        'options': {
          '': wholesalex_overview.i18n.whx_form_builder_select_condition,
          'is': wholesalex_overview.i18n.whx_form_builder_equal,
          'not_is': wholesalex_overview.i18n.whx_form_builder_not_equal
        },
        'default': ''
      },
      'value': {
        'type': 'text',
        'label': wholesalex_overview.i18n.whx_form_builder_value,
        'placeholder': wholesalex_overview.i18n.whx_form_builder_value,
        'default': ''
      }
    };
    const fieldNameHelpMessage = () => {
      if (column.hasOwnProperty("custom_field") && column.custom_field && !(column.hasOwnProperty("migratedFromOldBuilder") && column.migratedFromOldBuilder)) {
        return wholesalex_overview.i18n.whx_form_builder_custom_field_name_warning;
      }
      return '';
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-field-poup wsx-setting-popup_control",
      ref: settingRef
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, wholesalex_overview.i18n.whx_form_builder_edit_field), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-popup-close",
      onClick: () => setStatus(false)
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "20",
      height: "20",
      viewBox: "0 0 20 20",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M15 5L5 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M5 5L15 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-tab-heading"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: `whx-tab-heading ${fieldSettingActiveTab == 'setting' ? 'whx-active-tab' : ''}`,
      onClick: () => setFieldSettingActiveTab('setting')
    }, wholesalex_overview.i18n.whx_form_builder_field_settings), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: `whx-tab-heading wsx-field-condition-tab ${fieldSettingActiveTab == 'condition' ? 'whx-active-tab' : ''}`,
      onClick: () => {
        if (wholesalex.is_pro_active) {
          setFieldSettingActiveTab('condition');
        } else {
          setUpgradeProPopupStatus(true);
        }
      }
    }, wholesalex_overview.i18n.whx_form_builder_field_condition, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-form-tutorial",
      onClick: () => setShowVideo(true)
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "14",
      height: "14",
      viewBox: "0 0 14 14",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      "fill-rule": "evenodd",
      "clip-rule": "evenodd",
      d: "M7 12.25C7.68944 12.25 8.37213 12.1142 9.00909 11.8504C9.64605 11.5865 10.2248 11.1998 10.7123 10.7123C11.1998 10.2248 11.5865 9.64605 11.8504 9.00909C12.1142 8.37213 12.25 7.68944 12.25 7C12.25 6.31056 12.1142 5.62787 11.8504 4.99091C11.5865 4.35395 11.1998 3.7752 10.7123 3.28769C10.2248 2.80018 9.64605 2.41347 9.00909 2.14963C8.37213 1.8858 7.68944 1.75 7 1.75C5.60761 1.75 4.27226 2.30312 3.28769 3.28769C2.30312 4.27226 1.75 5.60761 1.75 7C1.75 8.39239 2.30312 9.72774 3.28769 10.7123C4.27226 11.6969 5.60761 12.25 7 12.25ZM6.29008 4.66083L9.58242 6.49017C9.67331 6.5407 9.74904 6.61462 9.80177 6.70426C9.85449 6.7939 9.88229 6.896 9.88229 7C9.88229 7.104 9.85449 7.2061 9.80177 7.29574C9.74904 7.38538 9.67331 7.4593 9.58242 7.50983L6.29008 9.33917C6.18348 9.39842 6.06324 9.42879 5.94129 9.42728C5.81933 9.42576 5.69989 9.3924 5.59479 9.33051C5.48969 9.26862 5.40259 9.18034 5.34211 9.07443C5.28164 8.96851 5.24988 8.84863 5.25 8.72667V5.27333C5.24988 5.15137 5.28164 5.03149 5.34211 4.92557C5.40259 4.81966 5.48969 4.73138 5.59479 4.66949C5.69989 4.6076 5.81933 4.57424 5.94129 4.57272C6.06324 4.57121 6.18348 4.60158 6.29008 4.66083Z",
      fill: "#6C6E77"
    })), wholesalex_overview.i18n.whx_form_builder_video), !wholesalex.is_pro_active && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "dashicons dashicons-lock",
      onClick: () => {
        setUpgradeProPopupStatus(true);
      }
    }))), fieldSettingActiveTab == 'setting' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wsx-form-setting wsx-header-popup_control"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-toggle-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'status',
      key: 'status',
      className: "wsx-toggle-setting",
      label: wholesalex_overview.i18n.whx_form_builder_field_status,
      value: column.status,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'status',
          value: e.target.checked
        });
      }
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'isRequired',
      key: 'isRequired',
      className: "wsx-toggle-setting wholesalex-field-setting-field-required",
      label: wholesalex_overview.i18n.whx_form_builder_required,
      value: column.required,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'required',
          value: e.target.checked
        });
      },
      disabled: column.name === 'user_confirm_pass' || column.name === 'user_confirm_email' || column.name === 'password' || column.name === 'user_email'
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'isLabelHide',
      key: 'isLabelHide',
      className: "wsx-toggle-setting wholesalex-field-setting-field-hide-label",
      label: wholesalex_overview.i18n.whx_form_builder_hide_label,
      value: column.isLabelHide,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'isLabelHide',
          value: e.target.checked
        });
      }
    })), column.name != 'wholesalex_registration_role' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-select-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
      className: "wsx-select-field-label wsx-setting-label wsx-font-12-normal",
      htmlFor: "excludeRoles"
    }, " ", wholesalex_overview.i18n.whx_form_builder_exclude_role_items), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_MultiSelect__WEBPACK_IMPORTED_MODULE_4__["default"], {
      options: wholesalex_overview.whx_form_builder_roles,
      value: column.excludeRoles || [],
      name: "excludeRoles",
      onMultiSelectChangeHandler: (fieldName, selectedValues) => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'excludeRoles',
          value: selectedValues
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_choose_roles
      // isDisable={!_isProActivate}
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: 'label',
      id: "label",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_field_label,
      type: "text",
      name: 'label',
      value: column.label,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'label',
          value: e.target.value
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_label
    })), column.name == 'user_pass' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-select-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
      className: "wsx-select-field-label wsx-setting-label wsx-font-12-normal",
      htmlFor: "passwordStrength"
    }, " ", wholesalex_overview.i18n.whx_form_builder_password_condition_label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_MultiSelect__WEBPACK_IMPORTED_MODULE_4__["default"], {
      options: wholesalex_overview.whx_form_builder_password_condition_options,
      value: column.passwordStrength || [],
      name: "password_strength",
      onMultiSelectChangeHandler: (fieldName, selectedValues) => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'passwordStrength',
          value: selectedValues
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_choose_password_strength
      // isDisable={!_isProActivate}
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap wsx-select-wrap wholesalex-field-setting-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
      className: "wsx-select-field-label wsx-setting-label wsx-font-12-normal",
      htmlFor: "passwordStrength"
    }, " ", wholesalex_overview.i18n.whx_form_builder_password_strength_message), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("textarea", {
      rows: 3,
      id: "passwordStrengthMessage",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      name: "password_strength_message",
      defaultValue: column?.password_strength_message,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'password_strength_message',
          value: e.target.value
        });
      }
    }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: 'name',
      id: "name",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_field_name,
      type: "text",
      name: 'name',
      value: column.name,
      onChange: e => {
        let newValue = e.target.value;
        newValue = newValue.replace(/\s/g, "_");
        newValue = newValue.replace(/[^\w\s]/g, "_");
        if (/^\d/.test(newValue)) {
          newValue = newValue.substring(1);
        }
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'name',
          value: newValue
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_field_name,
      disabled: !(column.hasOwnProperty("custom_field") && column.custom_field) || column.type === 'termCondition',
      help: fieldNameHelpMessage()
    })), (column.type == 'select' || column.type == 'radio' || column.type == 'checkbox') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-field-setting-row wsx-setting-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex_form_builder__settings_field"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
      className: "wholesalex_form_builder__settings_field_label wsx-setting-label wsx-font-12-normal"
    }, wholesalex_overview.i18n.whx_form_builder_options), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("textarea", {
      rows: 3,
      name: "option",
      defaultValue: optionStringGenerator(column.option),
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'option',
          value: optionsGenerator(e.target.value)
        });
      },
      readOnly: column.name == 'wholesalex_registration_role',
      disabled: column.name == 'wholesalex_registration_role'
    }), column.name === 'wholesalex_registration_role' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "wholesalex-warning"
    }, wholesalex_overview.i18n.whx_form_builder_you_cant_edit_role_selection_field))), (column.type == 'text' || column.type == 'number' || column.type == 'textarea' || column.type == 'email' || column.type == 'password') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap wholesalex-field-setting-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: 'placeholder',
      id: "placeholder",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_placeholder,
      type: "text",
      name: 'placeholder',
      value: column.placeholder,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'placeholder',
          value: e.target.value
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_placeholder
    })), column.type == 'termCondition' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap wholesalex-field-setting-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: 'termConditionLink',
      id: "termConditionLink",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_term_link,
      type: "text",
      name: 'termConditionLink',
      value: column?.term_link,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'term_link',
          value: e.target.value
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_term_link_placeholder,
      help: wholesalex_overview.i18n.whx_form_builder_term_condition_link_label_warning
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: 'termConditionLabel',
      id: "termConditionLabel",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_term_condition,
      type: "text",
      name: 'option',
      value: column.default_text,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'default_text',
          value: e.target.value
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_term_condition_placeholder,
      help: wholesalex_overview.i18n.whx_form_builder_term_condition_label_warning
    }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap wholesalex-field-setting-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: 'helpMessage',
      id: "helpMessage",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_help_message,
      type: "text",
      name: 'help_message',
      value: column?.help_message,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'help_message',
          value: e.target.value
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_help_message
    })), column.type == 'file' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-select-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
      className: "wsx-select-field-label wsx-setting-label wsx-font-12-normal",
      htmlFor: "allowed_file_types"
    }, " ", wholesalex_overview.i18n.whx_form_builder_allowed_file_types), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_MultiSelect__WEBPACK_IMPORTED_MODULE_4__["default"], {
      options: wholesalex_overview.whx_form_builder_file_condition_options,
      value: column.allowed_file_types || [],
      name: 'allowed_file_types',
      onMultiSelectChangeHandler: (fieldName, selectedValues) => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'allowed_file_types',
          value: selectedValues
        });
      },
      placeholder: wholesalex_overview.i18n.whx_form_builder_choose_file_type
      // isDisable={!_isProActivate}
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      key: `maximum_allowed_file_size`,
      id: "maximum_allowed_file_size",
      className: "wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview.i18n.whx_form_builder_maximum_allowed_file_size_in_bytes,
      type: "text",
      name: 'maximum_file_size',
      value: column?.maximum_file_size,
      onChange: e => {
        setRegistrationForm({
          type: 'updateField',
          index: parentIndex,
          columnIndex: columnIndex,
          property: 'maximum_file_size',
          value: e.target.value
        });
      }
    })))), fieldSettingActiveTab === 'condition' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wholesalex-field-conditions wsx-form-setting"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-field-condition-desc wsx-setting-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-field-condition-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `wsx-field-condition-btn ${column?.conditions?.status == 'show' ? 'wsx-is-active' : ''}`,
      onClick: () => {
        let _temp = {
          ...column
        };
        if (_temp['conditions'] == undefined) {
          _temp['conditions'] = {
            'tiers': []
          };
        }
        _temp['conditions']['status'] = 'show';
        setRegistrationForm({
          type: 'updateFieldCondition',
          parentIndex: parentIndex,
          columnIndex: columnIndex,
          value: _temp
        });
      }
    }, wholesalex_overview.i18n.whx_form_builder_show), " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `wsx-field-condition-btn ${column?.conditions?.status == 'hide' ? 'wsx-is-active' : ''}`,
      onClick: () => {
        let _temp = {
          ...column
        };
        if (_temp['conditions'] == undefined) {
          _temp['conditions'] = {
            'tiers': []
          };
        }
        _temp['conditions']['status'] = 'hide';
        setRegistrationForm({
          type: 'updateFieldCondition',
          parentIndex: parentIndex,
          columnIndex: columnIndex,
          value: _temp
        });
      }
    }, wholesalex_overview.i18n.whx_form_builder_hide, " ")), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: ""
    }, wholesalex_overview.i18n.whx_form_builder_visibility, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_9__["default"], {
      content: wholesalex_overview.i18n.whx_form_builder_visibility_tooltip
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "dashicons dashicons-editor-help wholesalex_tooltip_icon"
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-field-condition-desc wsx-setting-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-field-condition-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `wsx-field-condition-btn ${column?.conditions?.relation == 'all' ? 'wsx-is-active' : ''}`,
      onClick: () => {
        let _temp = {
          ...column
        };
        if (_temp['conditions'] == undefined) {
          _temp['conditions'] = {
            'tiers': []
          };
        }
        _temp['conditions']['relation'] = 'all';
        setRegistrationForm({
          type: 'updateFieldCondition',
          parentIndex: parentIndex,
          columnIndex: columnIndex,
          value: _temp
        });
      }
    }, wholesalex_overview.i18n.whx_form_builder_all), " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `wsx-field-condition-btn ${column?.conditions?.relation == 'any' ? 'wsx-is-active' : ''}`,
      onClick: () => {
        let _temp = {
          ...column
        };
        if (_temp['conditions'] == undefined) {
          _temp['conditions'] = {
            'tiers': []
          };
        }
        _temp['conditions']['relation'] = 'any';
        setRegistrationForm({
          type: 'updateFieldCondition',
          parentIndex: parentIndex,
          columnIndex: columnIndex,
          value: _temp
        });
      }
    }, wholesalex_overview.i18n.whx_form_builder_any)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: ""
    }, wholesalex_overview.i18n.whx_form_builder_operator, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_9__["default"], {
      content: wholesalex_overview.i18n.whx_form_builder_operator_tooltip
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "dashicons dashicons-editor-help wholesalex_tooltip_icon"
    })))), " "), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-field-conditions-tier"
    }, column.conditions && column.conditions['tiers'].length != 0 && column.conditions['tiers'].map((t, i) => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tier__WEBPACK_IMPORTED_MODULE_7__["default"], {
        fields: conditionFields,
        tier: column,
        setTier: r => {
          setRegistrationForm({
            type: 'updateFieldCondition',
            parentIndex: parentIndex,
            columnIndex: columnIndex,
            value: r
          });
        },
        tierName: 'conditions',
        index: i,
        src: "registration_form"
      });
    }), (column.conditions == undefined || column.conditions['tiers'] && column.conditions['tiers'].length == 0) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tier__WEBPACK_IMPORTED_MODULE_7__["default"], {
      fields: conditionFields,
      tier: column,
      setTier: r => {
        setRegistrationForm({
          type: 'updateFieldCondition',
          parentIndex: parentIndex,
          columnIndex: columnIndex,
          value: r
        });
      },
      tierName: 'conditions',
      index: 0,
      src: "registration_form"
    })))), (column.hasOwnProperty("custom_field") && column.custom_field || column.name === 'wholesalex_registration_role') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold wsx-field-wc-options-popup"
    }, wholesalex_overview.i18n.whx_form_builder_woocommerce_option, " ", !wholesalex.is_pro_active && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "dashicons dashicons-lock",
      onClick: () => {
        setUpgradeProPopupStatus(true);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wsx-form-setting"
    }, !(column.name === 'user_email' || column.name === 'user_pass') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-toggle-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'isAddToWooCommerceRegistration',
      key: 'isAddToWooCommerceRegistration',
      className: "wsx-toggle-setting",
      label: wholesalex_overview.i18n.whx_form_builder_add_woocommerce_registration,
      value: column.isAddToWooCommerceRegistration,
      onChange: e => {
        if (!wholesalex.is_pro_active) {
          setUpgradeProPopupStatus(true);
        } else {
          setRegistrationForm({
            type: 'updateField',
            index: parentIndex,
            columnIndex: columnIndex,
            property: 'isAddToWooCommerceRegistration',
            value: e.target.checked
          });
        }
      }
      // isDisable = {!wholesalex.is_pro_active}
      ,
      isLock: !wholesalex.is_pro_active
    })), (column.billingMapping === '' || column.billingMapping == undefined) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-setting-wrap wsx-toggle-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'enableForBillingForm',
      key: 'enableForBillingForm',
      className: "wsx-toggle-setting",
      label: wholesalex_overview.i18n.whx_form_builder_add_custom_field_to_billing,
      value: column?.enableForBillingForm,
      onChange: e => {
        if (!wholesalex.is_pro_active) {
          setUpgradeProPopupStatus(true);
        } else {
          setRegistrationForm({
            type: 'updateField',
            index: parentIndex,
            columnIndex: columnIndex,
            property: 'enableForBillingForm',
            value: e.target.checked
          });
        }
      }
      // isDisable = {!wholesalex.is_pro_active}
      ,
      isLock: !wholesalex.is_pro_active
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'isRequiredInBilling',
      key: 'isRequiredInBilling',
      className: "wsx-toggle-setting",
      label: wholesalex_overview.i18n.whx_form_builder_required_in_billing,
      value: column?.isRequiredInBilling,
      onChange: e => {
        if (!wholesalex.is_pro_active) {
          setUpgradeProPopupStatus(true);
        } else {
          setRegistrationForm({
            type: 'updateField',
            index: parentIndex,
            columnIndex: columnIndex,
            property: 'isRequiredInBilling',
            value: e.target.checked
          });
        }
      }
      // isDisable = {!wholesalex.is_pro_active}
      ,
      isLock: !wholesalex.is_pro_active
    })))));
  };
  const columnSetting = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-reg-form-row-setting"
    }, showDelete && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "move-column-icon",
      onMouseEnter: () => {
        makeDragable(true);
        colRef.current = 'column_move';
      },
      onMouseLeave: () => {
        makeDragable(false);
        colRef.current = null;
      }
    }, icons.drag), column.name != 'user_login' && column.name != 'user_email' && column.name != 'user_pass' && showDelete && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "delete-column-icon",
      onClick: () => {
        if (column?.columnPosition === 'left') {
          setRegistrationForm({
            type: 'deleteLeftColumn',
            field: column
          });
        } else if (column?.columnPosition === 'right') {
          setRegistrationForm({
            type: 'deleteRightColumn',
            field: column
          });
        }
      }
    }, icons.delete), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_2__["default"], {
      renderToogle: (status, setStatus) => {
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
          className: `field-setting-icon ${status ? 'wsx-active' : ''}`,
          onClick: () => setStatus(!status)
        }, icons.setting);
      },
      renderContent: (status, setStatus) => {
        return fieldSetting(setStatus);
      },
      className: "wsx-setting-popup_control"
    }));
  };
  // isRowActive?columnSetting:false
  const renderField = (field, columnIndex) => {
    return (0,_Utils__WEBPACK_IMPORTED_MODULE_6__.getInputFieldVariation)(registrationForm.settings.inputVariation, field, activeField == columnIndex && isRowActive ? columnSetting : false);
  };
  const videoModalContent = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex_popup_modal__addon_video_container"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("iframe", {
      className: "wholesalex_popup_modal__addon_video ",
      width: "560",
      height: "315",
      src: 'https://www.youtube.com/embed/2O99u8Ipn2Y?si=aMXoms1JUuVSDMhs&amp;start=606&amp;autoplay=1',
      frameBorder: "0",
      allow: "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen",
      title: 'Create WooCommerce Wholesale Registration Form with Conditional Advance Custom Fields',
      loading: "lazy"
    }));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-registration-form-column ${column?.columnPosition} ${!column.status ? 'wsx-disable-field' : ''} ${activeField != -1 ? 'wsx-field-active' : ''} `,
    draggable: isDragable,
    onDragStart: e => colRef.current == 'column_move' && handleColumnDragStart(e, columnIndex),
    onDragOver: e => e.preventDefault(),
    onDragEnter: e => colRef.current == 'column_move' && handleColumnDragEnter(e, columnIndex),
    onDragEnd: e => {
      e.preventDefault();
    },
    onMouseEnter: () => !settingRef?.current && setActiveField(columnIndex),
    onMouseLeave: () => !settingRef?.current && setActiveField(-1)
  }, renderField(column, columnIndex), showVideo && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_PopupModal__WEBPACK_IMPORTED_MODULE_8__["default"], {
    renderContent: videoModalContent,
    onClose: () => setShowVideo(false),
    className: "wsx-field-condition-popup"
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ColumnField);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/Editor.js":
/*!***************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/Editor.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _assets_scss_Form_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../assets/scss/Form.scss */ "./reactjs/src/assets/scss/Form.scss");
/* harmony import */ var _assets_scss_Editor_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../assets/scss/Editor.scss */ "./reactjs/src/assets/scss/Editor.scss");
/* harmony import */ var _assets_scss_Shortcode_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../assets/scss/Shortcode.scss */ "./reactjs/src/assets/scss/Shortcode.scss");
/* harmony import */ var _components_LoadingSpinner__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../components/LoadingSpinner */ "./reactjs/src/components/LoadingSpinner.js");
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../context/toastContent */ "./reactjs/src/context/toastContent.js");
/* harmony import */ var _components_Toast__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../components/Toast */ "./reactjs/src/components/Toast.js");
/* harmony import */ var _components_Slider__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../components/Slider */ "./reactjs/src/components/Slider.js");
/* harmony import */ var _ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./ColorPanenDropdown */ "./reactjs/src/pages/registration_form_builder/ColorPanenDropdown.js");
/* harmony import */ var _InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./InputWithIncrementDecrement */ "./reactjs/src/pages/registration_form_builder/InputWithIncrementDecrement.js");
/* harmony import */ var _ChooseInputStyle__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./ChooseInputStyle */ "./reactjs/src/pages/registration_form_builder/ChooseInputStyle.js");
/* harmony import */ var _RegistrationFormBuilder__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./RegistrationFormBuilder */ "./reactjs/src/pages/registration_form_builder/RegistrationFormBuilder.js");
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");
/* harmony import */ var _LoginForm__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./LoginForm */ "./reactjs/src/pages/registration_form_builder/LoginForm.js");
/* harmony import */ var _Utils__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./Utils */ "./reactjs/src/pages/registration_form_builder/Utils.js");
/* harmony import */ var _ShortcodesModal__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./ShortcodesModal */ "./reactjs/src/pages/registration_form_builder/ShortcodesModal.js");
/* harmony import */ var _PremadeDesignModal__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./PremadeDesignModal */ "./reactjs/src/pages/registration_form_builder/PremadeDesignModal.js");
/* harmony import */ var _components_Panel__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../../components/Panel */ "./reactjs/src/components/Panel.js");
/* harmony import */ var _components_Section__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../../components/Section */ "./reactjs/src/components/Section.js");
/* harmony import */ var _components_ToggleGroupControl__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ../../components/ToggleGroupControl */ "./reactjs/src/components/ToggleGroupControl.js");
/* harmony import */ var _components_PopupModal__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ../../components/PopupModal */ "./reactjs/src/components/PopupModal.js");
/* harmony import */ var _components_Button_New__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ../../components/Button_New */ "./reactjs/src/components/Button_New.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");
























function Editor() {
  const [shortcodeModalStatus, setShortcodeModalStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [premadeDesignModalStatus, setPremadeDesignModalStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [resetData, setResetData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(JSON.parse(wholesalex_overview.whx_form_builder_form_data));
  const [upgradeProPopupStatus, setUpgradeProPopupStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [appState, setAppState] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    loading: false,
    loadingOnSave: false
  });
  const [settingsData, setSettingsData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    colorfieldtype: 'signIn',
    colorfieldstate: 'normal',
    colorbuttonstate: 'normal',
    colorbuttontype: 'signIn',
    colorcontainertype: 'main',
    typographyfieldtype: 'label',
    sizeSpacingcontainertype: 'main'
  });
  const [enableSidebar, setSidebar] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const {
    dispatch
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_toastContent__WEBPACK_IMPORTED_MODULE_6__.ToastContext);
  const {
    registrationForm,
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_13__.RegistrationFormContext);
  const fetchData = async function () {
    let type = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'get';
    let attr = {
      type: type,
      action: 'builder_action',
      nonce: wholesalex.nonce
    };
    if (type === 'post') {
      attr['data'] = JSON.stringify(registrationForm);
    }
    wp.apiFetch({
      path: '/wholesalex/v1/builder_action',
      method: 'POST',
      data: attr
    }).then(res => {
      if (res.success) {
        if (type === 'get') {
          setAppState({
            ...appState,
            loading: false
          });
        } else if (type === 'post') {
          dispatch({
            type: "ADD_MESSAGE",
            payload: {
              id: Date.now().toString(),
              type: 'success',
              message: 'Successfully Saved'
            }
          });
          setAppState({
            ...appState,
            loadingOnSave: false
          });
        }
      }
    });
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (wholesalex_overview.whx_form_builder_form_data) {
      setRegistrationForm({
        type: 'init',
        value: JSON.parse(wholesalex_overview.whx_form_builder_form_data)
      });
    }
  }, []);
  const onSave = () => {
    let userLogin = 'no';
    registrationForm.registrationFields.map(items => {
      items.columns.forEach(element => {
        if (element.name == 'user_login') {
          userLogin = 'yes';
        }
      });
    });
    if (wholesalex_overview.whx_form_builder_is_woo_username == 'no' && userLogin == 'no') {
      dispatch({
        type: "ADD_MESSAGE",
        payload: {
          id: Date.now().toString(),
          delay: 4000,
          type: 'error',
          message: 'Please provide USER NAME'
        }
      });
      return;
    }
    setAppState({
      ...appState,
      loadingOnSave: true
    });
    setResetData(registrationForm);
    fetchData('post');
  };
  const onReset = () => {
    if (wholesalex_overview.whx_form_builder_form_data) {
      setRegistrationForm({
        type: 'init',
        value: resetData
      });
      dispatch({
        type: "ADD_MESSAGE",
        payload: {
          id: Date.now().toString(),
          type: 'success',
          message: wholesalex_overview.i18n.whx_form_builder_reset_successful
        }
      });
    }
  };
  const proPopupContent = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
      className: "wsx-addon-popup-image",
      src: wholesalex.url + '/assets/img/unlock.svg',
      alt: "Unlock Icon"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium wsx-mt-4"
    }, wholesalex_overview.i18n.whx_form_builder_unlock_heading, " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('with ', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-d-flex wsx-item-center wsx-justify-center wsx-gap-4 wsx-mb-16"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('WholesaleX ', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium wsx-color-primary"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Pro', 'wholesalex'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_22__["default"], {
      buttonLink: "https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=email-unlock_features-upgrade_to_pro&utm_campaign=wholesalex-DB",
      label: wholesalex_overview.i18n.whx_form_builder_upgrade_to_pro,
      background: "secondary",
      iconName: "growUp",
      customClass: "wsx-w-auto wsx-br-lg wsx-justify-center wsx-font-16"
    }));
  };
  const premadeDesignBtnIcon = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "20",
    height: "20",
    viewBox: "0 0 20 20",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M18.4186 5.76875L7.39355 1.3C7.2998 1.2625 7.21855 1.25 7.1248 1.25C6.8498 1.25 6.59355 1.4125 6.48105 1.68125L5.83105 3.29375L6.53105 3.44375L7.13105 1.95L15.1936 5.2125L16.4748 5.73125L18.1498 6.4125L14.8248 14.625L14.2623 17.3688V17.7313C14.2623 17.7688 14.2623 17.8 14.2561 17.8375C14.2811 17.7938 14.3061 17.7563 14.3248 17.7063L18.7936 6.68125C18.9436 6.325 18.7748 5.91875 18.4123 5.76875H18.4186Z",
    fill: "white"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M16.0195 5.38125L4.60078 3.04375C4.55703 3.0375 4.50703 3.03125 4.46953 3.03125C4.14453 3.03125 3.86953 3.25625 3.79453 3.575L3.42578 5.39375H4.14453L4.48203 3.73125L12.657 5.39375L14.157 5.69375L15.8633 6.04375L14.2633 13.925V17.3625L16.5445 6.18125C16.6195 5.8125 16.382 5.45 16.0133 5.375L16.0195 5.38125Z",
    fill: "white"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M13.5875 5.39374H1.93125C1.55625 5.39374 1.25 5.69999 1.25 6.07499V17.7312C1.25 18.1062 1.55625 18.4125 1.93125 18.4125H13.5875C13.9625 18.4125 14.2688 18.1062 14.2688 17.7312V6.07499C14.2688 5.69999 13.9625 5.39374 13.5875 5.39374ZM9.45625 7.68124C10.1938 7.68124 10.7938 8.28124 10.7938 9.01874C10.7938 9.75624 10.1938 10.3562 9.45625 10.3562C8.71875 10.3562 8.11875 9.75624 8.11875 9.01874C8.11875 8.28124 8.71875 7.68124 9.45625 7.68124ZM12.4688 17.0875H2.85C2.575 17.0875 2.40625 16.7812 2.55 16.55L6.05 10.9625C6.1875 10.7437 6.50625 10.7437 6.64375 10.9625L8.625 14.125C8.7625 14.3437 9.08125 14.3437 9.21875 14.125L9.93125 12.9875C10.0688 12.7687 10.3875 12.7687 10.525 12.9875L12.7563 16.55C12.9 16.7875 12.7375 17.0875 12.4563 17.0875H12.4688Z",
    fill: "white"
  }));
  const TEXT_TRANSFORMS_OPTIONS = [{
    name: wholesalex_overview.i18n.whx_form_builder_none,
    value: 'none',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M7 11.5h10V13H7z"
    }))
  }, {
    name: wholesalex_overview.i18n.whx_form_builder_uppercase,
    value: 'uppercase',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M6.1 6.8L2.1 18h1.6l1.1-3h4.3l1.1 3h1.6l-4-11.2H6.1zm-.8 6.8L7 8.9l1.7 4.7H5.3zm15.1-.7c-.4-.5-.9-.8-1.6-1 .4-.2.7-.5.8-.9.2-.4.3-.9.3-1.4 0-.9-.3-1.6-.8-2-.6-.5-1.3-.7-2.4-.7h-3.5V18h4.2c1.1 0 2-.3 2.6-.8.6-.6 1-1.4 1-2.4-.1-.8-.3-1.4-.6-1.9zm-5.7-4.7h1.8c.6 0 1.1.1 1.4.4.3.2.5.7.5 1.3 0 .6-.2 1.1-.5 1.3-.3.2-.8.4-1.4.4h-1.8V8.2zm4 8c-.4.3-.9.5-1.5.5h-2.6v-3.8h2.6c1.4 0 2 .6 2 1.9.1.6-.1 1-.5 1.4z"
    }))
  }, {
    name: wholesalex_overview.i18n.whx_form_builder_lowercase,
    value: 'lowercase',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M11 16.8c-.1-.1-.2-.3-.3-.5v-2.6c0-.9-.1-1.7-.3-2.2-.2-.5-.5-.9-.9-1.2-.4-.2-.9-.3-1.6-.3-.5 0-1 .1-1.5.2s-.9.3-1.2.6l.2 1.2c.4-.3.7-.4 1.1-.5.3-.1.7-.2 1-.2.6 0 1 .1 1.3.4.3.2.4.7.4 1.4-1.2 0-2.3.2-3.3.7s-1.4 1.1-1.4 2.1c0 .7.2 1.2.7 1.6.4.4 1 .6 1.8.6.9 0 1.7-.4 2.4-1.2.1.3.2.5.4.7.1.2.3.3.6.4.3.1.6.1 1.1.1h.1l.2-1.2h-.1c-.4.1-.6 0-.7-.1zM9.2 16c-.2.3-.5.6-.9.8-.3.1-.7.2-1.1.2-.4 0-.7-.1-.9-.3-.2-.2-.3-.5-.3-.9 0-.6.2-1 .7-1.3.5-.3 1.3-.4 2.5-.5v2zm10.6-3.9c-.3-.6-.7-1.1-1.2-1.5-.6-.4-1.2-.6-1.9-.6-.5 0-.9.1-1.4.3-.4.2-.8.5-1.1.8V6h-1.4v12h1.3l.2-1c.2.4.6.6 1 .8.4.2.9.3 1.4.3.7 0 1.2-.2 1.8-.5.5-.4 1-.9 1.3-1.5.3-.6.5-1.3.5-2.1-.1-.6-.2-1.3-.5-1.9zm-1.7 4c-.4.5-.9.8-1.6.8s-1.2-.2-1.7-.7c-.4-.5-.7-1.2-.7-2.1 0-.9.2-1.6.7-2.1.4-.5 1-.7 1.7-.7s1.2.3 1.6.8c.4.5.6 1.2.6 2s-.2 1.4-.6 2z"
    }))
  }, {
    name: wholesalex_overview.i18n.whx_form_builder_capitalize,
    value: 'capitalize',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M7.1 6.8L3.1 18h1.6l1.1-3h4.3l1.1 3h1.6l-4-11.2H7.1zm-.8 6.8L8 8.9l1.7 4.7H6.3zm14.5-1.5c-.3-.6-.7-1.1-1.2-1.5-.6-.4-1.2-.6-1.9-.6-.5 0-.9.1-1.4.3-.4.2-.8.5-1.1.8V6h-1.4v12h1.3l.2-1c.2.4.6.6 1 .8.4.2.9.3 1.4.3.7 0 1.2-.2 1.8-.5.5-.4 1-.9 1.3-1.5.3-.6.5-1.3.5-2.1-.1-.6-.2-1.3-.5-1.9zm-1.7 4c-.4.5-.9.8-1.6.8s-1.2-.2-1.7-.7c-.4-.5-.7-1.2-.7-2.1 0-.9.2-1.6.7-2.1.4-.5 1-.7 1.7-.7s1.2.3 1.6.8c.4.5.6 1.2.6 2 .1.8-.2 1.4-.6 2z"
    }))
  }];
  const WEIGHT_OPTIONS = {
    100: '100',
    200: '200',
    300: '300',
    400: '400',
    500: '500',
    600: '600',
    700: '700',
    800: '800',
    900: '900'
  };
  const createToggleSelectorMain = (options, defaultVal) => ({
    type: 'toggleSelectorMain',
    options,
    default: defaultVal
  });
  const createToggleSelectorState = function (options, defaultVal) {
    let title = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    return {
      type: 'toggleSelectorState',
      options,
      default: defaultVal,
      title
    };
  };
  const createToggleSelectorAlignment = function (options, defaultVal) {
    let title = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    return {
      type: 'toggleSelectorAlignment',
      options,
      default: defaultVal,
      title
    };
  };
  const createColorSelector = (options, defaults, dependsOptions) => ({
    type: 'colorSelector',
    options,
    default: defaults,
    dependsOptions
  });
  const createNumberWithIncrementDecrement = (label, meta, defaultVal, depends) => ({
    type: 'numberWIthIncrementDecrement',
    label,
    meta,
    default: defaultVal,
    depends
  });
  const createSelect = (label, options, defaultVal) => ({
    type: 'select',
    label,
    options,
    default: defaultVal
  });
  const createTransformSelect = (label, options, defaultVal) => ({
    type: 'transformSelect',
    label,
    options,
    default: defaultVal
  });
  const createPanel = (label, children) => ({
    type: 'panel',
    label,
    children
  });
  const createSection = (label, children) => ({
    type: 'section',
    label,
    children
  });
  const fields = {
    color: createSection(wholesalex_overview.i18n.whx_form_builder_color_settings, {
      field: createPanel(wholesalex_overview.i18n.whx_form_builder_field, {
        type: createToggleSelectorMain({
          'signIn': wholesalex_overview.i18n.whx_form_builder_sign_in,
          'signUp': wholesalex_overview.i18n.whx_form_builder_sign_up
        }, 'signIn'),
        state: createToggleSelectorState({
          'normal': wholesalex_overview.i18n.whx_form_builder_normal,
          'active': wholesalex_overview.i18n.whx_form_builder_active,
          'warning': wholesalex_overview.i18n.whx_form_builder_warning
        }, 'normal', wholesalex_overview.i18n.whx_form_builder_state),
        colors: createColorSelector([{
          label: wholesalex_overview.i18n.whx_form_builder_label,
          value: 'label',
          depends: []
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_input_text,
          value: 'text',
          depends: []
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_background,
          value: 'background',
          depends: []
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_border,
          value: 'border',
          depends: []
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_placeholder,
          value: 'placeholder',
          depends: [{
            key: 'colorfieldstate',
            value: 'normal',
            operator: 'equal'
          }]
        }], {
          'label': '',
          'text': '',
          'background': '',
          'border': ''
        })
      }),
      button: createPanel(wholesalex_overview.i18n.whx_form_builder_button, {
        type: createToggleSelectorMain({
          'signIn': wholesalex_overview.i18n.whx_form_builder_sign_in,
          'signUp': wholesalex_overview.i18n.whx_form_builder_sign_up
        }, 'signIn'),
        state: createToggleSelectorState({
          'normal': wholesalex_overview.i18n.whx_form_builder_normal,
          'hover': wholesalex_overview.i18n.whx_form_builder_hover
        }, 'normal', wholesalex_overview.i18n.whx_form_builder_state),
        colors: createColorSelector([{
          label: wholesalex_overview.i18n.whx_form_builder_text,
          value: 'text'
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_background,
          value: 'background'
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_border,
          value: 'border'
        }], {
          'text': '',
          'background': '',
          'border': ''
        })
      }),
      container: createPanel(wholesalex_overview.i18n.whx_form_builder_container, {
        type: createToggleSelectorMain({
          'main': wholesalex_overview.i18n.whx_form_builder_main,
          'signIn': wholesalex_overview.i18n.whx_form_builder_sign_in,
          'signUp': wholesalex_overview.i18n.whx_form_builder_sign_up
        }, 'signIn'),
        colors: createColorSelector([{
          label: wholesalex_overview.i18n.whx_form_builder_background,
          value: 'background'
        }, {
          label: wholesalex_overview.i18n.whx_form_builder_border,
          value: 'border'
        }], {
          'text': '',
          'background': '',
          'border': ''
        })
      })
    }),
    typography: createSection(wholesalex_overview.i18n.whx_form_builder_typography_settings, {
      field: createPanel(wholesalex_overview.i18n.whx_form_builder_field, {
        type: createToggleSelectorMain({
          'label': wholesalex_overview.i18n.whx_form_builder_label,
          'input': wholesalex_overview.i18n.whx_form_builder_input
        }, 'label'),
        size: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_size, 'unit: px', 14),
        weight: createSelect(wholesalex_overview.i18n.whx_form_builder_weight, WEIGHT_OPTIONS, 500),
        transform: createTransformSelect(wholesalex_overview.i18n.whx_form_builder_transform, TEXT_TRANSFORMS_OPTIONS, 'none')
      }),
      button: createPanel(wholesalex_overview.i18n.whx_form_builder_button, {
        size: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_size, 'unit: px', 14),
        weight: createSelect(wholesalex_overview.i18n.whx_form_builder_weight, WEIGHT_OPTIONS, 500),
        transform: createTransformSelect(wholesalex_overview.i18n.whx_form_builder_transform, TEXT_TRANSFORMS_OPTIONS, 'none')
      })
    }),
    sizeSpacing: createSection(wholesalex_overview.i18n.whx_form_builder_size_and_spacing_settings, {
      input: createPanel(wholesalex_overview.i18n.whx_form_builder_input, {
        padding: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_padding, 'unit: px', 14),
        width: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_max_width, 'unit: %', 395),
        border: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_border, 'unit: px', 1),
        borderRadius: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_border_radius, 'unit: px', 1)
      }),
      button: createPanel(wholesalex_overview.i18n.whx_form_builder_button, {
        padding: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_padding, 'unit: px', 14),
        width: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_width, 'unit: %', 50),
        align: createToggleSelectorAlignment({
          'left': wholesalex_overview.i18n.whx_form_builder_left,
          'center': wholesalex_overview.i18n.whx_form_builder_center,
          'right': wholesalex_overview.i18n.whx_form_builder_right
        }, 'left', wholesalex_overview.i18n.whx_form_builder_alignment),
        border: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_border, 'unit: px', 1),
        borderRadius: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_border_radius, 'unit: px', 2)
      }),
      container: createPanel(wholesalex_overview.i18n.whx_form_builder_container, {
        type: createToggleSelectorMain({
          'main': wholesalex_overview.i18n.whx_form_builder_main,
          'signIn': wholesalex_overview.i18n.whx_form_builder_sign_in,
          'signUp': wholesalex_overview.i18n.whx_form_builder_sign_up
        }, 'signIn'),
        width: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_max_width, 'unit: px', 2),
        border: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_border, 'unit: px', 2),
        borderRadius: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_border_radius, 'unit: px', 16),
        padding: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_padding, 'unit: px', 0),
        separator: createNumberWithIncrementDecrement(wholesalex_overview.i18n.whx_form_builder_separator, 'unit: px', 16, [{
          key: 'sizeSpacingcontainertype',
          value: 'main',
          operator: 'equal'
        }])
      })
    })
  };
  const updateFieldStyle = (sectionName, panelName, loginOrSignUp, stateName, property, value) => {
    setRegistrationForm({
      type: 'setStyle',
      sectionName,
      loginOrSignUp,
      stateName,
      panelName,
      stateName,
      property,
      value
    });
  };
  const getFieldStyle = (sectionName, panelName, loginOrSignUp, stateName, property) => {
    let val = '';
    if (stateName && loginOrSignUp) {
      val = registrationForm?.style?.[sectionName]?.[panelName]?.[loginOrSignUp]?.[stateName]?.[property];
    } else if (loginOrSignUp && !stateName) {
      val = registrationForm?.style?.[sectionName]?.[panelName]?.[loginOrSignUp]?.[property];
    } else if (!loginOrSignUp && !stateName) {
      val = registrationForm?.style?.[sectionName]?.[panelName]?.[property];
    }
    return val;
  };
  const getSettingFields = () => {
    const checkDepends = depends => {
      if (!depends || depends.length == 0) {
        return true;
      }
      let status = false;
      depends.forEach(depend => {
        let key = depend['key'];
        let value = depend['value'];
        let operator = depend['operator'];
        if (operator == 'equal') {
          status = settingsData[key] == value;
        } else if (operator == 'not_equal') {
          status = settingsData[key] != value;
        }
      });
      return status;
    };
    return Object.keys(fields).map(_section => {
      const section = fields[_section];
      return section && section.type == 'section' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Section__WEBPACK_IMPORTED_MODULE_19__["default"], {
        title: section['label']
      }, section['children'] && Object.keys(section['children']).map(_sectionChild => {
        const sectionChildren = section['children'][_sectionChild];
        return sectionChildren.type == 'panel' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Panel__WEBPACK_IMPORTED_MODULE_18__["default"], {
          title: sectionChildren['label']
        }, Object.keys(sectionChildren['children']).map(_child => {
          let field = sectionChildren['children'][_child];
          const key = _section + _sectionChild + _child;
          const defVal = settingsData[key] ? settingsData[key] : field['default'];
          let loginOrSignupKey = _section + _sectionChild + 'type';
          let stateKey = _section + _sectionChild + 'state';
          switch (field['type']) {
            case 'colorSelector':
              return field['options'].map(colorField => {
                return checkDepends(colorField.depends) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_9__["default"], {
                  label: colorField['label'],
                  value: getFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], colorField.value),
                  onChange: val => {
                    updateFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], colorField.value, val);
                  }
                });
              });
              break;
            case 'toggleSelectorMain':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_ToggleGroupControl__WEBPACK_IMPORTED_MODULE_20__["default"], {
                options: field.options,
                className: 'wsx-control-tab',
                value: defVal,
                onChange: val => {
                  setSettingsData({
                    ...settingsData,
                    [key]: val
                  });
                }
              });
              break;
            case 'toggleSelectorState':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_ToggleGroupControl__WEBPACK_IMPORTED_MODULE_20__["default"], {
                title: field.title,
                className: 'wsx-control-state wsx-flex_space-between_center',
                options: field.options,
                value: defVal,
                onChange: val => {
                  setSettingsData({
                    ...settingsData,
                    [key]: val
                  });
                }
              });
              break;
            case 'toggleSelectorAlignment':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_ToggleGroupControl__WEBPACK_IMPORTED_MODULE_20__["default"], {
                title: field.title,
                className: 'wsx-control-alignment wsx-flex_space-between_center',
                options: field.options,
                value: getFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child),
                onChange: val => {
                  updateFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child, val);
                }
              });
              break;
            case 'numberWIthIncrementDecrement':
              return checkDepends(field.depends) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_10__["default"], {
                label: field.label,
                className: 'wsx-control-increment',
                value: getFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child),
                onChange: val => {
                  updateFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child, val);
                },
                labelMeta: field.meta
              });
              break;
            case 'select':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                className: "wholesalex-select-field wholesalex-style-formatting-field"
              }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
              }, field.label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("select", {
                value: getFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child),
                onChange: e => {
                  updateFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child, e.target.value);
                },
                className: "wholesalex-style-formatting-field-content"
              }, Object.keys(field.options).map(option => {
                return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("option", {
                  value: option
                }, field.options[option]);
              })));
              break;
            case 'transformSelect':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                className: 'wholesalex-text-transform-control wholesalex-style-formatting-field'
              }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
              }, field.label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
                className: "wholesalex-text-transform-control__buttons wholesalex-style-formatting-field-content"
              }, field.options.map(textTransform => {
                return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
                  className: `wholesalex-text-transform-control__button wsx-font-12-normal ${textTransform.value === getFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child) ? 'is-pressed' : ''}`,
                  key: textTransform.value,
                  onClick: () => {
                    updateFieldStyle(_section, _sectionChild, settingsData[loginOrSignupKey], settingsData[stateKey], _child, textTransform.value);
                  }
                }, textTransform.icon);
              })));
            default:
              break;
          }
        }));
      }));
    });
  };
  const handleGoBack = () => {
    window.history.back();
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder__form"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-header wsx-flex_space-between_center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-header__left wsx-d-flex wsx-item-center wsx-gap-12"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_22__["default"]
  // label={wholesalex_overview.i18n.whx_form_builder_go_back}
  , {
    iconName: "arrowLeft_24",
    padding: "8px",
    onClick: handleGoBack,
    background: "primary"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-form-builder-header__heading"
  }, wholesalex_overview.i18n.whx_form_builder_form_builder)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-header__right"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_22__["default"], {
    onClick: onReset,
    label: wholesalex_overview.i18n.whx_form_builder_reset,
    iconName: "reset",
    background: "base2",
    iconPosition: "after"
    // smallButton={true}
    ,
    borderColor: "border-tertiary"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_22__["default"], {
    onClick: onSave,
    label: wholesalex_overview.i18n.whx_form_builder_save_form_changes,
    background: "primary"
    // smallButton={true}
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_22__["default"], {
    onClick: e => {
      setShortcodeModalStatus(true);
    },
    label: wholesalex_overview.i18n.whx_form_builder_get_shortcodes,
    iconName: "clipboard",
    background: "base2",
    iconPosition: "after"
    // smallButton={true}
    ,
    borderColor: "border-tertiary"
  }))), appState.loadingOnSave && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_LoadingSpinner__WEBPACK_IMPORTED_MODULE_5__["default"], null), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-form-builder"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-wrapper",
    style: (0,_Utils__WEBPACK_IMPORTED_MODULE_15__.getFormStyle)(registrationForm?.style, registrationForm?.loginFormHeader?.styles, registrationForm?.registrationFormHeader?.styles)
  }, registrationForm?.settings?.isShowLoginForm && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_LoginForm__WEBPACK_IMPORTED_MODULE_14__["default"], null), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-form-separator"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_RegistrationFormBuilder__WEBPACK_IMPORTED_MODULE_12__["default"], {
    upgradeProPopupStatus: upgradeProPopupStatus,
    setUpgradeProPopupStatus: setUpgradeProPopupStatus
  }))))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-sidebar-container ${enableSidebar ? '' : 'wsx-sidebar-hide'}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-form-builder__typography-setting`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-form-control_hide",
    onClick: () => setSidebar(!enableSidebar)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "dashicons dashicons-arrow-left-alt2"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-typography-setting-header"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-form-builder__typography-setting-heading"
  }, wholesalex_overview.i18n.whx_form_builder_styling_n_formatting)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-style-formatting-controller"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "premade-section"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button", {
    className: "wholesalex-premade-btn",
    onClick: () => setPremadeDesignModalStatus(!premadeDesignModalStatus)
  }, premadeDesignBtnIcon, " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-premade-btn-label"
  }, " ", wholesalex_overview.i18n.whx_form_builder_premade_design))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "typography-section"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "typography-field"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_8__["default"], {
    name: 'isShowFormTitle',
    key: 'isShowFormTitle',
    className: "wholesalex-field-setting-field-required",
    label: wholesalex_overview.i18n.whx_form_builder_form_title,
    value: registrationForm.settings.isShowFormTitle,
    onChange: e => {
      setRegistrationForm({
        type: 'toogleFormTitle',
        value: e.target.checked
      });
    }
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "typography-field"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_8__["default"], {
    name: 'isShowLoginForm',
    key: 'isShowLoginForm',
    className: "wholesalex-field-setting-field-required",
    label: wholesalex_overview.i18n.whx_form_builder_show_login_form,
    value: registrationForm.settings.isShowLoginForm,
    onChange: e => {
      setRegistrationForm({
        type: 'toogleShowLoginFormStatus',
        value: e.target.checked
      });
    }
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ChooseInputStyle__WEBPACK_IMPORTED_MODULE_11__["default"], null), getSettingFields()))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Toast__WEBPACK_IMPORTED_MODULE_7__["default"], {
    position: 'top_right',
    delay: 5000
  }), shortcodeModalStatus && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ShortcodesModal__WEBPACK_IMPORTED_MODULE_16__["default"], {
    setModalStatus: setShortcodeModalStatus,
    roles: wholesalex_overview.whx_form_builder_roles
  }), premadeDesignModalStatus && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_PremadeDesignModal__WEBPACK_IMPORTED_MODULE_17__["default"], {
    setModalStatus: setPremadeDesignModalStatus
  }), upgradeProPopupStatus && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_PopupModal__WEBPACK_IMPORTED_MODULE_21__["default"], {
    className: "wsx-pro-modal",
    renderContent: proPopupContent,
    onClose: () => setUpgradeProPopupStatus(false)
  }));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Editor);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/FieldInserter.js":
/*!**********************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/FieldInserter.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FormBuilderDropdown */ "./reactjs/src/pages/registration_form_builder/FormBuilderDropdown.js");
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../context/toastContent */ "./reactjs/src/context/toastContent.js");




const FieldInserter = _ref => {
  let {
    type,
    index,
    setUpgradeProPopupStatus
  } = _ref;
  const {
    dispatch
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_toastContent__WEBPACK_IMPORTED_MODULE_3__.ToastContext);
  const {
    registrationForm,
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_2__.RegistrationFormContext);

  // Memoize the getUsedFieldsName function to prevent unnecessary recalculations
  const getUsedFieldsName = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    let _fieldsName = [];
    registrationForm['registrationFields'] && registrationForm['registrationFields'].forEach(row => {
      row['columns'].forEach(_field => {
        _fieldsName.push(_field.name);
      });
    });
    return _fieldsName;
  }, [registrationForm]);
  const defaultFields = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => ({
    'first_name': 'First Name',
    'last_name': 'Last Name',
    'user_email': 'Email',
    'user_login': 'Username',
    'user_pass': 'Password',
    'user_confirm_pass': 'Confirm Password',
    'description': 'User Bio',
    'nickname': 'Nickname',
    'wholesalex_registration_role': 'Select Role',
    'display_name': 'Display Name',
    'url': 'Website',
    'user_confirm_email': 'Confirm Email',
    'wholesalex_term_condition': 'Term and Condition'

    // 'privacy_policy_text': 'Privacy Policy'
  }), []);
  const extraFields = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => ({
    'text': 'Text',
    'textarea': 'Text Area',
    'radio': 'Radio',
    'checkbox': 'Checkbox',
    'file': 'File',
    'select': 'Select',
    'email': 'Email',
    'number': 'Number',
    'date': 'Date'
  }), []);
  const usedFieldNames = getUsedFieldsName;
  const getFieldType = name => {
    switch (name) {
      case 'first_name':
      case 'last_name':
      case 'nickname':
      case 'display_name':
      case 'text_field':
      case 'user_login':
        return 'text';
      case 'user_email':
      case 'user_confirm_email':
      case 'email':
        return 'email';
      case 'user_pass':
      case 'user_confirm_pass':
        return 'password';
      case 'url':
        return 'url';
      case 'description':
        return 'textarea';
      case 'wholesalex_registration_role':
        return 'select';
      case 'wholesalex_term_condition':
        return 'termCondition';
      // case 'privacy_policy_text':
      //     return 'privacy_policy_text';

      default:
        break;
    }
  };
  const addField = (fieldType, fieldLabel, fieldName, isCustomField) => {
    let _row = {};
    switch (type) {
      case 'newFieldRow':
        setRegistrationForm({
          type: 'newFieldRow',
          field: {
            type: fieldType,
            label: fieldLabel,
            name: fieldName,
            isCustomField: isCustomField
          }
        });
        break;
      case 'insertFieldOnColumn':
        setRegistrationForm({
          type: 'insertFieldOnColumn',
          field: {
            type: fieldType,
            label: fieldLabel,
            name: fieldName,
            isCustomField: isCustomField
          },
          index: index
        });
        break;
      case 'insertFieldOnRightColumn':
        setRegistrationForm({
          type: 'insertFieldOnRightColumn',
          field: {
            type: fieldType,
            label: fieldLabel,
            name: fieldName,
            isCustomField: isCustomField
          },
          index: index
        });
        break;
      case 'insertFieldOnLeftColumn':
        setRegistrationForm({
          type: 'insertFieldOnLeftColumn',
          field: {
            type: fieldType,
            label: fieldLabel,
            name: fieldName,
            isCustomField: isCustomField
          },
          index: index
        });
        break;
      default:
        break;
    }
  };
  const fieldsPopup = setStatus => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-field-poup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, wholesalex_overview.i18n.whx_form_builder_default_fields), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-popup-close",
      onClick: () => setStatus(false)
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "20",
      height: "20",
      viewBox: "0 0 20 20",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M15 5L5 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M5 5L15 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("ul", null, Object.keys(defaultFields).map(fieldKey => {
      const _isDisable = usedFieldNames.includes(fieldKey);
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("li", {
        className: `wsx-insert-field-btn ${_isDisable ? 'wholesalex-disabled' : ''}`,
        onClick: e => {
          if (!_isDisable) {
            addField(getFieldType(fieldKey), defaultFields[fieldKey], fieldKey, false);
            setStatus(false);
          } else {
            dispatch({
              type: "ADD_MESSAGE",
              payload: {
                id: Date.now().toString(),
                type: 'error',
                message: wholesalex_overview.i18n.whx_form_builder_already_used
              }
            });
          }
        }
      }, defaultFields[fieldKey]);
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold wsx-extra-field-popup"
    }, wholesalex_overview.i18n.whx_form_builder_extra_fields, " ", !wholesalex.is_pro_active && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "dashicons dashicons-lock",
      onClick: () => {
        setUpgradeProPopupStatus(true);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("ul", null, Object.keys(extraFields).map(fieldKey => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("li", {
        className: `wsx-insert-field-btn ${!wholesalex.is_pro_active ? 'wholesalex-disabled' : ''}`,
        onClick: e => {
          if (wholesalex.is_pro_active) {
            addField(fieldKey, extraFields[fieldKey], fieldKey, true);
            setStatus(false);
          } else {
            setUpgradeProPopupStatus(true);
          }
        }
      }, extraFields[fieldKey]);
    })))));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-form-builder-field-inserter"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_1__["default"], {
    renderToogle: (status, setStatus) => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: `wholesalex-field-insert-btn dashicons dashicons-plus-alt2 ${status ? 'is-active wsx-active' : ''}`,
        onClick: () => setStatus(!status)
      });
    },
    renderContent: (status, setStatus) => {
      return fieldsPopup(setStatus);
    },
    placement: "bottom",
    contentClassName: "wsx-field-insert-popup"
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FieldInserter);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/FontWeightSelect.js":
/*!*************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/FontWeightSelect.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const FontWeightSelect = _ref => {
  let {
    label,
    getValue,
    setValue
  } = _ref;
  const options = {
    100: '100',
    200: '200',
    300: '300',
    400: '400',
    500: '500',
    600: '600',
    700: '700',
    800: '800',
    900: '900'
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-select-field wholesalex-style-formatting-field"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
  }, label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("select", {
    value: getValue(),
    onChange: e => {
      setValue(e.target.value);
    },
    className: "wholesalex-style-formatting-field-content"
  }, Object.keys(options).map(option => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("option", {
      value: option
    }, options[option]);
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FontWeightSelect);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/FormBuilderDropdown.js":
/*!****************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/FormBuilderDropdown.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);


function FormBuilderDropdown(_ref) {
  let {
    renderToogle,
    renderContent,
    className,
    contentClassName = '',
    placement = 'bottom-start',
    offset = 15
  } = _ref;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.Dropdown, {
    className: `wholesalex-form-builder-dropdown ${className}`,
    contentClassName: `wholesalex-form-builder-dropdown-content ${contentClassName}`,
    popoverProps: {
      animate: true,
      placement: placement,
      shift: true,
      resize: true,
      offset: offset
    },
    renderToggle: _ref2 => {
      let {
        onToggle,
        isOpen
      } = _ref2;
      return renderToogle(isOpen, onToggle);
    },
    renderContent: _ref3 => {
      let {
        isOpen,
        onToggle
      } = _ref3;
      return renderContent(isOpen, onToggle);
    }
  });
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FormBuilderDropdown);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/InputWithIncrementDecrement.js":
/*!************************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/InputWithIncrementDecrement.js ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const InputWithIncrementDecrement = _ref => {
  let {
    label,
    className,
    value,
    onChange,
    getValue,
    setValue,
    labelMeta
  } = _ref;
  const decrementSVG = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "14",
    height: "14",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M2.91699 7H11.0837",
    stroke: "#6C6E77",
    "stroke-width": "1.125",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }));
  const incrementSVG = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "14",
    height: "14",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M7 2.91666V11.0833",
    stroke: "#6C6E77",
    "stroke-width": "1.125",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M2.91699 7H11.0837",
    stroke: "#6C6E77",
    "stroke-width": "1.125",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }));
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-style-formatting-field ${className}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
  }, label, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-style-formatting-field-label-meta wsx-font-12-normal"
  }, labelMeta ? '(' + labelMeta + ')' : '(unit: px)')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-style-formatting-field-content`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "decrement",
    onClick: () => {
      // setValue(parseInt(getValue()) - 1);
      onChange(parseInt(value) - 1);
    }
  }, decrementSVG), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "number",
    value: value,
    onChange: e => {
      let val = e.target.value;
      // setValue(parseInt(val));
      onChange(parseInt(val));
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "increment",
    onClick: () => {
      // setValue(parseInt(getValue()) + 1);
      onChange(parseInt(value) + 1);
    }
  }, incrementSVG)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (InputWithIncrementDecrement);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/LoginForm.js":
/*!******************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/LoginForm.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");
/* harmony import */ var _FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FormBuilderDropdown */ "./reactjs/src/pages/registration_form_builder/FormBuilderDropdown.js");
/* harmony import */ var _components_Slider__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/Slider */ "./reactjs/src/components/Slider.js");
/* harmony import */ var _components_Input__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../components/Input */ "./reactjs/src/components/Input.js");
/* harmony import */ var _InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./InputWithIncrementDecrement */ "./reactjs/src/pages/registration_form_builder/InputWithIncrementDecrement.js");
/* harmony import */ var _FontWeightSelect__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./FontWeightSelect */ "./reactjs/src/pages/registration_form_builder/FontWeightSelect.js");
/* harmony import */ var _TextTransformControl__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./TextTransformControl */ "./reactjs/src/pages/registration_form_builder/TextTransformControl.js");
/* harmony import */ var _ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./ColorPanenDropdown */ "./reactjs/src/pages/registration_form_builder/ColorPanenDropdown.js");
/* harmony import */ var _Utils__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./Utils */ "./reactjs/src/pages/registration_form_builder/Utils.js");










const LoginForm = () => {
  const {
    registrationForm,
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_1__.RegistrationFormContext);
  const [isActive, setActive] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [loginFields, setLoginFields] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(JSON.parse(wholesalex_overview.whx_form_builder_login_form_data));
  const colorPickerRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const renderRows = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-login-fields wsx-fields-container"
    }, loginFields && loginFields?.map((row, index) => {
      return (0,_Utils__WEBPACK_IMPORTED_MODULE_9__.getInputFieldVariation)(registrationForm?.settings?.inputVariation, row['columns'][0]);
    }));
  };
  const formTitleSettingPopupContent = setStatus => {
    const getTitleStyle = property => {
      return registrationForm?.loginFormHeader?.styles?.title[property];
    };
    const setTitleStyle = (property, value) => {
      setRegistrationForm({
        type: 'updateFormHeadingStyle',
        formType: 'Login',
        styleFor: 'title',
        property: property,
        value: value
      });
    };
    const getDescriptionStyle = property => {
      return registrationForm?.loginFormHeader?.styles?.description[property];
    };
    const setDescriptionStyle = (property, value) => {
      setRegistrationForm({
        type: 'updateFormHeadingStyle',
        formType: 'Login',
        styleFor: 'description',
        property: property,
        value: value
      });
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold"
    }, wholesalex_overview?.i18n?.form_title_setting, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-popup-close",
      onClick: () => setStatus(false)
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "20",
      height: "20",
      viewBox: "0 0 20 20",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M15 5L5 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M5 5L15 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-field-poup wsx-header-popup_control"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wsx-header-popup_control wsx-form-setting"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-setting-row wsx-setting-wrap wsx-toggle-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_3__["default"], {
      name: 'hideDescription',
      key: 'hideDescription',
      className: "wholesalex-form-description-status-field wholesalex_slider_field wsx-toggle-setting",
      label: wholesalex_overview?.i18n?.hide_form_description,
      value: registrationForm?.loginFormHeader?.isHideDescription,
      onChange: e => {
        setRegistrationForm({
          type: 'toogleLoginFormDescriptionStatus',
          value: e?.target?.checked
        });
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-setting-row wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_4__["default"], {
      key: 'formTitle',
      name: 'formTitle',
      className: "wholesalex-form-title-field wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview?.i18n?.title,
      type: "text",
      value: registrationForm?.loginFormHeader?.title,
      onChange: e => {
        setRegistrationForm({
          type: 'updateLoginFormTitle',
          value: e?.target?.value
        });
      },
      placeholder: wholesalex_overview?.i18n?.title
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-setting-row wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_4__["default"], {
      key: 'formDescription',
      name: 'formDescription',
      className: "wholesalex-form-title-field wholesalex_form_builder__settings_field wsx-input-setting",
      label: wholesalex_overview?.i18n?.description,
      type: "text",
      value: registrationForm?.loginFormHeader?.description,
      onChange: e => {
        setRegistrationForm({
          type: 'updateLoginFormDescription',
          value: e?.target?.value
        });
      },
      placeholder: wholesalex_overview?.i18n?.description
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-popup-last wsx-font-18-lightBold"
    }, wholesalex_overview?.i18n?.style), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wholesalex-form-title-style"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column-heading wsx-font-14-normal"
    }, wholesalex_overview?.i18n?.title), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: 'wholesalex-form-title-font-size wholesalex-size-selector',
      label: wholesalex_overview?.i18n?.font_size,
      value: getTitleStyle('size'),
      onChange: val => {
        setTitleStyle('size', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row "
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FontWeightSelect__WEBPACK_IMPORTED_MODULE_6__["default"], {
      label: wholesalex_overview?.i18n?.font_weight,
      getValue: () => getTitleStyle('weight'),
      setValue: val => {
        setTitleStyle('weight', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_TextTransformControl__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: wholesalex_overview?.i18n?.font_case,
      getValue: () => getTitleStyle('transform'),
      setValue: val => setTitleStyle('transform', val)
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
    }, "Form Title Color"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_8__["default"], {
      colorPickerRef: colorPickerRef,
      className: 'wholesalex-style-formatting-field-content wholesalex-outside-click-whitelist',
      value: getTitleStyle('color'),
      onChange: val => setTitleStyle('color', val)
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column-heading wsx-font-14-normal"
    }, wholesalex_overview?.i18n?.description), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: 'wholesalex-form-title-font-size wholesalex-size-selector',
      label: wholesalex_overview?.i18n?.font_size,
      value: getDescriptionStyle('size'),
      onChange: val => {
        setDescriptionStyle('size', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FontWeightSelect__WEBPACK_IMPORTED_MODULE_6__["default"], {
      label: wholesalex_overview?.i18n?.font_weight,
      getValue: () => getDescriptionStyle('weight'),
      setValue: val => {
        setDescriptionStyle('weight', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_TextTransformControl__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: wholesalex_overview?.i18n?.font_case,
      getValue: () => getDescriptionStyle('transform'),
      setValue: val => setDescriptionStyle('transform', val)
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
    }, "Form Title Color"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_8__["default"], {
      colorPickerRef: colorPickerRef,
      className: 'wholesalex-style-formatting-field-content wholesalex-outside-click-whitelist',
      value: getDescriptionStyle('color'),
      onChange: val => setDescriptionStyle('color', val),
      placement: "right-start"
    }))))))), " ");
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-login-form"
  }, registrationForm?.settings?.isShowFormTitle && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-login-form-title ${isActive ? 'wsx-row-active' : ''}`,
    onMouseEnter: () => setActive(true),
    onMouseLeave: () => setActive(false)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-login-form-title-text wsx-editable"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "text",
    className: "wsx-editable-area",
    value: registrationForm?.loginFormHeader?.title,
    onChange: e => {
      setRegistrationForm({
        type: 'updateLoginFormTitle',
        value: e.target.value.replace(/["']/g, "")
      });
    }
  }), isActive && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-editable-edit-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M8.16667 1.16666L10.5 3.5L4.08333 9.91666H1.75V7.58333L8.16667 1.16666Z",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M1.75 12.8333H12.25",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  })))), !registrationForm?.loginFormHeader?.isHideDescription && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-login-form-subtitle-text wsx-editable"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "text",
    className: "wsx-editable-area",
    value: registrationForm?.loginFormHeader?.description,
    onChange: e => {
      setRegistrationForm({
        type: 'updateLoginFormDescription',
        value: e.target.value.replace(/["']/g, "")
      });
    }
  }), isActive && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-editable-edit-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M8.16667 1.16666L10.5 3.5L4.08333 9.91666H1.75V7.58333L8.16667 1.16666Z",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M1.75 12.8333H12.25",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_2__["default"], {
    renderToogle: (status, setStatus) => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: `dashicons dashicons-admin-generic ${status ? 'is-active' : ''}`,
        onClick: () => setStatus(!status)
      });
    },
    renderContent: (status, setStatus) => {
      return formTitleSettingPopupContent(setStatus);
    },
    className: "wsx-header-popup_control"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-fields-wrapper wsx_${registrationForm.settings.inputVariation}`
  }, renderRows()), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-form-btn-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button", {
    contentEditable: true,
    className: `button wsx-login-btn ${registrationForm['styles']?.layout?.button?.align} wsx-editable`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "text",
    className: "wsx-editable-area",
    value: registrationForm?.loginFormButton?.title,
    onChange: e => {
      setRegistrationForm({
        type: 'updateLoginButtonText',
        value: e.target.value.replace(/["']/g, "")
      });
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-editable-edit-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M8.16667 1.16666L10.5 3.5L4.08333 9.91666H1.75V7.58333L8.16667 1.16666Z",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M1.75 12.8333H12.25",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LoginForm);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/PremadeDesignModal.js":
/*!***************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/PremadeDesignModal.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_OutsideClick__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../components/OutsideClick */ "./reactjs/src/components/OutsideClick.js");
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");



const PremadeDesignModal = _ref => {
  let {
    setModalStatus
  } = _ref;
  const {
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_2__.RegistrationFormContext);
  const deleteIcon = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M18 6L6 18",
    stroke: "#343A46",
    "stroke-width": "1.5",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M6 6L18 18",
    stroke: "#343A46",
    "stroke-width": "1.5",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }));
  const options = ['premade_1', 'premade_2', 'premade_3', 'premade_4', 'premade_5', 'premade_6', 'premade_7', 'premade_8', 'premade_9'];
  const getImageUrl = option => {
    return wholesalex.url + 'assets/img/premade-forms/' + option + '.svg';
  };
  const dropdownRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  (0,_components_OutsideClick__WEBPACK_IMPORTED_MODULE_1__["default"])(dropdownRef, e => {
    setModalStatus(false);
  });
  const setPremade = premade => {
    setRegistrationForm({
      type: 'setPremade',
      premade: premade
    });
    setModalStatus(false);
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: dropdownRef,
    className: "wsx-reg-premade"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-reg-premade_container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-reg-premade_heading wsx-flex_space-between_center"
  }, wholesalex_overview.i18n.whx_form_builder_premade_heading, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-reg-premade_close",
    onClick: () => setModalStatus(false)
  }, deleteIcon)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-reg-premade__body"
  }, options.map((option, idx) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-premade-item"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-premade-media"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
      src: getImageUrl(option),
      alt: "wsx premade design"
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-premade-content wsx-font-18-lightBold wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, 'Template #' + (idx + 1)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-premade-action"
    }, idx == 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      onClick: () => setPremade(option),
      className: "wsx-premade-template wsx-font-14-normal"
    }, wholesalex_overview.i18n.whx_form_builder_use_design), idx != 0 && !wholesalex.is_pro_active && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
      target: "_blank",
      className: "wsx-font-14-normal wsx-premade-upgrade",
      href: "https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=email-unlock_features-upgrade_to_pro&utm_campaign=wholesalex-DB"
    }, wholesalex_overview.i18n.whx_form_builder_upgrade_to_pro), idx != 0 && wholesalex.is_pro_active && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      onClick: () => setPremade(option),
      className: "wsx-premade-template wsx-font-14-normal"
    }, wholesalex_overview.i18n.whx_form_builder_use_design))));
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PremadeDesignModal);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/RegistrationFormBuilder.js":
/*!********************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/RegistrationFormBuilder.js ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _FieldInserter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FieldInserter */ "./reactjs/src/pages/registration_form_builder/FieldInserter.js");
/* harmony import */ var _RowField__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./RowField */ "./reactjs/src/pages/registration_form_builder/RowField.js");
/* harmony import */ var _FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./FormBuilderDropdown */ "./reactjs/src/pages/registration_form_builder/FormBuilderDropdown.js");
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");
/* harmony import */ var _components_Slider__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../components/Slider */ "./reactjs/src/components/Slider.js");
/* harmony import */ var _components_Input__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../components/Input */ "./reactjs/src/components/Input.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./InputWithIncrementDecrement */ "./reactjs/src/pages/registration_form_builder/InputWithIncrementDecrement.js");
/* harmony import */ var _FontWeightSelect__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./FontWeightSelect */ "./reactjs/src/pages/registration_form_builder/FontWeightSelect.js");
/* harmony import */ var _TextTransformControl__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./TextTransformControl */ "./reactjs/src/pages/registration_form_builder/TextTransformControl.js");
/* harmony import */ var _ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./ColorPanenDropdown */ "./reactjs/src/pages/registration_form_builder/ColorPanenDropdown.js");













const RegistrationFormBuilder = _ref => {
  let {
    upgradeProPopupStatus,
    setUpgradeProPopupStatus
  } = _ref;
  const {
    registrationForm,
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_4__.RegistrationFormContext);
  const [isActive, setActive] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const headingRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const draggingItem = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const dragOverItem = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const rowRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const colorPickerRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const [activeRow, setActiveRow] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(-1);
  const handleDragStart = (e, position) => {
    draggingItem.current = position;
  };
  const handleDragEnter = (e, position) => {
    if (position === draggingItem.current) {
      return;
    }
    dragOverItem.current = position;
    let _fields = [...registrationForm['registrationFields']];
    const draggingItemContent = _fields[draggingItem.current];
    _fields.splice(draggingItem.current, 1);
    _fields.splice(dragOverItem.current, 0, draggingItemContent);
    draggingItem.current = dragOverItem.current;
    dragOverItem.current = null;
    setRegistrationForm({
      type: 'updateFullState',
      updatedState: _fields
    });
  };
  const settingRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const renderRows = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: `wsx-reg-fields wsx-fields-container`
    }, registrationForm['registrationFields'] && registrationForm['registrationFields'].map((row, index) => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_RowField__WEBPACK_IMPORTED_MODULE_2__["default"], {
        row: row,
        index: index,
        handleDragEnter: handleDragEnter,
        handleDragStart: handleDragStart,
        rowRef: rowRef,
        activeRow: activeRow,
        setActiveRow: setActiveRow,
        settingRef: settingRef,
        setUpgradeProPopupStatus: setUpgradeProPopupStatus
      });
    }));
  };
  const formTitleSettingPopupContent = setStatus => {
    const getTitleStyle = property => {
      return registrationForm?.registrationFormHeader?.styles?.title[property];
    };
    const setTitleStyle = (property, value) => {
      setRegistrationForm({
        type: 'updateFormHeadingStyle',
        formType: 'Registration',
        styleFor: 'title',
        property: property,
        value: value
      });
    };
    const getDescriptionStyle = property => {
      return registrationForm?.registrationFormHeader?.styles?.description[property];
    };
    const setDescriptionStyle = (property, value) => {
      setRegistrationForm({
        type: 'updateFormHeadingStyle',
        formType: 'Registration',
        styleFor: 'description',
        property: property,
        value: value
      });
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold"
    }, wholesalex_overview?.i18n?.form_title_setting, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-popup-close",
      onClick: () => setStatus(false)
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "20",
      height: "20",
      viewBox: "0 0 20 20",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M15 5L5 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M5 5L15 15",
      stroke: "#343A46",
      "stroke-width": "1.5",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-field-poup wsx-header-popup_control",
      ref: headingRef
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wsx-form-setting wsx-header-popup_control"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-setting-row wsx-setting-wrap wsx-toggle-wrap wsx-flex_space-between_center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_5__["default"], {
      name: 'hideDescription',
      key: 'hideDescription',
      className: "wholesalex-form-description-status-field wsx-toggle-setting",
      label: wholesalex_overview?.i18n?.hide_form_description,
      value: registrationForm?.registrationFormHeader?.isHideDescription,
      onChange: e => {
        setRegistrationForm({
          type: 'toogleRegistrationFormDescriptionStatus',
          value: e?.target?.checked
        });
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-setting-row wsx-setting-wrap wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_6__["default"], {
      key: 'formTitle',
      name: 'formTitle',
      className: "wholesalex-form-title-field wsx-input-setting",
      label: wholesalex_overview?.i18n?.title,
      type: "text",
      value: registrationForm?.registrationFormHeader?.title,
      onChange: e => {
        setRegistrationForm({
          type: 'updateRegistrationFormTitle',
          value: e?.target?.value
        });
      },
      placeholder: wholesalex_overview?.i18n?.title
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-setting-row wsx-input-wrap"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_6__["default"], {
      key: 'formDescription',
      name: 'formDescription',
      className: "wholesalex-form-title-field wsx-input-setting",
      label: wholesalex_overview?.i18n?.description,
      type: "text",
      value: registrationForm?.registrationFormHeader?.description,
      onChange: e => {
        setRegistrationForm({
          type: 'updateRegistrationFormDescription',
          value: e?.target?.value
        });
      },
      placeholder: wholesalex_overview?.i18n?.description
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-builder-popup"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-heading wsx-font-18-lightBold wsx-popup-last"
    }, wholesalex_overview?.i18n?.style), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-popup-content wholesalex-form-title-style wsx-form-title-setting"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column-heading wsx-font-14-normal"
    }, wholesalex_overview?.i18n?.title), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_8__["default"], {
      className: 'wholesalex-form-title-font-size wholesalex-size-selector',
      label: wholesalex_overview?.i18n?.font_size,
      value: getTitleStyle('size'),
      onChange: val => {
        setTitleStyle('size', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row "
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FontWeightSelect__WEBPACK_IMPORTED_MODULE_9__["default"], {
      label: wholesalex_overview?.i18n?.font_weight,
      getValue: () => getTitleStyle('weight'),
      setValue: val => {
        setTitleStyle('weight', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_TextTransformControl__WEBPACK_IMPORTED_MODULE_10__["default"], {
      label: wholesalex_overview?.i18n?.font_case,
      getValue: () => getTitleStyle('transform'),
      setValue: val => setTitleStyle('transform', val)
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
    }, "Color"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_11__["default"], {
      colorPickerRef: colorPickerRef,
      className: 'wholesalex-style-formatting-field-content wholesalex-outside-click-whitelist',
      value: getTitleStyle('color'),
      onChange: val => setTitleStyle('color', val)
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-column-heading wsx-font-14-normal"
    }, wholesalex_overview?.i18n?.description), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_InputWithIncrementDecrement__WEBPACK_IMPORTED_MODULE_8__["default"], {
      className: 'wholesalex-form-title-font-size wholesalex-size-selector',
      label: wholesalex_overview?.i18n?.font_size,
      value: getDescriptionStyle('size'),
      onChange: val => {
        setDescriptionStyle('size', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FontWeightSelect__WEBPACK_IMPORTED_MODULE_9__["default"], {
      label: wholesalex_overview?.i18n?.font_weight,
      getValue: () => getDescriptionStyle('weight'),
      setValue: val => {
        setDescriptionStyle('weight', val);
      }
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_TextTransformControl__WEBPACK_IMPORTED_MODULE_10__["default"], {
      label: wholesalex_overview?.i18n?.font_case,
      getValue: () => getDescriptionStyle('transform'),
      setValue: val => setDescriptionStyle('transform', val)
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-form-title-style-row"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
    }, "Color"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ColorPanenDropdown__WEBPACK_IMPORTED_MODULE_11__["default"], {
      colorPickerRef: colorPickerRef,
      className: 'wholesalex-style-formatting-field-content wholesalex-outside-click-whitelist',
      value: getDescriptionStyle('color'),
      onChange: val => setDescriptionStyle('color', val),
      placement: "right-start"
    }))))))));
  };
  const isFormEmpty = () => {
    let status = false;
    if (registrationForm['registrationFields']) {
      if (registrationForm['registrationFields'].length == 0) {
        status = true;
      } else if (registrationForm['registrationFields'].length == 1) {
        status = registrationForm['registrationFields'][0]['columns'].length == 0;
      }
    } else {
      status = true;
    }
    return status;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-registration-form ${isFormEmpty() ? '' : 'wsx-not-empty'}`
  }, registrationForm?.settings?.isShowFormTitle && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-reg-form-heading ${isActive ? 'wsx-row-active' : ''}`,
    onMouseEnter: () => !headingRef?.current && setActive(true),
    onMouseLeave: () => !headingRef?.current && setActive(false)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-reg-form-heading-text wsx-editable"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "text",
    className: "wsx-editable-area",
    value: registrationForm?.registrationFormHeader?.title,
    onChange: e => {
      setRegistrationForm({
        type: 'updateRegistrationFormTitle',
        value: e.target.value.replace(/["']/g, "")
      });
    }
  }), isActive && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-editable-edit-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M8.16667 1.16666L10.5 3.5L4.08333 9.91666H1.75V7.58333L8.16667 1.16666Z",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M1.75 12.8333H12.25",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  })))), !registrationForm?.registrationFormHeader?.isHideDescription && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-registration-form-subtitle-text wsx-editable"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "text",
    className: "wsx-editable-area",
    value: registrationForm?.registrationFormHeader?.description,
    onChange: e => {
      setRegistrationForm({
        type: 'updateRegistrationFormDescription',
        value: e.target.value.replace(/["']/g, "")
      });
    }
  }), isActive && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-editable-edit-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M8.16667 1.16666L10.5 3.5L4.08333 9.91666H1.75V7.58333L8.16667 1.16666Z",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M1.75 12.8333H12.25",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FormBuilderDropdown__WEBPACK_IMPORTED_MODULE_3__["default"], {
    renderToogle: (status, setStatus) => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: `dashicons dashicons-admin-generic ${status ? 'is-active wsx-active' : ''}`,
        onClick: () => setStatus(!status)
      });
    },
    renderContent: (status, setStatus) => {
      return formTitleSettingPopupContent(setStatus);
    },
    whitelistRef: colorPickerRef,
    className: "wsx-header-popup_control",
    placement: "bottom",
    offset: 80
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex-fields-wrapper wsx_${registrationForm.settings.inputVariation}`
  }, renderRows()), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FieldInserter__WEBPACK_IMPORTED_MODULE_1__["default"], {
    type: 'newFieldRow',
    setUpgradeProPopupStatus: setUpgradeProPopupStatus
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-form-btn-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button", {
    className: `button wholesalex-btn wsx-register-btn wsx-editable ${registrationForm['styles']?.layout?.button?.align}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "text",
    className: "wsx-editable-area",
    value: registrationForm?.registrationFormButton?.title,
    onChange: e => {
      setRegistrationForm({
        type: 'updateRegistrationButtonText',
        value: e.target.value.replace(/["']/g, "")
      });
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-editable-edit-icon"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "12",
    height: "12",
    viewBox: "0 0 14 14",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M8.16667 1.16666L10.5 3.5L4.08333 9.91666H1.75V7.58333L8.16667 1.16666Z",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M1.75 12.8333H12.25",
    stroke: "white",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (RegistrationFormBuilder);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/RowField.js":
/*!*****************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/RowField.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _ColumnField__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ColumnField */ "./reactjs/src/pages/registration_form_builder/ColumnField.js");
/* harmony import */ var _FieldInserter__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FieldInserter */ "./reactjs/src/pages/registration_form_builder/FieldInserter.js");
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");




const RowField = _ref => {
  let {
    row,
    index,
    handleDragEnter,
    handleDragStart,
    rowRef,
    activeRow,
    setActiveRow,
    settingRef,
    setUpgradeProPopupStatus
  } = _ref;
  const {
    setRegistrationForm
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_3__.RegistrationFormContext);
  const icons = {
    drag: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "18",
      height: "18",
      viewBox: "0 0 18 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M4.14341 6.85839L2 9.0018L4.14341 11.1452M6.85839 4.14341L9.0018 2L11.1452 4.14341M11.1452 13.9316L9.0018 16.075L6.85839 13.9316M13.9316 6.85839L16.075 9.0018L13.9316 11.1452M2.78592 9.0018H15.2177M9.0018 2.71447V15.2891",
      stroke: "#343A46",
      "stroke-width": "1.3",
      "stroke-linecap": "round",
      "stroke-linejoin": "round"
    })),
    delete: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "18",
      height: "18",
      viewBox: "0 0 18 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M6.76247 2.42324C6.86385 2.03514 7.18817 1.76758 7.5572 1.76758H10.4429C10.812 1.76758 11.1363 2.03514 11.2377 2.42324L11.4855 3.37197C11.5869 3.76006 11.9112 4.02762 12.2802 4.02762H14.1659C14.5082 4.02762 14.7858 4.33118 14.7858 4.70564C14.7858 5.08009 14.5082 5.38365 14.1659 5.38365H3.83425C3.49189 5.38365 3.21436 5.08009 3.21436 4.70564C3.21436 4.33118 3.49189 4.02762 3.83425 4.02762H5.7199C6.08893 4.02762 6.41325 3.76006 6.51463 3.37197L6.76247 2.42324Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M4.04089 6.28767H8.5868V13.0678C8.5868 13.3174 8.77182 13.5198 9.00007 13.5198C9.22832 13.5198 9.41333 13.3174 9.41333 13.0678V6.28767H13.9593L12.9144 14.6681C12.8027 15.564 12.1032 16.2319 11.2765 16.2319H6.72363C5.89697 16.2319 5.19742 15.564 5.08573 14.6681L4.04089 6.28767Z",
      fill: "#343A46"
    })),
    singleColumn: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "18",
      height: "18",
      viewBox: "0 0 18 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M1 1H17V4H1V1Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M1 7.5H17V10.5H1V7.5Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M1 14H17V17H1V14Z",
      fill: "#343A46"
    })),
    doubleColumn: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "30",
      height: "18",
      viewBox: "0 0 30 18",
      fill: "none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M1 1H13V4H1V1Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M1 7.5H13V10.5H1V7.5Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M1 14H13V17H1V14Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M17 1H29V4H17V1Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M17 7.5H29V10.5H17V7.5Z",
      fill: "#343A46"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
      d: "M17 14H29V17H17V14Z",
      fill: "#343A46"
    }))
  };
  const [isDragable, makeDragable] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const colRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const rowSetting = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-reg-form-row-setting"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "move-row-icon",
      onMouseEnter: () => {
        makeDragable(true);
        rowRef.current = 'row_move';
      },
      onMouseLeave: () => {
        makeDragable(false);
        rowRef.current = null;
      }
    }, icons.drag), row.columns[0].name != 'user_login' && row.columns[0].name != 'user_email' && row.columns[0].name != 'user_pass' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `delete-row-icon`,
      onClick: () => {
        setRegistrationForm({
          type: 'deleteRow',
          index: index
        });
      }
    }, icons.delete), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `single-column-icon ${row?.isMultiColumn ? '' : 'is-active'}`,
      onClick: () => {
        setRegistrationForm({
          type: 'makeOneColumn',
          index: index
        });
      }
    }, icons.singleColumn), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: `double-column-icon ${row?.isMultiColumn ? 'is-active' : ''}`,
      onClick: () => {
        setRegistrationForm({
          type: 'makeTwoColumn',
          index: index
        });
      }
    }, icons.doubleColumn));
  };
  const isLeftColumnExist = () => {
    let status = false;
    row['columns'].forEach(element => {
      status = element?.columnPosition == 'left';
    });
    return status;
  };
  const isRightColumnExist = () => {
    let status = false;
    row['columns'].forEach(element => {
      status = element?.columnPosition == 'right';
    });
    return status;
  };
  const draggingColumnItem = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const dragOverColumnItem = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const handleColumnDragStart = (e, position) => {
    // 
    // e.dataTransfer.setData('text/plain', position);
    // e.dataTransfer.setDragImage(e.target, window.outerWidth, window.outerHeight);

    draggingColumnItem.current = position;
  };
  const handleColumnDragEnter = (e, position) => {
    if (position === draggingColumnItem.current) {
      return;
    }
    dragOverColumnItem.current = position;
    let _fields = [...row['columns']];
    const draggingColumnItemContent = _fields[draggingColumnItem.current];
    _fields.splice(draggingColumnItem.current, 1);
    _fields.splice(dragOverColumnItem.current, 0, draggingColumnItemContent);
    draggingColumnItem.current = dragOverColumnItem.current;
    dragOverColumnItem.current = null;
    setRegistrationForm({
      type: 'updateColumns',
      index: index,
      updatedColumns: _fields
    });
  };
  return (row['columns'].length || !row['columns'].length && row?.isMultiColumn) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-reg-form-row-wrapper ${activeRow == index ? 'wsx-row-active' : ''}`,
    draggable: isDragable,
    onDragStart: e => rowRef.current == 'row_move' && handleDragStart(e, index),
    onDragOver: e => e.preventDefault(),
    onDragEnter: e => rowRef.current == 'row_move' && handleDragEnter(e, index),
    onDragEnd: e => {
      e.preventDefault();
    },
    onMouseEnter: () => !settingRef.current && setActiveRow(index),
    onMouseLeave: () => !settingRef.current && setActiveRow(-1)
  }, activeRow == index && rowSetting(), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-reg-form-row ${row?.isMultiColumn ? 'double-column' : ''}`
  }, row['columns'].length < 2 && !isLeftColumnExist() && row?.isMultiColumn && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-registration-form-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FieldInserter__WEBPACK_IMPORTED_MODULE_2__["default"], {
    type: 'insertFieldOnLeftColumn',
    index: index
  })), row['columns'].map((column, idx) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_ColumnField__WEBPACK_IMPORTED_MODULE_1__["default"], {
      showDelete: row['columns'].length == 2,
      column: column,
      handleColumnDragEnter: handleColumnDragEnter,
      handleColumnDragStart: handleColumnDragStart,
      parentIndex: index,
      columnIndex: idx,
      colRef: colRef,
      isRowActive: activeRow == index,
      settingRef: settingRef,
      setUpgradeProPopupStatus: setUpgradeProPopupStatus
    });
  }), row['columns'].length < 2 && !isRightColumnExist() && row?.isMultiColumn && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex-registration-form-column-spacer"
  }), "  ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex-registration-form-column wsx-empty-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_FieldInserter__WEBPACK_IMPORTED_MODULE_2__["default"], {
    type: 'insertFieldOnRightColumn',
    index: index
  })), " ")));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (RowField);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/ShortcodesModal.js":
/*!************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/ShortcodesModal.js ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _components_Tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../components/Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _assets_scss_ShortcodesModal_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../assets/scss/ShortcodesModal.scss */ "./reactjs/src/assets/scss/ShortcodesModal.scss");
/* harmony import */ var _components_PopupModal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../components/PopupModal */ "./reactjs/src/components/PopupModal.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");






const ShortcodesModal = _ref => {
  let {
    setModalStatus,
    roles
  } = _ref;
  const [loginShortcodeStatus, setLoginShortcodeStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [shortcodes, setShortcodes] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const getShortcode = shortcode => {
    if (wholesalex_overview?.whx_form_builder_whitelabel_enabled && wholesalex_overview?.whx_form_builder_slug) {
      shortcode = shortcode.replaceAll('wholesalex_', `${wholesalex_overview.whx_form_builder_slug}_`);
    }
    return shortcode;
  };
  const deleteIcon = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    width: "24",
    height: "24",
    viewBox: "0 0 24 24",
    fill: "none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M18 6L6 18",
    stroke: "#343A46",
    "stroke-width": "1.5",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
    d: "M6 6L18 18",
    stroke: "#343A46",
    "stroke-width": "1.5",
    "stroke-linecap": "round",
    "stroke-linejoin": "round"
  }));
  const popupContent = () => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-title wsx-font-20 wsx-pb-20"
  }, wholesalex_overview.i18n.whx_form_builder_specific_shortcode_list), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-container wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-item wsx-d-grid wsx-item-center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-for wsx-font-16 wsx-color-text-medium"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Global', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-relative wsx-d-flex wsx-item-center wsx-gap-8 wsx-w-fit"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "checkbox",
    id: `short_code_for_login_global`,
    name: `short_code_for_login_global`,
    value: loginShortcodeStatus['global'],
    onChange: e => {
      let _status = !loginShortcodeStatus['global'];
      setLoginShortcodeStatus({
        ...loginShortcodeStatus,
        ['global']: _status
      });
      let _shortcode = `[wholesalex_registration]`;
      if (_status) {
        _shortcode = `[wholesalex_login_registration]`;
      }
      _shortcode = getShortcode(_shortcode);
      setShortcodes({
        ...shortcodes,
        ['global']: _shortcode
      });
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-checkbox-mark"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    htmlFor: 'short_code_for_login_global',
    className: "wsx-checkbox-option-label wsx-curser-pointer"
  }, wholesalex_overview.i18n.whx_form_builder_with_login_form)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-item-center wsx-gap-12"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: wholesalex_overview.i18n.whx_form_builder_regi_form_with_all_roles
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].help), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    className: "wsx-font-12",
    type: "text",
    "data-role": "global",
    value: shortcodes['global'] ? getShortcode(shortcodes['global']) : getShortcode(`[wholesalex_registration]`),
    onFocus: e => e.target.select(),
    onClick: e => e.target.select(),
    readOnly: true
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon",
    onClick: () => {
      let _element = `[data-role="global"]`;
      let _shortcode = document.querySelector(_element);
      if (navigator.clipboard) {
        _shortcode = _shortcode.value;
        navigator.clipboard.writeText(_shortcode);
      } else {
        try {
          _shortcode.focus();
          document.execCommand("copy");
        } catch (err) {}
      }
    }
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].copy))))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-item wsx-d-grid wsx-item-center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-for wsx-font-16 wsx-color-text-medium"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Login Form', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    htmlFor: 'short_code_for_login',
    className: "wsx-checkbox-option-label"
  }, wholesalex_overview.i18n.whx_form_builder_only_login_form), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-item-center wsx-gap-12"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: wholesalex_overview.i18n.whx_form_builder_login_form_with_all_roles
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].help), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    className: "wsx-font-12",
    type: "text",
    "data-role": "global_login",
    value: shortcodes['global_login'] ? getShortcode(shortcodes['global_login']) : getShortcode(`[wholesalex_login]`),
    onFocus: e => e.target.select(),
    onClick: e => e.target.select(),
    readOnly: true
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon",
    onClick: () => {
      let _element = `[data-role="global_login"]`;
      let _shortcode = document.querySelector(_element);
      if (navigator.clipboard) {
        _shortcode = _shortcode.value;
        navigator.clipboard.writeText(_shortcode);
      } else {
        try {
          _shortcode.focus();
          document.execCommand("copy");
        } catch (err) {}
      }
    }
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].copy))))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-item wsx-d-grid wsx-item-center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode-for wsx-font-16 wsx-color-text-medium"
  }, wholesalex_overview.i18n.whx_form_builder_b2b_global_form), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-relative wsx-d-flex wsx-item-center wsx-gap-8 wsx-w-fit"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    id: `short_code_for_login_global_b2b`,
    name: `short_code_for_login_global_b2b`,
    type: "checkbox",
    value: loginShortcodeStatus['global_b2b'],
    onChange: e => {
      let _status = !loginShortcodeStatus['global_b2b'];
      setLoginShortcodeStatus({
        ...loginShortcodeStatus,
        ['global_b2b']: _status
      });
      let _shortcode = `[wholesalex_registration registration_role="all_b2b"]`;
      if (_status) {
        _shortcode = `[wholesalex_login_registration registration_role="all_b2b"]`;
      }
      _shortcode = getShortcode(_shortcode);
      setShortcodes({
        ...shortcodes,
        ['global_b2b']: _shortcode
      });
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-checkbox-mark"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    htmlFor: 'short_code_for_login_global_b2b',
    className: "wsx-checkbox-option-label wsx-curser-pointer"
  }, wholesalex_overview.i18n.whx_form_builder_with_login_form)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-item-center wsx-gap-12"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: wholesalex_overview.i18n.whx_form_builder_regi_form_with_all_roles
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].help), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-shortcode"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    className: "wsx-font-12",
    type: "text",
    "data-role": "global_b2b",
    value: shortcodes['global_b2b'] ? getShortcode(shortcodes['global_b2b']) : getShortcode(`[wholesalex_registration registration_role="all_b2b"]`),
    onFocus: e => e.target.select(),
    onClick: e => e.target.select(),
    readOnly: true
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
    content: wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon",
    onClick: () => {
      let _element = `[data-role="global_b2b"]`;
      let _shortcode = document.querySelector(_element);
      if (navigator.clipboard) {
        _shortcode = _shortcode.value;
        navigator.clipboard.writeText(_shortcode);
      } else {
        try {
          _shortcode.focus();
          document.execCommand("copy");
        } catch (err) {}
      }
    }
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].copy))))), roles.map((role, i) => {
    return role['value'] !== "wholesalex_guest" && role.value && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-shortcode-item wsx-d-grid wsx-item-center"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-shortcode-for wsx-font-16 wsx-color-text-medium"
    }, role.name), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-relative wsx-d-flex wsx-item-center wsx-gap-8 wsx-w-fit"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
      id: `short_code_for_login_${i}`,
      name: `short_code_for_login_${i}`,
      type: "checkbox",
      value: loginShortcodeStatus[role.value],
      onChange: e => {
        let _status = !loginShortcodeStatus[role.value];
        setLoginShortcodeStatus({
          ...loginShortcodeStatus,
          [role.value]: _status
        });
        let _shortcode = `[wholesalex_registration registration_role="${role.value}"]`;
        if (_status) {
          _shortcode = `[wholesalex_login_registration registration_role="${role.value}"]`;
        }
        _shortcode = getShortcode(_shortcode);
        setShortcodes({
          ...shortcodes,
          [role.value]: _shortcode
        });
      }
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-checkbox-mark"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
      htmlFor: `short_code_for_login_${i}`,
      className: "wsx-checkbox-option-label wsx-curser-pointer"
    }, wholesalex_overview.i18n.whx_form_builder_with_login_form)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-d-flex wsx-item-center wsx-gap-12"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
      content: `${wholesalex_overview.i18n.whx_form_builder_regi_form_only_for} ${role.name} ${wholesalex_overview.i18n.whx_form_builder_role}`
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].help), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-shortcode"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
      className: "wsx-font-12",
      type: "text",
      "data-role": getShortcode(role.value),
      "date-before-copy": "Copy To Clipboard",
      "date-copied": "Copied!",
      value: shortcodes[role.value] ? getShortcode(shortcodes[role.value]) : getShortcode(`[wholesalex_registration registration_role="${role.value}"]`),
      onFocus: e => e.target.select(),
      onClick: e => e.target.select(),
      readOnly: true
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
      content: wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-icon",
      onClick: e => {
        let _element = `[data-role="${role.value}"]`;
        let _shortcode = document.querySelector(_element);
        if (navigator.clipboard) {
          _shortcode = _shortcode.value;
          navigator.clipboard.writeText(_shortcode);
        } else {
          try {
            _shortcode.focus();
            document.execCommand("copy");
          } catch (err) {}
        }
      }
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_5__["default"].copy)))));
  })));
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, setModalStatus && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_PopupModal__WEBPACK_IMPORTED_MODULE_4__["default"], {
    renderContent: popupContent,
    onClose: () => setModalStatus(false),
    wrapperClass: "wsx-width-90v wsx-width-1230 wsx-shortcode-wrapper"
  }));
  // return (
  // 	<div className="wsx-reg-premade form-builder-modal">
  // 		<div className="wsx-reg-premade_container">
  // 			<div className="wsx-reg-premade_heading wsx-flex_space-between_center">
  // 				{wholesalex_overview.i18n.whx_form_builder_specific_shortcode_list}
  // 				<span className="wsx-reg-premade_close" onClick={() => setModalStatus(false)}>{deleteIcon}</span>
  // 			</div>
  // 			<div className="modal-content">
  // 				<ul>
  // 					<li>
  // 						<div className="shortcode-for">
  // 							{__('Global', 'wholesalex')}
  // 						</div>
  // 						<div class="wsx-relative wsx-d-flex wsx-item-center wsx-gap-8">
  // 							<input
  // 								type="checkbox"
  // 								id={`short_code_for_login_global`}
  // 								name={`short_code_for_login_global`}
  // 								value={loginShortcodeStatus['global']}
  // 								onChange={(e) => {
  // 									let _status = !loginShortcodeStatus['global'];
  // 									setLoginShortcodeStatus({ ...loginShortcodeStatus, ['global']: _status });
  // 									let _shortcode = `[wholesalex_registration]`;
  // 									if (_status) {
  // 										_shortcode = `[wholesalex_login_registration]`;
  // 									}
  // 									_shortcode = getShortcode(_shortcode);
  // 									setShortcodes({ ...shortcodes, ['global']: _shortcode });
  // 								}}
  // 							/>
  // 							<div class="wsx-checkbox-mark"></div>
  // 							<label htmlFor={'short_code_for_login_global'} className='wsx-checkbox-option-label'>{wholesalex_overview.i18n.whx_form_builder_with_login_form}</label>
  // 						</div>
  // 						{/* <label htmlFor={'short_code_for_login_global'} class="wsx-checkbox-container">
  // 							<input
  // 								type="checkbox"
  // 								id={`short_code_for_login_global`}
  // 								name={`short_code_for_login_global`}
  // 								value={loginShortcodeStatus['global']}
  // 								onChange={(e) => {
  // 									let _status = !loginShortcodeStatus['global'];
  // 									setLoginShortcodeStatus({ ...loginShortcodeStatus, ['global']: _status });
  // 									let _shortcode = `[wholesalex_registration]`;
  // 									if (_status) {
  // 										_shortcode = `[wholesalex_login_registration]`;
  // 									}
  // 									_shortcode = getShortcode(_shortcode);
  // 									setShortcodes({ ...shortcodes, ['global']: _shortcode });
  // 								}}
  // 							/>
  // 							<span class="wsx-checkbox-mark"></span>
  // 							{wholesalex_overview.i18n.whx_form_builder_with_login_form}
  // 						</label> */}
  // 						{/* <div className='wsx-checkbox-option-wrapper'>
  // 							<input 
  // 								type="checkbox" 
  // 								id={`short_code_for_login_global`}
  // 								name={`short_code_for_login_global`}
  // 								value={loginShortcodeStatus['global']}
  // 								onChange={(e) => {
  // 									let _status = !loginShortcodeStatus['global'];
  // 									setLoginShortcodeStatus({ ...loginShortcodeStatus, ['global']: _status });
  // 									let _shortcode = `[wholesalex_registration]`;
  // 									if (_status) {
  // 										_shortcode = `[wholesalex_login_registration]`;
  // 									}
  // 									_shortcode = getShortcode(_shortcode);
  // 									setShortcodes({ ...shortcodes, ['global']: _shortcode });
  // 								}}
  // 								// defaultChecked={value.includes(option)} 
  // 							/>
  // 							<label htmlFor={'short_code_for_login_global'} className='wsx-checkbox-option-label'>{wholesalex_overview.i18n.whx_form_builder_with_login_form}</label>
  // 						</div> */}
  // 						{/* <div className="wholesalex-shortcode-for-login"> */}
  // 						{/* <input
  // 								id={`short_code_for_login_global`}
  // 								name={`short_code_for_login_global`}
  // 								type="checkbox"
  // 								value={loginShortcodeStatus['global']}
  // 								onChange={(e) => {
  // 									let _status = !loginShortcodeStatus['global'];
  // 									setLoginShortcodeStatus({ ...loginShortcodeStatus, ['global']: _status });
  // 									let _shortcode = `[wholesalex_registration]`;
  // 									if (_status) {
  // 										_shortcode = `[wholesalex_login_registration]`;
  // 									}
  // 									_shortcode = getShortcode(_shortcode);
  // 									setShortcodes({ ...shortcodes, ['global']: _shortcode });
  // 								}}
  // 							/> */}
  // 						{/* <label htmlFor={'short_code_for_login_global'}>{wholesalex_overview.i18n.whx_form_builder_with_login_form}</label> */}

  // 						{/* </div> */}

  // 						<div className="shortcode-content">
  // 							<div className="shortcode-tooltip">
  // 								<Tooltip content={wholesalex_overview.i18n.whx_form_builder_regi_form_with_all_roles} ><span className="tooltip-icon dashicons dashicons-editor-help" /></Tooltip>
  // 							</div>
  // 							<div className="shortcode">
  // 								<input type="text" data-role="global" value={shortcodes['global'] ? getShortcode(shortcodes['global']) : getShortcode(`[wholesalex_registration]`)} onFocus={e => e.target.select()} onClick={e => e.target.select()} readOnly />
  // 								<Tooltip content={wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard}>
  // 									<span className="shortcode-icon dashicons dashicons-clipboard"
  // 										onClick={() => {
  // 											let _element = `[data-role="global"]`;
  // 											let _shortcode = document.querySelector(_element);
  // 											if (navigator.clipboard) {
  // 												_shortcode = _shortcode.value;
  // 												navigator.clipboard.writeText(_shortcode);
  // 											} else {
  // 												try {
  // 													_shortcode.focus();
  // 													document.execCommand("copy");

  // 												} catch (err) { }
  // 											}
  // 										}} />
  // 								</Tooltip>
  // 							</div>
  // 						</div>
  // 					</li>
  // 					<li>
  // 						<div className="shortcode-for">
  // 							{__('Login Form', 'wholesalex')}
  // 						</div>
  // 						<div className="wholesalex-shortcode-for-login">
  // 							<label htmlFor={'short_code_for_login'}>{wholesalex_overview.i18n.whx_form_builder_only_login_form}</label>
  // 						</div>
  // 						<div className="shortcode-content">
  // 							<div className="shortcode-tooltip">
  // 								<Tooltip content={wholesalex_overview.i18n.whx_form_builder_login_form_with_all_roles} ><span className="tooltip-icon dashicons dashicons-editor-help" /></Tooltip>
  // 							</div>
  // 							<div className="shortcode">
  // 								<input type="text" data-role="global_login" value={shortcodes['global_login'] ? getShortcode(shortcodes['global_login']) : getShortcode(`[wholesalex_login]`)} onFocus={e => e.target.select()} onClick={e => e.target.select()} readOnly />
  // 								<Tooltip content={wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard} >
  // 									<span className="shortcode-icon dashicons dashicons-clipboard"
  // 										onClick={() => {
  // 											let _element = `[data-role="global_login"]`;
  // 											let _shortcode = document.querySelector(_element);
  // 											if (navigator.clipboard) {
  // 												_shortcode = _shortcode.value;
  // 												navigator.clipboard.writeText(_shortcode);
  // 											} else {
  // 												try {
  // 													_shortcode.focus();
  // 													document.execCommand("copy");

  // 												} catch (err) { }
  // 											}
  // 										}} />
  // 								</Tooltip>
  // 							</div>
  // 						</div>
  // 					</li>
  // 					<li>
  // 						<div className="shortcode-for">
  // 							{wholesalex_overview.i18n.whx_form_builder_b2b_global_form}
  // 						</div>
  // 						<div className="wholesalex-shortcode-for-login">
  // 							<input
  // 								id={`short_code_for_login_global_b2b`}
  // 								name={`short_code_for_login_global_b2b`}
  // 								type="checkbox"
  // 								value={loginShortcodeStatus['global_b2b']}
  // 								onChange={(e) => {
  // 									let _status = !loginShortcodeStatus['global_b2b'];
  // 									setLoginShortcodeStatus({ ...loginShortcodeStatus, ['global_b2b']: _status });
  // 									let _shortcode = `[wholesalex_registration registration_role="all_b2b"]`;
  // 									if (_status) {
  // 										_shortcode = `[wholesalex_login_registration registration_role="all_b2b"]`;
  // 									}
  // 									_shortcode = getShortcode(_shortcode);
  // 									setShortcodes({ ...shortcodes, ['global_b2b']: _shortcode });
  // 								}}
  // 							/>
  // 							<label htmlFor={'short_code_for_login_global_b2b'}>{wholesalex_overview.i18n.whx_form_builder_with_login_form}</label>

  // 						</div>

  // 						<div className="shortcode-content">
  // 							<div className="shortcode-tooltip">
  // 								<Tooltip content={wholesalex_overview.i18n.whx_form_builder_regi_form_with_all_roles} ><span className="tooltip-icon dashicons dashicons-editor-help" /></Tooltip>
  // 							</div>
  // 							<div className="shortcode">
  // 								<input type="text" data-role="global_b2b" value={shortcodes['global_b2b'] ? getShortcode(shortcodes['global_b2b']) : getShortcode(`[wholesalex_registration registration_role="all_b2b"]`)} onFocus={e => e.target.select()} onClick={e => e.target.select()} readOnly />
  // 								<Tooltip content={wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard} >
  // 									<span className="shortcode-icon dashicons dashicons-clipboard"
  // 										onClick={() => {
  // 											let _element = `[data-role="global_b2b"]`;
  // 											let _shortcode = document.querySelector(_element);
  // 											if (navigator.clipboard) {
  // 												_shortcode = _shortcode.value;
  // 												navigator.clipboard.writeText(_shortcode);
  // 											} else {
  // 												try {
  // 													_shortcode.focus();
  // 													document.execCommand("copy");

  // 												} catch (err) { }
  // 											}
  // 										}} />
  // 								</Tooltip>
  // 							</div>
  // 						</div>
  // 					</li>
  // 					{roles.map((role, i) => {
  // 						return role['value'] !== "wholesalex_guest" && role.value && <li key={`modal_${i}`}>
  // 							<div className="shortcode-for">
  // 								{role.name}
  // 							</div>
  // 							<div className="wholesalex-shortcode-for-login">
  // 								<input
  // 									id={`short_code_for_login_${i}`}
  // 									name={`short_code_for_login_${i}`}
  // 									type="checkbox"
  // 									value={loginShortcodeStatus[role.value]}
  // 									onChange={(e) => {
  // 										let _status = !loginShortcodeStatus[role.value];
  // 										setLoginShortcodeStatus({ ...loginShortcodeStatus, [role.value]: _status });
  // 										let _shortcode = `[wholesalex_registration registration_role="${role.value}"]`;
  // 										if (_status) {
  // 											_shortcode = `[wholesalex_login_registration registration_role="${role.value}"]`;
  // 										}
  // 										_shortcode = getShortcode(_shortcode);
  // 										setShortcodes({ ...shortcodes, [role.value]: _shortcode });
  // 									}}
  // 								/>
  // 								<label htmlFor={`short_code_for_login_${i}`}>{wholesalex_overview.i18n.whx_form_builder_with_login_form}</label>

  // 							</div>
  // 							<div className="shortcode-content">
  // 								<div className="shortcode-tooltip">
  // 									<Tooltip content={`${wholesalex_overview.i18n.whx_form_builder_regi_form_only_for} ${role.name} ${wholesalex_overview.i18n.whx_form_builder_role}`}> <span className="tooltip-icon dashicons dashicons-editor-help"></span></Tooltip>
  // 								</div>
  // 								<div className="shortcode">
  // 									<input type="text" data-role={getShortcode(role.value)} date-before-copy="Copy To Clipboard" date-copied="Copied!" value={shortcodes[role.value] ? getShortcode(shortcodes[role.value]) : getShortcode(`[wholesalex_registration registration_role="${role.value}"]`)} onFocus={e => e.target.select()} onClick={e => e.target.select()} readOnly />
  // 									<Tooltip content={wholesalex_overview.i18n.whx_form_builder_copy_to_clipboard} >
  // 										<span className="shortcode-icon dashicons dashicons-clipboard" onClick={(e) => {
  // 											let _element = `[data-role="${role.value}"]`;
  // 											let _shortcode = document.querySelector(_element);
  // 											if (navigator.clipboard) {
  // 												_shortcode = _shortcode.value;
  // 												navigator.clipboard.writeText(_shortcode);
  // 											} else {
  // 												try {
  // 													_shortcode.focus();
  // 													document.execCommand("copy");
  // 												} catch (err) { }
  // 											}
  // 										}} />
  // 									</Tooltip>

  // 								</div>
  // 							</div>
  // 						</li>

  // 					}
  // 					)}
  // 				</ul>
  // 			</div>
  // 		</div>

  // 	</div>
  // );
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ShortcodesModal);

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/TextTransformControl.js":
/*!*****************************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/TextTransformControl.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TextTransformControl)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);


function TextTransformControl(_ref) {
  let {
    label,
    getValue,
    setValue
  } = _ref;
  const TEXT_TRANSFORMS = [{
    name: wholesalex_overview.i18n.whx_form_builder_none,
    value: 'none',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("path", {
      d: "M7 11.5h10V13H7z"
    }))
  }, {
    name: wholesalex_overview.i18n.whx_form_builder_uppercase,
    value: 'uppercase',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("path", {
      d: "M6.1 6.8L2.1 18h1.6l1.1-3h4.3l1.1 3h1.6l-4-11.2H6.1zm-.8 6.8L7 8.9l1.7 4.7H5.3zm15.1-.7c-.4-.5-.9-.8-1.6-1 .4-.2.7-.5.8-.9.2-.4.3-.9.3-1.4 0-.9-.3-1.6-.8-2-.6-.5-1.3-.7-2.4-.7h-3.5V18h4.2c1.1 0 2-.3 2.6-.8.6-.6 1-1.4 1-2.4-.1-.8-.3-1.4-.6-1.9zm-5.7-4.7h1.8c.6 0 1.1.1 1.4.4.3.2.5.7.5 1.3 0 .6-.2 1.1-.5 1.3-.3.2-.8.4-1.4.4h-1.8V8.2zm4 8c-.4.3-.9.5-1.5.5h-2.6v-3.8h2.6c1.4 0 2 .6 2 1.9.1.6-.1 1-.5 1.4z"
    }))
  }, {
    name: wholesalex_overview.i18n.whx_form_builder_lowercase,
    value: 'lowercase',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("path", {
      d: "M11 16.8c-.1-.1-.2-.3-.3-.5v-2.6c0-.9-.1-1.7-.3-2.2-.2-.5-.5-.9-.9-1.2-.4-.2-.9-.3-1.6-.3-.5 0-1 .1-1.5.2s-.9.3-1.2.6l.2 1.2c.4-.3.7-.4 1.1-.5.3-.1.7-.2 1-.2.6 0 1 .1 1.3.4.3.2.4.7.4 1.4-1.2 0-2.3.2-3.3.7s-1.4 1.1-1.4 2.1c0 .7.2 1.2.7 1.6.4.4 1 .6 1.8.6.9 0 1.7-.4 2.4-1.2.1.3.2.5.4.7.1.2.3.3.6.4.3.1.6.1 1.1.1h.1l.2-1.2h-.1c-.4.1-.6 0-.7-.1zM9.2 16c-.2.3-.5.6-.9.8-.3.1-.7.2-1.1.2-.4 0-.7-.1-.9-.3-.2-.2-.3-.5-.3-.9 0-.6.2-1 .7-1.3.5-.3 1.3-.4 2.5-.5v2zm10.6-3.9c-.3-.6-.7-1.1-1.2-1.5-.6-.4-1.2-.6-1.9-.6-.5 0-.9.1-1.4.3-.4.2-.8.5-1.1.8V6h-1.4v12h1.3l.2-1c.2.4.6.6 1 .8.4.2.9.3 1.4.3.7 0 1.2-.2 1.8-.5.5-.4 1-.9 1.3-1.5.3-.6.5-1.3.5-2.1-.1-.6-.2-1.3-.5-1.9zm-1.7 4c-.4.5-.9.8-1.6.8s-1.2-.2-1.7-.7c-.4-.5-.7-1.2-.7-2.1 0-.9.2-1.6.7-2.1.4-.5 1-.7 1.7-.7s1.2.3 1.6.8c.4.5.6 1.2.6 2s-.2 1.4-.6 2z"
    }))
  }, {
    name: wholesalex_overview.i18n.whx_form_builder_capitalize,
    value: 'capitalize',
    icon: /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      viewBox: "0 0 24 24",
      width: "24",
      height: "24",
      "aria-hidden": "true",
      focusable: "false"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("path", {
      d: "M7.1 6.8L3.1 18h1.6l1.1-3h4.3l1.1 3h1.6l-4-11.2H7.1zm-.8 6.8L8 8.9l1.7 4.7H6.3zm14.5-1.5c-.3-.6-.7-1.1-1.2-1.5-.6-.4-1.2-.6-1.9-.6-.5 0-.9.1-1.4.3-.4.2-.8.5-1.1.8V6h-1.4v12h1.3l.2-1c.2.4.6.6 1 .8.4.2.9.3 1.4.3.7 0 1.2-.2 1.8-.5.5-.4 1-.9 1.3-1.5.3-.6.5-1.3.5-2.1-.1-.6-.2-1.3-.5-1.9zm-1.7 4c-.4.5-.9.8-1.6.8s-1.2-.2-1.7-.7c-.4-.5-.7-1.2-.7-2.1 0-.9.2-1.6.7-2.1.4-.5 1-.7 1.7-.7s1.2.3 1.6.8c.4.5.6 1.2.6 2 .1.8-.2 1.4-.6 2z"
    }))
  }];
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: 'wholesalex-text-transform-control wholesalex-style-formatting-field'
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wholesalex-style-formatting-field-label wsx-font-12-normal"
  }, label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wholesalex-text-transform-control__buttons wholesalex-style-formatting-field-content"
  }, TEXT_TRANSFORMS.map(textTransform => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("span", {
      className: `wholesalex-text-transform-control__button wsx-font-12-normal ${textTransform.value === getValue() ? 'is-pressed' : ''}`,
      key: textTransform.value,
      onClick: () => {
        setValue(textTransform.value === getValue() ? undefined : textTransform.value);
      }
    }, textTransform.icon);
  })));
}

/***/ }),

/***/ "./reactjs/src/pages/registration_form_builder/Utils.js":
/*!**************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/Utils.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getFormStyle: () => (/* binding */ getFormStyle),
/* harmony export */   getInputFieldVariation: () => (/* binding */ getInputFieldVariation),
/* harmony export */   getRegistrationFormFieldOptions: () => (/* binding */ getRegistrationFormFieldOptions)
/* harmony export */ });
const getInputFieldVariation = function (variation, field) {
  let getFieldSettings = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  const isHeadingShow = () => {
    return !field.isLabelHide || getFieldSettings;
  };

  //Render Term and Condition Field in Builder
  const renderTermConditionField = (field, isHeadingShow, getFieldSettings) => {
    return /*#__PURE__*/React.createElement("div", {
      className: "wsx-form-field wsx-form-checkbox"
    }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
      className: "wsx-field-heading wsx-flex_space-between_center"
    }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
      className: "wsx-form-label",
      htmlFor: field.name
    }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
      "aria-label": "required"
    }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
      className: "wsx-field-content"
    }, /*#__PURE__*/React.createElement("div", {
      className: "wholesalex-field-wrap"
    }, /*#__PURE__*/React.createElement("input", {
      type: "checkbox",
      id: field.name,
      name: field.name,
      value: ''
    }), /*#__PURE__*/React.createElement("label", {
      htmlFor: field.name
    }, field.default_text))), field.help_message && /*#__PURE__*/React.createElement("span", {
      className: "wsx-form-field-help-message"
    }, field.help_message));
  };
  switch (variation) {
    case 'variation_1':
    case 'variation_3':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: field.type,
            name: field.name,
            required: field.required,
            placeholder: field.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'select':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("select", {
            name: field.name,
            id: field.name
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("option", {
              value: option.value
            }, option.name);
          }))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'checkbox':
          return /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-form-checkbox"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-content"
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("div", {
              className: "wholesalex-field-wrap"
            }, /*#__PURE__*/React.createElement("input", {
              type: "checkbox",
              id: option.value,
              name: field.name,
              value: option.value
            }), /*#__PURE__*/React.createElement("label", {
              htmlFor: option.value
            }, option.name));
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-field-radio"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-content"
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("div", {
              className: "wholesalex-field-wrap"
            }, /*#__PURE__*/React.createElement("input", {
              type: "radio",
              id: option.value,
              name: field.name,
              value: option.value
            }), /*#__PURE__*/React.createElement("label", {
              htmlFor: option.value
            }, option.name));
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'file':
          return /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-form-file"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("label", {
            className: "wsx-field-content"
          }, /*#__PURE__*/React.createElement("input", {
            type: field.type,
            id: field.id,
            placeholder: field?.placeholder,
            pattern: field?.pattern,
            name: field.name
          }), /*#__PURE__*/React.createElement("div", {
            className: "wsx-file-label",
            htmlFor: field?.name
          }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            width: "18",
            height: "18",
            viewBox: "0 0 18 18",
            fill: "none"
          }, /*#__PURE__*/React.createElement("path", {
            d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
            stroke: "#6C6CFF",
            "stroke-width": "1.5",
            "stroke-linecap": "round",
            "stroke-linejoin": "round"
          })), "Upload File"), 'No File Chosen')), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'tel':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: 'tel',
            name: field.name,
            required: field.required,
            pattern: field?.inputPattern,
            placeholder: field.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'url':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: 'url',
            name: field.name,
            required: field.required,
            pattern: field?.inputPattern,
            placeholder: field.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'password':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: 'password',
            name: field.name,
            required: field.required,
            minLength: field?.minLength,
            maxLength: field?.maxLength,
            size: field?.size,
            pattern: field?.inputPattern,
            placeholder: field.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'textarea':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("textarea", {
            id: field.name,
            name: field.name,
            required: field.required,
            rows: field?.rows,
            cols: field?.cols,
            placeholder: field.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        // case 'privacy_policy_text':
        //     return <div className="wsx-privacy-policy">{wholesalex_overview?.whx_form_builder_privacy_policy_text}</div>
        default:
          break;
      }
      break;
    case 'variation_2':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
        case 'url':
        case 'tel':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("input", {
              id: field.name,
              type: field.type,
              name: field.name,
              required: field.required,
              placeholder: field.placeholder,
              autoComplete: "false"
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label wsx-clone-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'password':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              className: "wsx-form-field__input",
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name,
              minLength: field?.minLength,
              maxLength: field?.maxLength,
              size: field?.size,
              autoComplete: "false"
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label wsx-clone-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'textarea':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-textarea"
            }, /*#__PURE__*/React.createElement("textarea", {
              id: field.name,
              className: "wsx-form-field__textarea",
              name: field.name,
              required: field.required,
              rows: field?.rows,
              cols: field?.cols,
              placeholder: field.placeholder,
              autoComplete: "false"
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label wsx-clone-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'file':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-file wsx-file-outline"
            }, /*#__PURE__*/React.createElement("label", {
              className: "wsx-field-content",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name
            }), !field.isLabelHide && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label wsx-clone-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label",
              htmlFor: field?.name
            }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
              xmlns: "http://www.w3.org/2000/svg",
              width: "18",
              height: "18",
              viewBox: "0 0 18 18",
              fill: "none"
            }, /*#__PURE__*/React.createElement("path", {
              d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
              stroke: "#6C6CFF",
              "stroke-width": "1.5",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            })), "Upload File"), 'No File Chosen'))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'select':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-select"
            }, /*#__PURE__*/React.createElement("select", {
              name: field.name,
              id: field.name
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("option", {
                value: option.value
              }, option.name);
            })), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label wsx-clone-label"
            }, field.label), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'checkbox':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-checkbox"
            }, /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("label", {
                className: "wholesalex-field-wrap",
                htmlFor: field.name
              }, /*#__PURE__*/React.createElement("input", {
                type: "checkbox",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("div", null, option.name)));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-field-radio"
            }, /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("div", {
                className: "wholesalex-field-wrap"
              }, /*#__PURE__*/React.createElement("input", {
                type: "radio",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("label", {
                htmlFor: option.value
              }, option.name));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        default:
          break;
      }
      break;
    case 'variation_6':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'url':
        case 'tel':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("input", {
              id: field.name,
              type: field.type,
              name: field.name,
              placeholder: field.label + (field.required ? '*' : ''),
              required: field.required,
              autoComplete: false
            })), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message, "  "))
          );
        case 'date':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-date wsx-marginTop_36"
            }, /*#__PURE__*/React.createElement("input", {
              id: field.name,
              type: field.type,
              name: field.name,
              required: field.required
            })), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'password':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              className: "wsx-form-field__input",
              id: field.id,
              placeholder: field.label + (field.required ? '*' : ''),
              pattern: field?.pattern,
              name: field.name,
              minLength: field?.minLength,
              maxLength: field?.maxLength,
              size: field?.size,
              autoComplete: "false"
            })), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'textarea':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-textarea"
            }, /*#__PURE__*/React.createElement("textarea", {
              id: field.name,
              className: "wsx-form-field__textarea",
              name: field.name,
              required: field.required,
              rows: field?.rows,
              cols: field?.cols,
              placeholder: field.label + (field.required ? '*' : ''),
              autoComplete: "false"
            })), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'file':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-file wsx-marginTop_36"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("label", {
              className: "wsx-field-content",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label",
              htmlFor: field?.name
            }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
              xmlns: "http://www.w3.org/2000/svg",
              width: "18",
              height: "18",
              viewBox: "0 0 18 18",
              fill: "none"
            }, /*#__PURE__*/React.createElement("path", {
              d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
              stroke: "#6C6CFF",
              "stroke-width": "1.5",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            })), "Upload File"), 'No File Chosen'))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'select':
          return /*#__PURE__*/React.createElement(React.Fragment, null, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-outline-focus wsx-form-select wsx-marginTop_36"
          }, /*#__PURE__*/React.createElement("select", {
            name: field.name,
            id: field.name
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("option", {
              value: option.value
            }, option.name);
          }))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'checkbox':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-checkbox"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("label", {
                className: "wholesalex-field-wrap",
                htmlFor: field.name
              }, /*#__PURE__*/React.createElement("input", {
                type: "checkbox",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("div", {
                htmlFor: option.value
              }, option.name)));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-field-radio"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("div", {
                className: "wholesalex-field-wrap"
              }, /*#__PURE__*/React.createElement("input", {
                type: "radio",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("label", {
                htmlFor: option.value
              }, option.name));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        default:
          break;
      }
      break;
    case 'variation_4':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
        case 'url':
        case 'tel':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("input", {
              id: field.name,
              type: field.type,
              name: field.name,
              required: field.required,
              placeholder: field.placeholder,
              autoComplete: "false"
            }), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'password':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-field wsx-outline-focus",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              className: "wsx-form-field__input",
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name,
              minLength: field?.minLength,
              maxLength: field?.maxLength,
              size: field?.size,
              autoComplete: "false"
            }), !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'textarea':
          return /*#__PURE__*/React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-outline-focus wsx-form-textarea"
          }, /*#__PURE__*/React.createElement("textarea", {
            id: field.name,
            className: "wsx-form-field__textarea",
            name: field.name,
            required: field.required,
            rows: field?.rows,
            cols: field?.cols,
            placeholder: field.placeholder,
            autoComplete: "false"
          }), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'file':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-file"
            }, /*#__PURE__*/React.createElement("label", {
              className: "wsx-field-content",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label",
              htmlFor: field?.name
            }, /*#__PURE__*/React.createElement("div", null, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label_wrap"
            }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
              xmlns: "http://www.w3.org/2000/svg",
              width: "18",
              height: "18",
              viewBox: "0 0 18 18",
              fill: "none"
            }, /*#__PURE__*/React.createElement("path", {
              d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
              stroke: "#6C6CFF",
              "stroke-width": "1.5",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            })), "Upload File"), 'No File Chosen')))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'select':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("select", {
              name: field.name,
              id: field.name
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("option", {
                value: option.value
              }, option.name);
            })), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'checkbox':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-form-checkbox"
          }, /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-content"
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("label", {
              className: "wholesalex-field-wrap",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: "checkbox",
              id: option.value,
              name: field.name,
              value: option.value
            }), /*#__PURE__*/React.createElement("label", {
              htmlFor: option.value
            }, option.name)));
          }))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-field-radio"
            }, /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("div", {
                className: "wholesalex-field-wrap"
              }, /*#__PURE__*/React.createElement("input", {
                type: "radio",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("label", {
                htmlFor: option.value
              }, option.name));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        default:
          break;
      }
      break;
    case 'variation_5':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
        case 'url':
        case 'tel':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("input", {
              id: field.name,
              type: field.type,
              name: field.name,
              required: field.required,
              placeholder: field?.placeholder,
              autoComplete: "false"
            }), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'password':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-field wsx-outline-focus",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              className: "wsx-form-field__input",
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name,
              minLength: field?.minLength,
              maxLength: field?.maxLength,
              size: field?.size
            }), !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'textarea':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-textarea"
            }, /*#__PURE__*/React.createElement("textarea", {
              id: field.name,
              className: "wsx-form-field__textarea",
              name: field.name,
              required: field.required,
              rows: field?.rows,
              cols: field?.cols,
              placeholder: field?.placeholder
            }), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'file':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-file"
            }, /*#__PURE__*/React.createElement("label", {
              className: "wsx-field-content",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label",
              htmlFor: field?.name
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label_wrap"
            }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
              xmlns: "http://www.w3.org/2000/svg",
              width: "18",
              height: "18",
              viewBox: "0 0 18 18",
              fill: "none"
            }, /*#__PURE__*/React.createElement("path", {
              d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
              stroke: "#6C6CFF",
              "stroke-width": "1.5",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            })), "Upload File"), 'No File Chosen')))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'select':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus"
            }, /*#__PURE__*/React.createElement("select", {
              name: field.name,
              id: field.name
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("option", {
                value: option.value
              }, option.name);
            })), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              htmlFor: field?.name,
              className: "wsx-form-label"
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'checkbox':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-checkbox"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("label", {
                className: "wholesalex-field-wrap",
                htmlFor: field.name
              }, /*#__PURE__*/React.createElement("input", {
                type: "checkbox",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("div", {
                htmlFor: option.value
              }, option.name)));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-field-radio"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("div", {
                className: "wholesalex-field-wrap"
              }, /*#__PURE__*/React.createElement("input", {
                type: "radio",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("label", {
                htmlFor: option.value
              }, option.name));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        default:
          break;
      }
      break;
    case 'variation_7':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
        case 'url':
        case 'tel':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-formBuilder-input-width"
            }, /*#__PURE__*/React.createElement("input", {
              id: field.name,
              type: field.type,
              name: field.name,
              required: field.required,
              autoComplete: "false",
              placeholder: " "
            }), /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("div", {
              className: "wsx-clone-label wsx-form-label"
            }, field.label)), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'password':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-formBuilder-input-width"
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              className: "wsx-form-field__input",
              id: field.name,
              pattern: field?.pattern,
              name: field.name,
              minLength: field?.minLength,
              maxLength: field?.maxLength,
              size: field?.size,
              autoComplete: "false",
              placeholder: " "
            }), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("div", {
              className: "wsx-clone-label wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'textarea':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-textarea wsx-formBuilder-input-width"
            }, /*#__PURE__*/React.createElement("textarea", {
              id: field.name,
              className: "wsx-form-field__textarea",
              name: field.name,
              required: field.required,
              rows: field?.rows,
              cols: field?.cols,
              autoComplete: "false",
              placeholder: " "
            }), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), /*#__PURE__*/React.createElement("div", {
              className: "wsx-clone-label wsx-form-label"
            }, field.label)), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'file':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-file wsx-form-file wsx-marginTop_36"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("label", {
              className: "wsx-field-content",
              htmlFor: field.name
            }, /*#__PURE__*/React.createElement("input", {
              type: field.type,
              id: field.id,
              placeholder: field?.placeholder,
              pattern: field?.pattern,
              name: field.name
            }), /*#__PURE__*/React.createElement("div", {
              className: "wsx-file-label",
              htmlFor: field?.name
            }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
              xmlns: "http://www.w3.org/2000/svg",
              width: "18",
              height: "18",
              viewBox: "0 0 18 18",
              fill: "none"
            }, /*#__PURE__*/React.createElement("path", {
              d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
              stroke: "#6C6CFF",
              "stroke-width": "1.5",
              "stroke-linecap": "round",
              "stroke-linejoin": "round"
            })), "Upload File"), 'No File Chosen'))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'select':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-outline-focus wsx-form-select wsx-formBuilder-input-width"
            }, /*#__PURE__*/React.createElement("select", {
              name: field.name,
              id: field.name
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("option", {
                value: option.value
              }, option.name);
            })), !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
              className: "wsx-form-label"
            }, field.label), /*#__PURE__*/React.createElement("div", {
              className: "wsx-clone-label wsx-form-label"
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*"))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'checkbox':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-form-checkbox"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("label", {
                className: "wholesalex-field-wrap",
                htmlFor: field.name
              }, /*#__PURE__*/React.createElement("input", {
                type: "checkbox",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("div", {
                htmlFor: option.value
              }, option.name)));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return (
            /*#__PURE__*/
            // wsx-form-field--focused 
            React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-field wsx-field-radio"
            }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-heading wsx-flex_space-between_center"
            }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
              className: "wsx-form-label",
              htmlFor: field.name
            }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
              "aria-label": "required"
            }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
              className: "wsx-field-content"
            }, field.option.map(option => {
              return /*#__PURE__*/React.createElement("div", {
                className: "wholesalex-field-wrap"
              }, /*#__PURE__*/React.createElement("input", {
                type: "radio",
                id: option.value,
                name: field.name,
                value: option.value
              }), /*#__PURE__*/React.createElement("label", {
                htmlFor: option.name
              }, option.name));
            }))), field.help_message && /*#__PURE__*/React.createElement("span", {
              className: "wsx-form-field-help-message"
            }, field.help_message))
          );
        default:
          break;
      }
      break;
    case 'variation_8':
      switch (field.type) {
        case 'text':
        case 'email':
        case 'number':
        case 'date':
        case 'url':
          return /*#__PURE__*/React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-field wsx-outline-focus wsx-formBuilder-input-width"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: field.type,
            name: field.name,
            required: field.required,
            placeholder: field?.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'select':
          return /*#__PURE__*/React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-outline-focus wsx-formBuilder-input-width"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), /*#__PURE__*/React.createElement("select", {
            name: field.name,
            id: field.name
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("option", {
              value: option.value
            }, option.name);
          }))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'checkbox':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-form-checkbox"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-content"
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("div", {
              className: "wholesalex-field-wrap"
            }, /*#__PURE__*/React.createElement("input", {
              type: "checkbox",
              id: option.value,
              name: field.name,
              value: option.value
            }), /*#__PURE__*/React.createElement("label", {
              htmlFor: option.value
            }, option.name));
          }))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'termCondition':
          return renderTermConditionField(field, isHeadingShow, getFieldSettings);
        case 'radio':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-field-radio"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-content"
          }, field.option.map(option => {
            return /*#__PURE__*/React.createElement("div", {
              className: "wholesalex-field-wrap"
            }, /*#__PURE__*/React.createElement("input", {
              type: "radio",
              id: option.value,
              name: field.name,
              value: option.value
            }), /*#__PURE__*/React.createElement("label", {
              htmlFor: option.value
            }, option.name));
          }))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'file':
          return /*#__PURE__*/React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-form-file"
          }, /*#__PURE__*/React.createElement("label", {
            className: "wsx-field-content"
          }, /*#__PURE__*/React.createElement("input", {
            type: field.type,
            id: field.id,
            placeholder: field?.placeholder,
            pattern: field?.pattern,
            name: field.name
          }), /*#__PURE__*/React.createElement("div", {
            className: "wsx-file-label",
            htmlFor: field?.name
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), /*#__PURE__*/React.createElement("div", {
            className: "wsx-file-label_wrap"
          }, /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            width: "18",
            height: "18",
            viewBox: "0 0 18 18",
            fill: "none"
          }, /*#__PURE__*/React.createElement("path", {
            d: "M2.25 11.25V14.25C2.25 15.075 2.925 15.75 3.75 15.75H14.25C14.6478 15.75 15.0294 15.592 15.3107 15.3107C15.592 15.0294 15.75 14.6478 15.75 14.25V11.25M12.75 6L9 2.25L5.25 6M9 3.15V10.875",
            stroke: "#6C6CFF",
            "stroke-width": "1.5",
            "stroke-linecap": "round",
            "stroke-linejoin": "round"
          })), "Upload File"), 'No File Chosen')))), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'tel':
          return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field"
          }, isHeadingShow() && /*#__PURE__*/React.createElement("div", {
            className: "wsx-field-heading wsx-flex_space-between_center"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), getFieldSettings && getFieldSettings()), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: 'tel',
            name: field.name,
            required: field.required,
            pattern: field?.inputPattern,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'password':
          return /*#__PURE__*/React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-outline-focus wsx-formBuilder-input-width"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), /*#__PURE__*/React.createElement("input", {
            id: field.name,
            type: 'password',
            name: field.name,
            required: field.required,
            minLength: field?.minLength,
            maxLength: field?.maxLength,
            size: field?.size,
            pattern: field?.inputPattern,
            placeholder: field?.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        case 'textarea':
          return /*#__PURE__*/React.createElement(React.Fragment, null, getFieldSettings && getFieldSettings(), /*#__PURE__*/React.createElement("div", {
            className: "wsx-form-field wsx-outline-focus wsx-formBuilder-input-width"
          }, !field.isLabelHide && /*#__PURE__*/React.createElement("label", {
            className: "wsx-form-label",
            htmlFor: field.name
          }, field.label, " ", field.required && /*#__PURE__*/React.createElement("span", {
            "aria-label": "required"
          }, "*")), /*#__PURE__*/React.createElement("textarea", {
            id: field.name,
            name: field.name,
            required: field.required,
            rows: field?.rows,
            cols: field?.cols,
            placeholder: field?.placeholder,
            autoComplete: "false"
          })), field.help_message && /*#__PURE__*/React.createElement("span", {
            className: "wsx-form-field-help-message"
          }, field.help_message));
        default:
          break;
      }
      break;
    default:
      break;
  }
};
const getFormStyle = (style, loginHeaderStyle, RegistrationHeaderStyle) => {
  let _style = {
    // Color
    // Field Sign Up Normal

    '--wsx-input-color': style?.color?.field?.signUp?.normal?.text,
    '--wsx-input-bg': style?.color?.field?.signUp?.normal?.background,
    '--wsx-input-border-color': style?.color?.field?.signUp?.normal?.border,
    '--wsx-input-placeholder-color': style?.color?.field?.signUp?.normal?.placeholder,
    '--wsx-form-label-color': style?.color?.field?.signUp?.normal?.label,
    // Field Sign Up Active
    '--wsx-input-focus-color': style?.color?.field?.signUp?.active?.text,
    '--wsx-input-focus-bg': style?.color?.field?.signUp?.active?.background,
    '--wsx-input-focus-border-color': style?.color?.field?.signUp?.active?.border,
    '--wsx-form-label-color-active': style?.color?.field?.signUp?.active?.label,
    // Field Sign Up Warning

    '--wsx-input-warning-color': style?.color?.field?.signUp?.warning?.text,
    '--wsx-input-warning-bg': style?.color?.field?.signUp?.warning?.background,
    '--wsx-input-warning-border-color': style?.color?.field?.signUp?.warning?.border,
    '--wsx-form-label-color-warning': style?.color?.field?.signUp?.warning?.label,
    // Field Sign In Normal

    '--wsx-login-input-color': style?.color?.field?.signIn?.normal?.text,
    '--wsx-login-input-bg': style?.color?.field?.signIn?.normal?.background,
    '--wsx-login-input-border-color': style?.color?.field?.signIn?.normal?.border,
    '--wsx-login-input-placeholder-color': style?.color?.field?.signIn?.normal?.placeholder,
    '--wsx-login-form-label-color': style?.color?.field?.signIn?.normal?.label,
    // Field Sign In Active
    '--wsx-login-input-focus-color': style?.color?.field?.signIn?.active?.text,
    '--wsx-login-input-focus-bg': style?.color?.field?.signIn?.active?.background,
    '--wsx-login-input-focus-border-color': style?.color?.field?.signIn?.active?.border,
    '--wsx-login-form-label-color-active': style?.color?.field?.signIn?.active?.label,
    // Field Sign In Warning
    '--wsx-login-input-warning-color': style?.color?.field?.signIn?.warning?.text,
    '--wsx-login-input-warning-bg': style?.color?.field?.signIn?.warning?.background,
    '--wsx-login-input-warning-border-color': style?.color?.field?.signIn?.warning?.border,
    '--wsx-login-form-label-color-warning': style?.color?.field?.signIn?.warning?.label,
    // Button Sign UP Normal
    '--wsx-form-button-color': style?.color?.button?.signUp?.normal?.text,
    '--wsx-form-button-bg': style?.color?.button?.signUp?.normal?.background,
    '--wsx-form-button-border-color': style?.color?.button?.signUp?.normal?.border,
    // Button Sign UP Hover
    '--wsx-form-button-hover-color': style?.color?.button?.signUp?.hover?.text,
    '--wsx-form-button-hover-bg': style?.color?.button?.signUp?.hover?.background,
    '--wsx-form-button-hover-border-color': style?.color?.button?.signUp?.hover?.border,
    // Button Sign In Normal
    '--wsx-login-form-button-color': style?.color?.button?.signIn?.normal?.text,
    '--wsx-login-form-button-bg': style?.color?.button?.signIn?.normal?.background,
    '--wsx-login-form-button-border-color': style?.color?.button?.signIn?.normal?.border,
    // Button Sign In Hover
    '--wsx-login-form-button-hover-color': style?.color?.button?.signIn?.hover?.text,
    '--wsx-login-form-button-hover-bg': style?.color?.button?.signIn?.hover?.background,
    '--wsx-login-form-button-hover-border-color': style?.color?.button?.signIn?.hover?.border,
    // Container Main
    '--wsx-form-container-bg': style?.color?.container?.main?.background,
    '--wsx-form-container-border-color': style?.color?.container?.main?.border,
    // Container Sign UP
    '--wsx-form-reg-bg': style?.color?.container?.signUp?.background,
    '--wsx-form-reg-border-color': style?.color?.container?.signUp?.border,
    // Container Sign IN
    '--wsx-login-bg': style?.color?.container?.signIn?.background,
    '--wsx-login-border-color': style?.color?.container?.signIn?.border,
    // Typography
    // Field - Label
    '--wsx-form-label-font-size': style?.typography?.field?.label?.size + 'px',
    '--wsx-form-label-weight': style?.typography?.field?.label?.weight,
    '--wsx-form-label-case-transform': style?.typography?.field?.label?.transform,
    // Field - Input
    '--wsx-input-font-size': style?.typography?.field?.input?.size + 'px',
    '--wsx-input-weight': style?.typography?.field?.input?.weight,
    '--wsx-input-case-transform': style?.typography?.field?.input?.transform,
    // Button

    '--wsx-form-button-font-size': style?.typography?.button?.size + 'px',
    '--wsx-form-button-weight': style?.typography?.button?.weight,
    '--wsx-form-button-case-transform': style?.typography?.button?.transform,
    // Size and Spacing
    // Input
    '--wsx-input-padding': style?.sizeSpacing?.input?.padding + 'px',
    '--wsx-input-width': style?.sizeSpacing?.input?.width + 'px',
    '--wsx-input-border-width': style?.sizeSpacing?.input?.border + 'px',
    '--wsx-input-border-radius': style?.sizeSpacing?.input?.borderRadius + 'px',
    // Button
    '--wsx-form-button-padding': style?.sizeSpacing?.button?.padding + 'px',
    '--wsx-form-button-width': style?.sizeSpacing?.button?.width + '%',
    '--wsx-form-button-border-width': style?.sizeSpacing?.button?.border + 'px',
    '--wsx-form-button-border-radius': style?.sizeSpacing?.button?.borderRadius + 'px',
    '--wsx-form-button-align': style?.sizeSpacing?.button?.align,
    // Container - Main
    '--wsx-form-container-border-width': style?.sizeSpacing?.container?.main?.border + 'px',
    '--wsx-form-container-width': style?.sizeSpacing?.container?.main?.width + 'px',
    '--wsx-form-container-border-radius': style?.sizeSpacing?.container?.main?.borderRadius + 'px',
    '--wsx-form-container-padding': style?.sizeSpacing?.container?.main?.padding + 'px',
    '--wsx-form-container-separator': style?.sizeSpacing?.container?.main?.separator + 'px',
    // Container - Sign In
    '--wsx-login-width': style?.sizeSpacing?.container?.signIn?.width + 'px',
    '--wsx-login-border-width': style?.sizeSpacing?.container?.signIn?.border + 'px',
    '--wsx-login-padding': style?.sizeSpacing?.container?.signIn?.padding + 'px',
    '--wsx-login-border-radius': style?.sizeSpacing?.container?.signIn?.borderRadius + 'px',
    // Container - Sign Up
    '--wsx-form-reg-width': style?.sizeSpacing?.container?.signUp?.width + 'px',
    '--wsx-form-reg-border-width': style?.sizeSpacing?.container?.signUp?.border + 'px',
    '--wsx-form-reg-padding': style?.sizeSpacing?.container?.signUp?.padding + 'px',
    '--wsx-form-reg-border-radius': style?.sizeSpacing?.container?.signUp?.borderRadius + 'px',
    '--wsx-login-title-font-size': loginHeaderStyle?.title?.size + 'px',
    '--wsx-login-title-case-transform': loginHeaderStyle?.title?.transform,
    '--wsx-login-title-font-weight': loginHeaderStyle?.title?.weight,
    '--wsx-login-title-color': loginHeaderStyle?.title?.color,
    '--wsx-login-description-font-size': loginHeaderStyle?.description?.size + 'px',
    '--wsx-login-description-case-transform': loginHeaderStyle?.description?.transform,
    '--wsx-login-description-font-weight': loginHeaderStyle?.description?.weight,
    '--wsx-login-description-color': loginHeaderStyle?.description?.color,
    '--wsx-reg-title-font-size': RegistrationHeaderStyle?.title?.size + 'px',
    '--wsx-reg-title-case-transform': RegistrationHeaderStyle?.title?.transform,
    '--wsx-reg-title-font-weight': RegistrationHeaderStyle?.title?.weight,
    '--wsx-reg-title-color': RegistrationHeaderStyle?.title?.color,
    '--wsx-reg-description-font-size': RegistrationHeaderStyle?.description?.size + 'px',
    '--wsx-reg-description-case-transform': RegistrationHeaderStyle?.description?.transform,
    '--wsx-reg-description-font-weight': RegistrationHeaderStyle?.description?.weight,
    '--wsx-reg-description-color': RegistrationHeaderStyle?.description?.color
  };
  return _style;
};
const getRegistrationFormFieldOptions = function (registrationForm) {
  let exclude = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  let _fields = {};
  for (let index = 0; index < registrationForm.registrationFields.length; index++) {
    const row = registrationForm.registrationFields[index];
    for (let j = 0; j < Object.keys(row['columns']).length; j++) {
      const col = row['columns'][j];
      if (col['status']) {
        _fields[col['name']] = col['label'];
      }
    }
  }
  if (exclude) {
    delete _fields[exclude];
  }
  return _fields;
};

/***/ }),

/***/ "./reactjs/src/utils/Icons.js":
/*!************************************!*\
  !*** ./reactjs/src/utils/Icons.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
const icons = {};
icons.growUp = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m20.2 7.8-7.7 7.7-4-4-5.7 5.7"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M15 7h6v6"
}));
icons.calendar = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  viewBox: "0 0 20 20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M2.577 7.837H17.43m-3.728 3.254h.007m-3.705 0h.008m-3.714 0h.008m7.396 3.239h.007m-3.705 0h.008m-3.714 0h.008M13.37 1.667v2.742M6.638 1.667v2.742"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M13.532 2.983H6.476C4.029 2.983 2.5 4.346 2.5 6.852v7.541c0 2.546 1.529 3.94 3.976 3.94h7.048c2.455 0 3.976-1.37 3.976-3.877V6.852c.008-2.506-1.513-3.87-3.968-3.87Z",
  clipRule: "evenodd"
}));
icons.information = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("g", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  clipPath: "url(#a)"
}, /*#__PURE__*/React.createElement("path", {
  d: "M8 14.667A6.667 6.667 0 1 0 8 1.333a6.667 6.667 0 0 0 0 13.334Zm0-4V8m0-2.667h.007"
})), /*#__PURE__*/React.createElement("defs", null, /*#__PURE__*/React.createElement("clipPath", {
  id: "a"
}, /*#__PURE__*/React.createElement("path", {
  fill: "none",
  d: "M0 0h16v16H0z"
}))));
icons.checkedMarked = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "10",
  height: "8",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeWidth: "1.5",
  d: "m1 4 2.5 2.5L9 1"
}));
icons.dummyProfile = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "800",
  height: "800",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  fill: "#d4d4da",
  d: "M12 22.01c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10Z"
}), /*#__PURE__*/React.createElement("path", {
  fill: "#9e9da7",
  d: "M12 6.94c-2.07 0-3.75 1.68-3.75 3.75 0 2.03 1.59 3.68 3.7 3.74h.18a3.743 3.743 0 0 0 3.62-3.74c0-2.07-1.68-3.75-3.75-3.75Zm6.78 12.42A9.976 9.976 0 0 1 12 22.01c-2.62 0-5-1.01-6.78-2.65.24-.91.89-1.74 1.84-2.38 2.73-1.82 7.17-1.82 9.88 0 .96.64 1.6 1.47 1.84 2.38Z"
}));
icons.profile = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("g", {
  fill: "currentColor"
}, /*#__PURE__*/React.createElement("path", {
  d: "M22 12c0-5.51-4.49-10-10-10S2 6.49 2 12c0 2.9 1.25 5.51 3.23 7.34 0 .01 0 .01-.01.02.1.1.22.18.32.27.06.05.11.1.17.14.18.15.38.29.57.43l.2.14c.19.13.39.25.6.36.07.04.15.09.22.13.2.11.41.21.63.3.08.04.16.08.24.11.22.09.44.17.66.24.08.03.16.06.24.08.24.07.48.13.72.19.07.02.14.04.22.05.28.06.56.1.85.13.04 0 .08.01.12.02.34.03.68.05 1.02.05.34 0 .68-.02 1.01-.05.04 0 .08-.01.12-.02.29-.03.57-.07.85-.13.07-.01.14-.04.22-.05.24-.06.49-.11.72-.19.08-.03.16-.06.24-.08.22-.08.45-.15.66-.24.08-.03.16-.07.24-.11.21-.09.42-.19.63-.3.08-.04.15-.09.22-.13.2-.12.4-.23.6-.36.07-.04.13-.09.2-.14.2-.14.39-.28.57-.43.06-.05.11-.1.17-.14.11-.09.22-.18.32-.27 0-.01 0-.01-.01-.02C20.75 17.51 22 14.9 22 12Zm-5.06 4.97c-2.71-1.82-7.15-1.82-9.88 0-.44.29-.8.63-1.1 1A8.48 8.48 0 0 1 3.5 12c0-4.69 3.81-8.5 8.5-8.5 4.69 0 8.5 3.81 8.5 8.5 0 2.32-.94 4.43-2.46 5.97-.29-.37-.66-.71-1.1-1Z"
}), /*#__PURE__*/React.createElement("path", {
  d: "M12 6.93c-2.07 0-3.75 1.68-3.75 3.75 0 2.03 1.59 3.68 3.7 3.74h.18a3.743 3.743 0 0 0 3.62-3.74c0-2.07-1.68-3.75-3.75-3.75Z"
})));
icons.profileUpdate = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  viewBox: "0 0 436.24 436.239"
}, /*#__PURE__*/React.createElement("g", {
  fill: "currentColor"
}, /*#__PURE__*/React.createElement("path", {
  d: "M396.64 211.551c-12.75 0-23.084 10.336-23.084 23.084 0 85.708-69.73 155.437-155.439 155.437-85.709 0-155.437-69.729-155.437-155.437S132.41 79.197 218.118 79.197c5.504 0 10.98.293 16.411.862l-13.607 24.446a9.231 9.231 0 0 0 .146 9.232 9.23 9.23 0 0 0 7.922 4.492c.049 0 .098 0 .146-.002l114.146-1.792a9.24 9.24 0 0 0 7.924-4.741 9.236 9.236 0 0 0-.145-9.233L292.443 4.493c-1.695-2.83-4.756-4.563-8.068-4.491a9.236 9.236 0 0 0-7.924 4.742l-17.996 32.332a202.485 202.485 0 0 0-40.335-4.049c-53.851 0-104.479 20.971-142.557 59.049-38.078 38.078-59.049 88.706-59.049 142.558 0 53.851 20.971 104.479 59.049 142.557 38.078 38.078 88.705 59.049 142.556 59.049 53.85 0 104.479-20.971 142.557-59.049s59.051-88.706 59.051-142.558c-.003-12.746-10.339-23.082-23.087-23.082z"
}), /*#__PURE__*/React.createElement("path", {
  d: "M317.91 333.346c-2.557-6.568-4.311-10.745-4.311-10.745-2.309-5.521-7.861-9.056-13.162-11.229l-35.835-16.498-17.214-14.518-27.662 27.513 9.98 66.722c34.376-2.823 65.282-18.074 88.204-41.245zM135.8 311.37c-5.302 2.175-11.07 5.95-13.162 11.229 0 0-1.754 4.178-4.311 10.745 22.922 23.171 53.828 38.423 88.203 41.243l9.979-66.722-27.661-27.513-17.214 14.518-35.834 16.5zm146.938-122.945c.396-39.734-18.527-63.515-64.621-63.515-46.094 0-65.021 23.779-64.618 63.515.622 57.262 27.969 91.406 64.618 91.406 36.648 0 63.998-34.145 64.621-91.406z"
})));
icons.import = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M2.5 12.5v3.333c0 .917.75 1.667 1.667 1.667h11.666a1.666 1.666 0 0 0 1.667-1.667V12.5m-3.333-5L10 11.667 5.833 7.5M10 10.667V2.083"
}));
icons.importFile = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "72",
  height: "72",
  viewBox: "0 0 72 72",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeWidth: "2.5",
  d: "M14.996 13.5v45a4.5 4.5 0 0 0 4.5 4.5h33a4.5 4.5 0 0 0 4.5-4.5V19.864a4.5 4.5 0 0 0-1.318-3.182l-6.364-6.364A4.5 4.5 0 0 0 46.132 9H19.496a4.5 4.5 0 0 0-4.5 4.5Z"
}), /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M41.996 24V9h6l9 9v6h-15Z"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeWidth: "2.5",
  d: "M46.504 42 36.216 52.288a.3.3 0 0 1-.424 0L25.504 42m10.5 10.5V30"
}));
icons.drag = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  d: "m11.999 2 4.166 5h-3.332v4.167H17V7.833L22 12l-5 4.167v-3.334h-4.167V17h3.332L12 22l-4.167-5h3.334v-4.167H7v3.334L2 12l5-4.167v3.334h4.166V7H7.832l4.167-5Z",
  clipRule: "evenodd"
}));
icons.image = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "72",
  height: "72",
  viewBox: "0 0 32 32"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  d: "M8 10a2 2 0 1 1 .001-4.001A2 2 0 0 1 8 10Zm0-6a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm22 13.128L24 11l-9.941 10.111L10 17l-8 7.337V4a2 2 0 0 1 2-2h24a2 2 0 0 1 2 2v13.128ZM30 28a2 2 0 0 1-2 2h-5.168l-7.368-7.465L24 13.999l6 6V28ZM4 30a2 2 0 0 1-2-2v-.939l7.945-7.116L20.001 30H4ZM28 0H4a4 4 0 0 0-4 4v24a4 4 0 0 0 4 4h24a4 4 0 0 0 4-4V4a4 4 0 0 0-4-4Z"
}));
icons.csv = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "72",
  height: "72",
  viewBox: "0 0 400 400"
}, /*#__PURE__*/React.createElement("g", {
  fill: "currentColor"
}, /*#__PURE__*/React.createElement("path", {
  d: "M325 105h-75a5 5 0 0 1-5-5V25a5 5 0 1 1 10 0v70h70a5 5 0 0 1 0 10Z"
}), /*#__PURE__*/React.createElement("path", {
  d: "M325 154.83a5 5 0 0 1-5-5v-47.76L247.93 30H100a20 20 0 0 0-20 20v98.17a5 5 0 0 1-10 0V50a30 30 0 0 1 30-30h150a5 5 0 0 1 3.54 1.46l75 75A5 5 0 0 1 330 100v49.83a5 5 0 0 1-5 5ZM300 380H100a30 30 0 0 1-30-30v-75a5 5 0 0 1 10 0v75a20 20 0 0 0 20 20h200a20 20 0 0 0 20-20v-75a5 5 0 0 1 10 0v75a30 30 0 0 1-30 30Z"
}), /*#__PURE__*/React.createElement("path", {
  d: "M275 280H125a5 5 0 1 1 0-10h150a5 5 0 0 1 0 10Zm-75 50h-75a5 5 0 1 1 0-10h75a5 5 0 0 1 0 10Z"
}), /*#__PURE__*/React.createElement("path", {
  d: "M325 280H75a30 30 0 0 1-30-30v-76.83a30 30 0 0 1 30-30h.2l250 1.66a30.09 30.09 0 0 1 29.81 30V250A30 30 0 0 1 325 280ZM75 153.17a20 20 0 0 0-20 20V250a20 20 0 0 0 20 20h250a20 20 0 0 0 20-20v-75.17a20.06 20.06 0 0 0-19.88-20l-250-1.66Z"
}), /*#__PURE__*/React.createElement("path", {
  d: "m168.48 217.48 8.91 1a20.84 20.84 0 0 1-6.19 13.18q-5.33 5.18-14 5.18-7.31 0-11.86-3.67a23.43 23.43 0 0 1-7-10 37.74 37.74 0 0 1-2.46-13.87q0-12.19 5.78-19.82t15.9-7.64a18.69 18.69 0 0 1 13.2 4.88q5.27 4.88 6.64 14l-8.91.94q-2.46-12.07-10.86-12.07-5.39 0-8.38 5t-3 14.55q0 9.69 3.2 14.63t8.48 4.94a9.3 9.3 0 0 0 7.19-3.32 13.25 13.25 0 0 0 3.36-7.91Zm10.93 5.67 9.34-2q1.68 7.93 12.89 7.93 5.12 0 7.87-2a6.07 6.07 0 0 0 2.75-5 7.09 7.09 0 0 0-1.25-4q-1.25-1.85-5.35-2.91l-10.2-2.66a25.1 25.1 0 0 1-7.73-3.11 12.15 12.15 0 0 1-4-4.9 15.54 15.54 0 0 1-1.5-6.76 14 14 0 0 1 5.31-11.46q5.31-4.32 13.59-4.32a24.86 24.86 0 0 1 12.29 3 13.56 13.56 0 0 1 6.89 8.52l-9.14 2.27q-2.11-6.05-9.84-6.05-4.49 0-6.86 1.88a5.83 5.83 0 0 0-2.36 4.77q0 4.57 7.42 6.41l9.06 2.27q8.24 2.07 11.05 6.11a15.29 15.29 0 0 1 2.81 8.93 14.7 14.7 0 0 1-5.92 12.36q-5.92 4.51-15.33 4.51a28 28 0 0 1-13.89-3.32 16.29 16.29 0 0 1-7.9-10.47Zm70.9 12.85h-9.77l-16.44-53.32h10.16l12.23 40.86L259 182.68h8Z"
})));
icons.excel = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "72",
  height: "72",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  d: "M9.293 1.293A1 1 0 0 1 10 1h8a3 3 0 0 1 3 3v5a1 1 0 1 1-2 0V4a1 1 0 0 0-1-1h-7v5a1 1 0 0 1-1 1H5v11a1 1 0 0 0 1 1h1a1 1 0 1 1 0 2H6a3 3 0 0 1-3-3V8a1 1 0 0 1 .293-.707l6-6ZM6.414 7H9V4.414L6.414 7ZM19 12a1 1 0 0 1 1 1v6h3a1 1 0 1 1 0 2h-4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Zm-7.186.419a1 1 0 1 0-1.628 1.162l2.085 2.919-2.085 2.919a1 1 0 1 0 1.628 1.162l1.686-2.36 1.686 2.36a1 1 0 1 0 1.628-1.162L14.729 16.5l2.085-2.919a1 1 0 1 0-1.628-1.162l-1.686 2.36-1.686-2.36Z",
  clipRule: "evenodd"
}));
icons.docFile = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "72",
  height: "72",
  viewBox: "0 0 512 512"
}, /*#__PURE__*/React.createElement("path", {
  fillRule: "evenodd",
  d: "M100.642 425.75h5.504c13.141 0 22.87-2.155 29.205-6.422 5.547-3.69 9.558-9.365 12.011-17.088 1.813-5.717 2.73-11.797 2.73-18.261 0-6.934-1.045-13.355-3.157-19.264-2.133-5.91-5.013-10.518-8.682-13.846-3.499-3.114-7.488-5.248-11.947-6.357-4.459-1.11-11.179-1.685-20.16-1.685h-5.504v82.922Zm-28.864 22.613V320.235h35.456c15.083 0 27.115 1.45 36.16 4.352 11.819 3.797 20.843 10.837 27.115 21.098 6.272 10.283 9.43 23.168 9.43 38.656 0 15.915-3.158 28.907-9.43 38.976-7.83 12.694-19.926 20.438-36.33 23.147-7.532 1.259-17.174 1.899-28.929 1.899H71.778Zm186.39-107.78c-10.433 0-18.475 4.33-24.15 13.013-5.12 7.85-7.68 17.898-7.68 30.186 0 14.23 3.03 25.28 9.13 33.11 5.718 7.424 13.334 11.114 22.785 11.114 10.368 0 18.453-4.352 24.234-13.12 5.12-7.7 7.68-17.877 7.68-30.549 0-13.93-3.05-24.79-9.13-32.64-5.718-7.403-13.355-11.115-22.87-11.115m.086-22.613c20.138 0 35.562 6.379 46.293 19.179 10.304 12.224 15.445 27.946 15.445 47.19 0 21.034-6.08 37.76-18.24 50.175-10.496 10.73-25.002 16.085-43.498 16.085-20.118 0-35.563-6.378-46.294-19.157-10.304-12.224-15.466-28.203-15.466-47.915 0-20.608 6.101-37.14 18.282-49.557 10.54-10.667 25.046-16 43.478-16m176.27 100.175 8.427 23.061c-7.616 3.691-14.229 6.166-19.904 7.467-5.674 1.28-12.672 1.92-20.97 1.92-12.544 0-22.912-1.792-31.104-5.397-11.478-5.078-20.118-13.056-25.942-23.958-5.248-9.77-7.872-21.717-7.872-35.818 0-23.787 7.254-41.835 21.782-54.059 10.538-8.917 24.448-13.376 41.685-13.376 7.36 0 14.037.661 20.075 2.005 6.016 1.366 12.864 3.755 20.501 7.19l-9.664 21.717c-10.176-5.547-19.947-8.32-29.29-8.32-11.265 0-19.883 3.861-25.857 11.563-6.272 8.085-9.408 18.752-9.408 32.021 0 13.739 3.286 24.49 9.856 32.235C383.41 424.139 392.476 428 404.06 428c5.226 0 10.07-.683 14.464-2.112 4.394-1.408 9.728-3.99 16-7.744M320 42.667H85.333v234.666H128v-192h174.293L384 167.04v110.293h42.667v-128L320 42.667Z"
}));
icons.file = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "72",
  height: "72",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "2",
  d: "M9 17h6m-6-4h6M9 9h1m3-6H8.2c-1.12 0-1.68 0-2.108.218a2 2 0 0 0-.874.874C5 4.52 5 5.08 5 6.2v11.6c0 1.12 0 1.68.218 2.108a2 2 0 0 0 .874.874C6.52 21 7.08 21 8.2 21h7.6c1.12 0 1.68 0 2.108-.218a2 2 0 0 0 .874-.874C19 19.48 19 18.92 19 17.8V9m-6-6 6 6m-6-6v4.4c0 .56 0 .84.109 1.054a1 1 0 0 0 .437.437C13.76 9 14.04 9 14.6 9H19"
}));
icons.tick = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  d: "M13.334 4 6 11.333 2.667 8"
}));
icons.cross = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  d: "m12 4-8 8m0-8 8 8"
}));
icons.plus_20 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M10 4.167v11.666M4.167 10h11.666"
}));
icons.plus = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M12 5v14m-7-7h14"
}));
icons.search = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M9.167 15.833a6.667 6.667 0 1 0 0-13.333 6.667 6.667 0 0 0 0 13.333ZM17.5 17.5l-3.625-3.625"
}));
icons.delete = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  d: "M12.883 6.312s-.361 4.49-.571 6.381c-.1.904-.659 1.433-1.572 1.45-1.74.031-3.481.033-5.22-.004-.88-.018-1.428-.553-1.526-1.44-.211-1.909-.571-6.387-.571-6.387M13.805 4.16H2.5m9.127 0a1.1 1.1 0 0 1-1.077-.883l-.162-.81a.853.853 0 0 0-.824-.633H6.742a.853.853 0 0 0-.825.632l-.162.811a1.099 1.099 0 0 1-1.077.883"
}));
icons.delete_24 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M19.325 9.468s-.543 6.735-.858 9.572c-.15 1.355-.987 2.15-2.358 2.174-2.61.047-5.221.05-7.83-.005-1.318-.027-2.141-.83-2.288-2.162-.317-2.862-.857-9.579-.857-9.579M20.708 6.24H3.75m13.69 0a1.648 1.648 0 0 1-1.614-1.324L15.583 3.7a1.28 1.28 0 0 0-1.237-.95h-4.233a1.28 1.28 0 0 0-1.237.95l-.243 1.216A1.648 1.648 0 0 1 7.018 6.24"
}));
icons.copy = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("g", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  clipPath: "url(#a)"
}, /*#__PURE__*/React.createElement("path", {
  d: "M13.333 6h-6C6.597 6 6 6.597 6 7.333v6c0 .737.597 1.334 1.333 1.334h6c.737 0 1.334-.597 1.334-1.334v-6c0-.736-.597-1.333-1.334-1.333Z"
}), /*#__PURE__*/React.createElement("path", {
  d: "M3.333 10h-.666a1.333 1.333 0 0 1-1.333-1.333v-6a1.333 1.333 0 0 1 1.333-1.334h6A1.333 1.333 0 0 1 10 2.667v.666"
})), /*#__PURE__*/React.createElement("defs", null, /*#__PURE__*/React.createElement("clipPath", {
  id: "a"
}, /*#__PURE__*/React.createElement("path", {
  fill: "#fff",
  d: "M0 0h16v16H0z"
}))));
icons.edit = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M9.333 1.333 12 4l-7.333 7.333H2V8.667l7.333-7.334ZM2 14.667h12"
}));
icons.reset = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "14",
  height: "14",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  d: "M1 1.002v3.6h3.692M1.098 9.144a6.043 6.043 0 0 0 2.646 3.038 6.287 6.287 0 0 0 4.014.752 6.205 6.205 0 0 0 3.612-1.866 5.927 5.927 0 0 0 1.616-3.66 5.89 5.89 0 0 0-1.093-3.84A6.15 6.15 0 0 0 8.574 1.24a6.302 6.302 0 0 0-4.082.215 6.103 6.103 0 0 0-3.043 2.66"
}));
icons.clipboard = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "18",
  height: "18",
  fill: "none",
  transform: "rotate(45)",
  viewBox: "-2.4 -2.4 28.8 28.8"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  stroke: "none",
  d: "M7.263 3.26A2.25 2.25 0 0 1 9.5 1.25h5a2.25 2.25 0 0 1 2.237 2.01c.764.016 1.423.055 1.987.159.758.14 1.403.404 1.928.93.602.601.86 1.36.982 2.26.116.866.116 1.969.116 3.336v6.11c0 1.367 0 2.47-.116 3.337-.122.9-.38 1.658-.982 2.26-.602.602-1.36.86-2.26.982-.867.116-1.97.116-3.337.116h-6.11c-1.367 0-2.47 0-3.337-.116-.9-.122-1.658-.38-2.26-.982-.602-.602-.86-1.36-.981-2.26-.117-.867-.117-1.97-.117-3.337v-6.11c0-1.367 0-2.47.117-3.337.12-.9.38-1.658.981-2.26.525-.525 1.17-.79 1.928-.929.564-.104 1.224-.143 1.987-.159Zm.002 1.5c-.718.016-1.272.052-1.718.134-.566.104-.895.272-1.138.515-.277.277-.457.665-.556 1.4-.101.754-.103 1.756-.103 3.191v6c0 1.435.002 2.436.103 3.192.099.734.28 1.122.556 1.399.277.277.665.457 1.4.556.754.101 1.756.103 3.191.103h6c1.435 0 2.436-.002 3.192-.103.734-.099 1.122-.28 1.399-.556.277-.277.457-.665.556-1.4.101-.755.103-1.756.103-3.191v-6c0-1.435-.002-2.437-.103-3.192-.099-.734-.28-1.122-.556-1.399-.244-.243-.572-.41-1.138-.515-.446-.082-1-.118-1.718-.133A2.25 2.25 0 0 1 14.5 6.75h-5a2.25 2.25 0 0 1-2.235-1.99ZM9.5 2.75a.75.75 0 0 0-.75.75v1c0 .414.336.75.75.75h5a.75.75 0 0 0 .75-.75v-1a.75.75 0 0 0-.75-.75h-5ZM6.25 10.5A.75.75 0 0 1 7 9.75h.5a.75.75 0 0 1 0 1.5H7a.75.75 0 0 1-.75-.75Zm3.5 0a.75.75 0 0 1 .75-.75H17a.75.75 0 0 1 0 1.5h-6.5a.75.75 0 0 1-.75-.75ZM6.25 14a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5H7a.75.75 0 0 1-.75-.75Zm3.5 0a.75.75 0 0 1 .75-.75H17a.75.75 0 0 1 0 1.5h-6.5a.75.75 0 0 1-.75-.75Zm-3.5 3.5a.75.75 0 0 1 .75-.75h.5a.75.75 0 0 1 0 1.5H7a.75.75 0 0 1-.75-.75Zm3.5 0a.75.75 0 0 1 .75-.75H17a.75.75 0 0 1 0 1.5h-6.5a.75.75 0 0 1-.75-.75Z",
  clipRule: "evenodd"
}));
icons.save = /*#__PURE__*/React.createElement("svg", {
  width: "20",
  height: "20",
  viewBox: "0 0 1920 1920",
  xmlns: "http://www.w3.org/2000/svg"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M790.706 338.824v112.94H395.412c-31.06 0-56.47 25.3-56.47 56.471v744.509c17.73-6.325 36.592-10.391 56.47-10.391h1129.412c19.877 0 38.738 4.066 56.47 10.39V508.236c0-31.171-25.412-56.47-56.47-56.47h-395.295V338.824h395.295c93.402 0 169.411 76.009 169.411 169.411v1242.353c0 93.403-76.01 169.412-169.411 169.412H395.412C302.009 1920 226 1843.99 226 1750.588V508.235c0-93.402 76.01-169.411 169.412-169.411h395.294Zm734.118 1016.47H395.412c-31.06 0-56.47 25.299-56.47 56.47v338.824c0 31.172 25.41 56.47 56.47 56.47h1129.412c31.058 0 56.47-25.298 56.47-56.47v-338.823c0-31.172-25.412-56.47-56.47-56.47ZM1016.622-.023v880.151l246.212-246.325 79.85 79.85-382.532 382.644-382.645-382.644 79.85-79.85L903.68 880.128V-.022h112.941ZM564.824 1468.235c-62.344 0-112.942 50.71-112.942 112.941s50.598 112.942 112.942 112.942c62.343 0 112.94-50.71 112.94-112.942 0-62.23-50.597-112.94-112.94-112.94Z",
  fillRule: "evenodd"
}));
icons.menu = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeMiterlimit: "10",
  strokeWidth: "2.5",
  d: "M5 4.167v11.666m5-11.666v11.666m5-11.666v11.666"
}));
icons.dot3 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  d: "M8 8.667a.667.667 0 1 0 0-1.334.667.667 0 0 0 0 1.334Zm4.667 0a.667.667 0 1 0 0-1.334.667.667 0 0 0 0 1.334Zm-9.333 0a.667.667 0 1 0 0-1.334.667.667 0 0 0 0 1.334Z"
}));
icons.lock = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  viewBox: "0 0 330 330"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M65 330h200c8.284 0 15-6.716 15-15V145c0-8.284-6.716-15-15-15h-15V85c0-46.869-38.131-85-85-85S80 38.131 80 85v45H65c-8.284 0-15 6.716-15 15v170c0 8.284 6.716 15 15 15zm115-95.014V255c0 8.284-6.716 15-15 15s-15-6.716-15-15v-20.014c-6.068-4.565-10-11.824-10-19.986 0-13.785 11.215-25 25-25s25 11.215 25 25c0 8.162-3.932 15.421-10 19.986zM110 85c0-30.327 24.673-55 55-55s55 24.673 55 55v45H110V85z"
}));
icons.help = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  viewBox: "0 0 20 20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  d: "M10.001 18.333a8.334 8.334 0 1 0 0-16.667 8.334 8.334 0 0 0 0 16.667ZM8.613 8.148c0-.238.078-.615.288-.906.175-.242.475-.482 1.1-.482.765 0 1.163.405 1.297.81.142.427.021.903-.422 1.198-.73.487-1.268.958-1.552 1.62-.25.584-.25 1.225-.249 1.826v.102h1.853c0-.759.016-1.005.1-1.198.062-.148.218-.371.876-.81a2.866 2.866 0 0 0 1.152-3.323c-.39-1.175-1.505-2.078-3.055-2.078-1.228 0-2.084.533-2.604 1.253a3.51 3.51 0 0 0-.636 1.988h1.852Zm2.314 6.945V13.24H9.074v1.852h1.854Z",
  clipRule: "evenodd"
}));
icons.settings = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  d: "m20.073 7.988-.57-.99a1.754 1.754 0 0 0-2.392-.646 1.745 1.745 0 0 1-2.39-.62 1.678 1.678 0 0 1-.236-.84 1.753 1.753 0 0 0-1.753-1.804h-1.15a1.745 1.745 0 0 0-1.745 1.754A1.754 1.754 0 0 1 8.083 6.57a1.678 1.678 0 0 1-.839-.235 1.754 1.754 0 0 0-2.391.646L4.24 7.988a1.754 1.754 0 0 0 .638 2.392 1.754 1.754 0 0 1 0 3.037A1.745 1.745 0 0 0 4.24 15.8l.58.998a1.753 1.753 0 0 0 2.39.68 1.737 1.737 0 0 1 2.383.638c.151.254.232.543.235.839 0 .968.786 1.753 1.754 1.753h1.15c.965 0 1.749-.78 1.753-1.745a1.745 1.745 0 0 1 1.754-1.754c.295.008.583.09.839.235a1.754 1.754 0 0 0 2.391-.637l.604-1.007a1.745 1.745 0 0 0-.637-2.391 1.746 1.746 0 0 1-.638-2.392 1.72 1.72 0 0 1 .638-.637 1.754 1.754 0 0 0 .637-2.383v-.009Z"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  d: "M12.16 14.315a2.416 2.416 0 1 0 0-4.833 2.416 2.416 0 0 0 0 4.833Z"
}));
icons.doc = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M12.281 2.302H6.737a3.184 3.184 0 0 0-3.195 3.107v8.928a3.19 3.19 0 0 0 3.119 3.259h6.734a3.239 3.239 0 0 0 3.107-3.26V6.699l-4.22-4.396Z",
  clipRule: "evenodd"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M12.063 2.292v2.424c0 1.183.956 2.142 2.14 2.146h2.295m-4.591 5.937h-4.5m2.795-3.127H7.406"
}));
icons.play = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeWidth: "1.5",
  d: "M14.504 8.336a2 2 0 0 1 0 3.328l-6.395 4.263C6.78 16.813 5 15.86 5 14.263V5.737c0-1.597 1.78-2.55 3.11-1.664l6.394 4.263Z"
}));
icons.playFill = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M14.92 8.613c.99.66.99 2.114 0 2.774l-7.33 4.886C6.484 17.01 5 16.217 5 14.886V5.114C5 3.783 6.484 2.99 7.591 3.727l7.329 4.886Z"
}));
icons.smile = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  viewBox: "0 0 34.25 34.25"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M17.125 0C7.668 0 0 7.667 0 17.125S7.668 34.25 17.125 34.25c9.459 0 17.125-7.667 17.125-17.125S26.584 0 17.125 0zm3.432 10.182a2.234 2.234 0 0 1 2.443.192 2.216 2.216 0 0 1 2.441-.192 2.23 2.23 0 0 1 1.047 2.619c-.355 1.504-2.952 3.173-3.25 3.359a.445.445 0 0 1-.478 0c-.298-.186-2.896-1.855-3.252-3.36a2.234 2.234 0 0 1-.105-.667 2.233 2.233 0 0 1 1.154-1.951zm-11.75 0a2.234 2.234 0 0 1 2.443.192 2.216 2.216 0 0 1 2.441-.192 2.23 2.23 0 0 1 1.048 2.619c-.356 1.504-2.953 3.173-3.25 3.359a.451.451 0 0 1-.48 0c-.297-.186-2.895-1.855-3.252-3.36a2.233 2.233 0 0 1-.104-.667 2.235 2.235 0 0 1 1.154-1.951zm17.556 12.897a11.217 11.217 0 0 1-9.237 4.864 11.215 11.215 0 0 1-9.24-4.865 1.5 1.5 0 1 1 2.471-1.7 8.217 8.217 0 0 0 6.77 3.565 8.22 8.22 0 0 0 6.768-3.565 1.498 1.498 0 1 1 2.468 1.701z"
}));
icons.sad = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  viewBox: "0 0 36 36"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M18 2a16 16 0 1 0 16 16A16 16 0 0 0 18 2Zm9 12.28a1.8 1.8 0 1 1-1.8-1.8 1.8 1.8 0 0 1 1.8 1.8Zm-15.55 1.8a1.8 1.8 0 1 1 1.8-1.8 1.8 1.8 0 0 1-1.84 1.8Zm14 7.53a1 1 0 0 1-1.6 1.2 7 7 0 0 0-11.31.13 1 1 0 1 1-1.63-1.16 9 9 0 0 1 14.54-.17Z"
}), /*#__PURE__*/React.createElement("path", {
  fill: "none",
  d: "M0 0h36v36H0z"
}));
icons.view = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  viewBox: "0 0 1024 1024"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M512 160c320 0 512 352 512 352S832 864 512 864 0 512 0 512s192-352 512-352zm0 64c-225.28 0-384.128 208.064-436.8 288 52.608 79.872 211.456 288 436.8 288 225.28 0 384.128-208.064 436.8-288-52.608-79.872-211.456-288-436.8-288zm0 64a224 224 0 1 1 0 448 224 224 0 0 1 0-448zm0 64a160.192 160.192 0 0 0-160 160c0 88.192 71.744 160 160 160s160-71.808 160-160-71.744-160-160-160z"
}));
icons.download = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("g", {
  stroke: "currentColor",
  strokeWidth: "1.5"
}, /*#__PURE__*/React.createElement("path", {
  strokeLinecap: "round",
  strokeLinejoin: "round",
  d: "M12 7v7m0 0 3-3m-3 3-3-3"
}), /*#__PURE__*/React.createElement("path", {
  strokeLinecap: "round",
  d: "M16 17H8"
}), /*#__PURE__*/React.createElement("path", {
  d: "M2 12c0-4.714 0-7.071 1.464-8.536C4.93 2 7.286 2 12 2c4.714 0 7.071 0 8.535 1.464C22 4.93 22 7.286 22 12c0 4.714 0 7.071-1.465 8.535C19.072 22 16.714 22 12 22s-7.071 0-8.536-1.465C2 19.072 2 16.714 2 12Z"
})));
icons.warningMessage = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none",
  transform: "scale(-1 1)",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  d: "M4 2h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H6l-4 4V4c0-1.1.9-2 2-2Zm7 3h2v6h-2V5Zm2 8h-2v2h2v-2Z",
  clipRule: "evenodd"
}));
icons.emailFill = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "32",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("rect", {
  width: "32",
  height: "24",
  fill: "#6C6CFF",
  rx: "4"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "#E7E7FF",
  strokeLinecap: "round",
  strokeWidth: "1.5",
  d: "m5 5 8.467 6.928a4 4 0 0 0 5.066 0L27 5"
}));
icons.registrationCardFill = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "32",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("rect", {
  width: "32",
  height: "24",
  fill: "#6C6CFF",
  rx: "4"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "#E7E7FF",
  strokeLinecap: "round",
  strokeWidth: "1.5",
  d: "M18 10h10m-10 4h7M9 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm0 0a5 5 0 0 0-5 5m5-5a5 5 0 0 1 5 5"
}));
icons.listCheckMark = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "18",
  height: "18",
  fill: "none"
}, /*#__PURE__*/React.createElement("circle", {
  cx: "9",
  cy: "9",
  r: "9",
  fill: "#E7E7FF"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "#6C6CFF",
  strokeWidth: "1.5",
  d: "m4.5 9 3 3 6-6"
}));
icons.angleDown = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m5 7.5 5 5 5-5"
}));
icons.angleDown_24 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m6 9 6 6 6-6"
}));
icons.angleLeft_24 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m15 18-6-6 6-6"
}));
icons.angleRight_24 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m9 18 6-6-6-6"
}));
icons.angleLeft = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m15 18-6-6 6-6"
}));
icons.angleRight = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none",
  viewBox: "0 0 24 24"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "m9 18 6-6-6-6"
}));
icons.arrowRight = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "16",
  height: "16",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.2",
  d: "M2 8h11.333m-4-4.667L14 8l-4.667 4.667"
}));
icons.arrowLeft_24 = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  stroke: "currentColor",
  strokeLinecap: "round",
  strokeLinejoin: "round",
  strokeWidth: "1.5",
  d: "M19 12H6m6-7-7 7 7 7"
}));
icons.proLock = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "40",
  height: "40",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  fillRule: "evenodd",
  d: "M14 13a6 6 0 0 1 12 0v2H14v-2Zm16 2v-2c0-5.523-4.477-10-10-10S10 7.477 10 13v2a4 4 0 0 0-4 4v14a4 4 0 0 0 4 4h20a4 4 0 0 0 4-4V19a4 4 0 0 0-4-4Zm-9.137 10.753a2.455 2.455 0 1 0-1.725 0L17.546 30h4.909l-1.592-4.247Z",
  clipRule: "evenodd"
}));

// icons.customerNumber = <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none"><circle cx="20" cy="20" r="20" fill="#5CADFF"/><path stroke="#fff" strokeWidth="1.5" d="M20 20a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 0a8 8 0 0 0-8 8m8-8a8 8 0 0 1 8 8"/></svg>
// icons.totalOrder = <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none"><circle cx="20" cy="20" r="20" fill="#A367F0"/><path stroke="#fff" strokeWidth="1.5" d="M12 26V14a2 2 0 0 1 2-2h8.864a2 2 0 0 1 1.414.586l3.136 3.136A2 2 0 0 1 28 17.136V26a2 2 0 0 1-2 2H14a2 2 0 0 1-2-2Zm4-3h8m-8-3h8m-8-3h6"/></svg>
// icons.totalSale = <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none"><circle cx="20" cy="20" r="20" fill="#6C6CFF"/><path stroke="#fff" strokeWidth="1.5" d="M25 16.5a3.5 3.5 0 0 0-3.5-3.5h-3a3.5 3.5 0 1 0 0 7h3a3.5 3.5 0 1 1 0 7h-3a3.5 3.5 0 0 1-3.5-3.5M20 10v20"/></svg>
// icons.netRevenue = <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none"><circle cx="20" cy="20" r="20" fill="#52D47F"/><path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M11 11v18h18"/><path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="m26.7 16-5.1 5.2-2.8-2.7-3.8 3.8"/></svg>
// icons.grossSale = <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none"><circle cx="20" cy="20" r="20" fill="#FEAD01"/><path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="m28.2 15.8-7.7 7.7-4-4-5.7 5.7"/><path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M23 15h6v6"/></svg>
// icons.avgOrderValue = <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none"><circle cx="20" cy="20" r="20" fill="#94C752"/><path fill="#fff" d="M17.75 22a.75.75 0 0 0-1.5 0h1.5Zm4.5-4a.75.75 0 0 0 1.5 0h-1.5Zm-3 8a.75.75 0 0 0 1.5 0h-1.5Zm1.5-12a.75.75 0 0 0-1.5 0h1.5Zm.25 1.25h-2v1.5h2v-1.5Zm-2 5.5h2v-1.5h-2v1.5Zm2 2.5h-2v1.5h2v-1.5Zm-2 0c-.69 0-1.25-.56-1.25-1.25h-1.5A2.75 2.75 0 0 0 19 24.75v-1.5ZM22.25 22c0 .69-.56 1.25-1.25 1.25v1.5A2.75 2.75 0 0 0 23.75 22h-1.5ZM21 20.75c.69 0 1.25.56 1.25 1.25h1.5A2.75 2.75 0 0 0 21 19.25v1.5ZM16.25 18A2.75 2.75 0 0 0 19 20.75v-1.5c-.69 0-1.25-.56-1.25-1.25h-1.5ZM19 15.25A2.75 2.75 0 0 0 16.25 18h1.5c0-.69.56-1.25 1.25-1.25v-1.5ZM23.75 18A2.75 2.75 0 0 0 21 15.25v1.5c.69 0 1.25.56 1.25 1.25h1.5Zm-3 8v-2h-1.5v2h1.5Zm-1.5-12v2h1.5v-2h-1.5Z"/><circle cx="20" cy="20" r="9" stroke="#fff" strokeWidth="1.5"/></svg>

icons.noData = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "150",
  height: "168",
  viewBox: "0 0 288 168",
  fill: "none"
}, /*#__PURE__*/React.createElement("path", {
  fill: "#070707",
  d: "M26.59 129.909V159h-3.408L7.33 136.159h-.285V159H3.523v-29.091h3.409l15.909 22.898h.284v-22.898h3.466Zm15.019 29.546c-1.97 0-3.698-.469-5.185-1.407-1.477-.937-2.632-2.249-3.466-3.934-.823-1.686-1.235-3.656-1.235-5.909 0-2.273.412-4.257 1.235-5.952.834-1.695 1.99-3.012 3.466-3.949 1.487-.938 3.215-1.406 5.185-1.406 1.97 0 3.693.468 5.17 1.406 1.487.937 2.642 2.254 3.466 3.949.834 1.695 1.25 3.679 1.25 5.952 0 2.253-.416 4.223-1.25 5.909-.823 1.685-1.979 2.997-3.465 3.934-1.478.938-3.201 1.407-5.171 1.407Zm0-3.012c1.496 0 2.727-.383 3.693-1.15.966-.767 1.681-1.776 2.145-3.026.464-1.25.696-2.604.696-4.062 0-1.459-.232-2.818-.696-4.077-.464-1.26-1.179-2.278-2.145-3.054-.966-.777-2.197-1.165-3.693-1.165s-2.727.388-3.693 1.165c-.966.776-1.681 1.794-2.145 3.054-.464 1.259-.696 2.618-.696 4.077 0 1.458.232 2.812.696 4.062.464 1.25 1.179 2.259 2.145 3.026.966.767 2.197 1.15 3.693 1.15ZM76.414 159h-8.977v-29.091h9.375c2.822 0 5.237.582 7.244 1.747 2.008 1.156 3.547 2.817 4.617 4.986 1.07 2.159 1.605 4.744 1.605 7.756 0 3.03-.54 5.639-1.62 7.826-1.079 2.178-2.65 3.855-4.715 5.029-2.065 1.165-4.574 1.747-7.529 1.747Zm-5.454-3.125h5.227c2.405 0 4.399-.464 5.98-1.392 1.582-.928 2.76-2.249 3.537-3.963.777-1.714 1.165-3.755 1.165-6.122 0-2.349-.384-4.371-1.15-6.066-.768-1.704-1.913-3.011-3.438-3.92-1.525-.919-3.423-1.378-5.696-1.378H70.96v22.841Zm30.753 3.636c-1.383 0-2.637-.26-3.764-.781-1.127-.53-2.022-1.292-2.685-2.287-.663-1.004-.994-2.216-.994-3.636 0-1.25.246-2.263.739-3.04a5.221 5.221 0 0 1 1.974-1.847 10.387 10.387 0 0 1 2.727-.994 33.4 33.4 0 0 1 3.026-.54c1.325-.17 2.4-.298 3.224-.383.834-.095 1.44-.251 1.818-.469.389-.218.583-.597.583-1.136v-.114c0-1.401-.384-2.49-1.151-3.267-.757-.776-1.908-1.165-3.452-1.165-1.6 0-2.855.351-3.764 1.051-.909.701-1.548 1.449-1.917 2.245l-3.182-1.137c.568-1.325 1.326-2.358 2.273-3.096a8.526 8.526 0 0 1 3.125-1.563 12.968 12.968 0 0 1 3.352-.454c.701 0 1.506.085 2.415.255a7.738 7.738 0 0 1 2.656 1.009c.862.511 1.577 1.283 2.145 2.315.568 1.032.852 2.415.852 4.148V159h-3.352v-2.955h-.171c-.227.474-.606.981-1.136 1.52-.53.54-1.236.999-2.117 1.378-.88.379-1.955.568-3.224.568Zm.511-3.011c1.326 0 2.444-.26 3.353-.781.918-.521 1.609-1.193 2.074-2.017.473-.824.71-1.691.71-2.6v-3.068c-.142.171-.455.327-.938.469-.473.132-1.022.251-1.647.355-.616.095-1.217.18-1.804.256-.578.066-1.047.123-1.407.17-.871.114-1.685.298-2.443.554-.748.246-1.354.62-1.818 1.122-.455.493-.682 1.165-.682 2.017 0 1.165.431 2.046 1.293 2.642.87.587 1.974.881 3.309.881Zm24.656-19.318v2.841h-11.307v-2.841h11.307Zm-8.011-5.227h3.352v20.795c0 .947.137 1.657.412 2.131.284.464.644.776 1.08.937.445.152.913.227 1.406.227.369 0 .672-.018.909-.056l.568-.114.682 3.011a6.681 6.681 0 0 1-.952.256c-.407.095-.923.142-1.548.142a6.75 6.75 0 0 1-2.784-.611 5.517 5.517 0 0 1-2.244-1.861c-.588-.833-.881-1.884-.881-3.153v-21.704Zm19.034 27.556c-1.382 0-2.637-.26-3.764-.781-1.127-.53-2.022-1.292-2.685-2.287-.662-1.004-.994-2.216-.994-3.636 0-1.25.246-2.263.739-3.04a5.216 5.216 0 0 1 1.974-1.847 10.39 10.39 0 0 1 2.727-.994 33.48 33.48 0 0 1 3.026-.54 138.22 138.22 0 0 1 3.224-.383c.834-.095 1.44-.251 1.819-.469.388-.218.582-.597.582-1.136v-.114c0-1.401-.383-2.49-1.151-3.267-.757-.776-1.908-1.165-3.451-1.165-1.601 0-2.855.351-3.764 1.051-.91.701-1.549 1.449-1.918 2.245l-3.182-1.137c.568-1.325 1.326-2.358 2.273-3.096a8.523 8.523 0 0 1 3.125-1.563 12.973 12.973 0 0 1 3.352-.454c.701 0 1.506.085 2.415.255a7.738 7.738 0 0 1 2.656 1.009c.862.511 1.577 1.283 2.145 2.315.568 1.032.852 2.415.852 4.148V159h-3.352v-2.955h-.17c-.228.474-.606.981-1.137 1.52-.53.54-1.236.999-2.116 1.378-.881.379-1.956.568-3.225.568Zm.512-3.011c1.325 0 2.443-.26 3.352-.781.919-.521 1.61-1.193 2.074-2.017.473-.824.71-1.691.71-2.6v-3.068c-.142.171-.454.327-.937.469-.474.132-1.023.251-1.648.355-.616.095-1.217.18-1.804.256-.578.066-1.047.123-1.406.17-.872.114-1.686.298-2.444.554-.748.246-1.354.62-1.818 1.122-.454.493-.682 1.165-.682 2.017 0 1.165.431 2.046 1.293 2.642.871.587 1.974.881 3.31.881Zm26.431 2.5v-29.091h17.444v3.125h-13.921v9.83h12.614v3.125h-12.614V159h-3.523Zm29.845.455c-1.97 0-3.698-.469-5.185-1.407-1.477-.937-2.633-2.249-3.466-3.934-.824-1.686-1.236-3.656-1.236-5.909 0-2.273.412-4.257 1.236-5.952.833-1.695 1.989-3.012 3.466-3.949 1.487-.938 3.215-1.406 5.185-1.406 1.969 0 3.693.468 5.17 1.406 1.487.937 2.642 2.254 3.466 3.949.833 1.695 1.25 3.679 1.25 5.952 0 2.253-.417 4.223-1.25 5.909-.824 1.685-1.979 2.997-3.466 3.934-1.477.938-3.201 1.407-5.17 1.407Zm0-3.012c1.496 0 2.727-.383 3.693-1.15.966-.767 1.681-1.776 2.145-3.026.464-1.25.696-2.604.696-4.062 0-1.459-.232-2.818-.696-4.077-.464-1.26-1.179-2.278-2.145-3.054-.966-.777-2.197-1.165-3.693-1.165-1.497 0-2.728.388-3.694 1.165-.966.776-1.68 1.794-2.145 3.054-.464 1.259-.696 2.618-.696 4.077 0 1.458.232 2.812.696 4.062.465 1.25 1.179 2.259 2.145 3.026.966.767 2.197 1.15 3.694 1.15Zm28.313-6.363v-12.898h3.352V159h-3.352v-3.693h-.227c-.512 1.108-1.307 2.05-2.387 2.827-1.079.767-2.443 1.15-4.09 1.15-1.364 0-2.576-.298-3.637-.895-1.06-.606-1.894-1.515-2.5-2.727-.606-1.222-.909-2.76-.909-4.617v-13.863h3.352v13.636c0 1.591.445 2.86 1.336 3.807.899.947 2.045 1.42 3.437 1.42.833 0 1.681-.213 2.543-.639.871-.426 1.6-1.079 2.187-1.96.597-.881.895-2.003.895-3.366Zm12.405-4.205V159h-3.353v-21.818h3.239v3.409h.284a6.232 6.232 0 0 1 2.33-2.671c1.041-.681 2.386-1.022 4.034-1.022 1.477 0 2.77.303 3.878.909 1.107.596 1.969 1.505 2.585 2.727.615 1.212.923 2.746.923 4.602V159h-3.352v-13.636c0-1.714-.445-3.05-1.335-4.006-.891-.966-2.112-1.449-3.665-1.449-1.07 0-2.027.232-2.87.696-.833.464-1.491 1.141-1.974 2.031-.483.891-.724 1.97-.724 3.239Zm27.844 13.58c-1.818 0-3.423-.46-4.815-1.378-1.392-.928-2.481-2.235-3.267-3.921-.786-1.695-1.179-3.698-1.179-6.008 0-2.292.393-4.281 1.179-5.966.786-1.686 1.88-2.988 3.281-3.906 1.402-.919 3.021-1.378 4.858-1.378 1.421 0 2.543.236 3.367.71.833.464 1.467.994 1.903 1.591.445.587.791 1.07 1.037 1.449h.284v-10.739h3.352V159h-3.238v-3.352h-.398c-.246.397-.597.899-1.051 1.505-.455.597-1.103 1.132-1.946 1.606-.843.464-1.965.696-3.367.696Zm.455-3.012c1.345 0 2.481-.35 3.409-1.051.928-.71 1.634-1.69 2.116-2.94.483-1.26.725-2.713.725-4.361 0-1.629-.237-3.054-.71-4.276-.474-1.231-1.175-2.187-2.103-2.869-.928-.691-2.073-1.037-3.437-1.037-1.421 0-2.604.365-3.551 1.094-.938.72-1.643 1.7-2.117 2.94-.464 1.231-.696 2.614-.696 4.148 0 1.553.237 2.964.711 4.233.482 1.259 1.193 2.263 2.13 3.011.947.739 2.121 1.108 3.523 1.108Zm20.142-26.534-.284 20.909h-3.295l-.284-20.909h3.863Zm-1.932 29.318c-.7 0-1.302-.251-1.803-.753a2.459 2.459 0 0 1-.753-1.804c0-.7.251-1.302.753-1.804a2.461 2.461 0 0 1 1.803-.752c.701 0 1.303.251 1.804.752.502.502.753 1.104.753 1.804 0 .464-.118.891-.355 1.279a2.646 2.646 0 0 1-.923.937 2.431 2.431 0 0 1-1.279.341Z"
}), /*#__PURE__*/React.createElement("path", {
  fill: "#FEAD01",
  d: "M76 24c0-6.627 5.373-12 12-12h31.029a12 12 0 0 0 8.486-3.515l4.97-4.97A12.002 12.002 0 0 1 140.971 0H192c6.627 0 12 5.373 12 12v72c0 6.627-5.373 12-12 12H88c-6.627 0-12-5.373-12-12V24Z"
}), /*#__PURE__*/React.createElement("ellipse", {
  cx: "118.223",
  cy: "36.445",
  fill: "#070707",
  rx: "6.222",
  ry: "9.333"
}), /*#__PURE__*/React.createElement("ellipse", {
  cx: "161.778",
  cy: "36.444",
  fill: "#070707",
  rx: "6.222",
  ry: "9.333"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "#070707",
  strokeWidth: "3.111",
  d: "M167.306 76.889C164.477 64.419 153.326 55.11 140 55.11c-13.325 0-24.476 9.309-27.306 21.778"
}));
icons.activeStatus = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "64",
  height: "64",
  fill: "none"
}, /*#__PURE__*/React.createElement("circle", {
  cx: "32",
  cy: "32",
  r: "32",
  fill: "#62C45A"
}), /*#__PURE__*/React.createElement("path", {
  stroke: "#fff",
  strokeWidth: "3",
  d: "m22 33 6 6 14-14"
}));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (icons);

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Editor.scss":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Editor.scss ***!
  \**************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/* =====================================
    wsx Flex Style ( justify, alignment)
=========================================*/
/* =====================================
    WSX Font Style size, weight, height
=====================================*/
/* ===================================================  
    wsx Positioning Position, Top, Left, Right,bottom 
===================================================*/
/* ===============================  
        Global & Common Style Start 
================================*/
.wsx-component-toggle-group-control, .wsx-font-12-normal {
  font-size: 12px !important;
  font-weight: 400;
  font-style: normal;
  line-height: 1.6em !important; }

.wsx-font-14-normal {
  font-size: 14px !important;
  font-weight: 400;
  font-style: normal;
  line-height: 22px !important; }

.wsx-font-18-lightBold {
  font-size: 18px !important;
  font-weight: 500;
  font-style: normal;
  line-height: 22px !important; }

.wsx-marginTop_36 {
  margin-top: 36px !important; }

.wsx-flex_space-between_center {
  display: flex !important;
  justify-content: space-between;
  align-items: center; }

/* ===============================  Global & Common Style End  ================================*/
/* ===============================  Builder Main Container Start  ================================*/
.wholesalex-form-builder {
  display: flex;
  padding: 1px; }
  .wholesalex-form-builder .wsx-form-field {
    transition: .5s; }
  .wholesalex-form-builder .wholesalex-reset-btn {
    gap: 8px !important;
    padding: 5px 16px; }
  .wholesalex-form-builder .wholesalex-fields-wrapper {
    border-radius: 4px;
    box-sizing: border-box;
    position: relative; }
  .wholesalex-form-builder .wholesalex-login-form-title {
    position: relative; }
  .wholesalex-form-builder .wsx-form-file.wsx-form-field .wsx-field-heading,
  .wholesalex-form-builder .wsx-form-file.wsx-form-field .wsx-reg-form-row-setting,
  .wholesalex-form-builder .wsx-reg-form-row .wsx-field-radio.wsx-form-field .wsx-field-heading,
  .wholesalex-form-builder .wsx-reg-form-row .wsx-field-radio.wsx-form-field .wsx-reg-form-row-setting,
  .wholesalex-form-builder .wsx-reg-form-row .wsx-form-checkbox.wsx-form-field .wsx-field-heading,
  .wholesalex-form-builder .wsx-reg-form-row .wsx-form-checkbox.wsx-form-field .wsx-reg-form-row-setting {
    position: static !important; }

.wholesalex-registration-form .wsx-row-active .wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox),
.wholesalex-registration-form .wsx_variation_1 .wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox),
.wholesalex-registration-form .wsx_variation_3 .wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox) {
  margin-top: 36px !important; }

.wholesalex-registration-form .wsx-row-active .wholesalex-registration-form-column.wsx-empty-column,
.wholesalex-registration-form .wsx-row-active .wholesalex-registration-form-column-spacer,
.wholesalex-registration-form .wsx_variation_1 .wholesalex-registration-form-column.wsx-empty-column,
.wholesalex-registration-form .wsx_variation_1 .wholesalex-registration-form-column-spacer,
.wholesalex-registration-form .wsx_variation_3 .wholesalex-registration-form-column.wsx-empty-column,
.wholesalex-registration-form .wsx_variation_3 .wholesalex-registration-form-column-spacer {
  margin-top: 36px; }

.wholesalex-registration-form .wsx_variation_2 .double-column .wholesalex-registration-form-column,
.wholesalex-registration-form .wsx_variation_5 .double-column .wholesalex-registration-form-column {
  display: flex !important;
  justify-content: flex-end;
  align-items: center;
  flex-direction: column; }

/* ===============================  Builder Main Container End  ================================*/
/* ===============================  Actual Form Container Start ================================*/
.wsx-form-builder {
  display: flex !important;
  justify-content: flex-start;
  align-items: center;
  flex-direction: column;
  padding: 0px 50px; }

.wholesalex-form-builder__form {
  width: 100%; }
  .wholesalex-form-builder__form .wholesalex-form-builder-header {
    flex-wrap: wrap;
    background: #fff;
    padding: 12px 24px;
    max-height: 64px;
    border-bottom: 1px solid #E6E5E5; }
    .wholesalex-form-builder__form .wholesalex-form-builder-header__heading {
      color: #343A46;
      font-size: 20px !important;
      font-weight: 500;
      font-style: normal;
      line-height: 28px !important; }
    .wholesalex-form-builder__form .wholesalex-form-builder-header__right {
      display: flex;
      gap: 16px; }

.wholesalex-form-builder-container {
  display: block;
  border-radius: 8px;
  background: #FFF;
  max-width: 1280px;
  margin: 40px auto;
  margin: 50px 50px 0px;
  width: 100%; }
  .wholesalex-form-builder-container .is-active svg, .wholesalex-form-builder-container .is-active path {
    fill: #6C6CFF; }

.wholesalex-registration-form,
.wholesalex-login-form {
  flex: 1; }
  .wholesalex-registration-form .wsx-reg-form-row,
  .wholesalex-login-form .wsx-reg-form-row {
    border-radius: 2px;
    padding: 10px 16px 16px;
    box-sizing: border-box;
    border: 1px solid transparent;
    cursor: pointer; }
  .wholesalex-registration-form .move-row-icon,
  .wholesalex-login-form .move-row-icon {
    cursor: move !important; }
  .wholesalex-registration-form .wholesalex-registration-form-column.left,
  .wholesalex-login-form .wholesalex-registration-form-column.left {
    order: 0; }
  .wholesalex-registration-form .wholesalex-registration-form-column.right,
  .wholesalex-login-form .wholesalex-registration-form-column.right {
    order: 1; }
  .wholesalex-registration-form .wsx-reg-form-row-setting,
  .wholesalex-login-form .wsx-reg-form-row-setting {
    display: flex; }
    .wholesalex-registration-form .wsx-reg-form-row-setting *,
    .wholesalex-login-form .wsx-reg-form-row-setting * {
      cursor: pointer; }
  .wholesalex-registration-form .wsx-header-popup_control,
  .wholesalex-login-form .wsx-header-popup_control {
    position: absolute !important;
    top: 6px !important;
    right: -9px !important; }
  .wholesalex-registration-form .wsx-row-active .wsx-reg-form-row,
  .wholesalex-login-form .wsx-row-active .wsx-reg-form-row {
    border-radius: 0px 2px 2px 2px;
    border: 1px solid #E4E4FF;
    background: rgba(250, 250, 255, 0.08); }
  .wholesalex-registration-form .wholesalex-registration-form-row.double-column,
  .wholesalex-login-form .wholesalex-registration-form-row.double-column {
    display: flex !important;
    justify-content: flex-start;
    align-items: flex-end;
    gap: 5px; }
    .wholesalex-registration-form .wholesalex-registration-form-row.double-column .wholesalex-registration-form-column,
    .wholesalex-login-form .wholesalex-registration-form-row.double-column .wholesalex-registration-form-column {
      flex: 1; }
    .wholesalex-registration-form .wholesalex-registration-form-row.double-column .wholesalex-form-builder-field-inserter,
    .wholesalex-login-form .wholesalex-registration-form-row.double-column .wholesalex-form-builder-field-inserter {
      height: 40px; }

.wholesalex-form-wrapper button.wsx-register-btn {
  margin: 16px;
  margin-left: 0px; }

.wholesalex-form-wrapper .wholesalex-login-form {
  flex: 1; }
  .wholesalex-form-wrapper .wholesalex-login-form .wsx-form-field {
    margin-top: 0px !important; }

.wholesalex-registration-form.wsx-not-empty > .wholesalex-form-builder-field-inserter {
  height: auto !important;
  border: none !important;
  margin-top: -8px; }

.wholesalex-registration-form.wsx-not-empty .wsx-fields-container .wsx-reg-form-row-wrapper:last-child .wsx-reg-form-row {
  margin-bottom: 0px !important; }

.wholesalex-registration-form .wsx-row-active .wholesalex-registration-form-column:not(.left) {
  margin-top: 40px; }

.wholesalex-registration-form .wsx-disable-field {
  opacity: 0.6; }

.wholesalex-registration-form .wsx-reg-form-heading {
  margin-bottom: 15px !important; }

.wholesalex-registration-form .wsx-reg-form-row-wrapper {
  position: relative; }
  .wholesalex-registration-form .wsx-reg-form-row-wrapper > div.wsx-reg-form-row-setting {
    position: absolute !important;
    top: -31px !important; }

.wholesalex-registration-form .wsx-reg-form-row {
  display: flex !important;
  justify-content: space-between;
  align-items: stretch;
  gap: 8px; }
  .wholesalex-registration-form .wsx-reg-form-row > div {
    width: 100%; }
  .wholesalex-registration-form .wsx-reg-form-row .wsx-reg-form-row-setting {
    justify-content: flex-end; }
  .wholesalex-registration-form .wsx-reg-form-row .wholesalex-registration-form-column {
    position: relative; }
    .wholesalex-registration-form .wsx-reg-form-row .wholesalex-registration-form-column:is(.right) {
      margin-top: 0px; }
    .wholesalex-registration-form .wsx-reg-form-row .wholesalex-registration-form-column .wsx-field-heading {
      width: 100%;
      height: 36px;
      position: absolute !important;
      top: 0px !important; }
    .wholesalex-registration-form .wsx-reg-form-row .wholesalex-registration-form-column .wsx-reg-form-row-setting {
      position: absolute !important;
      top: 0px !important;
      right: 0px !important;
      z-index: 1; }
    .wholesalex-registration-form .wsx-reg-form-row .wholesalex-registration-form-column > .wholesalex-form-builder-field-inserter {
      height: 100% !important; }
  .wholesalex-registration-form .wsx-reg-form-row > .wholesalex-registration-form-column-spacer,
  .wholesalex-registration-form .wsx-reg-form-row > div .wholesalex-registration-form-column-spacer {
    width: fit-content !important;
    border: 2px solid #CFCFCF;
    padding: 3px;
    border-top: 0px;
    border-bottom: 0px; }

.wsx-reg-form-row-setting > * {
  height: 32px;
  box-sizing: border-box;
  border: 1px solid #E4E4FF;
  background-color: #FAFAFF; }
  .wsx-reg-form-row-setting > *:hover .wsx-form-field {
    margin-top: 36px; }
  .wsx-reg-form-row-setting > *:not(:last-child) {
    border-right: 0px !important; }
  .wsx-reg-form-row-setting > *:first-child {
    border-radius: 2px 0px 0px 2px; }
  .wsx-reg-form-row-setting > *:last-child {
    border-radius: 0px 2px 2px 0px; }
  .wsx-reg-form-row-setting > *:hover {
    background-color: #E4E4FF; }

.wsx-reg-form-row-setting svg {
  padding: 7px; }

.wholesalex-registration-form .wsx-reg-fields.wsx-fields-container {
  margin-left: -16px; }

/* ===============================  Actual Form Container End ================================*/
/* ===============================  Sidebar Parent Starts ================================*/
.wsx-sidebar-container {
  transition: .7s;
  width: 100%;
  max-width: 300px; }

.wholesalex-form-builder__typography-setting {
  transition: 1s;
  animation: wsxSidebarHide .3s;
  /*------Choose Input Field Variation Start-------------*/
  /*------Choose Input Field Variation End-------------*/ }
  .wholesalex-form-builder__typography-setting::-webkit-scrollbar {
    /* width */
    width: 6px;
    border-radius: 3px; }
  .wholesalex-form-builder__typography-setting::-webkit-scrollbar-thumb, .wholesalex-form-builder__typography-setting::-webkit-scrollbar-thumb:hover {
    /* Handle */
    background: #C8C8D6; }
  .wholesalex-form-builder__typography-setting .wsx-chose-input-wrap {
    display: flex;
    flex-direction: column;
    max-height: 75px;
    border-radius: 2px;
    border: 1px solid #E9E9F0;
    padding: 11px 12px;
    height: 100%; }
    .wholesalex-form-builder__typography-setting .wsx-chose-input-wrap .wholesalex-choose-input-selected {
      display: flex !important;
      justify-content: center;
      align-items: center;
      gap: 12px;
      cursor: pointer; }
  .wholesalex-form-builder__typography-setting .wholesalex-choose-input-field-label {
    color: var(2, #343A46);
    font-size: 14px !important;
    font-weight: 500;
    font-style: normal;
    line-height: 22px !important;
    margin-bottom: 8px; }
  .wholesalex-form-builder__typography-setting .wholesalex-choose-input-field-wrap {
    position: relative;
    background-color: white; }
    .wholesalex-form-builder__typography-setting .wholesalex-choose-input-field-wrap img {
      max-width: 100%;
      width: 270px;
      margin-top: 8px;
      padding-top: 6px;
      border-top: 1px solid #e9e9f0; }
  .wholesalex-form-builder__typography-setting .wholesalex-choose-input-dropdown-icon {
    cursor: pointer; }
  .wholesalex-form-builder__typography-setting .wholesalex-choose-input-field-popup {
    display: flex;
    flex-direction: column; }

.wholesalex-choose-input-field-popup ul {
  width: auto;
  max-height: 420px;
  background: #fff;
  margin: 0px 16px;
  border-radius: 2px;
  border: 1px solid #E9E9F0;
  box-sizing: border-box;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
  overflow: auto;
  position: absolute !important;
  top: calc(100% + 2px) !important;
  right: 0px !important;
  z-index: 9999;
  transition: .4s; }
  .wholesalex-choose-input-field-popup ul li {
    cursor: pointer;
    border: 3px solid transparent;
    margin: 0px 5px;
    padding: 10px 10px 7px;
    animation: premadeOverflowAnimation 1.5s;
    font-size: 11px !important;
    font-weight: 500;
    font-style: normal;
    line-height: 1.6em !important; }
    .wholesalex-choose-input-field-popup ul li.wsx-selected {
      border: 3px solid;
      border-color: #6c6cff; }
    .wholesalex-choose-input-field-popup ul li:hover {
      border-color: #6c6cff; }
  .wholesalex-choose-input-field-popup ul::-webkit-scrollbar {
    width: 3px;
    background-color: transparent;
    border-radius: 0; }
  .wholesalex-choose-input-field-popup ul:hover::-webkit-scrollbar, .wholesalex-choose-input-field-popup ul:hover::-webkit-scrollbar-thumb {
    width: 3px;
    border-radius: 0;
    height: 3px; }
  .wholesalex-choose-input-field-popup ul:hover::-webkit-scrollbar {
    transition: 400ms; }
  .wholesalex-choose-input-field-popup ul:hover::-webkit-scrollbar-thumb {
    background-color: #2c2c2c; }

.wholesalex-form-builder-select-color {
  border-radius: 0px 0px 2px 2px;
  box-sizing: border-box;
  border-top: none;
  cursor: pointer; }
  .wholesalex-form-builder-select-color.is-open {
    background: #fafaff;
    transition: 0.5s; }
    .wholesalex-form-builder-select-color.is-open .selected-color-code {
      border-color: #e5e5e5; }
  .wholesalex-form-builder-select-color:hover .select-color-details {
    color: var(--color-primary-hover); }

.wholesalex-form-builder__typography-setting {
  width: 100%;
  max-width: 300px;
  background-color: #fff;
  border-left: 1px solid #E6E5E5;
  max-height: 100vh;
  overflow-y: scroll;
  position: sticky !important;
  top: 30px !important; }
  .wholesalex-form-builder__typography-setting .components-popover {
    will-change: transform;
    z-index: 1000000; }
  .wholesalex-form-builder__typography-setting .components-popover.is-expanded {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0;
    z-index: 1000000 !important; }
  .wholesalex-form-builder__typography-setting .components-popover__content {
    background: #fff;
    border-radius: 2px;
    box-shadow: 0 0 0 1px #ccc, 0 0.7px 1px rgba(0, 0, 0, 0.1), 0 1.2px 1.7px -0.2px rgba(0, 0, 0, 0.1), 0 2.3px 3.3px -0.5px rgba(0, 0, 0, 0.1);
    box-sizing: border-box;
    width: min-content; }

/* ===============================  Sidebar Parent End  ================================*/
/* ===============================  Sidebar & Field Control Common Style Start  ================================*/
.wholesalex-form-builder-select-color__dropdown {
  background-color: white; }

.wsx-color-picker-content {
  z-index: 999999;
  box-shadow: 8px 9px 16px 0px #0000001f; }

.select-color-details {
  display: flex;
  gap: 8px; }

.select-color-label {
  color: #343A46; }

.wholesalex-style-formatting-field {
  display: flex !important;
  justify-content: space-between;
  align-items: center;
  padding: 10px 8px;
  box-sizing: border-box;
  border-top: none; }

.wholesalex-style-formatting-field-content {
  display: flex !important;
  justify-content: flex-start;
  align-items: center;
  max-width: 88px !important;
  box-sizing: border-box; }
  .wholesalex-style-formatting-field-content input {
    width: 100%; }

select.wholesalex-style-formatting-field-content {
  height: 25.6px;
  min-height: unset; }

.wholesalex-size-selector input[type="number"] {
  height: 24px;
  min-height: unset;
  font-size: 12px;
  max-width: 40px;
  -moz-appearance: textfield;
  margin: 0px;
  border-radius: 0px;
  border: none;
  padding: 5px;
  text-align: center; }
  .wholesalex-size-selector input[type="number"]::-webkit-inner-spin-button, .wholesalex-size-selector input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0; }
  .wholesalex-size-selector input[type="number"]:focus {
    box-shadow: unset; }

.wholesalex-size-selector input[type='text'] {
  height: 24px;
  min-height: unset;
  font-size: 12px;
  width: 86px;
  -moz-appearance: textfield;
  margin: 0px;
  border-radius: 0px;
  border: none;
  padding: 5px;
  text-align: center; }
  .wholesalex-size-selector input[type='text']::-webkit-inner-spin-button, .wholesalex-size-selector input[type='text']::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0; }
  .wholesalex-size-selector input[type='text']:focus {
    box-shadow: unset; }

.wholesalex-size-selector .decrement,
.wholesalex-size-selector .increment {
  height: 24px;
  width: 24px;
  border-radius: 0px;
  background: #D9D9D9;
  cursor: pointer; }
  .wholesalex-size-selector .decrement:hover,
  .wholesalex-size-selector .increment:hover {
    background: #6c6c6c;
    color: white;
    border-color: #6c6c6c; }
    .wholesalex-size-selector .decrement:hover svg path,
    .wholesalex-size-selector .increment:hover svg path {
      stroke: white; }
  .wholesalex-size-selector .decrement svg,
  .wholesalex-size-selector .increment svg {
    margin-top: 5px;
    margin-left: 5px; }
  .wholesalex-size-selector .decrement input,
  .wholesalex-size-selector .increment input {
    margin: 0px; }

.wholesalex-style-formatting-field-label,
.components-custom-select-control__label {
  color: #343A46;
  margin: 0px;
  text-transform: capitalize; }

.components-custom-select-control__label {
  font-size: 12px !important;
  font-weight: 400;
  font-style: normal;
  line-height: 1.6em !important; }

.wholesalex-style-formatting-field-label-meta {
  color: #6C6E77;
  margin-left: 4px;
  text-transform: lowercase; }

.wholesalex-text-transform-control__buttons {
  display: flex;
  gap: 5px;
  padding: 3px;
  width: 88px;
  height: 25.6px; }

.wholesalex-text-transform-control__button {
  cursor: pointer;
  height: 16px; }
  .wholesalex-text-transform-control__button svg {
    fill: #6C6E77 !important;
    width: 16px;
    height: 16px; }
  .wholesalex-text-transform-control__button:not(.is-pressed):hover svg {
    fill: var(--color-primary-hover) !important; }
  .wholesalex-text-transform-control__button.is-pressed {
    border-radius: 0.5px;
    background: #D9D9D9; }

.component-color-indicator.wholesalex-form-builder__color {
  display: block;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  border: 1px solid #F2F2F2; }

.wholesalex_tooltip {
  margin-left: 10px; }
  .wholesalex_tooltip .wholesalex_tooltip_icon {
    cursor: pointer; }
  .wholesalex_tooltip .tooltip-content {
    left: auto; }
    .wholesalex_tooltip .tooltip-content::before {
      left: 69px; }

.wsx-form-setting > div:last-child {
  margin-bottom: 0px !important; }

.wsx-form-setting .wsx-field-condition-desc {
  display: flex !important;
  justify-content: space-between;
  align-items: center;
  flex-direction: row-reverse;
  font-weight: 500;
  text-transform: capitalize; }

.wsx-form-setting .wsx-setting-wrap {
  margin-bottom: 24px; }

.wsx-form-setting .wholesalex_discount_options {
  overflow-y: scroll !important;
  overflow: hidden; }

.wsx-form-setting .wsx-field-condition-wrap {
  border: 1px solid #8888;
  display: flex;
  width: fit-content; }
  .wsx-form-setting .wsx-field-condition-wrap .wsx-field-condition-btn {
    padding: 9px 16px;
    width: 55px;
    text-align: center;
    border: 1px solid #6c6cff; }
    .wsx-form-setting .wsx-field-condition-wrap .wsx-field-condition-btn:not(:last-child) {
      border-right: 0px; }
    .wsx-form-setting .wsx-field-condition-wrap .wsx-field-condition-btn:hover, .wsx-form-setting .wsx-field-condition-wrap .wsx-field-condition-btn.wsx-is-active {
      color: #fff;
      background-color: #6c6cff; }

.wsx-form-setting .wsx-setting-label,
.wsx-form-setting .wsx-select-wrap select
.wsx-toggle-wrap .wholesalex_field_label,
.wsx-form-setting .wsx-input-wrap .wholesalex_field_label,
.wsx-form-setting .wsx-select-wrap .wholesalex_field_label {
  color: #343A46;
  font-size: 12px !important;
  font-weight: 400;
  font-style: normal;
  line-height: 1.6em !important; }

.wsx-form-setting .wsx-toggle-wrap {
  display: flex !important;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap; }
  .wsx-form-setting .wsx-toggle-wrap .wsx-toggle-setting {
    display: flex !important;
    justify-content: center;
    align-items: center;
    flex-direction: row-reverse;
    gap: 12px; }

.wsx-form-setting .wsx-input-wrap .wholesalex_field_label {
  margin-top: 10px;
  max-width: 85px;
  width: 100%; }

.wsx-form-setting .wsx-input-wrap .wsx-input-setting {
  display: flex !important;
  justify-content: center;
  align-items: flex-start;
  flex-direction: row;
  width: 100%; }

.wsx-form-setting .wsx-input-wrap .wholesalex_input_field__content {
  width: 100%; }

.wsx-form-setting .wsx-select-wrap select {
  width: 100% !important;
  max-width: 100%;
  height: 40px !important; }

.wsx-form-setting .wsx-select-wrap .wsx-setting-label {
  display: block;
  margin-bottom: 5px;
  width: fit-content; }

/* ===============================  Sidebar & Field Control Common Style End ================================*/
/* ===============================  Input Field Control Popup Settings Start ================================*/
.wholesalex-popup-content {
  padding: 24px 24px; }

/* Field Setting Tab*/
.wholesalex-popup-tab-heading {
  display: flex !important;
  justify-content: space-evenly;
  align-items: flex-start;
  padding: 24px 24px 0px; }
  .wholesalex-popup-tab-heading .whx-tab-heading {
    cursor: pointer;
    width: 100%;
    background: #f5f5f5;
    text-align: center;
    font-weight: 500;
    padding: 16px 0px;
    display: flex;
    justify-content: space-evenly;
    gap: 11px; }
    .wholesalex-popup-tab-heading .whx-tab-heading:first-child {
      border-top-left-radius: 4px; }
    .wholesalex-popup-tab-heading .whx-tab-heading:last-child {
      border-top-right-radius: 4px; }
    .wholesalex-popup-tab-heading .whx-tab-heading .wsx-form-tutorial {
      cursor: pointer;
      font-size: 10px !important;
      font-weight: 400;
      font-style: normal;
      line-height: 14px !important;
      border: .5px solid #6C6E77;
      border-radius: 10px;
      padding: 1px 3px 1px 2px;
      display: flex !important;
      justify-content: center;
      align-items: center;
      gap: 2px;
      border-color: #6c6cff;
      display: none !important; }
      .wholesalex-popup-tab-heading .whx-tab-heading .wsx-form-tutorial svg path {
        fill: #6c6cff; }
    .wholesalex-popup-tab-heading .whx-tab-heading.whx-active-tab {
      color: #fff;
      background: #6c6cff; }
      .wholesalex-popup-tab-heading .whx-tab-heading.whx-active-tab .wsx-form-tutorial {
        display: flex !important;
        border-color: #fff; }
        .wholesalex-popup-tab-heading .whx-tab-heading.whx-active-tab .wsx-form-tutorial svg path {
          fill: #fff; }

.wholesalex_tiers_fields {
  display: flex !important;
  justify-content: flex-start;
  align-items: flex-end;
  gap: 10px; }

.wholesalex-tier {
  gap: 10px;
  display: flex !important;
  justify-content: space-between;
  align-items: flex-start;
  width: 100%; }
  .wholesalex-tier .tier-field {
    width: 100%; }

.wsx-field-conditions-tier {
  display: flex;
  flex-direction: column;
  width: 100%;
  gap: 10px;
  padding: 16px 24px 24px;
  margin-top: 40px;
  background: whitesmoke;
  box-sizing: border-box;
  position: relative !important;
  top: -24px !important; }
  .wsx-field-conditions-tier select,
  .wsx-field-conditions-tier input[type=text] {
    height: 40px !important;
    width: 100% !important; }

.wholesalex-tier-add {
  font-size: var(--wholesalex-size-20) !important;
  font-weight: 400;
  font-style: normal;
  line-height: 22px !important;
  padding: 9px 22px 9px 22px;
  box-sizing: border-box;
  height: 40px;
  display: flex !important;
  justify-content: center;
  align-items: center;
  color: white;
  border-radius: 3px;
  line-height: 21px;
  background-color: var(--wholesalex-primary-color); }

.wholesalex-tier-delete {
  font-size: var(--wholesalex-size-20) !important;
  font-weight: 400;
  font-style: normal;
  line-height: 22px !important;
  color: white;
  background-color: #d54013;
  border-radius: 2px;
  padding: 10px 13px 9px 14px; }

span.wsx-field-condition-btn.wsx-is-active {
  background-color: yellow; }

.wholesalex-field-conditions {
  padding-bottom: 0px !important; }

.wsx-reg-form-row-setting {
  z-index: 999; }

/* ===============================  Input Field Control Popup Settings End ================================*/
/* ===============================  Login Registration Header Start ================================*/
.wholesalex-disabled {
  opacity: 0.6; }

.wsx-popup-close {
  cursor: pointer; }
  .wsx-popup-close:hover path {
    stroke: #6C6CFF; }

.wsx-field-insert-popup {
  width: 496px;
  flex-shrink: 0; }

.wholesalex-field-insert-btn {
  cursor: pointer;
  color: white;
  height: 16px;
  width: 16px;
  font-size: 16px;
  border-radius: 16px;
  background: #343A46;
  padding: 8px; }
  .wholesalex-field-insert-btn:hover {
    background-color: #22252d; }
  .wholesalex-field-insert-btn.is-active {
    background: linear-gradient(180deg, #6C6CFF 0%, #4747D9 100%); }

.wholesalex-popup-heading {
  padding: 9px 24px;
  color: #343A46;
  border-radius: 8px 8px 0px 0px;
  background: rgba(108, 108, 255, 0.06);
  padding-right: 10px; }
  .wholesalex-popup-heading > div {
    display: flex !important;
    justify-content: center;
    align-items: center;
    gap: 14px; }

.wholesalex-form-builder-toogle {
  position: relative; }

.wholesalex-form-builder-field-poup {
  position: relative;
  animation: overflowAnimation .3s;
  max-height: 450px; }
  .wholesalex-form-builder-field-poup ul, .wholesalex-form-builder-field-poup li {
    padding: 0px;
    margin: 0px; }
  .wholesalex-form-builder-field-poup .wholesalex-popup-heading {
    font-weight: 500; }
  .wholesalex-form-builder-field-poup .wsx-insert-field-btn {
    display: inline-block;
    padding: 6px 12px;
    gap: 8px;
    border-radius: 2px;
    border: 1px solid rgba(108, 108, 255, 0.2);
    background: rgba(108, 108, 255, 0.12);
    margin-right: 4px;
    margin-bottom: 4px;
    cursor: pointer; }
    .wholesalex-form-builder-field-poup .wsx-insert-field-btn:hover {
      background: rgba(108, 108, 255, 0.2); }
  .wholesalex-form-builder-field-poup .wholesalex-form-builder-popup:not(:first-child) .wholesalex-popup-heading {
    position: static !important; }

.wholesalex-form-title-style-column-heading {
  color: #6C6E77;
  border-bottom: 1px solid #E9E9F0;
  margin-bottom: 16px; }

.wsx-reg-form-heading,
.wholesalex-login-form-title {
  cursor: pointer;
  border-radius: 2px;
  box-sizing: content-box;
  padding: 18px 16px 22px;
  border-radius: 0px 2px 2px 2px;
  border: 1px solid #E4E4FF;
  background: rgba(250, 250, 255, 0.08);
  transition: .3s; }

.field-setting-icon.wsx-active path {
  fill: #6C6CFF !important; }

.wholesalex-form-title-style-column {
  flex: 1; }
  .wholesalex-form-title-style-column .increment,
  .wholesalex-form-title-style-column .decrement {
    height: 36px;
    width: 36px;
    display: flex !important;
    justify-content: center;
    align-items: center; }
    .wholesalex-form-title-style-column .increment svg,
    .wholesalex-form-title-style-column .decrement svg {
      margin: 0px;
      width: 20px;
      height: 20px; }
  .wholesalex-form-title-style-column .wholesalex-form-title-style-row {
    margin-bottom: 16px; }
  .wholesalex-form-title-style-column .wholesalex-style-formatting-field {
    height: unset; }
  .wholesalex-form-title-style-column .wholesalex-text-transform-control {
    display: flex; }
    .wholesalex-form-title-style-column .wholesalex-text-transform-control .wholesalex-style-formatting-field-content {
      align-items: center !important;
      justify-content: space-around !important; }
  .wholesalex-form-title-style-column .wholesalex-form-builder-select-color {
    height: 36px;
    padding-left: 12px; }
  .wholesalex-form-title-style-column .wholesalex-text-transform-control__buttons {
    height: 36px; }
  .wholesalex-form-title-style-column .wholesalex-style-formatting-field-content {
    width: 100%;
    max-width: unset !important;
    justify-content: space-between;
    border-radius: 1px;
    border: 1px solid #E9E9F0; }
  .wholesalex-form-title-style-column .wholesalex-size-selector input[type=number] {
    height: 36px;
    width: 100%;
    max-width: 100% !important;
    border: 1px solid #E9E9F0; }

.wholesalex-form-builder-dropdown-content {
  border-radius: 8px;
  border: 1px solid #E4E4FF;
  background: #FFF;
  box-shadow: 0px 24px 40px 8px rgba(108, 108, 255, 0.22);
  width: 600px;
  max-height: 500px;
  z-index: 99999; }
  .wholesalex-form-builder-dropdown-content.wsx-setting-popup_control {
    top: 94px !important; }
  .wholesalex-form-builder-dropdown-content.wsx-header-popup_control {
    translate: -30% !important;
    top: 72px; }
  .wholesalex-form-builder-dropdown-content .wholesalex-popup-heading {
    position: sticky !important;
    top: 0px !important;
    z-index: 9999;
    background-color: #f6f6ff;
    display: flex !important;
    justify-content: space-between;
    align-items: center; }
    .wholesalex-form-builder-dropdown-content .wholesalex-popup-heading.wsx-popup-last {
      position: static !important;
      border-radius: 0px; }
  .wholesalex-form-builder-dropdown-content .components-popover__content::-webkit-scrollbar {
    width: 6px;
    transition: 400ms;
    background-color: #F6F6FF;
    padding: 1px; }
  .wholesalex-form-builder-dropdown-content .components-popover__content::-webkit-scrollbar-thumb {
    width: 6px;
    height: 80px;
    background-color: #C8C8D6; }
  .wholesalex-form-builder-dropdown-content .components-popover__content:hover .components-popover__content::-webkit-scrollbar-thumb {
    width: 6px;
    height: 80px;
    background-color: #C8C8D6; }

.wholesalex-form-builder-typography-setting-header {
  display: flex !important;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  max-height: 64px;
  border-bottom: 1px solid #E6E5E5;
  padding: 21px 24px;
  color: #343A46;
  max-width: 300px;
  font-size: 14px !important;
  font-weight: 500;
  font-style: normal;
  line-height: 22px !important;
  position: sticky !important;
  top: 0px !important;
  z-index: 999;
  background-color: #fff; }

.wholesalex-popup-content.wholesalex-form-title-style {
  display: flex;
  gap: 48px; }

.wholesalex-form-title-style .wholesalex-style-formatting-field {
  border: none;
  padding: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px; }

.wsx-form-title-setting .wholesalex-form-title-style-column-heading {
  font-weight: 500;
  line-height: 32px; }

.wholesalex-form-builder-field-inserter,
.wholesalex-registration-form-column > .wholesalex-form-builder-field-inserter {
  width: 100%;
  display: flex !important;
  justify-content: center;
  align-items: center;
  border-radius: 2px;
  border: 1px dashed #E4E4FF;
  height: 120px;
  position: relative;
  box-sizing: border-box; }

@keyframes overflowAnimation {
  0% {
    top: -100px; }
  100% {
    top: 0px; } }

select.wholesalex-style-formatting-field-content {
  height: 36px; }

.wholesalex-form-title-style-row .wholesalex-form-builder-select-color {
  border: none;
  width: 100%;
  flex-direction: row-reverse; }
  .wholesalex-form-title-style-row .wholesalex-form-builder-select-color .select-color-details {
    border-left: 1px solid #E9E9F0; }
  .wholesalex-form-title-style-row .wholesalex-form-builder-select-color .wholesalex-form-builder__color {
    height: 28px;
    width: 28px;
    border-radius: 1px;
    border: 4px solid #FAF7FC; }
  .wholesalex-form-title-style-row .wholesalex-form-builder-select-color .selected-color-code {
    text-transform: uppercase;
    color: #6C6E77;
    padding: 6px;
    border-radius: 1px;
    width: 88px;
    border: none; }

.wsx-editable-area {
  background-color: transparent !important;
  border: transparent !important;
  box-shadow: unset !important; }

.wsx-editable.wsx-register-btn svg {
  fill: var(--wsx-form-button-color) !important; }
  .wsx-editable.wsx-register-btn svg path {
    stroke: var(--wsx-form-button-color) !important; }

.wsx-editable.wsx-register-btn input {
  text-align: center !important;
  color: var(--wsx-form-button-color) !important;
  font-size: var(--wsx-form-button-font-size) !important;
  font-weight: var(--wsx-form-button-weight);
  font-style: normal;
  line-height: 1.6em !important;
  text-transform: var(--wsx-form-button-case-transform);
  display: inline-block;
  text-align: center;
  box-sizing: border-box;
  padding-block: 0px !important;
  padding-inline: 0px !important;
  width: 80%; }

.wsx-editable.wsx-register-btn:hover input {
  color: var(--wsx-form-button-hover-color) !important; }

.wsx-editable.wsx-login-btn svg {
  fill: var(--wsx-login-form-button-color) !important; }
  .wsx-editable.wsx-login-btn svg path {
    stroke: var(--wsx-login-form-button-color) !important; }

.wsx-editable.wsx-login-btn input {
  font-size: var(--wsx-form-button-font-size) !important;
  font-weight: var(--wsx-form-button-weight);
  font-style: normal;
  line-height: 1.6em !important;
  color: var(--wsx-login-form-button-color) !important;
  text-align: center !important;
  text-transform: var(--wsx-form-button-case-transform);
  display: inline-block;
  box-sizing: border-box;
  padding-block: 0px !important;
  padding-inline: 0px !important;
  width: 80%; }

.wsx-editable.wsx-login-btn:hover input {
  color: var(--wsx-login-form-button-hover-color) !important; }

.wsx-login-form-title-text.wsx-editable .wsx-editable-area {
  font-size: var(--wsx-login-title-font-size) !important;
  font-weight: var(--wsx-login-title-font-weight);
  font-style: normal;
  line-height: 1.6em !important;
  color: var(--wsx-login-title-color);
  text-transform: var(--wsx-login-title-case-transform);
  width: 100%;
  padding-block: 0px !important;
  padding-inline: 0px !important; }

.wholesalex-login-form-subtitle-text.wsx-editable .wsx-editable-area {
  width: 100%;
  color: var(--wsx-login-description-color);
  font-size: var(--wsx-login-description-font-size) !important;
  font-weight: var(--wsx-login-description-font-weight);
  font-style: normal;
  line-height: 1.6em !important;
  text-transform: var(--wsx-login-description-case-transform);
  padding-block: 0px !important;
  padding-inline: 0px !important; }

.wsx-editable {
  display: flex;
  align-items: center; }

.wsx-login-form-title-text.wsx-editable {
  width: calc(100% - 34px); }
  .wsx-login-form-title-text.wsx-editable svg {
    fill: var(--wsx-login-title-color); }
    .wsx-login-form-title-text.wsx-editable svg path {
      stroke: var(--wsx-login-title-color); }

.wholesalex-login-form-subtitle-text.wsx-editable {
  width: calc(100% - 34px); }
  .wholesalex-login-form-subtitle-text.wsx-editable svg {
    fill: var(--wsx-login-description-color); }
    .wholesalex-login-form-subtitle-text.wsx-editable svg path {
      stroke: var(--wsx-login-description-color); }

.wsx-reg-form-heading-text.wsx-editable .wsx-editable-area {
  color: var(--wsx-reg-title-color);
  font-size: var(--wsx-reg-title-font-size) !important;
  font-weight: var(--wsx-reg-title-font-weight);
  font-style: normal;
  line-height: 1.6em !important;
  text-transform: var(--wsx-reg-title-case-transform) !important;
  width: 100%;
  padding-block: 0px !important;
  padding-inline: 0px !important; }

.wsx-reg-form-heading-text.wsx-editable {
  width: calc(100% - 34px); }
  .wsx-reg-form-heading-text.wsx-editable svg {
    fill: var(--wsx-reg-title-color); }
    .wsx-reg-form-heading-text.wsx-editable svg path {
      stroke: var(--wsx-reg-title-color); }

.wholesalex-registration-form-subtitle-text.wsx-editable .wsx-editable-area {
  color: var(--wsx-reg-description-color);
  font-size: var(--wsx-reg-description-font-size) !important;
  font-weight: var(--wsx-reg-description-font-weight);
  font-style: normal;
  line-height: 1.6em !important;
  text-transform: var(--wsx-reg-description-case-transform);
  width: 100%;
  padding-block: 0px !important;
  padding-inline: 0px !important; }

.wholesalex-registration-form-subtitle-text.wsx-editable {
  width: calc(100% - 34px); }
  .wholesalex-registration-form-subtitle-text.wsx-editable svg {
    fill: var(--wsx-reg-description-color); }
    .wholesalex-registration-form-subtitle-text.wsx-editable svg path {
      stroke: var(--wsx-reg-description-color); }

.wsx-reg-form-heading .wholesalex-form-builder-toogle,
.wholesalex-login-form-title .wholesalex-form-builder-toogle {
  visibility: visible; }

.wsx-reg-form-heading .dashicons.dashicons-admin-generic,
.wholesalex-login-form-title .dashicons.dashicons-admin-generic {
  cursor: pointer;
  height: 18px;
  font-size: 18px;
  border: 1px solid #E4E4FF;
  border-radius: 2px;
  background: #FAFAFF;
  position: absolute !important;
  right: 16px !important;
  padding: 5px; }
  .wsx-reg-form-heading .dashicons.dashicons-admin-generic.wsx-active, .wsx-reg-form-heading .dashicons.dashicons-admin-generic.is-active,
  .wholesalex-login-form-title .dashicons.dashicons-admin-generic.wsx-active,
  .wholesalex-login-form-title .dashicons.dashicons-admin-generic.is-active {
    color: #6C6CFF !important; }
  .wsx-reg-form-heading .dashicons.dashicons-admin-generic:hover,
  .wholesalex-login-form-title .dashicons.dashicons-admin-generic:hover {
    background: #E4E4FF; }

.wsx-reg-form-heading .wholesalex-popup-content.wholesalex-form-title-style,
.wholesalex-login-form-title .wholesalex-popup-content.wholesalex-form-title-style {
  display: flex;
  gap: 48px; }

.wsx-reg-form-heading .wholesalex-form-title-style-column-heading,
.wholesalex-login-form-title .wholesalex-form-title-style-column-heading {
  color: #6C6E77;
  border-bottom: 1px solid #E9E9F0;
  margin-bottom: 16px; }

.wsx-reg-form-heading .wholesalex-form-title-style .wholesalex-style-formatting-field,
.wholesalex-login-form-title .wholesalex-form-title-style .wholesalex-style-formatting-field {
  border: none;
  padding: 0;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px; }

.wsx-reg-form-heading select.wholesalex-style-formatting-field-content,
.wholesalex-login-form-title select.wholesalex-style-formatting-field-content {
  height: 36px; }

.wsx-reg-form-heading .wholesalex-form-title-style-column,
.wholesalex-login-form-title .wholesalex-form-title-style-column {
  flex: 1; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .wholesalex-form-title-style-row,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .wholesalex-form-title-style-row {
    margin-bottom: 16px; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .wholesalex-form-title-style-row .wholesalex-form-builder-select-color,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .wholesalex-form-title-style-row .wholesalex-form-builder-select-color {
    border: none;
    width: 100%; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .wholesalex-text-transform-control__buttons,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .wholesalex-text-transform-control__buttons {
    height: 36px; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .wholesalex-style-formatting-field,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .wholesalex-style-formatting-field {
    height: unset; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .wholesalex-style-formatting-field-content,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .wholesalex-style-formatting-field-content {
    width: 100%;
    max-width: unset;
    justify-content: space-between; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .wholesalex-size-selector input[type=number],
  .wholesalex-login-form-title .wholesalex-form-title-style-column .wholesalex-size-selector input[type=number] {
    height: 36px;
    width: 100%; }
  .wsx-reg-form-heading .wholesalex-form-title-style-column .increment,
  .wsx-reg-form-heading .wholesalex-form-title-style-column .decrement,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .increment,
  .wholesalex-login-form-title .wholesalex-form-title-style-column .decrement {
    height: 36px;
    width: 36px;
    display: flex !important;
    justify-content: center;
    align-items: center; }
    .wsx-reg-form-heading .wholesalex-form-title-style-column .increment svg,
    .wsx-reg-form-heading .wholesalex-form-title-style-column .decrement svg,
    .wholesalex-login-form-title .wholesalex-form-title-style-column .increment svg,
    .wholesalex-login-form-title .wholesalex-form-title-style-column .decrement svg {
      margin: 0px;
      width: 20px;
      height: 20px; }

/* ===============================  Login & Registration Header End ================================*/
/* ===============================  Sidebar Pre-made Popup Start ================================*/
.wsx-reg-premade {
  width: 100%;
  height: 100%;
  z-index: 999;
  background-color: rgba(52, 58, 70, 0.5);
  padding: 40px 40px 40px 200px;
  box-sizing: border-box;
  position: fixed !important;
  top: 0px !important;
  left: 0px !important;
  display: flex !important;
  justify-content: center;
  align-items: center; }

.wsx-reg-premade_container {
  max-width: 1280px;
  background: #fff;
  max-height: 1011px;
  overflow-y: scroll;
  -ms-overflow-style: none;
  /* IE 11 */
  scrollbar-width: none;
  /* Firefox 64 */
  width: 100%;
  height: 100%;
  border-radius: 16px; }
  .wsx-reg-premade_container::-webkit-scrollbar {
    display: none; }
  .wsx-reg-premade_container .wsx-reg-premade_heading {
    color: #343A46;
    font-size: 20px !important;
    font-weight: 500;
    font-style: normal;
    line-height: 28px !important;
    background-color: #F6F6FF;
    padding: 13px 24px;
    position: sticky !important;
    top: 0px !important;
    z-index: 999; }
  .wsx-reg-premade_container .wsx-reg-premade_close {
    cursor: pointer;
    display: block;
    background: rgba(52, 58, 70, 0.1);
    padding: 0px;
    line-height: 0px;
    padding: 4px;
    border-radius: 4px; }
    .wsx-reg-premade_container .wsx-reg-premade_close:hover path {
      stroke: #6c6cff; }

.wsx-reg-premade__body {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 30px;
  padding: 24px;
  animation: premadeOverflowAnimation 1s; }
  .wsx-reg-premade__body .wsx-premade-item {
    border-radius: 4px 4px 0px 0px;
    border: 1px solid rgba(228, 228, 255, 0.94);
    background: #FCFCFC;
    text-align: center;
    max-width: 390px;
    width: 100%;
    margin: 0 auto; }
    .wsx-reg-premade__body .wsx-premade-item:hover {
      box-shadow: 0px 16px 36px 0px rgba(108, 108, 255, 0.12); }
    .wsx-reg-premade__body .wsx-premade-item .wsx-premade-media {
      text-align: center;
      height: 220px;
      display: flex !important;
      justify-content: center;
      align-items: center;
      padding: 16px;
      cursor: pointer; }
      .wsx-reg-premade__body .wsx-premade-item .wsx-premade-media img {
        height: 100%;
        width: 100%; }

.wsx-premade-content {
  text-align: left;
  color: #6C6E77;
  border-radius: 4px 4px 0px 0px;
  border: 1px solid #E5E5FF;
  background: #F6F6FF;
  padding: 10px;
  flex-wrap: wrap;
  gap: 10px;
  border-bottom: 0px;
  border-left: 0px;
  border-right: 0px; }
  .wsx-premade-content .wsx-premade-action {
    display: flex !important;
    justify-content: flex-end;
    align-items: center;
    gap: 8px; }
    .wsx-premade-content .wsx-premade-action .wsx-premade-template {
      color: #FFF;
      border-radius: 2px;
      padding: 7px 20px;
      box-sizing: border-box;
      background: linear-gradient(180deg, #6C6CFF 0%, #4747D9 100%);
      cursor: pointer; }
      .wsx-premade-content .wsx-premade-action .wsx-premade-template:hover {
        color: #dcdce7; }
    .wsx-premade-content .wsx-premade-action .wsx-premade-upgrade {
      color: #FFF;
      border-radius: 2px;
      padding: 7px 20px;
      box-sizing: border-box;
      background-image: linear-gradient(to bottom, #ff9336, #de521e);
      text-decoration: none;
      cursor: pointer; }
      .wsx-premade-content .wsx-premade-action .wsx-premade-upgrade:hover {
        color: #dcdce7; }

@keyframes premadeOverflowAnimation {
  0% {
    transform: translate(0px, -249px); }
  100% {
    transform: translate(0px, 0px); } }

@media only screen and (max-width: 1024px) {
  .wsx-reg-premade__body {
    grid-template-columns: 1fr 1fr !important; } }

@media only screen and (max-width: 960px) {
  .wsx-reg-premade {
    padding: 40px !important; } }

@media only screen and (max-width: 768px) {
  .wsx-reg-premade__body {
    grid-template-columns: 1fr !important; } }

.wholesalex-form-builder-style-formatting-controller {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 100vh;
  padding-bottom: 100px; }
  .wholesalex-form-builder-style-formatting-controller img {
    max-width: 100%; }
  .wholesalex-form-builder-style-formatting-controller .wholesalex-premade-btn {
    border: unset;
    border-radius: 2px;
    background: linear-gradient(180deg, #9DC2FF 0.21%, #6C6CFF 100.21%);
    height: 40px;
    display: flex !important;
    justify-content: center;
    align-items: center;
    width: 268px;
    gap: 8px;
    width: 100%;
    margin-top: 16px;
    cursor: pointer; }
    .wholesalex-form-builder-style-formatting-controller .wholesalex-premade-btn:hover {
      background: linear-gradient(180deg, #678ac2 0.21%, var(--color-primary-hover) 100.21%); }
    .wholesalex-form-builder-style-formatting-controller .wholesalex-premade-btn-label {
      font-size: 14px !important;
      font-weight: 500;
      font-style: normal;
      line-height: 22px !important;
      color: white; }
  .wholesalex-form-builder-style-formatting-controller .typography-field .wholesalex_slider_field {
    display: flex !important;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px; }
  .wholesalex-form-builder-style-formatting-controller .wsx-component-section__title {
    font-size: 14px !important;
    font-weight: 500;
    font-style: normal;
    line-height: 22px !important;
    background-color: #F6F6FF;
    padding-top: 4px !important;
    padding-bottom: 4px !important; }
  .wholesalex-form-builder-style-formatting-controller > div:not(.wsx-component-section),
  .wholesalex-form-builder-style-formatting-controller .wsx-component-section__title {
    padding-left: 16px;
    padding-right: 16px; }
  .wholesalex-form-builder-style-formatting-controller .wsx-component-section__body > div {
    padding-left: 16px;
    padding-right: 16px; }
  .wholesalex-form-builder-style-formatting-controller .wsx-component-panel.wsx-component-panel__open .wsx-component-panel__title {
    border: none !important; }
  .wholesalex-form-builder-style-formatting-controller .wsx-component-panel .wsx-component-panel__title {
    font-size: 12px !important;
    font-weight: 500;
    font-style: normal;
    line-height: 16px !important;
    cursor: pointer;
    margin: 12px 0px;
    padding: 0px 16px 12px;
    border-bottom: 1px solid #E6E5E5; }
    .wholesalex-form-builder-style-formatting-controller .wsx-component-panel .wsx-component-panel__title .wsx-component-panen__icon {
      display: flex; }
  .wholesalex-form-builder-style-formatting-controller .wsx-control-tab .wsx-component-toggle-group-control-options {
    background-color: #E9E9F0; }
    .wholesalex-form-builder-style-formatting-controller .wsx-control-tab .wsx-component-toggle-group-control-options .wsx-component-toggle-group-control-options__option {
      padding: 6px 13px;
      width: 100%;
      text-align: center;
      cursor: pointer;
      transition: .3s; }
      .wholesalex-form-builder-style-formatting-controller .wsx-control-tab .wsx-component-toggle-group-control-options .wsx-component-toggle-group-control-options__option[data-active-item~='true'] {
        color: #fff;
        background-color: #343A46; }
  .wholesalex-form-builder-style-formatting-controller .wsx-control-state .wsx-component-toggle-group-control-options > div,
  .wholesalex-form-builder-style-formatting-controller .wsx-control-alignment .wsx-component-toggle-group-control-options > div {
    padding: 4px 8px;
    border: 1px solid #6C6E77;
    transition: .3s;
    cursor: pointer; }
    .wholesalex-form-builder-style-formatting-controller .wsx-control-state .wsx-component-toggle-group-control-options > div:not(:last-child),
    .wholesalex-form-builder-style-formatting-controller .wsx-control-alignment .wsx-component-toggle-group-control-options > div:not(:last-child) {
      border-right: 0px; }
    .wholesalex-form-builder-style-formatting-controller .wsx-control-state .wsx-component-toggle-group-control-options > div:hover, .wholesalex-form-builder-style-formatting-controller .wsx-control-state .wsx-component-toggle-group-control-options > div[data-active-item~='true'],
    .wholesalex-form-builder-style-formatting-controller .wsx-control-alignment .wsx-component-toggle-group-control-options > div:hover,
    .wholesalex-form-builder-style-formatting-controller .wsx-control-alignment .wsx-component-toggle-group-control-options > div[data-active-item~='true'] {
      color: #fff;
      background-color: #6C6E77; }

.wsx-component-panel__body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px solid #E6E5E5;
  animation: wsxAccordingAnimation .3s; }
  .wsx-component-panel__body > div {
    padding: 0px 16px; }
  .wsx-component-panel__body input,
  .wsx-component-panel__body select {
    font-size: 12px !important;
    font-weight: 400;
    font-style: normal;
    line-height: 1.6em !important; }

@keyframes wsxAccordingAnimation {
  0% {
    opacity: 0; }
  100% {
    opacity: 1; } }

.wsx-control-increment .wholesalex-style-formatting-field-content {
  border: 1px solid #E9E9F0;
  align-items: stretch !important;
  border-radius: 1px;
  height: 24px; }
  .wsx-control-increment .wholesalex-style-formatting-field-content input {
    border: 0px;
    padding: 0px;
    border-radius: 0px;
    text-align: center;
    min-height: auto;
    -moz-appearance: textfield; }
    .wsx-control-increment .wholesalex-style-formatting-field-content input::-webkit-inner-spin-button, .wsx-control-increment .wholesalex-style-formatting-field-content input::-webkit-outer-spin-button {
      -webkit-appearance: none; }
  .wsx-control-increment .wholesalex-style-formatting-field-content span {
    display: flex !important;
    justify-content: center;
    align-items: center;
    padding: 5px;
    cursor: pointer;
    background-color: #D9D9D9;
    margin: 1px;
    transition: .3s; }
    .wsx-control-increment .wholesalex-style-formatting-field-content span:hover {
      background-color: #c1c1c1; }

.wholesalex-select-field select.wholesalex-style-formatting-field-content {
  width: 100%;
  padding: 0px 6px;
  line-height: normal;
  border-radius: 0px !important; }

.wholesalex-text-transform-control .wholesalex-style-formatting-field-content {
  align-items: stretch;
  justify-content: space-between;
  gap: 0px;
  border-radius: 1px;
  border: 1px solid #E9E9F0;
  padding: 0px; }
  .wholesalex-text-transform-control .wholesalex-style-formatting-field-content .wholesalex-text-transform-control__button {
    display: flex;
    align-items: center;
    height: -webkit-fill-available;
    padding: 3px; }
    .wholesalex-text-transform-control .wholesalex-style-formatting-field-content .wholesalex-text-transform-control__button:hover {
      background: #D9D9D9; }

.wsx-form-control_hide {
  cursor: pointer;
  margin-left: -25px;
  background: white;
  border-radius: 4px 0px 0px 4px;
  border-style: solid;
  border-width: 1px 0px 1px 1px;
  padding: 7px 2px;
  border-color: #e6e5e5;
  position: fixed !important;
  top: 50% !important;
  z-index: 9999999; }
  .wsx-form-control_hide > span {
    transform: rotate(180deg);
    transition: .5s; }

.wholesalex-form-builder__typography-setting > div:not(.wsx-form-control_hide) {
  transition: 2s;
  opacity: 1; }

.wsx-sidebar-hide {
  width: 0%;
  max-width: 0px;
  transition: .5s; }
  .wsx-sidebar-hide .wholesalex-form-builder__typography-setting > div:not(.wsx-form-control_hide) {
    opacity: 0;
    transition: .1s; }
  .wsx-sidebar-hide .wsx-form-control_hide > span {
    transform: rotate(0deg);
    animation: wsxSideBarIconRotate .5s; }

/* ===============================  Sidebar Pre-made Popup End ================================*/
@keyframes wsxSideBarIconRotate {
  0% {
    transform: rotate(180deg); }
  100% {
    transform: rotate(0deg); } }

.wholesalex-popup-heading.wsx-extra-field-popup, .wholesalex-popup-heading.wsx-field-wc-options-popup {
  justify-content: flex-start;
  gap: 10px; }
  .wholesalex-popup-heading.wsx-extra-field-popup .dashicons.dashicons-lock, .wholesalex-popup-heading.wsx-field-wc-options-popup .dashicons.dashicons-lock {
    color: #6c6cff;
    cursor: pointer; }

.wsx-field-condition-tab .dashicons.dashicons-lock {
  color: #6c6cff;
  cursor: pointer; }

.wholesalex-form-builder-dropdown-content .components-popover__content {
  width: 100% !important; }

.wsx-field-insert-popup {
  z-index: 99 !important; }

.wsx-field-condition-popup .pro_popup_container {
  max-width: fit-content !important; }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/CommonMixin.scss","webpack://./reactjs/src/assets/scss/Editor.scss"],"names":[],"mappings":"AAAA;;0CCE0C;ADM1C;;sCCHsC;ADkBtC;;oDCfoD;AAPpD;;iCAUiC;AAPjC;EDQI,0BAA2B;EAC3B,gBAF+C;EAG/C,kBAAkB;EAEd,6BAAmC,EAAA;;ACT3C;EDKI,0BAA2B;EAC3B,gBCLqC;EDMrC,kBAAkB;EAEd,4BAAmC,EAAA;;ACN3C;EDEI,0BAA2B;EAC3B,gBCFqC;EDGrC,kBAAkB;EAEd,4BAAmC,EAAA;;ACH3C;EACI,2BAA2B,EAAA;;AAE/B;EDZI,wBAAwB;EACxB,8BCYoC;EDXpC,mBCW4C,EAAA;;AAEhD,gGAAA;AAEA,mGAAA;AACA;EACI,aAAa;EACb,YAAY,EAAA;EAFhB;IAIQ,eAAe,EAAA;EAJvB;IAOQ,mBAAmB;IACnB,iBAAiB,EAAA;EARzB;IAWQ,kBAAkB;IAClB,sBAAsB;IACtB,kBAAkB,EAAA;EAb1B;IAgBQ,kBAAkB,EAAA;EAhB1B;;;;;;IAuBY,2BAA2B,EAAA;;AAIvC;;;EAKY,2BAA2B,EAAA;;AALvC;;;;;;EASY,gBAAgB,EAAA;;AAT5B;;ED7CI,wBAAwB;EACxB,yBC2DsC;ED1DtC,mBC0D8C;EACtC,sBAAsB,EAAA;;AAIlC,iGAAA;AAEA,iGAAA;AACA;EDpEI,wBAAwB;EACxB,2BCoEgC;EDnEhC,mBCmEwC;EACxC,sBAAsB;EACtB,iBAAiB,EAAA;;AAErB;EACI,WAAW,EAAA;EADf;IAGQ,eAAe;IACf,gBAAgB;IAChB,kBAAkB;IAClB,gBAAgB;IAChB,gCAAgC,EAAA;IAPxC;MASY,cAAc;MD1EtB,0BAA2B;MAC3B,gBC0E6C;MDzE7C,kBAAkB;MAEd,4BAAmC,EAAA;IC6D3C;MAaY,aAAa;MACb,SAAS,EAAA;;AAIrB;EACI,cAAc;EACd,kBAAkB;EAClB,gBAAgB;EAChB,iBAAiB;EACjB,iBAAiB;EACjB,qBAAqB;EACrB,WAAW,EAAA;EAPf;IAUY,aAAa,EAAA;;AAIzB;;EAEI,OAAO,EAAA;EAFX;;IAIQ,kBAAkB;IAClB,uBAAuB;IACvB,sBAAsB;IACtB,6BAA6B;IAC7B,eAAe,EAAA;EARvB;;IAYQ,uBAAuB,EAAA;EAZ/B;;IAeQ,QAAQ,EAAA;EAfhB;;IAkBQ,QAAQ,EAAA;EAlBhB;;IAsBQ,aAAa,EAAA;IAtBrB;;MAwBY,eAAe,EAAA;EAxB3B;;IDlFI,6BAA2B;IAEvB,mBAAoB;IAMpB,sBAAwB,EAAA;EC0EhC;;IA+BQ,8BAA8B;IAC9B,yBAAyB;IACzB,qCAAqC,EAAA;EAjC7C;;IDzGI,wBAAwB;IACxB,2BC6IoC;ID5IpC,qBC4I8C;IAC1C,QAAQ,EAAA;IAtChB;;MAwCY,OAAO,EAAA;IAxCnB;;MA2CY,YAAY,EAAA;;AAIxB;EAEQ,YAAY;EACZ,gBAAgB,EAAA;;AAHxB;EAMQ,OAAO,EAAA;EANf;IAQY,0BAA0B,EAAA;;AAItC;EAGY,uBAAuB;EACvB,uBAAuB;EACvB,gBAAgB,EAAA;;AAL5B;EAUoB,6BAA6B,EAAA;;AAVjD;EAkBgB,gBAAgB,EAAA;;AAlBhC;EAuBQ,YAAY,EAAA;;AAvBpB;EA0BQ,8BAA8B,EAAA;;AA1BtC;EA6BQ,kBAAkB,EAAA;EA7B1B;ID7II,6BAA2B;IAEvB,qBAAoB,EAAA;;AC2I5B;EDpKI,wBAAwB;EACxB,8BCsMuC;EDrMvC,oBCqMgD;EAC5C,QAAQ,EAAA;EApChB;IAsCY,WAAW,EAAA;EAtCvB;IAyCY,yBAAyB,EAAA;EAzCrC;IA4CY,kBAAkB,EAAA;IA5C9B;MA8CgB,eAAe,EAAA;IA9C/B;MAiDgB,WAAW;MACX,YAAY;MD/LxB,6BAA2B;MAEvB,mBAAoB,EAAA;IC2I5B;MD7II,6BAA2B;MAEvB,mBAAoB;MAMpB,qBAAwB;MC4LhB,UAAU,EAAA;IAvD1B;MA0DgB,uBAAuB,EAAA;EA1DvC;;IA+DY,6BAA6B;IAC7B,yBAAyB;IACzB,YAAY;IACZ,eAAe;IACf,kBAAkB,EAAA;;AAI9B;EAEQ,YAAY;EACZ,sBAAsB;EACtB,yBAAyB;EACzB,yBAAyB,EAAA;EALjC;IAOY,gBAAgB,EAAA;EAP5B;IAUY,4BAA4B,EAAA;EAVxC;IAaY,8BAA8B,EAAA;EAb1C;IAgBY,8BAA8B,EAAA;EAhB1C;IAmBY,yBAAyB,EAAA;;AAnBrC;EAuBQ,YAAY,EAAA;;AAGpB;EACI,kBAAkB,EAAA;;AAEtB,+FAAA;AAEA,2FAAA;AACA;EACI,eAAe;EACf,WAAW;EACX,gBAAgB,EAAA;;AAEpB;EACI,cAAc;EACd,6BAA6B;EAsB7B,wDAAA;EAwBA,sDAAA,EAAuD;EAhD3D;IAG2B,UAAA;IACnB,UAAU;IACV,kBAAkB,EAAA;EAL1B;IAOmE,WAAA;IAC3D,mBAAmB,EAAA;EAR3B;IAWQ,aAAa;IACb,sBAAsB;IACtB,gBAAgB;IAChB,kBAAkB;IAClB,yBAAyB;IACzB,kBAAkB;IAClB,YAAY,EAAA;IAjBpB;MDhRI,wBAAwB;MACxB,uBCkSoC;MDjSpC,mBCiS4C;MACpC,SAAS;MACT,eAAe,EAAA;EArB3B;IA0BQ,sBAAwB;IDlS5B,0BAA2B;IAC3B,gBCkSyC;IDjSzC,kBAAkB;IAEd,4BAAmC;ICgSnC,kBAAkB,EAAA;EA5B1B;IA+BQ,kBAAkB;IAClB,uBAAuB,EAAA;IAhC/B;MAkCY,eAAe;MACf,YAAY;MACZ,eAAe;MACf,gBAAgB;MAChB,6BAA6B,EAAA;EAtCzC;IA0CQ,eAAe,EAAA;EA1CvB;IA6CQ,aAAa;IACb,sBAAsB,EAAA;;AAI9B;EAEQ,WAAW;EACX,iBAAiB;EACjB,gBAAgB;EAChB,gBAAgB;EAChB,kBAAkB;EAClB,yBAAyB;EACzB,sBAAsB;EACtB,0CAAyC;EACzC,cAAc;EDrTlB,6BAA2B;EAEvB,gCAAoB;EAMpB,qBAAwB;EC+SxB,aAAa;EACb,eAAe,EAAA;EAbvB;IAeY,eAAe;IACf,6BAA6B;IAC7B,eAAe;IACf,sBAAsB;IACtB,wCAAwC;ID7UhD,0BAA2B;IAC3B,gBC6U6C;ID5U7C,kBAAkB;IAEd,6BAAmC,EAAA;ICsT3C;MAsBgB,iBAAiB;MACjB,qBAAqB,EAAA;IAvBrC;MA0BgB,qBAAqB,EAAA;EA1BrC;IA8BY,UAAU;IACV,6BAA6B;IAC7B,gBAAgB,EAAA;EAhC5B;IAoCgB,UAAU;IACV,gBAAgB;IAChB,WAAW,EAAA;EAtC3B;IAyCgB,iBAAiB,EAAA;EAzCjC;IA4CgB,yBAAyB,EAAA;;AAMzC;EACI,8BAA8B;EAC9B,sBAAsB;EACtB,gBAAgB;EAChB,eAAe,EAAA;EAJnB;IAMQ,mBAAmB;IACnB,gBAAgB,EAAA;IAPxB;MASY,qBAAqB,EAAA;EATjC;IAcY,iCAAiC,EAAA;;AAI7C;EACI,WAAW;EACX,gBAAgB;EAChB,sBAAsB;EACtB,8BAA8B;EAC9B,iBAAiB;EACjB,kBAAkB;EDrXlB,2BAA2B;EAEvB,oBAAoB,EAAA;EC6W5B;IASQ,sBAAsB;IACtB,gBACJ,EAAA;EAXJ;ID/WI,0BAA2B;IAEvB,iBAAoB;IAGpB,kBAAsB;IAGtB,mBAAwB;ICqXxB,SAAS;IACT,2BACJ,EAAA;EAhBJ;IAkBQ,gBAAgB;IAChB,kBAAkB;IAClB,4IAAsI;IACtI,sBAAsB;IACtB,kBACJ,EAAA;;AAEJ,yFAAA;AAEA,iHAAA;AAEA;EACI,uBAAuB,EAAA;;AAE3B;EACI,eAAe;EACf,sCAAsC,EAAA;;AAE1C;EACI,aAAa;EACb,QAAQ,EAAA;;AAEZ;EACI,cAAc,EAAA;;AAElB;EDjbI,wBAAwB;EACxB,8BCibmC;EDhbnC,mBCgb2C;EAC3C,iBAAiB;EACjB,sBAAsB;EACtB,gBAAgB,EAAA;;AAEpB;EDvbI,wBAAwB;EACxB,2BCubgC;EDtbhC,mBCsbwC;EACxC,0BAA0B;EAC1B,sBAAsB,EAAA;EAH1B;IAKQ,WAAW,EAAA;;AAGnB;EACI,cAAc;EACd,iBAAiB,EAAA;;AAErB;EAEQ,YAAY;EACZ,iBAAiB;EACjB,eAAe;EACf,eAAe;EACf,0BAA0B;EAC1B,WAAW;EACX,kBAAkB;EAClB,YAAY;EACZ,YAAY;EACZ,kBAAkB,EAAA;EAX1B;IAcY,wBAAwB;IACxB,SAAS,EAAA;EAfrB;IAkBY,iBAAiB,EAAA;;AAlB7B;EAsBQ,YAAY;EACZ,iBAAiB;EACjB,eAAe;EACf,WAAW;EACX,0BAA0B;EAC1B,WAAW;EACX,kBAAkB;EAClB,YAAY;EACZ,YAAY;EACZ,kBAAkB,EAAA;EA/B1B;IAkCY,wBAAwB;IACxB,SAAS,EAAA;EAnCrB;IAsCY,iBAAiB,EAAA;;AAtC7B;;EA2CQ,YAAY;EACZ,WAAW;EACX,kBAAkB;EAClB,mBAAmB;EACnB,eAAe,EAAA;EA/CvB;;IAiDY,mBAAmB;IACnB,YAAY;IACZ,qBAAqB,EAAA;IAnDjC;;MAqDgB,aAAa,EAAA;EArD7B;;IAyDY,eAAe;IACf,gBAAgB,EAAA;EA1D5B;;IA6DY,WAAW,EAAA;;AAIvB;;EAEI,cAAc;EACd,WAAW;EACX,0BAA0B,EAAA;;AAE9B;EDlgBI,0BAA2B;EAC3B,gBAF+C;EAG/C,kBAAkB;EAEd,6BAAmC,EAAA;;ACigB3C;EACI,cAAc;EACd,gBAAgB;EAChB,yBAAyB,EAAA;;AAE7B;EACI,aAAa;EACb,QAAQ;EACR,YAAY;EACZ,WAAW;EACX,cAAc,EAAA;;AAGlB;EACI,eAAe;EACf,YAAY,EAAA;EAFhB;IAIQ,wBAAwB;IACxB,WAAW;IACX,YAAY,EAAA;EANpB;IAUY,2CAA2C,EAAA;EAVvD;IAcQ,oBAAoB;IACpB,mBAAmB,EAAA;;AAI3B;EACI,cAAc;EACd,YAAY;EACZ,WAAW;EACX,kBAAkB;EAClB,yBAAyB,EAAA;;AAE7B;EACI,iBAAiB,EAAA;EADrB;IAGQ,eAAe,EAAA;EAHvB;IAMQ,UAAU,EAAA;IANlB;MAQY,UAAU,EAAA;;AAItB;EAEQ,6BAA6B,EAAA;;AAFrC;EDhkBI,wBAAwB;EACxB,8BCokBuC;EDnkBvC,mBCmkB+C;EAC3C,2BAA2B;EAC3B,gBAAgB;EAChB,0BAA0B,EAAA;;AARlC;EAWQ,mBAAmB,EAAA;;AAX3B;EAcQ,6BAA6B;EAC7B,gBAAgB,EAAA;;AAfxB;EAkBQ,uBAAuB;EACvB,aAAa;EACb,kBAAkB,EAAA;EApB1B;IAsBY,iBAAiB;IACjB,WAAW;IACX,kBAAkB;IAClB,yBAAyB,EAAA;IAzBrC;MA2BgB,iBAAiB,EAAA;IA3BjC;MA+BgB,WAAW;MACX,yBAAyB,EAAA;;AAhCzC;;;;;EAyCQ,cAAc;EDjmBlB,0BAA2B;EAC3B,gBAF+C;EAG/C,kBAAkB;EAEd,6BAAmC,EAAA;;ACojB3C;EDhkBI,wBAAwB;EACxB,8BC4mBuC;ED3mBvC,mBC2mB+C;EAC3C,eAAe,EAAA;EA9CvB;IDhkBI,wBAAwB;IACxB,uBC+mBoC;ID9mBpC,mBC8mB4C;IACpC,2BAA2B;IAC3B,SAAS,EAAA;;AAlDrB;EAuDY,gBAAgB;EAChB,eAAe;EACf,WAAW,EAAA;;AAzDvB;EDhkBI,wBAAwB;EACxB,uBC2nBoC;ED1nBpC,uBC0nBgD;EACxC,mBAAmB;EACnB,WAAW,EAAA;;AA9DvB;EAiEY,WAAW,EAAA;;AAjEvB;EAsEY,sBAAsB;EACtB,eAAe;EACf,uBAAuB,EAAA;;AAxEnC;EA2EY,cAAc;EACd,kBAAkB;EAClB,kBAAkB,EAAA;;AAkB9B,8GAAA;AAEA,8GAAA;AACA;EACI,kBAAkB,EAAA;;AAEtB,qBAAA;AACA;EDtqBI,wBAAwB;EACxB,6BCsqBkC;EDrqBlC,uBCqqB8C;EAC9C,sBAAsB,EAAA;EAF1B;IAKQ,eAAe;IACf,WAAW;IACX,mBAAmB;IACnB,kBAAkB;IAClB,gBAAgB;IAChB,iBAAiB;IACjB,aAAa;IACb,6BAA6B;IAC7B,SAAS,EAAA;IAbjB;MAeY,2BAA2B,EAAA;IAfvC;MAkBY,4BAA4B,EAAA;IAlBxC;MAqBY,eAAe;MDnrBvB,0BAA2B;MAC3B,gBCmrB6C;MDlrB7C,kBAAkB;MAEd,4BAAmC;MCirB/B,0BAA0B;MAC1B,mBAAmB;MACnB,wBAAwB;MD/rBhC,wBAAwB;MACxB,uBC+rBoC;MD9rBpC,mBC8rB4C;MACpC,QAAQ;MACR,qBAAqB;MACrB,wBAAwB,EAAA;MA7BpC;QA+BgB,aAAa,EAAA;IA/B7B;MAmCY,WAAW;MACX,mBAAmB,EAAA;MApC/B;QAsCgB,wBAAwB;QACxB,kBAAkB,EAAA;QAvClC;UAyCoB,UAAU,EAAA;;AAM9B;EDrtBI,wBAAwB;EACxB,2BCqtBgC;EDptBhC,qBCotB0C;EAC1C,SAAS,EAAA;;AAEb;EACI,SAAS;ED1tBT,wBAAwB;EACxB,8BC0tBmC;EDztBnC,uBCytB+C;EAC/C,WAAW,EAAA;EAHf;IAKQ,WAAW,EAAA;;AAGnB;EACI,aAAa;EACb,sBAAsB;EACtB,WAAW;EACX,SAAS;EACT,uBAAuB;EACvB,gBAAgB;EAChB,sBAAsB;EAEtB,sBAAsB;EDntBtB,6BAA2B;EAEvB,qBAAoB,EAAA;ECwsB5B;;IAaQ,uBAAuB;IACvB,sBAAsB,EAAA;;AAG9B;ED1uBI,+CAA2B;EAC3B,gBC0uB0D;EDzuB1D,kBAAkB;EAEd,4BAAmC;ECwuBvC,0BAA0B;EAC1B,sBAAsB;EACtB,YAAY;EDtvBZ,wBAAwB;EACxB,uBCsvB4B;EDrvB5B,mBCqvBoC;EACpC,YAAY;EACZ,kBAAkB;EAClB,iBAAiB;EACjB,iDAAiD,EAAA;;AAErD;EDrvBI,+CAA2B;EAC3B,gBCqvB0D;EDpvB1D,kBAAkB;EAEd,4BAAmC;ECmvBvC,YAAY;EACZ,yBAAyB;EACzB,kBAAkB;EAClB,2BAA2B,EAAA;;AAE/B;EACI,wBAAwB,EAAA;;AAE5B;EACI,8BAA8B,EAAA;;AAElC;EACI,YAAY,EAAA;;AAEhB,4GAAA;AAEA,qGAAA;AACA;EACI,YAAY,EAAA;;AAEhB;EACI,eAAe,EAAA;EADnB;IAGQ,eAAe,EAAA;;AAGvB;EACI,YAAY;EACZ,cAAc,EAAA;;AAElB;EACI,eAAe;EACf,YAAY;EACZ,YAAY;EACZ,WAAW;EACX,eAAe;EACf,mBAAmB;EACnB,mBAAmB;EACnB,YAAY,EAAA;EARhB;IAUQ,yBAAyB,EAAA;EAVjC;IAaQ,6DAA6D,EAAA;;AAGrE;EACI,iBAAiB;EACjB,cAAc;EACd,8BAA8B;EAC9B,qCAAqC;EACrC,mBAAmB,EAAA;EALvB;ID7yBI,wBAAwB;IACxB,uBCmzBgC;IDlzBhC,mBCkzBwC;IACpC,SAAS,EAAA;;AAGjB;EACI,kBAAkB,EAAA;;AAEtB;EACI,kBAAkB;EAClB,gCAAgC;EAChC,iBAAiB,EAAA;EAHrB;IAKQ,YAAY;IACZ,WAAW,EAAA;EANnB;IASQ,gBAAgB,EAAA;EATxB;IAYQ,qBAAqB;IACrB,iBAAiB;IACjB,QAAQ;IACR,kBAAkB;IAClB,0CAA2C;IAC3C,qCAAqC;IACrC,iBAAiB;IACjB,kBAAkB;IAClB,eAAe,EAAA;IApBvB;MAsBY,oCAAqC,EAAA;EAtBjD;IA0BQ,2BAA2B,EAAA;;AAGnC;EACI,cAAc;EACd,gCAAgC;EAChC,mBAAmB,EAAA;;AAEvB;;EAEI,eAAe;EACf,kBAAkB;EAClB,uBAAuB;EACvB,uBAAuB;EACvB,8BAA8B;EAC9B,yBAAyB;EACzB,qCAAqC;EACrC,eAAe,EAAA;;AAKnB;EACI,wBAAwB,EAAA;;AAG5B;EACI,OAAO,EAAA;EADX;;IAIQ,YAAY;IACZ,WAAW;IDp3Bf,wBAAwB;IACxB,uBCo3BgC;IDn3BhC,mBCm3BwC,EAAA;IAN5C;;MAQY,WAAW;MACX,WAAW;MACX,YAAY,EAAA;EAVxB;IAcQ,mBAAmB,EAAA;EAd3B;IAiBQ,aAAa,EAAA;EAjBrB;IAoBQ,aAAa,EAAA;IApBrB;MAsBY,8BAA8B;MAC9B,wCAAwC,EAAA;EAvBpD;IA2BQ,YAAY;IACZ,kBAAkB,EAAA;EA5B1B;IA+BQ,YAAY,EAAA;EA/BpB;IAkCQ,WAAW;IACX,2BAA2B;IAC3B,8BAA8B;IAC9B,kBAAkB;IAClB,yBAAyB,EAAA;EAtCjC;IAyCQ,YAAY;IACZ,WAAW;IACX,0BAA0B;IAC1B,yBAAyB,EAAA;;AAGjC;EACI,kBAAkB;EAClB,yBAAyB;EACzB,gBAAgB;EAChB,uDAAuD;EACvD,YAAY;EACZ,iBAAiB;EACjB,cAAc,EAAA;EAPlB;IASQ,oBAAoB,EAAA;EAT5B;IAYQ,0BAA0B;IAC1B,SAAS,EAAA;EAbjB;IDv4BI,2BAA2B;IAEvB,mBAAoB;ICs5BpB,aAAa;IACb,yBAAoC;IDh7BxC,wBAAwB;IACxB,8BCg7BuC;ID/6BvC,mBC+6B+C,EAAA;IAnBnD;MAqBY,2BAA2B;MAC3B,kBAAkB,EAAA;EAtB9B;IA0BQ,UAAU;IACV,iBAAiB;IACjB,yBAAyB;IACzB,YAAY,EAAA;EA7BpB;IAgCQ,UAAU;IACV,YAAY;IACZ,yBAAyB,EAAA;EAlCjC;IAsCY,UAAU;IACV,YAAY;IACZ,yBAAyB,EAAA;;AAIrC;ED18BI,wBAAwB;EACxB,uBC08B4B;EDz8B5B,mBCy8BoC;EACpC,eAAe;EACf,gBAAgB;EAChB,gCAAgC;EAChC,kBAAkB;EAClB,cAAc;EACd,gBAAgB;EDz8BhB,0BAA2B;EAC3B,gBCy8BqC;EDx8BrC,kBAAkB;EAEd,4BAAmC;EAWvC,2BAA2B;EAEvB,mBAAoB;EC27BxB,YAAY;EACZ,sBAAsB,EAAA;;AAE1B;EACI,aAAa;EACb,SAAS,EAAA;;AAEb;EACI,YAAY;EACZ,UAAU;EACV,sBAAsB;EACtB,uBAAuB;EACvB,QAAQ,EAAA;;AAEZ;EACI,gBAAgB;EAChB,iBAAiB,EAAA;;AAErB;;EAEI,WAAW;EDx+BX,wBAAwB;EACxB,uBCw+B4B;EDv+B5B,mBCu+BoC;EACpC,kBAAkB;EAClB,0BAA0B;EAC1B,aAAa;EACb,kBAAkB;EAClB,sBAAsB,EAAA;;AAE1B;EACI;IAAK,WAAW,EAAA;EAChB;IAAO,QAAQ,EAAA,EAAA;;AAEnB;EACI,YAAY,EAAA;;AAEhB;EACI,YAAY;EACZ,WAAW;EACX,2BAA2B,EAAA;EAH/B;IAKQ,8BAA8B,EAAA;EALtC;IAQQ,YAAY;IACZ,WAAW;IACX,kBAAkB;IAClB,yBAAyB,EAAA;EAXjC;IAcQ,yBAAyB;IACzB,cAAc;IACd,YAAY;IACZ,kBAAkB;IAClB,WAAW;IACX,YAAY,EAAA;;AAGpB;EACI,wCAAwC;EACxC,8BAA8B;EAC9B,4BAA4B,EAAA;;AAEhC;EAEQ,6CAA6C,EAAA;EAFrD;IAIY,+CAA+C,EAAA;;AAI3D;EAEQ,6BAA6B;EAC7B,8CAA8C;EDrhClD,sDAA2B;EAC3B,0CCqhCgG;EDphChG,kBAAkB;EAEd,6BAAmC;ECmhCnC,qDAAqD;EACrD,qBAAqB;EACrB,kBAAkB;EAClB,sBAAsB;EACtB,6BAA6B;EAC7B,8BAA8B;EAC9B,UAAU,EAAA;;AAXlB;EAcQ,oDAAoD,EAAA;;AAG5D;EAEQ,mDAAmD,EAAA;EAF3D;IAIY,qDAAqD,EAAA;;AAIjE;ED3iCI,sDAA2B;EAC3B,0CC4iCgG;ED3iChG,kBAAkB;EAEd,6BAAmC;EC0iCnC,oDAAoD;EACpD,6BAA6B;EAC7B,qDAAqD;EACrD,qBAAqB;EACrB,sBAAsB;EACtB,6BAA6B;EAC7B,8BAA8B;EAC9B,UAAU,EAAA;;AAVlB;EAaQ,0DAA0D,EAAA;;AAGlE;ED3jCI,sDAA2B;EAC3B,+CC2jCiG;ED1jCjG,kBAAkB;EAEd,6BAAmC;ECyjCvC,mCAAmC;EACnC,qDAAqD;EACrD,WAAW;EACX,6BAA6B;EAC7B,8BAA8B,EAAA;;AAElC;EACI,WAAW;EACX,yCAAyC;EDrkCzC,4DAA2B;EAC3B,qDCqkC6G;EDpkC7G,kBAAkB;EAEd,6BAAmC;ECmkCvC,2DAA2D;EAC3D,6BAA6B;EAC7B,8BAA8B,EAAA;;AAElC;EACI,aAAa;EACb,mBAAmB,EAAA;;AAEvB;EACI,wBAAwB,EAAA;EAD5B;IAGQ,kCAAkC,EAAA;IAH1C;MAKY,oCAAoC,EAAA;;AAIhD;EACI,wBAAwB,EAAA;EAD5B;IAGQ,wCAAwC,EAAA;IAHhD;MAKY,0CAA0C,EAAA;;AAItD;EACI,iCAAiC;EDlmCjC,oDAA2B;EAC3B,6CCkmC6F;EDjmC7F,kBAAkB;EAEd,6BAAmC;ECgmCvC,8DAA8D;EAC9D,WAAW;EACX,6BAA6B;EAC7B,8BAA8B,EAAA;;AAElC;EACI,wBAAwB,EAAA;EAD5B;IAGQ,gCAAgC,EAAA;IAHxC;MAKY,kCAAkC,EAAA;;AAI9C;EACI,uCAAuC;EDnnCvC,0DAA2B;EAC3B,mDCmnCyG;EDlnCzG,kBAAkB;EAEd,6BAAmC;ECinCvC,yDAAyD;EACzD,WAAW;EACX,6BAA6B;EAC7B,8BAA8B,EAAA;;AAElC;EACI,wBAAwB,EAAA;EAD5B;IAGQ,sCAAsC,EAAA;IAH9C;MAKY,wCAAwC,EAAA;;AAIpD;;EAGQ,mBAAmB,EAAA;;AAH3B;;EAMQ,eAAe;EACf,YAAY;EACZ,eAAe;EACf,yBAAyB;EACzB,kBAAkB;EAClB,mBAAmB;ED/nCvB,6BAA2B;EAQvB,sBAAwB;ECynCxB,YAAY,EAAA;EAbpB;;;IAgBY,yBAAyB,EAAA;EAhBrC;;IAmBY,mBAAmB,EAAA;;AAnB/B;;EAuBQ,aAAa;EACb,SAAS,EAAA;;AAxBjB;;EA2BQ,cAAc;EACd,gCAAgC;EAChC,mBAAmB,EAAA;;AA7B3B;;EAgCQ,YAAY;EACZ,UAAU;EACV,sBAAsB;EACtB,uBAAuB;EACvB,QAAQ,EAAA;;AApChB;;EAuCQ,YAAY,EAAA;;AAvCpB;;EA0CQ,OAAO,EAAA;EA1Cf;;IA4CY,mBAAmB,EAAA;EA5C/B;;IA+CY,YAAY;IACZ,WAAW,EAAA;EAhDvB;;IAmDY,YAAY,EAAA;EAnDxB;;IAsDY,aAAa,EAAA;EAtDzB;;IAyDY,WAAW;IACX,gBAAgB;IAChB,8BAA8B,EAAA;EA3D1C;;IA8DY,YAAY;IACZ,WAAW,EAAA;EA/DvB;;;;IAmEY,YAAY;IACZ,WAAW;ID/sCnB,wBAAwB;IACxB,uBC+sCoC;ID9sCpC,mBC8sC4C,EAAA;IArEhD;;;;MAuEgB,WAAW;MACX,WAAW;MACX,YAAY,EAAA;;AAK5B,qGAAA;AAEA,kGAAA;AACA;EACI,WAAW;EACX,YAAY;EACZ,YAAY;EACZ,uCAAwC;EACxC,6BAA6B;EAC7B,sBAAsB;ED3sCtB,0BAA2B;EAEvB,mBAAoB;EAGpB,oBAAsB;EA5B1B,wBAAwB;EACxB,uBCmuC4B;EDluC5B,mBCkuCoC,EAAA;;AAExC;EACI,iBAAiB;EACjB,gBAAgB;EAChB,kBAAkB;EAClB,kBAAkB;EAClB,wBAAwB;EAAE,UAAA;EAC1B,qBAAqB;EAAG,eAAA;EACxB,WAAW;EACX,YAAY;EACZ,mBAAmB,EAAA;EATvB;IAWQ,aAAa,EAAA;EAXrB;IAcQ,cAAc;ID5uClB,0BAA2B;IAC3B,gBC4uCyC;ID3uCzC,kBAAkB;IAEd,4BAAmC;IC0uCnC,yBAAyB;IACzB,kBAAkB;IDhuCtB,2BAA2B;IAEvB,mBAAoB;ICguCpB,YAAY,EAAA;EAnBpB;IAsBQ,eAAe;IACf,cAAc;IACd,iCAAkC;IAClC,YAAY;IACZ,gBAAgB;IAChB,YAAY;IACZ,kBAAkB,EAAA;IA5B1B;MA8BY,eAAe,EAAA;;AAI3B;EACI,aAAa;EACb,kCAAkC;EAClC,SAAS;EACT,aAAa;EACb,sCAAsC,EAAA;EAL1C;IAOQ,8BAA8B;IAC9B,2CAA2C;IAC3C,mBAAmB;IACnB,kBAAkB;IAClB,gBAAgB;IAChB,WAAW;IACX,cAAc,EAAA;IAbtB;MAeY,uDAAuD,EAAA;IAfnE;MAkBY,kBAAkB;MAClB,aAAa;MD3xCrB,wBAAwB;MACxB,uBC2xCoC;MD1xCpC,mBC0xC4C;MACpC,aAAa;MACb,eAAe,EAAA;MAtB3B;QAwBgB,YAAY;QACZ,WAAW,EAAA;;AAK3B;EACI,gBAAgB;EAChB,cAAc;EACd,8BAA8B;EAC9B,yBAAyB;EACzB,mBAAmB;EACnB,aAAa;EACb,eAAe;EACf,SAAS;EACT,kBAAkB;EAClB,gBAAgB;EAChB,iBAAiB,EAAA;EAXrB;IDtyCI,wBAAwB;IACxB,yBCkzCkC;IDjzClC,mBCizC0C;IACtC,QAAQ,EAAA;IAdhB;MAgBY,WAAW;MACX,kBAAkB;MAClB,iBAAiB;MACjB,sBAAsB;MACtB,6DAA6D;MAC7D,eAAe,EAAA;MArB3B;QAuBgB,cAAc,EAAA;IAvB9B;MA2BY,WAAW;MACX,kBAAkB;MAClB,iBAAiB;MACjB,sBAAsB;MACtB,8DAA8D;MAC9D,qBAAqB;MACrB,eAAe,EAAA;MAjC3B;QAmCgB,cAAc,EAAA;;AAM9B;EACI;IAAK,iCAAiC,EAAA;EACtC;IAAO,8BAA+B,EAAA,EAAA;;AAE1C;EACI;IACI,yCAAyC,EAAA,EAC5C;;AAEL;EACI;IACI,wBAAwB,EAAA,EAC3B;;AAEL;EACI;IACI,qCAAqC,EAAA,EACxC;;AAGL;EACI,aAAa;EACb,sBAAsB;EACtB,SAAS;EACT,iBAAiB;EACjB,qBAAqB,EAAA;EALzB;IAOQ,eAAe,EAAA;EAPvB;IAUQ,aAAa;IACb,kBAAkB;IAClB,mEAAmE;IACnE,YAAY;IDh3ChB,wBAAwB;IACxB,uBCg3CgC;ID/2ChC,mBC+2CwC;IACpC,YAAY;IACZ,QAAQ;IACR,WAAW;IACX,gBAAgB;IAChB,eAAe,EAAA;IAnBvB;MAqBY,sFAAsF,EAAA;IArBlG;MD31CI,0BAA2B;MAC3B,gBCk3C6C;MDj3C7C,kBAAkB;MAEd,4BAAmC;MCg3C/B,YAAY,EAAA;EAzBxB;IDn2CI,wBAAwB;IACxB,8BC+3CuC;ID93CvC,mBC83C+C;IAC3C,gBAAgB,EAAA;EA9BxB;ID31CI,0BAA2B;IAC3B,gBC23CyC;ID13CzC,kBAAkB;IAEd,4BAAmC;ICy3CnC,yBAAyB;IACzB,2BAA2B;IAC3B,8BAA8B,EAAA;EApCtC;;IAwCQ,kBAAkB;IAClB,mBAAmB,EAAA;EAzC3B;IA6CY,kBAAkB;IAClB,mBAAmB,EAAA;EA9C/B;IAmDY,uBAAuB,EAAA;EAnDnC;ID31CI,0BAA2B;IAC3B,gBCg5C6C;ID/4C7C,kBAAkB;IAEd,4BAAmC;IC84C/B,eAAe;IACf,gBAAgB;IAChB,sBAAsB;IACtB,gCAAgC,EAAA;IA1D5C;MA4DgB,aAAa,EAAA;EA5D7B;IAiEQ,yBAAyB,EAAA;IAjEjC;MAmEY,iBAAiB;MACjB,WAAW;MACX,kBAAkB;MAClB,eAAe;MACf,eAAe,EAAA;MAvE3B;QAyEgB,WAAW;QACX,yBAAyB,EAAA;EA1EzC;;IAkFgB,gBAAgB;IAChB,yBAAyB;IACzB,eAAe;IACf,eAAe,EAAA;IArF/B;;MAuFoB,iBAAiB,EAAA;IAvFrC;;;MA2FoB,WAAW;MACX,yBAAyB,EAAA;;AAM7C;EACI,aAAa;EACb,sBAAsB;EACtB,SAAS;EACT,oBAAoB;EACpB,gCAAgC;EAChC,oCAAoC,EAAA;EANxC;IAQQ,iBAAiB,EAAA;EARzB;;ID77CI,0BAA2B;IAC3B,gBAF+C;IAG/C,kBAAkB;IAEd,6BAAmC,EAAA;;ACw8C3C;EACI;IAAK,UAAU,EAAA;EAAI;IAAO,UAAU,EAAA,EAAA;;AAExC;EACI,yBAAyB;EACzB,+BAA+B;EAC/B,kBAAkB;EAClB,YAAY,EAAA;EAJhB;IAMQ,WAAW;IACX,YAAY;IACZ,kBAAkB;IAClB,kBAAkB;IAClB,gBAAgB;IAChB,0BAA0B,EAAA;IAXlC;MAcY,wBAAwB,EAAA;EAdpC;IDv9CI,wBAAwB;IACxB,uBCw+CgC;IDv+ChC,mBCu+CwC;IACpC,YAAY;IACZ,eAAe;IACf,yBAAyB;IACzB,WAAW;IACX,eAAe,EAAA;IAvBvB;MAyBY,yBAAyB,EAAA;;AAIrC;EACI,WAAW;EACX,gBAAgB;EAChB,mBAAmB;EACnB,6BAA6B,EAAA;;AAEjC;EACI,oBAAoB;EACpB,8BAA8B;EAC9B,QAAQ;EACR,kBAAkB;EAClB,yBAAyB;EACzB,YAAY,EAAA;EANhB;IAQQ,aAAa;IACb,mBAAmB;IACnB,8BAA8B;IAC9B,YAAY,EAAA;IAXpB;MAaY,mBAAmB,EAAA;;AAI/B;EACI,eAAe;EACf,kBAAkB;EAClB,iBAAiB;EACjB,8BAA8B;EAC9B,mBAAmB;EACnB,6BAA6B;EAC7B,gBAAgB;EAChB,qBAAqB;ED5/CrB,0BAA2B;EAEvB,mBAAoB;EC4/CxB,gBAAgB,EAAA;EAVpB;IAYQ,yBAAyB;IACzB,eAAe,EAAA;;AAGvB;EACI,cAAc;EACd,UAAU,EAAA;;AAEd;EACI,SAAS;EACT,cAAc;EACd,eAAe,EAAA;EAHnB;IAKQ,UAAU;IACV,eAAe,EAAA;EANvB;IASQ,uBAAuB;IACvB,mCAAmC,EAAA;;AAG3C,gGAAA;AACA;EACI;IACI,yBAAyB,EAAA;EAE7B;IACI,uBAAuB,EAAA,EAAA;;AAI/B;EACI,2BAA2B;EAC3B,SAAS,EAAA;EAFb;IAKQ,cAAa;IACb,eAAe,EAAA;;AAGvB;EAEQ,cAAa;EACb,eAAe,EAAA;;AAGvB;EACI,sBAAsB,EAAA;;AAE1B;EACI,sBAAsB,EAAA;;AAE1B;EACI,iCAAiC,EAAA","sourcesContent":["/* =====================================\r\n    wsx Flex Style ( justify, alignment)\r\n=========================================*/\r\n@mixin WsxFlexStyle($justify: flex-start, $align: flex-start) {\r\n    display: flex !important;\r\n    justify-content: $justify;\r\n    align-items: $align;\r\n}\r\n/* =====================================\r\n    WSX Font Style size, weight, height\r\n=====================================*/\r\n@mixin WsxTypographyStyle($size: 12px, $weight: 400, $lineHeight: 1.6em, $transformation: false) {\r\n    font-size: $size !important;\r\n    font-weight: $weight;\r\n    font-style: normal;\r\n    @if $lineHeight {\r\n        line-height: $lineHeight !important;\r\n    }\r\n    @if $transformation {\r\n        text-transform: $transformation !important;\r\n    }\r\n}\r\n\r\n/* ===================================================  \r\n    wsx Positioning Position, Top, Left, Right,bottom \r\n===================================================*/\r\n@mixin WsxPositionStyle($style, $top: false, $left: false, $right: false, $bottom: false) {\r\n    position: $style !important;\r\n    @if $top {\r\n        top: $top !important;\r\n    }\r\n    @if $left {\r\n        left: $left !important;\r\n    }\r\n    @if $right {\r\n        right: $right !important;\r\n    }\r\n    @if $bottom {\r\n        bottom: $bottom !important;\r\n    }\r\n}","@import './CommonMixin.scss';\r\n/* ===============================  \r\n        Global & Common Style Start \r\n================================*/\r\n.wsx-component-toggle-group-control, .wsx-font-12-normal {\r\n    @include WsxTypographyStyle(); // size: 12, weight: 400, height 1.6em\r\n} \r\n.wsx-font-14-normal {\r\n    @include WsxTypographyStyle(14px, 400, 22px); // size: 14, weight: 400, height 22px\r\n} \r\n.wsx-font-18-lightBold {\r\n    @include WsxTypographyStyle(18px, 500, 22px); // size: 18, weight: 500, height: 22px;\r\n} \r\n.wsx-marginTop_36 {\r\n    margin-top: 36px !important;\r\n}\r\n.wsx-flex_space-between_center {\r\n    @include WsxFlexStyle( space-between, center);\r\n}\r\n/* ===============================  Global & Common Style End  ================================*/\r\n\r\n/* ===============================  Builder Main Container Start  ================================*/\r\n.wholesalex-form-builder {\r\n    display: flex;\r\n    padding: 1px;\r\n    .wsx-form-field { \r\n        transition: .5s;\r\n    }\r\n    .wholesalex-reset-btn {\r\n        gap: 8px !important;\r\n        padding: 5px 16px;\r\n    }\r\n    .wholesalex-fields-wrapper {\r\n        border-radius: 4px;\r\n        box-sizing: border-box;  // margin-top: 4px; why need dnt knows\r\n        position: relative;\r\n    }\r\n    .wholesalex-login-form-title {\r\n        position: relative;\r\n    }\r\n    .wsx-form-file.wsx-form-field,\r\n    .wsx-reg-form-row .wsx-field-radio.wsx-form-field,\r\n    .wsx-reg-form-row .wsx-form-checkbox.wsx-form-field  {\r\n        .wsx-field-heading,\r\n        .wsx-reg-form-row-setting {\r\n            position: static !important;\r\n        }\r\n    }\r\n}\r\n.wholesalex-registration-form {\r\n    .wsx-row-active,\r\n    .wsx_variation_1, \r\n    .wsx_variation_3 {\r\n        .wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox) {\r\n            margin-top: 36px !important;\r\n        }\r\n        .wholesalex-registration-form-column.wsx-empty-column,\r\n        .wholesalex-registration-form-column-spacer {\r\n            margin-top: 36px;\r\n        }\r\n    }\r\n    .wsx_variation_2, \r\n    .wsx_variation_5 {\r\n        .double-column .wholesalex-registration-form-column {\r\n            @include WsxFlexStyle(flex-end, center);\r\n            flex-direction: column;\r\n        }\r\n    }\r\n}\r\n/* ===============================  Builder Main Container End  ================================*/\r\n\r\n/* ===============================  Actual Form Container Start ================================*/\r\n.wsx-form-builder {\r\n    @include WsxFlexStyle(flex-start, center);\r\n    flex-direction: column;\r\n    padding: 0px 50px;\r\n}\r\n.wholesalex-form-builder__form {\r\n    width: 100%;\r\n    .wholesalex-form-builder-header { // @include WsxFlexStyle( space-between, center);\r\n        flex-wrap: wrap;\r\n        background: #fff;\r\n        padding: 12px 24px;\r\n        max-height: 64px;\r\n        border-bottom: 1px solid #E6E5E5;\r\n        &__heading {\r\n            color: #343A46;\r\n            @include WsxTypographyStyle(20px, 500, 28px);\r\n        }\r\n        &__right {\r\n            display: flex;\r\n            gap: 16px;\r\n        }\r\n    }\r\n}\r\n.wholesalex-form-builder-container {\r\n    display: block;\r\n    border-radius: 8px;\r\n    background: #FFF;\r\n    max-width: 1280px;\r\n    margin: 40px auto;\r\n    margin: 50px 50px 0px;\r\n    width: 100%;\r\n    .is-active {\r\n        svg, path {\r\n            fill: #6C6CFF;\r\n        }\r\n    }\r\n}\r\n.wholesalex-registration-form,\r\n.wholesalex-login-form {\r\n    flex: 1;\r\n    .wsx-reg-form-row {\r\n        border-radius: 2px;\r\n        padding: 10px 16px 16px;\r\n        box-sizing: border-box;\r\n        border: 1px solid transparent;\r\n        cursor: pointer;\r\n    }\r\n    //Dragable \r\n    .move-row-icon {\r\n        cursor: move !important;\r\n    }\r\n    .wholesalex-registration-form-column.left {\r\n        order: 0;\r\n    }\r\n    .wholesalex-registration-form-column.right {\r\n        order: 1;\r\n    }\r\n    //Row Setting\r\n    .wsx-reg-form-row-setting {\r\n        display: flex;\r\n        * {\r\n            cursor: pointer;\r\n        }\r\n    }\r\n    .wsx-header-popup_control {\r\n        @include WsxPositionStyle(absolute, 6px, false, -9px);\r\n    }\r\n    .wsx-row-active .wsx-reg-form-row {\r\n        border-radius: 0px 2px 2px 2px;\r\n        border: 1px solid #E4E4FF;\r\n        background: rgba(250, 250, 255, 0.08);\r\n    }\r\n    // Double Column\r\n    .wholesalex-registration-form-row.double-column {\r\n        @include WsxFlexStyle(flex-start, flex-end);\r\n        gap: 5px;\r\n        .wholesalex-registration-form-column {\r\n            flex: 1;\r\n        }\r\n        .wholesalex-form-builder-field-inserter {\r\n            height: 40px;\r\n        }\r\n    }\r\n}\r\n.wholesalex-form-wrapper {\r\n    button.wsx-register-btn {\r\n        margin: 16px;\r\n        margin-left: 0px;\r\n    }\r\n    .wholesalex-login-form {\r\n        flex: 1;\r\n        .wsx-form-field {\r\n            margin-top: 0px !important;\r\n        }\r\n    }\r\n}\r\n.wholesalex-registration-form {\r\n    &.wsx-not-empty {\r\n        & > .wholesalex-form-builder-field-inserter {\r\n            height: auto !important;\r\n            border: none !important;\r\n            margin-top: -8px;\r\n        }\r\n        .wsx-fields-container {\r\n            .wsx-reg-form-row-wrapper:last-child {\r\n                .wsx-reg-form-row {\r\n                    margin-bottom: 0px !important;\r\n                }\r\n            }\r\n        }\r\n    }\r\n    .wsx-row-active {\r\n        .wholesalex-registration-form-column {\r\n            &:not(.left) {\r\n                margin-top: 40px;\r\n            }\r\n        }\r\n    }\r\n    .wsx-disable-field {\r\n        opacity: 0.6;\r\n    }\r\n    .wsx-reg-form-heading {\r\n        margin-bottom: 15px !important;\r\n    }\r\n    .wsx-reg-form-row-wrapper {\r\n        position: relative;\r\n        & > div.wsx-reg-form-row-setting {\r\n            @include WsxPositionStyle(absolute, -31px, false); //position: absolute !important; // top:-31px;\r\n        }\r\n    }\r\n    .wsx-reg-form-row {\r\n        @include WsxFlexStyle(space-between, stretch );\r\n        gap: 8px;\r\n        & > div {\r\n            width: 100%;\r\n        }\r\n        .wsx-reg-form-row-setting {\r\n            justify-content: flex-end;\r\n        }\r\n        .wholesalex-registration-form-column {\r\n            position: relative;\r\n            &:is(.right) {\r\n                margin-top: 0px;\r\n            }\r\n            .wsx-field-heading { // @include WsxFlexStyle(space-between, center);\r\n                width: 100%;\r\n                height: 36px;\r\n                @include WsxPositionStyle(absolute, 0px, false);\r\n            }\r\n            .wsx-reg-form-row-setting {\r\n                @include WsxPositionStyle(absolute, 0px, false, 0px);\r\n                z-index: 1;\r\n            }\r\n            & > .wholesalex-form-builder-field-inserter {\r\n                height: 100% !important;\r\n            }\r\n        }\r\n        & > .wholesalex-registration-form-column-spacer,\r\n        & > div .wholesalex-registration-form-column-spacer {\r\n            width: fit-content !important;\r\n            border: 2px solid #CFCFCF;\r\n            padding: 3px;\r\n            border-top: 0px;\r\n            border-bottom: 0px;\r\n        }\r\n    }\r\n}\r\n.wsx-reg-form-row-setting {\r\n    & > * {\r\n        height: 32px;\r\n        box-sizing: border-box;\r\n        border: 1px solid #E4E4FF;\r\n        background-color: #FAFAFF;\r\n        &:hover .wsx-form-field {\r\n            margin-top: 36px;\r\n        }\r\n        &:not(:last-child) {\r\n            border-right: 0px !important;\r\n        }\r\n        &:first-child {\r\n            border-radius: 2px 0px 0px 2px;\r\n        }\r\n        &:last-child {\r\n            border-radius: 0px 2px 2px 0px;\r\n        }\r\n        &:hover {\r\n            background-color: #E4E4FF;\r\n        }\r\n    }\r\n    svg {\r\n        padding: 7px;\r\n    }\r\n}\r\n.wholesalex-registration-form .wsx-reg-fields.wsx-fields-container {\r\n    margin-left: -16px;\r\n}\r\n/* ===============================  Actual Form Container End ================================*/\r\n\r\n/* ===============================  Sidebar Parent Starts ================================*/\r\n.wsx-sidebar-container {\r\n    transition: .7s;\r\n    width: 100%;\r\n    max-width: 300px;\r\n}\r\n.wholesalex-form-builder__typography-setting {\r\n    transition: 1s;\r\n    animation: wsxSidebarHide .3s;\r\n    &::-webkit-scrollbar { /* width */\r\n        width: 6px;\r\n        border-radius: 3px;\r\n    }\r\n    &::-webkit-scrollbar-thumb, &::-webkit-scrollbar-thumb:hover { /* Handle */\r\n        background: #C8C8D6;\r\n    }\r\n    .wsx-chose-input-wrap {\r\n        display: flex;\r\n        flex-direction: column;\r\n        max-height: 75px;\r\n        border-radius: 2px;\r\n        border: 1px solid #E9E9F0;\r\n        padding: 11px 12px;\r\n        height: 100%;\r\n        .wholesalex-choose-input-selected {\r\n            @include WsxFlexStyle(center, center);\r\n            gap: 12px;\r\n            cursor: pointer;\r\n        }        \r\n    } \r\n    /*------Choose Input Field Variation Start-------------*/\r\n    .wholesalex-choose-input-field-label {\r\n        color: var(--2, #343A46);\r\n        @include WsxTypographyStyle(14px, 500, 22px);\r\n        margin-bottom: 8px;\r\n    }\r\n    .wholesalex-choose-input-field-wrap {\r\n        position: relative;\r\n        background-color: white;\r\n        img {\r\n            max-width: 100%;\r\n            width: 270px;\r\n            margin-top: 8px;\r\n            padding-top: 6px;\r\n            border-top: 1px solid #e9e9f0;\r\n        }\r\n    }\r\n    .wholesalex-choose-input-dropdown-icon {\r\n        cursor: pointer;\r\n    }\r\n    .wholesalex-choose-input-field-popup {\r\n        display: flex;\r\n        flex-direction: column;\r\n    }\r\n    /*------Choose Input Field Variation End-------------*/\r\n}\r\n.wholesalex-choose-input-field-popup {\r\n    ul {\r\n        width: auto;\r\n        max-height: 420px;\r\n        background: #fff;\r\n        margin: 0px 16px;\r\n        border-radius: 2px;\r\n        border: 1px solid #E9E9F0;\r\n        box-sizing: border-box;\r\n        box-shadow: 0 10px 20px rgba(0, 0, 0, .2);\r\n        overflow: auto;\r\n        @include WsxPositionStyle(absolute, calc(100% + 2px), false, 0px); // top: calc(100% + 2px);\r\n        z-index: 9999;\r\n        transition: .4s;\r\n        li {\r\n            cursor: pointer;\r\n            border: 3px solid transparent;\r\n            margin: 0px 5px;\r\n            padding: 10px 10px 7px;\r\n            animation: premadeOverflowAnimation 1.5s;\r\n            @include WsxTypographyStyle(11px, 500, 1.6em);\r\n            &.wsx-selected {\r\n                border: 3px solid;\r\n                border-color: #6c6cff;\r\n            }\r\n            &:hover {\r\n                border-color: #6c6cff;\r\n            }\r\n        }\r\n        &::-webkit-scrollbar {\r\n            width: 3px;\r\n            background-color: transparent;\r\n            border-radius: 0;\r\n        }\r\n        &:hover {\r\n            &::-webkit-scrollbar, &::-webkit-scrollbar-thumb  {\r\n                width: 3px;\r\n                border-radius: 0;\r\n                height: 3px;\r\n            }\r\n            &::-webkit-scrollbar {\r\n                transition: 400ms;\r\n            }\r\n            &::-webkit-scrollbar-thumb {\r\n                background-color: #2c2c2c;\r\n            }\r\n        }\r\n    }\r\n}\r\n// Color Selection Section\r\n.wholesalex-form-builder-select-color { // @include WsxFlexStyle(space-between, center);\r\n    border-radius: 0px 0px 2px 2px;\r\n    box-sizing: border-box;\r\n    border-top: none;\r\n    cursor: pointer;\r\n    &.is-open {\r\n        background: #fafaff;\r\n        transition: 0.5s;\r\n        .selected-color-code {\r\n            border-color: #e5e5e5;\r\n        }\r\n    }\r\n    &:hover {\r\n        .select-color-details {\r\n            color: var(--color-primary-hover);\r\n        }\r\n    }\r\n}\r\n.wholesalex-form-builder__typography-setting {\r\n    width: 100%;\r\n    max-width: 300px;\r\n    background-color: #fff;\r\n    border-left: 1px solid #E6E5E5;\r\n    max-height: 100vh;\r\n    overflow-y: scroll;\r\n    @include WsxPositionStyle(sticky, 30px); // position: sticky; // top: 30px;\r\n    .components-popover {\r\n        will-change: transform;\r\n        z-index: 1000000\r\n    }\r\n    .components-popover.is-expanded {\r\n        @include WsxPositionStyle(fixed, 0, 0, 0); // position: fixed; // left: 0; // right: 0; // top: 0;\r\n        bottom: 0;\r\n        z-index: 1000000 !important\r\n    }\r\n    .components-popover__content {\r\n        background: #fff;\r\n        border-radius: 2px;\r\n        box-shadow: 0 0 0 1px #ccc, 0 .7px 1px rgba(0, 0, 0, .1), 0 1.2px 1.7px -.2px rgba(0, 0, 0, .1), 0 2.3px 3.3px -.5px rgba(0, 0, 0, .1);\r\n        box-sizing: border-box;\r\n        width: min-content\r\n    }\r\n}\r\n/* ===============================  Sidebar Parent End  ================================*/\r\n\r\n/* ===============================  Sidebar & Field Control Common Style Start  ================================*/\r\n// Style and Formatting\r\n.wholesalex-form-builder-select-color__dropdown {\r\n    background-color: white;\r\n}\r\n.wsx-color-picker-content {\r\n    z-index: 999999;\r\n    box-shadow: 8px 9px 16px 0px #0000001f;\r\n}\r\n.select-color-details {\r\n    display: flex;\r\n    gap: 8px;\r\n}\r\n.select-color-label {\r\n    color: #343A46;\r\n}\r\n.wholesalex-style-formatting-field {\r\n    @include WsxFlexStyle(space-between, center);\r\n    padding: 10px 8px;\r\n    box-sizing: border-box;\r\n    border-top: none;\r\n}\r\n.wholesalex-style-formatting-field-content {\r\n    @include WsxFlexStyle(flex-start, center);\r\n    max-width: 88px !important;\r\n    box-sizing: border-box;\r\n    input {\r\n        width: 100%;\r\n    }\r\n}\r\nselect.wholesalex-style-formatting-field-content {\r\n    height: 25.6px;\r\n    min-height: unset;\r\n}\r\n.wholesalex-size-selector {\r\n    input[type=\"number\"] {\r\n        height: 24px;\r\n        min-height: unset;\r\n        font-size: 12px;\r\n        max-width: 40px;\r\n        -moz-appearance: textfield;\r\n        margin: 0px;\r\n        border-radius: 0px;\r\n        border: none;\r\n        padding: 5px;\r\n        text-align: center;\r\n        &::-webkit-inner-spin-button,\r\n        &::-webkit-outer-spin-button {\r\n            -webkit-appearance: none;\r\n            margin: 0;\r\n        }\r\n        &:focus {\r\n            box-shadow: unset;\r\n        }\r\n    }\r\n    input[type='text'] {\r\n        height: 24px;\r\n        min-height: unset;\r\n        font-size: 12px;\r\n        width: 86px;\r\n        -moz-appearance: textfield;\r\n        margin: 0px;\r\n        border-radius: 0px;\r\n        border: none;\r\n        padding: 5px;\r\n        text-align: center;\r\n        &::-webkit-inner-spin-button,\r\n        &::-webkit-outer-spin-button {\r\n            -webkit-appearance: none;\r\n            margin: 0;\r\n        }\r\n        &:focus {\r\n            box-shadow: unset;\r\n        }\r\n    }\r\n    .decrement,\r\n    .increment {\r\n        height: 24px;\r\n        width: 24px;\r\n        border-radius: 0px;\r\n        background: #D9D9D9;\r\n        cursor: pointer;\r\n        &:hover {\r\n            background: #6c6c6c;\r\n            color: white;\r\n            border-color: #6c6c6c;\r\n            svg path {\r\n                stroke: white;\r\n            }\r\n        }\r\n        svg {\r\n            margin-top: 5px;\r\n            margin-left: 5px;\r\n        }\r\n        input {\r\n            margin: 0px;\r\n        }\r\n    }\r\n}\r\n.wholesalex-style-formatting-field-label,\r\n.components-custom-select-control__label {\r\n    color: #343A46;\r\n    margin: 0px;\r\n    text-transform: capitalize;\r\n}\r\n.components-custom-select-control__label {\r\n    @include WsxTypographyStyle();\r\n}\r\n.wholesalex-style-formatting-field-label-meta {\r\n    color: #6C6E77;\r\n    margin-left: 4px;\r\n    text-transform: lowercase;\r\n}\r\n.wholesalex-text-transform-control__buttons {\r\n    display: flex;\r\n    gap: 5px;\r\n    padding: 3px;\r\n    width: 88px;\r\n    height: 25.6px;\r\n    ;\r\n}\r\n.wholesalex-text-transform-control__button {\r\n    cursor: pointer;\r\n    height: 16px;\r\n    svg {\r\n        fill: #6C6E77 !important;\r\n        width: 16px;\r\n        height: 16px;\r\n    }\r\n    &:not(.is-pressed):hover {\r\n        svg {\r\n            fill: var(--color-primary-hover) !important;\r\n        }\r\n    }\r\n    &.is-pressed {\r\n        border-radius: 0.5px;\r\n        background: #D9D9D9;\r\n    }\r\n}\r\n// Color Indicator\r\n.component-color-indicator.wholesalex-form-builder__color {\r\n    display: block;\r\n    height: 20px;\r\n    width: 20px;\r\n    border-radius: 50%;\r\n    border: 1px solid #F2F2F2;\r\n}\r\n.wholesalex_tooltip {\r\n    margin-left: 10px;\r\n    .wholesalex_tooltip_icon {\r\n        cursor: pointer;\r\n    }\r\n    .tooltip-content {\r\n        left: auto;\r\n        &::before {\r\n            left: 69px;\r\n        }\r\n    }\r\n}\r\n.wsx-form-setting {\r\n    & > div:last-child {\r\n        margin-bottom: 0px !important;\r\n    }\r\n    .wsx-field-condition-desc {\r\n        @include WsxFlexStyle(space-between, center);\r\n        flex-direction: row-reverse;\r\n        font-weight: 500;\r\n        text-transform: capitalize;\r\n    }\r\n    .wsx-setting-wrap {\r\n        margin-bottom: 24px;\r\n    }\r\n    .wholesalex_discount_options {\r\n        overflow-y: scroll !important;\r\n        overflow: hidden;\r\n    }\r\n    .wsx-field-condition-wrap {\r\n        border: 1px solid #8888;\r\n        display: flex;\r\n        width: fit-content;\r\n        .wsx-field-condition-btn {\r\n            padding: 9px 16px;\r\n            width: 55px;\r\n            text-align: center;\r\n            border: 1px solid #6c6cff;\r\n            &:not(:last-child) {\r\n                border-right: 0px;\r\n            }\r\n            &:hover,\r\n            &.wsx-is-active {\r\n                color: #fff;\r\n                background-color: #6c6cff;\r\n            }\r\n        }\r\n    }\r\n    .wsx-setting-label,\r\n    .wsx-select-wrap select \r\n    .wsx-toggle-wrap .wholesalex_field_label,\r\n    .wsx-input-wrap .wholesalex_field_label,\r\n    .wsx-select-wrap .wholesalex_field_label {\r\n        color: #343A46;\r\n        @include WsxTypographyStyle();\r\n    }\r\n    .wsx-toggle-wrap {\r\n        @include WsxFlexStyle(space-between, center);\r\n        flex-wrap: wrap;\r\n        .wsx-toggle-setting {\r\n            @include WsxFlexStyle(center, center);\r\n            flex-direction: row-reverse;\r\n            gap: 12px;\r\n        }\r\n    }\r\n    .wsx-input-wrap {\r\n        .wholesalex_field_label {\r\n            margin-top: 10px;\r\n            max-width: 85px;\r\n            width: 100%;\r\n        }\r\n        .wsx-input-setting {\r\n            @include WsxFlexStyle(center, flex-start);\r\n            flex-direction: row;\r\n            width: 100%;\r\n        }\r\n        .wholesalex_input_field__content {\r\n            width: 100%;\r\n        }\r\n    }\r\n    .wsx-select-wrap {\r\n        select {\r\n            width: 100% !important;\r\n            max-width: 100%;\r\n            height: 40px !important;\r\n        }\r\n        .wsx-setting-label {\r\n            display: block;\r\n            margin-bottom: 5px;\r\n            width: fit-content;\r\n        }\r\n        // &.wsx-select-inline {\r\n        //     display: flex;\r\n        //     .wholesalex_field_label {\r\n        //         max-width: 100px;\r\n        //         width: 100%;\r\n        //     }\r\n        //     & > div {\r\n        //         @include WsxFlexStyle(space-between, center);\r\n        //         width: 100%;\r\n        //         .wholesalex_select_field__options {\r\n        //             width: 100%;\r\n        //         }\r\n        //     }\r\n        // }\r\n    }\r\n}\r\n/* ===============================  Sidebar & Field Control Common Style End ================================*/\r\n\r\n/* ===============================  Input Field Control Popup Settings Start ================================*/\r\n.wholesalex-popup-content {\r\n    padding: 24px 24px;\r\n}\r\n/* Field Setting Tab*/\r\n.wholesalex-popup-tab-heading {\r\n    @include WsxFlexStyle(space-evenly, flex-start);\r\n    padding: 24px 24px 0px;\r\n    // whx-active-tab\r\n    .whx-tab-heading {\r\n        cursor: pointer;\r\n        width: 100%;\r\n        background: #f5f5f5;\r\n        text-align: center;\r\n        font-weight: 500;\r\n        padding: 16px 0px;\r\n        display: flex;\r\n        justify-content: space-evenly;\r\n        gap: 11px;\r\n        &:first-child {\r\n            border-top-left-radius: 4px;\r\n        }\r\n        &:last-child {\r\n            border-top-right-radius: 4px;\r\n        }\r\n        .wsx-form-tutorial {\r\n            cursor: pointer;\r\n            @include WsxTypographyStyle(10px, 400, 14px);\r\n            border: .5px solid #6C6E77;\r\n            border-radius: 10px;\r\n            padding: 1px 3px 1px 2px;\r\n            @include WsxFlexStyle(center, center);\r\n            gap: 2px;\r\n            border-color: #6c6cff;\r\n            display: none !important;\r\n            svg path {\r\n                fill: #6c6cff;\r\n            }\r\n        }\r\n        &.whx-active-tab {\r\n            color: #fff;\r\n            background: #6c6cff;\r\n            .wsx-form-tutorial {\r\n                display: flex !important;\r\n                border-color: #fff;\r\n                svg path {\r\n                    fill: #fff;\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n.wholesalex_tiers_fields {\r\n    @include WsxFlexStyle(flex-start, flex-end);\r\n    gap: 10px;\r\n}\r\n.wholesalex-tier {\r\n    gap: 10px;\r\n    @include WsxFlexStyle(space-between, flex-start);\r\n    width: 100%;\r\n    .tier-field {\r\n        width: 100%;\r\n    }\r\n}\r\n.wsx-field-conditions-tier {\r\n    display: flex;\r\n    flex-direction: column;\r\n    width: 100%;\r\n    gap: 10px;\r\n    padding: 16px 24px 24px;\r\n    margin-top: 40px;\r\n    background: whitesmoke;\r\n    // width: calc(100% + 48px);\r\n    box-sizing: border-box;\r\n    @include WsxPositionStyle(relative, -24px); // position: relative;// left: -24px;\r\n    select,\r\n    input[type=text] {\r\n        height: 40px !important;\r\n        width: 100% !important;\r\n    }\r\n}\r\n.wholesalex-tier-add {\r\n    @include WsxTypographyStyle(var(--wholesalex-size-20), 400, 22px);\r\n    padding: 9px 22px 9px 22px;\r\n    box-sizing: border-box;\r\n    height: 40px;\r\n    @include WsxFlexStyle(center, center);\r\n    color: white;\r\n    border-radius: 3px;\r\n    line-height: 21px;\r\n    background-color: var(--wholesalex-primary-color);\r\n}\r\n.wholesalex-tier-delete {\r\n    @include WsxTypographyStyle(var(--wholesalex-size-20), 400, 22px);\r\n    color: white;\r\n    background-color: #d54013;\r\n    border-radius: 2px;\r\n    padding: 10px 13px 9px 14px;\r\n}\r\nspan.wsx-field-condition-btn.wsx-is-active {\r\n    background-color: yellow;\r\n}\r\n.wholesalex-field-conditions {\r\n    padding-bottom: 0px !important;\r\n}\r\n.wsx-reg-form-row-setting {\r\n    z-index: 999;\r\n}\r\n/* ===============================  Input Field Control Popup Settings End ================================*/\r\n\r\n/* ===============================  Login Registration Header Start ================================*/\r\n.wholesalex-disabled {\r\n    opacity: 0.6;\r\n}\r\n.wsx-popup-close {\r\n    cursor: pointer;\r\n    &:hover path {\r\n        stroke: #6C6CFF;\r\n    }\r\n}\r\n.wsx-field-insert-popup {\r\n    width: 496px;\r\n    flex-shrink: 0;\r\n}\r\n.wholesalex-field-insert-btn {\r\n    cursor: pointer;\r\n    color: white;\r\n    height: 16px;\r\n    width: 16px;\r\n    font-size: 16px;\r\n    border-radius: 16px;\r\n    background: #343A46;\r\n    padding: 8px;\r\n    &:hover {\r\n        background-color: #22252d;\r\n    }\r\n    &.is-active {\r\n        background: linear-gradient(180deg, #6C6CFF 0%, #4747D9 100%);\r\n    }\r\n}\r\n.wholesalex-popup-heading {\r\n    padding: 9px 24px;\r\n    color: #343A46;\r\n    border-radius: 8px 8px 0px 0px;\r\n    background: rgba(108, 108, 255, 0.06);\r\n    padding-right: 10px;\r\n    & > div {\r\n        @include WsxFlexStyle(center, center);\r\n        gap: 14px;\r\n    }\r\n}\r\n.wholesalex-form-builder-toogle {\r\n    position: relative;\r\n}\r\n.wholesalex-form-builder-field-poup {\r\n    position: relative;\r\n    animation: overflowAnimation .3s;\r\n    max-height: 450px;\r\n    ul, li {\r\n        padding: 0px;\r\n        margin: 0px;\r\n    }\r\n    .wholesalex-popup-heading {\r\n        font-weight: 500;\r\n    }\r\n    .wsx-insert-field-btn {\r\n        display: inline-block;\r\n        padding: 6px 12px;\r\n        gap: 8px;\r\n        border-radius: 2px;\r\n        border: 1px solid rgba(108, 108, 255, 0.20);\r\n        background: rgba(108, 108, 255, 0.12);\r\n        margin-right: 4px;\r\n        margin-bottom: 4px;\r\n        cursor: pointer;\r\n        &:hover {\r\n            background: rgba(108, 108, 255, 0.20);\r\n        }\r\n    }\r\n    .wholesalex-form-builder-popup:not(:first-child) .wholesalex-popup-heading {\r\n        position: static !important;\r\n    }\r\n}\r\n.wholesalex-form-title-style-column-heading {\r\n    color: #6C6E77;\r\n    border-bottom: 1px solid #E9E9F0;\r\n    margin-bottom: 16px;\r\n}\r\n.wsx-reg-form-heading,\r\n.wholesalex-login-form-title {\r\n    cursor: pointer;\r\n    border-radius: 2px;\r\n    box-sizing: content-box;\r\n    padding: 18px 16px 22px;\r\n    border-radius: 0px 2px 2px 2px;\r\n    border: 1px solid #E4E4FF;\r\n    background: rgba(250, 250, 255, 0.08);\r\n    transition: .3s;\r\n    // &:hover {\r\n    //     padding: 18px 16px 22px;\r\n    // }\r\n}\r\n.field-setting-icon.wsx-active path {\r\n    fill: #6C6CFF !important;\r\n}\r\n\r\n.wholesalex-form-title-style-column {\r\n    flex: 1;\r\n    .increment,\r\n    .decrement {\r\n        height: 36px;\r\n        width: 36px;\r\n        @include WsxFlexStyle(center, center);\r\n        svg {\r\n            margin: 0px;\r\n            width: 20px;\r\n            height: 20px;\r\n        }\r\n    }\r\n    .wholesalex-form-title-style-row {\r\n        margin-bottom: 16px;\r\n    }\r\n    .wholesalex-style-formatting-field {\r\n        height: unset;\r\n    }\r\n    .wholesalex-text-transform-control {\r\n        display: flex;\r\n        .wholesalex-style-formatting-field-content {\r\n            align-items: center !important;\r\n            justify-content: space-around !important;\r\n        }\r\n    }\r\n    .wholesalex-form-builder-select-color {\r\n        height: 36px;\r\n        padding-left: 12px;\r\n    }\r\n    .wholesalex-text-transform-control__buttons {\r\n        height: 36px;\r\n    }\r\n    .wholesalex-style-formatting-field-content {\r\n        width: 100%;\r\n        max-width: unset !important;\r\n        justify-content: space-between;\r\n        border-radius: 1px;\r\n        border: 1px solid #E9E9F0;\r\n    }\r\n    .wholesalex-size-selector input[type=number] {\r\n        height: 36px;\r\n        width: 100%;\r\n        max-width: 100% !important;\r\n        border: 1px solid #E9E9F0;\r\n    }\r\n}\r\n.wholesalex-form-builder-dropdown-content {\r\n    border-radius: 8px;\r\n    border: 1px solid #E4E4FF;\r\n    background: #FFF;\r\n    box-shadow: 0px 24px 40px 8px rgba(108, 108, 255, 0.22);\r\n    width: 600px;\r\n    max-height: 500px;\r\n    z-index: 99999;\r\n    &.wsx-setting-popup_control {\r\n        top: 94px !important;\r\n    }\r\n    &.wsx-header-popup_control {\r\n        translate: -30% !important;\r\n        top: 72px;\r\n    }\r\n    .wholesalex-popup-heading {\r\n        @include WsxPositionStyle(sticky, 0px); // position: sticky; // top: 0px;\r\n        z-index: 9999;\r\n        background-color: rgb(246, 246, 255);\r\n        @include WsxFlexStyle(space-between, center);\r\n        &.wsx-popup-last {\r\n            position: static !important;\r\n            border-radius: 0px;\r\n        }\r\n    }\r\n    .components-popover__content::-webkit-scrollbar {\r\n        width: 6px;\r\n        transition: 400ms;\r\n        background-color: #F6F6FF;\r\n        padding: 1px;\r\n    }\r\n    .components-popover__content::-webkit-scrollbar-thumb {\r\n        width: 6px;\r\n        height: 80px;\r\n        background-color: #C8C8D6;\r\n    }\r\n    .components-popover__content:hover {\r\n        .components-popover__content::-webkit-scrollbar-thumb {\r\n            width: 6px;\r\n            height: 80px;\r\n            background-color: #C8C8D6;\r\n        }\r\n    }\r\n}\r\n.wholesalex-form-builder-typography-setting-header {\r\n    @include WsxFlexStyle(center, center);\r\n    flex-wrap: wrap;\r\n    max-height: 64px;\r\n    border-bottom: 1px solid #E6E5E5;\r\n    padding: 21px 24px;\r\n    color: #343A46;\r\n    max-width: 300px;\r\n    @include WsxTypographyStyle(14px, 500, 22px);\r\n    @include WsxPositionStyle(sticky, 0px); // position: sticky; // top: 30px;\r\n    z-index: 999;\r\n    background-color: #fff;\r\n}\r\n.wholesalex-popup-content.wholesalex-form-title-style {\r\n    display: flex;\r\n    gap: 48px;\r\n}\r\n.wholesalex-form-title-style .wholesalex-style-formatting-field {\r\n    border: none;\r\n    padding: 0;\r\n    flex-direction: column;\r\n    align-items: flex-start;\r\n    gap: 8px;\r\n}\r\n.wsx-form-title-setting .wholesalex-form-title-style-column-heading {\r\n    font-weight: 500;\r\n    line-height: 32px;\r\n}\r\n.wholesalex-form-builder-field-inserter,\r\n.wholesalex-registration-form-column>.wholesalex-form-builder-field-inserter {\r\n    width: 100%;\r\n    @include WsxFlexStyle(center, center);\r\n    border-radius: 2px;\r\n    border: 1px dashed #E4E4FF;\r\n    height: 120px;\r\n    position: relative;\r\n    box-sizing: border-box;\r\n}\r\n@keyframes overflowAnimation {\r\n    0% { top: -100px; }\r\n    100% { top: 0px; }\r\n}\r\nselect.wholesalex-style-formatting-field-content {\r\n    height: 36px;\r\n}\r\n.wholesalex-form-title-style-row .wholesalex-form-builder-select-color {\r\n    border: none;\r\n    width: 100%;\r\n    flex-direction: row-reverse;\r\n    .select-color-details {\r\n        border-left: 1px solid #E9E9F0;\r\n    }\r\n    .wholesalex-form-builder__color {\r\n        height: 28px;\r\n        width: 28px;\r\n        border-radius: 1px;\r\n        border: 4px solid #FAF7FC;\r\n    }\r\n    .selected-color-code {\r\n        text-transform: uppercase;\r\n        color: #6C6E77;\r\n        padding: 6px;\r\n        border-radius: 1px;\r\n        width: 88px;\r\n        border: none;\r\n    }\r\n}\r\n.wsx-editable-area {\r\n    background-color: transparent !important;\r\n    border: transparent !important;\r\n    box-shadow: unset !important;\r\n}\r\n.wsx-editable.wsx-register-btn {\r\n    svg {\r\n        fill: var(--wsx-form-button-color) !important; \r\n        path {\r\n            stroke: var(--wsx-form-button-color) !important;\r\n        } \r\n    }\r\n}\r\n.wsx-editable.wsx-register-btn {\r\n    input {\r\n        text-align: center !important; \r\n        color: var(--wsx-form-button-color) !important;\r\n        @include WsxTypographyStyle( var(--wsx-form-button-font-size), var(--wsx-form-button-weight), 1.6em);\r\n        text-transform: var(--wsx-form-button-case-transform);\r\n        display: inline-block;\r\n        text-align: center;\r\n        box-sizing: border-box;\r\n        padding-block: 0px !important;\r\n        padding-inline: 0px !important;\r\n        width: 80%;\r\n    }\r\n    &:hover input {\r\n        color: var(--wsx-form-button-hover-color) !important;\r\n    }\r\n}\r\n.wsx-editable.wsx-login-btn {\r\n    svg {\r\n        fill: var(--wsx-login-form-button-color) !important;\r\n        path {\r\n            stroke: var(--wsx-login-form-button-color) !important;\r\n        } \r\n    }\r\n}\r\n.wsx-editable.wsx-login-btn {\r\n    input {\r\n        @include WsxTypographyStyle( var(--wsx-form-button-font-size), var(--wsx-form-button-weight), 1.6em);\r\n        color: var(--wsx-login-form-button-color) !important;\r\n        text-align: center !important;\r\n        text-transform: var(--wsx-form-button-case-transform);\r\n        display: inline-block;\r\n        box-sizing: border-box;\r\n        padding-block: 0px !important;\r\n        padding-inline: 0px !important;\r\n        width: 80%;\r\n    }\r\n    &:hover input {\r\n        color: var(--wsx-login-form-button-hover-color) !important;\r\n    }\r\n}\r\n.wsx-login-form-title-text.wsx-editable .wsx-editable-area {\r\n    @include WsxTypographyStyle( var(--wsx-login-title-font-size), var(--wsx-login-title-font-weight), 1.6em);\r\n    color: var(--wsx-login-title-color);\r\n    text-transform: var(--wsx-login-title-case-transform);\r\n    width: 100%;\r\n    padding-block: 0px !important;\r\n    padding-inline: 0px !important;\r\n}\r\n.wholesalex-login-form-subtitle-text.wsx-editable .wsx-editable-area {\r\n    width: 100%;\r\n    color: var(--wsx-login-description-color);\r\n    @include WsxTypographyStyle( var(--wsx-login-description-font-size), var(--wsx-login-description-font-weight), 1.6em);\r\n    text-transform: var(--wsx-login-description-case-transform);\r\n    padding-block: 0px !important;\r\n    padding-inline: 0px !important;\r\n}\r\n.wsx-editable {\r\n    display: flex;\r\n    align-items: center;\r\n}\r\n.wsx-login-form-title-text.wsx-editable {\r\n    width: calc(100% - 34px);\r\n    svg {\r\n        fill: var(--wsx-login-title-color);\r\n        path {\r\n            stroke: var(--wsx-login-title-color);\r\n        } \r\n    }\r\n}\r\n.wholesalex-login-form-subtitle-text.wsx-editable {\r\n    width: calc(100% - 34px);\r\n    svg {\r\n        fill: var(--wsx-login-description-color);\r\n        path {\r\n            stroke: var(--wsx-login-description-color);\r\n        } \r\n    }\r\n}\r\n.wsx-reg-form-heading-text.wsx-editable .wsx-editable-area {\r\n    color: var(--wsx-reg-title-color);\r\n    @include WsxTypographyStyle( var(--wsx-reg-title-font-size), var(--wsx-reg-title-font-weight), 1.6em);\r\n    text-transform: var(--wsx-reg-title-case-transform) !important;\r\n    width: 100%;\r\n    padding-block: 0px !important;\r\n    padding-inline: 0px !important;\r\n}\r\n.wsx-reg-form-heading-text.wsx-editable {\r\n    width: calc(100% - 34px);\r\n    svg {\r\n        fill: var(--wsx-reg-title-color);\r\n        path {\r\n            stroke: var(--wsx-reg-title-color);\r\n        } \r\n    }\r\n}\r\n.wholesalex-registration-form-subtitle-text.wsx-editable .wsx-editable-area {\r\n    color: var(--wsx-reg-description-color);\r\n    @include WsxTypographyStyle( var(--wsx-reg-description-font-size), var(--wsx-reg-description-font-weight), 1.6em);\r\n    text-transform: var(--wsx-reg-description-case-transform);\r\n    width: 100%;\r\n    padding-block: 0px !important;\r\n    padding-inline: 0px !important;  \r\n}\r\n.wholesalex-registration-form-subtitle-text.wsx-editable {\r\n    width: calc(100% - 34px);\r\n    svg {\r\n        fill: var(--wsx-reg-description-color);\r\n        path {\r\n            stroke: var(--wsx-reg-description-color);\r\n        } \r\n    } \r\n}\r\n.wsx-reg-form-heading,\r\n.wholesalex-login-form-title {\r\n    .wholesalex-form-builder-toogle {\r\n        visibility: visible;\r\n    }\r\n    .dashicons.dashicons-admin-generic {\r\n        cursor: pointer;\r\n        height: 18px;\r\n        font-size: 18px;\r\n        border: 1px solid #E4E4FF;\r\n        border-radius: 2px;\r\n        background: #FAFAFF;\r\n        @include WsxPositionStyle(absolute, false, false, 16px); // position: absolute;// right: 16px;\r\n        padding: 5px;\r\n        &.wsx-active,\r\n        &.is-active {\r\n            color: #6C6CFF !important;\r\n        }\r\n        &:hover {\r\n            background: #E4E4FF;\r\n        }\r\n    }\r\n    .wholesalex-popup-content.wholesalex-form-title-style {\r\n        display: flex;\r\n        gap: 48px;\r\n    }\r\n    .wholesalex-form-title-style-column-heading {\r\n        color: #6C6E77;\r\n        border-bottom: 1px solid #E9E9F0;\r\n        margin-bottom: 16px;\r\n    }\r\n    .wholesalex-form-title-style .wholesalex-style-formatting-field {\r\n        border: none;\r\n        padding: 0;\r\n        flex-direction: column;\r\n        align-items: flex-start;\r\n        gap: 8px;\r\n    }\r\n    select.wholesalex-style-formatting-field-content {\r\n        height: 36px;\r\n    }\r\n    .wholesalex-form-title-style-column {\r\n        flex: 1;\r\n        .wholesalex-form-title-style-row {\r\n            margin-bottom: 16px;\r\n        }\r\n        .wholesalex-form-title-style-row .wholesalex-form-builder-select-color {\r\n            border: none;\r\n            width: 100%;\r\n        }\r\n        .wholesalex-text-transform-control__buttons {\r\n            height: 36px;\r\n        }\r\n        .wholesalex-style-formatting-field {\r\n            height: unset;\r\n        }\r\n        .wholesalex-style-formatting-field-content {\r\n            width: 100%;\r\n            max-width: unset;\r\n            justify-content: space-between;\r\n        }\r\n        .wholesalex-size-selector input[type=number] {\r\n            height: 36px;\r\n            width: 100%;\r\n        }\r\n        .increment,\r\n        .decrement {\r\n            height: 36px;\r\n            width: 36px;\r\n            @include WsxFlexStyle(center, center);\r\n            svg {\r\n                margin: 0px;\r\n                width: 20px;\r\n                height: 20px;\r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Login & Registration Header End ================================*/\r\n\r\n/* ===============================  Sidebar Pre-made Popup Start ================================*/\r\n.wsx-reg-premade { // Premade Popup\r\n    width: 100%;\r\n    height: 100%;\r\n    z-index: 999;\r\n    background-color: rgba(52, 58, 70, 0.50);\r\n    padding: 40px 40px 40px 200px;\r\n    box-sizing: border-box;\r\n    @include WsxPositionStyle(fixed, 0px, 0px); // position: fixed; // top: 0px; // left: 0px;\r\n    @include WsxFlexStyle(center, center);\r\n}\r\n.wsx-reg-premade_container {\r\n    max-width: 1280px;\r\n    background: #fff;\r\n    max-height: 1011px;\r\n    overflow-y: scroll;\r\n    -ms-overflow-style: none; /* IE 11 */\r\n    scrollbar-width: none;  /* Firefox 64 */\r\n    width: 100%;\r\n    height: 100%;\r\n    border-radius: 16px;\r\n    &::-webkit-scrollbar {\r\n        display: none;\r\n    }\r\n    .wsx-reg-premade_heading {\r\n        color: #343A46;\r\n        @include WsxTypographyStyle(20px, 500, 28px);\r\n        background-color: #F6F6FF;\r\n        padding: 13px 24px; // @include WsxFlexStyle(space-between, center);\r\n        @include WsxPositionStyle(sticky, 0px); // position: sticky; // top: 0px;\r\n        z-index: 999;\r\n    }\r\n    .wsx-reg-premade_close {\r\n        cursor: pointer;\r\n        display: block;\r\n        background: rgba(52, 58, 70, 0.10);\r\n        padding: 0px;\r\n        line-height: 0px;\r\n        padding: 4px;\r\n        border-radius: 4px;\r\n        &:hover path {\r\n            stroke: #6c6cff;\r\n        }\r\n    }\r\n}\r\n.wsx-reg-premade__body {\r\n    display: grid;\r\n    grid-template-columns: 1fr 1fr 1fr;\r\n    gap: 30px;\r\n    padding: 24px;\r\n    animation: premadeOverflowAnimation 1s;\r\n    .wsx-premade-item {\r\n        border-radius: 4px 4px 0px 0px;\r\n        border: 1px solid rgba(228, 228, 255, 0.94);\r\n        background: #FCFCFC;\r\n        text-align: center;\r\n        max-width: 390px;\r\n        width: 100%;\r\n        margin: 0 auto;\r\n        &:hover {\r\n            box-shadow: 0px 16px 36px 0px rgba(108, 108, 255, 0.12);\r\n        }\r\n        .wsx-premade-media {\r\n            text-align: center;\r\n            height: 220px;\r\n            @include WsxFlexStyle(center, center);\r\n            padding: 16px;\r\n            cursor: pointer;\r\n            img {\r\n                height: 100%;\r\n                width: 100%;\r\n            }\r\n        }\r\n    }\r\n}\r\n.wsx-premade-content {\r\n    text-align: left;\r\n    color: #6C6E77;\r\n    border-radius: 4px 4px 0px 0px;\r\n    border: 1px solid #E5E5FF;\r\n    background: #F6F6FF; // @include WsxFlexStyle(space-between, center);\r\n    padding: 10px;\r\n    flex-wrap: wrap;\r\n    gap: 10px;\r\n    border-bottom: 0px;\r\n    border-left: 0px;\r\n    border-right: 0px;\r\n    .wsx-premade-action {\r\n        @include WsxFlexStyle(flex-end, center);\r\n        gap: 8px;\r\n        .wsx-premade-template {\r\n            color: #FFF;\r\n            border-radius: 2px;\r\n            padding: 7px 20px;\r\n            box-sizing: border-box;\r\n            background: linear-gradient(180deg, #6C6CFF 0%, #4747D9 100%);\r\n            cursor: pointer;\r\n            &:hover {\r\n                color: #dcdce7;\r\n            }\r\n        }\r\n        .wsx-premade-upgrade {\r\n            color: #FFF;\r\n            border-radius: 2px;\r\n            padding: 7px 20px;\r\n            box-sizing: border-box;\r\n            background-image: linear-gradient(to bottom, #ff9336, #de521e);\r\n            text-decoration: none;\r\n            cursor: pointer;\r\n            &:hover {\r\n                color: #dcdce7;\r\n            }\r\n        }\r\n\r\n    }\r\n}\r\n@keyframes premadeOverflowAnimation {\r\n    0% { transform: translate(0px, -249px); }\r\n    100% { transform: translate(0px, -0px); }\r\n}\r\n@media only screen and (max-width: 1024px) {\r\n    .wsx-reg-premade__body {\r\n        grid-template-columns: 1fr 1fr !important;\r\n    }\r\n}\r\n@media only screen and (max-width: 960px) {\r\n    .wsx-reg-premade {\r\n        padding: 40px !important;\r\n    }\r\n}\r\n@media only screen and (max-width: 768px) {\r\n    .wsx-reg-premade__body {\r\n        grid-template-columns: 1fr !important;\r\n    }\r\n}\r\n// According & Sidebar Setting Panel\r\n.wholesalex-form-builder-style-formatting-controller {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 12px;\r\n    min-height: 100vh; // padding: 16px 15px 100px;\r\n    padding-bottom: 100px;\r\n    img {\r\n        max-width: 100%;\r\n    }\r\n    .wholesalex-premade-btn {\r\n        border: unset;\r\n        border-radius: 2px;\r\n        background: linear-gradient(180deg, #9DC2FF 0.21%, #6C6CFF 100.21%);\r\n        height: 40px;\r\n        @include WsxFlexStyle(center, center);\r\n        width: 268px;\r\n        gap: 8px;\r\n        width: 100%;\r\n        margin-top: 16px;\r\n        cursor: pointer;\r\n        &:hover {\r\n            background: linear-gradient(180deg, #678ac2 0.21%, var(--color-primary-hover) 100.21%);\r\n        }\r\n        &-label {\r\n            @include WsxTypographyStyle(14px, 500, 22px);\r\n            color: white;\r\n        }\r\n    }\r\n    .typography-field .wholesalex_slider_field {\r\n        @include WsxFlexStyle(space-between, center);\r\n        margin-top: 10px;\r\n    }\r\n    .wsx-component-section__title {\r\n        @include WsxTypographyStyle(14px, 500, 22px);\r\n        background-color: #F6F6FF;\r\n        padding-top: 4px !important;\r\n        padding-bottom: 4px !important;\r\n    }\r\n    &>div:not(.wsx-component-section),\r\n    .wsx-component-section__title {\r\n        padding-left: 16px;\r\n        padding-right: 16px;\r\n    }\r\n    .wsx-component-section__body {\r\n        & > div { // &>div &>div,\r\n            padding-left: 16px;\r\n            padding-right: 16px;\r\n        }\r\n    }\r\n    .wsx-component-panel {\r\n        &.wsx-component-panel__open .wsx-component-panel__title {\r\n            border: none !important;\r\n        }\r\n        .wsx-component-panel__title {\r\n            @include WsxTypographyStyle(12px, 500, 16px);\r\n            cursor: pointer; // @include WsxFlexStyle(space-between, center);\r\n            margin: 12px 0px;\r\n            padding: 0px 16px 12px;\r\n            border-bottom: 1px solid #E6E5E5;\r\n            .wsx-component-panen__icon {\r\n                display: flex;\r\n            }\r\n        }\r\n    }\r\n    .wsx-control-tab .wsx-component-toggle-group-control-options { // @include WsxFlexStyle(space-between, center);\r\n        background-color: #E9E9F0;\r\n        .wsx-component-toggle-group-control-options__option {\r\n            padding: 6px 13px;\r\n            width: 100%;\r\n            text-align: center;\r\n            cursor: pointer;\r\n            transition: .3s;\r\n            &[data-active-item~='true'] {\r\n                color: #fff;\r\n                background-color: #343A46;\r\n            }\r\n        }\r\n    }\r\n    .wsx-control-state,\r\n    .wsx-control-alignment { // @include WsxFlexStyle(space-between, center);\r\n    .wsx-component-toggle-group-control-options { // @include WsxFlexStyle(space-between, center);\r\n            & > div {\r\n                padding: 4px 8px;\r\n                border: 1px solid #6C6E77;\r\n                transition: .3s;\r\n                cursor: pointer;\r\n                &:not(:last-child) {\r\n                    border-right: 0px;\r\n                }\r\n                &:hover,\r\n                &[data-active-item~='true'] {\r\n                    color: #fff;\r\n                    background-color: #6C6E77;\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n.wsx-component-panel__body { // accordin sidebar panel\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 12px;\r\n    padding-bottom: 16px;\r\n    border-bottom: 1px solid #E6E5E5;\r\n    animation: wsxAccordingAnimation .3s;\r\n    & > div {\r\n        padding: 0px 16px;\r\n    }\r\n    input,\r\n    select {\r\n        @include WsxTypographyStyle();\r\n    }\r\n}\r\n@keyframes wsxAccordingAnimation {\r\n    0% { opacity: 0; } 100% { opacity: 1; }\r\n}\r\n.wsx-control-increment .wholesalex-style-formatting-field-content  { // Increment/decrement\r\n    border: 1px solid #E9E9F0;\r\n    align-items: stretch !important;\r\n    border-radius: 1px;\r\n    height: 24px;\r\n    input {\r\n        border: 0px;\r\n        padding: 0px;\r\n        border-radius: 0px;\r\n        text-align: center;\r\n        min-height: auto;\r\n        -moz-appearance: textfield;\r\n        &::-webkit-inner-spin-button,\r\n        &::-webkit-outer-spin-button {\r\n            -webkit-appearance: none;\r\n        }\r\n    }\r\n    span {\r\n        @include WsxFlexStyle(center, center);\r\n        padding: 5px;\r\n        cursor: pointer;\r\n        background-color: #D9D9D9;\r\n        margin: 1px;\r\n        transition: .3s;\r\n        &:hover {\r\n            background-color: #c1c1c1;\r\n        }\r\n    }\r\n}\r\n.wholesalex-select-field select.wholesalex-style-formatting-field-content {\r\n    width: 100%;\r\n    padding: 0px 6px;\r\n    line-height: normal;\r\n    border-radius: 0px !important;\r\n}\r\n.wholesalex-text-transform-control .wholesalex-style-formatting-field-content {\r\n    align-items: stretch;\r\n    justify-content: space-between;\r\n    gap: 0px;\r\n    border-radius: 1px;\r\n    border: 1px solid #E9E9F0;\r\n    padding: 0px;\r\n    .wholesalex-text-transform-control__button {\r\n        display: flex;\r\n        align-items: center;\r\n        height: -webkit-fill-available;\r\n        padding: 3px;\r\n        &:hover {\r\n            background: #D9D9D9;\r\n        }\r\n    }\r\n}\r\n.wsx-form-control_hide {\r\n    cursor: pointer;\r\n    margin-left: -25px;\r\n    background: white;\r\n    border-radius: 4px 0px 0px 4px;\r\n    border-style: solid;\r\n    border-width: 1px 0px 1px 1px;\r\n    padding: 7px 2px;\r\n    border-color: #e6e5e5;\r\n    @include WsxPositionStyle(fixed, 50% ); //  position: fixed; //  top: 50%;\r\n    z-index: 9999999;\r\n    & > span {\r\n        transform: rotate(180deg);\r\n        transition: .5s;\r\n    }\r\n}\r\n.wholesalex-form-builder__typography-setting  > div:not(.wsx-form-control_hide) {\r\n    transition: 2s;\r\n    opacity: 1;\r\n}\r\n.wsx-sidebar-hide {\r\n    width: 0%;\r\n    max-width: 0px;\r\n    transition: .5s;\r\n    .wholesalex-form-builder__typography-setting  > div:not(.wsx-form-control_hide) {\r\n        opacity: 0;\r\n        transition: .1s;\r\n    }\r\n    .wsx-form-control_hide > span { \r\n        transform: rotate(0deg);\r\n        animation: wsxSideBarIconRotate .5s;  \r\n    }\r\n}\r\n/* ===============================  Sidebar Pre-made Popup End ================================*/\r\n@keyframes wsxSideBarIconRotate {\r\n    0% { \r\n        transform: rotate(180deg);\r\n    }\r\n    100% {\r\n        transform: rotate(0deg);\r\n    }\r\n}\r\n\r\n.wholesalex-popup-heading.wsx-extra-field-popup,.wholesalex-popup-heading.wsx-field-wc-options-popup {\r\n    justify-content: flex-start;\r\n    gap: 10px;\r\n\r\n    .dashicons.dashicons-lock{\r\n        color:#6c6cff;\r\n        cursor: pointer;\r\n    }\r\n}\r\n.wsx-field-condition-tab {\r\n    .dashicons.dashicons-lock{\r\n        color:#6c6cff;\r\n        cursor: pointer;\r\n    }\r\n}\r\n.wholesalex-form-builder-dropdown-content .components-popover__content {\r\n    width: 100% !important;\r\n}\r\n.wsx-field-insert-popup {\r\n    z-index: 99 !important;\r\n}\r\n.wsx-field-condition-popup .pro_popup_container{\r\n    max-width: fit-content !important;\r\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Form.scss":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Form.scss ***!
  \************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/* =====================================
    wsx Flex Style ( justify, alignment)
=========================================*/
/* =====================================
    WSX Font Style size, weight, height
=====================================*/
/* ===================================================  
    wsx Positioning Position, Top, Left, Right,bottom 
===================================================*/
/* =============== Common Class =============== */
.wsx-input-typography, .wsx-form-field.wsx-form-checkbox .wsx-field-content label, .wsx-form-field.wsx-field-radio .wsx-field-content label, .wsx_variation_1 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > select, .wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > input,
.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > select, .wsx_variation_4 .wsx-reg-fields .wsx-form-field.wsx-outline-focus, .wsx_variation_5 .wsx-reg-fields .wsx-form-field.wsx-outline-focus, .wsx_variation_4 .wsx-form-field .wsx-form-field > input,
.wsx_variation_4 .wsx-form-field .wsx-form-field > select,
.wsx_variation_4 .wsx-form-field .wsx-form-field > textarea, .wsx_variation_5 .wsx-form-field .wsx-form-field > input,
.wsx_variation_5 .wsx-form-field .wsx-form-field > select,
.wsx_variation_5 .wsx-form-field .wsx-form-field > textarea, .wsx_variation_7 .wsx-outline-focus > input,
.wsx_variation_7 .wsx-outline-focus > textarea,
.wsx_variation_7 .wsx-outline-focus > select, .wsx_variation_8 .wsx-form-field > input,
.wsx_variation_8 .wsx-form-field > textarea,
.wsx_variation_8 .wsx-form-field > select, .wsx_variation_8 .wsx-form-field.wsx-outline-focus {
  font-size: var(--wsx-input-font-size) !important;
  font-weight: var(--wsx-input-weight);
  font-style: normal;
  line-height: 1.6em !important;
  text-transform: var(--wsx-input-case-transform) !important; }

.wsx-formBuilder-input-width, .wholesalex-form-wrapper .wholesalex-login-form > *, .wholesalex-form-wrapper .wholesalex-registration-form > *, .wsx_variation_1 .wsx-outline-focus {
  width: 100%;
  max-width: var(--wsx-input-width); }

.wsx-formBuilder-input-layout, .wsx-form-file .wsx-field-content .wsx-file-label, .wsx_variation_1 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > select, .wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > input,
.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > select, .wsx_variation_4 .wsx-field-content, .wsx_variation_5 .wsx-field-content, .wsx_variation_8 .wsx-field-content, .wsx_variation_4 .wsx-reg-fields .wsx-form-field.wsx-outline-focus, .wsx_variation_5 .wsx-reg-fields .wsx-form-field.wsx-outline-focus, .wsx_variation_7 .wsx-outline-focus > input,
.wsx_variation_7 .wsx-outline-focus > textarea,
.wsx_variation_7 .wsx-outline-focus > select, .wsx_variation_8 .wsx-form-field.wsx-outline-focus {
  border-radius: var(--wsx-input-border-radius);
  padding: var(--wsx-input-padding);
  border: var(--wsx-input-border-width) solid var(--wsx-input-border-color); }

/* =============== Common Class =============== */
/* ======= Container Style  ======= */
.wholesalex-form, .wsx-form-wrapper_frontend {
  width: 100% !important;
  max-width: var(--wsx-form-container-width) !important; }
  .wholesalex-form .wholesalex_circular_loading__wrapper, .wsx-form-wrapper_frontend .wholesalex_circular_loading__wrapper {
    display: none; }

/* ===============================  Variable Styling End ================================*/
/* ===============================  Input Variation Common Style Start ================================*/
.wholesalex-form-wrapper {
  height: auto;
  width: 100%;
  box-sizing: border-box;
  border: var(--wsx-form-container-border-width) solid var(--wsx-form-container-border-color);
  border-radius: var(--wsx-form-container-border-radius);
  background-color: var(--wsx-form-container-bg);
  padding: var(--wsx-form-container-padding);
  display: flex !important;
  justify-content: center;
  align-items: stretch; }
  .wholesalex-form-wrapper input, .wholesalex-form-wrapper select, .wholesalex-form-wrapper textarea {
    min-height: auto !important;
    max-width: 100%;
    box-shadow: none !important;
    margin: 0px !important; }
  .wholesalex-form-wrapper input, .wholesalex-form-wrapper select {
    height: auto !important; }
  .wholesalex-form-wrapper .wsx-form-label {
    margin-bottom: 0px; }
    .wholesalex-form-wrapper .wsx-form-label > span {
      color: #FF2727 !important;
      margin-left: 4px; }
  .wholesalex-form-wrapper .wsx-clone-label span {
    visibility: hidden; }
  .wholesalex-form-wrapper .wsx-reg-form-heading-text {
    color: var(--wsx-reg-title-color);
    font-size: var(--wsx-reg-title-font-size) !important;
    font-weight: var(--wsx-reg-title-font-size);
    font-style: normal;
    line-height: 1.5em !important;
    text-transform: var(--wsx-reg-title-case-transform) !important; }
  .wholesalex-form-wrapper .wsx-login-form-title-text {
    color: var(--wsx-login-title-color);
    font-size: var(--wsx-login-title-font-size) !important;
    font-weight: var(--wsx-login-title-font-weight);
    font-style: normal;
    line-height: 1.5em !important;
    text-transform: var(--wsx-login-title-case-transform) !important; }
  .wholesalex-form-wrapper .wholesalex-registration-form-subtitle-text {
    color: var(--wsx-reg-description-color);
    font-size: var(--wsx-reg-description-font-size) !important;
    font-weight: var(--wsx-reg-description-font-weight);
    font-style: normal;
    line-height: 1.5em !important;
    text-transform: var(--wsx-reg-description-case-transform) !important; }
  .wholesalex-form-wrapper .wholesalex-login-form-subtitle-text {
    color: var(--wsx-login-description-color);
    font-size: var(--wsx-login-description-font-size) !important;
    font-weight: var(--wsx-login-description-font-weight);
    font-style: normal;
    line-height: 1.5 !important;
    text-transform: var(--wsx-login-description-case-transform) !important; }
  .wholesalex-form-wrapper .wholesalex-login-form {
    width: 100%;
    max-width: var(--wsx-login-width);
    box-sizing: border-box;
    border: var(--wsx-login-border-width) solid var(--wsx-login-border-color);
    border-radius: var(--wsx-login-border-radius);
    background-color: var(--wsx-login-bg);
    padding: var(--wsx-login-padding) 16px var(--wsx-login-padding) 16px; }
    .wholesalex-form-wrapper .wholesalex-login-form > * {
      margin: 0 auto; }
    .wholesalex-form-wrapper .wholesalex-login-form .wsx-fields-container {
      display: flex;
      flex-direction: column;
      gap: 25px; }
    .wholesalex-form-wrapper .wholesalex-login-form .wholesalex-login-form-title {
      margin-bottom: 30px !important; }
  .wholesalex-form-wrapper .wsx-login-fields input {
    caret-color: var(--wsx-login-input-color); }
    .wholesalex-form-wrapper .wsx-login-fields input::placeholder {
      color: var(--wsx-login-input-placeholder-color) !important; }
    .wholesalex-form-wrapper .wsx-login-fields input::-ms-input-placeholder {
      color: var(--wsx-login-input-placeholder-color) !important; }
  .wholesalex-form-wrapper .wholesalex-registration-form {
    width: 100%;
    max-width: var(--wsx-form-reg-width);
    box-sizing: border-box;
    border-radius: var(--wsx-form-reg-border-radius);
    border: var(--wsx-form-reg-border-width) solid var(--wsx-form-reg-border-color);
    background-color: var(--wsx-form-reg-bg);
    padding: var(--wsx-form-reg-padding) 16px var(--wsx-form-reg-padding) 16px; }
    .wholesalex-form-wrapper .wholesalex-registration-form > * {
      margin: 0 auto; }
  .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > select option {
    background-color: var(--wsx-form-reg-bg) !important; }
  .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > select option, .wholesalex-form-wrapper .wsx-reg-fields .wsx-privacy-policy {
    color: var(--wsx-input-color) !important; }
  .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > input, .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > textarea {
    caret-color: var(--wsx-input-color); }
    .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > input::placeholder, .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > textarea::placeholder {
      color: var(--wsx-input-placeholder-color) !important; }
    .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > input::-ms-input-placeholder, .wholesalex-form-wrapper .wsx-reg-fields .wsx-form-field > textarea::-ms-input-placeholder {
      color: var(--wsx-input-placeholder-color) !important; }
  .wholesalex-form-wrapper .wsx-form-field > textarea,
  .wholesalex-form-wrapper .wsx-form-field > select,
  .wholesalex-form-wrapper .wsx-form-field > input {
    display: block;
    width: 100% !important;
    min-width: auto !important;
    line-height: 1.6em;
    box-sizing: border-box; }
    .wholesalex-form-wrapper .wsx-form-field > textarea:focus,
    .wholesalex-form-wrapper .wsx-form-field > select:focus,
    .wholesalex-form-wrapper .wsx-form-field > input:focus {
      box-shadow: none !important;
      outline: none !important; }
    .wholesalex-form-wrapper .wsx-form-field > textarea[type='date']::-webkit-calendar-picker-indicator,
    .wholesalex-form-wrapper .wsx-form-field > select[type='date']::-webkit-calendar-picker-indicator,
    .wholesalex-form-wrapper .wsx-form-field > input[type='date']::-webkit-calendar-picker-indicator {
      background-color: var(--wsx-input-placeholder-color); }
  .wholesalex-form-wrapper .wsx-form-btn-wrapper {
    text-align: var(--wsx-form-button-align);
    margin-bottom: 1.5rem; }
  .wholesalex-form-wrapper button.wsx-register-btn {
    color: var(--wsx-form-button-color) !important;
    border: var(--wsx-form-button-border-width) solid var(--wsx-form-button-border-color);
    background-color: var(--wsx-form-button-bg) !important;
    transition: .3s;
    cursor: pointer; }
    .wholesalex-form-wrapper button.wsx-register-btn:hover {
      color: var(--wsx-form-button-hover-color) !important;
      border-color: var(--wsx-form-button-hover-border-color) !important;
      background-color: var(--wsx-form-button-hover-bg) !important; }
    .wholesalex-form-wrapper button.wsx-register-btn:disabled {
      opacity: .5; }
  .wholesalex-form-wrapper .wholesalex-login-form .wsx-form-btn-wrapper >
button.wsx-login-btn {
    color: var(--wsx-login-form-button-color);
    border: var(--wsx-form-button-border-width) solid var(--wsx-login-form-button-border-color);
    background-color: var(--wsx-login-form-button-bg);
    transition: .3s;
    cursor: pointer; }
    .wholesalex-form-wrapper .wholesalex-login-form .wsx-form-btn-wrapper >
button.wsx-login-btn:hover {
      color: var(--wsx-login-form-button-hover-color);
      border-color: var(--wsx-login-form-button-hover-border-color);
      background-color: var(--wsx-login-form-button-hover-bg); }
  .wholesalex-form-wrapper button.wsx-register-btn,
  .wholesalex-form-wrapper button.wsx-login-btn {
    width: 100%;
    max-width: var(--wsx-form-button-width);
    min-height: auto !important;
    display: inline-block;
    border-radius: var(--wsx-form-button-border-radius);
    padding: var(--wsx-form-button-padding) !important;
    text-align: center;
    margin-top: 36px;
    box-sizing: border-box;
    font-size: var(--wsx-form-button-font-size) !important;
    font-weight: var(--wsx-form-button-weight);
    font-style: normal;
    line-height: 1.6em !important;
    text-transform: var(--wsx-form-button-case-transform) !important; }

/* ============= Form Separator =========== */
.wsx-form-separator {
  width: var(--wsx-form-container-separator);
  display: block;
  background-color: var(--wsx-form-container-border-color); }

/* ============= Input Label  =========== */
.wsx-field-heading {
  margin-bottom: 8px; }

label.wsx-form-label, .wsx-form-label, .wsx-form-field-label {
  color: var(--wsx-form-label-color) !important;
  font-size: var(--wsx-form-label-font-size) !important;
  font-weight: var(--wsx-form-label-weight);
  font-style: normal;
  line-height: 1.6em !important;
  text-transform: var(--wsx-form-label-case-transform) !important; }

.wsx-login-fields label.wsx-form-label, .wsx-login-fields .wsx-form-label, .wsx-login-fields .wsx-form-field-label {
  color: var(--wsx-login-form-label-color) !important; }

.wsx-login-fields .wsx-form-field-help-message {
  color: var(--wsx-login-input-color); }

/* ============= Help Message =========== */
.wsx-form-field-help-message, .wsx-form-field-warning-message {
  font-style: italic;
  font-size: 12px;
  display: block;
  margin-top: 4px; }

.wsx-form-field-warning-message {
  color: var(--wsx-input-warning-color); }

/* ======= Heading & Settings Dropdown ======= */
.wsx-reg-form-heading,
.wholesalex-login-form-title {
  position: relative; }
  .wsx-reg-form-heading .wsx-reg-form-heading-text,
  .wsx-reg-form-heading .wsx-login-form-title-text,
  .wholesalex-login-form-title .wsx-reg-form-heading-text,
  .wholesalex-login-form-title .wsx-login-form-title-text {
    margin-bottom: 10px; }

/* ===============================  Input Variation Common Style End ================================*/
/* ===============================  File Input Style Start ================================*/
.wsx-form-file .wsx-field-content input {
  opacity: 0;
  overflow: hidden;
  position: absolute;
  z-index: 0; }
  .wsx-form-file .wsx-field-content input::-webkit-file-upload-button {
    visibility: hidden; }

.wsx-form-file .wsx-field-content .wsx-file-label {
  color: var(--wsx-input-color);
  background-color: var(--wsx-input-bg);
  display: flex !important;
  justify-content: flex-start;
  align-items: center;
  gap: 8px; }
  .wsx-form-file .wsx-field-content .wsx-file-label span {
    color: var(--wsx-form-label-color);
    border-radius: 1px;
    border: 1px solid var(--wsx-form-label-color);
    padding: 2px 8px;
    display: flex !important;
    justify-content: center;
    align-items: center;
    gap: 6px; }
    .wsx-form-file .wsx-field-content .wsx-file-label span svg {
      stroke: var(--wsx-form-label-color); }
      .wsx-form-file .wsx-field-content .wsx-file-label span svg path {
        stroke: var(--wsx-form-label-color) !important; }

body .wholesalex-form-wrapper .wsx-file-outline .wsx-form-label {
  position: absolute;
  transform: translate(16px, -50%);
  gap: 5px;
  padding: 0px 5px; }

body .wholesalex-form-wrapper .wsx-file-outline .wsx-clone-label {
  color: transparent !important;
  background-color: var(--wsx-input-bg);
  padding: 0px 5px;
  height: var(--wsx-input-border-width);
  transform: translate(16px, 0%); }
  body .wholesalex-form-wrapper .wsx-file-outline .wsx-clone-label > span {
    visibility: hidden; }

/* ===============================  File Input Style End ================================*/
/* ===============================  Radio Style Start ================================*/
.wsx-field-radio .wsx-field-content {
  border: 0px !important; }

.wsx-field-radio .wholesalex-field-wrap > div {
  color: var(--wsx-input-color); }

.wsx-field-radio .wholesalex-field-wrap > input {
  accent-color: var(--wsx-input-color);
  height: 17px !important;
  width: 17px !important;
  appearance: auto !important; }
  .wsx-field-radio .wholesalex-field-wrap > input:checked::before {
    content: none !important; }
  .wsx-field-radio .wholesalex-field-wrap > input:hover {
    accent-color: var(--wsx-input-focus-color); }
  .wsx-field-radio .wholesalex-field-wrap > input:focus {
    box-shadow: unset !important; }

/* ===============================  Radio Style End ================================*/
/* ===============================  Checkbox Style Start ================================*/
.wsx-form-checkbox .wholesalex-field-wrap > input {
  height: 16px !important;
  width: 16px !important;
  min-width: 16px;
  border-radius: 2px;
  overflow: hidden;
  appearance: unset !important;
  display: flex !important;
  justify-content: center;
  align-items: center;
  margin-top: 6px !important; }
  .wsx-form-checkbox .wholesalex-field-wrap > input:checked::before {
    content: " \\2714" !important;
    width: 16px !important;
    height: 16px !important;
    font-size: 11px;
    border-radius: 2px;
    margin: 0px !important;
    background-color: var(--wsx-input-bg) !important;
    display: flex !important;
    justify-content: center;
    align-items: center; }

.wsx-login-fields {
  caret-color: var(--wsx-login-input-color); }
  .wsx-login-fields .wsx-form-checkbox .wholesalex-field-wrap > input {
    border: 1.5px solid var(--wsx-login-input-color); }
    .wsx-login-fields .wsx-form-checkbox .wholesalex-field-wrap > input:checked::before {
      color: var(--wsx-login-input-color);
      background-color: var(--wsx-login-input-bg) !important; }
  .wsx-login-fields .wsx-form-checkbox .wsx-field-content {
    border-color: var(--wsx-login-input-border-color); }
  .wsx-login-fields .wsx-form-checkbox label {
    color: var(--wsx-login-input-color) !important; }

.wsx-reg-fields {
  caret-color: var(--wsx-input-color); }
  .wsx-reg-fields .wsx-form-field-help-message {
    color: var(--wsx-input-color); }
  .wsx-reg-fields .wsx-form-checkbox .wholesalex-field-wrap > input {
    border: 1.5px solid var(--wsx-input-color);
    accent-color: transparent !important; }
    .wsx-reg-fields .wsx-form-checkbox .wholesalex-field-wrap > input:checked::before {
      color: var(--wsx-input-color); }

/* ===============================  Checkbox Style End ================================*/
/* ===============================  Checkbox & Radio Common Start ================================*/
.wsx-form-field.wsx-field-radio .wholesalex-field-wrap {
  display: flex !important;
  justify-content: flex-start;
  align-items: center; }

.wsx-form-field.wsx-form-checkbox .wholesalex-field-wrap {
  display: flex !important;
  justify-content: flex-start;
  align-items: flex-start; }

.wsx-form-field.wsx-form-checkbox .wholesalex-field-wrap, .wsx-form-field.wsx-field-radio .wholesalex-field-wrap {
  gap: 8px; }
  .wsx-form-field.wsx-form-checkbox .wholesalex-field-wrap input, .wsx-form-field.wsx-field-radio .wholesalex-field-wrap input {
    margin: 0px;
    cursor: pointer; }
    .wsx-form-field.wsx-form-checkbox .wholesalex-field-wrap input:focus, .wsx-form-field.wsx-field-radio .wholesalex-field-wrap input:focus {
      outline: none;
      box-shadow: none; }

.wsx-form-field.wsx-form-checkbox .wsx-form-field-help-message, .wsx-form-field.wsx-field-radio .wsx-form-field-help-message {
  margin-top: 8px !important; }

.wsx-form-field.wsx-form-checkbox .wsx-field-content, .wsx-form-field.wsx-field-radio .wsx-field-content {
  display: flex;
  row-gap: 20px;
  column-gap: 32px;
  flex-wrap: wrap; }
  .wsx-form-field.wsx-form-checkbox .wsx-field-content label, .wsx-form-field.wsx-field-radio .wsx-field-content label {
    color: var(--wsx-input-color);
    margin: 0px !important; }

/* ===============================  Checkbox & Radio Common End ================================*/
/* ===============================  Common Variation Style  Start ================================*/
.wholesalex-registration-form .wsx_variation_1 .wsx-reg-form-row-setting,
.wholesalex-registration-form .wsx_variation_3 .wsx-reg-form-row-setting {
  position: static; }

.wsx_variation_1 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_1 .wsx-login-fields .wsx-form-field > textarea,
.wsx_variation_1 .wsx-login-fields .wsx-form-field > select,
.wsx_variation_2 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_2 .wsx-login-fields .wsx-form-field > textarea,
.wsx_variation_2 .wsx-login-fields .wsx-form-field > select,
.wsx_variation_3 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_3 .wsx-login-fields .wsx-form-field > textarea,
.wsx_variation_3 .wsx-login-fields .wsx-form-field > select,
.wsx_variation_6 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_6 .wsx-login-fields .wsx-form-field > textarea,
.wsx_variation_6 .wsx-login-fields .wsx-form-field > select,
.wsx_variation_7 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_7 .wsx-login-fields .wsx-form-field > textarea,
.wsx_variation_7 .wsx-login-fields .wsx-form-field > select {
  color: var(--wsx-login-input-color);
  background-color: var(--wsx-login-input-bg) !important;
  padding: var(--wsx-input-padding);
  border-radius: var(--wsx-input-border-radius);
  caret-color: var(--wsx-login-input-color);
  border: var(--wsx-input-border-width) solid var(--wsx-login-input-border-color);
  font-size: var(--wsx-input-font-size) !important;
  font-weight: var(--wsx-input-weight);
  font-style: normal;
  line-height: 1.6em !important; }
  .wsx_variation_1 .wsx-login-fields .wsx-form-field > input:focus,
  .wsx_variation_1 .wsx-login-fields .wsx-form-field > textarea:focus,
  .wsx_variation_1 .wsx-login-fields .wsx-form-field > select:focus,
  .wsx_variation_2 .wsx-login-fields .wsx-form-field > input:focus,
  .wsx_variation_2 .wsx-login-fields .wsx-form-field > textarea:focus,
  .wsx_variation_2 .wsx-login-fields .wsx-form-field > select:focus,
  .wsx_variation_3 .wsx-login-fields .wsx-form-field > input:focus,
  .wsx_variation_3 .wsx-login-fields .wsx-form-field > textarea:focus,
  .wsx_variation_3 .wsx-login-fields .wsx-form-field > select:focus,
  .wsx_variation_6 .wsx-login-fields .wsx-form-field > input:focus,
  .wsx_variation_6 .wsx-login-fields .wsx-form-field > textarea:focus,
  .wsx_variation_6 .wsx-login-fields .wsx-form-field > select:focus,
  .wsx_variation_7 .wsx-login-fields .wsx-form-field > input:focus,
  .wsx_variation_7 .wsx-login-fields .wsx-form-field > textarea:focus,
  .wsx_variation_7 .wsx-login-fields .wsx-form-field > select:focus {
    color: var(--wsx-login-input-focus-color) !important;
    background-color: var(--wsx-login-input-focus-bg) !important;
    border-color: var(--wsx-login-input-focus-border-color) !important; }

.wsx_variation_1 .wsx-login-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_1 .wsx-login-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_1 .wsx-login-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_2 .wsx-login-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_2 .wsx-login-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_2 .wsx-login-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_3 .wsx-login-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_3 .wsx-login-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_3 .wsx-login-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_6 .wsx-login-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_6 .wsx-login-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_6 .wsx-login-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_7 .wsx-login-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_7 .wsx-login-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_7 .wsx-login-fields .wsx-form-field.wsx-field-warning > select {
  color: var(--wsx-login-input-warning-color) !important;
  background-color: var(--wsx-login-input-warning-bg) !important;
  border-color: var(--wsx-login-input-warning-border-color) !important; }

.wsx_variation_1 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field > select {
  color: var(--wsx-input-color);
  background-color: var(--wsx-input-bg) !important;
  caret-color: var(--wsx-input-color); }
  .wsx_variation_1 .wsx-reg-fields .wsx-form-field > input:focus,
  .wsx_variation_1 .wsx-reg-fields .wsx-form-field > textarea:focus,
  .wsx_variation_1 .wsx-reg-fields .wsx-form-field > select:focus,
  .wsx_variation_2 .wsx-reg-fields .wsx-form-field > input:focus,
  .wsx_variation_2 .wsx-reg-fields .wsx-form-field > textarea:focus,
  .wsx_variation_2 .wsx-reg-fields .wsx-form-field > select:focus,
  .wsx_variation_3 .wsx-reg-fields .wsx-form-field > input:focus,
  .wsx_variation_3 .wsx-reg-fields .wsx-form-field > textarea:focus,
  .wsx_variation_3 .wsx-reg-fields .wsx-form-field > select:focus,
  .wsx_variation_6 .wsx-reg-fields .wsx-form-field > input:focus,
  .wsx_variation_6 .wsx-reg-fields .wsx-form-field > textarea:focus,
  .wsx_variation_6 .wsx-reg-fields .wsx-form-field > select:focus,
  .wsx_variation_7 .wsx-reg-fields .wsx-form-field > input:focus,
  .wsx_variation_7 .wsx-reg-fields .wsx-form-field > textarea:focus,
  .wsx_variation_7 .wsx-reg-fields .wsx-form-field > select:focus {
    color: var(--wsx-input-focus-color) !important;
    background-color: var(--wsx-input-focus-bg) !important;
    border-color: var(--wsx-input-focus-border-color) !important; }

.wsx_variation_1 .wsx-reg-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_1 .wsx-reg-fields .wsx-form-field.wsx-field-warning > .wsx-field-content > .wsx-file-label,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_2 .wsx-reg-fields .wsx-form-field.wsx-field-warning > .wsx-field-content > .wsx-file-label,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_3 .wsx-reg-fields .wsx-form-field.wsx-field-warning > .wsx-field-content > .wsx-file-label,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_6 .wsx-reg-fields .wsx-form-field.wsx-field-warning > .wsx-field-content > .wsx-file-label,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field.wsx-field-warning > input,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field.wsx-field-warning > textarea,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field.wsx-field-warning > select,
.wsx_variation_7 .wsx-reg-fields .wsx-form-field.wsx-field-warning > .wsx-field-content > .wsx-file-label {
  color: var(--wsx-input-warning-color) !important;
  background-color: var(--wsx-input-warning-bg) !important;
  border-color: var(--wsx-input-warning-border-color) !important; }

/* ===============================  Common Variation Style  End ================================*/
/* ===============================  Variation Style 2 Start ================================*/
.wsx_variation_2 > .wsx-reg-form-row-setting {
  position: absolute !important;
  right: 0px !important;
  bottom: 100% !important; }

.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > input,
.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > textarea,
.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > select {
  color: var(--wsx-input-color);
  background-color: var(--wsx-input-bg) !important;
  caret-color: var(--wsx-input-color); }
  .wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > input:focus + .wsx-form-label,
  .wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > textarea:focus + .wsx-form-label,
  .wsx_variation_2 .wsx-reg-fields .wsx-outline-focus > select:focus + .wsx-form-label {
    background-color: var(--wsx-input-focus-bg) !important; }

.wsx_variation_2 .wsx-reg-fields .wsx-outline-focus .wsx-form-label.wsx-clone-label {
  background-color: var(--wsx-input-bg); }

.wsx_variation_2 .wsx-login-fields .wsx-outline-focus > input,
.wsx_variation_2 .wsx-login-fields .wsx-outline-focus > textarea,
.wsx_variation_2 .wsx-login-fields .wsx-outline-focus > select {
  color: var(--wsx-login-input-color);
  background-color: var(--wsx-login-input-bg) !important;
  padding: var(--wsx-input-padding);
  border-radius: var(--wsx-input-border-radius);
  caret-color: var(--wsx-login-input-color);
  border: var(--wsx-input-border-width) solid var(--wsx-login-input-border-color);
  font-size: var(--wsx-input-font-size) !important;
  font-weight: var(--wsx-input-weight);
  font-style: normal;
  line-height: 1.6em !important; }
  .wsx_variation_2 .wsx-login-fields .wsx-outline-focus > input:focus + .wsx-form-label,
  .wsx_variation_2 .wsx-login-fields .wsx-outline-focus > textarea:focus + .wsx-form-label,
  .wsx_variation_2 .wsx-login-fields .wsx-outline-focus > select:focus + .wsx-form-label {
    background-color: var(--wsx-login-input-focus-bg) !important; }

.wsx_variation_2 .wsx-login-fields .wsx-outline-focus .wsx-form-label.wsx-clone-label {
  background-color: var(--wsx-login-input-bg); }

.wsx_variation_2 .wsx-outline-focus {
  display: flex;
  background: transparent;
  position: relative; }
  .wsx_variation_2 .wsx-outline-focus .wsx-form-label {
    padding: 0px 5px;
    position: absolute;
    transform: translate(16px, -50%);
    display: flex !important;
    justify-content: center;
    align-items: center;
    gap: 5px; }
    .wsx_variation_2 .wsx-outline-focus .wsx-form-label.wsx-clone-label {
      color: transparent !important;
      padding: 0px 5px;
      height: var(--wsx-input-border-width);
      transform: translate(16px, 0%); }

/* ===============================  Variation Style 2 End ================================*/
/* ===============================  Variation Style 3  Start ================================*/
.wsx_variation_3 .wsx-form-field > textarea,
.wsx_variation_3 .wsx-form-field > select,
.wsx_variation_3 .wsx-form-field .wsx-file-label,
.wsx_variation_3 .wsx-form-field > input {
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important;
  border-bottom-left-radius: 0px !important;
  border-bottom-right-radius: 0px !important; }
  .wsx_variation_3 .wsx-form-field > textarea:focus,
  .wsx_variation_3 .wsx-form-field > select:focus,
  .wsx_variation_3 .wsx-form-field .wsx-file-label:focus,
  .wsx_variation_3 .wsx-form-field > input:focus {
    border-top: 0px !important;
    border-left: 0px !important;
    border-right: 0px !important;
    border-bottom-left-radius: 0px !important;
    border-bottom-right-radius: 0px !important; }

/* ===============================  Variation Style 3  End ================================*/
/* ===============================  Variation Style 3 & 4 Start ================================*/
.wsx_variation_3 .wsx-form-file .wsx-file-label, .wsx_variation_4 .wsx-form-file .wsx-file-label {
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important; }

/* ===============================  Variation Style 3 & 4 End ================================*/
/* ===============================  Variation Style 4 Start ================================*/
.wsx_variation_4 .wsx-form-file .wsx-field-content {
  border-bottom-left-radius: 0px;
  border-bottom-right-radius: 0px;
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important; }

.wsx_variation_4 .wsx-outline-focus {
  border-bottom-left-radius: 0px !important;
  border-bottom-right-radius: 0px !important;
  padding-top: calc(var(--wsx-input-padding) / 2) !important;
  padding-bottom: calc(var(--wsx-input-padding) / 2) !important;
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important; }

/* ===============================  Variation Style 4 End ================================*/
/* ===============================  Variation Style 4 & 5 Start ================================*/
.wsx_variation_4 .wsx-field-content, .wsx_variation_5 .wsx-field-content, .wsx_variation_8 .wsx-field-content {
  background-color: var(--wsx-input-bg);
  display: block; }

.wsx_variation_4 .wsx-file-label, .wsx_variation_5 .wsx-file-label, .wsx_variation_8 .wsx-file-label {
  background-color: unset !important;
  padding: 0px !important;
  border: 0px !important;
  border-radius: 0px !important;
  flex-direction: column !important;
  align-items: flex-start !important; }
  .wsx_variation_4 .wsx-file-label span, .wsx_variation_5 .wsx-file-label span, .wsx_variation_8 .wsx-file-label span {
    padding: 0px;
    border: 0px; }
  .wsx_variation_4 .wsx-file-label .wsx-form-label, .wsx_variation_5 .wsx-file-label .wsx-form-label, .wsx_variation_8 .wsx-file-label .wsx-form-label {
    display: flex;
    gap: 5px; }
    .wsx_variation_4 .wsx-file-label .wsx-form-label span, .wsx_variation_5 .wsx-file-label .wsx-form-label span, .wsx_variation_8 .wsx-file-label .wsx-form-label span {
      border: 0px !important;
      padding: 0px !important; }

.wsx_variation_4 .wsx-file-label_wrap, .wsx_variation_5 .wsx-file-label_wrap {
  display: flex !important;
  align-items: center;
  gap: 20px; }

.wsx_variation_4 .wsx-reg-fields .wsx-form-field.wsx-outline-focus, .wsx_variation_5 .wsx-reg-fields .wsx-form-field.wsx-outline-focus {
  color: var(--wsx-input-color);
  background-color: var(--wsx-input-bg) !important;
  caret-color: var(--wsx-input-color); }
  .wsx_variation_4 .wsx-reg-fields .wsx-form-field.wsx-outline-focus:focus-within, .wsx_variation_5 .wsx-reg-fields .wsx-form-field.wsx-outline-focus:focus-within {
    border-style: solid;
    color: var(--wsx-input-focus-color) !important;
    background-color: var(--wsx-input-focus-bg) !important;
    border-color: var(--wsx-input-focus-border-color) !important; }

.wsx_variation_4 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_4 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_4 .wsx-reg-fields .wsx-form-field > textarea, .wsx_variation_5 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_5 .wsx-reg-fields .wsx-form-field > select,
.wsx_variation_5 .wsx-reg-fields .wsx-form-field > textarea {
  color: var(--wsx-input-color); }

.wsx_variation_4 .wsx-login-fields .wsx-form-field.wsx-outline-focus, .wsx_variation_5 .wsx-login-fields .wsx-form-field.wsx-outline-focus {
  color: var(--wsx-login-input-color);
  background-color: var(--wsx-login-input-bg) !important;
  padding: var(--wsx-input-padding);
  border-radius: var(--wsx-input-border-radius);
  caret-color: var(--wsx-login-input-color);
  border: var(--wsx-input-border-width) solid var(--wsx-login-input-border-color);
  font-size: var(--wsx-input-font-size) !important;
  font-weight: var(--wsx-input-weight);
  font-style: normal;
  line-height: 1.6em !important; }
  .wsx_variation_4 .wsx-login-fields .wsx-form-field.wsx-outline-focus:focus-within, .wsx_variation_5 .wsx-login-fields .wsx-form-field.wsx-outline-focus:focus-within {
    border-style: solid;
    color: var(--wsx-login-input-focus-color) !important;
    background-color: var(--wsx-login-input-focus-bg) !important;
    border-color: var(--wsx-login-input-focus-border-color) !important; }

.wsx_variation_4 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_4 .wsx-login-fields .wsx-form-field > select,
.wsx_variation_4 .wsx-login-fields .wsx-form-field > textarea, .wsx_variation_5 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_5 .wsx-login-fields .wsx-form-field > select,
.wsx_variation_5 .wsx-login-fields .wsx-form-field > textarea {
  color: var(--wsx-login-input-color); }

.wsx_variation_4 .wsx-form-field.wsx-outline-focus, .wsx_variation_5 .wsx-form-field.wsx-outline-focus {
  position: relative;
  border-style: solid;
  margin-top: 4px;
  display: flex !important;
  justify-content: center;
  align-items: flex-start;
  flex-direction: column-reverse; }
  .wsx_variation_4 .wsx-form-field.wsx-outline-focus > select,
  .wsx_variation_4 .wsx-form-field.wsx-outline-focus > input, .wsx_variation_5 .wsx-form-field.wsx-outline-focus > select,
  .wsx_variation_5 .wsx-form-field.wsx-outline-focus > input {
    border: none !important;
    box-shadow: none;
    background-color: transparent;
    padding: 0px !important;
    margin: 0px !important;
    min-height: fit-content !important; }
  .wsx_variation_4 .wsx-form-field.wsx-outline-focus > textarea, .wsx_variation_5 .wsx-form-field.wsx-outline-focus > textarea {
    border: none !important;
    box-shadow: none;
    background-color: transparent;
    padding: 0px !important;
    margin: 0px !important; }

.wsx_variation_4 .wsx-login-fields .wsx-field-warning.wsx-form-field:not(.wsx-form-checkbox), .wsx_variation_5 .wsx-login-fields .wsx-field-warning.wsx-form-field:not(.wsx-form-checkbox) {
  color: var(--wsx-login-input-warning-color) !important;
  background-color: var(--wsx-login-input-warning-bg) !important;
  border-color: var(--wsx-login-input-warning-border-color) !important; }

.wsx_variation_4 .wsx-reg-fields .wsx-field-warning.wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox):not(.wsx-form-file), .wsx_variation_4 .wsx-reg-fields .wsx-form-file.wsx-field-warning .wsx-field-content, .wsx_variation_5 .wsx-reg-fields .wsx-field-warning.wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox):not(.wsx-form-file), .wsx_variation_5 .wsx-reg-fields .wsx-form-file.wsx-field-warning .wsx-field-content {
  color: var(--wsx-input-warning-color) !important;
  background-color: var(--wsx-input-warning-bg) !important;
  border-color: var(--wsx-input-warning-border-color) !important; }

.wsx_variation_4 .wsx-field-radio .wsx-field-content, .wsx_variation_4 .wsx-form-checkbox .wsx-field-content, .wsx_variation_5 .wsx-field-radio .wsx-field-content, .wsx_variation_5 .wsx-form-checkbox .wsx-field-content {
  background-color: unset;
  border: 0px;
  padding: 0px; }

/* ===============================  Variation Style 4 & 5 End ================================*/
/* ===============================  Variation Style 5 ================================*/
.wsx_variation_5 .wsx-outline-focus {
  padding-top: calc(var(--wsx-input-padding) / 2) !important;
  padding-bottom: calc(var(--wsx-input-padding) / 2) !important; }

/* ===============================  Variation Style 5 End ================================*/
/* ===============================  Variation Style 6 Start ================================*/
.wsx_variation_6 .wholesalex-registration-form-column:has(> .wsx-form-select) .wsx-field-heading {
  position: static !important; }

.wsx_variation_6 .wsx-form-date,
.wsx_variation_6 .wsx-form-select {
  display: flex !important;
  justify-content: center;
  align-items: flex-start;
  flex-direction: column-reverse; }

.wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) {
  display: flex !important;
  justify-content: flex-start;
  align-items: center; }
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > select,
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > input {
    height: fit-content !important;
    box-sizing: border-box; }
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > textarea:focus ~ .wsx-form-label,
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > textarea:focus-within ~ .wsx-form-label,
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > input:focus ~ .wsx-form-label,
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > input:focus-within ~ .wsx-form-label {
    display: none !important; }
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) > input:not([value]):not(:defined) {
    display: none !important; }
  .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) .wsx-form-label {
    position: absolute;
    padding-left: var(--wsx-input-padding); }
    .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) .wsx-form-label:focus, .wsx_variation_6 .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) .wsx-form-label:focus-within {
      display: none !important; }

/* ===============================  Variation Style 6 End ================================*/
/* ===============================  Variation Style 7 Start ================================*/
.wsx_variation_7 .wsx-outline-focus {
  display: flex !important;
  background: transparent;
  position: relative; }
  .wsx_variation_7 .wsx-outline-focus > input,
  .wsx_variation_7 .wsx-outline-focus > textarea,
  .wsx_variation_7 .wsx-outline-focus > select {
    color: var(--wsx-input-color);
    background-color: var(--wsx-input-bg) !important;
    caret-color: var(--wsx-input-color); }
    .wsx_variation_7 .wsx-outline-focus > input:focus ~ .wsx-form-label, .wsx_variation_7 .wsx-outline-focus > input:focus ~ .wsx-clone-label,
    .wsx_variation_7 .wsx-outline-focus > textarea:focus ~ .wsx-form-label,
    .wsx_variation_7 .wsx-outline-focus > textarea:focus ~ .wsx-clone-label,
    .wsx_variation_7 .wsx-outline-focus > select:focus ~ .wsx-form-label,
    .wsx_variation_7 .wsx-outline-focus > select:focus ~ .wsx-clone-label {
      transform: translate(16px, -50%);
      padding-left: 6px;
      padding-right: 6px;
      top: 0px !important; }
    .wsx_variation_7 .wsx-outline-focus > input:not(:placeholder-shown) ~ .wsx-form-label,
    .wsx_variation_7 .wsx-outline-focus > textarea:not(:placeholder-shown) ~ .wsx-form-label,
    .wsx_variation_7 .wsx-outline-focus > select:not(:placeholder-shown) ~ .wsx-form-label {
      transform: translate(16px, -50%) !important;
      padding-left: 6px !important;
      padding-right: 6px !important;
      top: 0px !important; }
    .wsx_variation_7 .wsx-outline-focus > input:not(:placeholder-shown) ~ .wsx-clone-label,
    .wsx_variation_7 .wsx-outline-focus > textarea:not(:placeholder-shown) ~ .wsx-clone-label,
    .wsx_variation_7 .wsx-outline-focus > select:not(:placeholder-shown) ~ .wsx-clone-label {
      transform: translate(16px, 0%) !important;
      padding-top: 0px !important;
      padding-bottom: 0px !important;
      background-color: var(--wsx-input-bg); }
    .wsx_variation_7 .wsx-outline-focus > input:focus ~ .wsx-clone-label,
    .wsx_variation_7 .wsx-outline-focus > textarea:focus ~ .wsx-clone-label,
    .wsx_variation_7 .wsx-outline-focus > select:focus ~ .wsx-clone-label {
      transform: translate(16px, 0%) !important;
      padding-top: 0px !important;
      padding-bottom: 0px;
      top: 0px !important;
      background-color: var(--wsx-input-focus-bg) !important; }
  .wsx_variation_7 .wsx-outline-focus .wsx-form-label {
    padding: var(--wsx-input-padding);
    z-index: 999 !important;
    transform: translate(var(--wsx-input-padding), -50%);
    transition: .3s;
    position: absolute !important;
    top: 50% !important;
    left: 0% !important; }
    .wsx_variation_7 .wsx-outline-focus .wsx-form-label.wsx-clone-label {
      position: absolute;
      height: calc(var(--wsx-input-border-width)* 2);
      z-index: 0 !important;
      color: transparent !important;
      transform: translate(0px, -50%) !important;
      padding-top: 0px !important;
      padding-bottom: 0px !important;
      transition: unset !important; }

.wsx_variation_7 .wsx-login-fields .wsx-outline-focus > input:not(:placeholder-shown) ~ .wsx-clone-label,
.wsx_variation_7 .wsx-login-fields .wsx-outline-focus > textarea:not(:placeholder-shown) ~ .wsx-clone-label,
.wsx_variation_7 .wsx-login-fields .wsx-outline-focus > select:not(:placeholder-shown) ~ .wsx-clone-label {
  background-color: var(--wsx-login-input-focus-bg) !important; }

.wsx_variation_7 .wsx-login-fields .wsx-outline-focus > input:focus ~ .wsx-clone-label,
.wsx_variation_7 .wsx-login-fields .wsx-outline-focus > textarea:focus ~ .wsx-clone-label,
.wsx_variation_7 .wsx-login-fields .wsx-outline-focus > select:focus ~ .wsx-clone-label {
  background-color: var(--wsx-login-input-focus-bg) !important; }

/* ===============================  Variation Style 7 End ================================*/
/* ===============================  Variation Style 8 Start ================================*/
.wsx_variation_8 .wsx-form-field > input,
.wsx_variation_8 .wsx-form-field > textarea,
.wsx_variation_8 .wsx-form-field > select {
  border: none !important;
  box-sizing: border-box;
  background-color: transparent !important;
  padding: 0px; }

.wsx_variation_8 .wsx-form-field.wsx-outline-focus {
  cursor: text;
  display: block;
  padding-left: 0px !important;
  margin-top: 0px !important;
  box-sizing: border-box;
  color: var(--wsx-input-color);
  background-color: var(--wsx-input-bg) !important;
  caret-color: var(--wsx-input-color);
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important;
  border-bottom-left-radius: 0px !important;
  border-bottom-right-radius: 0px !important; }

.wsx_variation_8 .wsx-login-fields .wsx-form-field > input,
.wsx_variation_8 .wsx-login-fields .wsx-form-field > textarea,
.wsx_variation_8 .wsx-login-fields .wsx-form-field > select {
  color: var(--wsx-login-input-color); }
  .wsx_variation_8 .wsx-login-fields .wsx-form-field > input:focus,
  .wsx_variation_8 .wsx-login-fields .wsx-form-field > textarea:focus,
  .wsx_variation_8 .wsx-login-fields .wsx-form-field > select:focus {
    color: var(--wsx-login-input-focus-color) !important; }

.wsx_variation_8 .wsx-login-fields .wsx-form-field.wsx-outline-focus {
  background-color: var(--wsx-login-input-bg) !important; }
  .wsx_variation_8 .wsx-login-fields .wsx-form-field.wsx-outline-focus:focus-within {
    border-color: var(--wsx-login-input-focus-color) !important;
    background-color: var(--wsx-login-input-focus-bg) !important; }

.wsx_variation_8 .wsx-login-fields .wsx-form-field.wsx-field-warning:not(.wsx-field-radio):not(.wsx-form-checkbox), .wsx_variation_8 .wsx-login-fields .wsx-form-field.wsx-field-warning.wsx-form-file > .wsx-field-content {
  color: var(--wsx-login-input-warning-color) !important;
  background-color: var(--wsx-login-input-warning-bg) !important;
  border-color: var(--wsx-login-input-warning-border-color) !important; }

.wsx_variation_8 .wsx-reg-fields .wsx-form-field > input,
.wsx_variation_8 .wsx-reg-fields .wsx-form-field > textarea,
.wsx_variation_8 .wsx-reg-fields .wsx-form-field > select {
  color: var(--wsx-input-color); }
  .wsx_variation_8 .wsx-reg-fields .wsx-form-field > input:focus,
  .wsx_variation_8 .wsx-reg-fields .wsx-form-field > textarea:focus,
  .wsx_variation_8 .wsx-reg-fields .wsx-form-field > select:focus {
    color: var(--wsx-input-focus-color) !important; }

.wsx_variation_8 .wsx-reg-fields .wsx-form-field.wsx-outline-focus {
  background-color: var(--wsx-input-bg) !important; }
  .wsx_variation_8 .wsx-reg-fields .wsx-form-field.wsx-outline-focus:focus-within {
    border-color: var(--wsx-input-focus-border-color) !important;
    background-color: var(--wsx-input-focus-bg) !important; }

.wsx_variation_8 .wsx-reg-fields .wsx-form-field.wsx-field-warning:not(.wsx-field-radio):not(.wsx-form-checkbox), .wsx_variation_8 .wsx-reg-fields .wsx-form-field.wsx-field-warning.wsx-form-file > .wsx-field-content {
  color: var(--wsx-input-warning-color) !important;
  background-color: var(--wsx-input-warning-bg) !important;
  border-color: var(--wsx-input-warning-border-color) !important; }

.wsx_variation_8 .wsx-field-radio .wsx-field-content, .wsx_variation_8 .wsx-form-checkbox .wsx-field-content {
  background-color: unset;
  border: 0px;
  padding: 0px; }

.wsx_variation_8 .wsx-file-label {
  flex-direction: column;
  align-items: flex-start !important;
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important;
  border-bottom-left-radius: 0px !important;
  border-bottom-right-radius: 0px !important; }

.wsx_variation_8 .wsx-file-label_wrap {
  display: flex !important;
  justify-content: flex-start;
  align-items: center;
  gap: 20px; }

.wsx_variation_8 .wsx-field-content {
  border-radius: var(--wsx-input-border-radius) var(--wsx-input-border-radius) 0px 0px;
  border-top: 0px !important;
  border-left: 0px !important;
  border-right: 0px !important; }

/* ===============================  Variation Style 8 End ================================*/
/* ===============================  Editor & Frontend Start ================================*/
.wsx-form-wrapper_frontend.wsx_variation_5 .wsx-field-content > .wsx-file-label, .wsx-form-wrapper_frontend.wsx_variation_4 .wsx-field-content > .wsx-file-label {
  flex-direction: row !important;
  align-items: center !important; }

[data-type='wholesalex/forms'] {
  width: 100% !important;
  max-width: 100% !important; }

[data-type='wholesalex/forms'] .wsx-reg-form-heading, .wp-block-wholesalex-forms.wholesalex-form .wsx-reg-form-heading, .wsx-form-wrapper_frontend .wsx-reg-form-heading {
  padding-bottom: 20px; }

[data-type='wholesalex/forms'] .wholesalex-fields-wrapper, .wp-block-wholesalex-forms.wholesalex-form .wholesalex-fields-wrapper, .wsx-form-wrapper_frontend .wholesalex-fields-wrapper {
  display: flex;
  flex-direction: column;
  gap: 25px; }

[data-type='wholesalex/forms'] .wholesalex-form-wrapper, [data-type='wholesalex/forms'].wholesalex-form-wrapper, .wp-block-wholesalex-forms.wholesalex-form .wholesalex-form-wrapper, .wp-block-wholesalex-forms.wholesalex-form.wholesalex-form-wrapper, .wsx-form-wrapper_frontend .wholesalex-form-wrapper, .wsx-form-wrapper_frontend.wholesalex-form-wrapper {
  overflow: hidden; }

[data-type='wholesalex/forms'] .wholesalex-registration-form .wholesalex-fields-wrapper, .wp-block-wholesalex-forms.wholesalex-form .wholesalex-fields-wrapper, .wsx-form-wrapper_frontend .wholesalex-fields-wrapper {
  margin-top: 20px !important; }

.wholesalex-form-preview-block-placeholder {
  flex-direction: column;
  display: flex !important;
  justify-content: flex-start;
  align-items: center; }

@media only screen and (max-width: 1100px) {
  .wholesalex-form-wrapper:not(.wsx-form-wrapper_frontend) {
    flex-direction: column;
    align-items: center; } }

@media only screen and (max-width: 991px) {
  .wsx-form-wrapper_frontend.wholesalex-form-wrapper {
    flex-direction: column;
    align-items: center; } }

@media only screen and (max-width: 768px) {
  .wholesalex-form-wrapper {
    display: block; } }

.wsx-reg-form-row.double-column {
  display: flex;
  justify-content: space-between;
  gap: 16px; }
  .wsx-reg-form-row.double-column > div {
    flex: 1; }

.wholesalex_circular_loading__wrapper {
  background-color: rgba(255, 255, 255, 0.5);
  margin: 0;
  transition: opacity 0.3s;
  position: absolute !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  z-index: 9999999;
  cursor: wait;
  height: 100vh;
  top: 0;
  position: fixed !important; }

.wholesalex_loading_spinner {
  width: 100%;
  text-align: center;
  margin-top: -21px;
  position: absolute !important;
  top: 50% !important;
  top: 50% !important; }

.wholesalex_circular_loading_icon {
  stroke-width: 2;
  stroke-dashoffset: 0;
  stroke-dasharray: 90, 150;
  stroke: var(--wholesalex-primary-color);
  stroke-linecap: round;
  animation: wholesalex_circular_loading 1.5s ease-in-out infinite; }

@keyframes wholesalex_circular_loading {
  0% {
    stroke-dasharray: 1, 140;
    stroke-dashoffset: 0; } }

.wholesalex_loading_spinner .move_circular {
  height: 42px;
  width: 42px;
  animation: circular_rotate 2s linear infinite; }

@keyframes circular_rotate {
  100% {
    transform: rotate(1turn); } }

.wsx_variation_1 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_2 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_3 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_4 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_5 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_7 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_8 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-login-form-label-color-warning) !important; }

.wsx_variation_1 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_2 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_3 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_4 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_5 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_7 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_8 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-login-form-label-color-active) !important; }

.wsx_variation_1 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_2 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_3 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_4 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_5 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_7 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_8 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-form-label-color-warning) !important; }

.wsx_variation_1 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_2 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_3 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_4 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_5 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_7 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label),
.wsx_variation_8 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-form-label-color-active) !important; }

.wsx_variation_6 .wsx-login-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-login-form-label-color-warning) !important; }

.wsx_variation_6 .wsx-login-fields .wsx-reg-form-row:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx_variation_6 .wsx-login-fields .wsx-field:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx_variation_6 .wsx-login-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-login-form-label-color-active) !important; }

.wsx_variation_6 .wsx-reg-fields .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-form-label-color-warning) !important; }

.wsx_variation_6 .wsx-reg-fields .wsx-reg-form-row:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx_variation_6 .wsx-reg-fields .wsx-field:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx_variation_6 .wsx-reg-fields .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {
  color: var(--wsx-form-label-color-active) !important; }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/CommonMixin.scss","webpack://./reactjs/src/assets/scss/Form.scss"],"names":[],"mappings":"AAAA;;0CCE0C;ADM1C;;sCCHsC;ADkBtC;;oDCfoD;AAPpD,iDAAA;AACA;;;;;;;;;;;;;;;;;;;;;;;;;EDUI,gDAA2B;EAC3B,oCCV+E;EDW/E,kBAAkB;EAEd,6BAAmC;EAGnC,0DAA0C,EAAA;;ACdlD;EACI,WAAW;EACX,iCAAiC,EAAA;;AAErC;;;;;;;;;;;;;;;;;;;EACI,6CAA6C;EAC7C,iCAAiC;EACjC,yEAAyE,EAAA;;AAG7E,iDAAA;AAyCA,qCAAA;AAEA;EACI,sBAAsB;EACtB,qDAAqD,EAAA;EAFzD;IAIQ,aAAa,EAAA;;AAGrB,0FAAA;AAEA,wGAAA;AACA;EACI,YAAY;EACZ,WAAW;EACX,sBAAsB;EACtB,2FAA2F;EAC3F,sDAAsD;EACtD,8CAA8C;EAC9C,0CAA0C;EDvE1C,wBAAwB;EACxB,uBCuE4B;EDtE5B,oBCsEqC,EAAA;EARzC;IAUQ,2BAA2B;IAC3B,eAAe;IACf,2BAA2B;IAC3B,sBAAsB,EAAA;EAb9B;IAgBQ,uBAAuB,EAAA;EAhB/B;IAmBQ,kBAAkB,EAAA;IAnB1B;MAqBY,yBAAyB;MACzB,gBAAgB,EAAA;EAtB5B;IA0BQ,kBAAkB,EAAA;EA1B1B;IA8BQ,iCAAiC;IDtFrC,oDAA2B;IAC3B,2CCsF8F;IDrF9F,kBAAkB;IAEd,6BAAmC;IAGnC,8DAA0C,EAAA;ECiDlD;IAkCQ,mCAAmC;ID1FvC,sDAA2B;IAC3B,+CC0FoG;IDzFpG,kBAAkB;IAEd,6BAAmC;IAGnC,gEAA0C,EAAA;ECiDlD;IAsCQ,uCAAuC;ID9F3C,0DAA2B;IAC3B,mDC8F4G;ID7F5G,kBAAkB;IAEd,6BAAmC;IAGnC,oEAA0C,EAAA;ECiDlD;IA0CQ,yCAAyC;IDlG7C,4DAA2B;IAC3B,qDCkGgH;IDjGhH,kBAAkB;IAEd,2BAAmC;IAGnC,sEAA0C,EAAA;ECiDlD;IAgDQ,WAAW;IACX,iCAAiC;IACjC,sBAAsB;IACtB,yEAAyE;IACzE,6CAA6C;IAC7C,qCAAqC;IACrC,oEAAoE,EAAA;IAtD5E;MAyDY,cAAc,EAAA;IAzD1B;MA4DY,aAAa;MACb,sBAAsB;MACtB,SAAS,EAAA;IA9DrB;MAiEY,8BAA8B,EAAA;EAjE1C;IAqEQ,yCAAyC,EAAA;IArEjD;MAuEY,0DAA0D,EAAA;IAvEtE;MA0EY,0DAA0D,EAAA;EA1EtE;IA+EQ,WAAW;IACX,oCAAoC;IACpC,sBAAsB;IACtB,gDAAgD;IAChD,+EAA+E;IAC/E,wCAAwC;IACxC,0EAA0E,EAAA;IArFlF;MAwFY,cAAc,EAAA;EAxF1B;IA6FY,mDAAmD,EAAA;EA7F/D;IAgGY,wCAAwC,EAAA;EAhGpD;IAmGY,mCAAmC,EAAA;IAnG/C;MAqGgB,oDAAoD,EAAA;IArGpE;MAwGgB,oDAAoD,EAAA;EAxGpE;;;IAgHY,cAAc;IACd,sBAAsB;IACtB,0BAA0B;IAC1B,kBAAkB;IAClB,sBAAsB,EAAA;IApHlC;;;MAsHgB,2BAA2B;MAC3B,wBAAwB,EAAA;IAvHxC;;;MA2HoB,oDAAoD,EAAA;EA3HxE;IAkIQ,wCAAwC;IACxC,qBAAqB,EAAA;EAnI7B;IAsIQ,8CAA8C;IAC9C,qFAAqF;IACrF,sDAAsD;IACtD,eAAe;IACf,eAAe,EAAA;IA1IvB;MA4IY,oDAAoD;MACpD,kEAAkE;MAClE,4DAA4D,EAAA;IA9IxE;MAiJY,WAAW,EAAA;EAjJvB;;IAsJQ,yCAAyC;IACzC,2FAA2F;IAC3F,iDAAiD;IACjD,eAAe;IACf,eAAe,EAAA;IA1JvB;;MA4JY,+CAA+C;MAC/C,6DAA6D;MAC7D,uDAAuD,EAAA;EA9JnE;;IAmKQ,WAAW;IACX,uCAAuC;IACvC,2BAA2B;IAE3B,qBAAqB;IACrB,mDAAmD;IACnD,kDAAkD;IAClD,kBAAkB;IAClB,gBAAgB;IAChB,sBAAsB;IDpO1B,sDAA2B;IAC3B,0CCoO+F;IDnO/F,kBAAkB;IAEd,6BAAmC;IAGnC,gEAA0C,EAAA;;ACkOlD,6CAAA;AACA;EACI,0CAA0C;EAC1C,cAAc;EACd,wDAAwD,EAAA;;AAE5D,2CAAA;AACA;EACI,kBAAkB,EAAA;;AAEtB;EACI,6CAA6C;EDpP7C,qDAA2B;EAC3B,yCCoPyF;EDnPzF,kBAAkB;EAEd,6BAAmC;EAGnC,+DAA0C,EAAA;;ACgPlD;EAEQ,mDAAmD,EAAA;;AAF3D;EAKQ,mCAAmC,EAAA;;AAG3C,2CAAA;AACA;EACI,kBAAkB;EAClB,eAAe;EACf,cAAc;EACd,eAAe,EAAA;;AAEnB;EACI,qCAAqC,EAAA;;AAEzC,gDAAA;AACA;;EAEI,kBAAkB,EAAA;EAFtB;;;;IAKQ,mBAAmB,EAAA;;AAG3B,sGAAA;AAEA,4FAAA;AACA;EAEQ,UAAU;EACV,gBAAgB;EAChB,kBAAkB;EAClB,UAAU,EAAA;EALlB;IAOY,kBAAkB,EAAA;;AAP9B;EAWQ,6BAA6B;EAC7B,qCAAqC;EDzSzC,wBAAwB;EACxB,2BCySoC;EDxSpC,mBCwS4C;EACxC,QAAQ,EAAA;EAdhB;IAiBY,kCAAkC;IAClC,kBAAkB;IAClB,6CAA6C;IAC7C,gBAAgB;IDjTxB,wBAAwB;IACxB,uBCiToC;IDhTpC,mBCgT4C;IACpC,QAAQ,EAAA;IAtBpB;MAwBgB,mCAAoC,EAAA;MAxBpD;QA0BoB,8CAA+C,EAAA;;AAOnE;EAEQ,kBAAkB;EAClB,gCAAgC;EAChC,QAAQ;EACR,gBAAgB,EAAA;;AALxB;EAQQ,6BAA6B;EAC7B,qCAAqC;EACrC,gBAAgB;EAChB,qCAAqC;EACrC,8BAA8B,EAAA;EAZtC;IAcY,kBAAkB,EAAA;;AAI9B,0FAAA;AAEA,uFAAA;AACA;EAEQ,sBAAsB,EAAA;;AAF9B;EAMY,6BAA6B,EAAA;;AANzC;EASY,oCAAoC;EACpC,uBAAuB;EACvB,sBAAsB;EACtB,2BAA2B,EAAA;EAZvC;IAcgB,wBAAwB,EAAA;EAdxC;IAiBgB,0CAA0C,EAAA;EAjB1D;IAoBgB,4BAA4B,EAAA;;AAK5C,qFAAA;AAEA,0FAAA;AACA;EAEQ,uBAAuB;EACvB,sBAAsB;EACtB,eAAe;EACf,kBAAkB;EAClB,gBAAgB;EAChB,4BAA4B;EDtXhC,wBAAwB;EACxB,uBCsXgC;EDrXhC,mBCqXwC;EACpC,0BAA0B,EAAA;EATlC;IAWY,4BAA4B;IAC5B,sBAAsB;IACtB,uBAAuB;IACvB,eAAe;IACf,kBAAkB;IAClB,sBAAsB;IACtB,gDAA+C;IDhYvD,wBAAwB;IACxB,uBCgYoC;ID/XpC,mBC+X4C,EAAA;;AAIhD;EACI,yCAAyC,EAAA;EAD7C;IAGQ,gDAAgD,EAAA;IAHxD;MAKY,mCAAmC;MACnC,sDAAsD,EAAA;EANlE;IAWY,iDAAiD,EAAA;EAX7D;IAcY,8CAA8C,EAAA;;AAK1D;EACI,mCAAmC,EAAA;EADvC;IAGQ,6BAA6B,EAAA;EAHrC;IAMQ,0CAA0C;IAC1C,oCAAoC,EAAA;IAP5C;MASY,6BAA6B,EAAA;;AAIzC,wFAAA;AAEA,mGAAA;AACA;EDxaI,wBAAwB;EACxB,2BCyaoC;EDxapC,mBCwa4C,EAAA;;AAGhD;ED7aI,wBAAwB;EACxB,2BC8aoC;ED7apC,uBC6agD,EAAA;;AAGpD;EAEQ,QAAQ,EAAA;EAFhB;IAIY,WAAW;IACX,eAAe,EAAA;IAL3B;MAOgB,aAAa;MACb,gBAAgB,EAAA;;AARhC;EAaQ,0BAAyB,EAAA;;AAbjC;EAgBQ,aAAa;EACb,aAAa;EACb,gBAAgB;EAChB,eAAe,EAAA;EAnBvB;IAqBY,6BAA6B;IAE7B,sBAAsB,EAAA;;AAIlC,iGAAA;AAEA,mGAAA;AACA;;EAGQ,gBAAgB,EAAA;;AAMxB;;;;;;;;;;;;;;;EAhcI,mCAAmC;EACnC,sDAAsD;EACtD,iCAAiC;EACjC,6CAA6C;EAC7C,yCAAyC;EACzC,+EAA+E;EDtB/E,gDAA2B;EAC3B,oCCsB+E;EDrB/E,kBAAkB;EAEd,6BAAmC,EAAA;EC6c3C;;;;;;;;;;;;;;;IAlbI,oDAAoD;IACpD,4DAA4D;IAC5D,kEAAkE,EAAA;;AAgbtE;;;;;;;;;;;;;;;EA5cI,sDAAwB;EACxB,8DAAwC;EACxC,oEAAqC,EAAA;;AA0czC;;;;;;;;;;;;;;;EAvcI,6BAA6B;EAC7B,gDAAgD;EAChD,mCAAmC,EAAA;EAqcvC;;;;;;;;;;;;;;;IAvbI,8CAA8C;IAC9C,sDAAsD;IACtD,4DAA4D,EAAA;;AAqbhE;;;;;;;;;;;;;;;;;;;;EA5cI,gDAAwB;EACxB,wDAAwC;EACxC,8DAAqC,EAAA;;AAufzC,iGAAA;AAEA,6FAAA;AACA;EDlfI,6BAA2B;EAQvB,qBAAwB;EAGxB,uBAA0B,EAAA;;ACuelC;;;EAvfI,6BAA6B;EAC7B,gDAAgD;EAChD,mCAAmC,EAAA;EAqfvC;;;IAWoB,sDAAsD,EAAA;;AAX1E;EAegB,qCAAqC,EAAA;;AAfrD;;;EAhfI,mCAAmC;EACnC,sDAAsD;EACtD,iCAAiC;EACjC,6CAA6C;EAC7C,yCAAyC;EACzC,+EAA+E;EDtB/E,gDAA2B;EAC3B,oCCsB+E;EDrB/E,kBAAkB;EAEd,6BAAmC,EAAA;EC6f3C;;;IA0BoB,4DAA4D,EAAA;;AA1BhF;EA8BgB,2CAA2C,EAAA;;AA9B3D;EAmCQ,aAAa;EACb,uBAAuB;EACvB,kBAAkB,EAAA;EArC1B;IAuCY,gBAAgB;IAChB,kBAAkB;IAClB,gCAAgC;IDljBxC,wBAAwB;IACxB,uBCkjBoC;IDjjBpC,mBCijB4C;IACpC,QAAQ,EAAA;IA3CpB;MA6CgB,6BAA6B;MAC7B,gBAAgB;MAChB,qCAAqC;MACrC,8BAA+B,EAAA;;AAK/C,2FAAA;AAEA,8FAAA;AACA;;;;EAjhBI,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B;EAN5B,yCAAyC;EACzC,0CAA0C,EAAA;EAohB9C;;;;IAjhBI,0BAA0B;IAC1B,2BAA2B;IAC3B,4BAA4B;IAN5B,yCAAyC;IACzC,0CAA0C,EAAA;;AAmiB9C,4FAAA;AAEA,iGAAA;AACA;EAniBI,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B,EAAA;;AAsiBhC,+FAAA;AAEA,6FAAA;AACA;EAEQ,8BAA8B;EAC9B,+BAA+B;EA9iBnC,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B,EAAA;;AAyiBhC;EAOQ,yCAAyC;EACzC,0CAA0C;EAC1C,0DAA0D;EAC1D,6DAA6D;EArjBjE,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B,EAAA;;AAujBhC,2FAAA;AAEA,iGAAA;AACA;EAEQ,qCAAqC;EACrC,cAAc,EAAA;;AAHtB;EAOQ,kCAAkC;EAClC,uBAAuB;EACvB,sBAAsB;EACtB,6BAA6B;EAC7B,iCAAiC;EACjC,kCAAkC,EAAA;EAZ1C;IAcY,YAAY;IACZ,WAAW,EAAA;EAfvB;IAkBY,aAAa;IACb,QAAQ,EAAA;IAnBpB;MAqBgB,sBAAsB;MACtB,uBAAuB,EAAA;;AAKvC;EAEQ,wBAAwB;EACxB,mBAAmB;EACnB,SAAS,EAAA;;AAJjB;EArnBI,6BAA6B;EAC7B,gDAAgD;EAChD,mCAAmC,EAAA;EAmnBvC;IAUgB,mBAAmB;IA/mB/B,8CAA8C;IAC9C,sDAAsD;IACtD,4DAA4D,EAAA;;AAmmBhE;;;;;EAkBgB,6BAA6B,EAAA;;AAlB7C;EA9mBI,mCAAmC;EACnC,sDAAsD;EACtD,iCAAiC;EACjC,6CAA6C;EAC7C,yCAAyC;EACzC,+EAA+E;EDtB/E,gDAA2B;EAC3B,oCCsB+E;EDrB/E,kBAAkB;EAEd,6BAAmC,EAAA;EC2nB3C;IA0BgB,mBAAmB;IA1nB/B,oDAAoD;IACpD,4DAA4D;IAC5D,kEAAkE,EAAA;;AA8lBtE;;;;;EAkCgB,mCAAmC,EAAA;;AAlCnD;EAwCY,kBAAkB;EAClB,mBAAmB;EACnB,eAAe;EDjrBvB,wBAAwB;EACxB,uBCirBoC;EDhrBpC,uBCgrBgD;EACxC,8BAA8B,EAAA;EA5C1C;;;IA+CgB,uBAAuB;IACvB,gBAAgB;IAChB,6BAA6B;IAC7B,uBAAuB;IACvB,sBAAsB;IACtB,kCAAkC,EAAA;EApDlD;IAuDgB,uBAAuB;IACvB,gBAAgB;IAChB,6BAA6B;IAC7B,uBAAuB;IACvB,sBAAsB,EAAA;;AA3DtC;EA1nBI,sDAAwB;EACxB,8DAAwC;EACxC,oEAAqC,EAAA;;AAwnBzC;EA1nBI,gDAAwB;EACxB,wDAAwC;EACxC,8DAAqC,EAAA;;AAwnBzC;EA+EQ,uBAAuB;EACvB,WAAW;EACX,YAAY,EAAA;;AAGpB,+FAAA;AAEA,uFAAA;AACA;EACI,0DAA0D;EAC1D,6DAA6D,EAAA;;AAEjE,2FAAA;AAEA,6FAAA;AACA;EAEQ,2BAA2B,EAAA;;AAFnC;;EDruBI,wBAAwB;EACxB,uBC0uBgC;EDzuBhC,uBCyuB4C;EACxC,8BAA8B,EAAA;;AAPtC;EDruBI,wBAAwB;EACxB,2BC+uBoC;ED9uBpC,mBC8uB4C,EAAA;EAXhD;;IAcY,8BAA8B;IAC9B,sBAAsB,EAAA;EAflC;;;;IAqBY,wBAAwB,EAAA;EArBpC;IAwBY,wBAAwB,EAAA;EAxBpC;IA2BY,kBAAkB;IAClB,sCAAsC,EAAA;IA5BlD;MA8BgB,wBAAwB,EAAA;;AAKxC,2FAAA;AAEA,6FAAA;AACA;EAEQ,wBAAwB;EACxB,uBAAuB;EACvB,kBAAkB,EAAA;EAJ1B;;;IAzvBI,6BAA6B;IAC7B,gDAAgD;IAChD,mCAAmC,EAAA;IAuvBvC;;;;;MAUgB,gCAAgC;MAChC,iBAAiB;MACjB,kBAAkB;MAClB,mBAAmB,EAAA;IAbnC;;;MAgBgB,2CAA2C;MAC3C,4BAA4B;MAC5B,6BAA6B;MAC7B,mBAAmB,EAAA;IAnBnC;;;MAsBgB,yCAAyC;MACzC,2BAA2B;MAC3B,8BAA8B;MAC9B,qCAAqC,EAAA;IAzBrD;;;MA4BgB,yCAAyC;MACzC,2BAA2B;MAC3B,mBAAmB;MACnB,mBAAmB;MACnB,sDAAsD,EAAA;EAhCtE;IAoCY,iCAAiC;IACjC,uBAAuB;IACvB,oDAAoD;IACpD,eAAgB;ID3xBxB,6BAA2B;IAEvB,mBAAoB;IAGpB,mBAAsB,EAAA;IC+uB9B;MA0CgB,kBAAkB;MAClB,8CAA8C;MAC9C,qBAAqB;MACrB,6BAA6B;MAC7B,0CAA0C;MAC1C,2BAA2B;MAC3B,8BAA8B;MAC9B,4BAA4B,EAAA;;AAjD5C;;;EA2DoB,4DAA4D,EAAA;;AA3DhF;;;EA8DoB,4DAA4D,EAAA;;AAMhF,2FAAA;AAEA,6FAAA;AACA;;;EAKY,uBAAuB;EACvB,sBAAsB;EACtB,wCAAwC;EACxC,YAAY,EAAA;;AARxB;EAYY,YAAY;EACZ,cAAc;EACd,4BAA4B;EAC5B,0BAA0B;EAC1B,sBAAsB;EAh1B9B,6BAA6B;EAC7B,gDAAgD;EAChD,mCAAmC;EA4BnC,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B;EAN5B,yCAAyC;EACzC,0CAA0C,EAAA;;AAqyB9C;;;EA4BgB,mCAAmC,EAAA;EA5BnD;;;IA8BoB,oDAAoD,EAAA;;AA9BxE;EAkCgB,sDAAsD,EAAA;EAlCtE;IAoCoB,2DAA2D;IAC3D,4DAA4D,EAAA;;AArChF;EAr0BI,sDAAwB;EACxB,8DAAwC;EACxC,oEAAqC,EAAA;;AAm0BzC;;;EAkDgB,6BAA6B,EAAA;EAlD7C;;;IAoDoB,8CAA8C,EAAA;;AApDlE;EAwDgB,gDAAgD,EAAA;EAxDhE;IA0DoB,4DAA4D;IAC5D,sDAAsD,EAAA;;AA3D1E;EAr0BI,gDAAwB;EACxB,wDAAwC;EACxC,8DAAqC,EAAA;;AAm0BzC;EAoEQ,uBAAuB;EACvB,WAAW;EACX,YAAY,EAAA;;AAtEpB;EAyEQ,sBAAsB;EACtB,kCAAkC;EA52BtC,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B;EAN5B,yCAAyC;EACzC,0CAA0C,EAAA;;AAqyB9C;EDl1BI,wBAAwB;EACxB,2BCg6BoC;ED/5BpC,mBC+5B4C;EACxC,SAAS,EAAA;;AAhFjB;EAmFQ,oFAAoF;EAr3BxF,0BAA0B;EAC1B,2BAA2B;EAC3B,4BAA4B,EAAA;;AAu3BhC,2FAAA;AAEA,6FAAA;AACA;EAEQ,8BAA8B;EAC9B,8BAA8B,EAAA;;AA4BtC;EAxBI,sBAAsB;EACtB,0BAA0B,EAAA;;AA2B9B;EAvBQ,oBAAoB,EAAA;;AA0B5B;EAvBQ,aAAa;EACb,sBAAsB;EACtB,SAAS,EAAA;;AA0BjB;EAvBQ,gBAAgB,EAAA;;AA0BxB;EAtBI,2BAA2B,EAAA;;AAE/B;EACI,sBAAsB;EDv8BtB,wBAAwB;EACxB,2BCu8BgC;EDt8BhC,mBCs8BwC,EAAA;;AAE5C;EACI;IACI,sBAAsB;IACtB,mBAAmB,EAAA,EACtB;;AAEL;EACI;IACI,sBAAsB;IACtB,mBAAmB,EAAA,EACtB;;AAEL;EAt5BA;IAw5BQ,cAAc,EAAA,EACjB;;AAGL;EACI,aAAa;EACb,8BAA8B;EAC9B,SAAS,EAAA;EAHb;IAKQ,OAAO,EAAA;;AAGf;EACI,0CAAwC;EACxC,SAAS;EACT,wBAAwB;EDh9BxB,6BAA2B;EAEvB,iBAAoB;EAGpB,kBAAsB;EAGtB,mBAAwB;EC08B5B,gBAAgB;EAChB,YAAY;EACZ,aAAa;EACb,MAAM;EACN,0BAA0B,EAAA;;AAE9B;EACI,WAAW;EACX,kBAAkB;EAClB,iBAAiB;ED39BjB,6BAA2B;EAEvB,mBAAoB;EC29BxB,mBAAmB,EAAA;;AAEvB;EACI,eAAe;EACf,oBAAoB;EACpB,yBAAyB;EACzB,uCAAuC;EACvC,qBAAqB;EACrB,gEAAgE,EAAA;;AAEpE;EACI;IACI,wBAAwB;IACxB,oBAAoB,EAAA,EAAA;;AAG5B;EACI,YAAY;EACZ,WAAW;EACX,6CAA6C,EAAA;;AAEjD;EACI;IACI,wBAAwB,EAAA,EAAA;;AAIhC;;;;;;;EAUY,2DAA2D,EAAA;;AAVvE;;;;;;;EAaY,0DAA0D,EAAA;;AAbtE;;;;;;;EAmBY,qDAAqD,EAAA;;AAnBjE;;;;;;;EAsBY,oDAAoD,EAAA;;AAIhE;EAIY,2DAA2D,EAAA;;AAJvE;EAQY,0DAA0D,EAAA;;AARtE;EAcY,qDAAqD,EAAA;;AAdjE;EAiBY,oDAAoD,EAAA","sourcesContent":["/* =====================================\r\n    wsx Flex Style ( justify, alignment)\r\n=========================================*/\r\n@mixin WsxFlexStyle($justify: flex-start, $align: flex-start) {\r\n    display: flex !important;\r\n    justify-content: $justify;\r\n    align-items: $align;\r\n}\r\n/* =====================================\r\n    WSX Font Style size, weight, height\r\n=====================================*/\r\n@mixin WsxTypographyStyle($size: 12px, $weight: 400, $lineHeight: 1.6em, $transformation: false) {\r\n    font-size: $size !important;\r\n    font-weight: $weight;\r\n    font-style: normal;\r\n    @if $lineHeight {\r\n        line-height: $lineHeight !important;\r\n    }\r\n    @if $transformation {\r\n        text-transform: $transformation !important;\r\n    }\r\n}\r\n\r\n/* ===================================================  \r\n    wsx Positioning Position, Top, Left, Right,bottom \r\n===================================================*/\r\n@mixin WsxPositionStyle($style, $top: false, $left: false, $right: false, $bottom: false) {\r\n    position: $style !important;\r\n    @if $top {\r\n        top: $top !important;\r\n    }\r\n    @if $left {\r\n        left: $left !important;\r\n    }\r\n    @if $right {\r\n        right: $right !important;\r\n    }\r\n    @if $bottom {\r\n        bottom: $bottom !important;\r\n    }\r\n}","@import './CommonMixin.scss';\r\n/* =============== Common Class =============== */\r\n.wsx-input-typography {\r\n    @include WsxTypographyStyle(var(--wsx-input-font-size), var(--wsx-input-weight), 1.6em, var(--wsx-input-case-transform));\r\n}\r\n.wsx-formBuilder-input-width {\r\n    width: 100%;\r\n    max-width: var(--wsx-input-width);\r\n}\r\n.wsx-formBuilder-input-layout {\r\n    border-radius: var(--wsx-input-border-radius);\r\n    padding: var(--wsx-input-padding);\r\n    border: var(--wsx-input-border-width) solid var(--wsx-input-border-color);\r\n}\r\n\r\n/* =============== Common Class =============== */\r\n@mixin inputWarningStyle($color, $background, $borderColor) {\r\n    color: $color !important;\r\n    background-color: $background !important;\r\n    border-color: $borderColor !important;\r\n}\r\n@mixin inputStyle() {\r\n    color: var(--wsx-input-color);\r\n    background-color: var(--wsx-input-bg) !important;\r\n    caret-color: var(--wsx-input-color);\r\n    @extend .wsx-input-typography;\r\n    @extend .wsx-formBuilder-input-layout;\r\n}\r\n@mixin loginInputStyle() {\r\n    color: var(--wsx-login-input-color);\r\n    background-color: var(--wsx-login-input-bg) !important;\r\n    padding: var(--wsx-input-padding);\r\n    border-radius: var(--wsx-input-border-radius);\r\n    caret-color: var(--wsx-login-input-color);\r\n    border: var(--wsx-input-border-width) solid var(--wsx-login-input-border-color);\r\n    @include WsxTypographyStyle(var(--wsx-input-font-size), var(--wsx-input-weight)); // font-size: var(--wsx-input-font-size);font-weight: var(--wsx-input-weight);\r\n}\r\n@mixin inputFocusStyle() {\r\n    color: var(--wsx-input-focus-color) !important;\r\n    background-color: var(--wsx-input-focus-bg) !important;\r\n    border-color: var(--wsx-input-focus-border-color) !important;\r\n}\r\n@mixin loginInputFocusStyle() {\r\n    color: var(--wsx-login-input-focus-color) !important;\r\n    background-color: var(--wsx-login-input-focus-bg) !important;\r\n    border-color: var(--wsx-login-input-focus-border-color) !important;\r\n}\r\n@mixin BottomRadiusNone() {\r\n    border-bottom-left-radius: 0px !important;\r\n    border-bottom-right-radius: 0px !important;\r\n}\r\n@mixin BorderOutlineStyle1() {\r\n    border-top: 0px !important;\r\n    border-left: 0px !important;\r\n    border-right: 0px !important;\r\n}\r\n/* ======= Container Style  ======= */\r\n\r\n.wholesalex-form,.wsx-form-wrapper_frontend  {\r\n    width: 100% !important;\r\n    max-width: var(--wsx-form-container-width) !important;\r\n    .wholesalex_circular_loading__wrapper {\r\n        display: none;\r\n    }\r\n}\r\n/* ===============================  Variable Styling End ================================*/\r\n\r\n/* ===============================  Input Variation Common Style Start ================================*/\r\n.wholesalex-form-wrapper {\r\n    height: auto;\r\n    width: 100%;\r\n    box-sizing: border-box;\r\n    border: var(--wsx-form-container-border-width) solid var(--wsx-form-container-border-color);\r\n    border-radius: var(--wsx-form-container-border-radius);\r\n    background-color: var(--wsx-form-container-bg);\r\n    padding: var(--wsx-form-container-padding);\r\n    @include WsxFlexStyle(center, stretch);\r\n    input, select, textarea {\r\n        min-height: auto !important;\r\n        max-width: 100%;\r\n        box-shadow: none !important;\r\n        margin: 0px !important;\r\n    }\r\n    input, select {\r\n        height: auto !important;\r\n    }\r\n    .wsx-form-label {\r\n        margin-bottom: 0px;\r\n        & > span {\r\n            color: #FF2727 !important;\r\n            margin-left: 4px;\r\n        }\r\n    }\r\n    .wsx-clone-label span {\r\n        visibility: hidden;\r\n    }\r\n    // Heading Style start\r\n    .wsx-reg-form-heading-text {\r\n        color: var(--wsx-reg-title-color);\r\n        @include WsxTypographyStyle(var(--wsx-reg-title-font-size), var(--wsx-reg-title-font-size), 1.5em, var(--wsx-reg-title-case-transform)); // font-size: var(--wsx-reg-title-font-size); font-weight: var(--wsx-reg-title-font-weight);\r\n    }\r\n    .wsx-login-form-title-text {\r\n        color: var(--wsx-login-title-color);\r\n        @include WsxTypographyStyle(var(--wsx-login-title-font-size), var(--wsx-login-title-font-weight), 1.5em, var(--wsx-login-title-case-transform)); // font-size: var(--wsx-login-title-font-size);  // font-weight: var(--wsx-login-title-font-weight);\r\n    }\r\n    .wholesalex-registration-form-subtitle-text {\r\n        color: var(--wsx-reg-description-color);\r\n        @include WsxTypographyStyle(var(--wsx-reg-description-font-size), var(--wsx-reg-description-font-weight),1.5em, var(--wsx-reg-description-case-transform)); // font-size: var(--wsx-reg-description-font-size); // font-weight: var(--wsx-reg-description-font-weight);\r\n    }\r\n    .wholesalex-login-form-subtitle-text {\r\n        color: var(--wsx-login-description-color);\r\n        @include WsxTypographyStyle(var(--wsx-login-description-font-size), var(--wsx-login-description-font-weight),1.5, var(--wsx-login-description-case-transform)); // font-size: var(--wsx-login-description-font-size); // font-weight: var(--wsx-login-description-font-weight);\r\n    }\r\n    // Heading Style end\r\n    // Login form\r\n    .wholesalex-login-form {\r\n        width: 100%;\r\n        max-width: var(--wsx-login-width);\r\n        box-sizing: border-box;\r\n        border: var(--wsx-login-border-width) solid var(--wsx-login-border-color);\r\n        border-radius: var(--wsx-login-border-radius);\r\n        background-color: var(--wsx-login-bg);\r\n        padding: var(--wsx-login-padding) 16px var(--wsx-login-padding) 16px;\r\n        & > * {\r\n            @extend .wsx-formBuilder-input-width;\r\n            margin: 0 auto;\r\n        }\r\n        .wsx-fields-container {\r\n            display: flex;\r\n            flex-direction: column;\r\n            gap: 25px;\r\n        }\r\n        .wholesalex-login-form-title {\r\n            margin-bottom: 30px !important;\r\n        }\r\n    }\r\n    .wsx-login-fields input {\r\n        caret-color: var(--wsx-login-input-color);\r\n        &::placeholder { \r\n            color: var(--wsx-login-input-placeholder-color) !important;\r\n        }\r\n        &::-ms-input-placeholder { \r\n            color: var(--wsx-login-input-placeholder-color) !important;\r\n        }\r\n    }\r\n    // Registration form\r\n    .wholesalex-registration-form {\r\n        width: 100%;\r\n        max-width: var(--wsx-form-reg-width);\r\n        box-sizing: border-box;\r\n        border-radius: var(--wsx-form-reg-border-radius);\r\n        border: var(--wsx-form-reg-border-width) solid var(--wsx-form-reg-border-color);\r\n        background-color: var(--wsx-form-reg-bg);\r\n        padding: var(--wsx-form-reg-padding) 16px var(--wsx-form-reg-padding) 16px;\r\n        & > * {\r\n            @extend .wsx-formBuilder-input-width;\r\n            margin: 0 auto;\r\n        }\r\n    }\r\n    .wsx-reg-fields {\r\n        .wsx-form-field > select option {\r\n            background-color: var(--wsx-form-reg-bg) !important;\r\n        }\r\n        .wsx-form-field > select option, .wsx-privacy-policy {\r\n            color: var(--wsx-input-color) !important;\r\n        }\r\n        .wsx-form-field > input, .wsx-form-field > textarea {\r\n            caret-color: var(--wsx-input-color);\r\n            &::placeholder{ \r\n                color: var(--wsx-input-placeholder-color) !important;\r\n            }\r\n            &::-ms-input-placeholder { \r\n                color: var(--wsx-input-placeholder-color) !important;\r\n            }\r\n        }\r\n    }\r\n    .wsx-form-field {\r\n        & > textarea,\r\n        & > select,\r\n        & > input {\r\n            display: block;\r\n            width: 100% !important;\r\n            min-width: auto !important;\r\n            line-height: 1.6em;\r\n            box-sizing: border-box;\r\n            &:focus {\r\n                box-shadow: none !important;\r\n                outline: none !important;\r\n            }\r\n            &[type='date'] {\r\n                &::-webkit-calendar-picker-indicator {\r\n                    background-color: var(--wsx-input-placeholder-color);\r\n                }\r\n            }\r\n        }\r\n    }\r\n    // Button\r\n    .wsx-form-btn-wrapper {\r\n        text-align: var(--wsx-form-button-align);\r\n        margin-bottom: 1.5rem;\r\n    }\r\n    button.wsx-register-btn {\r\n        color: var(--wsx-form-button-color) !important;\r\n        border: var(--wsx-form-button-border-width) solid var(--wsx-form-button-border-color);\r\n        background-color: var(--wsx-form-button-bg) !important;\r\n        transition: .3s;\r\n        cursor: pointer;\r\n        &:hover {\r\n            color: var(--wsx-form-button-hover-color) !important;\r\n            border-color: var(--wsx-form-button-hover-border-color) !important;\r\n            background-color: var(--wsx-form-button-hover-bg) !important;\r\n        }\r\n        &:disabled{\r\n            opacity: .5;\r\n        }\r\n    }\r\n    .wholesalex-login-form .wsx-form-btn-wrapper >  \r\n    button.wsx-login-btn {\r\n        color: var(--wsx-login-form-button-color);\r\n        border: var(--wsx-form-button-border-width) solid var(--wsx-login-form-button-border-color);\r\n        background-color: var(--wsx-login-form-button-bg);\r\n        transition: .3s;\r\n        cursor: pointer;\r\n        &:hover {\r\n            color: var(--wsx-login-form-button-hover-color);\r\n            border-color: var(--wsx-login-form-button-hover-border-color);\r\n            background-color: var(--wsx-login-form-button-hover-bg);\r\n        }\r\n    }\r\n    button.wsx-register-btn, \r\n    button.wsx-login-btn {\r\n        width: 100%;\r\n        max-width: var(--wsx-form-button-width);\r\n        min-height: auto !important;\r\n        // font style\r\n        display: inline-block;\r\n        border-radius: var(--wsx-form-button-border-radius);\r\n        padding: var(--wsx-form-button-padding) !important;\r\n        text-align: center;\r\n        margin-top: 36px;\r\n        box-sizing: border-box;\r\n        @include WsxTypographyStyle(var(--wsx-form-button-font-size), var(--wsx-form-button-weight), 1.6em, var(--wsx-form-button-case-transform)); // font-size: var(--wsx-form-button-font-size) !important; // font-weight: var(--wsx-form-button-weight);\r\n    }\r\n}\r\n\r\n/* ============= Form Separator =========== */\r\n.wsx-form-separator {\r\n    width: var(--wsx-form-container-separator);\r\n    display: block;\r\n    background-color: var(--wsx-form-container-border-color);\r\n}\r\n/* ============= Input Label  =========== */\r\n.wsx-field-heading {\r\n    margin-bottom: 8px;\r\n}\r\nlabel.wsx-form-label, .wsx-form-label, .wsx-form-field-label {\r\n    color: var(--wsx-form-label-color) !important;\r\n    @include WsxTypographyStyle(var(--wsx-form-label-font-size), var(--wsx-form-label-weight), 1.6em, var(--wsx-form-label-case-transform)); // font-weight: var(--wsx-form-label-weight) !important; // font-size: var(--wsx-form-label-font-size) !important;\r\n}\r\n.wsx-login-fields {\r\n    label.wsx-form-label, .wsx-form-label, .wsx-form-field-label {\r\n        color: var(--wsx-login-form-label-color) !important;\r\n    }\r\n    .wsx-form-field-help-message {\r\n        color: var(--wsx-login-input-color);\r\n    }\r\n}\r\n/* ============= Help Message =========== */\r\n.wsx-form-field-help-message,.wsx-form-field-warning-message {\r\n    font-style: italic;\r\n    font-size: 12px;\r\n    display: block;\r\n    margin-top: 4px;\r\n}\r\n.wsx-form-field-warning-message {\r\n    color: var(--wsx-input-warning-color);\r\n}\r\n/* ======= Heading & Settings Dropdown ======= */\r\n.wsx-reg-form-heading,\r\n.wholesalex-login-form-title {\r\n    position: relative;\r\n    .wsx-reg-form-heading-text, \r\n    .wsx-login-form-title-text {\r\n        margin-bottom: 10px;\r\n    }\r\n}\r\n/* ===============================  Input Variation Common Style End ================================*/\r\n\r\n/* ===============================  File Input Style Start ================================*/\r\n.wsx-form-file .wsx-field-content {\r\n    input {\r\n        opacity: 0;\r\n        overflow: hidden;\r\n        position: absolute;\r\n        z-index: 0;\r\n        &::-webkit-file-upload-button {\r\n            visibility: hidden;\r\n        }\r\n    }\r\n    .wsx-file-label {\r\n        color: var(--wsx-input-color);\r\n        background-color: var(--wsx-input-bg);\r\n        @include WsxFlexStyle(flex-start, center);\r\n        gap: 8px;\r\n        @extend .wsx-formBuilder-input-layout;\r\n        span {\r\n            color: var(--wsx-form-label-color);\r\n            border-radius: 1px;\r\n            border: 1px solid var(--wsx-form-label-color);\r\n            padding: 2px 8px;\r\n            @include WsxFlexStyle(center, center);\r\n            gap: 6px;\r\n            svg {\r\n                stroke:  var(--wsx-form-label-color);\r\n                path {\r\n                    stroke:  var(--wsx-form-label-color) !important;\r\n                }\r\n            }\r\n        }\r\n        \r\n    }\r\n}\r\nbody .wholesalex-form-wrapper  .wsx-file-outline {\r\n    .wsx-form-label {\r\n        position: absolute;\r\n        transform: translate(16px, -50%);\r\n        gap: 5px;\r\n        padding: 0px 5px;\r\n    }\r\n    .wsx-clone-label {\r\n        color: transparent !important;\r\n        background-color: var(--wsx-input-bg);\r\n        padding: 0px 5px;\r\n        height: var(--wsx-input-border-width);\r\n        transform: translate(16px, 0%);\r\n        & > span {\r\n            visibility: hidden;\r\n        }\r\n    }\r\n}\r\n/* ===============================  File Input Style End ================================*/\r\n\r\n/* ===============================  Radio Style Start ================================*/\r\n.wsx-field-radio {\r\n    .wsx-field-content {\r\n        border: 0px !important;\r\n    }\r\n    .wholesalex-field-wrap {\r\n        & > div {\r\n            color: var(--wsx-input-color);\r\n        }\r\n        & > input {\r\n            accent-color: var(--wsx-input-color);\r\n            height: 17px !important;\r\n            width: 17px !important;\r\n            appearance: auto !important;\r\n            &:checked::before {\r\n                content: none !important;\r\n            }\r\n            &:hover {\r\n                accent-color: var(--wsx-input-focus-color);\r\n            }\r\n            &:focus {\r\n                box-shadow: unset !important;\r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Radio Style End ================================*/\r\n\r\n/* ===============================  Checkbox Style Start ================================*/\r\n.wsx-form-checkbox .wholesalex-field-wrap {\r\n    & > input {\r\n        height: 16px !important;\r\n        width: 16px !important;\r\n        min-width: 16px;\r\n        border-radius: 2px;\r\n        overflow: hidden;\r\n        appearance: unset !important;\r\n        @include WsxFlexStyle(center, center );\r\n        margin-top: 6px !important;\r\n        &:checked::before {\r\n            content: \" \\2714\" !important;\r\n            width: 16px !important;\r\n            height: 16px !important;\r\n            font-size: 11px;\r\n            border-radius: 2px;\r\n            margin: 0px !important;\r\n            background-color: var(--wsx-input-bg)!important; // avada theme conflict\r\n            @include WsxFlexStyle(center, center);\r\n        }\r\n    }\r\n}\r\n.wsx-login-fields {\r\n    caret-color: var(--wsx-login-input-color);\r\n    .wsx-form-checkbox .wholesalex-field-wrap  > input {\r\n        border: 1.5px solid var(--wsx-login-input-color);\r\n        &:checked::before { \r\n            color: var(--wsx-login-input-color);\r\n            background-color: var(--wsx-login-input-bg) !important; // avada theme conflict\r\n        }\r\n    }\r\n    .wsx-form-checkbox {\r\n        .wsx-field-content {\r\n            border-color: var(--wsx-login-input-border-color);\r\n        }\r\n        label {\r\n            color: var(--wsx-login-input-color) !important;\r\n        }\r\n    }\r\n\r\n}\r\n.wsx-reg-fields {\r\n    caret-color: var(--wsx-input-color);\r\n    .wsx-form-field-help-message {\r\n        color: var(--wsx-input-color);\r\n    }\r\n    .wsx-form-checkbox .wholesalex-field-wrap > input {\r\n        border: 1.5px solid var(--wsx-input-color);\r\n        accent-color: transparent !important;\r\n        &:checked::before { \r\n            color: var(--wsx-input-color);\r\n        }\r\n    }\r\n}\r\n/* ===============================  Checkbox Style End ================================*/\r\n\r\n/* ===============================  Checkbox & Radio Common Start ================================*/\r\n.wsx-form-field.wsx-field-radio {\r\n    .wholesalex-field-wrap {\r\n        @include WsxFlexStyle(flex-start, center);\r\n    }\r\n}\r\n.wsx-form-field.wsx-form-checkbox {\r\n    .wholesalex-field-wrap {\r\n        @include WsxFlexStyle(flex-start, flex-start);\r\n    }\r\n}\r\n.wsx-form-field.wsx-form-checkbox, .wsx-form-field.wsx-field-radio {\r\n    .wholesalex-field-wrap {\r\n        gap: 8px;\r\n        input {\r\n            margin: 0px;\r\n            cursor: pointer;\r\n            &:focus {\r\n                outline: none;\r\n                box-shadow: none;\r\n            }\r\n        }\r\n    }\r\n    .wsx-form-field-help-message {\r\n        margin-top:8px !important;\r\n    }\r\n    .wsx-field-content {\r\n        display: flex;\r\n        row-gap: 20px;\r\n        column-gap: 32px;\r\n        flex-wrap: wrap;\r\n        label {\r\n            color: var(--wsx-input-color);\r\n            @extend .wsx-input-typography;\r\n            margin: 0px !important;\r\n        }\r\n    }\r\n}\r\n/* ===============================  Checkbox & Radio Common End ================================*/\r\n\r\n/* ===============================  Common Variation Style  Start ================================*/\r\n.wholesalex-registration-form .wsx_variation_1,\r\n.wholesalex-registration-form .wsx_variation_3 {\r\n    .wsx-reg-form-row-setting {\r\n        position: static;\r\n    }\r\n}\r\n.wsx_variation_1 .wsx-outline-focus {\r\n    @extend .wsx-formBuilder-input-width;\r\n}\r\n.wsx_variation_1,\r\n.wsx_variation_2,\r\n.wsx_variation_3, \r\n.wsx_variation_6, \r\n.wsx_variation_7 {\r\n    .wsx-login-fields  {\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                @include loginInputStyle();\r\n                &:focus {\r\n                    @include loginInputFocusStyle();\r\n                }\r\n            }\r\n            &.wsx-field-warning {\r\n                & > input,\r\n                & > textarea,\r\n                & > select {\r\n                    @include inputWarningStyle(var(--wsx-login-input-warning-color), var(--wsx-login-input-warning-bg), var(--wsx-login-input-warning-border-color));\r\n                }\r\n            }\r\n        }\r\n    }\r\n    .wsx-reg-fields  {\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                @include inputStyle();\r\n                &:focus {\r\n                    @include inputFocusStyle();\r\n                }\r\n            }\r\n            &.wsx-field-warning {\r\n                & > input,\r\n                & > textarea,\r\n                & > select,\r\n                & > .wsx-field-content > .wsx-file-label {\r\n                    @include inputWarningStyle(var(--wsx-input-warning-color), var(--wsx-input-warning-bg), var(--wsx-input-warning-border-color));\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Common Variation Style  End ================================*/\r\n\r\n/* ===============================  Variation Style 2 Start ================================*/\r\n.wsx_variation_2 {\r\n    & > .wsx-reg-form-row-setting {\r\n        @include WsxPositionStyle(absolute, false, false, 0px, 100%);\r\n    }\r\n    .wsx-reg-fields {\r\n        .wsx-outline-focus {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                @include inputStyle();\r\n                &:focus + .wsx-form-label {\r\n                    background-color: var(--wsx-input-focus-bg) !important;\r\n                }\r\n            }\r\n            .wsx-form-label.wsx-clone-label {\r\n                background-color: var(--wsx-input-bg);\r\n            }\r\n        }\r\n    }\r\n    .wsx-login-fields {\r\n        .wsx-outline-focus {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                @include loginInputStyle();\r\n                &:focus + .wsx-form-label {\r\n                    background-color: var(--wsx-login-input-focus-bg) !important;\r\n                }\r\n            }\r\n            .wsx-form-label.wsx-clone-label {\r\n                background-color: var(--wsx-login-input-bg);\r\n            }\r\n        }\r\n    }\r\n    .wsx-outline-focus {\r\n        display: flex;\r\n        background: transparent;\r\n        position: relative;\r\n        .wsx-form-label {\r\n            padding: 0px 5px;\r\n            position: absolute;\r\n            transform: translate(16px, -50%);\r\n            @include WsxFlexStyle(center, center);\r\n            gap: 5px;\r\n            &.wsx-clone-label {\r\n                color: transparent !important;\r\n                padding: 0px 5px;\r\n                height: var(--wsx-input-border-width);\r\n                transform: translate(16px, -0%);\r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Variation Style 2 End ================================*/\r\n\r\n/* ===============================  Variation Style 3  Start ================================*/\r\n.wsx_variation_3 {\r\n    .wsx-form-field {\r\n        & > textarea,\r\n        & > select,\r\n        .wsx-file-label,\r\n        & > input {\r\n            @include BorderOutlineStyle1();\r\n            @include BottomRadiusNone();\r\n            &:focus {\r\n                @include BorderOutlineStyle1();\r\n                @include BottomRadiusNone();\r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Variation Style 3  End ================================*/\r\n\r\n/* ===============================  Variation Style 3 & 4 Start ================================*/\r\n.wsx_variation_3, .wsx_variation_4 {\r\n    .wsx-form-file  .wsx-file-label {\r\n        @include BorderOutlineStyle1();\r\n    }\r\n}\r\n/* ===============================  Variation Style 3 & 4 End ================================*/\r\n\r\n/* ===============================  Variation Style 4 Start ================================*/\r\n.wsx_variation_4 {\r\n    .wsx-form-file .wsx-field-content {\r\n        border-bottom-left-radius: 0px;\r\n        border-bottom-right-radius: 0px;\r\n        @include BorderOutlineStyle1();\r\n    }\r\n    .wsx-outline-focus  { \r\n        border-bottom-left-radius: 0px !important;\r\n        border-bottom-right-radius: 0px !important;\r\n        padding-top: calc(var(--wsx-input-padding) / 2) !important;\r\n        padding-bottom: calc(var(--wsx-input-padding) / 2) !important;\r\n        @include BorderOutlineStyle1();\r\n    }\r\n}\r\n/* ===============================  Variation Style 4 End ================================*/\r\n\r\n/* ===============================  Variation Style 4 & 5 Start ================================*/\r\n.wsx_variation_4, .wsx_variation_5, .wsx_variation_8 {\r\n    .wsx-field-content {\r\n        background-color: var(--wsx-input-bg);\r\n        display: block;\r\n        @extend .wsx-formBuilder-input-layout;\r\n    }\r\n    .wsx-file-label {\r\n        background-color: unset !important;\r\n        padding: 0px !important;\r\n        border: 0px !important;\r\n        border-radius: 0px !important;\r\n        flex-direction: column !important;\r\n        align-items: flex-start !important;\r\n        span {\r\n            padding: 0px;\r\n            border: 0px;\r\n        }\r\n        .wsx-form-label {\r\n            display: flex;\r\n            gap: 5px;\r\n            span {\r\n                border: 0px !important;\r\n                padding: 0px !important;\r\n            }\r\n        }\r\n    }\r\n}\r\n.wsx_variation_4, .wsx_variation_5 {\r\n    .wsx-file-label_wrap {\r\n        display: flex !important;\r\n        align-items: center;\r\n        gap: 20px;\r\n    }\r\n    .wsx-reg-fields {\r\n        .wsx-form-field.wsx-outline-focus {\r\n            @include inputStyle();\r\n            &:focus-within {\r\n                border-style: solid;\r\n                @include inputFocusStyle();\r\n            }\r\n        }\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > select,\r\n            & > textarea {\r\n                color: var(--wsx-input-color);\r\n            }\r\n        }\r\n    }\r\n    .wsx-login-fields {\r\n        .wsx-form-field.wsx-outline-focus {\r\n            @include loginInputStyle();\r\n            &:focus-within {\r\n                border-style: solid;\r\n                @include loginInputFocusStyle();\r\n            }\r\n        }\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > select,\r\n            & > textarea {\r\n                color: var(--wsx-login-input-color);\r\n            }\r\n        }\r\n    }\r\n    .wsx-form-field {\r\n        &.wsx-outline-focus {\r\n            position: relative;\r\n            border-style: solid;\r\n            margin-top: 4px;\r\n            @include WsxFlexStyle(center, flex-start);\r\n            flex-direction: column-reverse;\r\n            & > select,\r\n            & > input {\r\n                border: none !important;\r\n                box-shadow: none;\r\n                background-color: transparent;\r\n                padding: 0px !important;\r\n                margin: 0px !important;\r\n                min-height: fit-content !important;\r\n            }\r\n            & > textarea {\r\n                border: none !important;\r\n                box-shadow: none;\r\n                background-color: transparent;\r\n                padding: 0px !important;\r\n                margin: 0px !important;\r\n            }\r\n        }\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > select,\r\n            & > textarea {\r\n                @extend .wsx-input-typography;\r\n            }\r\n        }\r\n    }\r\n    .wsx-login-fields .wsx-field-warning.wsx-form-field:not(.wsx-form-checkbox) {\r\n        @include inputWarningStyle(var(--wsx-login-input-warning-color), var(--wsx-login-input-warning-bg), var(--wsx-login-input-warning-border-color));\r\n    }\r\n    .wsx-reg-fields  {\r\n        .wsx-field-warning.wsx-form-field:not(.wsx-field-radio):not(.wsx-form-checkbox):not(.wsx-form-file), .wsx-form-file.wsx-field-warning .wsx-field-content {\r\n            @include inputWarningStyle(var(--wsx-input-warning-color), var(--wsx-input-warning-bg), var(--wsx-input-warning-border-color));\r\n        }\r\n    }\r\n    .wsx-field-radio .wsx-field-content, .wsx-form-checkbox .wsx-field-content {\r\n        background-color: unset;\r\n        border: 0px;\r\n        padding: 0px;\r\n    }\r\n}\r\n/* ===============================  Variation Style 4 & 5 End ================================*/\r\n\r\n/* ===============================  Variation Style 5 ================================*/\r\n.wsx_variation_5 .wsx-outline-focus {\r\n    padding-top: calc(var(--wsx-input-padding) / 2) !important;\r\n    padding-bottom: calc(var(--wsx-input-padding) / 2) !important;\r\n}\r\n/* ===============================  Variation Style 5 End ================================*/\r\n\r\n/* ===============================  Variation Style 6 Start ================================*/\r\n.wsx_variation_6 {\r\n    .wholesalex-registration-form-column:has( > .wsx-form-select) .wsx-field-heading{\r\n        position: static !important;\r\n    }\r\n    .wsx-form-date , \r\n    .wsx-form-select {\r\n        @include WsxFlexStyle(center, flex-start );\r\n        flex-direction: column-reverse;\r\n    }\r\n    .wsx-outline-focus:not(.wsx-form-select):not(.wsx-form-date) {\r\n        // margin-top: 4px; // double column issue\r\n        @include WsxFlexStyle(flex-start, center);\r\n        & > select,\r\n        & > input {\r\n            height: fit-content !important;\r\n            box-sizing: border-box;\r\n        }\r\n        & > textarea:focus ~ .wsx-form-label, \r\n        & > textarea:focus-within ~ .wsx-form-label, \r\n        & > input:focus ~ .wsx-form-label, \r\n        & > input:focus-within ~ .wsx-form-label {\r\n            display: none !important;\r\n        }\r\n        & > input:not([value]):not(:defined) {\r\n            display: none !important;\r\n        }\r\n        .wsx-form-label {\r\n            position: absolute;\r\n            padding-left: var(--wsx-input-padding);\r\n            &:focus, &:focus-within {\r\n                display: none !important;\r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Variation Style 6 End ================================*/\r\n\r\n/* ===============================  Variation Style 7 Start ================================*/\r\n.wsx_variation_7 {\r\n    .wsx-outline-focus {\r\n        display: flex !important;\r\n        background: transparent;\r\n        position: relative;\r\n        & > input,\r\n        & > textarea,\r\n        & > select {\r\n            @include inputStyle();\r\n            &:focus ~ .wsx-form-label, &:focus ~ .wsx-clone-label {\r\n                transform: translate(16px, -50%);\r\n                padding-left: 6px;\r\n                padding-right: 6px;\r\n                top: 0px !important;\r\n            }\r\n            &:not(:placeholder-shown) ~ .wsx-form-label {\r\n                transform: translate(16px, -50%) !important; \r\n                padding-left: 6px !important;\r\n                padding-right: 6px !important;\r\n                top: 0px !important;\r\n            }\r\n            &:not(:placeholder-shown) ~ .wsx-clone-label {\r\n                transform: translate(16px, 0%) !important;\r\n                padding-top: 0px !important;\r\n                padding-bottom: 0px !important;\r\n                background-color: var(--wsx-input-bg);\r\n            }\r\n            &:focus ~ .wsx-clone-label {\r\n                transform: translate(16px, 0%) !important;\r\n                padding-top: 0px !important;\r\n                padding-bottom: 0px;\r\n                top: 0px !important;\r\n                background-color: var(--wsx-input-focus-bg) !important;\r\n            }\r\n        }\r\n        .wsx-form-label {\r\n            padding: var(--wsx-input-padding);\r\n            z-index: 999 !important;\r\n            transform: translate(var(--wsx-input-padding), -50%);\r\n            transition:  .3s;\r\n            @include WsxPositionStyle(absolute, 50%, 0%);\r\n            &.wsx-clone-label {\r\n                position: absolute;\r\n                height: calc(var(--wsx-input-border-width)* 2);\r\n                z-index: 0 !important;\r\n                color: transparent !important;\r\n                transform: translate(0px, -50%) !important;\r\n                padding-top: 0px !important;\r\n                padding-bottom: 0px !important;\r\n                transition: unset !important;\r\n            }\r\n        }\r\n    }\r\n    .wsx-login-fields  {\r\n        .wsx-outline-focus {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                &:not(:placeholder-shown) ~ .wsx-clone-label {\r\n                    background-color: var(--wsx-login-input-focus-bg) !important;\r\n                }\r\n                &:focus ~ .wsx-clone-label {\r\n                    background-color: var(--wsx-login-input-focus-bg) !important;\r\n                }    \r\n            }\r\n        }\r\n    }\r\n}\r\n/* ===============================  Variation Style 7 End ================================*/\r\n\r\n/* ===============================  Variation Style 8 Start ================================*/\r\n.wsx_variation_8 {\r\n    .wsx-form-field {\r\n        & > input,\r\n        & > textarea,\r\n        & > select {\r\n            border: none !important;\r\n            box-sizing: border-box;\r\n            background-color: transparent !important;\r\n            padding: 0px;\r\n            @extend .wsx-input-typography;\r\n        }\r\n        &.wsx-outline-focus {\r\n            cursor: text;\r\n            display: block;\r\n            padding-left: 0px !important;\r\n            margin-top: 0px !important;\r\n            box-sizing: border-box;\r\n            @include inputStyle();\r\n            @include BorderOutlineStyle1(); \r\n            @include BottomRadiusNone();\r\n            @extend .wsx-formBuilder-input-layout;\r\n        }\r\n    }\r\n    .wsx-login-fields  {\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                color: var(--wsx-login-input-color);\r\n                &:focus {\r\n                    color: var(--wsx-login-input-focus-color) !important;\r\n                }\r\n            }\r\n            &.wsx-outline-focus {\r\n                background-color: var(--wsx-login-input-bg) !important;\r\n                &:focus-within {\r\n                    border-color: var(--wsx-login-input-focus-color) !important;\r\n                    background-color: var(--wsx-login-input-focus-bg) !important;\r\n                }\r\n            }\r\n            &.wsx-field-warning:not(.wsx-field-radio):not(.wsx-form-checkbox), &.wsx-field-warning.wsx-form-file  > .wsx-field-content {\r\n                @include inputWarningStyle(var(--wsx-login-input-warning-color), var(--wsx-login-input-warning-bg), var(--wsx-login-input-warning-border-color));           \r\n            }\r\n        }\r\n    }\r\n    .wsx-reg-fields  {\r\n        .wsx-form-field {\r\n            & > input,\r\n            & > textarea,\r\n            & > select {\r\n                color: var(--wsx-input-color);\r\n                &:focus {\r\n                    color: var(--wsx-input-focus-color) !important;\r\n                }\r\n            }\r\n            &.wsx-outline-focus {\r\n                background-color: var(--wsx-input-bg) !important;\r\n                &:focus-within {\r\n                    border-color: var(--wsx-input-focus-border-color) !important;\r\n                    background-color: var(--wsx-input-focus-bg) !important;\r\n                }\r\n            }\r\n            &.wsx-field-warning:not(.wsx-field-radio):not(.wsx-form-checkbox), &.wsx-field-warning.wsx-form-file  > .wsx-field-content {\r\n                @include inputWarningStyle(var(--wsx-input-warning-color), var(--wsx-input-warning-bg), var(--wsx-input-warning-border-color));\r\n            }\r\n        }\r\n    }\r\n    .wsx-field-radio .wsx-field-content, .wsx-form-checkbox .wsx-field-content {\r\n        background-color: unset;\r\n        border: 0px;\r\n        padding: 0px;\r\n    }\r\n    .wsx-file-label {\r\n        flex-direction: column;\r\n        align-items: flex-start !important;\r\n        @include BorderOutlineStyle1();\r\n        @include BottomRadiusNone();\r\n    }\r\n    .wsx-file-label_wrap {\r\n        @include WsxFlexStyle(flex-start, center );\r\n        gap: 20px;\r\n    }\r\n    .wsx-field-content {\r\n        border-radius: var(--wsx-input-border-radius) var(--wsx-input-border-radius) 0px 0px;\r\n        @include BorderOutlineStyle1();\r\n    }\r\n}\r\n/* ===============================  Variation Style 8 End ================================*/\r\n\r\n/* ===============================  Editor & Frontend Start ================================*/\r\n.wsx-form-wrapper_frontend.wsx_variation_5, .wsx-form-wrapper_frontend.wsx_variation_4  {\r\n    .wsx-field-content  > .wsx-file-label {\r\n        flex-direction: row !important;\r\n        align-items: center !important;\r\n    }\r\n}\r\n[data-type='wholesalex/forms']  {\r\n    width: 100% !important;\r\n    max-width: 100% !important;\r\n}\r\n[data-type='wholesalex/forms'], .wp-block-wholesalex-forms.wholesalex-form, .wsx-form-wrapper_frontend  {\r\n    .wsx-reg-form-heading {\r\n        padding-bottom: 20px;\r\n    }\r\n    .wholesalex-fields-wrapper {\r\n        display: flex;\r\n        flex-direction: column;\r\n        gap: 25px;\r\n    }\r\n    .wholesalex-form-wrapper, &.wholesalex-form-wrapper {\r\n        overflow: hidden;\r\n    }\r\n}\r\n[data-type='wholesalex/forms'] .wholesalex-registration-form .wholesalex-fields-wrapper, .wp-block-wholesalex-forms.wholesalex-form .wholesalex-fields-wrapper, .wsx-form-wrapper_frontend .wholesalex-fields-wrapper {\r\n    margin-top: 20px !important;\r\n}\r\n.wholesalex-form-preview-block-placeholder {\r\n    flex-direction: column;\r\n    @include WsxFlexStyle(flex-start, center);\r\n}\r\n@media only screen and (max-width: 1100px) {\r\n    .wholesalex-form-wrapper:not(.wsx-form-wrapper_frontend) {\r\n        flex-direction: column;\r\n        align-items: center;\r\n    }\r\n}\r\n@media only screen and (max-width: 991px) {\r\n    .wsx-form-wrapper_frontend.wholesalex-form-wrapper {\r\n        flex-direction: column;\r\n        align-items: center;\r\n    }\r\n}\r\n@media only screen and (max-width: 768px) {\r\n    .wholesalex-form-wrapper {\r\n        display: block;\r\n    }\r\n}\r\n// Double Coulmn\r\n.wsx-reg-form-row.double-column {\r\n    display: flex;\r\n    justify-content: space-between;\r\n    gap: 16px;\r\n    & > div {\r\n        flex: 1;\r\n    }\r\n}\r\n.wholesalex_circular_loading__wrapper {  \r\n    background-color: hsla(0, 0%, 100%, 0.5);\r\n    margin: 0;\r\n    transition: opacity 0.3s;\r\n    @include WsxPositionStyle(absolute, 0, 0, 0);\r\n    z-index: 9999999;\r\n    cursor: wait;\r\n    height: 100vh;\r\n    top: 0;\r\n    position: fixed !important;\r\n}\r\n.wholesalex_loading_spinner {\r\n    width: 100%;\r\n    text-align: center;\r\n    margin-top: -21px;\r\n    @include WsxPositionStyle(absolute, 50%);\r\n    top: 50% !important;\r\n}\r\n.wholesalex_circular_loading_icon {\r\n    stroke-width: 2;\r\n    stroke-dashoffset: 0;\r\n    stroke-dasharray: 90, 150;\r\n    stroke: var(--wholesalex-primary-color);\r\n    stroke-linecap: round;\r\n    animation: wholesalex_circular_loading 1.5s ease-in-out infinite;\r\n}\r\n@keyframes wholesalex_circular_loading {\r\n    0% {\r\n        stroke-dasharray: 1, 140;\r\n        stroke-dashoffset: 0;\r\n    }\r\n}\r\n.wholesalex_loading_spinner .move_circular {\r\n    height: 42px;\r\n    width: 42px;\r\n    animation: circular_rotate 2s linear infinite;\r\n}\r\n@keyframes circular_rotate {\r\n    100% {\r\n        transform: rotate(1turn);\r\n    }\r\n}\r\n\r\n.wsx_variation_1,\r\n.wsx_variation_2,\r\n.wsx_variation_3,\r\n.wsx_variation_4,\r\n.wsx_variation_5,\r\n.wsx_variation_7,\r\n.wsx_variation_8 {\r\n    .wsx-login-fields {\r\n        \r\n        .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-login-form-label-color-warning) !important;\r\n        }\r\n        .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-login-form-label-color-active) !important;\r\n        }\r\n    }\r\n\r\n    .wsx-reg-fields {\r\n        .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-form-label-color-warning) !important;\r\n        }\r\n        .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-form-label-color-active) !important;\r\n        }\r\n    }\r\n}\r\n.wsx_variation_6 {\r\n    .wsx-login-fields {\r\n        \r\n        .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-login-form-label-color-warning) !important;\r\n        }\r\n\r\n        .wsx-reg-form-row:focus-within .wsx-form-label:not(.wsx-clone-label),  .wsx-field:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-login-form-label-color-active) !important;\r\n        }\r\n    }\r\n\r\n    .wsx-reg-fields {\r\n        .wsx-field-warning .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-form-label-color-warning) !important;\r\n        }\r\n        .wsx-reg-form-row:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx-field:focus-within .wsx-form-label:not(.wsx-clone-label), .wsx-form-field:focus-within .wsx-form-label:not(.wsx-clone-label) {\r\n            color: var(--wsx-form-label-color-active) !important;\r\n        }\r\n    }\r\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/FormBuilder.scss":
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/FormBuilder.scss ***!
  \*******************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.wholesalex_form_builder {
  display: flex; }
  .wholesalex_form_builder .form-builder__field-exclude-roles {
    display: flex;
    align-items: center; }
  .wholesalex_form_builder ul.exclude-roles {
    display: flex; }
    .wholesalex_form_builder ul.exclude-roles li {
      margin: 0; }
  .wholesalex_form_builder ul.exclude-roles li:not(:last-child):after {
    content: ","; }

.wholesalex_form_builder__settings {
  background-color: rgba(108, 108, 255, 0.08);
  width: 30%;
  max-width: 300px;
  text-align: left; }

.wholesalex_form_builder__settings_heading {
  font-size: 20px;
  font-weight: 500;
  color: var(--wholesalex-primary-color);
  padding: 17px 25px;
  border-bottom: 1px solid rgba(108, 108, 255, 0.15);
  line-height: 28px; }

.wholesalex_form_builder__container {
  padding-bottom: 50px;
  background-color: white;
  width: 70%; }

.wholesalex_form_builder__settings_content {
  padding: 25px; }

.wholesalex_form_builder__settings_field {
  display: flex;
  flex-direction: column;
  gap: 8px; }

.wholesalex_form_builder__settings_fields {
  display: flex;
  flex-direction: column;
  gap: 25px; }

.wholesalex_form_builder__container_heading {
  font-size: 20px;
  line-height: 28px;
  color: var(--wholesalex-heading-text-color);
  text-align: left;
  font-weight: 500;
  padding: 17px 25px; }

.wholesalex_form_builder__container_header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--wholesalex-border-color);
  padding-right: 20px; }

.wholesalex_form_builder__container_header__right {
  display: flex;
  align-items: center;
  gap: 10px; }

.wholesalex_form_builder__fields {
  padding: 100px 50px;
  display: flex;
  flex-direction: column;
  gap: 70px; }

.wholesalex_form_builder__field {
  position: relative; }

.wholesalex_form_builder__field {
  text-align: left;
  display: flex;
  gap: 10px;
  flex-direction: column;
  justify-content: flex-end;
  padding: 22px 20px 20px;
  border-radius: 2px;
  border: solid 1px #c8c8d6;
  background-color: #fff; }

.wholesalex_form_builder__field_control {
  display: flex;
  align-items: center;
  border: 1px solid #c8c8d6;
  width: fit-content;
  position: absolute;
  top: -50px;
  left: 0; }

.wholesalex_form_builder__field .dashicons {
  font-size: 18px;
  width: 18px;
  color: var(--wholesalex-heading-text-color); }
  .wholesalex_form_builder__field .dashicons:hover {
    color: #2E333D; }

.wholesalex_form_builder__field_control > div {
  display: flex;
  align-items: center;
  padding: 0px 10px;
  cursor: pointer;
  height: 36px;
  flex-direction: column;
  justify-content: center; }
  .wholesalex_form_builder__field_control > div:hover {
    background-color: #F5F5F5;
    color: #2E333D; }

.wholesalex_form_builder__field_control > :not(:last-child) {
  border-right: 1px solid #c8c8d6; }

.wholesalex_form_builder__add_fields_container {
  padding: 22px 29px 25px 25px;
  border-radius: 4px;
  box-shadow: 2px 2px 8px 0 rgba(108, 108, 255, 0.16);
  background-color: #fff; }

.wholesalex_form_builder__add_new_field_popup {
  margin-top: -50px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 30px; }

.wholesalex_form_builder__add_fields__label {
  color: var(--wholesalex-heading-text-color);
  font-size: 18px;
  font-weight: 500;
  line-height: 22px;
  margin-bottom: 15px; }

button.wholesalex_form_builder__field_button {
  padding: 6px 13px 6px 12px;
  border-radius: 2px;
  background-color: rgba(108, 108, 255, 0.15);
  border: none;
  color: var(--wholesalex-heading-text-color);
  font-size: 14px;
  line-height: 22px;
  cursor: pointer; }

.wholesalex_form_builder__add_fields_group {
  display: flex;
  flex-wrap: wrap;
  gap: 5px; }

.wholesalex_form_builder__field_button:disabled {
  background-color: rgba(108, 108, 255, 0.08); }

.wholesalex_form_builder__add_fields_container {
  display: flex;
  flex-direction: column;
  gap: 30px;
  text-align: left;
  max-width: 365px; }

.wholesalex_form_builder__field_preview {
  display: flex;
  flex-direction: column;
  gap: 10px; }

.wholesalex_form_builder__settings_field .wholesalex_mulitple_select_inputs {
  min-width: unset;
  border-radius: 2px;
  border: solid 1px var(--wholesalex-border-color); }

.wholesalex_form_builder__settings_field_label.wholesalex_field_label {
  display: flex;
  align-items: center;
  gap: 5px; }

.dashicons-lock {
  color: #f78430; }

input#excludeRoles {
  box-shadow: none; }

button:not([disabled]).wholesalex_form_builder__field_button:hover {
  background-color: rgba(24, 24, 227, 0.15); }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/FormBuilder.scss"],"names":[],"mappings":"AAAA;EACI,aAAa,EAAA;EADjB;IAIQ,aAAa;IACb,mBAAmB,EAAA;EAL3B;IAQQ,aAAa,EAAA;IARrB;MAUY,SAAS,EAAA;EAVrB;IAcQ,YAAY,EAAA;;AAGpB;EACI,2CAA2C;EAC3C,UAAU;EACV,gBAAgB;EAChB,gBAAgB,EAAA;;AAEpB;EACI,eAAe;EACf,gBAAgB;EAChB,sCAAsC;EACtC,kBAAiB;EACjB,kDAAkD;EAClD,iBAAiB,EAAA;;AAErB;EACI,oBAAoB;EACpB,uBAAuB;EACvB,UAAU,EAAA;;AAGd;EACI,aAAY,EAAA;;AAGhB;EACI,aAAa;EACb,sBAAsB;EACtB,QAAQ,EAAA;;AAEZ;EACI,aAAa;EACb,sBAAsB;EACtB,SAAS,EAAA;;AAEb;EACI,eAAe;EACf,iBAAiB;EACjB,2CAA2C;EAC3C,gBAAgB;EAChB,gBAAgB;EAChB,kBAAkB,EAAA;;AAGtB;EACI,aAAa;EACb,mBAAmB;EACnB,8BAA8B;EAC9B,uDAAuD;EACvD,mBAAmB,EAAA;;AAGvB;EACI,aAAa;EACb,mBAAmB;EACnB,SAAS,EAAA;;AAGb;EACI,mBAAmB;EACnB,aAAa;EACb,sBAAsB;EACtB,SAAQ,EAAA;;AAGZ;EACI,kBAAkB,EAAA;;AAGtB;EACI,gBAAgB;EAChB,aAAa;EACb,SAAS;EACT,sBAAsB;EAEtB,yBAAyB;EACzB,uBAAuB;EACvB,kBAAkB;EAClB,yBAAyB;EACzB,sBAAsB,EAAA;;AAE1B;EACI,aAAa;EACb,mBAAmB;EACnB,yBAAyB;EACzB,kBAAkB;EAClB,kBAAkB;EAClB,UAAU;EACV,OAAO,EAAA;;AAIX;EACI,eAAe;EACf,WAAW;EACX,2CAA2C,EAAA;EAH/C;IAKQ,cAAc,EAAA;;AAItB;EACI,aAAa;EACb,mBAAmB;EACnB,iBAAiB;EACjB,eAAe;EACf,YAAY;EACZ,sBAAsB;EACtB,uBAAuB,EAAA;EAP3B;IAUQ,yBAAyB;IACzB,cAAc,EAAA;;AAItB;EACI,+BAA+B,EAAA;;AAGnC;EACI,4BAA4B;EAC5B,kBAAkB;EAClB,mDAAmD;EACnD,sBAAsB,EAAA;;AAG1B;EACI,iBAAiB;EACjB,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,sBAAsB;EACtB,SAAS,EAAA;;AAGb;EACI,2CAA2C;EAC3C,eAAe;EACf,gBAAgB;EAChB,iBAAiB;EACjB,mBAAmB,EAAA;;AAGvB;EACI,0BAA0B;EAC1B,kBAAkB;EAClB,2CAA2C;EAC3C,YAAY;EACZ,2CAA2C;EAC3C,eAAe;EACf,iBAAiB;EACjB,eAAe,EAAA;;AAGnB;EACI,aAAa;EACb,eAAe;EACf,QAAQ,EAAA;;AAGZ;EACI,2CAA2C,EAAA;;AAG/C;EACI,aAAa;EACb,sBAAsB;EACtB,SAAS;EACT,gBAAgB;EAChB,gBAAgB,EAAA;;AAGpB;EACI,aAAa;EACb,sBAAsB;EACtB,SAAS,EAAA;;AAGb;EACI,gBAAgB;EAChB,kBAAkB;EAClB,gDAAgD,EAAA;;AAGpD;EACI,aAAa;EACb,mBAAmB;EACnB,QAAQ,EAAA;;AAGZ;EACI,cAAc,EAAA;;AAElB;EACI,gBAAgB,EAAA;;AAEpB;EACI,yCAAyC,EAAA","sourcesContent":[".wholesalex_form_builder{\r\n    display: flex;\r\n\r\n    .form-builder__field-exclude-roles {\r\n        display: flex;\r\n        align-items: center;\r\n    }\r\n    ul.exclude-roles {\r\n        display: flex;\r\n        li{\r\n            margin: 0;\r\n        }\r\n    }\r\n    ul.exclude-roles li:not(:last-child):after {\r\n        content: \",\";\r\n    }\r\n}\r\n.wholesalex_form_builder__settings{\r\n    background-color: rgba(108, 108, 255, 0.08);\r\n    width: 30%;\r\n    max-width: 300px;\r\n    text-align: left;\r\n}\r\n.wholesalex_form_builder__settings_heading {\r\n    font-size: 20px;\r\n    font-weight: 500;\r\n    color: var(--wholesalex-primary-color);\r\n    padding:17px 25px;\r\n    border-bottom: 1px solid rgba(108, 108, 255, 0.15);\r\n    line-height: 28px;\r\n}\r\n.wholesalex_form_builder__container{\r\n    padding-bottom: 50px;\r\n    background-color: white;\r\n    width: 70%;\r\n}\r\n\r\n.wholesalex_form_builder__settings_content{\r\n    padding:25px;\r\n}\r\n\r\n.wholesalex_form_builder__settings_field {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 8px;\r\n}\r\n.wholesalex_form_builder__settings_fields {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 25px;\r\n}\r\n.wholesalex_form_builder__container_heading {\r\n    font-size: 20px;\r\n    line-height: 28px;\r\n    color: var(--wholesalex-heading-text-color);\r\n    text-align: left;\r\n    font-weight: 500;\r\n    padding: 17px 25px;\r\n}\r\n\r\n.wholesalex_form_builder__container_header {\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: space-between;\r\n    border-bottom: 1px solid var(--wholesalex-border-color);\r\n    padding-right: 20px;\r\n}\r\n\r\n.wholesalex_form_builder__container_header__right {\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 10px;\r\n}\r\n\r\n.wholesalex_form_builder__fields {\r\n    padding: 100px 50px;\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap:70px;\r\n}\r\n\r\n.wholesalex_form_builder__field{\r\n    position: relative;\r\n}\r\n\r\n.wholesalex_form_builder__field {\r\n    text-align: left;\r\n    display: flex;\r\n    gap: 10px;\r\n    flex-direction: column;\r\n    // min-height: 65px;\r\n    justify-content: flex-end;\r\n    padding: 22px 20px 20px;\r\n    border-radius: 2px;\r\n    border: solid 1px #c8c8d6;\r\n    background-color: #fff;\r\n}\r\n.wholesalex_form_builder__field_control {\r\n    display: flex;\r\n    align-items: center;\r\n    border: 1px solid #c8c8d6;\r\n    width: fit-content;\r\n    position: absolute;\r\n    top: -50px;\r\n    left: 0;\r\n    \r\n}\r\n\r\n.wholesalex_form_builder__field .dashicons {\r\n    font-size: 18px;\r\n    width: 18px;\r\n    color: var(--wholesalex-heading-text-color);\r\n    &:hover{\r\n        color: #2E333D;\r\n    }\r\n   \r\n}\r\n.wholesalex_form_builder__field_control > div {\r\n    display: flex;\r\n    align-items: center;\r\n    padding: 0px 10px;\r\n    cursor: pointer;\r\n    height: 36px;\r\n    flex-direction: column;\r\n    justify-content: center;\r\n\r\n    &:hover{\r\n        background-color: #F5F5F5;\r\n        color: #2E333D;\r\n    }\r\n}\r\n\r\n.wholesalex_form_builder__field_control> :not(:last-child) {\r\n    border-right: 1px solid #c8c8d6;\r\n}\r\n\r\n.wholesalex_form_builder__add_fields_container{\r\n    padding: 22px 29px 25px 25px;\r\n    border-radius: 4px;\r\n    box-shadow: 2px 2px 8px 0 rgba(108, 108, 255, 0.16);\r\n    background-color: #fff;\r\n}\r\n\r\n.wholesalex_form_builder__add_new_field_popup {\r\n    margin-top: -50px;\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: center;\r\n    flex-direction: column;\r\n    gap: 30px;\r\n}\r\n\r\n.wholesalex_form_builder__add_fields__label {\r\n    color: var(--wholesalex-heading-text-color);\r\n    font-size: 18px;\r\n    font-weight: 500;\r\n    line-height: 22px;\r\n    margin-bottom: 15px;\r\n}\r\n\r\nbutton.wholesalex_form_builder__field_button {\r\n    padding: 6px 13px 6px 12px;\r\n    border-radius: 2px;\r\n    background-color: rgba(108, 108, 255, 0.15);\r\n    border: none;\r\n    color: var(--wholesalex-heading-text-color);\r\n    font-size: 14px;\r\n    line-height: 22px;\r\n    cursor: pointer;\r\n}\r\n\r\n.wholesalex_form_builder__add_fields_group {\r\n    display: flex;\r\n    flex-wrap: wrap;\r\n    gap: 5px;\r\n}\r\n\r\n.wholesalex_form_builder__field_button:disabled {\r\n    background-color: rgba(108, 108, 255, 0.08);\r\n}\r\n\r\n.wholesalex_form_builder__add_fields_container {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 30px;\r\n    text-align: left;\r\n    max-width: 365px;\r\n}\r\n\r\n.wholesalex_form_builder__field_preview {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 10px;\r\n}\r\n\r\n.wholesalex_form_builder__settings_field .wholesalex_mulitple_select_inputs {\r\n    min-width: unset;\r\n    border-radius: 2px;\r\n    border: solid 1px var(--wholesalex-border-color);\r\n}\r\n\r\n.wholesalex_form_builder__settings_field_label.wholesalex_field_label {\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 5px;\r\n}\r\n\r\n.dashicons-lock {\r\n    color: #f78430;\r\n}\r\ninput#excludeRoles {\r\n    box-shadow: none;\r\n}\r\nbutton:not([disabled]).wholesalex_form_builder__field_button:hover {\r\n    background-color: rgba(24, 24, 227, 0.15);\r\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Shortcode.scss":
/*!*****************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Shortcode.scss ***!
  \*****************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ``, "",{"version":3,"sources":[],"names":[],"mappings":"","sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/ShortcodesModal.scss":
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/ShortcodesModal.scss ***!
  \***********************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.form-builder-modal .shortcode input {
  border: solid 1px var(--wholesalex-border-color);
  padding-right: 65px;
  font-size: 12px;
  background-color: white; }

.form-builder-modal .shortcode {
  display: flex;
  align-items: center; }

.form-builder-modal .modal-content ul li {
  padding-bottom: 15px;
  margin-bottom: 15px; }
  .form-builder-modal .modal-content ul li div {
    align-items: center;
    margin-right: 5px; }

.form-builder-modal .shortcode .shortcode-icon {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  background-color: #e2e2fe;
  color: var(--wholesalex-primary-color);
  line-height: 22px;
  cursor: copy;
  right: 0;
  translate: -130%; }

.form-builder-modal .shortcode-icon {
  font-size: 14px; }

.form-builder-modal .shortcode {
  width: auto; }

.form-builder-modal .shortcode input[type="text"] {
  min-width: 360px; }

.form-builder-modal .shortcode .tooltip-content {
  transform: translateX(-73%); }

.play-icon {
  font-size: 14px;
  border: 2px solid var(--wholesalex-primary-color);
  line-height: 20px;
  border-radius: 50%;
  height: 14px;
  width: 14px;
  display: flex;
  align-items: center;
  justify-content: center; }

.modal-help {
  color: var(--wholesalex-primary-color);
  font-size: 15px;
  line-height: 20px;
  border: 1px solid var(--wholesalex-primary-color);
  display: inline-flex;
  padding: 7px 15px;
  border-radius: 2px;
  align-items: center;
  gap: 10px; }

.modal-header {
  background-color: #f7f7f7;
  padding: 15px;
  display: flex;
  gap: 20px;
  align-items: center;
  border-bottom: 1px solid #eaeaea;
  border-top-left-radius: 6px;
  border-top-right-radius: 6px;
  position: relative; }

.modal-title {
  color: #262626;
  font-size: 20px;
  line-height: 22px;
  font-weight: 500; }

.close-modal-icon {
  font-size: 22px;
  line-height: 26px;
  color: white;
  background-color: black;
  height: 40px;
  width: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  right: 0;
  top: 0;
  translate: 50% -50%;
  cursor: pointer; }

.modal-content ul li {
  display: flex;
  align-items: center;
  gap: 25px;
  justify-content: space-between;
  padding-bottom: 25px;
  border-bottom: solid 1px #e2e2e2; }
  .modal-content ul li div {
    flex: 1; }
  .modal-content ul li .wholesalex-shortcode-for-login input[type=checkbox] {
    margin: 0;
    margin-right: 5px;
    margin-top: 1px; }

.shortcode-for {
  font-size: 16px;
  line-height: 20px;
  color: #262626; }

.modal-content {
  padding: 25px;
  height: 60vh;
  overflow: auto; }

.shortcode-content {
  display: flex; }

.wholesalex_modal_wrapper {
  position: fixed;
  max-width: 1200px;
  width: 100%;
  top: 140px; }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/ShortcodesModal.scss"],"names":[],"mappings":"AAAA;EASQ,gDAAgD;EAChD,mBAAmB;EACnB,eAAe;EACf,uBAAuB,EAAA;;AAZ/B;EAgBQ,aAAa;EACb,mBAAmB,EAAA;;AAjB3B;EAqBQ,oBAAoB;EACpB,mBAAmB,EAAA;EAtB3B;IAwBY,mBAAmB;IACnB,iBAAiB,EAAA;;AAzB7B;EA8BQ,WAAW;EACX,YAAY;EACZ,kBAAkB;EAClB,yBAAyB;EACzB,sCAAsC;EACtC,iBAAiB;EACjB,YAAY;EACZ,QAAQ;EACR,gBAAgB,EAAA;;AAtCxB;EAyCQ,eAAe,EAAA;;AAzCvB;EA6CQ,WAAW,EAAA;;AA7CnB;EAiDQ,gBAAgB,EAAA;;AAjDxB;EAqDQ,2BAA2B,EAAA;;AAGnC;EACI,eAAe;EACf,iDAAiD;EACjD,iBAAiB;EACjB,kBAAkB;EAClB,YAAY;EACZ,WAAW;EACX,aAAa;EACb,mBAAmB;EACnB,uBAAuB,EAAA;;AAE3B;EACI,sCAAsC;EACtC,eAAe;EACf,iBAAiB;EACjB,iDAAiD;EACjD,oBAAoB;EACpB,iBAAiB;EACjB,kBAAkB;EAClB,mBAAmB;EACnB,SAAS,EAAA;;AAEb;EACI,yBAAyB;EACzB,aAAa;EACb,aAAa;EACb,SAAS;EACT,mBAAmB;EACnB,gCAAgC;EAChC,2BAA2B;EAC3B,4BAA4B;EAC5B,kBAAkB,EAAA;;AAEtB;EACI,cAAc;EACd,eAAe;EACf,iBAAiB;EACjB,gBAAgB,EAAA;;AAEpB;EACI,eAAe;EACf,iBAAiB;EACjB,YAAY;EACZ,uBAAuB;EACvB,YAAY;EACZ,WAAW;EACX,kBAAkB;EAClB,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,kBAAkB;EAClB,QAAQ;EACR,MAAM;EACN,mBAAmB;EACnB,eAAe,EAAA;;AAInB;EACI,aAAa;EACb,mBAAmB;EACnB,SAAS;EACT,8BAA8B;EAC9B,oBAAoB;EACpB,gCAAgC,EAAA;EANpC;IAQQ,OAAO,EAAA;EARf;IAaY,SAAQ;IACR,iBAAiB;IACjB,eAAe,EAAA;;AAK3B;EACI,eAAe;EACf,iBAAiB;EACjB,cAAc,EAAA;;AAElB;EACI,aAAa;EACb,YAAY;EACZ,cAAc,EAAA;;AAElB;EACI,aAAa,EAAA;;AAGjB;EACI,eAAe;EACf,iBAAiB;EACjB,WAAW;EACX,UAAU,EAAA","sourcesContent":[".form-builder-modal {\r\n    // padding: 0 0 25px;\r\n    // border-radius: 6px;\r\n    // box-shadow: 0 0 40px 0 rgba(0, 0, 0, 0.16);\r\n    // background-color: #ffffff;\r\n    // z-index: 99999999;\r\n    // width: 100%;\r\n\r\n    .shortcode input {\r\n        border: solid 1px var(--wholesalex-border-color);\r\n        padding-right: 65px;\r\n        font-size: 12px;\r\n        background-color: white;\r\n    }\r\n    \r\n    .shortcode {\r\n        display: flex;\r\n        align-items: center;\r\n    }\r\n    \r\n    .modal-content ul li {\r\n        padding-bottom: 15px;\r\n        margin-bottom: 15px;\r\n        div {\r\n            align-items: center;\r\n            margin-right: 5px;\r\n        }\r\n    }\r\n    \r\n    .shortcode .shortcode-icon {\r\n        width: 22px;\r\n        height: 22px;\r\n        border-radius: 4px;\r\n        background-color: #e2e2fe;\r\n        color: var(--wholesalex-primary-color);\r\n        line-height: 22px;\r\n        cursor: copy;\r\n        right: 0;\r\n        translate: -130%;\r\n    }\r\n    .shortcode-icon {\r\n        font-size: 14px;\r\n    }\r\n    \r\n    .shortcode {\r\n        width: auto;\r\n    }\r\n    \r\n    .shortcode input[type=\"text\"] {\r\n        min-width: 360px;\r\n    }\r\n\r\n    .shortcode .tooltip-content {\r\n        transform: translateX(-73%);\r\n    }\r\n}\r\n.play-icon {\r\n    font-size: 14px;\r\n    border: 2px solid var(--wholesalex-primary-color);\r\n    line-height: 20px;\r\n    border-radius: 50%;\r\n    height: 14px;\r\n    width: 14px;\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: center;\r\n}\r\n.modal-help {\r\n    color: var(--wholesalex-primary-color);\r\n    font-size: 15px;\r\n    line-height: 20px;\r\n    border: 1px solid var(--wholesalex-primary-color);\r\n    display: inline-flex;\r\n    padding: 7px 15px;\r\n    border-radius: 2px;\r\n    align-items: center;\r\n    gap: 10px;\r\n}\r\n.modal-header {\r\n    background-color: #f7f7f7;\r\n    padding: 15px;\r\n    display: flex;\r\n    gap: 20px;\r\n    align-items: center;\r\n    border-bottom: 1px solid #eaeaea;\r\n    border-top-left-radius: 6px;\r\n    border-top-right-radius: 6px;\r\n    position: relative;\r\n}\r\n.modal-title {\r\n    color: #262626;\r\n    font-size: 20px;\r\n    line-height: 22px;\r\n    font-weight: 500;\r\n}\r\n.close-modal-icon {\r\n    font-size: 22px;\r\n    line-height: 26px;\r\n    color: white;\r\n    background-color: black;\r\n    height: 40px;\r\n    width: 40px;\r\n    border-radius: 50%;\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: center;\r\n    position: absolute;\r\n    right: 0;\r\n    top: 0;\r\n    translate: 50% -50%;\r\n    cursor: pointer;\r\n}\r\n\r\n\r\n.modal-content ul li {\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 25px;\r\n    justify-content: space-between;\r\n    padding-bottom: 25px;\r\n    border-bottom: solid 1px #e2e2e2;\r\n    div {\r\n        flex: 1;\r\n    }\r\n\r\n    .wholesalex-shortcode-for-login{\r\n        input[type=checkbox] {\r\n            margin:0;\r\n            margin-right: 5px;\r\n            margin-top: 1px;\r\n        }\r\n    }\r\n}\r\n\r\n.shortcode-for {\r\n    font-size: 16px;\r\n    line-height: 20px;\r\n    color: #262626;\r\n}\r\n.modal-content {\r\n    padding: 25px;\r\n    height: 60vh;\r\n    overflow: auto;\r\n}\r\n.shortcode-content {\r\n    display: flex;\r\n}\r\n\r\n.wholesalex_modal_wrapper {\r\n    position: fixed;\r\n    max-width: 1200px;\r\n    width: 100%;\r\n    top: 140px;\r\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/runtime/api.js":
/*!*****************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/api.js ***!
  \*****************************************************/
/***/ ((module) => {

"use strict";


/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
module.exports = function (cssWithMappingToString) {
  var list = [];

  // return the list of modules as css string
  list.toString = function toString() {
    return this.map(function (item) {
      var content = "";
      var needLayer = typeof item[5] !== "undefined";
      if (item[4]) {
        content += "@supports (".concat(item[4], ") {");
      }
      if (item[2]) {
        content += "@media ".concat(item[2], " {");
      }
      if (needLayer) {
        content += "@layer".concat(item[5].length > 0 ? " ".concat(item[5]) : "", " {");
      }
      content += cssWithMappingToString(item);
      if (needLayer) {
        content += "}";
      }
      if (item[2]) {
        content += "}";
      }
      if (item[4]) {
        content += "}";
      }
      return content;
    }).join("");
  };

  // import a list of modules into the list
  list.i = function i(modules, media, dedupe, supports, layer) {
    if (typeof modules === "string") {
      modules = [[null, modules, undefined]];
    }
    var alreadyImportedModules = {};
    if (dedupe) {
      for (var k = 0; k < this.length; k++) {
        var id = this[k][0];
        if (id != null) {
          alreadyImportedModules[id] = true;
        }
      }
    }
    for (var _k = 0; _k < modules.length; _k++) {
      var item = [].concat(modules[_k]);
      if (dedupe && alreadyImportedModules[item[0]]) {
        continue;
      }
      if (typeof layer !== "undefined") {
        if (typeof item[5] === "undefined") {
          item[5] = layer;
        } else {
          item[1] = "@layer".concat(item[5].length > 0 ? " ".concat(item[5]) : "", " {").concat(item[1], "}");
          item[5] = layer;
        }
      }
      if (media) {
        if (!item[2]) {
          item[2] = media;
        } else {
          item[1] = "@media ".concat(item[2], " {").concat(item[1], "}");
          item[2] = media;
        }
      }
      if (supports) {
        if (!item[4]) {
          item[4] = "".concat(supports);
        } else {
          item[1] = "@supports (".concat(item[4], ") {").concat(item[1], "}");
          item[4] = supports;
        }
      }
      list.push(item);
    }
  };
  return list;
};

/***/ }),

/***/ "./node_modules/css-loader/dist/runtime/sourceMaps.js":
/*!************************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/sourceMaps.js ***!
  \************************************************************/
/***/ ((module) => {

"use strict";


module.exports = function (item) {
  var content = item[1];
  var cssMapping = item[3];
  if (!cssMapping) {
    return content;
  }
  if (typeof btoa === "function") {
    var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(cssMapping))));
    var data = "sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(base64);
    var sourceMapping = "/*# ".concat(data, " */");
    return [content].concat([sourceMapping]).join("\n");
  }
  return [content].join("\n");
};

/***/ }),

/***/ "./reactjs/src/assets/scss/Editor.scss":
/*!*********************************************!*\
  !*** ./reactjs/src/assets/scss/Editor.scss ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Editor_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./Editor.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Editor.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Editor_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Editor_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Editor_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Editor_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./reactjs/src/assets/scss/Form.scss":
/*!*******************************************!*\
  !*** ./reactjs/src/assets/scss/Form.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Form_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./Form.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Form.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Form_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Form_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Form_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Form_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./reactjs/src/assets/scss/FormBuilder.scss":
/*!**************************************************!*\
  !*** ./reactjs/src/assets/scss/FormBuilder.scss ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_FormBuilder_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./FormBuilder.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/FormBuilder.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_FormBuilder_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_FormBuilder_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_FormBuilder_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_FormBuilder_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./reactjs/src/assets/scss/Shortcode.scss":
/*!************************************************!*\
  !*** ./reactjs/src/assets/scss/Shortcode.scss ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Shortcode_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./Shortcode.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Shortcode.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Shortcode_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Shortcode_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Shortcode_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Shortcode_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./reactjs/src/assets/scss/ShortcodesModal.scss":
/*!******************************************************!*\
  !*** ./reactjs/src/assets/scss/ShortcodesModal.scss ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_ShortcodesModal_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./ShortcodesModal.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/ShortcodesModal.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_ShortcodesModal_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_ShortcodesModal_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_ShortcodesModal_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_ShortcodesModal_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js":
/*!****************************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js ***!
  \****************************************************************************/
/***/ ((module) => {

"use strict";


var stylesInDOM = [];
function getIndexByIdentifier(identifier) {
  var result = -1;
  for (var i = 0; i < stylesInDOM.length; i++) {
    if (stylesInDOM[i].identifier === identifier) {
      result = i;
      break;
    }
  }
  return result;
}
function modulesToDom(list, options) {
  var idCountMap = {};
  var identifiers = [];
  for (var i = 0; i < list.length; i++) {
    var item = list[i];
    var id = options.base ? item[0] + options.base : item[0];
    var count = idCountMap[id] || 0;
    var identifier = "".concat(id, " ").concat(count);
    idCountMap[id] = count + 1;
    var indexByIdentifier = getIndexByIdentifier(identifier);
    var obj = {
      css: item[1],
      media: item[2],
      sourceMap: item[3],
      supports: item[4],
      layer: item[5]
    };
    if (indexByIdentifier !== -1) {
      stylesInDOM[indexByIdentifier].references++;
      stylesInDOM[indexByIdentifier].updater(obj);
    } else {
      var updater = addElementStyle(obj, options);
      options.byIndex = i;
      stylesInDOM.splice(i, 0, {
        identifier: identifier,
        updater: updater,
        references: 1
      });
    }
    identifiers.push(identifier);
  }
  return identifiers;
}
function addElementStyle(obj, options) {
  var api = options.domAPI(options);
  api.update(obj);
  var updater = function updater(newObj) {
    if (newObj) {
      if (newObj.css === obj.css && newObj.media === obj.media && newObj.sourceMap === obj.sourceMap && newObj.supports === obj.supports && newObj.layer === obj.layer) {
        return;
      }
      api.update(obj = newObj);
    } else {
      api.remove();
    }
  };
  return updater;
}
module.exports = function (list, options) {
  options = options || {};
  list = list || [];
  var lastIdentifiers = modulesToDom(list, options);
  return function update(newList) {
    newList = newList || [];
    for (var i = 0; i < lastIdentifiers.length; i++) {
      var identifier = lastIdentifiers[i];
      var index = getIndexByIdentifier(identifier);
      stylesInDOM[index].references--;
    }
    var newLastIdentifiers = modulesToDom(newList, options);
    for (var _i = 0; _i < lastIdentifiers.length; _i++) {
      var _identifier = lastIdentifiers[_i];
      var _index = getIndexByIdentifier(_identifier);
      if (stylesInDOM[_index].references === 0) {
        stylesInDOM[_index].updater();
        stylesInDOM.splice(_index, 1);
      }
    }
    lastIdentifiers = newLastIdentifiers;
  };
};

/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/insertBySelector.js":
/*!********************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/insertBySelector.js ***!
  \********************************************************************/
/***/ ((module) => {

"use strict";


var memo = {};

/* istanbul ignore next  */
function getTarget(target) {
  if (typeof memo[target] === "undefined") {
    var styleTarget = document.querySelector(target);

    // Special case to return head of iframe instead of iframe itself
    if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
      try {
        // This will throw an exception if access to iframe is blocked
        // due to cross-origin restrictions
        styleTarget = styleTarget.contentDocument.head;
      } catch (e) {
        // istanbul ignore next
        styleTarget = null;
      }
    }
    memo[target] = styleTarget;
  }
  return memo[target];
}

/* istanbul ignore next  */
function insertBySelector(insert, style) {
  var target = getTarget(insert);
  if (!target) {
    throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");
  }
  target.appendChild(style);
}
module.exports = insertBySelector;

/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/insertStyleElement.js":
/*!**********************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/insertStyleElement.js ***!
  \**********************************************************************/
/***/ ((module) => {

"use strict";


/* istanbul ignore next  */
function insertStyleElement(options) {
  var element = document.createElement("style");
  options.setAttributes(element, options.attributes);
  options.insert(element, options.options);
  return element;
}
module.exports = insertStyleElement;

/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js ***!
  \**********************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


/* istanbul ignore next  */
function setAttributesWithoutAttributes(styleElement) {
  var nonce =  true ? __webpack_require__.nc : 0;
  if (nonce) {
    styleElement.setAttribute("nonce", nonce);
  }
}
module.exports = setAttributesWithoutAttributes;

/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/styleDomAPI.js":
/*!***************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/styleDomAPI.js ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";


/* istanbul ignore next  */
function apply(styleElement, options, obj) {
  var css = "";
  if (obj.supports) {
    css += "@supports (".concat(obj.supports, ") {");
  }
  if (obj.media) {
    css += "@media ".concat(obj.media, " {");
  }
  var needLayer = typeof obj.layer !== "undefined";
  if (needLayer) {
    css += "@layer".concat(obj.layer.length > 0 ? " ".concat(obj.layer) : "", " {");
  }
  css += obj.css;
  if (needLayer) {
    css += "}";
  }
  if (obj.media) {
    css += "}";
  }
  if (obj.supports) {
    css += "}";
  }
  var sourceMap = obj.sourceMap;
  if (sourceMap && typeof btoa !== "undefined") {
    css += "\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))), " */");
  }

  // For old IE
  /* istanbul ignore if  */
  options.styleTagTransform(css, styleElement, options.options);
}
function removeStyleElement(styleElement) {
  // istanbul ignore if
  if (styleElement.parentNode === null) {
    return false;
  }
  styleElement.parentNode.removeChild(styleElement);
}

/* istanbul ignore next  */
function domAPI(options) {
  if (typeof document === "undefined") {
    return {
      update: function update() {},
      remove: function remove() {}
    };
  }
  var styleElement = options.insertStyleElement(options);
  return {
    update: function update(obj) {
      apply(styleElement, options, obj);
    },
    remove: function remove() {
      removeStyleElement(styleElement);
    }
  };
}
module.exports = domAPI;

/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/styleTagTransform.js":
/*!*********************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/styleTagTransform.js ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";


/* istanbul ignore next  */
function styleTagTransform(css, styleElement) {
  if (styleElement.styleSheet) {
    styleElement.styleSheet.cssText = css;
  } else {
    while (styleElement.firstChild) {
      styleElement.removeChild(styleElement.firstChild);
    }
    styleElement.appendChild(document.createTextNode(css));
  }
}
module.exports = styleTagTransform;

/***/ }),

/***/ "./node_modules/tannin/index.js":
/*!**************************************!*\
  !*** ./node_modules/tannin/index.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Tannin)
/* harmony export */ });
/* harmony import */ var _tannin_plural_forms__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @tannin/plural-forms */ "./node_modules/@tannin/plural-forms/index.js");


/**
 * Tannin constructor options.
 *
 * @typedef {Object} TanninOptions
 *
 * @property {string}   [contextDelimiter] Joiner in string lookup with context.
 * @property {Function} [onMissingKey]     Callback to invoke when key missing.
 */

/**
 * Domain metadata.
 *
 * @typedef {Object} TanninDomainMetadata
 *
 * @property {string}            [domain]       Domain name.
 * @property {string}            [lang]         Language code.
 * @property {(string|Function)} [plural_forms] Plural forms expression or
 *                                              function evaluator.
 */

/**
 * Domain translation pair respectively representing the singular and plural
 * translation.
 *
 * @typedef {[string,string]} TanninTranslation
 */

/**
 * Locale data domain. The key is used as reference for lookup, the value an
 * array of two string entries respectively representing the singular and plural
 * translation.
 *
 * @typedef {{[key:string]:TanninDomainMetadata|TanninTranslation,'':TanninDomainMetadata|TanninTranslation}} TanninLocaleDomain
 */

/**
 * Jed-formatted locale data.
 *
 * @see http://messageformat.github.io/Jed/
 *
 * @typedef {{[domain:string]:TanninLocaleDomain}} TanninLocaleData
 */

/**
 * Default Tannin constructor options.
 *
 * @type {TanninOptions}
 */
var DEFAULT_OPTIONS = {
	contextDelimiter: '\u0004',
	onMissingKey: null,
};

/**
 * Given a specific locale data's config `plural_forms` value, returns the
 * expression.
 *
 * @example
 *
 * ```
 * getPluralExpression( 'nplurals=2; plural=(n != 1);' ) === '(n != 1)'
 * ```
 *
 * @param {string} pf Locale data plural forms.
 *
 * @return {string} Plural forms expression.
 */
function getPluralExpression( pf ) {
	var parts, i, part;

	parts = pf.split( ';' );

	for ( i = 0; i < parts.length; i++ ) {
		part = parts[ i ].trim();
		if ( part.indexOf( 'plural=' ) === 0 ) {
			return part.substr( 7 );
		}
	}
}

/**
 * Tannin constructor.
 *
 * @class
 *
 * @param {TanninLocaleData} data      Jed-formatted locale data.
 * @param {TanninOptions}    [options] Tannin options.
 */
function Tannin( data, options ) {
	var key;

	/**
	 * Jed-formatted locale data.
	 *
	 * @name Tannin#data
	 * @type {TanninLocaleData}
	 */
	this.data = data;

	/**
	 * Plural forms function cache, keyed by plural forms string.
	 *
	 * @name Tannin#pluralForms
	 * @type {Object<string,Function>}
	 */
	this.pluralForms = {};

	/**
	 * Effective options for instance, including defaults.
	 *
	 * @name Tannin#options
	 * @type {TanninOptions}
	 */
	this.options = {};

	for ( key in DEFAULT_OPTIONS ) {
		this.options[ key ] = options !== undefined && key in options
			? options[ key ]
			: DEFAULT_OPTIONS[ key ];
	}
}

/**
 * Returns the plural form index for the given domain and value.
 *
 * @param {string} domain Domain on which to calculate plural form.
 * @param {number} n      Value for which plural form is to be calculated.
 *
 * @return {number} Plural form index.
 */
Tannin.prototype.getPluralForm = function( domain, n ) {
	var getPluralForm = this.pluralForms[ domain ],
		config, plural, pf;

	if ( ! getPluralForm ) {
		config = this.data[ domain ][ '' ];

		pf = (
			config[ 'Plural-Forms' ] ||
			config[ 'plural-forms' ] ||
			// Ignore reason: As known, there's no way to document the empty
			// string property on a key to guarantee this as metadata.
			// @ts-ignore
			config.plural_forms
		);

		if ( typeof pf !== 'function' ) {
			plural = getPluralExpression(
				config[ 'Plural-Forms' ] ||
				config[ 'plural-forms' ] ||
				// Ignore reason: As known, there's no way to document the empty
				// string property on a key to guarantee this as metadata.
				// @ts-ignore
				config.plural_forms
			);

			pf = (0,_tannin_plural_forms__WEBPACK_IMPORTED_MODULE_0__["default"])( plural );
		}

		getPluralForm = this.pluralForms[ domain ] = pf;
	}

	return getPluralForm( n );
};

/**
 * Translate a string.
 *
 * @param {string}      domain   Translation domain.
 * @param {string|void} context  Context distinguishing terms of the same name.
 * @param {string}      singular Primary key for translation lookup.
 * @param {string=}     plural   Fallback value used for non-zero plural
 *                               form index.
 * @param {number=}     n        Value to use in calculating plural form.
 *
 * @return {string} Translated string.
 */
Tannin.prototype.dcnpgettext = function( domain, context, singular, plural, n ) {
	var index, key, entry;

	if ( n === undefined ) {
		// Default to singular.
		index = 0;
	} else {
		// Find index by evaluating plural form for value.
		index = this.getPluralForm( domain, n );
	}

	key = singular;

	// If provided, context is prepended to key with delimiter.
	if ( context ) {
		key = context + this.options.contextDelimiter + singular;
	}

	entry = this.data[ domain ][ key ];

	// Verify not only that entry exists, but that the intended index is within
	// range and non-empty.
	if ( entry && entry[ index ] ) {
		return entry[ index ];
	}

	if ( this.options.onMissingKey ) {
		this.options.onMissingKey( singular, domain );
	}

	// If entry not found, fall back to singular vs. plural with zero index
	// representing the singular value.
	return index === 0 ? singular : plural;
};


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

"use strict";
module.exports = React;

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
/***/ ((module) => {

"use strict";
module.exports = ReactDOM;

/***/ }),

/***/ "@wordpress/components":
/*!********************************!*\
  !*** external "wp.components" ***!
  \********************************/
/***/ ((module) => {

"use strict";
module.exports = wp.components;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/extends.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/extends.js ***!
  \************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _extends)
/* harmony export */ });
function _extends() {
  return _extends = Object.assign ? Object.assign.bind() : function (n) {
    for (var e = 1; e < arguments.length; e++) {
      var t = arguments[e];
      for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]);
    }
    return n;
  }, _extends.apply(null, arguments);
}


/***/ }),

/***/ "./node_modules/memize/dist/index.js":
/*!*******************************************!*\
  !*** ./node_modules/memize/dist/index.js ***!
  \*******************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ memize)
/* harmony export */ });
/**
 * Memize options object.
 *
 * @typedef MemizeOptions
 *
 * @property {number} [maxSize] Maximum size of the cache.
 */

/**
 * Internal cache entry.
 *
 * @typedef MemizeCacheNode
 *
 * @property {?MemizeCacheNode|undefined} [prev] Previous node.
 * @property {?MemizeCacheNode|undefined} [next] Next node.
 * @property {Array<*>}                   args   Function arguments for cache
 *                                               entry.
 * @property {*}                          val    Function result.
 */

/**
 * Properties of the enhanced function for controlling cache.
 *
 * @typedef MemizeMemoizedFunction
 *
 * @property {()=>void} clear Clear the cache.
 */

/**
 * Accepts a function to be memoized, and returns a new memoized function, with
 * optional options.
 *
 * @template {(...args: any[]) => any} F
 *
 * @param {F}             fn        Function to memoize.
 * @param {MemizeOptions} [options] Options object.
 *
 * @return {((...args: Parameters<F>) => ReturnType<F>) & MemizeMemoizedFunction} Memoized function.
 */
function memize(fn, options) {
	var size = 0;

	/** @type {?MemizeCacheNode|undefined} */
	var head;

	/** @type {?MemizeCacheNode|undefined} */
	var tail;

	options = options || {};

	function memoized(/* ...args */) {
		var node = head,
			len = arguments.length,
			args,
			i;

		searchCache: while (node) {
			// Perform a shallow equality test to confirm that whether the node
			// under test is a candidate for the arguments passed. Two arrays
			// are shallowly equal if their length matches and each entry is
			// strictly equal between the two sets. Avoid abstracting to a
			// function which could incur an arguments leaking deoptimization.

			// Check whether node arguments match arguments length
			if (node.args.length !== arguments.length) {
				node = node.next;
				continue;
			}

			// Check whether node arguments match arguments values
			for (i = 0; i < len; i++) {
				if (node.args[i] !== arguments[i]) {
					node = node.next;
					continue searchCache;
				}
			}

			// At this point we can assume we've found a match

			// Surface matched node to head if not already
			if (node !== head) {
				// As tail, shift to previous. Must only shift if not also
				// head, since if both head and tail, there is no previous.
				if (node === tail) {
					tail = node.prev;
				}

				// Adjust siblings to point to each other. If node was tail,
				// this also handles new tail's empty `next` assignment.
				/** @type {MemizeCacheNode} */ (node.prev).next = node.next;
				if (node.next) {
					node.next.prev = node.prev;
				}

				node.next = head;
				node.prev = null;
				/** @type {MemizeCacheNode} */ (head).prev = node;
				head = node;
			}

			// Return immediately
			return node.val;
		}

		// No cached value found. Continue to insertion phase:

		// Create a copy of arguments (avoid leaking deoptimization)
		args = new Array(len);
		for (i = 0; i < len; i++) {
			args[i] = arguments[i];
		}

		node = {
			args: args,

			// Generate the result from original function
			val: fn.apply(null, args),
		};

		// Don't need to check whether node is already head, since it would
		// have been returned above already if it was

		// Shift existing head down list
		if (head) {
			head.prev = node;
			node.next = head;
		} else {
			// If no head, follows that there's no tail (at initial or reset)
			tail = node;
		}

		// Trim tail if we're reached max size and are pending cache insertion
		if (size === /** @type {MemizeOptions} */ (options).maxSize) {
			tail = /** @type {MemizeCacheNode} */ (tail).prev;
			/** @type {MemizeCacheNode} */ (tail).next = null;
		} else {
			size++;
		}

		head = node;

		return node.val;
	}

	memoized.clear = function () {
		head = null;
		tail = null;
		size = 0;
	};

	// Ignore reason: There's not a clear solution to create an intersection of
	// the function with additional properties, where the goal is to retain the
	// function signature of the incoming argument and add control properties
	// on the return value.

	// @ts-ignore
	return memoized;
}




/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!**************************************************************!*\
  !*** ./reactjs/src/pages/registration_form_builder/index.js ***!
  \**************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Editor */ "./reactjs/src/pages/registration_form_builder/Editor.js");
/* harmony import */ var _assets_scss_FormBuilder_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../assets/scss/FormBuilder.scss */ "./reactjs/src/assets/scss/FormBuilder.scss");
/* harmony import */ var _context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../context/RegistrationFormContext */ "./reactjs/src/context/RegistrationFormContext.js");




const RegistrationEditorBuilder = () => {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_context_RegistrationFormContext__WEBPACK_IMPORTED_MODULE_3__.RegistrationFormContextProvider, null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Editor__WEBPACK_IMPORTED_MODULE_1__["default"], null));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (RegistrationEditorBuilder);
})();

/******/ })()
;
//# sourceMappingURL=whx_regi_builder.js.map