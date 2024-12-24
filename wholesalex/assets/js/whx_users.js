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

/***/ "./reactjs/src/components/Alert.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Alert.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _assets_scss_Common_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../assets/scss/Common.scss */ "./reactjs/src/assets/scss/Common.scss");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");




const Alert = _ref => {
  let {
    title,
    description,
    onClose,
    onConfirm,
    confirmText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('OK', 'wholesalex'),
    cancelText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Cancel', 'wholesalex')
  } = _ref;
  const handleClose = () => {
    if (onClose) {
      onClose();
    }
  };
  const handleConfirm = () => {
    if (onConfirm) {
      onConfirm();
    }
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-alert-popup-overlay"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-alert-popup"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-alert-header"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-alert-title-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    style: {
      lineHeight: 0,
      transform: 'rotate(180deg)',
      display: 'inline-block'
    }
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].information), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, title)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon-cross",
    onClick: handleClose
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-alert-body"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, description)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-alert-footer"
  }, onConfirm && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-btn wsx-btn-sm wsx-bg-approve",
    onClick: handleConfirm
  }, confirmText), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-btn wsx-btn-sm wsx-bg-tertiary",
    onClick: handleClose
  }, cancelText))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Alert);

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

/***/ "./reactjs/src/components/DragDropFileUpload.js":
/*!******************************************************!*\
  !*** ./reactjs/src/components/DragDropFileUpload.js ***!
  \******************************************************/
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
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");




const DragDropFileUpload = _ref => {
  let {
    wrapperClass = "",
    label,
    labelColor,
    labelClass,
    help,
    helpClass,
    onChange,
    name,
    tooltip,
    tooltipPosition,
    allowedTypes = []
  } = _ref;
  const [dragActive, setDragActive] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [fileDetails, setFileDetails] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [errorMessage, setErrorMessage] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const handleDrag = e => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };
  const handleDrop = e => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      validateAndHandleFile(e.dataTransfer.files[0]);
    }
  };
  const handleFileSelect = e => {
    if (e.target.files && e.target.files[0]) {
      validateAndHandleFile(e.target.files[0]);
    }
  };
  const resetFileUpload = () => {
    setFileDetails(null);
    setErrorMessage("");
    onChange(null);
    const fileInput = document.getElementById("wsx_hidden_file_upload");
    if (fileInput) {
      fileInput.value = "";
    }
  };
  const validateAndHandleFile = file => {
    if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
      setErrorMessage( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Invalid file type. Allowed types are: ", "wholesalex")), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "wsx-font-medium wsx-font-16"
      }, allowedTypes.join(", "))));
      return;
    }
    setErrorMessage("");
    const {
      name,
      size,
      type
    } = file;
    const sizeInKB = (size / 1024).toFixed(2) + " KB";
    setFileDetails({
      name,
      size: sizeInKB,
      type
    });
    onChange(file);
  };
  const triggerFileSelect = () => {
    document.getElementById("wsx_hidden_file_upload").click();
  };
  const getFileIcon = type => {
    if (type.startsWith("image/")) return _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].image;
    if (type === "text/csv") return _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].csv;
    if (type === "application/vnd.ms-excel") return _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].excel;
    if (type === "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") return _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].excel;
    if (type === "application/msword" || type === "application/vnd.openxmlformats-officedocument.wordprocessingml.document") return _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].docFile;
    return _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].file;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-drag-drop-file-upload-wrapper ${wrapperClass}`
  }, label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-label wsx-font-18 wsx-font-medium wsx-mb-14 ${tooltip ? "wsx-d-flex" : ""} ${labelColor && `wsx-color-${labelColor}`} ${labelClass}`
  }, label, " ", tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "file",
    id: "wsx_hidden_file_upload",
    name: name,
    style: {
      display: "none"
    },
    onChange: handleFileSelect,
    accept: allowedTypes.join(",")
  }), fileDetails === null ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-drag-drop-file-upload-container ${dragActive ? "active" : ""}`,
    onClick: triggerFileSelect,
    onDragEnter: handleDrag,
    onDragLeave: handleDrag,
    onDragOver: handleDrag,
    onDrop: handleDrop
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].importFile), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-font-16 wsx-font-medium wsx-color-text-medium"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Drag and Drop File Here or', 'wholesalex'), " "), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-color-primary wsx-border-bottom"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Click to upload', 'wholesalex')))), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-drag-drop-file-help wsx-help-message ${helpClass}`
  }, help), errorMessage && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-warning-message wsx-color-warning wsx-d-flex wsx-item-center wsx-gap-4"
  }, errorMessage)) : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-drag-drop-file-upload-container wsx-relative"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon"
  }, getFileIcon(fileDetails.type)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-file-name wsx-mb-4 wsx-font-16 wsx-font-medium wsx-color-text-medium"
  }, fileDetails.name), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-file-size"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('File size: ', 'wholesalex') + fileDetails.size), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon-cross wsx-icon-round wsx-absolute wsx-top-16 wsx-right-16",
    onClick: resetFileUpload
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DragDropFileUpload);

/***/ }),

/***/ "./reactjs/src/components/Dropdown.js":
/*!********************************************!*\
  !*** ./reactjs/src/components/Dropdown.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _OutsideClick__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./OutsideClick */ "./reactjs/src/components/OutsideClick.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_3__);




const Dropdown = props => {
  const [status, setStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const {
    title,
    renderContent,
    className = '',
    labelClassName = '',
    contentClass = '',
    onClickCallback,
    iconName = '',
    iconClass = '',
    noIcon = false,
    iconPosition = 'after',
    iconColor = '',
    iconGap = '8',
    iconRotation = 'full',
    ...rest
  } = props;
  const [contentPosition, setContentPosition] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    top: 0,
    left: 0,
    isAbove: false,
    isRight: false
  });
  const dropdownRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const contentRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const position = iconPosition === 'before' || iconPosition === 'left';
  const Icon = iconName && _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"][iconName] ? _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"][iconName] : false;
  (0,_OutsideClick__WEBPACK_IMPORTED_MODULE_1__["default"])(contentRef, () => {
    setStatus(false);
  });
  const toggleDropdown = () => {
    if (!status) {
      const rect = dropdownRef.current.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const viewportWidth = window.innerWidth;
      setTimeout(() => {
        const contentHeight = contentRef.current ? contentRef.current.getBoundingClientRect().height : 0;
        const contentWidth = contentRef.current ? contentRef.current.getBoundingClientRect().width : 0;
        const isAbove = rect.top - contentHeight > 0 ? rect.bottom + contentHeight > viewportHeight : false;
        const isRight = rect.left - contentWidth > 0 ? rect.left + contentWidth > viewportWidth : false;
        setContentPosition({
          top: isAbove ? rect.top + window.scrollY - contentHeight - 10 : rect.bottom + window.scrollY + 10,
          left: isRight ? rect.right + window.scrollX - contentWidth : rect.left + window.scrollX,
          isAbove,
          isRight
        });
      }, 0);
    }
    setStatus(!status);
  };
  const dropdownContent = status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-dropdown-content-wrapper ${props.padding ? `wsx-p-${props.padding}` : ''} ${contentClass}`,
    ref: contentRef,
    style: {
      position: "absolute",
      zIndex: status ? 999999 : -999999,
      visibility: status ? "visible" : "hidden",
      opacity: status ? 1 : 0,
      transition: "opacity 0.3s ease-in-out",
      top: status ? `${contentPosition.top - 5}px` : 0,
      left: status ? `${contentPosition.left}px` : 0
    }
  }, renderContent());
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-dropdown ${!noIcon && 'wsx-d-flex wsx-item-center'} wsx-gap-${iconGap} ${className} ${status && 'active'}`,
    ref: dropdownRef,
    onClick: e => {
      toggleDropdown();
      if (onClickCallback) onClickCallback(e);
    }
  }, !noIcon && position && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-icon ${iconColor ? `wsx-color-${iconColor}` : ''} ${iconClass}`,
    style: {
      transition: 'transform var(--transition-md)',
      transform: status ? iconRotation == 'full' ? 'rotate(180deg)' : iconRotation == 'half' ? 'rotate(90deg)' : 'rotate(0deg)' : 'rotate(0deg)'
    }
  }, Icon ? Icon : _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].angleDown), title && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-label wsx-mb-0 ${labelClassName} ${status && 'active'}`
  }, title), !noIcon && !position && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-icon ${iconColor ? `wsx-color-${iconColor}` : ''} ${iconClass}`,
    style: {
      transition: 'transform var(--transition-md)',
      transform: status ? iconRotation == 'full' ? 'rotate(180deg)' : iconRotation == 'half' ? 'rotate(90deg)' : 'rotate(0deg)' : 'rotate(0deg)'
    }
  }, Icon ? Icon : _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].angleDown), /*#__PURE__*/react_dom__WEBPACK_IMPORTED_MODULE_3___default().createPortal(dropdownContent, document.body));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Dropdown);

/***/ }),

/***/ "./reactjs/src/components/Header.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Header.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Dropdown__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Dropdown */ "./reactjs/src/components/Dropdown.js");
/* harmony import */ var _contexts_RouterContext__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../contexts/RouterContext */ "./reactjs/src/contexts/RouterContext.js");
/* harmony import */ var _Button_New__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button_New */ "./reactjs/src/components/Button_New.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
// import React, { useRef, useState, useEffect, useLayoutEffect } from "react";
// import Dropdown from "./Dropdown";
// import { Link } from "../contexts/RouterContext";
// import Button_New from "./Button_New";
// import { __ } from '@wordpress/i18n';

// const Header = ({ title, isFrontend }) => {

// 	const menuRef = useRef(null);
// 	const linkRefs = useRef([]);
// 	const [exceedingIndex, setExceedingIndex] = useState(0);

// 	useLayoutEffect(() => {
// 		// let firstLoading = true;
// 		let tempIndex = -1;
//         const calculateWidths = (firstLoading = false) => {
// 			let totalLinkWidth = 0;
// 			if (menuRef.current) {
// 				const menuWidth = menuRef.current.offsetWidth;
// 				if(linkRefs.current) {
// 					for( let index = 0; index < linkRefs.current.length; index++ ) {
// 						const linkRef = linkRefs.current[index];
// 						if (linkRef) {
// 							totalLinkWidth += (linkRef.offsetWidth + 40);
// 							if (tempIndex == -1 && totalLinkWidth > menuWidth || tempIndex != -1  && totalLinkWidth + 64 > menuWidth) {
// 								tempIndex = index;
// 								setExceedingIndex(Math.max(index - 1, 1));
// 								break;
// 								// if ( firstLoading && totalLinkWidth + 114 > menuWidth) {;
// 								// 	tempIndex = index;
// 								// 	setExceedingIndex(Math.max(index - 1, 1));
// 								// 	break;
// 								// } 
// 								// else if ( !firstLoading && totalLinkWidth + 62.33 > menuWidth) {
// 								// 	tempIndex = index;
// 								// 	setExceedingIndex(Math.max(index - 1, 1));
// 								// 	break;
// 								// }
// 							} 
// 							else {
// 								if (index + 1 == linkRefs.current.length -1) {
// 									tempIndex = -1;
// 									setExceedingIndex(0);
// 								}
// 								// setExceedingIndex(Math.max(index, 1));
// 							}
// 							// else if(tempIndex != -1  && totalLinkWidth + 64 > menuWidth ) {
// 							// 	tempIndex = index;
// 							// 	setExceedingIndex(Math.max(index - 1, 1));
// 							// 	break;
// 							// }

// 							// if (tempIndex == -1 && totalLinkWidth + 114 > menuWidth) {
// 							// 	tempIndex = index;
// 							// 	setExceedingIndex(Math.max(index - 1, 1));
// 							// 	break;
// 							// } 

// 							// else if(tempIndex != -1 && totalLinkWidth + 72.33  <= menuWidth) {
// 							// 	tempIndex = -1;
// 							// } else if (tempIndex != -1 && totalLinkWidth + 72.33  > menuWidth) {
// 							// 	tempIndex = index;
// 							// 	setExceedingIndex(Math.max(index - 1, 0));
// 							// }
// 						}
// 					}
// 					// if (firstLoading && totalLinkWidth + 114 <= menuWidth) {
// 					// 	tempIndex = -1;
// 					// 	// setExceedingIndex(0);
// 					// } else if (!firstLoading && totalLinkWidth + 62.33 <= menuWidth) {
// 					// 	tempIndex = -1;
// 					// 	// setExceedingIndex(0);
// 					// }
// 				}
// 			}
// 			// if (tempIndex !== exceedingIndex) {
// 			// 	setExceedingIndex(tempIndex !== -1 ? Math.max(tempIndex - 1, 1) : -1);
// 			// }
// 		};

//         // const handleCalculation = () => {
// 		// 	setTimeout(() => {
// 		// 		calculateWidths();
// 		// 	}, 0);
// 		// };

// 		// document.fonts.ready.then(handleCalculation);

// 		calculateWidths(true);

//         window.addEventListener("resize", calculateWidths);
// 		// window.dispatchEvent(new Event("resize"));
//         return () => window.removeEventListener("resize", calculateWidths);

// 		// const resizeObserver = new ResizeObserver(() => {
// 		// 	calculateWidths();
// 		// });

// 		// if (menuRef.current) {
// 		// 	resizeObserver.observe(menuRef.current);
// 		// }

// 		// return () => {
// 		// 	resizeObserver.disconnect();
// 		// };

// 		// // Debounce calculations to avoid frequent updates
// 		// let resizeTimeout;
// 		// const debouncedCalculateWidths = () => {
// 		// 	clearTimeout(resizeTimeout);
// 		// 	resizeTimeout = setTimeout(() => calculateWidths(), 100);
// 		// };

// 		// // Use ResizeObserver for efficient DOM updates
// 		// const resizeObserver = new ResizeObserver(debouncedCalculateWidths);

// 		// if (menuRef.current) {
// 		// 	resizeObserver.observe(menuRef.current);
// 		// }

// 		// // Initial calculation after fonts and styles are applied
// 		// calculateWidths();

// 		// return () => {
// 		// 	clearTimeout(resizeTimeout); // Clean up timeout
// 		// 	resizeObserver.disconnect(); // Disconnect observer
// 		// };

//     }, []);

// 	useLayoutEffect(() => {
// 		let tempIndex = -1;
// 		const calculateWidths = () => {
// 			let totalLinkWidth = 0;
// 			const menu = document.querySelector(".wsx-menu");
// 			const links = document.querySelectorAll(".wsx-nav-link");

// 			if (menu && links.length) {
// 				const menuWidth = menu.offsetWidth;

// 				links.forEach((link, index) => {
// 					totalLinkWidth += link.offsetWidth + 40;
// 					if (
// 						(tempIndex === -1 && totalLinkWidth > menuWidth) 
// 						// ||
// 						// (tempIndex !== -1 && totalLinkWidth + 64 > menuWidth)
// 					) {
// 						tempIndex = index;
// 						// setExceedingIndex(Math.max(index - 1, 1));
// 					}
// 					else if (tempIndex !== -1 && totalLinkWidth + 64 > menuWidth) {
// 						tempIndex = index;
// 						// setExceedingIndex(Math.max(index - 1, 1));
// 					}
// 					else {
// 						// tempIndex = -1;
// 						// setExceedingIndex(Math.max(index, 1));
// 					}
// 				});
// 			}
// 		};

// 		calculateWidths(true);

// 		window.addEventListener("resize", calculateWidths);
// 		return () => window.removeEventListener("resize", calculateWidths);
// 	}, []);

// 	const menuItems = [
// 		{ to: '/', label: 'Dashboard' },
// 		{ to: '/dynamic-rules', label: 'Dynamic Rules' },
// 		{ to: '/user-role', label: 'User Roles' },
// 		// { to: '/registration', label: 'Registration' },
// 		// { to: '/conversation', label: 'Conversation' },
// 		{ to: '/addons', label: 'Addons' },
// 		{ to: '/users', label: 'Users' },
// 		{ to: '/emails', label: 'Emails' },
// 		{ to: '/settings', label: 'Settings' },
// 		// { to: '/features', label: 'Features' },
// 		// { to: '/license', label: 'License' },
// 		{ to: '/quick-support', label: 'Support' },
// 	];

// 	const [showHelpPopUp, setShowHelpPopUp] = useState(false);
// 	const helpLinks = [
// 		{'iconClass':'dashicons-phone', 'label':'Get Supports','link':'https://getwholesalex.com/contact/?utm_source=wholesalex-menu&utm_medium=features_page-support&utm_campaign=wholesalex-DB'},
// 		{'iconClass':'dashicons-book', 'label':'Getting Started Guide','link':'https://getwholesalex.com/docs/wholesalex/getting-started/?utm_source=wholesalex-menu&utm_medium=features_page-guide&utm_campaign=wholesalex-DB'},
// 		{'iconClass':'dashicons-facebook-alt', 'label':'Join Community','link':'https://www.facebook.com/groups/wholesalexcommunity'},
// 		{'iconClass':'dashicons-book', 'label':'Feature Request','link':'https://getwholesalex.com/roadmap/?utm_source=wholesalex-menu&utm_medium=features_page-feature_request&utm_campaign=wholesalex-DB'},
// 		{'iconClass':'dashicons-youtube', 'label':'Youtube Tutorials','link':'https://www.youtube.com/@WholesaleX'},
// 		{'iconClass':'dashicons-book', 'label':'Documentation','link':'https://getwholesalex.com/documentation/?utm_source=wholesalex-menu&utm_medium=features_page-documentation&utm_campaign=wholesalex-DB'},
// 		{'iconClass':'dashicons-edit', 'label':'What’s New','link':'https://getwholesalex.com/roadmap/?utm_source=wholesalex-menu&utm_medium=features_page-what’s_new&utm_campaign=wholesalex-DB'},
// 	];

// 	const ref = useRef(null);

// 	const sleep =async (ms) =>{
// 		return new Promise((resolve) => setTimeout(resolve, ms));
// 	}

// 	const popupHanlder = (e) => {
// 		if (showHelpPopUp) {
// 			const style = ref?.current?.style;
// 			if (!style) return;
// 			sleep(200).then(() => {
// 				style.transition = "all 0.3s";
// 				style.transform = "translateY(-50%)";
// 				style.opacity = "0";
// 				sleep(200).then(() => {
// 					setShowHelpPopUp(false);
// 				});
// 			});
// 		} else {
// 			setShowHelpPopUp(true);
// 		}
// 	}

//     const renderHelpDropdownContent = () => {
//         return (
//              <ul className="wsx-list wsx-d-flex wsx-flex-column wsx-gap-16">
//                 {helpLinks.map((help)=>{
//                     return (
//                         <li className="wsx-list-item">
//                             <a href={help.link} className="wsx-list-link wsx-d-flex wsx-item-center wsx-gap-8" target='_blank'>
// 								<span
// 									className={`dashicons ${help.iconClass} wsx-list-icon`}
// 								></span>
//                                 <span className="wsx-list-label">
//                                     {help.label}
//                                 </span>
//                             </a>
//                         </li>
//         			);
//                 })}
//             </ul>
//         );
//     }

// 	const headerDropdownContent = () => {
// 		return (
// 			<div className="wsx-height-full wsx-width-70v wsx-width-360" onClick={(e)=> e.stopPropagation()}>
// 				<div className="wsx-logo wsx-justify-end">
// 					<img src={wholesalex?.logo_url} className="wsx-logo" />
// 					{!isFrontend && <span className="wsx-version">{`v${wholesalex.current_version}`}</span> }
// 				</div>
// 				<div className="wsx-menu wsx-flex-column wsx-flex-nowrap wsx-gap-24 wsx-mt-48 wsx-scrollbar">
// 					{menuItems.map((item, index) => (
// 						<Link navLink={true} key={index} to={item.to}>{item.label}</Link>
// 					))}
// 				</div>
// 			</div>
// 		);
// 	};

//     return (
// 		<div className="wsx-header-wrapper">
// 			<div style={{position:'absolute',left:'40%',top: '3%',fontSize: '32px', fontWeight: '900',color: '#0a36dc'}}>{exceedingIndex}</div>
// 			<div className="wsx-header">
// 				<div className="wsx-logo wsx-lg-d-none">
// 					<img src={wholesalex?.logo_url} className="wsx-logo" />
// 					{!isFrontend && <span className="wsx-version">{`v${wholesalex.current_version}`}</span> }
// 				</div>

// 				<div className="wsx-d-none wsx-lg-d-block wsx-icon" style={{width: '40px',marginLeft: '-15px'}}>
// 					<Dropdown 
// 						iconClass='wsx-hamburger-icon'
// 						contentClass = 'wsx-header-side-menu'
// 						iconName='menu'
// 						iconRotation='half'
// 						iconColor='tertiary'
// 						renderContent={headerDropdownContent}
// 					/>
// 				</div>

// 				<div className="wsx-header-content wsx-lg-justify-end">
// 					<div className="wsx-menu wsx-lg-d-none" ref={menuRef}>
// 						{/* {exceedingIndex !== 0 && exceedingIndex !== -1 ?
// 							<> */}
// 								{exceedingIndex != 0 && 
// 									<>
// 										{/* {exceedingIndex == 0 && setExceedingIndex(menuItems.length - 1)} */}
// 										{/* {menuItems.slice(0, exceedingIndex).map((item, index) => {
// 											return <Link ref={(el) => (linkRefs.current[index] = el)} navLink={true} key={index} to={item.to}>{item.label}</Link>
// 										})} */}
// 										{/* <Dropdown
// 											title={__('More', 'wholesalex')}
// 											contentClass="wsx-down-4 wsx-pt-12 wsx-pb-12"
// 											renderContent={() => (
// 												<div className="wsx-d-flex wsx-flex-column wsx-gap-12">
// 													{menuItems.slice(exceedingIndex).map((item, index) => {
// 														return <Link ref={(el) => (linkRefs.current[index] = el)} navLink={true} key={index} to={item.to}>{item.label}</Link>
// 													})} */}
// 													{/* {menuItems.map((item, index) => {
// 														const menuIndex = exceedingIndex + index;
// 														return <Link ref={(el) => (linkRefs.current[menuIndex] = el)} navLink={true} key={index} to={item.to}>{item.label}</Link>
// 													})} */}
// 												{/* </div>
// 											)}
// 										/> */}

// 										{/* <Dropdown
// 											title={__('More', 'wholesalex')}
// 											contentClass="wsx-down-4 wsx-pt-12 wsx-pb-12"
// 											renderContent={() => (
// 												<div className="wsx-d-flex wsx-flex-column wsx-gap-12">
// 													{menuItems.slice(exceedingIndex).map((item, index) => {
// 														return <Link ref={(el) => (linkRefs.current[exceedingIndex + index] = el)} navLink={true} key={index} to={item.to}>{item.label}</Link>
// 														})}
// 												</div>
// 											)}
// 										/> */}

// 										{menuItems.slice(0, exceedingIndex).map((item, index) => {
// 											return <Link navLink={true} key={index} to={item.to}>{item.label}</Link>
// 										})}
// 										<Dropdown
// 											title={__('More', 'wholesalex')}
// 											contentClass="wsx-down-4 wsx-pt-12 wsx-pb-12"
// 											renderContent={() => (
// 												<div className="wsx-d-flex wsx-flex-column wsx-gap-12">
// 													{menuItems.slice(exceedingIndex).map((item, index) => {
// 														return <Link navLink={true} key={index} to={item.to}>{item.label}</Link>
// 														})}
// 												</div>
// 											)}
// 										/>
// 										{menuItems.map((item, index) => (
// 											<Link ref={(el) => (linkRefs.current[index] = el)} isHidden={true} to={item.to}></Link>
// 										))}

// 										{/* <Dropdown
// 											title={__('More', 'wholesalex')}
// 											contentClass="wsx-down-4 wsx-pt-12 wsx-pb-12"
// 											renderContent={() => (
// 												<div className="wsx-d-flex wsx-flex-column wsx-gap-12">
// 													{menuItems.slice(exceedingIndex).map((item, index) => {
// 														const originalIndex = exceedingIndex + index; // Calculate the original index

// 														return (
// 															<Link
// 																ref={(el) => (linkRefs.current[originalIndex] = el)} // Use the original index
// 																navLink={true}
// 																key={originalIndex}
// 																to={item.to}
// 															>
// 																{item.label}
// 															</Link>
// 														);
// 													})}
// 												</div>
// 											)}
// 										/> */}
// 									</>
// 								}
// 								{exceedingIndex == 0 && 
// 									menuItems.map((item, index) => (
// 										<Link ref={(el) => (linkRefs.current[index] = el)} navLink={true} key={index} to={item.to}>{item.label}</Link>
// 									))
// 								}

// 							{/* </>
// 						: 
// 							menuItems.map((item, index) => (
// 								<Link ref={(el) => (linkRefs.current[index] = el)} navLink={true} key={index} to={item.to}>{item.label}</Link>
// 							))
// 						} */}
// 					</div>

// 					<div className="wsx-btn-group">
// 						{!wholesalex?.is_pro_active && <Button_New label="Upgrade to Pro" iconName="growUp" background="secondary" customClass="wsx-text-space-nowrap" />}
// 						{!isFrontend && <Dropdown iconName='help' iconRotation='none' iconColor='tertiary' renderContent={renderHelpDropdownContent} contentClass='wsx-down-4 wsx-right'/> }
// 					</div>
// 				</div>
// 			</div>
// 		</div>
//     );
// }

// export default Header;






const Header = _ref => {
  let {
    title,
    isFrontend
  } = _ref;
  const menuRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const linkRefs = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)([]);
  const [exceedingIndex, setExceedingIndex] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useLayoutEffect)(() => {
    let tempIndex = -1;
    const calculateWidths = () => {
      let totalLinkWidth = 0;
      if (menuRef.current) {
        const menuWidth = menuRef.current.offsetWidth;
        if (linkRefs.current) {
          for (let index = 0; index < linkRefs.current.length; index++) {
            const linkRef = linkRefs.current[index];
            if (linkRef) {
              totalLinkWidth += linkRef.offsetWidth + 40;
              if (tempIndex == -1 && totalLinkWidth > menuWidth || tempIndex != -1 && totalLinkWidth + 64 > menuWidth) {
                tempIndex = index;
                setExceedingIndex(Math.max(index - 1, 1));
                break;
              } else if (index + 1 == linkRefs.current.length - 1) {
                tempIndex = -1;
                setExceedingIndex(0);
              }
            }
          }
        }
      }
    };
    calculateWidths();
    window.addEventListener("resize", calculateWidths);
    return () => window.removeEventListener("resize", calculateWidths);
  }, []);
  const menuItems = [{
    to: '/',
    label: 'Dashboard'
  }, {
    to: '/dynamic-rules',
    label: 'Dynamic Rules'
  }, {
    to: '/user-role',
    label: 'User Roles'
  }, {
    to: '/registration',
    label: 'Registration Form'
  }, {
    to: '/addons',
    label: 'Addons'
  }, {
    to: '/users',
    label: 'Users'
  }, {
    to: '/emails',
    label: 'Emails'
  }, {
    to: '/settings',
    label: 'Settings'
  }, {
    to: '/quick-support',
    label: 'Quick Support'
  }
  // { to: '/conversation', label: 'Conversation' },
  // { to: '/features', label: 'Features' },
  // { to: '/license', label: 'License' },
  ];
  const helpLinks = [{
    'iconClass': 'dashicons-phone',
    'label': 'Get Supports',
    'link': 'https://getwholesalex.com/contact/?utm_source=wholesalex-menu&utm_medium=features_page-support&utm_campaign=wholesalex-DB'
  }, {
    'iconClass': 'dashicons-book',
    'label': 'Getting Started Guide',
    'link': 'https://getwholesalex.com/docs/wholesalex/getting-started/?utm_source=wholesalex-menu&utm_medium=features_page-guide&utm_campaign=wholesalex-DB'
  }, {
    'iconClass': 'dashicons-facebook-alt',
    'label': 'Join Community',
    'link': 'https://www.facebook.com/groups/wholesalexcommunity'
  }, {
    'iconClass': 'dashicons-book',
    'label': 'Feature Request',
    'link': 'https://getwholesalex.com/roadmap/?utm_source=wholesalex-menu&utm_medium=features_page-feature_request&utm_campaign=wholesalex-DB'
  }, {
    'iconClass': 'dashicons-youtube',
    'label': 'Youtube Tutorials',
    'link': 'https://www.youtube.com/@WholesaleX'
  }, {
    'iconClass': 'dashicons-book',
    'label': 'Documentation',
    'link': 'https://getwholesalex.com/documentation/?utm_source=wholesalex-menu&utm_medium=features_page-documentation&utm_campaign=wholesalex-DB'
  }, {
    'iconClass': 'dashicons-edit',
    'label': 'What’s New',
    'link': 'https://getwholesalex.com/roadmap/?utm_source=wholesalex-menu&utm_medium=features_page-what’s_new&utm_campaign=wholesalex-DB'
  }];
  const renderHelpDropdownContent = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("ul", {
      className: "wsx-list wsx-d-flex wsx-flex-column wsx-gap-16"
    }, helpLinks.map(help => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("li", {
        className: "wsx-list-item"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
        href: help.link,
        className: "wsx-list-link wsx-d-flex wsx-item-center wsx-gap-8",
        target: "_blank"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: `dashicons ${help.iconClass} wsx-list-icon`
      }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: "wsx-list-label"
      }, help.label)));
    }));
  };
  const headerDropdownContent = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-height-full wsx-width-70v wsx-width-360",
      onClick: e => e.stopPropagation()
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-logo wsx-justify-end"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
      src: wholesalex?.logo_url,
      className: "wsx-logo"
    }), !isFrontend && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "wsx-version"
    }, `v${wholesalex.current_version}`)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-menu wsx-flex-column wsx-flex-nowrap wsx-gap-24 wsx-mt-48 wsx-scrollbar"
    }, menuItems.map((item, index) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_contexts_RouterContext__WEBPACK_IMPORTED_MODULE_2__.Link, {
      navLink: true,
      key: index,
      to: item.to
    }, item.label))));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-header-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-header"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-logo wsx-lg-d-none"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
    src: wholesalex?.logo_url,
    className: "wsx-logo"
  }), !isFrontend && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-version"
  }, `v${wholesalex.current_version}`)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-none wsx-lg-d-block wsx-icon",
    style: {
      width: '40px',
      marginLeft: '-15px'
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Dropdown__WEBPACK_IMPORTED_MODULE_1__["default"], {
    iconClass: "wsx-hamburger-icon",
    contentClass: "wsx-header-side-menu",
    iconName: "menu",
    iconRotation: "half",
    iconColor: "tertiary",
    renderContent: headerDropdownContent
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-header-content wsx-lg-justify-end"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-menu wsx-lg-d-none",
    ref: menuRef
  }, exceedingIndex != 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, menuItems.slice(0, exceedingIndex).map((item, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_contexts_RouterContext__WEBPACK_IMPORTED_MODULE_2__.Link, {
      ref: el => linkRefs.current[index] = el,
      navLink: true,
      key: index,
      to: item.to
    }, item.label);
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Dropdown__WEBPACK_IMPORTED_MODULE_1__["default"], {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('More', 'wholesalex'),
    contentClass: "wsx-down-4 wsx-pt-12 wsx-pb-12",
    renderContent: () => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-d-flex wsx-flex-column wsx-gap-12"
    }, menuItems.slice(exceedingIndex).map((item, index) => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_contexts_RouterContext__WEBPACK_IMPORTED_MODULE_2__.Link, {
        ref: el => linkRefs.current[exceedingIndex + index] = el,
        navLink: true,
        key: index,
        to: item.to
      }, item.label);
    }))
  })), exceedingIndex == 0 && menuItems.map((item, index) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_contexts_RouterContext__WEBPACK_IMPORTED_MODULE_2__.Link, {
    ref: el => linkRefs.current[index] = el,
    navLink: true,
    key: index,
    to: item.to
  }, item.label))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-btn-group"
  }, !wholesalex?.is_pro_active && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_3__["default"], {
    label: "Upgrade to Pro",
    iconName: "growUp",
    background: "secondary",
    customClass: "wsx-text-space-nowrap"
  }), !isFrontend && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Dropdown__WEBPACK_IMPORTED_MODULE_1__["default"], {
    iconName: "help",
    iconRotation: "none",
    iconColor: "tertiary",
    renderContent: renderHelpDropdownContent,
    contentClass: "wsx-down-4 wsx-right wsx-text-space-nowrap"
  })))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Header);

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

/***/ "./reactjs/src/components/Modal.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Modal.js ***!
  \*****************************************/
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
/* harmony import */ var _Button_New__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button_New */ "./reactjs/src/components/Button_New.js");




const Modal = _ref => {
  let {
    title,
    status,
    setStatus,
    onDelete,
    customClass,
    smallModal
  } = _ref;
  const myRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const [isOpen, setIsOpen] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    setIsOpen(true);
  }, []);
  const handleClose = () => {
    setIsOpen(false);
  };
  const handleClickOutside = e => {
    if (!myRef.current.contains(e.target)) {
      setStatus(false);
    }
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);
  return status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-modal-wrapper ${customClass ? customClass : ''} ${isOpen ? 'open' : ''}`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-modal ${smallModal ? 'wsx-modal-sm' : ''}`,
    tabIndex: "-1",
    "aria-hidden": "true"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: myRef,
    className: "wsx-card"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-header"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-title"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Confirm Delete', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-modal-close",
    onClick: handleClose
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-body"
  }, `${(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Do You Want to delete', 'wholesalex')} ${title ? title : ''}? ${(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Be careful, this procedure is irreversible. Do you want to proceed?', 'wholesalex')}`), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-footer"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_3__["default"], {
    padding: "4px 12px",
    borderColor: "border-primary",
    color: "text-light",
    background: "base1",
    customClass: "wsx-font-14",
    onClick: () => {
      setStatus(false);
    },
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Cancel', 'wholesalex')
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_3__["default"], {
    padding: "4px 12px",
    borderColor: "border-primary",
    background: "negative",
    customClass: "wsx-font-14",
    onClick: e => {
      e.preventDefault();
      onDelete();
    },
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Delete', 'wholesalex')
  })))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Modal);

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

/***/ "./reactjs/src/components/OverlayWindow.js":
/*!*************************************************!*\
  !*** ./reactjs/src/components/OverlayWindow.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _OutsideClick__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./OutsideClick */ "./reactjs/src/components/OutsideClick.js");
/* harmony import */ var _assets_scss_OverlayWindow_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../assets/scss/OverlayWindow.scss */ "./reactjs/src/assets/scss/OverlayWindow.scss");
/* harmony import */ var _Button_New__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button_New */ "./reactjs/src/components/Button_New.js");




const OverlayWindow = _ref => {
  let {
    windowRef,
    heading,
    onClose,
    content
  } = _ref;
  (0,_OutsideClick__WEBPACK_IMPORTED_MODULE_1__["default"])(windowRef, () => {
    onClose();
  });
  return (
    /*#__PURE__*/
    // <div className='wsx-overlay-right-window-wrapper' >
    //     <div className={`wsx-overlay-edit`} ref={windowRef}>
    //         <div className="wsx-overlay-header">
    //             <div className="wsx-overlay-label">{heading}</div>
    //             <span className="dashicons dashicons-no-alt wholesalex_cross_icon wholesalex_icon" onClick={onClose}></span>
    //         </div>
    //         <div className="wsx-overlay-content">
    //             {content()}
    //         </div>
    //     </div>
    // </div>
    react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-side-modal-wrapper"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-side-modal-container",
      ref: windowRef
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-side-menu-body"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-side-menu-header"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      className: "wsx-font-medium wsx-font-16 wsx-color-text-medium"
    }, heading), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_3__["default"]
    // label={fields["_save"]?.label}
    , {
      onClick: onClose
      // background="positive"
      // customClass="wsx-font-14"
      ,
      iconName: "cross",
      color: "tertiary",
      customClass: "wsx-icon-cross"
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-side-menu-content wsx-scrollbar"
    }, content()))))
  );
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (OverlayWindow);

/***/ }),

/***/ "./reactjs/src/components/PopupMenu.js":
/*!*********************************************!*\
  !*** ./reactjs/src/components/PopupMenu.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const PopupMenu = _ref => {
  let {
    label,
    renderContent,
    className,
    status,
    outSideClick,
    whiteListClass = ''
  } = _ref;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex_popup_menu__wrapper"
  }, status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex_popup_menu ${className}`
  }, label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex_popup_menu__label"
  }, label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wholesalex_popup_menu__content"
  }, renderContent())));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PopupMenu);

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

/***/ "./reactjs/src/components/RadioButtons.js":
/*!************************************************!*\
  !*** ./reactjs/src/components/RadioButtons.js ***!
  \************************************************/
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



const RadioButtons = props => {
  let {
    options,
    label,
    labelSpace,
    name,
    defaultValue,
    value,
    onChange,
    required,
    isLabelHide,
    className = '',
    tooltip,
    help,
    helpClass,
    tooltipPosition,
    flexView
  } = props;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-radio-field ${className}`
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-label ${labelSpace ? `wsx-mb-${labelSpace}` : 'wsx-mb-32'} wsx-font-medium wsx-color-text-medium ${tooltip ? 'wsx-d-flex' : ''}`
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-radio-field-content"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-radio-field-options ${flexView ? 'wsx-radio-flex' : ''}`
  }, Object.keys(options).map((option, k) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: k,
    className: "wsx-radio-field-option"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    id: option,
    name: name,
    type: "radio",
    value: option,
    defaultChecked: option === value,
    onChange: onChange
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    htmlFor: option,
    className: "wsx-option-desc"
  }, " ", options[option])))), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-radio-field-help wsx-help-message ${helpClass}`
  }, help)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (RadioButtons);

/***/ }),

/***/ "./reactjs/src/components/Search.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Search.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");


const Search = props => {
  let {
    label,
    type,
    name,
    value,
    required,
    tooltip,
    help,
    helpClass,
    inputClass,
    className = '',
    onChange,
    isDisable,
    placeholder,
    isLabelHide,
    id,
    iconClass,
    iconName,
    iconColor,
    background,
    borderColor,
    maxHeight
  } = props;
  const Icon = iconName && _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"][iconName] ? _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"][iconName] : null;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `${className}`
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wholesalex_field_label wholesalex_search_field__label wholesalex_field_label`
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wholesalex_required required"
  }, "*")), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-wrapper-with-icon ${background ? `wsx-bg-${background}` : ''}`,
    style: {
      borderColor: borderColor ? borderColor : 'unset',
      maxHeight: maxHeight ? maxHeight : 'unset'
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    key: `${name}}`,
    id: id || name,
    name: name,
    type: type,
    value: value,
    onChange: onChange,
    disabled: isDisable ? true : false,
    placeholder: placeholder,
    required: required,
    className: inputClass
  }), Icon && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-lh-0 wsx-icon wsx-icon-right",
    style: {
      color: iconColor ? iconColor : 'unset',
      borderColor: borderColor ? borderColor : 'unset'
    }
  }, Icon), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-search-help wsx-help-message ${helpClass}`
  }, help)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Search);

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

/***/ "./reactjs/src/components/SelectWithOptGroup.js":
/*!******************************************************!*\
  !*** ./reactjs/src/components/SelectWithOptGroup.js ***!
  \******************************************************/
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




const SelectWithOptGroup = props => {
  let {
    optionGroup,
    label,
    labelColor,
    name,
    defaultValue,
    value,
    setValue,
    tooltip,
    tooltipPosition,
    help,
    onChange,
    helpClass,
    className,
    selectClass,
    isLabelHide,
    required,
    requiredColor,
    ...rest
  } = props;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-wrapper  ${className}`
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-input-label wsx-font-medium ${labelColor && `wsx-color-${labelColor}`}`
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
    onChange: onChange,
    className: selectClass
  }, rest), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("option", {
    value: ""
  }, props.placeholderOptionLabel), Object.keys(optionGroup).map((optGroupKey, q) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("optgroup", {
      label: optionGroup[optGroupKey]['label']
    }, Object.keys(optionGroup[optGroupKey]['options']).map((option, k) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("option", {
      value: option
    }, " ", optionGroup[optGroupKey]['options'][option])));
  })), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: `wsx-select-group-help wsx-help-message ${helpClass}`
  }, help)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SelectWithOptGroup);

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

/***/ "./reactjs/src/components/Switch.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Switch.js ***!
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



const Switch = props => {
  let {
    label,
    labelClass,
    name,
    onChange,
    defaultValue,
    isDisable,
    value,
    setValue,
    tooltip,
    tooltipPosition,
    help,
    helpClass,
    desc,
    className,
    required,
    requiredColor,
    isLabelHide,
    inputFieldClass
  } = props;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-switch-field ${className}`
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-input-label ${tooltip ? 'wsx-d-flex' : ''} ${labelClass}`
  }, label, " ", required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-switch-field-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    className: "wsx-switch-field-desc wsx-d-flex wsx-item-center wsx-w-fit wsx-curser-pointer",
    htmlFor: name
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-checkbox-option-wrapper",
    style: {
      marginRight: '16px'
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    id: name,
    name: name,
    type: "checkbox",
    defaultChecked: value && value !== 'no' ? true : false,
    onChange: onChange,
    disabled: isDisable,
    className: inputFieldClass ? inputFieldClass : ''
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-checkbox-mark"
  })), desc, " ", props?.descTooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: props?.descTooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help), " "), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: `wsx-switch-help wsx-help-message ${helpClass}`
  }, help)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Switch);

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

/***/ "./reactjs/src/components/UserPagination.js":
/*!**************************************************!*\
  !*** ./reactjs/src/components/UserPagination.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _Button_New__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Button_New */ "./reactjs/src/components/Button_New.js");



const Pagination = _ref => {
  let {
    paginationData,
    fetchData,
    spaceTop = 0,
    spaceBottom = 0,
    spaceRight = 0,
    spaceLeft = 0
  } = _ref;
  const [itemsPerPage, setItemsPerPage] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(10); // Default items per page
  const totalPages = Math.ceil(paginationData.totalUsers / itemsPerPage);
  const currentPage = paginationData.currentPage;
  const handleItemsPerPageChange = e => {
    const newLimit = parseInt(e.target.value);
    setItemsPerPage(newLimit);
    fetchData('get', 1, true, '', newLimit); // Reset to page 1 when items per page changes
  };
  const getPages = () => {
    let pages = [];
    if (totalPages <= 5) {
      for (let i = 1; i <= totalPages; i++) {
        pages.push(i);
      }
    } else {
      if (currentPage <= 3) {
        pages = [1, 2, 3, 4, '...', totalPages];
      } else if (currentPage >= totalPages - 2) {
        pages = [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
      } else {
        pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
      }
    }
    return pages;
  };
  const goToPage = page => {
    if (page !== '...' && page > 0 && page <= totalPages) {
      fetchData('get', page, itemsPerPage);
    }
  };
  const previousPage = () => {
    if (currentPage > 1) {
      fetchData('get', currentPage - 1, itemsPerPage);
    }
  };
  const nextPage = () => {
    if (currentPage < totalPages) {
      fetchData('get', currentPage + 1, itemsPerPage);
    }
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-pagination-wrapper",
    style: {
      margin: `${spaceTop}px ${spaceRight}px ${spaceBottom}px ${spaceLeft}px`
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-pagination-left wsx-btn-group wsx-gap-12"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_2__["default"], {
    iconName: "angleLeft_24",
    borderColor: "tertiary",
    iconColor: "tertiary",
    onClick: previousPage,
    disable: currentPage === 1
  }), getPages().map((page, i) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_2__["default"], {
    borderColor: "border-secondary",
    background: "base1",
    label: page,
    onClick: () => goToPage(page),
    customClass: currentPage == page ? 'active' : '',
    disable: page === '...'
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button_New__WEBPACK_IMPORTED_MODULE_2__["default"], {
    iconName: "angleRight_24",
    borderColor: "tertiary",
    iconColor: "tertiary",
    onClick: nextPage,
    disable: currentPage === totalPages
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-pagination-right wsx-d-flex wsx-item-center wsx-gap-8"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-color-text-light"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Show Result:', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("select", {
    className: "wsx-pagination-option-list",
    value: itemsPerPage,
    onChange: handleItemsPerPageChange
  }, [10, 20, 50, 100].map(pageSize => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("option", {
    className: "wsx-pagination-option",
    value: pageSize,
    key: pageSize
  }, pageSize)))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Pagination);

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

/***/ "./reactjs/src/contexts/RouterContext.js":
/*!***********************************************!*\
  !*** ./reactjs/src/contexts/RouterContext.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Link: () => (/* binding */ Link),
/* harmony export */   Route: () => (/* binding */ Route),
/* harmony export */   RouterProvider: () => (/* binding */ RouterProvider),
/* harmony export */   useNavigate: () => (/* binding */ useNavigate),
/* harmony export */   useRouter: () => (/* binding */ useRouter)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const RouterContext = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.createContext)();
const useRouter = () => (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(RouterContext);

// Custom useNavigate hook
const useNavigate = () => {
  const {
    push
  } = useRouter();
  return hash => {
    push(hash);
  };
};
const RouterProvider = _ref => {
  let {
    children
  } = _ref;
  const [currentHash, setCurrentHash] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(window.location.hash);
  const push = hash => {
    window.location.hash = hash;
  };
  const redirectTo = (hash, time) => {
    setTimeout(() => {
      push(hash);
    }, time);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const handleHashChange = () => {
      setCurrentHash(window.location.hash);
    };
    window.addEventListener('hashchange', handleHashChange);
    return () => window.removeEventListener('hashchange', handleHashChange);
  }, []);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(RouterContext.Provider, {
    value: {
      currentHash,
      push,
      redirectTo
    }
  }, children);
};

// A custom hook to create a link component
const Link = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((_ref2, ref) => {
  let {
    to,
    children,
    className,
    setMenuActive = null,
    navLink,
    isHidden = false
  } = _ref2;
  const {
    push,
    currentHash
  } = useRouter();
  const handleClick = event => {
    event.preventDefault();
    push(to);
  };
  const isActive = (path, currentHash) => {
    const formattedPath = path.replace(/^#/, '');
    const formattedHash = currentHash.replace(/^#/, '');
    const pathParts = formattedPath.split('/');
    const hashParts = formattedHash.split('/');
    if (hashParts[1] == pathParts[1]) {
      return true;
    }
    return false;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
    ref: ref,
    "data-ref": ref,
    href: `#${to}`,
    className: `${navLink ? 'wsx-nav-link' : ''} ${className ? className : ''} ${isActive(to, currentHash) ? 'active' : ''}`,
    onClick: handleClick,
    style: {
      visibility: `${isHidden && 'hidden'}`,
      position: `${isHidden && 'absolute'}`,
      zIndex: `${isHidden && '-1'}`
    }
  }, children);
});
const Route = _ref3 => {
  let {
    path,
    component: Component
  } = _ref3;
  const {
    currentHash
  } = useRouter();

  // Function to match the current hash with the route path
  const match = (routePath, currentHash) => {
    const routeParts = routePath.split('/');
    const currentParts = currentHash.replace(/^#/, '').split('/');
    if (routeParts.length !== currentParts.length) {
      return false;
    }
    const params = {};
    for (let i = 0; i < routeParts.length; i++) {
      if (routeParts[i].startsWith(':')) {
        const paramName = routeParts[i].substring(1);
        params[paramName] = currentParts[i];
      } else if (routeParts[i] !== currentParts[i]) {
        return false;
      }
    }
    return params;
  };
  const params = match(path, currentHash);
  return params ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(Component, params) : null;
};

/***/ }),

/***/ "./reactjs/src/pages/Users/Export.js":
/*!*******************************************!*\
  !*** ./reactjs/src/pages/Users/Export.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_OverlayWindow__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../components/OverlayWindow */ "./reactjs/src/components/OverlayWindow.js");
/* harmony import */ var _components_Switch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../components/Switch */ "./reactjs/src/components/Switch.js");
/* harmony import */ var _components_LoadingSpinner__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/LoadingSpinner */ "./reactjs/src/components/LoadingSpinner.js");
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../context/toastContent */ "./reactjs/src/context/toastContent.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _components_Button_New__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../components/Button_New */ "./reactjs/src/components/Button_New.js");







const Export = _ref => {
  let {
    getFilterStatus,
    getFilterRole,
    getSearchValue,
    getSelectedUserIds,
    windowRef,
    toggleOverlayWindow
  } = _ref;
  const [columns, setColumns] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [selectedColumns, setSelectedColumns] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [loader, setLoader] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const {
    dispatch
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_toastContent__WEBPACK_IMPORTED_MODULE_4__.ToastContext);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // fetchColumns();
    setColumns(wholesalex_overview.whx_users_exportable_columns);
    setSelectedColumns(Object.keys(wholesalex_overview.whx_users_exportable_columns));
  }, []);
  const handleColumnToggle = column => {
    if (selectedColumns.includes(column)) {
      setSelectedColumns(selectedColumns.filter(col => col !== column));
    } else {
      setSelectedColumns([...selectedColumns, column]);
    }
  };
  const handleExport = async () => {
    if (loader) {
      return;
    }
    setLoader(true);
    try {
      const formData = new FormData();
      formData.append("action", "wholesalex_export_users");
      formData.append("columns", JSON.stringify(selectedColumns));
      formData.append("getFilterStatus", getFilterStatus);
      formData.append("getFilterRole", getFilterRole);
      formData.append("getSearchValue", getSearchValue);
      formData.append("getSelectedUserIds", getSelectedUserIds);
      formData.append('nonce', wholesalex.nonce);
      fetch(wholesalex.ajax, {
        method: "POST",
        body: formData
      }).then(res => res.json()).then(res => {
        const fileUrl = window.URL.createObjectURL(new Blob([res.data]));
        const link = document.createElement("a");
        link.href = fileUrl;
        link.setAttribute("download", "wholesalex_users.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setLoader(false);
        dispatch({
          type: "ADD_MESSAGE",
          payload: {
            id: Date.now().toString(),
            type: 'success',
            message: 'Users Export Sucesfull!'
          }
        });
      }).then(err => {});
    } catch (error) {}
  };
  const content = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-pb-100 wsx-mb-100"
    }, loader && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_LoadingSpinner__WEBPACK_IMPORTED_MODULE_3__["default"], null), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-font-16 wsx-font-medium wsx-color-text-dark wsx-mb-24"
    }, wholesalex_overview.i18n.whx_users_select_fields_to_export), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: " wsx-column-2 wsx-sm-column-1 wsx-gap-24 wsx-mb-40"
    }, Object.keys(columns).map(column => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      key: column,
      className: ""
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Switch__WEBPACK_IMPORTED_MODULE_2__["default"], {
      className: 'wsx-d-flex wsx-item-center wsx-justify-start wsx-flex-reverse',
      label: columns[column],
      labelClass: "wsx-mb-0",
      value: selectedColumns.includes(column),
      onChange: () => handleColumnToggle(column)
    })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-help-message wsx-export-user-help wsx-mb-20"
    }, wholesalex_overview.i18n.whx_users_csv_comma_warning), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_6__["default"], {
      label: wholesalex_overview.i18n.whx_users_download_csv,
      onClick: handleExport,
      disable: loader,
      background: "primary"
    }))
    // <div className="wholesalex_user_export">
    //     {loader && (
    //         <LoadingSpinner />
    //     )}
    //     <div className="wholesalex_user_export__heading">{wholesalex_overview.i18n.whx_users_select_fields_to_export}</div>
    //     <div className="wholesalex_user_export__content">
    //         <ul className="wholesalex_user_export__columns">
    //             {Object.keys(columns).map((column) => (
    //                 <li key={column} className="wholesalex_user_export__column">
    // 					<Switch
    // 						className={'wholesalex_user_export__column_switch'}
    // 						label={columns[column]}
    // 						value={selectedColumns.includes(column)}
    // 						onChange={() => handleColumnToggle(column)}
    // 					/>
    //                 </li>
    //             ))}
    //         </ul>
    //         <div class="wholesalex_help_message wholesalex_export_user_help">
    //            {wholesalex_overview.i18n.whx_users_csv_comma_warning}
    //         </div>
    // 		<button className="wholesalex-btn wholesalex-primary-btn wholesalex-btn-lg"  disabled={loader}
    //             onClick={handleExport} >{wholesalex_overview.i18n.whx_users_download_csv}</button>
    //     </div>
    // </div>
    ;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_OverlayWindow__WEBPACK_IMPORTED_MODULE_1__["default"], {
    windowRef: windowRef,
    heading: wholesalex_overview.i18n.whx_users_export_users,
    onClose: toggleOverlayWindow,
    content: content
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Export);

/***/ }),

/***/ "./reactjs/src/pages/Users/Import.js":
/*!*******************************************!*\
  !*** ./reactjs/src/pages/Users/Import.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_OverlayWindow__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../components/OverlayWindow */ "./reactjs/src/components/OverlayWindow.js");
/* harmony import */ var _components_Switch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../components/Switch */ "./reactjs/src/components/Switch.js");
/* harmony import */ var _components_RadioButtons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/RadioButtons */ "./reactjs/src/components/RadioButtons.js");
/* harmony import */ var _components_DragDropFileUpload__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../components/DragDropFileUpload */ "./reactjs/src/components/DragDropFileUpload.js");
/* harmony import */ var _components_Input__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../components/Input */ "./reactjs/src/components/Input.js");
/* harmony import */ var _components_LoadingSpinner__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../components/LoadingSpinner */ "./reactjs/src/components/LoadingSpinner.js");
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../context/toastContent */ "./reactjs/src/context/toastContent.js");
/* harmony import */ var _components_PopupModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../components/PopupModal */ "./reactjs/src/components/PopupModal.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _components_Slider__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../components/Slider */ "./reactjs/src/components/Slider.js");
/* harmony import */ var _components_Button_New__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../components/Button_New */ "./reactjs/src/components/Button_New.js");












const Import = _ref => {
  let {
    windowRef,
    toggleOverlayWindow
  } = _ref;
  const [selectedFile, setSelectedFile] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [completed, setCompleted] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [messages, setMessages] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [loader, setLoader] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [isUpdateExisting, setIsUpdateExisting] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [showPopup, setShowPopUp] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [countData, setCountData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [log, setLog] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [processPerIteration, setProcessPerIteration] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(10);
  const [updateUsername, setUpdateUsername] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [findExistingUser, setFindExistingUser] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('username');
  const {
    state,
    dispatch
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_toastContent__WEBPACK_IMPORTED_MODULE_7__.ToastContext);
  const handleFileChange = e => {
    setSelectedFile(e.target.files[0]);
  };
  const fileUpload = () => {
    if (!selectedFile) {
      dispatch({
        type: "ADD_MESSAGE",
        payload: {
          id: Date.now().toString(),
          type: 'success',
          message: 'Please Select a valid csv file to process import!'
        }
      });
      return;
    }
    if (loader) {
      alert('Please Wait to complete existing import request!');
      return;
    }
    setLoader(true);
    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('action', 'wholesalex_import_users');
    formData.append('update_existing', isUpdateExisting ? 'yes' : 'no');
    formData.append('process_per_iteration', processPerIteration);
    if (isUpdateExisting) {
      formData.append('find_user_by', findExistingUser);
      formData.append('is_update_username', updateUsername ? 'yes' : 'no');
    }
    fetch(wholesalex.ajax, {
      method: 'POST',
      body: formData
    }).then(res => res.json()).then(res => {
      if (res.success) {
        setMessages(res.data);
        if (res?.data?.message) {
          dispatch({
            type: "ADD_MESSAGE",
            payload: {
              id: Date.now().toString(),
              type: 'error',
              message: res.data.message
            }
          });
          setLoader(false);
          return;
        }
        if (res.data.process < res.data.total) {
          //import user
          handleImport();
        } else {
          completeImport(res);
        }
      } else {
        window.alert("Error Occured!");
      }
    });
  };
  const handleImport = async () => {
    const formData = new FormData();
    formData.append('action', 'wholesalex_process_import_users');
    formData.append('update_existing', isUpdateExisting ? 'yes' : 'no');
    formData.append('process_per_iteration', processPerIteration);
    if (isUpdateExisting) {
      formData.append('find_user_by', findExistingUser);
      formData.append('is_update_username', updateUsername);
    }
    fetch(wholesalex.ajax, {
      method: 'POST',
      body: formData
    }).then(res => res.json()).then(res => {
      if (res.success) {
        if (res.data.process < res.data.total) {
          //import user
          handleImport();
        } else {
          completeImport(res);
        }
      } else {
        window.alert("Error Occured!");
      }
    });
  };
  const completeImport = res => {
    setLoader(false);
    let _data = {};
    if (res?.data?.insert_count) {
      _data['insert_count'] = res.data.insert_count;
    }
    if (res?.data?.update_count) {
      _data['update_count'] = res.data.update_count;
    }
    if (res?.data?.skipped_count) {
      _data['skipped_count'] = res.data.skipped_count;
    }
    setCountData(_data);
    if (res?.data?.log) {
      setLog(res.data.log);
      setShowPopUp(true);
    }
  };
  const downloadLog = () => {
    const element = document.createElement("a");
    const file = new Blob([log], {
      type: 'text/plain'
    });
    element.href = URL.createObjectURL(file);
    element.download = `import_log_${Date.now()}`;
    document.body.appendChild(element); // Required for this to work in FireFox
    element.click();
  };
  const content = () => {
    const renderPopupContent = () => {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "success-popup"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "success-popup-content"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        className: "success-icon"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("svg", {
        xmlns: "http://www.w3.org/2000/svg",
        viewBox: "0 0 52 52",
        className: "wholesalex-animation"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("circle", {
        className: "wholesalex__circle",
        cx: "26",
        cy: "26",
        r: "25",
        fill: "none"
      }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("path", {
        className: "wholesalex__check",
        fill: "none",
        d: "M14.1 27.2l7.1 7.2 16.7-16.8"
      }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "message"
      }, "Import Sucessful"), countData.update_count && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "update_message"
      }, " ", countData.update_count, " Users Updated "), countData.insert_count && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "insert_message"
      }, " ", countData.insert_count, " Users Inserted  "), countData.skipped_count && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "skipped_message"
      }, " ", countData.skipped_count, " Users Skipped "), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "log_message"
      }, " ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
        href: "",
        onClick: downloadLog
      }, "Download"), " Log For More Info  "), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("button", {
        className: "close-button",
        onClick: () => {
          setShowPopUp(false);
        }
      }, "Close")));
    };
    const existingUsersOptions = {
      username: 'Username',
      email: 'Email'
    };
    //     return (
    //         <div className="wholesalex_user_import">
    //             {loader && <LoadingSpinner />}
    //             {showPopup &&
    //                 <PopupModal renderContent={renderPopupContent} onClose={() => setShowPopUp(false)} />
    //             }

    //             <div className='wholesalex_user_import__input_file'>

    //                 {<DragDropFileUpload
    //                     name={'input_file'}
    //                     label={__('Upload CSV', 'wholesalex')}
    //                     help={__('You can upload only csv file format', 'wholesalex')}
    //                     onChange={(file) => {
    //                         setSelectedFile(file);
    //                     }}
    //                     allowedTypes={['text/csv']}
    //                 />}
    //             </div>
    //             <Switch value={isUpdateExisting} onChange={() => setIsUpdateExisting(!isUpdateExisting)} label={'Update Existing Users'} help={'Selecting "Update Existing Users" will only update existing users. No new user will be added.'} className={'wholesalex_user_import__update_existing'} />
    //             {isUpdateExisting && <div className='wholesalex_find_existing_users'>

    //                 <RadioButtons
    //                     className="wholesalex_user_import__radio_field"
    //                     label={'Find Existing Users By:'}
    //                     options={existingUsersOptions}
    //                     name={'wholesalex_how_find_existing_users'}
    //                     value={findExistingUser}
    //                     onChange={(e) => {
    //                         setFindExistingUser(e.target.value)
    //                     }}
    //                     defaultValue={'username'}
    //                     help={"Option to detect user from the uploaded CSV's email or username field."}
    //                 />

    //                 <div className='wholesalex_help_message'></div>
    //             </div>}
    //             <Input
    //                 className="wholesalex_import_export_ppi_field"
    //                 type="text"
    //                 label={'Process Per Iteration'}
    //                 name={'process_per_iteration'}
    //                 value={processPerIteration}
    //                 onChange={(e) => setProcessPerIteration(e.target.value)}
    //                 help={"Low process per iteration (PPI) increases the import's accuracy and success rate. A (PPI) higher than your server's maximum execution time might fail the import."}
    //             />
    //             {/* <div className="wholesalex_process_per_iteration">
    //     <label>  Process Per Iteration </label>
    //     <input value={processPerIteration} onChange={(e) => setProcessPerIteration(e.target.value)} className="wholesalex_import_process_per_iteration" type="text" />
    //     <div className="wholesalex_help_message">Low process per iteration (PPI) increases the import's accuracy and success rate. A (PPI) higher than your server's maximum execution time might fail the import.</div>
    //   </div> */}
    //             <button className="wholesalex-btn wholesalex-primary-btn  wholesalex-btn-lg" onClick={fileUpload}>Import</button>
    //         </div>
    //     );
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-d-flex wsx-flex-column wsx-gap-20"
    }, loader && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_LoadingSpinner__WEBPACK_IMPORTED_MODULE_6__["default"], null), showPopup && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_PopupModal__WEBPACK_IMPORTED_MODULE_8__["default"], {
      renderContent: renderPopupContent,
      onClose: () => setShowPopUp(false)
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_DragDropFileUpload__WEBPACK_IMPORTED_MODULE_4__["default"], {
      name: 'input_file',
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Upload CSV', 'wholesalex'),
      help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('You can upload only csv file format', 'wholesalex'),
      onChange: file => {
        setSelectedFile(file);
      },
      allowedTypes: ['text/csv']
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_10__["default"], {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Update Existing Users', 'wholesalex'),
      help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Selecting "Update Existing Users" will only update existing users. No new user will be added.', 'wholesalex'),
      onChange: () => setIsUpdateExisting(!isUpdateExisting),
      value: isUpdateExisting,
      className: "wsx-slider-sm",
      isLabelSide: true
    }), isUpdateExisting && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wholesalex_find_existing_users"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_RadioButtons__WEBPACK_IMPORTED_MODULE_3__["default"]
    // className="wholesalex_user_import__radio_field"
    , {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)('Find Existing Users By:', 'wholesalex'),
      options: existingUsersOptions,
      name: 'wsx_how_find_existing_users',
      value: findExistingUser,
      onChange: e => {
        setFindExistingUser(e.target.value);
      },
      defaultValue: 'username',
      help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_9__.__)("Option to detect user from the uploaded CSV's email or username field.", 'wholesalex'),
      flexView: true,
      labelSpace: "8"
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Input__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "wholesalex_import_export_ppi_field",
      type: "text",
      label: 'Process Per Iteration',
      name: 'process_per_iteration',
      value: processPerIteration,
      onChange: e => setProcessPerIteration(e.target.value),
      help: "Low process per iteration (PPI) increases the import's accuracy and success rate. A (PPI) higher than your server's maximum execution time might fail the import."
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_11__["default"], {
      label: wholesalex_overview.i18n.whx_users_import,
      background: "primary",
      customClass: "wsx-center-hz wsx-w-half wsx-text-center wsx-mt-20",
      onClick: fileUpload
    }));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_OverlayWindow__WEBPACK_IMPORTED_MODULE_1__["default"], {
    windowRef: windowRef,
    heading: 'Import Users',
    onClose: toggleOverlayWindow,
    content: content
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Import);

/***/ }),

/***/ "./reactjs/src/pages/Users/Users.js":
/*!******************************************!*\
  !*** ./reactjs/src/pages/Users/Users.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_Select__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../components/Select */ "./reactjs/src/components/Select.js");
/* harmony import */ var _components_Search__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../components/Search */ "./reactjs/src/components/Search.js");
/* harmony import */ var _components_PopupMenu__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/PopupMenu */ "./reactjs/src/components/PopupMenu.js");
/* harmony import */ var _components_Slider__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../components/Slider */ "./reactjs/src/components/Slider.js");
/* harmony import */ var _Import__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Import */ "./reactjs/src/pages/Users/Import.js");
/* harmony import */ var _Export__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Export */ "./reactjs/src/pages/Users/Export.js");
/* harmony import */ var _components_Dropdown__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../components/Dropdown */ "./reactjs/src/components/Dropdown.js");
/* harmony import */ var _components_Modal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../components/Modal */ "./reactjs/src/components/Modal.js");
/* harmony import */ var _components_PopupModal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../components/PopupModal */ "./reactjs/src/components/PopupModal.js");
/* harmony import */ var _components_Toast__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../components/Toast */ "./reactjs/src/components/Toast.js");
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../context/toastContent */ "./reactjs/src/context/toastContent.js");
/* harmony import */ var _components_SelectWithOptGroup__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../components/SelectWithOptGroup */ "./reactjs/src/components/SelectWithOptGroup.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @wordpress/i18n */ "./node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _components_Button_New__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../../components/Button_New */ "./reactjs/src/components/Button_New.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _components_UserPagination__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../../components/UserPagination */ "./reactjs/src/components/UserPagination.js");
/* harmony import */ var _components_Alert__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../../components/Alert */ "./reactjs/src/components/Alert.js");


















const Users = () => {
  const [initialLoader, setInitialLoader] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [actionData, setActionData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [users, setUsers] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [paginationData, setPaginationData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    totalUsers: 0,
    currentPage: 1,
    loader: false
  });
  const [alert, setAlert] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [isAlertVisible, setIsAlertVisible] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [pendingAction, setPendingAction] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [bulkAction, setBulkAction] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const [selectedUsers, setSelectedUsers] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [filterStatus, setFilteStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const [filterRole, setFilterRole] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const [seachValue, setSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)("");
  const [tempSearchValue, setTempSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(seachValue);
  const [isInitalRender, setIsInitialRender] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [columns, setColumns] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(wholesalex_overview?.whx_users_heading);
  const [showColumnsSelection, setShowColumnSelection] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [overlayWindow, setOverlayWindow] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    status: false,
    type: ''
  });
  const [currentUser, setCurrentUser] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [deleteModalStatus, setDeleteModalStatus] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const {
    state,
    dispatch
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useContext)(_context_toastContent__WEBPACK_IMPORTED_MODULE_11__.ToastContext);
  const [isBulkAction, setIsBulkAction] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [transactionFields, setTransactionFields] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [isPopupOpen, setIsPopupOpen] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [isLoading, setIsLoading] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const abortController = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  // const [allSelect,setAllSelect] = useState(false);
  // const columns = wholesalex_overview?.heading;

  const fetchData = async function () {
    let type = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "get";
    let page = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
    let isInital = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
    let message = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : '';
    let itemsPerPage = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 10;
    if (isInital) {
      setInitialLoader(true);
    } else {
      setPaginationData({
        ...paginationData,
        loader: true
      });
    }
    let attr = {
      type: type,
      action: "users",
      nonce: wholesalex.nonce,
      page: page ? page : 1,
      search: seachValue,
      itemsPerPage: itemsPerPage // Add itemsPerPage to request data
    };
    if (filterStatus) {
      attr['status'] = filterStatus;
    }
    if (filterRole) {
      attr['role'] = filterRole;
    }
    wp.apiFetch({
      path: "/wholesalex/v1/users",
      method: "POST",
      data: attr
    }).then(res => {
      if (res.status) {
        setUsers(res.data.users);
        setPaginationData({
          ...paginationData,
          totalUsers: res.data.total_users,
          loader: false,
          currentPage: page
        });
        setSelectedUsers([]);
        if (message) {
          dispatch({
            type: "ADD_MESSAGE",
            payload: {
              id: Date.now().toString(),
              type: 'success',
              message: message
            }
          });
        }
      }
      if (isInital) {
        setInitialLoader(false);
      }
    });
  };
  const updateStatus = async function () {
    let type = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "get";
    let page = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
    let user_action = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    let ids = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : '';
    setPaginationData({
      ...paginationData,
      loader: true
    });
    let attr = {
      type: type,
      action: "users",
      nonce: wholesalex.nonce,
      page: page ? page : 1,
      search: seachValue
    };
    if (filterStatus) {
      attr['status'] = filterStatus;
    }
    if (filterRole) {
      attr['role'] = filterRole;
    }
    switch (type) {
      case 'update_status':
        attr['user_action'] = user_action;
        attr['id'] = ids;
        break;
      case 'bulk_action':
        attr['user_action'] = user_action;
        attr['ids'] = ids;
        break;
      default:
        break;
    }
    wp.apiFetch({
      path: "/wholesalex/v1/users",
      method: "POST",
      data: attr
    }).then(res => {
      if (res.status) {
        fetchData('get', paginationData.currentPage, false, res.data);
      }
    });
  };
  const hasPagination = () => {
    return wholesalex_overview.whx_users_user_per_page <= paginationData.totalUsers;
  };
  const getTotalPage = () => {
    return Math.ceil(paginationData.totalUsers / wholesalex_overview.whx_users_user_per_page);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // fetchData();
    setIsInitialRender(true);
  }, []);
  const closeRowActionPopup = (index, user) => {
    if (actionData['index'] === index) {
      setActionData({
        status: false,
        index: '',
        user: ''
      });
    } else {
      setActionData({
        status: true,
        index: index,
        user: user
      });
    }
  };

  // const renderDropDownContent = () => {
  //     return (
  //         <div  className="wholesalex_popup_menu__wrapper">
  //             <div className="wholesalex_popup_menu__content">
  //                 {rowActions()}
  //             </div>
  //     	</div>
  //     );
  // }

  const handleTransactionHistory = user_id => {
    setIsPopupOpen(true);
    setIsLoading(true);
    const data = new FormData();
    data.append('action', 'get_user_transactions');
    data.append('user_id', user_id);
    fetch(ajaxurl, {
      method: 'POST',
      body: data
    }).then(response => response.json()).then(result => {
      if (result.success) {
        setTransactionFields(result.data);
      } else {
        setTransactionFields([]);
      }
    }).catch(error => {
      setTransactionFields([]);
    }).finally(() => {
      setIsLoading(false);
    });
  };
  const handleTogglePopup = () => {
    setIsPopupOpen(!isPopupOpen);
    setTransactionFields([]);
  };

  //Transaction MArkup 
  const generateTransactionTable = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, isPopupOpen && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-popup-overlay wsx-transaction-popup",
      onClick: handleTogglePopup
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-card",
      onClick: e => e.stopPropagation()
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-lh-0 wsx-icon",
      onClick: handleTogglePopup
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_15__["default"].cross), isLoading ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, "Loading transactions...") : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, !transactionFields || transactionFields.length === 0 ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, "No transactions available.") : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-lists-table-wrapper wsx-scrollbar"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("table", {
      className: "wsx-lists-table wsx-w-full wsx-shadow-none"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("thead", {
      className: "wsx-lists-table-header wsx-bg-text-medium"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
      className: "wsx-lists-table-header-row wsx-color-text-reverse"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, "Transactions ID"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, "Type"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, "Amount"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, "Date"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, "Details"))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tbody", {
      className: "wsx-lists-table-body"
    }, transactionFields.map((transaction, i) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
      key: `wallet_transaction_${i}`
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-transaction-id wsx-lists-table-column"
    }, transaction._id), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-transaction-type wsx-lists-table-column"
    }, transaction.type), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-amount wsx-lists-table-column"
    }, wholesalex_overview.whx_dr_currency + (transaction.amount !== undefined && transaction.amount !== null ? parseFloat(transaction.amount).toFixed(2) : "0.00")), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-date wsx-lists-table-column"
    }, new Date(parseInt(transaction.timestamp) * 1000).toDateString().split(' ').slice(1).join(' ')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-details wsx-lists-table-column"
    }, transaction.reason))))))))));
  };
  const renderTableCell = function (data, fieldType, name) {
    let user = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : '';
    let index = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : '';
    if (fieldType === "3dot") {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
        className: `wsx-lists-table-column-${name} wsx-lists-table-column`
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Dropdown__WEBPACK_IMPORTED_MODULE_7__["default"], {
        className: "wsx-3dot-wrapper"
        // contentClass='wsx-p-4 wsx-down-60p wsx-start-4'
        ,
        renderContent: renderDropDownContent,
        padding: "4",
        onClickCallback: e => {
          setCurrentUser(user);
        },
        iconName: "dot3",
        iconRotation: "none"
      }));
    } else if (fieldType === "html") {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
        className: `wsx-lists-table-column-${name} wsx-lists-table-column`,
        dangerouslySetInnerHTML: {
          __html: data
        }
      });
    } else if (fieldType === "btn") {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
        className: `wsx-lists-table-column-${name} wsx-lists-table-column`
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
        onClick: () => handleTransactionHistory(user.ID),
        className: "wsx-btn wsx-btn-border wsx-btn-border-primary wsx-btn-sm"
      }, "View"));
    } else {
      if (name == 'username') {
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
          className: `wsx-lists-table-column-${name} wsx-lists-table-column`
        }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
          target: "_blank",
          href: user?.edit_profile
        }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
          className: "wsx-ellipsis"
        }, data)));
      } else {
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
          className: `wsx-lists-table-column-${name} wsx-lists-table-column`
        }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
          className: `${Number.isInteger(data) ? '' : 'wsx-ellipsis'}`
        }, data));
      }
    }
  };
  const previousPage = () => {
    let currentPage = paginationData.currentPage;
    if (currentPage - 1 > 0) {
      fetchData('get', currentPage - 1, false);
    }
  };
  const nextPage = () => {
    let totalPage = getTotalPage();
    let currentPage = paginationData.currentPage;
    if (currentPage + 1 <= totalPage) {
      fetchData('get', currentPage + 1, false);
    }
  };
  const updateAllUserSelection = () => {
    if (selectedUsers.length === users.length) {
      setSelectedUsers([]);
    } else {
      let tempUsers = [...users];
      let _selectedUsers = [];
      tempUsers.map(user => {
        _selectedUsers.push(user["ID"]);
      });
      setSelectedUsers(_selectedUsers);
    }
  };
  const selectUser = e => {
    let user_id = Number(e.target.getAttribute("data-id"));
    if (selectedUsers.includes(user_id)) {
      const _selectedUsers = selectedUsers.filter(id => id !== user_id);
      setSelectedUsers(_selectedUsers);
    } else {
      setSelectedUsers([...selectedUsers, user_id]);
    }
  };
  const onBulkActionChange = e => {
    setBulkAction(e.target.value);
  };
  const searchUser = () => {
    fetchData('get', 1, false);
  };

  // Fetch data on component load
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    wp.apiFetch({
      path: "/wholesalex/v1/get-users-filters"
    }).then(response => {
      const updatedColumns = {
        ...columns
      };
      Object.keys(response.filters).forEach(key => {
        const columnKey = key.replace("users_filter_", ""); // Remove the prefix
        if (updatedColumns[columnKey]) {
          updatedColumns[columnKey].status = response.filters[key] == true; // Convert "yes"/"no" to boolean
        }
      });
      setColumns(updatedColumns);
    }).catch(error => {});
  }, []);

  // Function to update a specific column's status
  const setColumnStatus = (columnName, status) => {
    let _columns = {
      ...columns
    };
    let _column = {
      ..._columns[columnName],
      status: status
    };
    _columns[columnName] = _column;
    setColumns(_columns);
  };

  // Function to save the updated column status to the backend
  const handleSaveData = (filterKey, column) => {
    wp.apiFetch({
      path: "/wholesalex/v1/save-users-filters",
      method: "POST",
      data: {
        filters: {
          // Wrap the filters in a "filters" key
          [filterKey]: !columns[column].status // Ensure the key is correctly named
        }
      }
    }).then(response => {}).catch(error => {});
  };
  const columnsSelectionContent = () => {
    return Object.keys(columns).map(column => {
      const filterKey = `users_filter_${column}`; // Match backend key format

      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "wsx-dropdown-actions-list",
        onClick: e => e.stopPropagation(),
        key: filterKey
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Slider__WEBPACK_IMPORTED_MODULE_4__["default"], {
        label: columns[column].title,
        name: `wsx-overview-column-${column}`,
        value: columns[column].status,
        isLabelSide: true,
        className: "wsx-slider-md",
        onChange: e => {
          e.stopPropagation();
          setColumnStatus(column, !columns[column]['status']);
          handleSaveData(filterKey, column);
        }
      }));
    });
  };
  const onFilterStatusChange = e => {
    setFilteStatus(e.target.value);
  };
  const onFilterRoleChange = e => {
    setFilterRole(e.target.value);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchData('get', 1, false);
  }, [filterStatus]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchData('get', 1, false);
  }, [filterRole]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (users.length === 0 && seachValue == '' && !isInitalRender) {
      fetchData();
    } else {
      searchUser(seachValue);
    }
  }, [seachValue]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const delay = setTimeout(() => setSearchValue(tempSearchValue), 500);
    return () => clearTimeout(delay);
  }, [tempSearchValue]);
  const rowActionHandler = (action, id) => {
    switch (action) {
      case 'edit':
        window.open(currentUser.edit_profile, '_blank');
        break;
      case 'delete':
        setDeleteModalStatus(true);
        break;
      case 'active':
      case 'reject':
      case 'pending':
        updateStatus('update_status', paginationData.currentPage, action, id);
        break;
      default:
        break;
    }
  };
  const renderDropDownContent = () => {
    const _isPending = currentUser?.wholesalex_status === 'pending';
    const options = [{
      label: wholesalex_overview.i18n.whx_users_edit,
      iconClass: 'edit',
      action: 'edit',
      url: ''
    }, _isPending ? {
      label: wholesalex_overview.i18n.whx_users_active,
      iconClass: 'tick',
      action: 'active',
      url: ''
    } : '', _isPending ? {
      label: wholesalex_overview.i18n.whx_users_reject,
      iconClass: 'cross',
      action: 'reject',
      url: ''
    } : '', !_isPending ? {
      label: wholesalex_overview.i18n.whx_users_pending,
      iconClass: 'dot3',
      action: 'pending',
      url: ''
    } : '', {
      label: wholesalex_overview.i18n.whx_users_delete,
      iconClass: 'delete',
      action: 'delete',
      url: ''
    }];
    return (
      // <ul className="wsx-row-actions">
      // {
      options.map(option => {
        return option && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
          className: "wsx-row-actions-list",
          onClick: e => {
            rowActionHandler(option?.action, currentUser.ID);
          }
        }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
          className: "wsx-icon wsx-lh-0"
        }, _utils_Icons__WEBPACK_IMPORTED_MODULE_15__["default"][option.iconClass]), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
          className: "wsx-row-actions-list-link"
        }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
          className: "wsx-row-actions-list-link-label"
        }, option.label)));
      })
      // }
      // </ul>
    );
  };
  const columnsOutsideClickHandler = () => {
    if (showColumnsSelection) {
      setShowColumnSelection(false);
    }
  };
  const ref = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const sleep = async ms => {
    return new Promise(resolve => setTimeout(resolve, ms));
  };
  const toggleOverlayWindow = (e, type) => {
    if (overlayWindow.status) {
      const style = ref?.current?.style;
      if (!style) return;
      sleep(200).then(() => {
        style.transition = "all 0.3s ease-in-out";
        style.transform = "translateX(50%)";
        style.opacity = "0";
        sleep(300).then(() => {
          setOverlayWindow({
            ...overlayWindow,
            status: false
          });
        });
      });
    } else {
      setOverlayWindow({
        ...overlayWindow,
        type: type,
        status: true
      });
    }
  };
  const onImportClick = e => {
    toggleOverlayWindow(e, 'import');
  };
  const onExportClick = () => {
    toggleOverlayWindow(null, 'export');
  };
  const renderOverlayWindow = () => {
    if (!overlayWindow.status) {
      return;
    }
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, overlayWindow.type == 'import' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Import__WEBPACK_IMPORTED_MODULE_5__["default"], {
      toggleOverlayWindow: toggleOverlayWindow,
      overlayWindowStatus: overlayWindow.status,
      windowRef: ref
    }), overlayWindow.type == 'export' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Export__WEBPACK_IMPORTED_MODULE_6__["default"], {
      getFilterStatus: filterStatus,
      getFilterRole: filterRole,
      getSearchValue: seachValue,
      getSelectedUserIds: selectedUsers,
      toggleOverlayWindow: toggleOverlayWindow,
      overlayWindowStatus: overlayWindow.status,
      windowRef: ref
    }));
  };
  const deleteModal = () => {
    return deleteModalStatus && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Modal__WEBPACK_IMPORTED_MODULE_8__["default"], {
      title: isBulkAction ? wholesalex_overview.i18n.whx_users_selected_users : currentUser.full_name,
      status: deleteModalStatus,
      setStatus: setDeleteModalStatus,
      onDelete: () => {
        setDeleteModalStatus(false);
        updateStatus(isBulkAction ? 'bulk_action' : 'update_status', paginationData.currentPage, 'delete', isBulkAction ? selectedUsers : currentUser.ID);
      }
    })
    // deleteModalStatus && <PopupModal renderContent={renderContent} onClose={()=> setDeleteModalStatus(false)}  />
    ;
  };
  const handleBulkAction = () => {
    if (!bulkAction || bulkAction === 'default' || bulkAction === '' || selectedUsers.length === 0) {
      setAlert(true);
      return;
    }
    setPendingAction(bulkAction);
    setIsAlertVisible(true);
  };
  const confirmBulkAction = () => {
    setIsBulkAction(true);
    if ('export' === bulkAction) {
      onExportClick();
    } else if ('delete' === bulkAction) {
      setDeleteModalStatus(true);
    } else {
      updateStatus('bulk_action', paginationData.currentPage, bulkAction, selectedUsers);
    }
    setIsAlertVisible(false);
    setSelectedUsers([]);
  };
  const handleAlertClose = () => {
    setIsAlertVisible(false);
    setPendingAction(null);
    setAlert(false);
  };
  const handleEmptyBulkAction = () => {
    alert('Select an action first');
  };

  // const headerItems = () => {
  // 	{Object.keys(columns).map((heading, index) => {
  // 		return columns[heading].status && <th key={index} className={`wsx-lists-table-column-${columns[heading].name} wsx-lists-table-column`}> {columns[heading].title} </th>
  // 	})}
  // }
  // const userHeadingList = Object.keys(columns).map((heading) => {
  // 	return { title: columns[heading].title }
  // }) || [];
  const userHeadingList = Object.keys(columns).map(heading => {
    return heading == 'user_id' ? {
      title: columns[heading].title,
      withCheckbox: true
    } : {
      title: columns[heading].title
    };
  }) || [];
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-user-list-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-justify-wrapper wsx-slg-justify-wrapper wsx-gap-12 wsx-mb-24"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-gap-32 wsx-item-center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-gap-8 wsx-item-center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_SelectWithOptGroup__WEBPACK_IMPORTED_MODULE_12__["default"], {
    selectClass: "wsx-ellipsis",
    placeholderOptionLabel: 'Bulk Actions',
    optionGroup: wholesalex_overview.whx_users_bulk_actions,
    name: "wsx-lists-bulk-action",
    value: bulkAction,
    onChange: onBulkActionChange
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_14__["default"], {
    label: wholesalex_overview.i18n.whx_users_apply
    // onClick={bulkAction != "" ? handleBulkAction : handleEmptyBulkAction}
    ,
    onClick: handleBulkAction,
    background: "base2",
    borderColor: "primary",
    color: "primary",
    customClass: "wsx-font-14",
    padding: "11px 20px"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-gap-8 wsx-item-center wsx-user-select-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Select__WEBPACK_IMPORTED_MODULE_1__["default"], {
    selectClass: "wsx-ellipsis",
    options: wholesalex_overview.whx_users_statuses,
    name: "wsx-lists-filter-user-status",
    value: filterStatus,
    onChange: onFilterStatusChange
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Select__WEBPACK_IMPORTED_MODULE_1__["default"], {
    selectClass: "wsx-ellipsis",
    options: wholesalex_overview.whx_users_roles,
    name: "wsx-lists-filter-user-status",
    value: filterRole,
    onChange: onFilterRoleChange
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-d-flex wsx-gap-8 wsx-item-center"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Button_New__WEBPACK_IMPORTED_MODULE_14__["default"], {
    label: wholesalex_overview.i18n.whx_users_import
    // padding='8px 20px'
    ,
    background: "base2",
    iconName: "import",
    onClick: onImportClick,
    customClass: "wsx-font-regular"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Search__WEBPACK_IMPORTED_MODULE_2__["default"]
  // inputClass='wsx-input-wrapper-with-icon'
  , {
    type: "text",
    name: "wsx-lists-search-user",
    value: tempSearchValue,
    onChange: e => setTempSearchValue(e.target.value),
    iconName: "search",
    iconColor: "#070707",
    background: "transparent",
    borderColor: "#868393",
    maxHeight: "38px",
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_13__.__)('Search...', 'wholesalex')
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Dropdown__WEBPACK_IMPORTED_MODULE_7__["default"]
  // title={<>
  // 	<span className="dashicons dashicons-columns wholesalex_icon wholesalex_columns_icon"></span>
  // 	<span className="wholesalex_button_text">{wholesalex_overview.i18n.whx_users_columns}</span>
  // </>}
  // title = {<div className="wsx-lh-0">{Icons.menu}</div>}
  // labelClassName='wsx-btn wsx-bg-base2 wsx-border-default wsx-bc-primary wsx-p-10 wsx-mb-0 wsx-color-blue-dark'
  , {
    className: "wsx-relative wsx-bg-base2 wsx-border-default wsx-bc-primary wsx-p-10 wsx-mb-0 wsx-btn"
    // contentClass='wsx-right wsx-down-4'
    ,
    iconName: "menu",
    iconRotation: "half",
    renderContent: columnsSelectionContent
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-lists-table-wrapper wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("table", {
    className: "wsx-lists-table wsx-w-full"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("thead", {
    className: "wsx-lists-table-header"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-header-row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("th", {
    className: "wsx-checkbox-column wsx-lists-table-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    className: "wsx-checkbox-option-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "checkbox",
    checked: selectedUsers.length === users.length && users.length > 0,
    onChange: updateAllUserSelection
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-checkbox-mark"
  }))), Object.keys(columns).map((heading, index) => {
    return columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("th", {
      key: index,
      className: `wsx-lists-table-column-${columns[heading].name} wsx-lists-table-column`
    }, columns[heading].title);
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tbody", {
    className: "wsx-lists-table-body"
  }, initialLoader && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    className: `wsx-lists-table-column`
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-skeleton-loader"
  })), columns && Object.keys(columns).map(heading => {
    return columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: " wsx-skeleton-loader"
    }));
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    className: "wsx-lists-table-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-skeleton-loader"
  })), columns && Object.keys(columns).map(heading => {
    return columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: " wsx-skeleton-loader"
    }));
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    className: "wsx-lists-table-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-skeleton-loader"
  })), columns && Object.keys(columns).map(heading => {
    return columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: " wsx-skeleton-loader"
    }));
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    className: "wsx-lists-table-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-skeleton-loader"
  })), columns && Object.keys(columns).map(heading => {
    return columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: " wsx-skeleton-loader"
    }));
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    className: "wsx-lists-table-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-skeleton-loader"
  })), columns && Object.keys(columns).map(heading => {
    return columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
      className: "wsx-lists-table-column"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: " wsx-skeleton-loader"
    }));
  }))), !initialLoader && Object.keys(users).map((user, index) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    key: index
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    className: "wsx-checkbox-column wsx-lists-table-column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("label", {
    className: "wsx-checkbox-option-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    type: "checkbox",
    "data-id": users[user]["ID"],
    checked: selectedUsers.includes(Number(users[user]["ID"])),
    onChange: selectUser
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-checkbox-mark"
  }))), columns && Object.keys(columns).map((heading, headingIndex) => columns[heading].status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, renderTableCell(users[user][columns[heading].name], columns[heading].type, columns[heading].name, users[user], index))))), !initialLoader && Object.keys(users).length === 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("tr", {
    className: "wsx-lists-table-row wsx-lists-empty"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("td", {
    colSpan: 100,
    className: `wsx-lists-table-column`
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_15__["default"].noData))))), hasPagination() && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_UserPagination__WEBPACK_IMPORTED_MODULE_16__["default"], {
    paginationData: paginationData,
    fetchData: fetchData,
    spaceTop: 48
  })), renderOverlayWindow(), deleteModal(), state.length > 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Toast__WEBPACK_IMPORTED_MODULE_10__["default"], {
    position: 'top_right',
    delay: 5000
  }), generateTransactionTable(), alert && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Alert__WEBPACK_IMPORTED_MODULE_17__["default"], {
    title: "Please Select Properly!",
    description: "Please select rule from the list and an action to perform bulk action.",
    onClose: handleAlertClose
  }), isAlertVisible && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Alert__WEBPACK_IMPORTED_MODULE_17__["default"], {
    title: "Confirm Bulk Action",
    description: `Are you sure you want to ${pendingAction} the selected rules?`,
    onClose: handleAlertClose,
    onConfirm: confirmBulkAction
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Users);

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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Common.scss":
/*!**************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Common.scss ***!
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
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/getUrl.js */ "./node_modules/css-loader/dist/runtime/getUrl.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__);
// Imports



var ___CSS_LOADER_URL_IMPORT_0___ = new URL(/* asset import */ __webpack_require__(/*! ../../../../assets/img/angle-down-16.svg */ "./assets/img/angle-down-16.svg"), __webpack_require__.b);
var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
var ___CSS_LOADER_URL_REPLACEMENT_0___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_0___);
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/* Variables */
/* Default Style */
body {
  font-family: "Inter", sans-serif;
  font-size: 14px;
  line-height: 1.42857em;
  color: #87858d; }

a {
  text-decoration: none;
  color: initial; }
  a:hover {
    color: #6C6CFF; }
  a:focus {
    box-shadow: none;
    outline: none;
    color: initial; }
  a.wsx-color-primary:hover {
    color: #FEAD01; }

img {
  max-width: 100%;
  height: auto; }

input,
input[type=text],
input[type=number],
input[type=email],
input[type=url],
input[type=password],
textarea,
.wp-core-ui select {
  outline: none;
  border: 1px solid #E2E4E9;
  border-radius: 8px;
  box-shadow: none;
  background-color: transparent;
  width: 100%;
  min-height: 40px;
  height: 100%;
  margin: 0;
  padding: 0 8px; }
  input:focus,
  input[type=text]:focus,
  input[type=number]:focus,
  input[type=email]:focus,
  input[type=url]:focus,
  input[type=password]:focus,
  textarea:focus,
  .wp-core-ui select:focus {
    outline: none;
    box-shadow: none;
    border-color: #6C6CFF; }
  input.wsx-bg-base1,
  input[type=text].wsx-bg-base1,
  input[type=number].wsx-bg-base1,
  input[type=email].wsx-bg-base1,
  input[type=url].wsx-bg-base1,
  input[type=password].wsx-bg-base1,
  textarea.wsx-bg-base1,
  .wp-core-ui select.wsx-bg-base1 {
    background-color: #ffffff; }

.wp-core-ui select {
  position: relative;
  max-width: unset;
  background: transparent url(${___CSS_LOADER_URL_REPLACEMENT_0___}) no-repeat right 14px top 55%;
  background-size: 16px 16px;
  padding: 0 32px 0 8px; }

textarea {
  padding: 10px 8px; }

code, kbd {
  padding: 1px 6px 2px;
  background-color: #E2E4E9;
  font-size: 12px;
  line-height: 1.334em;
  font-style: italic;
  color: #343A46;
  margin-top: 4px;
  display: block;
  width: fit-content; }

/* Dashboard Style */
.wsx-dashboard {
  display: flex;
  gap: 48px; }
  .wsx-dashboard .wsx-container-left {
    flex-basis: 76%; }
  .wsx-dashboard .wsx-container-right {
    flex-basis: 24%; }
  .wsx-dashboard-header {
    position: relative; }

/* License Style */
.wsx-upgrade-license-wrapper {
  display: flex;
  gap: 20px;
  align-items: start;
  text-align: start;
  margin-top: 32px; }

/* Settings Style */
.wsx-settings-item .wsx-accordion-body {
  display: flex;
  flex-direction: column;
  gap: 30px; }

.wsx-settings-item input {
  padding: 0px 16px; }

.wsx-settings-item textarea {
  padding: 10px 16px; }

.wsx-design_two .wsx-column-1fr-2fr {
  display: block; }

.wsx-design_three {
  display: grid;
  gap: 32px;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }

/* Support Style */
.wsx-support-form input, .wsx-support-form textarea {
  padding: 10px 16px; }

/* Addon Style */
.wsx-addon-card-container {
  width: 100%;
  box-sizing: border-box;
  position: relative;
  background-color: #ffffff;
  box-shadow: 0px 2px 4px 0px rgba(78, 46, 206, 0.16);
  border-radius: 12px; }
  .wsx-addon-card-container .wsx-addon-card-body {
    padding: 32px 32px 90px 32px;
    position: relative; }
  .wsx-addon-card-container .wsx-addon-card-header img {
    max-width: 48px; }
  .wsx-addon-card-container .wsx-addon-card-footer {
    padding: 16px 32px;
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #E2E4E9;
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ffffff;
    border-radius: 0 0 12px 12px; }

.wsx-pro-active-card {
  border-radius: 12px;
  box-shadow: 0 1px 2px 0 rgba(78, 46, 206, 0.16);
  background-color: #ffffff;
  padding: 32px;
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  width: 90%;
  max-width: 1300px;
  margin: 0 auto; }
  .wsx-pro-active-card-content {
    width: 34vw; }
    @media (max-width: 1132px) {
      .wsx-pro-active-card-content {
        width: 100%; } }
  .wsx-pro-active-card img {
    max-width: 450px; }
  @media (max-width: 1132px) {
    .wsx-pro-active-card {
      text-align: center; }
      .wsx-pro-active-card .wsx-list {
        width: fit-content; }
      .wsx-pro-active-card .wsx-btn, .wsx-pro-active-card .wsx-list, .wsx-pro-active-card img {
        margin-left: auto;
        margin-right: auto; } }

.wsx-sales-card .wsx-growth-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 2px 6px;
  border-radius: 2px; }
  .wsx-sales-card .wsx-growth-wrapper.wsx-color-sky {
    background-color: rgba(92, 173, 255, 0.1); }
  .wsx-sales-card .wsx-growth-wrapper.wsx-color-orchid {
    background-color: rgba(163, 103, 240, 0.1); }
  .wsx-sales-card .wsx-growth-wrapper.wsx-color-primary {
    background-color: rgba(108, 108, 255, 0.1); }
  .wsx-sales-card .wsx-growth-wrapper.wsx-color-mint {
    background-color: rgba(82, 212, 127, 0.1); }
  .wsx-sales-card .wsx-growth-wrapper.wsx-color-secondary {
    background-color: rgba(254, 173, 1, 0.1); }
  .wsx-sales-card .wsx-growth-wrapper.wsx-color-lime {
    background-color: rgba(148, 199, 82, 0.1); }

.wsx-sales-card .wsx-sales-growth {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px; }

.wsx-chart {
  margin-left: -8px; }
  .wsx-chart-wrapper .wsx-card {
    padding: 24px 24px 32px; }
  .wsx-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 0 8px;
    margin-bottom: 28px; }
  .wsx-chart .recharts-cartesian-axis-ticks text {
    transform: translateX(-8px); }
  .wsx-chart .recharts-cartesian-axis-ticks tspan {
    font-size: 12px;
    line-height: 16px;
    stroke: #343A46;
    stroke-width: 0.3; }
  .wsx-chart .recharts-xAxis .recharts-cartesian-axis-ticks text {
    transform: translateY(16px); }
  .wsx-chart .recharts-xAxis .recharts-cartesian-axis-ticks tspan {
    stroke: #6B677A;
    stroke-width: 0.2; }
  .wsx-chart .recharts-cartesian-axis line, .wsx-chart .recharts-cartesian-grid-horizontal line {
    stroke: #E2E4E9;
    stroke-dasharray: 0; }

/* Side Menu Style  */
.wsx-side-menu-wrapper {
  display: flex;
  align-items: stretch;
  border-radius: 12px;
  box-shadow: 0 2px 4px 0 rgba(78, 46, 206, 0.16);
  overflow: hidden;
  white-space: nowrap; }

.wsx-side-menu-list {
  background-color: #E7E7FF;
  max-width: 232px;
  width: 100%; }

.wsx-side-menu-body {
  background-color: #ffffff;
  width: 100%; }

.wsx-side-menu-item {
  cursor: pointer;
  padding: 24px 32px;
  color: #070707;
  font-weight: 500;
  border-bottom: 1px solid #D5D5FA; }
  .wsx-side-menu-item:hover, .wsx-side-menu-item.active {
    background-color: #6C6CFF;
    color: #ffffff; }
  .wsx-side-menu-item:last-child {
    margin-bottom: 100px; }

.wsx-side-menu-header {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: space-between;
  padding: 16px 40px;
  border-bottom: 1px solid #D5D5FA; }

.wsx-side-menu-content {
  padding: 50px 40px 100px;
  display: flex;
  flex-direction: column;
  gap: 40px; }

/* Component Style */
.wsx-drag-drop-file-upload-container {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 4px;
  border: 2px dashed #6C6CFF;
  border-radius: 12px;
  background-color: #F4F4FF;
  width: auto;
  max-width: 95%;
  height: auto;
  min-height: 195px;
  padding: 16px 20px 20px;
  cursor: pointer;
  transition: all 0.3s; }
  .wsx-drag-drop-file-upload-container:hover, .wsx-drag-drop-file-upload-container.active {
    background-color: #E7E7FF; }
    .wsx-drag-drop-file-upload-container:hover .wsx-icon, .wsx-drag-drop-file-upload-container.active .wsx-icon {
      color: #6C6CFF; }

.wsx-drag-drop-file-details {
  background-color: #F6F8FA;
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border: 1px solid #E2E4E9; }

.wsx-font-container {
  display: flex;
  align-items: center;
  border: 1px solid #6B677A;
  border-radius: 8px;
  overflow: hidden; }

.wsx-font-value {
  width: 48px;
  text-align: center;
  font-size: 14px;
  line-height: 20px;
  background-color: #ffffff;
  color: #6B677A; }

.wsx-chart-wrapper {
  margin-bottom: 40px; }

.wsx-sales-chart-container {
  width: 100%; }

.wsx-sales-chart-header {
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  white-space: nowrap;
  display: grid;
  grid-template-columns: 1fr 2fr; }

.wsx-sales-chart-legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: end;
  gap: 16px; }

.wsx-sales-chart-wrapper {
  position: relative;
  width: 100%; }

.wsx-sales-chart-canvas {
  cursor: pointer;
  border: 1px solid #E7E7FF; }

.wsx-sales-chart-tooltip {
  position: absolute;
  z-index: 10;
  background-color: #ffffff;
  border: 1px solid #E2E4E9;
  border-radius: 8px;
  box-shadow: 0 16px 32px -16px rgba(107, 103, 122, 0.1);
  transform: translate(-50%, -100%);
  pointer-events: none;
  overflow: hidden; }

.wsx-sales-chart-tooltip-date {
  font-size: 12px;
  color: #343A46;
  padding: 8px;
  background-color: #F6F8FA;
  border-bottom: 1px solid #E2E4E9; }

.wsx-sales-chart-tooltip-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  background-color: #ffffff; }

.wsx-sales-chart-tooltip-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 14px; }

.wsx-sales-chart-tooltip-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0; }

.wsx-sales-chart-tooltip-label {
  font-size: 12px;
  color: #6B677A; }

.wsx-sales-chart-tooltip-value {
  font-weight: 500;
  font-size: 12px;
  color: #07050b; }

.wsx-date-picker-container {
  width: 100%; }
  .wsx-date-picker-container .react-date-picker__wrapper {
    border: 1px solid #E2E4E9;
    border-radius: 8px;
    background-color: #ffffff;
    padding: 3.5px 16px; }
    .wsx-date-picker-container .react-date-picker__wrapper input[type=number] {
      border: none;
      min-height: 20px;
      border-radius: 0;
      padding: 0 4px; }
      .wsx-date-picker-container .react-date-picker__wrapper input[type=number]:focus {
        border: none; }
    .wsx-date-picker-container .react-date-picker__wrapper input[type=date] {
      min-height: 20px; }
  .wsx-date-picker-container input[type="text"], .wsx-date-picker-container input:focus[type="text"] {
    min-width: auto;
    line-height: 16px;
    padding: 0 4px; }
    .wsx-date-picker-container input[type="text"].wsx-input-month, .wsx-date-picker-container input:focus[type="text"].wsx-input-month {
      width: 34px; }
      .wsx-date-picker-container input[type="text"].wsx-input-month:not(:placeholder-shown), .wsx-date-picker-container input:focus[type="text"].wsx-input-month:not(:placeholder-shown) {
        width: 24px; }
    .wsx-date-picker-container input[type="text"].wsx-input-day, .wsx-date-picker-container input:focus[type="text"].wsx-input-day {
      width: 26px; }
    .wsx-date-picker-container input[type="text"].wsx-input-year, .wsx-date-picker-container input:focus[type="text"].wsx-input-year {
      width: 120px; }

.wsx-date-picker-wrapper {
  justify-content: space-between;
  padding: 0 8px; }
  .wsx-date-picker-wrapper .wsx-icon {
    padding: 4px; }
    .wsx-date-picker-wrapper .wsx-icon.wsx-btn-action {
      background-color: transparent; }
      .wsx-date-picker-wrapper .wsx-icon.wsx-btn-action:hover {
        background-color: transparent; }

.wsx-date-picker-calendar {
  box-shadow: 0px 1px 10px 3px rgba(78, 46, 206, 0.161); }
  .wsx-date-picker-calendar .wsx-days {
    row-gap: 0; }
  .wsx-date-picker-calendar .wsx-day {
    border-radius: 50%;
    padding: 12px; }

.wsx-date-range-width-selector {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  z-index: 9;
  width: 844px;
  max-width: 92%;
  display: none;
  justify-content: end; }
  @media (max-width: 617px) {
    .wsx-date-range-width-selector {
      left: 0;
      margin: 0 auto; } }

.wsx-date-range-wrapper {
  width: max-content;
  max-width: 82%;
  padding: 20px;
  margin-bottom: 0; }
  @media (max-width: 617px) {
    .wsx-date-range-wrapper {
      margin: 0 auto; } }
  .wsx-date-range-wrapper .screen-reader-text {
    display: none; }
  .wsx-date-range-wrapper .woocommerce-segmented-selection__container {
    width: max-content;
    background-color: #F6F8FA;
    gap: 8px;
    padding: 16px;
    border-radius: 8px;
    border: none; }
  .wsx-date-range-wrapper .woocommerce-segmented-selection__item {
    position: relative;
    cursor: pointer;
    border: none !important; }
    .wsx-date-range-wrapper .woocommerce-segmented-selection__item input {
      position: absolute;
      visibility: hidden; }
  .wsx-date-range-wrapper .woocommerce-segmented-selection__input:active + label .woocommerce-segmented-selection__label {
    background-color: #F6F8FA; }
  .wsx-date-range-wrapper .woocommerce-segmented-selection__input:checked + label .woocommerce-segmented-selection__label {
    background-color: #070707;
    color: #ffffff; }
    .wsx-date-range-wrapper .woocommerce-segmented-selection__input:checked + label .woocommerce-segmented-selection__label::before {
      content: none; }
  .wsx-date-range-wrapper .woocommerce-segmented-selection__label {
    cursor: pointer;
    padding: 10px 27px;
    height: auto;
    text-align: center;
    border-radius: 8px;
    background-color: #ffffff;
    color: #6B677A;
    font-size: 12px;
    line-height: 1.34em;
    font-weight: 500; }
    .wsx-date-range-wrapper .woocommerce-segmented-selection__label::before {
      content: none; }

.wsx-date-range-title {
  font-weight: 500;
  text-align: center; }

.active .wsx-date-range-width-selector {
  display: flex; }

.wsx-calendar {
  padding: 16px 0px 12px;
  margin: 0 16px;
  border-top: 1px solid #E2E4E9; }
  .wsx-calendar-range-wrapper {
    border: 1px solid #070707;
    border-radius: 8px; }
  .wsx-calendar-header {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 32px;
    border-bottom: 1px solid #E2E4E9; }
  .wsx-calendar-title {
    display: flex;
    align-items: center;
    gap: 12px; }
    .wsx-calendar-title svg {
      color: #0A0D14; }
  .wsx-calendar-value {
    display: flex;
    align-items: center;
    gap: 20px; }
    .wsx-calendar-value .wsx-input-wrapper-with-icon .wsx-icon {
      padding: 6px; }
      .wsx-calendar-value .wsx-input-wrapper-with-icon .wsx-icon svg {
        width: 16px;
        height: 16px; }
    .wsx-calendar-value input, .wsx-calendar-value .wsx-date-input-wrapper {
      max-width: 140px;
      min-height: auto; }
    @media (max-width: 756px) {
      .wsx-calendar-value {
        flex-direction: column;
        gap: 12px;
        width: fit-content;
        align-items: end; } }
  .wsx-calendar-container {
    display: flex; }

.wsx-control-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 0;
  margin: 0 16px; }
  .wsx-control-wrapper .wsx-month {
    width: 100%;
    text-align: center;
    font-size: 18px;
    line-height: 1.4em;
    font-weight: 500; }
    @media (max-width: 480px) {
      .wsx-control-wrapper .wsx-month {
        font-size: 18px; } }
  .wsx-control-wrapper .wsx-calendar-controller {
    cursor: pointer; }
    .wsx-control-wrapper .wsx-calendar-controller:hover {
      color: #6C6CFF; }

.wsx-days-of-week,
.wsx-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  row-gap: 10px; }

.wsx-day {
  padding: 6px 10px;
  text-align: center;
  font-size: 14px;
  line-height: 1.34em;
  color: #0A0D14; }
  .wsx-day-name {
    text-align: center;
    font-size: 12px;
    line-height: 1.34em;
    font-weight: 500;
    text-transform: uppercase;
    margin-bottom: 16px;
    padding: 2px 8px; }
    @media (max-width: 480px) {
      .wsx-day-name {
        padding: 2px 4px; } }
  @media (max-width: 480px) {
    .wsx-day {
      padding: 4px 6px;
      font-size: 16px; } }
  .wsx-day.active {
    cursor: pointer; }
    .wsx-day.active:hover {
      background-color: #F4F4FF;
      color: #343A46; }
  .wsx-day.in-range, .wsx-day.hovered-range {
    background-color: #E7E7FF; }
    .wsx-day.in-range:hover, .wsx-day.hovered-range:hover {
      background-color: #E7E7FF; }
  .wsx-day.start, .wsx-day.end {
    background-color: #6C6CFF;
    color: #ffffff; }
    .wsx-day.start:hover, .wsx-day.end:hover {
      background-color: #6C6CFF;
      color: #ffffff; }
  .wsx-day.start {
    border-radius: 8px 0 0 8px; }
    .wsx-day.start.end {
      border-radius: 8px; }
  .wsx-day.end {
    border-radius: 0 8px 8px 0; }
  .wsx-day.selected {
    background-color: #6C6CFF;
    color: #ffffff; }
    .wsx-day.selected:hover {
      background-color: #6C6CFF;
      color: #ffffff; }
  .wsx-day.today {
    background-color: #FEAD01;
    color: #ffffff; }
    .wsx-day.today:hover {
      background-color: #FEAD01;
      color: #ffffff; }

.wsx-option-container {
  position: absolute;
  left: 0;
  z-index: 10;
  min-width: max-content;
  width: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  max-height: 60vh;
  box-shadow: 0 2px 4px 0 rgba(78, 46, 206, 0.16); }

.wsx-option-item {
  padding: 8px;
  cursor: pointer; }
  .wsx-option-item:last-child {
    border-bottom: 0; }
  .wsx-option-item:hover, .wsx-option-item.active {
    background-color: #6C6CFF;
    color: #ffffff; }

.wsx-selected-field-wrapper {
  width: 100%; }
  .wsx-selected-field-wrapper.wsx-multiple-field {
    width: 50%; }

.wsx-btn {
  opacity: 1; }
  .wsx-btn-group {
    display: flex;
    align-items: center; }
    .wsx-btn-group.wsx-center {
      justify-content: center;
      margin: 0 auto; }
  .wsx-btn .wsx-icon.wsx-color-primary {
    color: #6C6CFF; }
    .wsx-btn .wsx-icon.wsx-color-primary:hover {
      color: #FEAD01; }
  .wsx-btn .wsx-icon.wsx-color-secondary {
    color: #FEAD01; }
    .wsx-btn .wsx-icon.wsx-color-secondary:hover {
      color: #6C6CFF; }
  .wsx-btn-rescale {
    background-color: #E7E7FF;
    border: none;
    color: #343A46;
    width: 32px;
    height: 32px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 0; }
    .wsx-btn-rescale-left {
      border-right: 1px solid #6B677A; }
    .wsx-btn-rescale-right {
      border-left: 1px solid #6B677A; }
    .wsx-btn-rescale:hover {
      background-color: #070707;
      color: #ffffff; }
  .wsx-btn.wsx-color-primary {
    color: #6C6CFF; }
  .wsx-btn.wsx-color-text-medium {
    color: #343A46; }
  .wsx-btn.wsx-color-text-light {
    color: #6B677A; }
  .wsx-btn.wsx-color-text-reverse {
    color: #ffffff; }
  .wsx-btn.wsx-color-blue-dark {
    color: #0A0D14; }
  .wsx-btn.wsx-bg-primary:hover, .wsx-btn.wsx-bg-primary.active, .wsx-btn.wsx-bg-negative:hover, .wsx-btn.wsx-bg-negative.active {
    background-color: #FEAD01;
    border-color: #FEAD01; }
  .wsx-btn.wsx-bg-secondary {
    color: #070707; }
    .wsx-btn.wsx-bg-secondary:hover, .wsx-btn.wsx-bg-secondary.active {
      background-color: #6C6CFF;
      color: #ffffff;
      border-color: #6C6CFF; }
  .wsx-btn.wsx-bg-tertiary:hover, .wsx-btn.wsx-bg-tertiary.active, .wsx-btn.wsx-bg-positive:hover, .wsx-btn.wsx-bg-positive.active {
    background-color: #6C6CFF;
    border-color: #6C6CFF; }
  .wsx-btn.disable {
    cursor: not-allowed;
    opacity: 0.4;
    pointer-events: none; }
  .wsx-btn-action {
    background-color: #F4F4FF;
    border-radius: 8px;
    padding: 8px;
    color: #070707;
    line-height: 0;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    box-sizing: border-box;
    cursor: pointer; }
    .wsx-btn-action:hover {
      color: #6C6CFF;
      background-color: #E7E7FF; }

.wsx-color-picker-container {
  border-radius: 4px;
  border: 1px solid #E2E4E9;
  width: fit-content;
  min-width: 140px;
  overflow: hidden;
  background-color: #ffffff; }

.wsx-color-picker-label {
  padding: 10px 16px;
  color: #343A46;
  text-transform: uppercase; }

input[type=color].wsx-color-picker-input {
  outline: 0;
  box-shadow: none;
  padding: 0;
  width: 40px;
  height: 40px;
  border-radius: 0;
  border: 0;
  border-right: 1px solid #E2E4E9; }
  input[type=color].wsx-color-picker-input::-webkit-color-swatch-wrapper {
    padding: 0; }
  input[type=color].wsx-color-picker-input::-webkit-color-swatch {
    border: none; }

.wsx-alert-popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(78, 46, 206, 0.16);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999; }

.wsx-alert-popup {
  background-color: #ffffff;
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  padding: 16px;
  box-shadow: 0 4px 8px rgba(78, 46, 206, 0.16);
  text-align: center;
  border: 1px solid #E2E4E9; }

.wsx-alert-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  border-bottom: 1px solid #E2E4E9;
  padding-bottom: 12px; }

.wsx-alert-title-wrapper {
  font-weight: 500;
  color: #070707;
  display: flex;
  align-items: center;
  gap: 8px; }

.wsx-alert-body {
  margin-bottom: 20px;
  font-size: 12px;
  line-height: 16px;
  text-align: left;
  color: #343A46; }

.wsx-alert-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px; }

.wsx-drag-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  max-height: 50px;
  box-sizing: border-box;
  padding: 8px 16px;
  margin-bottom: 8px;
  border-radius: 8px;
  border: 1px solid #E2E4E9;
  background-color: #ffffff;
  cursor: move; }
  .wsx-drag-item:last-child {
    margin-bottom: 0; }
  .wsx-drag-item:hover {
    border-color: #6C6CFF; }
  .wsx-drag-item-value {
    max-width: 160px; }

.wsx-pagination-wrapper {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  white-space: nowrap; }
  .wsx-pagination-wrapper .wsx-btn {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0; }
    .wsx-pagination-wrapper .wsx-btn:hover {
      background-color: #070707; }
      .wsx-pagination-wrapper .wsx-btn:hover span {
        color: #ffffff; }

select.wsx-pagination-option-list {
  background-color: #ffffff;
  border: 1px solid #DCDCEE;
  padding: 1px 36px 1px 12px;
  border-radius: 8px;
  color: #343A46;
  min-height: 31px;
  padding-top: 4px; }
  select.wsx-pagination-option-list:focus {
    outline: none;
    box-shadow: none;
    border-color: #DCDCEE;
    color: #6C6CFF; }

.wsx-toast-wrapper {
  position: fixed;
  z-index: 99999;
  top: 110px;
  right: 15px; }

.wsx-toast-message-wrapper {
  display: flex;
  align-items: center;
  gap: 32px;
  padding: 12px;
  border-radius: 12px;
  color: #ffffff;
  box-shadow: 0 0 12px 0 rgba(7, 7, 7, 0.5); }

.wsx-toast-close {
  cursor: pointer; }
  .wsx-toast-close:hover {
    color: #070707; }

.wsx-shortcode-field-content {
  padding: 4px;
  background-color: #E7E7FF;
  border-radius: 8px;
  border: 1px solid #D5D5FA;
  display: flex;
  align-items: center;
  gap: 32px;
  width: fit-content; }
  .wsx-shortcode-field-content .wsx-icon-wrapper {
    padding: 4px;
    color: #ffffff;
    border-radius: 4px;
    background-color: #6C6CFF; }
    .wsx-shortcode-field-content .wsx-icon-wrapper:hover {
      background-color: #FEAD01; }
  .wsx-shortcode-field-content:hover {
    background-color: #F4F4FF; }

.wsx-get-shortcode-text {
  color: #343A46;
  padding-left: 12px; }

.wsx-shortcode {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  border: 1px solid #eeeeee;
  border-radius: 4px;
  padding: 0 12px; }
  .wsx-shortcode input, .wsx-shortcode input:focus {
    border: none;
    box-shadow: none;
    outline: none;
    min-height: 28px;
    padding: 0; }
  .wsx-shortcode-container {
    height: 60vh;
    overflow: auto; }
  .wsx-shortcode-item {
    padding: 20px 0;
    border-top: 1px solid #E2E4E9;
    min-width: 860px;
    grid-template-columns: 1fr 1fr 2fr; }
    .wsx-shortcode-item:last-child {
      border-bottom: 1px solid #E2E4E9; }
  .wsx-shortcode-wrapper .wsx-title {
    border-bottom: 1px solid #E2E4E9; }
  .wsx-shortcode .wsx-ellipsis {
    max-width: 20rem; }

/* ========
KM Start
======== */
.wsx-popup-overlay .wsx-lists-table-wrapper {
  overflow: auto;
  max-height: 60vh; }

.wsx-popup-overlay .wsx-lists-table-body tr {
  border: 0; }
  .wsx-popup-overlay .wsx-lists-table-body tr td {
    border-bottom: 1px solid #E2E4E9; }
    .wsx-popup-overlay .wsx-lists-table-body tr td:first-child {
      padding-left: 31px;
      border-left: 1px solid #E2E4E9; }
    .wsx-popup-overlay .wsx-lists-table-body tr td:last-child {
      padding-right: 31px;
      border-right: 1px solid #E2E4E9; }
  .wsx-popup-overlay .wsx-lists-table-body tr:last-child td:first-child {
    display: block;
    border-radius: 0 0 0 12px; }
  .wsx-popup-overlay .wsx-lists-table-body tr:last-child td:last-child {
    display: block;
    border-radius: 0 0 12px 0; }

.wsx-transaction-popup .wsx-card {
  padding: 46px 56px; }

.wsx-side-modal-wrapper {
  position: fixed;
  top: 32px;
  left: 0;
  bottom: 0;
  right: 0;
  display: flex;
  justify-content: flex-end;
  z-index: 9999;
  background-color: rgba(7, 7, 7, 0.6); }
  @media (max-width: 782px) {
    .wsx-side-modal-wrapper {
      top: 46px; } }

.wsx-side-modal-container {
  width: 90%;
  height: 100%;
  max-width: 890px;
  background-color: #ffffff; }
  .wsx-side-modal-container .wsx-side-menu-header {
    background-color: #F4F4FF;
    border-bottom: 0; }
  .wsx-side-modal-container .wsx-side-menu-content {
    overflow: auto;
    height: 100vh; }
    .wsx-side-modal-container .wsx-side-menu-content .wsx-flex-column:last-child {
      padding-bottom: 220px; }

.wsx-video-modal .wsx-popup-content-wrapper {
  max-width: 1120px;
  max-height: 630px;
  padding: 8px; }
  @media (max-width: 768px) {
    .wsx-video-modal .wsx-popup-content-wrapper {
      width: 90%;
      height: auto; } }

.wsx-video-modal .wsx-icon-cross {
  color: #ffffff;
  border: none;
  right: -14px;
  top: -14px;
  padding: 5px;
  background: #070707; }
  .wsx-video-modal .wsx-icon-cross:hover {
    background-color: #FF2F54; }

/*

Features Page Style Start

*/
.wsx-features-heading {
  text-align: center;
  margin-bottom: 48px; }
  .wsx-features-heading .wsx-heading-text {
    font-size: 24px;
    font-weight: 600;
    line-height: 32px;
    margin-bottom: 16px; }
    .wsx-features-heading .wsx-heading-text .wsx-text-highlight {
      color: #6C6CFF; }
  .wsx-features-heading .wsx-sub-heading-text {
    font-size: 14px;
    font-weight: 400;
    line-height: 20px;
    color: #343A46; }

.wsx-features-section {
  background-color: #E7E7FF;
  border-radius: 12px;
  margin-top: 80px; }

.wsx-feature-container {
  padding: 48px 80px; }

/*

Features Page Style End



*/
.wsx-badge-style {
  gap: 16px; }
  .wsx-badge-style label#choose-box-selected {
    border-color: #070707; }
  .wsx-badge-style .choose-box-selected {
    padding: 13px 16px; }
  .wsx-badge-style .wsx-choose-box-image {
    max-height: 22px; }

.wsx-accordion-body.wsx-close {
  display: none;
  max-height: 0;
  overflow: hidden;
  transition: max-height 3s ease-in-out; }

/* ========
KM End
======== */
/* Common Style */
.wsx-preview-container {
  border: 2px solid #E2E4E9;
  border-radius: 8px;
  width: 32vw;
  max-width: 611px; }

.wsx-preview-header {
  padding: 12px;
  text-align: center;
  background-color: #E2E4E9; }

.wsx-preview-body {
  padding: 16px 20px 24px; }

.wsx-profile-list {
  width: 24px;
  height: 24px;
  color: #ffffff;
  background-color: #868C98;
  border: 1px solid #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase; }

.wsx-hr-line {
  width: 100%;
  height: 1px;
  background-color: #E2E4E9; }

.wsx-item-divider-wrapper > div, .wsx-item-divider-wrapper > span {
  border-bottom: 1px solid #E2E4E9;
  padding-bottom: 20px;
  margin-bottom: 20px; }
  .wsx-item-divider-wrapper > div:last-child, .wsx-item-divider-wrapper > span:last-child {
    border-bottom: 0;
    padding-bottom: 0;
    margin-bottom: 0; }

.wsx-curser-pointer {
  cursor: pointer; }

.wsx-depends-message {
  margin-top: 8px;
  color: #FEAD01;
  font-size: 14px; }
  .wsx-depends-message a {
    color: #6C6CFF;
    font-weight: 600; }
    .wsx-depends-message a:hover, .wsx-depends-message a:focus, .wsx-depends-message a:visited {
      color: #62C55A; }

.wsx-3dot-wrapper {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background-color: #F4F4FF;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
  z-index: 0; }
  .wsx-3dot-wrapper:hover, .wsx-3dot-wrapper.active {
    background-color: #6C6CFF;
    color: #ffffff; }

.wsx-notification-icon {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: #ff176b;
  position: absolute;
  top: -5px;
  right: -5px; }

.wsx-dashboard-counter {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: #ff176b;
  position: absolute;
  top: -5px;
  right: -5px; }

.wsx-note-wrapper {
  width: fit-content;
  padding: 10px 16px;
  border-radius: 8px;
  background-color: #F4F4FF; }

.wsx-help-message {
  font-size: 12px;
  line-height: 1.334em;
  color: #87858d;
  font-style: italic;
  margin-top: 6px;
  white-space: normal; }

.wsx-accordion-wrapper {
  background-color: #F6F8FA;
  border-radius: 8px;
  border: 1px solid #E2E4E9; }
  .wsx-accordion-wrapper input, .wsx-accordion-wrapper select {
    background-color: #ffffff; }
  .wsx-accordion-wrapper .wsx-input-label {
    color: #343A46; }
  .wsx-accordion-wrapper-dynamic {
    display: grid;
    gap: 24px; }
    .wsx-accordion-wrapper-dynamic .wsx-switch-field-wrapper, .wsx-accordion-wrapper-dynamic .wsx-slider-wrapper {
      display: flex;
      align-items: center;
      max-height: 40px;
      height: 100%; }
      @media (max-width: 768px) {
        .wsx-accordion-wrapper-dynamic .wsx-switch-field-wrapper, .wsx-accordion-wrapper-dynamic .wsx-slider-wrapper {
          display: block; } }

.wsx-accordion-header .wsx-icon {
  color: #070707; }
  .wsx-accordion-header .wsx-icon svg {
    transition: all 0.3s; }
  .wsx-accordion-header .wsx-icon.active svg {
    transform: rotate(180deg); }

.wsx-accordion-title {
  font-size: 16px;
  line-height: 24px;
  font-weight: 500;
  color: #343A46; }

.wsx-accordion-body {
  padding: 32px 24px;
  border-top: 1px solid #E2E4E9; }

.wsx-tiers-fields {
  margin-bottom: 8px; }

.wsx-tier-wrapper, .wsx-tier-header {
  display: grid;
  grid-template-columns: repeat(4, 1fr) 40px;
  gap: 24px;
  align-items: end; }
  .wsx-tier-wrapper2, .wsx-tier-header2 {
    grid-template-columns: repeat(3, 1fr) 40px; }

.wsx-tier-wrapper {
  margin-bottom: 16px; }

.wsx-tier-header-item {
  padding-left: 4px;
  color: #343A46; }

.wsx-tier-design-settings {
  padding-right: 20px;
  border-right: 1px solid #070707;
  display: flex;
  flex-direction: column;
  gap: 32px; }

.wsx-condition-container .wsx-tier-wrapper, .wsx-condition-container .wsx-tier-header {
  grid-template-columns: repeat(3, 1fr) 40px; }

.wsx-edit-role-wrapper {
  color: #343A46; }
  .wsx-edit-role-wrapper.wsx-card {
    padding: 32px; }
  .wsx-edit-role-wrapper .wsx-edit-role-container {
    display: flex;
    flex-direction: column;
    gap: 40px; }
  .wsx-edit-role-wrapper .wsx-radio-field-label {
    margin-bottom: 20px; }
  .wsx-edit-role-wrapper input, .wsx-edit-role-wrapper .wsx-multiselect-wrapper {
    background-color: #ffffff; }

.wsx-role-title-wrapper {
  margin-bottom: -16px; }

.wsx-role-credit-wrapper {
  display: flex;
  align-items: center;
  gap: 32px; }
  .wsx-role-credit-wrapper .wsx-input-wrapper {
    width: 100%; }

.wsx-regi_form_section {
  padding: 20px 24px;
  background-color: #F6F8FA;
  border-radius: 8px;
  border: 1px solid #E2E4E9; }

.wsx-user-role-url-fields {
  gap: 32px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  padding: 20px 24px;
  background-color: #F6F8FA;
  border-radius: 8px;
  border: 1px solid #E2E4E9; }
  .wsx-user-role-url-fields .wsx-user-role-radio-field {
    width: 100%;
    flex-grow: 2; }
  .wsx-user-role-url-fields .wsx-input-wrapper {
    width: 48%;
    flex-grow: 1; }

.wsx-slider-line-container {
  display: flex;
  align-items: center;
  gap: 40px;
  flex-wrap: wrap; }

.wsx-shipping-zone-row {
  gap: 40px;
  display: flex;
  padding: 16px 24px;
  align-items: center;
  border-top: 1px solid #E2E4E9; }

.wsx-shipping-zone-title {
  color: #343A46;
  min-width: 100px;
  width: 18.575%; }

.wsx-table-wrapper {
  overflow: hidden;
  border-radius: 8px;
  box-shadow: 0 2px 4px 0 rgba(78, 46, 206, 0.16); }

.wsx-table-container {
  background-color: #ffffff;
  padding: 32px;
  overflow: auto;
  white-space: nowrap; }

.wsx-table-body {
  color: #6B677A; }
  .wsx-table-body .wsx-ellipsis {
    max-width: 12rem;
    display: block; }

.wsx-table-header, .wsx-table-row {
  display: grid;
  padding-bottom: 32px;
  width: 100%;
  border-bottom: 1px solid #E2E4E9; }

.wsx-table-header {
  font-weight: 500;
  color: #343A46; }

.wsx-table-row {
  padding: 20px 0;
  align-items: center; }

.wsx-lists-table {
  width: 100%;
  overflow-y: auto;
  border-spacing: 0;
  border-collapse: collapse;
  border-radius: 12px;
  background-color: #ffffff;
  box-shadow: 0 0px 4px 0 rgba(78, 46, 206, 0.16); }
  .wsx-lists-table-wrapper {
    overflow-x: auto; }
  .wsx-lists-table-header {
    color: #343A46;
    font-weight: 500;
    background-color: #E7E7FF; }
    .wsx-lists-table-header th {
      background-color: #E7E7FF; }
  .wsx-lists-table-column {
    padding: 20px 0;
    text-align: start;
    min-width: 110px; }
    .wsx-lists-table-column:first-child {
      padding-left: 32px;
      border-radius: 12px 0 0 0; }
    .wsx-lists-table-column:last-child {
      padding-right: 32px;
      border-radius: 0 12px 0 0; }
    .wsx-lists-table-column-user_id {
      min-width: unset;
      padding-left: 16px;
      padding-right: 10px; }
    .wsx-lists-table-column-action {
      text-align: end;
      min-width: unset;
      width: 32px; }
      .wsx-lists-table-column-action .wsx-btn, .wsx-lists-table-column-action .wsx-3dot-wrapper {
        margin-left: auto; }
    .wsx-lists-table-column .wsx-ellipsis {
      max-width: 12rem;
      width: 90%; }
    .wsx-lists-table-column a {
      color: #6C6CFF;
      text-decoration: underline; }
      .wsx-lists-table-column a:hover {
        color: orchid; }
  .wsx-lists-table-body tr {
    border-bottom: 1px solid #E2E4E9; }
    .wsx-lists-table-body tr:last-child {
      border-bottom: 0; }
    .wsx-lists-table-body tr.wsx-lists-empty td {
      border-radius: 0 0 12px 12px;
      text-align: center; }
  .wsx-lists-table-body td {
    background-color: #ffffff; }
    .wsx-lists-table-body td:first-child {
      border-radius: 0 0 0 12px; }
    .wsx-lists-table-body td:last-child {
      border-radius: 0 0 12px 0; }

.wsx-checkbox-column {
  width: 20px;
  min-width: unset; }

.wsx-row-actions-list {
  padding: 8px 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 8px;
  color: #6B677A;
  white-space: nowrap;
  cursor: pointer; }
  .wsx-row-actions-list:hover {
    background-color: #F4F4FF;
    color: #07050b; }

.wsx-row-wrapper {
  background-color: #E7E7FF;
  border-radius: 12px;
  padding: 20px 24px 88px; }

.wsx-row-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  overflow-y: auto;
  margin-bottom: 48px; }

.wsx-rules-header, .wsx-rules-row {
  display: grid;
  grid-template-columns: 3fr 0.7fr repeat(3, 2fr) 1fr;
  gap: 20px;
  align-items: center;
  width: 96.44%;
  min-width: fit-content; }

.wsx-rules-header {
  padding: 10px 20px; }
  .wsx-rules-header > div {
    padding-right: 10px;
    white-space: nowrap; }

.wsx-rules-row {
  background-color: #ffffff;
  border-radius: 12px;
  padding: 12px 20px; }

.wsx-roles-wrapper .wsx-rules-header, .wsx-roles-wrapper .wsx-rules-row {
  grid-template-columns: 3fr repeat(2, 2fr) 1fr; }

.wsx-rule-item {
  height: 100%;
  max-height: 36px;
  min-width: 156px;
  padding: 2px 0;
  padding-right: 10px;
  border-right: 1px solid #DCDCEE;
  display: flex;
  align-items: center;
  color: #6B677A; }

.wsx-rule-checkbox-with-title {
  display: flex;
  align-items: center;
  gap: 16px;
  color: #070707; }

.wsx-rule-status {
  max-width: 76px;
  min-width: unset; }

.wsx-user-image {
  max-width: 44px;
  max-height: 44px;
  border-radius: 50%; }

.wsx-user-list-wrapper .wsx-input-wrapper {
  max-width: 250px;
  background-color: #ffffff;
  width: 100%;
  border-radius: 8px; }

.wsx-user-select-container .wsx-input-wrapper {
  max-width: 164px;
  width: max-content; }

.wsx-dropdown-actions-list {
  margin-bottom: 20px; }
  .wsx-dropdown-actions-list:last-child, .wsx-dropdown-actions-list:only-child {
    margin-bottom: 0; }

.wsx-email-lists .wsx-lists-table-column-email_type {
  text-transform: capitalize; }

.wsx-email-lists .wsx-lists-table-column .wsx-ellipsis {
  max-width: 20rem;
  width: 60%; }

.wsx-row-gap-48 {
  row-gap: 48px; }

.wsx-row-gap-40 {
  row-gap: 40px; }

.wsx-row-gap-32 {
  row-gap: 32px; }

.wsx-row-gap-30 {
  row-gap: 30px; }

.wsx-row-gap-24 {
  row-gap: 24px; }

.wsx-row-gap-20 {
  row-gap: 20px; }

.wsx-row-gap-16 {
  row-gap: 16px; }

.wsx-row-gap-12 {
  row-gap: 12px; }

.wsx-row-gap-8 {
  row-gap: 8px; }

.wsx-row-gap-6 {
  row-gap: 6px; }

.wsx-row-gap-4 {
  row-gap: 4px; }

.wsx-grow-3 {
  flex-grow: 3; }

.wsx-grow-2 {
  flex-grow: 2; }

.wsx-grow-1 {
  flex-grow: 1; }

.wsx-shrink-0 {
  flex-shrink: 0; }

.wsx-f-basis-50 {
  flex-basis: 50%; }

.wsx-f-basis-20 {
  flex-basis: 20%; }

.wsx-f-basis-17 {
  flex-basis: 17%; }

.wsx-f-basis-4 {
  flex-basis: 4%; }

.wsx-shadow-none {
  box-shadow: none; }

/* Responsive style */
@media (max-width: 1300px) {
  .wsx-dashboard {
    flex-wrap: wrap; }
    .wsx-dashboard .wsx-container-left, .wsx-dashboard .wsx-container-right {
      flex-basis: 100%; }
  .wsx-rules-header > div {
    min-width: 144px; }
    .wsx-rules-header > div.wsx-rule-checkbox-with-title {
      min-width: 156px; }
    .wsx-rules-header > div.wsx-rule-status {
      min-width: unset; } }

@media (max-width: 1200px) {
  .wsx-rules-header > div {
    min-width: 156px; }
  .wsx-tier-design-settings {
    border-right: 0;
    padding-right: 0;
    padding-bottom: 20px;
    border-bottom: 1px solid #070707; }
  .wsx-settings-design-container {
    flex-direction: column; }
  .wsx-preview-container {
    width: 52vw; } }

@media (max-width: 991px) {
  .wsx-chart-header {
    flex-wrap: wrap;
    white-space: nowrap; } }

@media (max-width: 836px) {
  .wsx-side-menu-wrapper {
    display: block; }
  .wsx-side-menu-list {
    display: flex;
    overflow-y: hidden;
    overflow-x: auto;
    max-width: unset; }
  .wsx-side-menu-item {
    padding: 16px 24px;
    border-bottom: 0;
    border-right: 1px solid #D5D5FA; }
    .wsx-side-menu-item:last-child {
      border: 0;
      margin: 0; }
  .wsx-side-menu-header {
    padding: 14px 16px; }
  .wsx-side-menu-body .wsx-title {
    font-size: 16px; }
  .wsx-side-menu-body .wsx-btn {
    padding: 8px 12px;
    font-size: 13px; }
  .wsx-lists-table-body tr.wsx-lists-empty td {
    text-align: start; }
  .wsx-preview-container {
    width: 74vw; }
  .wsx-slg-justify-wrapper {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center; }
  .wsx-slg-flex-wrap {
    flex-wrap: wrap; }
  .wsx-slg-center-hz {
    margin-left: auto;
    margin-right: auto; }
  .wsx-slg-w-full {
    width: 100%; }
  .wsx-slg-w-half {
    width: 50%; }
  .wsx-slg-w-fit {
    width: fit-content; }
  .wsx-slg-p-32 {
    padding: 32px; }
  .wsx-slg-p-24 {
    padding: 24px; }
  .wsx-slg-p-16 {
    padding: 16px; }
  .wsx-slg-p-10 {
    padding: 10px; }
  .wsx-slg-p-4 {
    padding: 4px; }
  .wsx-slg-p-0 {
    padding: 0px; }
  .wsx-slg-pt-48 {
    padding-top: 48px; }
  .wsx-slg-pt-32 {
    padding-top: 32px; }
  .wsx-slg-pt-20 {
    padding-top: 20px; }
  .wsx-slg-pb-48 {
    padding-bottom: 48px; }
  .wsx-slg-pb-32 {
    padding-bottom: 32px; }
  .wsx-slg-pb-20 {
    padding-bottom: 20px; } }

@media (max-width: 768px) {
  .wsx-chart-header div {
    flex-wrap: wrap;
    gap: 10px; }
  .wsx-tier-wrapper, .wsx-tier-header {
    grid-template-columns: repeat(4, 1fr);
    gap: 12px; }
  .wsx-md-d-block {
    display: block; }
  .wsx-md-d-flex {
    display: flex; }
  .wsx-md-d-none {
    display: none; }
  .wsx-md-flex-column {
    flex-direction: column; }
  .wsx-md-flex-column-reverse {
    flex-direction: column-reverse; }
  .wsx-md-flex-reverse {
    flex-direction: row-reverse; }
  .wsx-md-column-1, .wsx-md-column-2, .wsx-md-column-3 {
    display: grid; }
  .wsx-md-column-3 {
    grid-template-columns: repeat(3, 1fr); }
  .wsx-md-column-2 {
    grid-template-columns: repeat(2, 1fr); }
  .wsx-md-column-1 {
    grid-template-columns: 1fr; }
  .wsx-md-gap-48 {
    gap: 48px; }
  .wsx-md-gap-40 {
    gap: 40px; }
  .wsx-md-gap-32 {
    gap: 32px; }
  .wsx-md-gap-30 {
    gap: 30px; }
  .wsx-md-gap-24 {
    gap: 24px; }
  .wsx-md-gap-20 {
    gap: 20px; }
  .wsx-md-gap-16 {
    gap: 16px; }
  .wsx-md-gap-12 {
    gap: 12px; }
  .wsx-md-gap-8 {
    gap: 8px; }
  .wsx-md-gap-6 {
    gap: 6px; }
  .wsx-md-gap-4 {
    gap: 4px; }
  .wsx-md-important-gap-16 {
    gap: 16px !important; }
  .wsx-md-w-full {
    width: 100%; }
  .wsx-md-w-half {
    width: 50%; }
  .wsx-md-w-fit {
    width: fit-content; }
  .wsx-md-p-32 {
    padding: 32px; }
  .wsx-md-p-24 {
    padding: 24px; }
  .wsx-md-p-16 {
    padding: 16px; }
  .wsx-md-p-10 {
    padding: 10px; }
  .wsx-md-p-4 {
    padding: 4px; }
  .wsx-md-p-0 {
    padding: 0px; }
  .wsx-md-pt-48 {
    padding-top: 48px; }
  .wsx-md-pt-32 {
    padding-top: 32px; }
  .wsx-md-pt-20 {
    padding-top: 20px; }
  .wsx-md-pb-48 {
    padding-bottom: 48px; }
  .wsx-md-pb-32 {
    padding-bottom: 32px; }
  .wsx-md-pb-20 {
    padding-bottom: 20px; } }

@media (max-width: 638px) {
  .wsx-justify-wrapper {
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center; }
  .wsx-smd-gap-48 {
    gap: 48px; }
  .wsx-smd-gap-40 {
    gap: 40px; }
  .wsx-smd-gap-32 {
    gap: 32px; }
  .wsx-smd-gap-30 {
    gap: 30px; }
  .wsx-smd-gap-24 {
    gap: 24px; }
  .wsx-smd-gap-20 {
    gap: 20px; }
  .wsx-smd-gap-16 {
    gap: 16px; }
  .wsx-smd-gap-12 {
    gap: 12px; }
  .wsx-smd-gap-8 {
    gap: 8px; }
  .wsx-smd-gap-6 {
    gap: 6px; }
  .wsx-smd-gap-4 {
    gap: 4px; }
  .wsx-smd-important-gap-16 {
    gap: 16px !important; }
  .wsx-smd-p-32 {
    padding: 32px; }
  .wsx-smd-p-24 {
    padding: 24px; }
  .wsx-smd-p-16 {
    padding: 16px; }
  .wsx-smd-p-10 {
    padding: 10px; }
  .wsx-smd-p-4 {
    padding: 4px; }
  .wsx-smd-p-0 {
    padding: 0px; }
  .wsx-smd-pt-48 {
    padding-top: 48px; }
  .wsx-smd-pt-32 {
    padding-top: 32px; }
  .wsx-smd-pt-20 {
    padding-top: 20px; }
  .wsx-smd-pb-48 {
    padding-bottom: 48px; }
  .wsx-smd-pb-32 {
    padding-bottom: 32px; }
  .wsx-smd-pb-20 {
    padding-bottom: 20px; } }

@media (max-width: 576px) {
  .wsx-sales-chart-legend {
    flex-direction: column;
    align-items: start;
    margin-left: auto; }
  .wsx-condition-container .wsx-tier-wrapper {
    grid-template-columns: 1fr; }
  .wsx-sm-gap-48 {
    gap: 48px; }
  .wsx-sm-gap-40 {
    gap: 40px; }
  .wsx-sm-gap-32 {
    gap: 32px; }
  .wsx-sm-gap-30 {
    gap: 30px; }
  .wsx-sm-gap-24 {
    gap: 24px; }
  .wsx-sm-gap-20 {
    gap: 20px; }
  .wsx-sm-gap-16 {
    gap: 16px; }
  .wsx-sm-gap-12 {
    gap: 12px; }
  .wsx-sm-gap-8 {
    gap: 8px; }
  .wsx-sm-gap-6 {
    gap: 6px; }
  .wsx-sm-gap-4 {
    gap: 4px; }
  .wsx-sm-important-gap-16 {
    gap: 16px !important; }
  .wsx-sm-flex-column {
    flex-direction: column; }
  .wsx-sm-flex-column-reverse {
    flex-direction: column-reverse; }
  .wsx-sm-flex-reverse {
    flex-direction: row-reverse; }
  .wsx-sm-m-0 {
    margin: 0; }
  .wsx-sm-mt-0 {
    margin-top: 0; }
  .wsx-sm-p-32 {
    padding: 32px; }
  .wsx-sm-p-24 {
    padding: 24px; }
  .wsx-sm-p-16 {
    padding: 16px; }
  .wsx-sm-p-10 {
    padding: 10px; }
  .wsx-sm-p-4 {
    padding: 4px; }
  .wsx-sm-p-0 {
    padding: 0px; }
  .wsx-sm-pt-48 {
    padding-top: 48px; }
  .wsx-sm-pt-32 {
    padding-top: 32px; }
  .wsx-sm-pt-20 {
    padding-top: 20px; }
  .wsx-sm-pb-48 {
    padding-bottom: 48px; }
  .wsx-sm-pb-32 {
    padding-bottom: 32px; }
  .wsx-sm-pb-20 {
    padding-bottom: 20px; } }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/Common.scss"],"names":[],"mappings":"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","sourcesContent":["/* Variables */\r\n// color_variables\r\n$color-primary: #6C6CFF;\r\n$color-secondary: #FEAD01;\r\n$color-tertiary: #070707;\r\n$color-light: #868C98;\r\n\r\n$color-negative: #FF2F54;\r\n$color-positive: #62C55A;\r\n$color-blue-dark: #0A0D14;\r\n$color-sky: #5CADFF;\r\n$color-orchid: #A367F0;\r\n$color-mint : #52D47F;\r\n$color-lime: #94C752;\r\n$color-pink: #ff176b;\r\n$color-indigo: #292461;\r\n$color-gray: #F6F8FA;\r\n\r\n$color-base-one: #ffffff;\r\n$color-base-two: #E7E7FF;\r\n$color-base-three: #F4F4FF;\r\n\r\n$color-text-dark: #07050b;\r\n$color-text-medium: #343A46;\r\n$color-text-light: #6B677A;\r\n$color-text-reverse: #ffffff;\r\n$color-text-body: #87858d;\r\n\r\n$color-border-primary: #E2E4E9;\r\n$color-border-secondary: #DCDCEE;\r\n$color-border-tertiary: #D5D5FA;\r\n$color-border-light: #eeeeee;\r\n\r\n$color-shadow-primary: rgba(78, 46, 206, 0.16);\r\n$color-shadow-dark: rgba(78, 46, 206, 0.161);\r\n$color-shadow-tertiary: rgba(7, 7, 7, 0.5);\r\n\r\n// border_variables\r\n$border-radius-xl: 16px;\r\n$border-radius-lg: 12px;\r\n$border-radius-md: 8px;\r\n$border-radius-sm: 4px;\r\n\r\n// button_variables\r\n$btn-font-size: 16px;\r\n$btn-font-weight: 500;\r\n$btn-line-height: 1.429em;\r\n$btn-vertical-padding: 10px;\r\n$btn-horizontal-padding: 20px;\r\n\r\n// animation_variables\r\n$transition-md: 0.3s;\r\n\r\n// typography_variables\r\n$body-font-family: 'Inter', sans-serif;\r\n$btn-font-family: 'Inter', sans-serif;\r\n\r\n$font-weight-bold: 600;\r\n$font-weight-medium: 500;\r\n$font-weight-regular: 400;\r\n\r\n\r\n@function hex-to-rgba($hex, $opacity) {\r\n    $red: red($hex);\r\n    $green: green($hex);\r\n    $blue: blue($hex);\r\n    @return rgba($red, $green, $blue, $opacity);\r\n}\r\n\r\n\r\n/* Default Style */\r\nbody {\r\n    font-family: $body-font-family;\r\n    font-size: 14px;\r\n    line-height: 1.42857em;\r\n    color: $color-text-body;\r\n}\r\n\r\na {\r\n    text-decoration: none;\r\n    color: initial;\r\n    &:hover {\r\n        color: $color-primary;\r\n    }\r\n    &:focus {\r\n        box-shadow: none;\r\n        outline: none;\r\n        color: initial;\r\n    }\r\n    &.wsx-color-primary:hover {\r\n        color: $color-secondary;\r\n    }\r\n}\r\nimg {\r\n    max-width: 100%;\r\n    height: auto;\r\n}\r\n\r\n\r\ninput,\r\ninput[type=text], \r\ninput[type=number], \r\ninput[type=email],\r\ninput[type=url],\r\ninput[type=password],\r\ntextarea, \r\n.wp-core-ui select {\r\n    outline: none;\r\n    border: 1px solid $color-border-primary;\r\n    border-radius: $border-radius-md;\r\n    box-shadow: none;\r\n    background-color: transparent;\r\n    width: 100%;\r\n    min-height: 40px;\r\n    height: 100%;\r\n    margin: 0;\r\n    padding: 0 8px;\r\n    &:focus {\r\n        outline: none;\r\n        box-shadow: none;\r\n        border-color: $color-primary;\r\n    }\r\n    &.wsx-bg-base1 {\r\n        background-color: $color-base-one;\r\n    }\r\n}\r\n.wp-core-ui select {\r\n    position: relative;\r\n    max-width: unset;\r\n    background: transparent url('../../../../assets/img/angle-down-16.svg') no-repeat right 14px top 55%;\r\n    background-size: 16px 16px;\r\n    padding: 0 32px 0 8px;\r\n}\r\ntextarea {\r\n    padding: 10px 8px;\r\n}\r\n\r\ncode, kbd {\r\n    padding: 1px 6px 2px;\r\n    background-color: $color-border-primary;\r\n    font-size: 12px;\r\n    line-height: 1.334em;\r\n    font-style: italic;\r\n    color: $color-text-medium;\r\n    margin-top: 4px;\r\n    display: block;\r\n    width: fit-content;\r\n}\r\n\r\n/* Dashboard Style */\r\n.wsx-dashboard {\r\n    display: flex;\r\n    gap: 48px;\r\n    .wsx-container-left {\r\n        flex-basis: 76%;\r\n    }\r\n    .wsx-container-right {\r\n        flex-basis: 24%;\r\n    }\r\n    &-header {\r\n        position: relative;\r\n    }\r\n}\r\n\r\n/* License Style */\r\n.wsx-upgrade-license-wrapper {\r\n    display: flex;\r\n    gap: 20px;\r\n    align-items: start;\r\n    text-align: start;\r\n    margin-top: 32px;\r\n}\r\n\r\n/* Settings Style */\r\n.wsx-settings {\r\n    &-item {\r\n        .wsx-accordion-body {\r\n            display: flex;\r\n            flex-direction: column;\r\n            gap: 30px;\r\n        }\r\n        input {\r\n            padding: 0px 16px;\r\n        }\r\n        textarea {\r\n            padding: 10px 16px;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-design_two {\r\n    .wsx-column-1fr-2fr {\r\n        display: block;\r\n    }\r\n}\r\n.wsx-design_three {\r\n    display: grid;\r\n    gap: 32px;\r\n    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));\r\n}\r\n\r\n/* Support Style */\r\n.wsx-support-form {\r\n    input, textarea {\r\n        padding: 10px 16px;\r\n    }\r\n}\r\n\r\n/* Addon Style */\r\n.wsx-addon-card-container {\r\n    width: 100%;\r\n    box-sizing: border-box;\r\n    position: relative;\r\n    background-color: $color-base-one;\r\n    box-shadow: 0px 2px 4px 0px $color-shadow-primary;\r\n    border-radius: $border-radius-lg;\r\n    \r\n    .wsx-addon-card-body {\r\n        padding: 32px 32px 90px 32px;\r\n        position: relative;\r\n    }\r\n\r\n    .wsx-addon-card-header {\r\n        img {\r\n            max-width: 48px;\r\n        }\r\n    }\r\n\r\n    .wsx-addon-card-footer {\r\n        padding: 16px 32px;\r\n        display: flex;\r\n        gap: 10px;\r\n        align-items: center;\r\n        justify-content: space-between;\r\n        border-top: 1px solid $color-border-primary;\r\n        position: absolute;\r\n        left: 0;\r\n        right: 0;\r\n        bottom: 0;\r\n        background-color: $color-base-one;\r\n        border-radius: 0 0 $border-radius-lg $border-radius-lg;\r\n    }\r\n}\r\n.wsx-pro-active-card {\r\n    border-radius: $border-radius-lg;\r\n    box-shadow: 0 1px 2px 0 $color-shadow-primary;\r\n    background-color: $color-base-one;\r\n    padding: 32px;\r\n    display: flex;\r\n    flex-wrap: wrap;\r\n    gap: 40px;\r\n    width: 90%;\r\n    max-width: 1300px;\r\n    margin: 0 auto;\r\n    &-content {\r\n        width: 34vw;\r\n        @media (max-width: 1132px) {\r\n            width: 100%;\r\n        }\r\n    }\r\n    img {\r\n        max-width: 450px;\r\n    }\r\n    @media (max-width: 1132px) {\r\n        text-align: center;\r\n        .wsx-list {\r\n            width: fit-content;\r\n        }\r\n        .wsx-btn, .wsx-list, img {\r\n            margin-left: auto;\r\n            margin-right: auto;\r\n        }\r\n    }\r\n}\r\n\r\n\r\n// Sales Card Style\r\n.wsx-sales-card {\r\n\r\n    .wsx-growth-wrapper {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 8px;\r\n        padding: 2px 6px;\r\n        border-radius: 2px;\r\n        &.wsx-color {\r\n            &-sky {\r\n                background-color: hex-to-rgba($color-sky, 0.1); \r\n            }\r\n            &-orchid {\r\n                background-color: hex-to-rgba($color-orchid, 0.1); \r\n            }\r\n            &-primary {\r\n                background-color: hex-to-rgba($color-primary, 0.1); \r\n            }\r\n            &-mint {\r\n                background-color: hex-to-rgba($color-mint, 0.1); \r\n            }\r\n            &-secondary {\r\n                background-color: hex-to-rgba($color-secondary, 0.1); \r\n            }\r\n            &-lime {\r\n                background-color: hex-to-rgba($color-lime, 0.1); \r\n            }\r\n        }\r\n    }\r\n    .wsx-sales-growth {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 8px;\r\n        margin-top: 12px;\r\n    }\r\n}\r\n\r\n// Chart Style\r\n.wsx-chart {\r\n    &-wrapper {\r\n        .wsx-card {\r\n            padding: 24px 24px 32px;\r\n        }\r\n    }\r\n    &-header {\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: space-between;\r\n        gap: 10px;\r\n        padding: 0 8px;\r\n        margin-bottom: 28px;\r\n    }\r\n    margin-left: -8px;\r\n    .recharts-cartesian-axis-ticks {\r\n        text {\r\n            transform: translateX(-8px);\r\n        }\r\n        tspan {\r\n            font-size: 12px;\r\n            line-height: 16px;\r\n            stroke: $color-text-medium;\r\n            stroke-width: 0.3;\r\n        }\r\n    }\r\n    .recharts-xAxis .recharts-cartesian-axis-ticks {\r\n        text {\r\n            transform: translateY(16px);\r\n        }\r\n        tspan {\r\n            stroke: $color-text-light;\r\n            stroke-width: 0.2;\r\n        }\r\n    }\r\n    .recharts-cartesian-axis, .recharts-cartesian-grid-horizontal {\r\n        line {\r\n            stroke: $color-border-primary;\r\n            stroke-dasharray: 0;\r\n        }\r\n    }\r\n}\r\n\r\n\r\n/* Side Menu Style  */\r\n.wsx-side-menu {\r\n    &-wrapper {\r\n        display: flex;\r\n        align-items: stretch;\r\n        border-radius: $border-radius-lg;\r\n        box-shadow: 0 2px 4px 0 $color-shadow-primary;\r\n        overflow: hidden;\r\n        white-space: nowrap;\r\n    }\r\n    &-list {\r\n        background-color: $color-base-two;\r\n        max-width: 232px;\r\n        width: 100%;\r\n    }\r\n    &-body {\r\n        background-color: $color-base-one;\r\n        width: 100%;\r\n    }\r\n    &-item {\r\n        cursor: pointer;\r\n        padding: 24px 32px;\r\n        color: $color-tertiary;\r\n        font-weight: $font-weight-medium;\r\n        border-bottom: 1px solid $color-border-tertiary;\r\n        &:hover, &.active {\r\n            background-color: $color-primary;\r\n            color: $color-text-reverse;\r\n        }\r\n        &:last-child {\r\n            margin-bottom: 100px;\r\n        }\r\n    }\r\n    &-header {\r\n        display: flex;\r\n        gap: 10px;\r\n        align-items: center;\r\n        justify-content: space-between;\r\n        padding: 16px 40px;\r\n        border-bottom: 1px solid $color-border-tertiary;\r\n    }\r\n    &-content {\r\n        padding: 50px 40px 100px;\r\n        display: flex;\r\n        flex-direction: column;\r\n        gap: 40px;\r\n    }\r\n}\r\n\r\n\r\n/* Component Style */\r\n// Drag and Drop Style\r\n.wsx-drag-drop-file {\r\n    &-upload-container {\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        flex-direction: column;\r\n        gap: 4px;\r\n        border: 2px dashed $color-primary;\r\n        border-radius: $border-radius-lg;\r\n        background-color: $color-base-three;\r\n        width: auto;\r\n        max-width: 95%;\r\n        height: auto;\r\n        min-height: 195px;\r\n        padding: 16px 20px 20px;\r\n        cursor: pointer;\r\n        transition: all $transition-md;\r\n        &:hover, &.active {\r\n            background-color: $color-base-two;\r\n            .wsx-icon {\r\n                color: $color-primary;\r\n            }\r\n        }\r\n    }\r\n    &-details {\r\n        background-color: $color-gray;\r\n        border-radius: $border-radius-lg;\r\n        padding: 16px;\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: space-between;\r\n        gap: 10px;\r\n        border: 1px solid $color-border-primary;\r\n    }\r\n} \r\n\r\n\r\n// FontSize Style\r\n.wsx-font {\r\n    &-container {\r\n        display: flex;\r\n        align-items: center;\r\n        border: 1px solid $color-text-light;\r\n        border-radius: $border-radius-md;\r\n        overflow: hidden;\r\n    }\r\n    &-value {\r\n        width: 48px;\r\n        text-align: center;\r\n        font-size: 14px;\r\n        line-height: 20px;\r\n        background-color: $color-base-one;\r\n        color: $color-text-light;\r\n    }\r\n}\r\n\r\n\r\n// Custom Sales Chart Style\r\n.wsx-chart-wrapper {\r\n    margin-bottom: 40px;\r\n}\r\n\r\n.wsx-sales-chart-container {\r\n    width: 100%;\r\n}\r\n  \r\n.wsx-sales-chart-header {\r\n    align-items: center;\r\n    gap: 10px;\r\n    margin-bottom: 12px;\r\n    white-space: nowrap;\r\n    display: grid;\r\n    grid-template-columns: 1fr 2fr;\r\n}\r\n\r\n.wsx-sales-chart-legend {\r\n    display: flex;\r\n    flex-wrap: wrap;\r\n    align-items: center;\r\n    justify-content: end;\r\n    gap: 16px;\r\n}\r\n\r\n.wsx-sales-chart-wrapper {\r\n    position: relative;\r\n    width: 100%;\r\n}\r\n\r\n.wsx-sales-chart-canvas {\r\n    cursor: pointer;\r\n    border: 1px solid $color-base-two;\r\n}\r\n\r\n.wsx-sales-chart-tooltip {\r\n    position: absolute;\r\n    z-index: 10;\r\n    background-color: $color-base-one;\r\n    border: 1px solid $color-border-primary;\r\n    border-radius: $border-radius-md;\r\n    box-shadow: 0 16px 32px -16px hex-to-rgba($color-text-light, 0.1);\r\n    transform: translate(-50%, -100%);\r\n    pointer-events: none;\r\n    overflow: hidden;\r\n}\r\n\r\n.wsx-sales-chart-tooltip-date {\r\n    font-size: 12px;\r\n    color: $color-text-medium;\r\n    padding: 8px;\r\n    background-color: $color-gray;\r\n    border-bottom: 1px solid $color-border-primary;\r\n}\r\n\r\n.wsx-sales-chart-tooltip-content {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 8px;\r\n    padding: 12px;\r\n    background-color: $color-base-one;\r\n}\r\n\r\n.wsx-sales-chart-tooltip-item {\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: space-between;\r\n    gap: 8px;\r\n    font-size: 14px;\r\n}\r\n\r\n.wsx-sales-chart-tooltip-dot {\r\n    width: 12px;\r\n    height: 12px;\r\n    border-radius: 50%;\r\n    flex-shrink: 0;\r\n}\r\n\r\n.wsx-sales-chart-tooltip-label {\r\n    font-size: 12px;\r\n    color: $color-text-light;\r\n}\r\n\r\n.wsx-sales-chart-tooltip-value {\r\n    font-weight: $font-weight-medium;\r\n    font-size: 12px;\r\n    color: $color-text-dark;\r\n}\r\n\r\n// Calendar Style \r\n.wsx-date-picker {\r\n    &-container {\r\n        width: 100%;\r\n        .react-date-picker__wrapper {\r\n            border: 1px solid $color-border-primary;\r\n            border-radius: $border-radius-md;\r\n            background-color: $color-base-one;\r\n            padding: 3.5px 16px;\r\n            input {\r\n                &[type=number] {\r\n                    border: none;\r\n                    min-height: 20px;\r\n                    border-radius: 0;\r\n                    padding: 0 4px;\r\n                    &:focus {\r\n                        border: none;\r\n                    }\r\n                }\r\n                &[type=date] {\r\n                    min-height: 20px;\r\n                }\r\n            }\r\n        }\r\n\r\n        input {\r\n            &[type=\"text\"], &:focus[type=\"text\"] {\r\n                min-width: auto;\r\n                line-height: 16px;\r\n                padding: 0 4px;\r\n                &.wsx-input {\r\n                    &-month {\r\n                        width: 34px;\r\n                        &:not(:placeholder-shown) {\r\n                            width: 24px;\r\n                        }\r\n                    }\r\n                    &-day {\r\n                        width: 26px;\r\n                    }\r\n                    &-year {\r\n                        width: 120px;\r\n                    }\r\n                }\r\n            }\r\n        }\r\n    }\r\n    &-wrapper {\r\n        justify-content: space-between;\r\n        padding: 0 8px;\r\n        .wsx-icon {\r\n            padding: 4px;\r\n            &.wsx-btn-action {\r\n                background-color: transparent;\r\n                &:hover {\r\n                    background-color: transparent;\r\n                }\r\n            }\r\n        }\r\n    }\r\n    &-calendar {\r\n        box-shadow: 0px 1px 10px 3px $color-shadow-dark;\r\n        .wsx-days {\r\n            row-gap: 0;\r\n        }\r\n        .wsx-day {\r\n            border-radius: 50%;\r\n            padding: 12px;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-date-range {\r\n    &-width-selector {\r\n        position: absolute;\r\n        top: calc(100% + 8px);\r\n        right: 0;\r\n        z-index: 9;\r\n        width: 844px;\r\n        max-width: 92%;\r\n        display: none;\r\n        justify-content: end;\r\n        @media (max-width: 617px) {\r\n            left: 0;     \r\n            margin: 0 auto;       \r\n        }\r\n    }\r\n    &-wrapper {\r\n        // position: absolute;\r\n        // top: calc(100% + 8px);\r\n        // right: 0;\r\n        // z-index: 9;\r\n        width: max-content;\r\n        max-width: 82%;\r\n        padding: 20px;\r\n        margin-bottom: 0;\r\n        // display: none;\r\n        @media (max-width: 617px) {\r\n            margin: 0 auto;\r\n        }\r\n        .screen-reader-text {\r\n            display: none;\r\n        }\r\n        .woocommerce-segmented-selection__container {\r\n            width: max-content;\r\n            background-color: $color-gray;\r\n            gap: 8px;\r\n            padding: 16px;\r\n            border-radius: $border-radius-md;\r\n            border: none;\r\n        }\r\n        .woocommerce-segmented-selection__item {\r\n            position: relative;\r\n            cursor: pointer;\r\n            border: none !important;\r\n            input {\r\n                position: absolute;\r\n                visibility: hidden;\r\n            }\r\n        }\r\n        .woocommerce-segmented-selection__input:active+label .woocommerce-segmented-selection__label {\r\n            background-color: $color-gray;\r\n        }\r\n        .woocommerce-segmented-selection__input:checked+label {\r\n            .woocommerce-segmented-selection__label {\r\n                background-color: $color-tertiary;\r\n                color: $color-text-reverse;\r\n                &::before {\r\n                    content: none;\r\n                }\r\n            }\r\n        }\r\n        .woocommerce-segmented-selection__label {\r\n            cursor: pointer;\r\n            padding: 10px 27px;\r\n            height: auto;\r\n            text-align: center;\r\n            border-radius: $border-radius-md;\r\n            background-color: $color-base-one;\r\n            color: $color-text-light;\r\n            font-size: 12px;\r\n            line-height: 1.34em;\r\n            font-weight: $font-weight-medium;\r\n            &::before {\r\n                content: none;\r\n            }\r\n        }\r\n    }\r\n    &-title {\r\n        font-weight: $font-weight-medium;\r\n        text-align: center;\r\n        // margin-bottom: 20px;\r\n    }\r\n}\r\n\r\n.active .wsx-date-range-width-selector {\r\n    display: flex;\r\n}\r\n\r\n.wsx-calendar {\r\n    &-range-wrapper {\r\n        border: 1px solid $color-tertiary;\r\n        border-radius: $border-radius-md;\r\n    }\r\n    &-header {\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        padding: 12px 32px;\r\n        border-bottom: 1px solid $color-border-primary;\r\n    }\r\n    &-title {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 12px;\r\n        svg {\r\n            color: $color-blue-dark;\r\n        }\r\n    }\r\n    &-value {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 20px;\r\n        .wsx-input-wrapper-with-icon .wsx-icon {\r\n            padding: 6px;\r\n            svg {\r\n                width: 16px;\r\n                height: 16px;\r\n            }\r\n        }\r\n        input, .wsx-date-input-wrapper {\r\n            max-width: 140px;\r\n            min-height: auto;\r\n        }\r\n        @media (max-width: 756px) {\r\n            flex-direction: column;\r\n            gap: 12px;\r\n            width: fit-content;\r\n            align-items: end;\r\n        }\r\n    }\r\n    &-container {\r\n        display: flex;\r\n    }\r\n    padding: 16px 0px 12px;\r\n    margin: 0 16px;\r\n    border-top: 1px solid $color-border-primary;\r\n}\r\n.wsx-control-wrapper {\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 10px;\r\n    padding: 16px 0;\r\n    margin: 0 16px;\r\n    .wsx-month {\r\n        width: 100%;\r\n        text-align: center;\r\n        font-size: 18px;\r\n        line-height: 1.4em;\r\n        font-weight: $font-weight-medium;\r\n        @media (max-width: 480px) {\r\n            font-size: 18px;\r\n        }\r\n    }\r\n    .wsx-calendar-controller {\r\n        cursor: pointer;\r\n        &:hover {\r\n            color: $color-primary;\r\n        }\r\n    }\r\n}\r\n.wsx-days-of-week,\r\n.wsx-days {\r\n    display: grid;\r\n    grid-template-columns: repeat(7, 1fr);\r\n    row-gap: 10px;\r\n}\r\n.wsx-day {\r\n    &-name {\r\n        text-align: center;\r\n        font-size: 12px;\r\n        line-height: 1.34em;\r\n        font-weight: $font-weight-medium;\r\n        text-transform: uppercase;\r\n        margin-bottom: 16px;\r\n        padding: 2px 8px;\r\n        @media (max-width: 480px) {\r\n            padding: 2px 4px;\r\n        }\r\n    }\r\n    // padding: 10px 14px;\r\n    padding: 6px 10px;\r\n    text-align: center;\r\n    font-size: 14px;\r\n    line-height: 1.34em;\r\n    color: $color-blue-dark;\r\n    @media (max-width: 480px) {\r\n        padding: 4px 6px;\r\n        font-size: 16px;\r\n    }\r\n    &.active {\r\n        cursor: pointer;\r\n        &:hover {\r\n            background-color: $color-base-three;\r\n            color: $color-text-medium;\r\n        }\r\n    }\r\n    &.in-range, &.hovered-range {\r\n        background-color: $color-base-two;\r\n        &:hover {\r\n            background-color: $color-base-two;\r\n        }\r\n    }\r\n    &.start, &.end {\r\n        background-color: $color-primary;\r\n        color: $color-text-reverse;\r\n        &:hover {\r\n            background-color: $color-primary;\r\n            color: $color-text-reverse;\r\n        }\r\n    }\r\n    &.start {\r\n        border-radius: 8px 0 0 8px;\r\n        &.end {\r\n            border-radius: 8px;\r\n        }\r\n    }\r\n    &.end {\r\n        border-radius: 0 8px 8px 0;\r\n    }\r\n\r\n    &.selected {\r\n        background-color: $color-primary;\r\n        color: $color-text-reverse;\r\n        &:hover {\r\n            background-color: $color-primary;\r\n            color: $color-text-reverse;\r\n        }\r\n    }\r\n    &.today {\r\n        background-color: $color-secondary;\r\n        color: $color-text-reverse;\r\n        &:hover {\r\n            background-color: $color-secondary;\r\n            color: $color-text-reverse;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-option {\r\n    &-container {\r\n        position: absolute;\r\n        left: 0;\r\n        z-index: 10;\r\n        min-width: max-content;\r\n        width: 100%;\r\n        overflow-x: hidden;\r\n        overflow-y: auto;\r\n        max-height: 60vh;\r\n        box-shadow: 0 2px 4px 0 $color-shadow-primary;\r\n    }\r\n    &-item {\r\n        padding: 8px;\r\n        cursor: pointer;\r\n        &:last-child {\r\n            border-bottom: 0;\r\n        }\r\n        &:hover, &.active {\r\n            background-color: $color-primary;\r\n            color: $color-text-reverse;\r\n        }\r\n    }\r\n}\r\n\r\n// Multiselect Style\r\n.wsx-selected-field-wrapper {\r\n    width: 100%;\r\n    &.wsx-multiple-field {\r\n        width: 50%;\r\n    }\r\n}\r\n\r\n// Button Style\r\n.wsx-btn {\r\n    &-group {\r\n        display: flex;\r\n        align-items: center;\r\n        &.wsx-center {\r\n            justify-content: center;\r\n            margin: 0 auto;\r\n        }\r\n    }\r\n    opacity: 1;\r\n\r\n    .wsx-icon {\r\n        &.wsx-color {\r\n            &-primary {\r\n                color: $color-primary;\r\n                &:hover {\r\n                    color: $color-secondary;\r\n                }\r\n            }\r\n            &-secondary {\r\n                color: $color-secondary;\r\n                &:hover {\r\n                    color: $color-primary;\r\n                }\r\n            }\r\n        }\r\n    }\r\n\r\n    &-rescale {\r\n        background-color: $color-base-two;\r\n        border: none;\r\n        color: $color-text-medium;\r\n        width: 32px;\r\n        height: 32px;\r\n        font-size: 18px;\r\n        font-weight: $font-weight-bold;\r\n        cursor: pointer;\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        line-height: 0;\r\n        &-left {\r\n            border-right: 1px solid $color-text-light;\r\n        }\r\n        &-right {\r\n            border-left: 1px solid $color-text-light;\r\n        }\r\n        &:hover {\r\n            background-color: $color-tertiary;\r\n            color: $color-text-reverse;\r\n        }\r\n    }\r\n\r\n    &.wsx-color {\r\n        &-primary {\r\n            color: $color-primary;\r\n        }\r\n        &-text-medium {\r\n            color: $color-text-medium;\r\n        }\r\n        &-text-light {\r\n            color: $color-text-light;\r\n        }\r\n        &-text-reverse {\r\n            color: $color-text-reverse;\r\n        }\r\n        &-blue-dark {\r\n            color: $color-blue-dark;\r\n        }\r\n    }\r\n\r\n    &.wsx-bg {\r\n        &-primary, &-negative {\r\n            &:hover, &.active {\r\n                background-color: $color-secondary;\r\n                border-color: $color-secondary;\r\n            }\r\n        }\r\n        &-secondary {\r\n            color: $color-tertiary;\r\n            &:hover, &.active {\r\n                background-color: $color-primary;\r\n                color: $color-text-reverse;\r\n                border-color: $color-primary;\r\n            }\r\n        }\r\n        &-tertiary, &-positive {\r\n            &:hover, &.active {\r\n                background-color: $color-primary;\r\n                border-color: $color-primary;\r\n            }\r\n        }\r\n    }\r\n\r\n    &.disable {\r\n        cursor: not-allowed;\r\n        opacity: 0.4;\r\n        pointer-events: none;\r\n    }\r\n\r\n    &-action {\r\n        background-color: $color-base-three;\r\n        border-radius: $border-radius-md;\r\n        padding: 8px;\r\n        color: $color-tertiary;\r\n        line-height: 0;\r\n        transition: all $transition-md;\r\n        display: inline-flex; \r\n        align-items: center; \r\n        box-sizing: border-box;\r\n        cursor: pointer;\r\n        &:hover {\r\n            color: $color-primary;\r\n            background-color: $color-base-two;\r\n        }\r\n    }\r\n}\r\n\r\n// Color Picker Style\r\n.wsx-color-picker {\r\n    &-container {\r\n        border-radius: $border-radius-sm;\r\n        border: 1px solid $color-border-primary;\r\n        width: fit-content;\r\n        min-width: 140px;\r\n        overflow: hidden;\r\n        background-color: $color-base-one;\r\n    }\r\n    &-label {\r\n        padding: 10px 16px;\r\n        color: $color-text-medium;\r\n        text-transform: uppercase;\r\n    }\r\n}\r\ninput[type=color].wsx-color-picker-input {\r\n    outline: 0;\r\n    box-shadow: none;\r\n    padding: 0;\r\n    width: 40px;\r\n    height: 40px;\r\n    border-radius: 0;\r\n    border: 0;\r\n    border-right: 1px solid $color-border-primary;\r\n    &::-webkit-color-swatch-wrapper {\r\n        padding: 0;\r\n    }\r\n    &::-webkit-color-swatch {\r\n        border: none;\r\n    }\r\n}\r\n\r\n// Alert Modal Popup\r\n.wsx-alert {\r\n    &-popup-overlay {\r\n        position: fixed;\r\n        top: 0;\r\n        left: 0;\r\n        width: 100%;\r\n        height: 100%;\r\n        background-color: $color-shadow-primary;\r\n        display: flex;\r\n        justify-content: center;\r\n        align-items: center;\r\n        z-index: 9999;\r\n    }\r\n    &-popup {\r\n        background-color: $color-base-one;\r\n        border-radius: $border-radius-lg;\r\n        width: 90%;\r\n        max-width: 400px;\r\n        padding: 16px;\r\n        box-shadow: 0 4px 8px $color-shadow-primary;\r\n        text-align: center;\r\n        border: 1px solid $color-border-primary;\r\n    }\r\n    &-header {\r\n        display: flex;\r\n        justify-content: space-between;\r\n        align-items: center;\r\n        margin-bottom: 15px;\r\n        border-bottom: 1px solid $color-border-primary;\r\n        padding-bottom: 12px;\r\n    }\r\n    &-title-wrapper {\r\n        font-weight: $font-weight-medium;\r\n        color: $color-tertiary;\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 8px;\r\n    }\r\n    &-body {\r\n        margin-bottom: 20px;\r\n        font-size: 12px;\r\n        line-height: 16px;\r\n        text-align: left;\r\n        color: $color-text-medium;\r\n    }\r\n    &-footer {\r\n        display: flex;\r\n        justify-content: flex-end;\r\n        align-items: center;\r\n        gap: 12px;\r\n    }\r\n}\r\n \r\n\r\n.wsx-drag {\r\n    &-item {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 12px;\r\n        width: 100%;\r\n        max-height: 50px;\r\n        box-sizing: border-box;\r\n        padding: 8px 16px;\r\n        margin-bottom: 8px;\r\n        border-radius: 8px;\r\n        border: 1px solid $color-border-primary;\r\n        background-color: $color-base-one;\r\n        cursor: move;\r\n        &:last-child {\r\n            margin-bottom: 0;\r\n        }\r\n        &:hover {\r\n            border-color: $color-primary;\r\n        }\r\n        &-value {\r\n            max-width: 160px;\r\n        }\r\n    }\r\n}\r\n\r\n// Pagination Style\r\n.wsx-pagination-wrapper {\r\n    width: 100%;\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: space-between;\r\n    white-space: nowrap;\r\n    .wsx-btn {\r\n        width: 30px;\r\n        height: 30px;\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        padding: 0;\r\n        &:hover {\r\n            background-color: $color-tertiary;\r\n            span {\r\n                color: $color-text-reverse;\r\n            }\r\n        }\r\n    }\r\n}\r\nselect.wsx-pagination-option-list {\r\n    background-color: $color-base-one;\r\n    border: 1px solid $color-border-secondary;\r\n    padding: 1px 36px 1px 12px;\r\n    border-radius: $border-radius-md;\r\n    color: $color-text-medium;\r\n    min-height: 31px;\r\n    padding-top: 4px;\r\n    &:focus {\r\n        outline: none;\r\n        box-shadow: none;\r\n        border-color: $color-border-secondary;\r\n        color: $color-primary;\r\n    }\r\n}\r\n\r\n// Toast Message Style \r\n.wsx-toast {\r\n    &-wrapper {\r\n        position: fixed;\r\n        z-index: 99999;\r\n        top: 110px;\r\n        right: 15px;\r\n    }\r\n    &-message-wrapper {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 32px;\r\n        padding: 12px;\r\n        border-radius: 12px;\r\n        color: $color-text-reverse;\r\n        box-shadow: 0 0 12px 0 $color-shadow-tertiary;\r\n    }\r\n    &-close {\r\n        cursor: pointer;\r\n        &:hover {\r\n            color: $color-tertiary;\r\n        }\r\n    }\r\n}\r\n\r\n\r\n// ShortCode Style\r\n.wsx-shortcode-field-content {\r\n    padding: 4px;\r\n    background-color: $color-base-two;\r\n    border-radius: $border-radius-md;\r\n    border: 1px solid $color-border-tertiary;\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 32px;\r\n    width: fit-content;\r\n    .wsx-icon-wrapper {\r\n        padding: 4px;\r\n        color: $color-text-reverse;\r\n        border-radius: $border-radius-sm;\r\n        background-color: $color-primary;\r\n        &:hover {\r\n            background-color: $color-secondary;\r\n        }\r\n    }\r\n    &:hover {\r\n        background-color: $color-base-three;\r\n    }\r\n}\r\n.wsx-get-shortcode-text {\r\n    color: $color-text-medium;\r\n    padding-left: 12px;\r\n}\r\n.wsx-shortcode {\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: space-between;\r\n    gap: 8px;\r\n    width: 100%;\r\n    border: 1px solid $color-border-light;\r\n    border-radius: $border-radius-sm;\r\n    padding: 0 12px;\r\n    input, input:focus {\r\n        border: none;\r\n        box-shadow: none;\r\n        outline: none;\r\n        min-height: 28px;\r\n        padding: 0;\r\n    }\r\n    &-container {\r\n        height: 60vh;\r\n        overflow: auto;\r\n    }\r\n    &-item {\r\n        padding: 20px 0;\r\n        border-top: 1px solid $color-border-primary;\r\n        min-width: 860px;\r\n        grid-template-columns: 1fr 1fr 2fr;\r\n        &:last-child {\r\n            border-bottom: 1px solid $color-border-primary;\r\n        }\r\n    }\r\n    &-wrapper {\r\n        .wsx-title {\r\n            border-bottom: 1px solid $color-border-primary;\r\n        }\r\n    }\r\n    .wsx-ellipsis {\r\n        max-width: 20rem;\r\n    }\r\n}\r\n\r\n/* ========\r\nKM Start\r\n======== */\r\n\r\n// PopUp Style\r\n.wsx-popup-overlay .wsx-lists-table {\r\n    &-wrapper {\r\n        overflow: auto;\r\n        max-height: 60vh;\r\n    }\r\n    &-body {\r\n        tr {\r\n            border: 0;\r\n            td {\r\n                border-bottom: 1px solid $color-border-primary;\r\n                &:first-child {\r\n                    padding-left: 31px;\r\n                    border-left: 1px solid $color-border-primary;\r\n                }\r\n                &:last-child {\r\n                    padding-right: 31px;\r\n                    border-right: 1px solid $color-border-primary;\r\n                }\r\n            }\r\n            &:last-child {\r\n                td {\r\n                    &:first-child {\r\n                        display: block;\r\n                        border-radius: 0 0 0 $border-radius-lg;\r\n                    }\r\n                    &:last-child {\r\n                        display: block;\r\n                        border-radius: 0 0 $border-radius-lg 0;\r\n                    }\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n.wsx-transaction-popup .wsx-card {\r\n    padding: 46px 56px;\r\n}\r\n.wsx-side-modal {\r\n    &-wrapper {\r\n        position: fixed;\r\n        top: 32px;\r\n        left: 0;\r\n        bottom: 0;\r\n        right: 0;\r\n        display: flex;\r\n        justify-content: flex-end;\r\n        z-index: 9999;\r\n        background-color: hex-to-rgba($color-tertiary, 0.6);\r\n        @media (max-width: 782px) {\r\n            top: 46px;\r\n        }\r\n    }\r\n    &-container {\r\n        width: 90%;\r\n        height: 100%;\r\n        max-width: 890px;\r\n        background-color: $color-base-one;\r\n        .wsx-side-menu-header {\r\n            background-color: $color-base-three;\r\n            border-bottom: 0;\r\n        }\r\n        .wsx-side-menu-content {\r\n            overflow: auto;\r\n            height: 100vh;\r\n            .wsx-flex-column:last-child {\r\n                padding-bottom: 220px;\r\n            }\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-video-modal {\r\n    .wsx-popup-content-wrapper {\r\n        max-width: 1120px;\r\n        max-height: 630px;\r\n        padding: 8px;\r\n        @media (max-width: 768px) {\r\n            width: 90%;\r\n            height: auto;\r\n        }\r\n    }\r\n    .wsx-icon-cross {\r\n        color: $color-text-reverse;\r\n        border: none;\r\n        right: -14px;\r\n        top: -14px;\r\n        padding: 5px;\r\n        background: $color-tertiary;\r\n        &:hover {\r\n            background-color: $color-negative;\r\n        }\r\n    }\r\n}\r\n\r\n\r\n/*\r\n\r\nFeatures Page Style Start\r\n\r\n*/\r\n.wsx-features-heading {\r\n    text-align: center;\r\n    margin-bottom: 48px;\r\n\r\n    .wsx-heading-text {\r\n        font-size: 24px;\r\n        font-weight: 600;\r\n        line-height: 32px;\r\n        margin-bottom: 16px;\r\n\r\n        .wsx-text-highlight {\r\n            color: $color-primary;\r\n        }\r\n    }\r\n    .wsx-sub-heading-text {\r\n        font-size: 14px;\r\n        font-weight: 400;\r\n        line-height: 20px;\r\n        color: $color-text-medium;\r\n    }\r\n}\r\n\r\n\r\n.wsx-features-section {\r\n    background-color: $color-base-two;\r\n    border-radius: 12px;\r\n    margin-top: 80px;\r\n}\r\n.wsx-feature-container {\r\n    padding: 48px 80px;\r\n}\r\n/*\r\n\r\nFeatures Page Style End\r\n\r\n\r\n\r\n*/\r\n// dynamic rules setting accordion style start\r\n.wsx-badge-style {\r\n    gap: 16px;\r\n    label#choose-box-selected {\r\n        border-color: $color-tertiary;\r\n    }\r\n    .choose-box-selected {\r\n        padding: 13px 16px;\r\n    }\r\n    .wsx-choose-box-image {\r\n        max-height: 22px;\r\n    }\r\n}\r\n\r\n.wsx-accordion-body.wsx-close {\r\n    display: none;\r\n    max-height: 0;\r\n    overflow: hidden;\r\n    transition: max-height 3s ease-in-out;\r\n}\r\n\r\n// dynamic rules setting accordion style end\r\n\r\n/* ========\r\nKM End\r\n======== */\r\n\r\n\r\n/* Common Style */\r\n.wsx-preview {\r\n    &-container {\r\n        border: 2px solid $color-border-primary;\r\n        border-radius: $border-radius-md;\r\n        width: 32vw;\r\n        max-width: 611px;\r\n    }\r\n    &-header {\r\n        padding: 12px;\r\n        text-align: center;\r\n        background-color: $color-border-primary;\r\n    }\r\n    &-body {\r\n        padding: 16px 20px 24px;\r\n    }\r\n}\r\n\r\n.wsx-profile-list {\r\n    width: 24px;\r\n    height: 24px;\r\n    color: $color-text-reverse;\r\n    background-color: $color-light;\r\n    border: 1px solid $color-base-one;\r\n    border-radius: 50%;\r\n    display: flex;\r\n    align-items: center;\r\n    justify-content: center;\r\n    font-size: 12px;\r\n    font-weight: $font-weight-medium;\r\n    text-transform: uppercase;\r\n}\r\n\r\n.wsx-hr-line {\r\n    width: 100%;\r\n    height: 1px;\r\n    background-color: $color-border-primary;\r\n}\r\n\r\n.wsx-item-divider-wrapper > {\r\n    div, span {\r\n        border-bottom: 1px solid $color-border-primary;\r\n        padding-bottom: 20px;\r\n        margin-bottom: 20px;\r\n        &:last-child {\r\n            border-bottom: 0;\r\n            padding-bottom: 0;\r\n            margin-bottom: 0;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-curser-pointer {\r\n    cursor: pointer;\r\n}\r\n\r\n.wsx-depends-message {\r\n    margin-top: 8px;\r\n    color: $color-secondary;\r\n    font-size: 14px;\r\n    a {\r\n        color: $color-primary;\r\n        font-weight: $font-weight-bold;\r\n        &:hover, &:focus, &:visited {\r\n            color: $color-positive;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-3dot {\r\n    &-wrapper {\r\n        width: 32px;\r\n        height: 32px;\r\n        display: flex;\r\n        align-items: center;\r\n        justify-content: center;\r\n        border-radius: $border-radius-md;\r\n        background-color: $color-base-three;\r\n        cursor: pointer;\r\n        transition: all $transition-md;\r\n        position: relative;\r\n        z-index: 0;\r\n        &:hover, &.active {\r\n            background-color: $color-primary;\r\n            color: $color-text-reverse;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-notification-icon {\r\n    width: 12px;\r\n    height: 12px;\r\n    border-radius: 50%;\r\n    background-color: $color-pink;\r\n    position: absolute;\r\n    top: -5px;\r\n    right: -5px;\r\n}\r\n\r\n.wsx-dashboard-counter {\r\n    width: 12px;\r\n    height: 12px;\r\n    border-radius: 50%;\r\n    background-color: $color-pink;\r\n    position: absolute;\r\n    top: -5px;\r\n    right: -5px;\r\n}\r\n\r\n.wsx-note-wrapper {\r\n    width: fit-content;\r\n    padding: 10px 16px;\r\n    border-radius: $border-radius-md;\r\n    background-color: $color-base-three;\r\n}\r\n\r\n.wsx-help-message {\r\n    font-size: 12px;\r\n    line-height: 1.334em;\r\n    color: $color-text-body;\r\n    font-style: italic;\r\n    margin-top: 6px;\r\n    white-space: normal;\r\n}\r\n\r\n// Accordion Style\r\n.wsx-accordion {\r\n    &-wrapper {\r\n        background-color: $color-gray;\r\n        border-radius: $border-radius-md;\r\n        border: 1px solid $color-border-primary;\r\n        input, select {\r\n            background-color: $color-base-one;\r\n        }\r\n        .wsx-input-label {\r\n            color: $color-text-medium;\r\n        }\r\n        &-dynamic {\r\n            display: grid;\r\n            gap: 24px;\r\n            .wsx-switch-field-wrapper, .wsx-slider-wrapper {\r\n                display: flex;\r\n                align-items: center;\r\n                max-height: 40px;\r\n                height: 100%;\r\n                @media (max-width: 768px) {\r\n                    display: block;\r\n                }\r\n            }\r\n        }\r\n    }\r\n    &-header {\r\n        .wsx-icon {\r\n            color: $color-tertiary;\r\n            svg {\r\n                transition: all $transition-md;\r\n            }\r\n            &.active svg {\r\n                transform: rotate(180deg);\r\n            }\r\n        }\r\n    }\r\n    &-title {\r\n        font-size: 16px;\r\n        line-height: 24px;\r\n        font-weight: 500;\r\n        color: $color-text-medium;\r\n    }\r\n    &-body {\r\n        padding: 32px 24px;\r\n        border-top: 1px solid $color-border-primary;\r\n    }\r\n}\r\n\r\n.wsx-tiers-fields {\r\n    margin-bottom: 8px;\r\n}\r\n.wsx-tier {\r\n    &-wrapper, &-header {\r\n        display: grid;\r\n        grid-template-columns: repeat(4, 1fr) 40px;\r\n        gap: 24px;\r\n        align-items: end;\r\n        &2 {\r\n            grid-template-columns: repeat(3, 1fr) 40px;\r\n        }\r\n    }\r\n    &-wrapper {\r\n        margin-bottom: 16px;\r\n    }\r\n    &-header {\r\n        &-item {\r\n            padding-left: 4px;\r\n            color: $color-text-medium;\r\n        }\r\n    }\r\n    &-design-settings {\r\n        padding-right: 20px;\r\n        border-right: 1px solid $color-tertiary;\r\n        display: flex;\r\n        flex-direction: column;\r\n        gap: 32px;\r\n    }\r\n}\r\n.wsx-condition-container .wsx-tier {\r\n    &-wrapper, &-header {\r\n        grid-template-columns: repeat(3, 1fr) 40px;\r\n    }\r\n}\r\n\r\n// User Roles Style\r\n.wsx-edit-role-wrapper {\r\n    color: $color-text-medium;\r\n    &.wsx-card {\r\n        padding: 32px;\r\n    }\r\n    .wsx-edit-role-container {\r\n        display: flex;\r\n        flex-direction: column;\r\n        gap: 40px;\r\n    }\r\n    .wsx-radio-field-label {\r\n        margin-bottom: 20px;\r\n    }\r\n    input, .wsx-multiselect-wrapper {\r\n        background-color: $color-base-one;\r\n    }\r\n}\r\n.wsx-role {\r\n    &-title-wrapper {\r\n        margin-bottom: -16px;\r\n    }\r\n    &-credit-wrapper {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 32px;\r\n        .wsx-input-wrapper {\r\n            width: 100%;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-regi_form_section {\r\n    padding: 20px 24px;\r\n    background-color: $color-gray;\r\n    border-radius: $border-radius-md;\r\n    border: 1px solid $color-border-primary;\r\n}\r\n.wsx-user-role-url-fields {\r\n    gap: 32px;\r\n    display: flex;\r\n    flex-wrap: wrap;\r\n    align-items: center;\r\n    padding: 20px 24px;\r\n    background-color: $color-gray;\r\n    border-radius: $border-radius-md;\r\n    border: 1px solid $color-border-primary;\r\n    .wsx-user-role-radio-field {\r\n        width: 100%;\r\n        flex-grow: 2;\r\n    }\r\n    .wsx-input-wrapper {\r\n        width: 48%;\r\n        flex-grow: 1;\r\n    }\r\n}\r\n.wsx-slider-line-container {\r\n    display: flex;\r\n    align-items: center;\r\n    gap: 40px;\r\n    flex-wrap: wrap;\r\n}\r\n\r\n// Shipping Zone Style \r\n.wsx-shipping-zone {\r\n    &-row {\r\n        gap: 40px;\r\n        display: flex;\r\n        padding: 16px 24px;\r\n        align-items: center;\r\n        border-top: 1px solid $color-border-primary;\r\n    }\r\n    &-title {\r\n        color: $color-text-medium;\r\n        min-width: 100px;\r\n        width: 18.575%;\r\n    }\r\n}\r\n\r\n\r\n// Table Style\r\n.wsx-table {\r\n    &-wrapper {\r\n        overflow: hidden;\r\n        border-radius: $border-radius-md;\r\n        box-shadow: 0 2px 4px 0 $color-shadow-primary;\r\n    }\r\n    &-container {\r\n        background-color: $color-base-one;\r\n        padding: 32px;\r\n        overflow: auto;\r\n        white-space: nowrap;\r\n    }\r\n    &-body {\r\n        color: $color-text-light;\r\n        .wsx-ellipsis {\r\n            max-width: 12rem;\r\n            display: block;\r\n        }\r\n    }\r\n    &-header, &-row {\r\n        display: grid;\r\n        padding-bottom: 32px;\r\n        width: 100%;\r\n        border-bottom: 1px solid $color-border-primary; \r\n    }\r\n    &-header {\r\n        font-weight: $font-weight-medium;\r\n        color: $color-text-medium;\r\n    }\r\n    &-row {\r\n        padding: 20px 0;\r\n        align-items: center;\r\n    }\r\n}\r\n.wsx-lists-table {\r\n    &-wrapper {\r\n        overflow-x: auto;\r\n    }\r\n    width: 100%;\r\n    overflow-y: auto;\r\n    border-spacing: 0;\r\n    border-collapse: collapse;\r\n    border-radius: $border-radius-lg;\r\n    background-color: $color-base-one;\r\n    box-shadow: 0 0px 4px 0 $color-shadow-primary;\r\n    &-header {\r\n        color: $color-text-medium;\r\n        font-weight: $font-weight-medium;\r\n        background-color: $color-base-two;\r\n        th {\r\n            background-color: $color-base-two;\r\n        }\r\n    }\r\n    &-column {\r\n        padding: 20px 0;\r\n        text-align: start;\r\n        min-width: 110px;\r\n        &:first-child {\r\n            padding-left: 32px;\r\n            border-radius: $border-radius-lg 0 0 0;\r\n        }\r\n        &:last-child {\r\n            padding-right: 32px;\r\n            border-radius: 0 $border-radius-lg 0 0;\r\n        }\r\n        &-user_id {\r\n            min-width: unset;\r\n            padding-left: 16px;\r\n            padding-right: 10px;\r\n        }\r\n        &-action {\r\n            text-align: end;\r\n            min-width: unset;\r\n            width: 32px;\r\n            .wsx-btn, .wsx-3dot-wrapper {\r\n                margin-left: auto;\r\n            }\r\n        }\r\n        .wsx-ellipsis {\r\n            max-width: 12rem;\r\n            width: 90%;\r\n        }\r\n        a {\r\n            color: $color-primary;\r\n            text-decoration: underline;\r\n            &:hover {\r\n                color: orchid;\r\n            }\r\n        }\r\n    }\r\n    &-body {\r\n        tr {\r\n            border-bottom: 1px solid $color-border-primary;\r\n            &:last-child {\r\n                border-bottom: 0;\r\n            }\r\n            &.wsx-lists-empty td {\r\n                border-radius: 0 0 $border-radius-lg $border-radius-lg;\r\n                text-align: center;\r\n            }\r\n        }\r\n        td {\r\n            background-color: $color-base-one;\r\n            &:first-child {\r\n                border-radius: 0 0 0 $border-radius-lg;\r\n            }\r\n            &:last-child {\r\n                border-radius: 0 0 $border-radius-lg 0;\r\n            }\r\n        }\r\n    }\r\n}\r\n.wsx-checkbox-column {\r\n    width: 20px;\r\n    min-width: unset;\r\n}\r\n\r\n\r\n\r\n\r\n// Rules Container\r\n.wsx-row {\r\n    &-actions-list {\r\n        padding: 8px 16px;\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 8px;\r\n        border-radius: $border-radius-md;\r\n        color: $color-text-light;\r\n        white-space: nowrap;\r\n        cursor: pointer;\r\n        &:hover {\r\n            background-color: $color-base-three;\r\n            color: $color-text-dark;\r\n        } \r\n    }\r\n    &-wrapper {\r\n        background-color: $color-base-two;\r\n        border-radius: $border-radius-lg;\r\n        padding: 20px 24px 88px;\r\n    }\r\n    &-container {\r\n        display: flex;\r\n        flex-direction: column;\r\n        gap: 12px;\r\n        overflow-y: auto;\r\n        margin-bottom: 48px;\r\n    }\r\n}\r\n\r\n.wsx-rules {\r\n    &-header, &-row {\r\n        display: grid;\r\n        grid-template-columns: 3fr .7fr repeat(3, 2fr) 1fr;\r\n        gap: 20px;\r\n        align-items: center;\r\n        width: 96.44%;\r\n        min-width: fit-content;\r\n    }\r\n    &-header {\r\n        padding: 10px 20px;\r\n        > div {\r\n            padding-right: 10px;\r\n            white-space: nowrap;\r\n        }\r\n    }\r\n    &-row {\r\n        background-color: $color-base-one;\r\n        border-radius: $border-radius-lg;\r\n        padding: 12px 20px;\r\n    }\r\n}\r\n.wsx-roles-wrapper {\r\n    .wsx-rules {\r\n        &-header, &-row {\r\n            grid-template-columns: 3fr repeat(2, 2fr) 1fr;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-rule {\r\n    &-item {\r\n        height: 100%;\r\n        max-height: 36px;\r\n        min-width: 156px;\r\n        padding: 2px 0;\r\n        padding-right: 10px;\r\n        border-right: 1px solid $color-border-secondary;\r\n        display: flex;\r\n        align-items: center;\r\n        color: $color-text-light;\r\n    }\r\n    &-checkbox-with-title {\r\n        display: flex;\r\n        align-items: center;\r\n        gap: 16px;\r\n        color: $color-tertiary;\r\n    }\r\n    &-status {\r\n        max-width: 76px;\r\n        min-width: unset;\r\n    }\r\n}\r\n\r\n\r\n.wsx-user-image {\r\n    max-width: 44px;\r\n    max-height: 44px;\r\n    border-radius: 50%;\r\n}\r\n\r\n\r\n// User List Style\r\n.wsx-user-list-wrapper {\r\n    .wsx-input-wrapper {\r\n        max-width: 250px;\r\n        background-color: $color-base-one;\r\n        width: 100%;\r\n        border-radius: $border-radius-md;\r\n    }\r\n}\r\n.wsx-user-select-container {\r\n    .wsx-input-wrapper {\r\n        max-width: 164px;\r\n        width: max-content;\r\n    }\r\n}\r\n.wsx-dropdown-actions-list {\r\n    margin-bottom: 20px;\r\n    &:last-child, &:only-child {\r\n        margin-bottom: 0;\r\n    }\r\n}\r\n\r\n// Email List Style\r\n.wsx-email-lists {\r\n    .wsx-lists-table-column {\r\n        &-email_type {\r\n            text-transform: capitalize;\r\n        }\r\n        .wsx-ellipsis {\r\n            max-width: 20rem;\r\n            width: 60%;\r\n        }\r\n    }\r\n}\r\n\r\n.wsx-row-gap {\r\n    &-48 {\r\n        row-gap: 48px;\r\n    }\r\n    &-40 {\r\n        row-gap: 40px;\r\n    }\r\n    &-32 {\r\n        row-gap: 32px;\r\n    }\r\n    &-30 {\r\n        row-gap: 30px;\r\n    }\r\n    &-24 {\r\n        row-gap: 24px;\r\n    }\r\n    &-20 {\r\n        row-gap: 20px;\r\n    }\r\n    &-16 {\r\n        row-gap: 16px;\r\n    }\r\n    &-12 {\r\n        row-gap: 12px;\r\n    }\r\n    &-8 {\r\n        row-gap: 8px;\r\n    }\r\n    &-6 {\r\n        row-gap: 6px;\r\n    }\r\n    &-4 {\r\n        row-gap: 4px;\r\n    }\r\n}\r\n\r\n.wsx-grow {\r\n    &-3 {\r\n        flex-grow: 3;\r\n    }\r\n    &-2 {\r\n        flex-grow: 2;\r\n    }\r\n    &-1 {\r\n        flex-grow: 1;\r\n    }\r\n}\r\n\r\n.wsx-shrink-0 {\r\n    flex-shrink: 0;\r\n}\r\n\r\n.wsx-f-basis {\r\n    &-50 {\r\n        flex-basis: 50%;\r\n    }\r\n    &-20 {\r\n        flex-basis: 20%;\r\n    }\r\n    &-17 {\r\n        flex-basis: 17%;\r\n    }\r\n    &-4 {\r\n        flex-basis: 4%;\r\n    }\r\n}\r\n\r\n\r\n// Shadow Style\r\n.wsx-shadow-none {\r\n    box-shadow: none;\r\n}\r\n\r\n\r\n/* Responsive style */\r\n@media (max-width: 1300px) {\r\n    .wsx-dashboard {\r\n        flex-wrap: wrap;\r\n        .wsx-container-left, .wsx-container-right {\r\n            flex-basis: 100%;\r\n        }\r\n    }\r\n    .wsx-rules-header > div {\r\n        min-width: 144px;\r\n        &.wsx-rule-checkbox-with-title {\r\n            min-width: 156px;\r\n        }\r\n        &.wsx-rule-status {\r\n            min-width: unset;\r\n        }\r\n    }\r\n}\r\n@media (max-width: 1200px) {\r\n    .wsx-rules-header > div {\r\n        min-width: 156px;\r\n    }    \r\n\r\n    .wsx-tier-design-settings {\r\n        border-right: 0;\r\n        padding-right: 0;\r\n        padding-bottom: 20px;\r\n        border-bottom: 1px solid $color-tertiary;\r\n    }\r\n    .wsx-settings-design-container {\r\n        flex-direction: column;\r\n    }\r\n    .wsx-preview-container {\r\n        width: 52vw;\r\n    }\r\n}\r\n\r\n@media (max-width: 991px) {\r\n    .wsx-chart-header {\r\n        flex-wrap: wrap;\r\n        white-space: nowrap;\r\n    }\r\n}\r\n\r\n@media (max-width: 836px) {\r\n    .wsx-side-menu {\r\n        &-wrapper {\r\n            display: block;\r\n        }\r\n        &-list {\r\n            display: flex;\r\n            overflow-y: hidden;\r\n            overflow-x: auto;\r\n            max-width: unset;\r\n        }\r\n        &-item {\r\n            padding: 16px 24px;\r\n            border-bottom: 0;\r\n            border-right: 1px solid $color-border-tertiary;\r\n            &:last-child {\r\n                border: 0;\r\n                margin: 0;\r\n            }\r\n        }\r\n        &-header {\r\n            padding: 14px 16px;\r\n        }\r\n        &-body {\r\n            .wsx-title {\r\n                font-size: 16px;\r\n            }\r\n            .wsx-btn {\r\n                padding: 8px 12px;\r\n                font-size: 13px;\r\n            }\r\n        }\r\n    }\r\n    \r\n    .wsx-lists-table-body tr.wsx-lists-empty td {\r\n        text-align: start;\r\n    }\r\n\r\n    .wsx-preview-container {\r\n        width: 74vw;\r\n    }\r\n\r\n    .wsx-slg {\r\n        &-justify-wrapper {\r\n            display: flex;\r\n            align-items: center;\r\n            flex-wrap: wrap;\r\n            gap: 20px;\r\n            justify-content: center;\r\n        }\r\n\r\n        &-flex {\r\n            &-wrap {\r\n                flex-wrap: wrap;\r\n            }\r\n        }\r\n\r\n        &-center-hz {\r\n            margin-left: auto;\r\n            margin-right: auto;\r\n        }\r\n\r\n        &-w {\r\n            &-full {\r\n                width: 100%;\r\n            }\r\n            &-half {\r\n                width: 50%;\r\n            }\r\n            &-fit {\r\n                width: fit-content;\r\n            }\r\n        }\r\n\r\n        &-p {\r\n            &-32 {\r\n                padding: 32px;\r\n            }\r\n            &-24 {\r\n                padding: 24px;\r\n            }\r\n            &-16 {\r\n                padding: 16px;\r\n            }\r\n            &-10 {\r\n                padding: 10px;\r\n            }\r\n            &-4 {\r\n                padding: 4px;\r\n            }\r\n            &-0 {\r\n                padding: 0px;\r\n            }\r\n            &t {\r\n                &-48 {\r\n                    padding-top: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-top: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-top: 20px;\r\n                }\r\n            }\r\n            &b {\r\n                &-48 {\r\n                    padding-bottom: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-bottom: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-bottom: 20px;\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n\r\n@media (max-width: 768px) {\r\n    .wsx-chart-header div {\r\n        flex-wrap: wrap;\r\n        gap: 10px;\r\n    }\r\n\r\n    .wsx-tier {\r\n        &-wrapper, &-header {\r\n            grid-template-columns: repeat(4, 1fr);\r\n            gap: 12px;\r\n        }\r\n    }\r\n\r\n    .wsx-md {\r\n        &-d {\r\n            &-block {\r\n                display: block;\r\n            }\r\n            &-flex {\r\n                display: flex;\r\n            }\r\n            &-none {\r\n                display: none;\r\n            }\r\n        }\r\n        &-flex {\r\n            &-column {\r\n                flex-direction: column;\r\n            }\r\n            &-column-reverse {\r\n                flex-direction: column-reverse;\r\n            }\r\n            &-reverse {\r\n                flex-direction: row-reverse;\r\n            }\r\n        }\r\n        &-column {\r\n            &-1,\r\n            &-2,\r\n            &-3 {\r\n                display: grid;\r\n            }\r\n            &-3 {\r\n                grid-template-columns: repeat(3, 1fr);\r\n            }\r\n            &-2 {\r\n                grid-template-columns: repeat(2, 1fr);\r\n            }\r\n            &-1 {\r\n                grid-template-columns: 1fr;\r\n            }\r\n        }\r\n        &-gap {\r\n            &-48 {\r\n                gap: 48px;\r\n            }\r\n            &-40 {\r\n                gap: 40px;\r\n            }\r\n            &-32 {\r\n                gap: 32px;\r\n            }\r\n            &-30 {\r\n                gap: 30px;\r\n            }\r\n            &-24 {\r\n                gap: 24px;\r\n            }\r\n            &-20 {\r\n                gap: 20px;\r\n            }\r\n            &-16 {\r\n                gap: 16px;\r\n            }\r\n            &-12 {\r\n                gap: 12px;\r\n            }\r\n            &-8 {\r\n                gap: 8px;\r\n            }\r\n            &-6 {\r\n                gap: 6px;\r\n            }\r\n            &-4 {\r\n                gap: 4px;\r\n            }\r\n        }\r\n        &-important-gap-16 {\r\n            gap: 16px !important;\r\n        }\r\n\r\n        &-w {\r\n            &-full {\r\n                width: 100%;\r\n            }\r\n            &-half {\r\n                width: 50%;\r\n            }\r\n            &-fit {\r\n                width: fit-content;\r\n            }\r\n        }\r\n\r\n        &-p {\r\n            &-32 {\r\n                padding: 32px;\r\n            }\r\n            &-24 {\r\n                padding: 24px;\r\n            }\r\n            &-16 {\r\n                padding: 16px;\r\n            }\r\n            &-10 {\r\n                padding: 10px;\r\n            }\r\n            &-4 {\r\n                padding: 4px;\r\n            }\r\n            &-0 {\r\n                padding: 0px;\r\n            }\r\n            &t {\r\n                &-48 {\r\n                    padding-top: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-top: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-top: 20px;\r\n                }\r\n            }\r\n            &b {\r\n                &-48 {\r\n                    padding-bottom: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-bottom: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-bottom: 20px;\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n@media (max-width: 638px) {\r\n    .wsx-justify-wrapper {\r\n        flex-wrap: wrap;\r\n        gap: 20px;\r\n        justify-content: center;\r\n    }\r\n\r\n    .wsx-smd {\r\n        &-gap {\r\n            &-48 {\r\n                gap: 48px;\r\n            }\r\n            &-40 {\r\n                gap: 40px;\r\n            }\r\n            &-32 {\r\n                gap: 32px;\r\n            }\r\n            &-30 {\r\n                gap: 30px;\r\n            }\r\n            &-24 {\r\n                gap: 24px;\r\n            }\r\n            &-20 {\r\n                gap: 20px;\r\n            }\r\n            &-16 {\r\n                gap: 16px;\r\n            }\r\n            &-12 {\r\n                gap: 12px;\r\n            }\r\n            &-8 {\r\n                gap: 8px;\r\n            }\r\n            &-6 {\r\n                gap: 6px;\r\n            }\r\n            &-4 {\r\n                gap: 4px;\r\n            }\r\n        }\r\n        &-important-gap-16 {\r\n            gap: 16px !important;\r\n        }\r\n\r\n\r\n        &-p {\r\n            &-32 {\r\n                padding: 32px;\r\n            }\r\n            &-24 {\r\n                padding: 24px;\r\n            }\r\n            &-16 {\r\n                padding: 16px;\r\n            }\r\n            &-10 {\r\n                padding: 10px;\r\n            }\r\n            &-4 {\r\n                padding: 4px;\r\n            }\r\n            &-0 {\r\n                padding: 0px;\r\n            }\r\n            &t {\r\n                &-48 {\r\n                    padding-top: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-top: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-top: 20px;\r\n                }\r\n            }\r\n            &b {\r\n                &-48 {\r\n                    padding-bottom: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-bottom: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-bottom: 20px;\r\n                }\r\n            }\r\n        }\r\n    }\r\n}\r\n@media (max-width: 576px) {\r\n    .wsx-sales-chart-legend {\r\n        flex-direction: column;\r\n        align-items: start;\r\n        margin-left: auto;\r\n    }\r\n\r\n    .wsx-condition-container .wsx-tier {\r\n        &-wrapper {\r\n            grid-template-columns: 1fr;\r\n        }\r\n    }\r\n\r\n    .wsx-sm {\r\n        &-gap {\r\n            &-48 {\r\n                gap: 48px;\r\n            }\r\n            &-40 {\r\n                gap: 40px;\r\n            }\r\n            &-32 {\r\n                gap: 32px;\r\n            }\r\n            &-30 {\r\n                gap: 30px;\r\n            }\r\n            &-24 {\r\n                gap: 24px;\r\n            }\r\n            &-20 {\r\n                gap: 20px;\r\n            }\r\n            &-16 {\r\n                gap: 16px;\r\n            }\r\n            &-12 {\r\n                gap: 12px;\r\n            }\r\n            &-8 {\r\n                gap: 8px;\r\n            }\r\n            &-6 {\r\n                gap: 6px;\r\n            }\r\n            &-4 {\r\n                gap: 4px;\r\n            }\r\n        }\r\n        &-important-gap-16 {\r\n            gap: 16px !important;\r\n        }\r\n\r\n        &-flex {\r\n            &-column {\r\n                flex-direction: column;\r\n            }\r\n            &-column-reverse {\r\n                flex-direction: column-reverse;\r\n            }\r\n            &-reverse {\r\n                flex-direction: row-reverse;\r\n            }\r\n        }\r\n\r\n        &-m {\r\n            &-0 {\r\n                margin: 0;\r\n            }\r\n            &t {\r\n                &-0 {\r\n                    margin-top: 0;\r\n                }\r\n            }\r\n        }\r\n\r\n        &-p {\r\n            &-32 {\r\n                padding: 32px;\r\n            }\r\n            &-24 {\r\n                padding: 24px;\r\n            }\r\n            &-16 {\r\n                padding: 16px;\r\n            }\r\n            &-10 {\r\n                padding: 10px;\r\n            }\r\n            &-4 {\r\n                padding: 4px;\r\n            }\r\n            &-0 {\r\n                padding: 0px;\r\n            }\r\n            &t {\r\n                &-48 {\r\n                    padding-top: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-top: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-top: 20px;\r\n                }\r\n            }\r\n            &b {\r\n                &-48 {\r\n                    padding-bottom: 48px;\r\n                }\r\n                &-32 {\r\n                    padding-bottom: 32px;\r\n                }\r\n                &-20 {\r\n                    padding-bottom: 20px;\r\n                }\r\n            }\r\n        }\r\n    }\r\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/OverlayWindow.scss":
/*!*********************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/OverlayWindow.scss ***!
  \*********************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.wsx-overlay-right-window-wrapper {
  position: fixed;
  top: 0;
  right: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 99999; }

.wsx-overlay-edit {
  width: 50%;
  height: 100%;
  background-color: white;
  float: right;
  animation: slide-right 0.3s ease-in-out;
  overflow-y: scroll; }

.wsx-overlay-header {
  padding: 10.5px 15px;
  border-radius: 4px;
  background-color: #f0f0ff;
  font-size: 14px;
  font-weight: 500;
  font-stretch: normal;
  font-style: normal;
  line-height: 2;
  letter-spacing: normal;
  text-align: left;
  color: var(--wholesalex-heading-text-color);
  display: flex;
  justify-content: space-between;
  align-items: center; }

.wsx-overlay-label {
  font-size: 14px;
  font-weight: 500;
  font-stretch: normal;
  font-style: normal;
  line-height: 2;
  letter-spacing: normal;
  text-align: left;
  color: var(--wholesalex-heading-text-color); }

.wholesalex_cross_icon {
  font-size: 25px;
  font-weight: normal;
  font-stretch: normal;
  font-style: normal;
  line-height: 28px;
  letter-spacing: normal;
  text-align: left;
  color: var(--wholesalex-heading-text-color);
  width: 28px;
  height: 29px;
  cursor: pointer; }
  .wholesalex_cross_icon:hover {
    color: red; }

.wsx-overlay-content {
  padding: 32px 40px;
  text-align: left;
  position: relative; }

.wholesalex_overlay_content__fields {
  display: flex;
  flex-direction: column;
  gap: 25px; }

@keyframes slide-right {
  0% {
    opacity: 0;
    transform: translateX(50%); }
  100% {
    opacity: 1;
    transform: translateX(0); } }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/OverlayWindow.scss"],"names":[],"mappings":"AAAA;EACI,eAAe;EACf,MAAM;EACN,QAAQ;EACR,WAAW;EACX,YAAY;EACZ,oCAAoC;EACpC,cAAc,EAAA;;AAGlB;EACI,UAAU;EACV,YAAY;EACZ,uBAAuB;EACvB,YAAY;EACZ,uCAAuC;EACvC,kBAAkB,EAAA;;AAGtB;EACI,oBAAoB;EACpB,kBAAkB;EAClB,yBAAyB;EACzB,eAAe;EACf,gBAAgB;EAChB,oBAAoB;EACpB,kBAAkB;EAClB,cAAc;EACd,sBAAsB;EACtB,gBAAgB;EAChB,2CAA2C;EAC3C,aAAa;EACb,8BAA8B;EAC9B,mBAAmB,EAAA;;AAGvB;EACI,eAAe;EACf,gBAAgB;EAChB,oBAAoB;EACpB,kBAAkB;EAClB,cAAc;EACd,sBAAsB;EACtB,gBAAgB;EAChB,2CAA2C,EAAA;;AAG/C;EACI,eAAe;EACf,mBAAmB;EACnB,oBAAoB;EACpB,kBAAkB;EAClB,iBAAiB;EACjB,sBAAsB;EACtB,gBAAgB;EAChB,2CAA2C;EAC3C,WAAW;EACX,YAAY;EACZ,eAAe,EAAA;EAXnB;IAcQ,UAAU,EAAA;;AAIlB;EACI,kBAAkB;EAClB,gBAAgB;EAChB,kBAAkB,EAAA;;AAGtB;EACI,aAAa;EACb,sBAAsB;EACtB,SAAS,EAAA;;AAIX;EACE;IACE,UAAU;IACV,0BAA0B,EAAA;EAE5B;IACE,UAAU;IACV,wBAAwB,EAAA,EAAA","sourcesContent":[".wsx-overlay-right-window-wrapper {\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    width: 100%;\r\n    height: 100%;\r\n    background-color: rgba(0, 0, 0, 0.5);\r\n    z-index: 99999; \r\n\r\n}\r\n.wsx-overlay-edit {\r\n    width: 50%;\r\n    height: 100%;\r\n    background-color: white;\r\n    float: right;\r\n    animation: slide-right 0.3s ease-in-out;\r\n    overflow-y: scroll;\r\n}\r\n\r\n.wsx-overlay-header {\r\n    padding: 10.5px 15px;\r\n    border-radius: 4px;\r\n    background-color: #f0f0ff;\r\n    font-size: 14px;\r\n    font-weight: 500;\r\n    font-stretch: normal;\r\n    font-style: normal;\r\n    line-height: 2;\r\n    letter-spacing: normal;\r\n    text-align: left;\r\n    color: var(--wholesalex-heading-text-color);\r\n    display: flex;\r\n    justify-content: space-between;\r\n    align-items: center;\r\n}\r\n\r\n.wsx-overlay-label {\r\n    font-size: 14px;\r\n    font-weight: 500;\r\n    font-stretch: normal;\r\n    font-style: normal;\r\n    line-height: 2;\r\n    letter-spacing: normal;\r\n    text-align: left;\r\n    color: var(--wholesalex-heading-text-color);\r\n}\r\n\r\n.wholesalex_cross_icon {\r\n    font-size: 25px;\r\n    font-weight: normal;\r\n    font-stretch: normal;\r\n    font-style: normal;\r\n    line-height: 28px;\r\n    letter-spacing: normal;\r\n    text-align: left;\r\n    color: var(--wholesalex-heading-text-color);\r\n    width: 28px;\r\n    height: 29px;\r\n    cursor: pointer;\r\n\r\n    &:hover{\r\n        color: red;\r\n    }\r\n}\r\n\r\n.wsx-overlay-content {\r\n    padding: 32px 40px;\r\n    text-align: left;\r\n    position: relative;\r\n}\r\n\r\n.wholesalex_overlay_content__fields {\r\n    display: flex;\r\n    flex-direction: column;\r\n    gap: 25px;\r\n}\r\n\r\n\r\n  @keyframes slide-right {\r\n    0% {\r\n      opacity: 0;\r\n      transform: translateX(50%);\r\n    }\r\n    100% {\r\n      opacity: 1;\r\n      transform: translateX(0);\r\n    }\r\n  }\r\n"],"sourceRoot":""}]);
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

/***/ "./node_modules/css-loader/dist/runtime/getUrl.js":
/*!********************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/getUrl.js ***!
  \********************************************************/
/***/ ((module) => {

"use strict";


module.exports = function (url, options) {
  if (!options) {
    options = {};
  }
  if (!url) {
    return url;
  }
  url = String(url.__esModule ? url.default : url);

  // If url is already wrapped in quotes, remove them
  if (/^['"].*['"]$/.test(url)) {
    url = url.slice(1, -1);
  }
  if (options.hash) {
    url += options.hash;
  }

  // Should url be wrapped?
  // See https://drafts.csswg.org/css-values-3/#urls
  if (/["'() \t\n]|(%20)/.test(url) || options.needQuotes) {
    return "\"".concat(url.replace(/"/g, '\\"').replace(/\n/g, "\\n"), "\"");
  }
  return url;
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

/***/ "./reactjs/src/assets/scss/Common.scss":
/*!*********************************************!*\
  !*** ./reactjs/src/assets/scss/Common.scss ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Common_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./Common.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Common.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Common_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Common_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Common_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Common_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./reactjs/src/assets/scss/OverlayWindow.scss":
/*!****************************************************!*\
  !*** ./reactjs/src/assets/scss/OverlayWindow.scss ***!
  \****************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_OverlayWindow_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./OverlayWindow.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/OverlayWindow.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_OverlayWindow_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_OverlayWindow_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_OverlayWindow_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_OverlayWindow_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


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

/***/ "./assets/img/angle-down-16.svg":
/*!**************************************!*\
  !*** ./assets/img/angle-down-16.svg ***!
  \**************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";
module.exports = __webpack_require__.p + "704fefddbc64ba8b712d.svg";

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
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
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
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
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
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript && document.currentScript.tagName.toUpperCase() === 'SCRIPT')
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"whx_users": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		// no on chunks loaded
/******/ 		
/******/ 		// no jsonp function
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
/*!******************************************!*\
  !*** ./reactjs/src/pages/Users/index.js ***!
  \******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Users__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Users */ "./reactjs/src/pages/Users/Users.js");
/* harmony import */ var _components_Header__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/Header */ "./reactjs/src/components/Header.js");
/* harmony import */ var _context_toastContent__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../context/toastContent */ "./reactjs/src/context/toastContent.js");





document.addEventListener("DOMContentLoaded", function () {
  if (document.body.contains(document.getElementById('wholeslex_users_root'))) {
    react_dom__WEBPACK_IMPORTED_MODULE_1___default().render( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().StrictMode), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_context_toastContent__WEBPACK_IMPORTED_MODULE_4__.ToastContextProvider, null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Header__WEBPACK_IMPORTED_MODULE_3__["default"], {
      title: whx_users.i18n.users
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Users__WEBPACK_IMPORTED_MODULE_2__["default"], null))), document.getElementById('wholeslex_users_root'));
  }
});
})();

/******/ })()
;
//# sourceMappingURL=whx_users.js.map