/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./reactjs/src/components/Button.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Button.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");

function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }


const Button = /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_1__.forwardRef)((_ref, ref) => {
  let {
    label = '',
    padding = '',
    iconName = '',
    iconClass = '',
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
  const Icon = _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"][iconName] || null;
  const IconPosition = iconPosition === 'before' || iconPosition === 'right';
  const [isActive, setIsActive] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const handleClick = e => {
    e.preventDefault();
    if (!disable) {
      const button = e.currentTarget;
      setIsActive(!isActive);
      button.style.transform = 'scale(0.95)';
      setTimeout(() => button.style.transform = 'scale(1)', 150);
      if (buttonLink) {
        if (sameTab) {
          window.location.href = buttonLink;
        } else {
          window.open(buttonLink, '_blank');
        }
      } else {
        onClick(e);
      }
    }
  };
  let transformValue;
  if (iconRotation && isActive) {
    if (iconRotation === 'full') {
      transformValue = 'rotate(180deg)';
    } else if (iconRotation === 'half') {
      transformValue = 'rotate(90deg)';
    } else {
      transformValue = 'rotate(0deg)';
    }
  } else {
    transformValue = 'rotate(0deg)';
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    ref: ref,
    className: "".concat(onlyText ? 'wsx-btn-text' : 'wsx-btn', " ").concat(background ? "wsx-bg-".concat(background) : '', " ").concat(color ? "wsx-color-".concat(color) : '', " ").concat(Icon ? 'wsx-btn-icon' : '', " ").concat(iconGap ? "wsx-gap-".concat(iconGap) : '', " ").concat(disable ? 'disable' : '', " ").concat(borderColor ? "wsx-border-default wsx-bc-".concat(borderColor) : '', " ").concat(smallButton ? 'wsx-btn-sm' : '', " ").concat(customClass),
    style: _objectSpread({
      padding
    }, style),
    onClick: handleClick,
    "data-target": dataTarget,
    onKeyDown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        handleClick;
      }
    },
    role: "button",
    tabIndex: "0"
  }, IconPosition && Icon && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-icon ".concat(iconAnimation ? "wsx-anim-".concat(iconAnimation) : '', " ").concat(iconColor ? "wsx-color-".concat(iconColor) : '', " ").concat(iconClass ? iconClass : ''),
    style: {
      transition: 'transform var(--transition-md)',
      transform: transformValue
    }
  }, Icon), label, !IconPosition && Icon && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-icon ".concat(iconAnimation ? "wsx-anim-".concat(iconAnimation) : '', " ").concat(iconColor ? "wsx-color-".concat(iconColor) : '', " ").concat(iconClass ? iconClass : ''),
    style: {
      transition: 'transform var(--transition-md)',
      transform: transformValue
    }
  }, Icon));
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Button);

/***/ }),

/***/ "./reactjs/src/components/Input.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Input.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);




const Input = props => {
  var _wholesalex_overview;
  const {
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
    disabled = false,
    placeholder,
    id,
    iconClass = '',
    smartTags = false,
    requiredColor,
    dataName = ''
  } = props;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-input-wrapper ".concat(className)
  }, !isLabelHide && label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-input-label wsx-font-medium ".concat(tooltip ? 'wsx-d-flex' : '', " ").concat(labelColor && "wsx-color-".concat(labelColor), " ").concat(labelClass)
  }, label, ' ', required && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: "wsx-required",
    style: {
      color: requiredColor || '#fc143f'
    }
  }, "*"), tooltip && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_1__["default"], {
    content: tooltip,
    direction: tooltipPosition,
    spaceLeft: "8px"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"] === null || _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"] === void 0 ? void 0 : _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].help)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-input-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    "data-name": dataName !== null && dataName !== void 0 ? dataName : '',
    id: id || name,
    name: name,
    type: type,
    value: value,
    onChange: onChange,
    disabled: !!disabled,
    placeholder: placeholder ? placeholder : '',
    required: !!required,
    className: "wsx-input ".concat(inputClass !== null && inputClass !== void 0 ? inputClass : '')
  }), iconClass && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
    className: iconClass,
    "aria-hidden": "true"
  }), help && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-input-help wsx-help-message ".concat(helpClass)
  }, help), smartTags && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-help-message"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-smart-tags-wrapper"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-smart-tags-heading"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-smart-tags-heading-text"
  }, ((_wholesalex_overview = wholesalex_overview) === null || _wholesalex_overview === void 0 || (_wholesalex_overview = _wholesalex_overview.i18n) === null || _wholesalex_overview === void 0 ? void 0 : _wholesalex_overview.smartTags) || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Available Smart Tags:', 'wholesalex'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-smart-tags"
  }, Object.keys(smartTags).map(tag => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("code", {
    key: tag
  }, tag, " : ", smartTags[tag])))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Input);

/***/ }),

/***/ "./reactjs/src/components/LoadingGif.js":
/*!**********************************************!*\
  !*** ./reactjs/src/components/LoadingGif.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

const LoadingGif = _ref => {
  let {
    overlay = true,
    insideContainer = false,
    customClass,
    loaderClass
  } = _ref;
  // decide what to render
  let content;
  if (overlay) {
    if (insideContainer) {
      content = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "wsx-absolute wsx-z-999 wsx-top wsx-bottom wsx-left wsx-right wsx-d-flex wsx-item-center wsx-justify-center",
        style: {
          backgroundColor: 'rgb(255 255 255 / 88%)'
        }
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
        className: "wsx-absolute wsx-z-99999 wsx-top-20p ".concat(loaderClass),
        style: {
          maxWidth: '100px',
          maxHeight: '100px'
        },
        src: "".concat(wholesalex.url, "assets/img/wsx-loading.gif"),
        alt: "loading-img"
      }));
    } else {
      content = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
        className: "wsx-popup-overlay ".concat(customClass),
        style: {
          backgroundColor: 'rgb(255 255 255 / 88%)'
        }
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
        className: "".concat(loaderClass),
        style: {
          maxWidth: '100px',
          maxHeight: '100px'
        },
        src: "".concat(wholesalex.url, "assets/img/wsx-loading.gif"),
        alt: "loading-gif"
      }));
    }
  } else {
    content = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
      className: "".concat(loaderClass),
      style: {
        maxWidth: '100px',
        maxHeight: '100px'
      },
      src: "".concat(wholesalex.url, "assets/img/wsx-loading.gif"),
      alt: "loading-image"
    });
  }
  return content;
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (LoadingGif);

/***/ }),

/***/ "./reactjs/src/components/Modal.js":
/*!*****************************************!*\
  !*** ./reactjs/src/components/Modal.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button */ "./reactjs/src/components/Button.js");




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
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
  return status && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-wrapper ".concat(customClass ? customClass : '', " ").concat(isOpen ? 'open' : '')
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal ".concat(smallModal ? 'wsx-modal-sm' : ''),
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
    onClick: handleClose,
    onKeyDown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        handleClose;
      }
    },
    role: "button",
    tabIndex: "0"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-body"
  }, "".concat((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Do You Want to delete', 'wholesalex'), " ").concat(title ? title : '', "? ").concat((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Be careful, this procedure is irreversible. Do you want to proceed?', 'wholesalex'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-footer"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
    padding: "4px 12px",
    borderColor: "border-primary",
    color: "text-light",
    background: "base1",
    customClass: "wsx-font-14",
    onClick: () => {
      setStatus(false);
    },
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Cancel', 'wholesalex')
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
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

/***/ "./reactjs/src/components/PopupModal.js":
/*!**********************************************!*\
  !*** ./reactjs/src/components/PopupModal.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");


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
    className: "wsx-modal-wrapper ".concat(className, " ").concat(isOpen ? 'open' : 'close')
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-popup-content-wrapper ".concat(wrapperClass)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-popup-content wsx-scrollbar"
  }, renderContent()), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-modal-close wsx-icon-cross",
    onClick: handleClose,
    onKeyDown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        handleClose;
      }
    },
    role: "button",
    tabIndex: "0"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"].cross)));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PopupModal);

/***/ }),

/***/ "./reactjs/src/components/Select.js":
/*!******************************************!*\
  !*** ./reactjs/src/components/Select.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_2__);



function Select(_ref) {
  let {
    id = '',
    name = '',
    value = '',
    noValue = false,
    label = '',
    labelClass = '',
    inputPadding = '',
    iconName = '',
    iconPosition = 'after',
    iconColor = '',
    iconGap = '8px',
    iconRotation = 'full',
    onChange,
    wrapperClass = '',
    customClass = '',
    inputBackground = 'transparent',
    inputColor = 'text-light',
    borderNone = false,
    borderColor = 'border-primary',
    borderRadius = 'md',
    containerBackground = 'base1',
    containerBorderRadius = 'md',
    containerBorderColor = 'base2',
    containerBorder = true,
    containerPadding = '4',
    minWidth = '',
    maxWidth = '',
    direction = 'ltr',
    textAlign = '',
    options = [],
    optionBorderBottom = false,
    optionBorderRadius = 'sm',
    optionBorderColor = 'border-light',
    optionCustomClass = '',
    selectedOptionClass = '',
    selectionText = 'Select an option'
  } = _ref;
  const position = iconPosition === 'before' || iconPosition === 'left';
  const Icon = iconName && _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"][iconName] ? _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"][iconName] : null;
  const [isDropdownOpen, setIsDropdownOpen] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [selectedOption, setSelectedOption] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(value || selectionText);
  const [dropdownPosition, setDropdownPosition] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    width: 0,
    top: 0,
    left: 0,
    isAbove: false,
    isRight: false
  });
  const dropdownRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const contentRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const inputRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  !textAlign && (direction === 'ltr' ? textAlign = 'left' : textAlign = 'right');
  const handleOutsideClick = e => {
    if (dropdownRef.current && (dropdownRef.current.contains(e.target) || contentRef.current && contentRef.current.contains(e.target))) {
      return;
    }
    setIsDropdownOpen(false);
  };
  const toggleDropdown = () => {
    if (!isDropdownOpen) {
      const rect = dropdownRef.current.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const viewportWidth = window.innerWidth;
      setTimeout(() => {
        const contentHeight = contentRef.current ? contentRef.current.getBoundingClientRect().height : 0;
        const contentWidth = contentRef.current ? contentRef.current.getBoundingClientRect().width : 0;
        const isAbove = rect.top - contentHeight > 0 ? rect.bottom + contentHeight > viewportHeight : false;
        const isRight = rect.left - contentWidth > 0 ? rect.left + contentWidth > viewportWidth : false;
        setDropdownPosition({
          width: rect.width,
          top: isAbove ? rect.top + window.scrollY - contentHeight - 1 : rect.bottom + window.scrollY + 1,
          left: isRight ? rect.right + window.scrollX - contentWidth : rect.left + window.scrollX,
          isAbove,
          isRight
        });
      }, 0);
    }
    setIsDropdownOpen(!isDropdownOpen);
  };
  const handleOptionSelect = optionValue => {
    const isLock = optionValue === null || optionValue === void 0 ? void 0 : optionValue.startsWith('pro_');
    if (onChange) {
      onChange({
        target: {
          name,
          value: optionValue,
          id
        }
      });
    }
    if (isLock) {
      setIsDropdownOpen(false);
      return;
    }
    const selected = options.find(option => option.value === optionValue);
    setSelectedOption(selected ? selected.label : selectionText);
    setIsDropdownOpen(false);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const effectiveValue = value ? value : 'default';
    const selected = options.find(option => option.value === effectiveValue);
    setSelectedOption(selected ? selected.label : selectionText);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (isDropdownOpen) {
      document.addEventListener('mousedown', handleOutsideClick);
    } else {
      document.removeEventListener('mousedown', handleOutsideClick);
    }
    return () => {
      document.removeEventListener('mousedown', handleOutsideClick);
    };
  }, [isDropdownOpen]);
  const dropdownContent = isDropdownOpen && !noValue && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-option-container wsx-scrollbar wsx-p-".concat(containerPadding, " wsx-bg-").concat(containerBackground, " wsx-br-").concat(containerBorderRadius, " ").concat(containerBorder ? "wsx-border-default wsx-bc-".concat(containerBorderColor) : ''),
    ref: contentRef,
    style: {
      zIndex: isDropdownOpen ? 999999 : -999999,
      visibility: isDropdownOpen ? 'visible' : 'hidden',
      opacity: isDropdownOpen ? 1 : 0,
      top: isDropdownOpen ? "".concat(dropdownPosition.top, "px") : 0,
      left: isDropdownOpen ? "".concat(dropdownPosition.left, "px") : 0,
      width: "".concat(dropdownPosition.width - 10, "px")
    }
  }, options.map(option => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: option.value,
    className: "wsx-option-item ".concat(optionBorderBottom ? 'wsx-border-bottom' : '', " wsx-br-").concat(optionBorderRadius, " wsx-bc-").concat(optionBorderColor, " ").concat(option.value !== 'default' && option.label === selectedOption ? 'active' : '', " ").concat(optionCustomClass),
    onClick: () => handleOptionSelect(option.value),
    onKeyDown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        handleOptionSelect(option.value);
      }
    },
    tabIndex: 0,
    role: "button"
  }, option.label)));
  const getIconRotation = (dropdownOpen, rotation) => {
    if (dropdownOpen) {
      if (rotation === 'full') {
        return 'rotate(180deg)';
      } else if (rotation === 'half') {
        return 'rotate(90deg)';
      }
    }
    return 'rotate(0deg)';
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-select-wrapper ".concat(wrapperClass, " ").concat(noValue ? 'wsx-d-flex wsx-item-center wsx-gap-8' : ''),
    ref: inputRef
  }, label && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-input-label wsx-font-medium ".concat(labelClass, " ").concat(noValue ? 'wsx-mb-0 wsx-no-value' : '')
  }, label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-input-inner-wrapper  ".concat(!noValue && (borderNone ? '' : "wsx-border-default wsx-bc-".concat(borderColor)), " wsx-bg-").concat(inputBackground, " wsx-color-").concat(inputColor, " wsx-br-").concat(borderRadius),
    ref: dropdownRef,
    style: {
      minWidth,
      maxWidth
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-select ".concat(customClass),
    onClick: toggleDropdown,
    onKeyDown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        toggleDropdown();
      }
    },
    tabIndex: 0,
    role: "button",
    style: {
      textAlign,
      direction,
      padding: !noValue && (inputPadding ? inputPadding : '10px 16px'),
      gap: !noValue && iconGap
    }
  }, position && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon",
    style: {
      transition: 'transform var(--transition-md)',
      transform: getIconRotation(isDropdownOpen, iconRotation),
      color: iconColor ? iconColor : 'unset'
    }
  }, Icon ? Icon : _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"].angleDown), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-select-value wsx-ellipsis ".concat(selectedOptionClass)
  }, !noValue && selectedOption), !position && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-icon",
    style: {
      transition: 'transform var(--transition-md)',
      transform: getIconRotation(isDropdownOpen, iconRotation),
      color: iconColor ? iconColor : 'unset'
    }
  }, Icon ? Icon : _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"].angleDown)), /*#__PURE__*/react_dom__WEBPACK_IMPORTED_MODULE_2___default().createPortal(dropdownContent, document.body)));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Select);

/***/ }),

/***/ "./reactjs/src/components/Tier.js":
/*!****************************************!*\
  !*** ./reactjs/src/components/Tier.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _pages_dynamic_rules_MultiSelect__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../pages/dynamic_rules/MultiSelect */ "./reactjs/src/pages/dynamic_rules/MultiSelect.js");
/* harmony import */ var _Input__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Input */ "./reactjs/src/components/Input.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _Tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Tooltip */ "./reactjs/src/components/Tooltip.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Button */ "./reactjs/src/components/Button.js");
/* harmony import */ var _assets_scss_Tier_scss__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../assets/scss/Tier.scss */ "./reactjs/src/assets/scss/Tier.scss");

function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }








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
    deleteSpaceLeft = 'auto'
  } = _ref;
  const inputData = (fieldData, fieldName) => {
    const flag = tier[tierName] && tier[tierName].tiers && tier[tierName].tiers[index] && tier[tierName].tiers[index][fieldName];
    const defValue = flag ? tier[tierName].tiers[index][fieldName] : fieldData.default;
    const onChangeHandler = e => {
      let tierValue = e.target.value;
      if (fieldData.type === 'number' && tierValue !== '' && tierValue < 0) {
        tierValue = 1;
      }
      const parent = tier[tierName] ? tier[tierName] : {
        tiers: []
      };
      const copy = parent.tiers;
      if (!copy[index]) {
        copy.push({});
      }
      copy[index][e.target.getAttribute('data-name')] = tierValue;
      parent.tiers = copy;
      setTier(_objectSpread(_objectSpread({}, tier), {}, {
        [tierName]: parent
      }));
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_Input__WEBPACK_IMPORTED_MODULE_3__["default"], {
      label: index === 0 ? fieldData.label : '',
      id: "".concat(fieldName, "_").concat(index),
      dataName: fieldName,
      type: fieldData.type,
      name: "".concat(fieldName, "_").concat(index),
      value: defValue,
      placeholder: fieldData.placeholder,
      onChange: onChangeHandler,
      inputClass: "wsx-bg-base1",
      labelClass: labelClass
    });
  };
  const selectData = (fieldData, fieldName) => {
    const flag = tier[tierName] && tier[tierName].tiers && tier[tierName].tiers[index] && tier[tierName].tiers[index][fieldName];
    const defValue = flag ? tier[tierName].tiers[index][fieldName] : fieldData.default;
    const onChangeHandler = e => {
      var _e$target;
      const isLock = e !== null && e !== void 0 && (_e$target = e.target) !== null && _e$target !== void 0 && (_e$target = _e$target.value) !== null && _e$target !== void 0 && _e$target.startsWith('pro_') ? true : false;
      if (isLock && setPopupStatus) {
        setPopupStatus(true);
      } else {
        const parent = tier[tierName] ? tier[tierName] : {
          tiers: []
        };
        const copy = parent.tiers;
        if (!copy[index]) {
          copy.push({});
        }
        copy[index][fieldName] = e.target.value;
        parent.tiers = copy;
        setTier(_objectSpread(_objectSpread({}, tier), {}, {
          [tierName]: parent
        }));
      }
    };
    const getOptionsArray = options => {
      return Object.keys(options).map(option => ({
        value: option,
        label: options[option]
      }));
    };
    return (
      /*#__PURE__*/
      //    <Select
      //         name={`${fieldName}_${index}`}
      //         label={index==0?fieldData.label:''}
      //         labelClass={labelClass}
      //         options={getOptionsArray(fieldData.options)}
      //         value={defValue}
      //         onChange={onChangeHandler}
      //         inputBackground='base1'
      //     />
      react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
        className: "wsx-tier-price-table-wrapper"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
        className: "wsx-input-label wsx-font-medium"
      }, index === 0 ? fieldData.label : ''), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("select", {
        name: "".concat(fieldName, "_").concat(index),
        value: defValue,
        onChange: onChangeHandler,
        className: "wsx-tier-table-select",
        style: {
          background: 'base1'
        }
      }, getOptionsArray(fieldData.options).map((option, idx) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("option", {
        key: idx,
        value: option.value
      }, option.label))))
    );
  };
  const dependencyCheck = deps => {
    if (!deps) {
      return true;
    }
    const _temp = tier[tierName] && tier[tierName].tiers && tier[tierName].tiers[index] ? tier[tierName].tiers[index] : {};
    let _flag = true;
    deps.forEach(dep => {
      if (_temp[dep.key] !== dep.value) {
        _flag = false;
      }
    });
    return _flag;
  };
  const multiselectData = (fieldData, fieldName) => {
    const flag = tier[tierName] && tier[tierName].tiers && tier[tierName].tiers[index] && tier[tierName].tiers[index][fieldName];
    const defValue = flag ? tier[tierName].tiers[index][fieldName] : fieldData.default;
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_pages_dynamic_rules_MultiSelect__WEBPACK_IMPORTED_MODULE_2__["default"], {
      name: fieldName,
      value: defValue,
      options: fieldData.options,
      placeholder: fieldData.placeholder,
      onMultiSelectChangeHandler: (selectedFieldName, selectedValues) => {
        const parent = tier[tierName] ? tier[tierName] : {
          tiers: []
        };
        const copy = parent.tiers;
        if (!copy[index]) {
          copy.push({});
        }
        copy[index][selectedFieldName] = [...selectedValues];
        parent.tiers = copy;
        setTier(_objectSpread(_objectSpread({}, tier), {}, {
          [tierName]: parent
        }));
      },
      isAjax: fieldData === null || fieldData === void 0 ? void 0 : fieldData.is_ajax,
      ajaxAction: fieldData === null || fieldData === void 0 ? void 0 : fieldData.ajax_action,
      ajaxSearch: fieldData === null || fieldData === void 0 ? void 0 : fieldData.ajax_search
    });
  };
  const getTierLastIndex = () => {
    return tier && tier[tierName] && tier[tierName].tiers && tier[tierName].tiers.length !== 0 ? tier[tierName].tiers.length - 1 : 0;
  };
  const handleDeleteCondition = () => {
    const parent = _objectSpread({}, tier);
    let copy = [...parent[tierName].tiers];
    if (copy.length === 1) {
      parent[tierName].tiers[0]._conditions_for = '';
      parent[tierName].tiers[0]._conditions_operator = '';
      parent[tierName].tiers[0]._conditions_value = '';
      parent[tierName].tiers[0]._discount_type = '';
      parent[tierName].tiers[0]._min_quantity = '';
      parent[tierName].tiers[0]._discount_name = '';
      parent[tierName].tiers[0]._discount_amount = '';
      parent[tierName].tiers[0]._product_filter = '';
      parent[tierName].tiers = copy;
      setTier(parent);
      return;
    }
    copy = copy.filter((row, r) => {
      return index !== r;
    });
    parent[tierName].tiers = copy;
    setTier(parent);
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-tiers-fields",
    key: "wsx-tier-fields-".concat(tierName, "-").concat(index)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-tier-wrapper"
  }, Object.keys(fields).map((fieldName, i) => {
    switch (fields[fieldName].type) {
      case 'number':
      case 'text':
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
          key: "tier_field_".concat(i),
          className: "tier-field ".concat(tierFieldClass)
        }, inputData(fields[fieldName], fieldName));
      case 'select':
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
          key: "tier_field_".concat(i),
          className: "tier-field ".concat(tierFieldClass)
        }, selectData(fields[fieldName], fieldName));
      case 'multiselect':
        return dependencyCheck(fields[fieldName].depends_on) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
          key: "tier_field_".concat(i),
          className: "tier-field multiselect ".concat(tierFieldClass)
        }, multiselectData(fields[fieldName], fieldName));
      case 'filter':
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
          className: "wsx-filter-group"
        }, fields[fieldName] && Object.keys(fields[fieldName]).map((filterFieldName, idx) => {
          switch (fields[fieldName][filterFieldName].type) {
            case 'select':
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
                key: "tier_field_".concat(idx),
                className: "tier-field ".concat(tierFieldClass)
              }, selectData(fields[fieldName][filterFieldName], filterFieldName));
            case 'multiselect':
              return dependencyCheck(fields[fieldName][filterFieldName].depends_on) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
                key: "tier_field_".concat(idx),
                className: "tier-field multiselect ".concat(tierFieldClass)
              }, multiselectData(fields[fieldName][filterFieldName], filterFieldName));
            default:
              return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", null);
          }
        }));
      default:
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", null);
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_Tooltip__WEBPACK_IMPORTED_MODULE_5__["default"], {
    content: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Delete', 'wholesalex'),
    direction: "top",
    spaceLeft: deleteSpaceLeft,
    className: "wsx-rtl-left"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("span", {
    key: "wsx-tier-field-delete-".concat(tierName, "_").concat(index),
    className: "wsx-tier-delete",
    onClick: handleDeleteCondition,
    onKeyDown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        handleDeleteCondition;
      }
    },
    role: "button",
    tabIndex: "0"
  }, _utils_Icons__WEBPACK_IMPORTED_MODULE_4__["default"].delete_24))), index === getTierLastIndex() && (tierName === 'quantity_based' || tierName === 'conditions') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Add New Condition', 'wholesalex'),
    iconName: "plus_20",
    background: "tertiary",
    customClass: "wsx-mt-24",
    onClick: () => {
      const parent = _objectSpread({}, tier);
      if (!parent[tierName]) {
        parent[tierName] = {
          tiers: []
        };
      }
      const copy = [...parent[tierName].tiers];
      copy.push({
        _id: Date.now().toString(),
        src: src ? src : 'dynamic_rule'
      });
      parent[tierName].tiers = copy;
      setTier(parent);
    }
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tier);

/***/ }),

/***/ "./reactjs/src/components/Tooltip.js":
/*!*******************************************!*\
  !*** ./reactjs/src/components/Tooltip.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
  const [adjustedDirection, setAdjustedDirection] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(props.direction || 'top');
  const parentRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const tooltipRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const getContent = contentProps => {
    if (contentProps.type === 'element') {
      return contentProps.content;
    }
    if (contentProps.content) {
      return contentProps.content.replace(/{.*}/, '');
    }
    return '';
  };
  const calculatePosition = () => {
    if (tooltipRef.current && parentRef.current) {
      const tooltipRect = tooltipRef.current.getBoundingClientRect();
      const parentRect = parentRef.current.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      let top = parentRect.top + window.scrollY - (tooltipRect.height + 8 || 0);
      let left = parentRect.left + window.scrollX - ((tooltipRect.width - parentRect.width) / 2 || 0);
      let newDirection = props.direction || 'top';
      if (newDirection === 'top') {
        top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
        left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
      } else if (newDirection === 'bottom') {
        top = parentRect.bottom + window.scrollY + 8;
        left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
      } else if (newDirection === 'left') {
        top = parentRect.top + window.scrollY + (parentRect.height - tooltipRect.height) / 2;
        left = parentRect.left + window.scrollX - (tooltipRect.width + 8);
      } else if (newDirection === 'right') {
        top = parentRect.top + window.scrollY + (parentRect.height - tooltipRect.height) / 2;
        left = parentRect.right + window.scrollX + 8;
      }
      if (tooltipRect.bottom + 0 > viewportHeight && newDirection === 'bottom') {
        newDirection = 'top';
        top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
      } else if (tooltipRect.top < tooltipRect.height + 70 && newDirection === 'top') {
        newDirection = 'bottom';
        top = parentRect.bottom + window.scrollY + 8;
        if (tooltipRect.width + parentRect.right > viewportWidth) {
          newDirection = 'left';
          top = parentRect.top + window.scrollY + (parentRect.height - tooltipRect.height) / 2;
          left = parentRect.left + window.scrollX - (tooltipRect.width + 8);
        }
      }
      if (tooltipRect.width + left + 70 > viewportWidth && newDirection === 'right') {
        if (tooltipRect.left > tooltipRect.width + 8) {
          newDirection = 'left';
          left = parentRect.left + window.scrollX - (tooltipRect.width + 8);
        } else if (tooltipRect.top < tooltipRect.height + 70) {
          newDirection = 'bottom';
          top = parentRect.bottom + window.scrollY + 8;
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        } else {
          newDirection = 'top';
          top = parentRect.top + window.scrollY - (tooltipRect.height + 8);
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        }
      } else if (parentRect.left < tooltipRect.width + 8 && newDirection === 'left') {
        if (tooltipRect.width + parentRect.right + 70 < viewportWidth) {
          newDirection = 'right';
          left = parentRect.right + window.scrollX + 8;
        } else if (tooltipRect.top < tooltipRect.height + 70) {
          newDirection = 'bottom';
          top = parentRect.bottom + window.scrollY + 8;
          left = parentRect.left + window.scrollX + (parentRect.width - tooltipRect.width) / 2;
        } else {
          newDirection = 'top';
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [active]);
  const tooltipContent = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: tooltipRef,
    className: "wsx-tooltip-content wsx-font-regular ".concat(props.tooltipContentClass, " ").concat(adjustedDirection),
    style: {
      position: 'absolute',
      zIndex: active ? 999999 : -999999,
      top: active ? position.top : 0,
      left: active ? position.left : 0,
      visibility: active ? 'visible' : 'hidden',
      opacity: active ? 1 : 0,
      transition: 'opacity var(--transition-md) ease-in-out'
    }
  }, getContent(props));
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: parentRef,
    className: "wsx-tooltip ".concat(props.className),
    onMouseEnter: showToolTip,
    onMouseLeave: hideToolTip,
    style: {
      margin: "".concat(props.spaceTop || '0', " ").concat(props.spaceRight || '0', " ").concat(props.spaceBottom || '0', " ").concat(props.spaceLeft || '0')
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "".concat(!props.onlyText && "wsx-lh-0 wsx-icon-wrapper ".concat(props.parentColor ? "wsx-color-".concat(props.parentColor) : 'wsx-color-secondary'), " wsx-w-fit")
  }, props.children)), /*#__PURE__*/react_dom__WEBPACK_IMPORTED_MODULE_1___default().createPortal(tooltipContent, document.body));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tooltip);

/***/ }),

/***/ "./reactjs/src/components/UpgradeProPopUp.js":
/*!***************************************************!*\
  !*** ./reactjs/src/components/UpgradeProPopUp.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _PopupModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PopupModal */ "./reactjs/src/components/PopupModal.js");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button */ "./reactjs/src/components/Button.js");




const UpgradeProPopUp = _ref => {
  let {
    UpgradeUrl = '',
    addonCount = '',
    title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Unlock', 'wholesalex'),
    onClose
  } = _ref;
  if (UpgradeUrl === '') {
    var _wholesalex;
    UpgradeUrl = (_wholesalex = wholesalex) === null || _wholesalex === void 0 ? void 0 : _wholesalex.pro_link;
  }
  const popupContent = () => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("img", {
      className: "wsx-addon-popup-image",
      src: wholesalex.url + '/assets/img/unlock.svg',
      alt: "Unlock Icon"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-d-flex wsx-item-center wsx-justify-center wsx-gap-4 wsx-mt-4"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium"
    }, title), addonCount && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium"
    }, addonCount, "+", ' ', (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Addons with', 'wholesalex'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-d-flex wsx-item-center wsx-justify-center wsx-gap-4 wsx-mb-16"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('WholesaleX', 'wholesalex')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-title wsx-font-18 wsx-font-medium wsx-color-primary"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Pro', 'wholesalex'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      buttonLink: "https://getwholesalex.com/pricing/?utm_source=wholesalex-menu&utm_medium=email-unlock_addon-upgrade_to_pro&utm_campaign=wholesalex-DB",
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Upgrade to Pro', 'wholesalex'),
      background: "secondary",
      iconName: "growUp",
      customClass: "wsx-w-auto wsx-br-lg wsx-justify-center wsx-font-16"
    }));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_PopupModal__WEBPACK_IMPORTED_MODULE_2__["default"], {
    className: "wsx-pro-modal",
    renderContent: popupContent,
    onClose: onClose
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UpgradeProPopUp);

/***/ }),

/***/ "./reactjs/src/pages/dynamic_rules/MultiSelect.js":
/*!********************************************************!*\
  !*** ./reactjs/src/pages/dynamic_rules/MultiSelect.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _components_Tooltip__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/Tooltip */ "./reactjs/src/components/Tooltip.js");




const MultiSelect = _ref => {
  let {
    name,
    value,
    placeholder,
    customClass,
    onMultiSelectChangeHandler,
    isDisable,
    isAjax,
    ajaxAction,
    ajaxSearch,
    dependsValue
  } = _ref;
  const [showList, setShowList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [selectedOptions, setSelectedOptions] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(value);
  const [optionList, setOptionList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [searchValue, setSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
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
        value: optionValue
      } = op;
      return optionValue.toString().toLowerCase() !== option.value.toString().toLowerCase();
    });
    setOptionList(searchResult);
    setTempSearchValue('');
    onMultiSelectChangeHandler(name, [...selectedOptions, option]);
    setShowList(false);
  };
  const deleteOption = option => {
    const selectedOptionAfterDeleted = selectedOptions.filter(op => {
      const {
        value: optionValue
      } = op;
      return optionValue.toString().toLowerCase() !== option.value.toString().toLowerCase();
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
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);
  const performAjaxSearch = async signal => {
    setIsSearching(true);
    const attr = {
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
        signal
      });
      if (res.status) {
        var _res$data;
        let selectedOptionValues = [];
        if (selectedOptions.length > 0) {
          selectedOptionValues = selectedOptions.map(option => option.value);
        }
        const searchResult = res === null || res === void 0 || (_res$data = res.data) === null || _res$data === void 0 ? void 0 : _res$data.filter(option => {
          const {
            value: optionValue
          } = option;
          return selectedOptionValues.indexOf(optionValue) === -1;
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
      }
    }
  };
  const getOptions = async signal => {
    setIsSearching(true);
    const attr = {
      type: 'get',
      action: 'dynamic_rule_action',
      nonce: wholesalex.nonce,
      ajax_action: ajaxAction
    };
    if (dependsValue) {
      attr.depends = dependsValue;
    }
    try {
      const res = await wp.apiFetch({
        path: '/wholesalex/v1/dynamic_rule_action',
        method: 'POST',
        data: attr,
        signal
      });
      if (res.status) {
        var _res$data2;
        let selectedOptionValues = [];
        if (selectedOptions.length > 0) {
          selectedOptionValues = selectedOptions.map(option => option.value);
        }
        const searchResult = res === null || res === void 0 || (_res$data2 = res.data) === null || _res$data2 === void 0 ? void 0 : _res$data2.filter(option => {
          const {
            value: optionValue
          } = option;
          return selectedOptionValues.indexOf(optionValue) === -1;
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
        name: optionName,
        value: optionValue
      } = option;
      return optionName.toLowerCase().includes(searchValue.toLowerCase()) && selectedOptionValues.indexOf(optionValue) === -1;
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
          abortController.current.abort('Duplicate');
        }
      };
    }
    performNonAjaxSearch();
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tempSearchValue]);

  //Remove Product title extra quote from esc_attr
  const decodeHtmlEntities = str => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-wrapper ".concat(isDisable ? 'locked' : ''),
    key: "wsx-multiselect-".concat(name)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-inputs"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-input-wrapper"
  }, selectedOptions.length > 0 && selectedOptions.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      key: "wsx-multiselect-opt-".concat(name, "-").concat(option.value, "-").concat(index),
      className: "wsx-selected-option"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      tabIndex: -1,
      className: "wsx-icon-cross wsx-lh-0",
      onClick: () => deleteOption(option),
      onKeyDown: e => {
        if (e.key === 'Enter' || e.key === ' ') {
          deleteOption(option);
        }
      },
      role: "button"
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_2__["default"].cross), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_3__["default"], {
      content: option.name,
      position: "top",
      onlyText: true
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
      href: option.permalink,
      target: "_blank",
      rel: "noopener noreferrer"
    }, decodeHtmlEntities(option.name)))));
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-wrapper ".concat(selectedOptions.length && selectedOptions.length !== 0 ? '' : 'wsx-w-full')
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    key: "wsx-input".concat(name),
    disabled: isDisable ? true : false,
    id: name,
    tabIndex: 0,
    autoComplete: "off",
    value: tempSearchValue,
    className: "wsx-input ".concat(customClass),
    placeholder: selectedOptions.length > 0 ? '' : placeholder,
    onChange: e => onInputChangeHandler(e),
    onClick: e => onInputChangeHandler(e)
  })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: myRef,
    key: "wsx-".concat(name)
  }, showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null, !isSearching && optionList.length > 0 && tempSearchValue.length > 1 && showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-card wsx-multiselect-options wsx-scrollbar",
    key: "wsx-opt-".concat(name)
  }, optionList.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-multiselect-option",
      key: "wsx-opt-".concat(name, "-").concat(option.value, "-").concat(index),
      onClick: () => selectOption(option),
      onKeyDown: e => {
        if (e.key === 'Enter' || e.key === ' ') {
          deleteOption(option);
        }
      },
      role: "button",
      tabIndex: -1
    }, option.name ? decodeHtmlEntities(option.name) : decodeHtmlEntities(option.userName));
  })), !isSearching && tempSearchValue.length > 1 && showList && optionList.length === 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: "wsx-".concat(name, "-not-found"),
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No Data Found! Please try with another keyword.', 'wholesalex'))), !isSearching && tempSearchValue.length < 2 && showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: "wsx-".concat(name, "-not-found"),
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enter 2 or more characters to search.', 'wholesalex'))), isSearching && showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: "wsx-".concat(name, "-not-found"),
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Searching…', 'wholesalex'))))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MultiSelect);

/***/ }),

/***/ "./reactjs/src/pages/user_profile/MultiSelect.js":
/*!*******************************************************!*\
  !*** ./reactjs/src/pages/user_profile/MultiSelect.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _components_Tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../components/Tooltip */ "./reactjs/src/components/Tooltip.js");



const MultiSelect = _ref => {
  let {
    name,
    value,
    placeholder,
    customClass,
    onMultiSelectChangeHandler,
    isDisable,
    isAjax,
    ajaxAction,
    ajaxSearch,
    dependsValue
  } = _ref;
  const [showList, setShowList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [selectedOptions, setSelectedOptions] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(value);
  const [optionList, setOptionList] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [searchValue, setSearchValue] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
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
        value: optionValue
      } = op;
      return optionValue.toString().toLowerCase() !== option.value.toString().toLowerCase();
    });
    setOptionList(searchResult);
    setShowList(false);
    setTempSearchValue('');
    onMultiSelectChangeHandler(name, [...selectedOptions, option]);
  };
  const deleteOption = option => {
    const selectedOptionAfterDeleted = selectedOptions.filter(op => {
      const {
        value: optionValue
      } = op;
      return optionValue.toString().toLowerCase() !== option.value.toString().toLowerCase();
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
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);
  const performAjaxSearch = async signal => {
    setIsSearching(true);
    const attr = {
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
        signal
      });
      if (res.status) {
        var _res$data;
        let selectedOptionValues = [];
        if (selectedOptions.length > 0) {
          selectedOptionValues = selectedOptions.map(option => option.value);
        }
        const searchResult = res === null || res === void 0 || (_res$data = res.data) === null || _res$data === void 0 ? void 0 : _res$data.filter(option => {
          const {
            value: optionValue
          } = option;
          return selectedOptionValues.indexOf(optionValue) === -1;
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
      }
    }
  };
  const getOptions = async signal => {
    setIsSearching(true);
    const attr = {
      type: 'get',
      action: 'dynamic_rule_action',
      nonce: wholesalex.nonce,
      ajax_action: ajaxAction
    };
    if (dependsValue) {
      attr.depends = dependsValue;
    }
    try {
      const res = await wp.apiFetch({
        path: '/wholesalex/v1/dynamic_rule_action',
        method: 'POST',
        data: attr,
        signal
      });
      if (res.status) {
        var _res$data2;
        let selectedOptionValues = [];
        if (selectedOptions.length > 0) {
          selectedOptionValues = selectedOptions.map(option => option.value);
        }
        const searchResult = res === null || res === void 0 || (_res$data2 = res.data) === null || _res$data2 === void 0 ? void 0 : _res$data2.filter(option => {
          const {
            value: optionValue
          } = option;
          return selectedOptionValues.indexOf(optionValue) === -1;
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
        name: optionName,
        value: optionValue
      } = option;
      return optionName.toLowerCase().includes(searchValue.toLowerCase()) && selectedOptionValues.indexOf(optionValue) === -1;
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
          abortController.current.abort('Duplicate');
        }
      };
    }
    performNonAjaxSearch();
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tempSearchValue]);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-wrapper ".concat(isDisable ? 'locked' : ''),
    key: "wsx-multiselect-".concat(name)
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-inputs"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-input-wrapper"
  }, selectedOptions.length > 0 && selectedOptions.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      key: "wsx-multiselect-opt-".concat(name, "-").concat(option.value, "-").concat(index),
      className: "wsx-selected-option"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", {
      tabIndex: -1,
      className: "wsx-icon-cross wsx-lh-0",
      onClick: () => deleteOption(option),
      onKeyDown: e => {
        if (e.key === 'Enter' || e.key === ' ') {
          deleteOption(option);
        }
      },
      role: "button"
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_1__["default"].cross), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_components_Tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
      content: option.name,
      position: "top",
      onlyText: true
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "multiselect-option-name"
    }, option.name)));
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-wrapper ".concat(selectedOptions.length && selectedOptions.length !== 0 ? '' : 'wsx-w-full')
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("input", {
    key: "wsx-input-".concat(name),
    disabled: isDisable ? true : false,
    id: name,
    tabIndex: 0,
    autoComplete: "off",
    value: tempSearchValue,
    className: "wsx-input ".concat(customClass),
    placeholder: selectedOptions.length > 0 ? '' : placeholder,
    onChange: e => onInputChangeHandler(e),
    onClick: e => onInputChangeHandler(e)
  })))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    ref: myRef,
    key: "wsx-".concat(name)
  }, showList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, !isSearching && optionList.length > 0 && tempSearchValue.length > 1 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-card wsx-multiselect-options wsx-scrollbar",
    key: "wsx-opt-".concat(name)
  }, optionList.map((option, index) => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "wsx-multiselect-option",
      key: "wsx-opt-".concat(name, "-").concat(option.value, "-").concat(index),
      onClick: () => selectOption(option),
      onKeyDown: e => {
        if (e.key === 'Enter' || e.key === ' ') {
          selectOption(option);
        }
      },
      role: "button",
      tabIndex: "0"
    }, option.name);
  })), !isSearching && tempSearchValue.length > 1 && optionList.length === 0 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: "wsx-".concat(name, "-not-found"),
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, wholesalex_profile.i18n.no_data_found)), !isSearching && tempSearchValue.length < 2 && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: "wsx-".concat(name, "-not-found"),
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, wholesalex_profile.i18n.enter_more_character)), isSearching && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    key: "wsx-".concat(name, "-not-found"),
    className: "wsx-card wsx-multiselect-options wsx-scrollbar"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "wsx-multiselect-option-message"
  }, wholesalex_profile.i18n.searching)))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MultiSelect);

/***/ }),

/***/ "./reactjs/src/pages/user_profile/Profile.js":
/*!***************************************************!*\
  !*** ./reactjs/src/pages/user_profile/Profile.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _MultiSelect__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./MultiSelect */ "./reactjs/src/pages/user_profile/MultiSelect.js");
/* harmony import */ var _components_Tier__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../components/Tier */ "./reactjs/src/components/Tier.js");
/* harmony import */ var _components_Modal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../components/Modal */ "./reactjs/src/components/Modal.js");
/* harmony import */ var _components_Select__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../components/Select */ "./reactjs/src/components/Select.js");
/* harmony import */ var _utils_Icons__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../utils/Icons */ "./reactjs/src/utils/Icons.js");
/* harmony import */ var _components_Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../components/Button */ "./reactjs/src/components/Button.js");
/* harmony import */ var _components_LoadingGif__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../components/LoadingGif */ "./reactjs/src/components/LoadingGif.js");
/* harmony import */ var _components_UpgradeProPopUp__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../components/UpgradeProPopUp */ "./reactjs/src/components/UpgradeProPopUp.js");

function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0,_babel_runtime_helpers_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }









function Profile() {
  const [fields, setFields] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [appState, setAppState] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({
    loading: true,
    currentWindow: 'new',
    index: -1,
    loadingOnSave: false
  });
  const [value, setValue] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [tierStatus, setTierStatus] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [tier, setTier] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const inititalTier = {
    _id: Date.now().toString(),
    _discount_type: '',
    _discount_amount: '',
    _min_quantity: '',
    _product_filter: '',
    src: 'profile'
  };
  const _isProActivate = wholesalex.is_pro_activated;
  const [popUpStatus, setPopUpStatus] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const fetchData = async function () {
    let type = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'get';
    let userAction = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
    const attr = {
      type,
      action: 'profile_action',
      nonce: wholesalex.nonce,
      user_id: document.getElementById('user_id').value
    };
    if (type === 'post') {
      attr.user_action = userAction;
      attr.user_role = value._wholesalex_role;
    }
    wp.apiFetch({
      path: '/wholesalex/v1/profile_action',
      method: 'POST',
      data: attr
    }).then(res => {
      if (res.success) {
        if (type === 'get') {
          setFields(res.data.default);
          setAppState(_objectSpread(_objectSpread({}, appState), {}, {
            loading: false
          }));
          if (res.data.tiers) {
            setTier(res.data.tiers);
          }
          if (res.data.settings) {
            setValue(res.data.settings);
          }
        } else {
          if (userAction === 'delete_user') {
            window.location = res.data.redirect;
          }
          if (res.data.settings) {
            setValue(_objectSpread(_objectSpread({}, value), res.data.settings));
          }
        }
      }
    });
  };
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    fetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
  const dependencyCheck = deps => {
    let _flag = true;
    deps.forEach(dep => {
      if (value[dep.key] !== dep.value) {
        _flag = false;
      }
    });
    return _flag;
  };
  const inputData = function (fieldData, field) {
    let fieldLabel = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    let inputClass = arguments.length > 3 ? arguments[3] : undefined;
    const defValue = value[field] ? value[field] : fieldData.default;
    const depsFullfilled = fieldData.depends_on ? dependencyCheck(fieldData.depends_on) : true;
    return depsFullfilled && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      key: field,
      className: "wsx-profile-item-wrapper"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("label", {
      className: "wsx-label wsx-input-label wsx-text-space-break",
      htmlFor: field
    }, fieldLabel), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("input", {
      disabled: fieldData.is_disable ? true : false,
      type: fieldData.type,
      onWheel: e => {
        e.target.blur();
      },
      id: field,
      name: field,
      value: defValue,
      onChange: e => {
        setValue(_objectSpread(_objectSpread({}, value), {}, {
          [field]: e.target.value
        }));
      },
      className: "wsx-input ".concat(inputClass)
    }));
  };
  const getOptionsArray = options => {
    return Object.keys(options).map(option => ({
      value: option === '' ? 'default' : option,
      label: options[option]
    }));
  };
  const selectData = function (fieldData, field) {
    let fieldLabel = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    const defValue = value[field] ? value[field] : fieldData.default;
    const depsFullfilled = fieldData.depends_on ? dependencyCheck(fieldData.depends_on) : true;
    return depsFullfilled && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Select__WEBPACK_IMPORTED_MODULE_5__["default"], {
      wrapperClass: "wsx-profile-item-wrapper",
      labelClass: "wsx-text-space-break",
      id: field,
      label: fieldLabel,
      options: getOptionsArray(fieldData.options),
      optionCustomClass: "wsx-w-full wsx-text-space-break",
      selectedOptionClass: "wsx-text-space-break",
      value: defValue,
      onChange: e => {
        setValue(_objectSpread(_objectSpread({}, value), {}, {
          [field]: e.target.value
        }));
      }
    });
  };
  const multiselectData = function (fieldData, field) {
    let fieldLabel = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    const defValue = value[field] ? value[field] : fieldData.default;
    const depsFullfilled = fieldData.depends_on ? dependencyCheck(fieldData.depends_on) : true;
    const optionsDependend = fieldData.options_dependent_on ? fieldData.options_dependent_on : false;
    const _options = fieldData.options;
    let flag = true;
    let dependsValue = '';
    if (optionsDependend) {
      if (value[optionsDependend]) {
        dependsValue = value[optionsDependend];
      } else {
        flag = false;
      }
    }
    if (dependsValue !== '') {
      flag = true;
    }
    return depsFullfilled && flag && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      className: "wsx-profile-item-wrapper"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("label", {
      className: "wsx-label wsx-input-label wsx-text-space-break",
      htmlFor: field
    }, fieldLabel), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_MultiSelect__WEBPACK_IMPORTED_MODULE_2__["default"], {
      name: field,
      value: defValue,
      options: _options,
      placeholder: fieldData.placeholder,
      onMultiSelectChangeHandler: (fieldName, selectedValues) => {
        const copy = _objectSpread({}, value);
        copy[fieldName] = [...selectedValues];
        setValue(_objectSpread(_objectSpread({}, value), copy));
      },
      isAjax: fieldData === null || fieldData === void 0 ? void 0 : fieldData.is_ajax,
      ajaxAction: fieldData === null || fieldData === void 0 ? void 0 : fieldData.ajax_action,
      ajaxSearch: fieldData === null || fieldData === void 0 ? void 0 : fieldData.ajax_search,
      dependsValue: optionsDependend ? dependsValue : false
    }));
  };
  const buttonData = function (fieldData, tierName) {
    let type = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    return type === 'add' ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: fieldData.label,
      background: "tertiary",
      iconName: "plus_20",
      onClick: e => {
        e.preventDefault();
        const copy = _objectSpread({}, tier);
        copy[tierName].tiers.push(inititalTier);
        setTier(copy);
      }
    }) : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: fieldData.label,
      background: "tertiary",
      onClick: e => {
        e.preventDefault();
        const copy = _objectSpread({}, tier);
        copy[tierName].tiers.push(inititalTier);
        setTier(copy);
      }
    });
  };
  const UpgradeButton = fieldData => {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: fieldData.label,
      onClick: e => {
        e.preventDefault();
        setPopUpStatus(true);
      },
      background: "secondary",
      iconName: "angleRight_24",
      iconPosition: "after",
      iconAnimation: "icon-left"
    });
  };
  const tierData = (fieldData, tierName, lockStatus) => {
    const defaultTier = fieldData._tiers.data;
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      key: tierName,
      className: "wsx-accordion-wrapper-profile"
    }, tier && tier[tierName] && tier[tierName].tiers.map((t, index) => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Tier__WEBPACK_IMPORTED_MODULE_3__["default"], {
      key: "wholesalex_".concat(tierName, "_tier_").concat(index),
      fields: defaultTier,
      tier: tier,
      setTier: setTier,
      index: index,
      tierName: tierName,
      tierFieldClass: "wsx-w-full",
      deleteSpaceLeft: "auto"
    })), !lockStatus && fieldData._tiers.add.type === 'button' && buttonData(fieldData._tiers.add, tierName, 'add'), lockStatus && fieldData._tiers.upgrade_pro.type === 'button' && UpgradeButton(fieldData._tiers.upgrade_pro, tierName));
  };
  const tiersData = function (fieldsData, section) {
    var _tier$section;
    let fieldLabel = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    const parent = _objectSpread({}, tier);
    const copy = parent[section] ? parent[section] : {};
    if (!copy.tiers) {
      copy.tiers = [];
      copy.tiers.push(_objectSpread({}, inititalTier));
      parent[section] = copy;
      setTier(parent);
    }
    let _limit = 99999999999;
    const isPro = fieldsData.is_pro;
    if (isPro) {
      var _fieldsData$pro_data;
      _limit = (_fieldsData$pro_data = fieldsData.pro_data) === null || _fieldsData$pro_data === void 0 ? void 0 : _fieldsData$pro_data.value;
    }
    if (!_limit) {
      _limit = 99999999999;
    }
    const isLock = (tier === null || tier === void 0 || (_tier$section = tier[section]) === null || _tier$section === void 0 ? void 0 : _tier$section.tiers.length) >= _limit && !_isProActivate;
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("section", {
      key: "wsx-".concat(section),
      className: section + ' wsx-accordion-wrapper'
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      className: "wsx-accordion-header",
      onClick: () => {
        setTierStatus(_objectSpread(_objectSpread({}, tierStatus), {}, {
          [section]: !tierStatus[section]
        }));
      },
      onKeyDown: e => {
        if (e.key === 'Enter' || e.key === ' ') {
          setTierStatus(_objectSpread(_objectSpread({}, tierStatus), {}, {
            [section]: !tierStatus[section]
          }));
        }
      },
      role: "button",
      tabIndex: "0"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      className: "wsx-accordion-title",
      key: "whx-role-header"
    }, fieldLabel), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("span", {
      className: "wsx-icon ".concat(tierStatus[section] ? 'active' : '')
    }, _utils_Icons__WEBPACK_IMPORTED_MODULE_6__["default"].angleDown_24)), tierStatus[section] && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      className: "wsx-accordion-body"
    }, fieldsData.attr && Object.keys(fieldsData.attr).map(fieldData => {
      switch (fieldsData.attr[fieldData].type) {
        case 'tier':
          return tierData(fieldsData.attr[fieldData], section, isLock);
        default:
          return [];
      }
    })));
  };
  const [deleteUserModal, setDeleteUserModal] = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const profileSettingsData = sectionData => {
    const onButtonClick = e => {
      e.preventDefault();
      switch (e.target.getAttribute('data-target')) {
        case 'approve_user':
        case 'active_user':
          fetchData('post', 'approve_user');
          break;
        case 'reject_user':
          fetchData('post', 'reject_user');
          break;
        case 'delete_user':
          setDeleteUserModal(true);
          break;
        case 'deactive_user':
          fetchData('post', 'deactive_user');
          break;
        default:
          break;
      }
    };
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      key: 'profile_settings',
      className: "wsx-profile-section wsx-profile-settings-section wsx-accordion-wrapper"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      className: "wsx-accordion-header wsx-accordion-title"
    }, sectionData.label), deleteUserModal && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Modal__WEBPACK_IMPORTED_MODULE_4__["default"], {
      status: deleteUserModal,
      setStatus: setDeleteUserModal,
      title: wholesalex_profile.i18n.this_user,
      onDelete: () => {
        fetchData('post', 'delete_user');
      }
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
      className: "wsx-accordion-body"
    }, Object.keys(sectionData.attr).map(fieldName => {
      const _data = sectionData.attr[fieldName];
      switch (sectionData.attr[fieldName].type) {
        case 'select':
          return selectData(_data, fieldName, sectionData.attr[fieldName].label);
        case 'text':
          return inputData(_data, fieldName, sectionData.attr[fieldName].label, 'wsx-input-inner-wrapper');
        case 'buttons':
          return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
            key: 'profile_settings_buttons',
            className: "profile-setting-buttons"
          }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("button", {
            className: "hidden",
            onClick: e => {
              e.preventDefault();
            }
          }), (value.__wholesalex_status === 'pending' || value.__wholesalex_status === '') && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
            className: "user-status-pending-buttons wsx-btn-group"
          }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_approve,
            onClick: onButtonClick,
            dataTarget: "approve_user",
            background: "approve"
          }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_reject,
            onClick: onButtonClick,
            dataTarget: "reject_user",
            background: "reject"
          }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_delete,
            onClick: onButtonClick,
            dataTarget: "delete_user",
            background: "delete",
            iconName: "delete"
          })), value.__wholesalex_status === 'reject' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
            className: "user-status-pending-buttons wsx-btn-group"
          }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_approve,
            onClick: onButtonClick,
            dataTarget: "approve_user",
            background: "approve"
          }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_delete,
            onClick: onButtonClick,
            dataTarget: "delete_user",
            background: "delete",
            iconName: "delete"
          })), value.__wholesalex_status === 'inactive' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
            className: "user-status-pending-buttons wsx-btn-group"
          }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_active,
            onClick: onButtonClick,
            dataTarget: "active_user",
            background: "approve"
          }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_delete,
            onClick: onButtonClick,
            dataTarget: "delete_user",
            background: "delete",
            iconName: "delete"
          })), value.__wholesalex_status === 'active' && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
            className: "user-status-active-buttons wsx-btn-group"
          }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_delete,
            onClick: onButtonClick,
            dataTarget: "delete_user",
            background: "delete",
            iconName: "delete"
          }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
            label: _data.btn_deactive,
            onClick: onButtonClick,
            dataTarget: "deactive_user",
            background: "deactive"
          })));
        default:
          return [];
      }
    })));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-profile wsx-card"
  }, appState.loading && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_LoadingGif__WEBPACK_IMPORTED_MODULE_8__["default"], null), !appState.loading && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement((react__WEBPACK_IMPORTED_MODULE_1___default().Fragment), null, Object.keys(fields).map(sections => /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-profile-settings",
    key: sections
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-profile-heading"
  }, fields[sections].label), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
    className: "wsx-profile-body"
  }, Object.keys(fields[sections].attr).map(section => {
    switch (section) {
      case '_profile_user_settings_section':
        return profileSettingsData(fields[sections].attr[section]);
      default:
        return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("div", {
          key: section,
          className: "wsx-profile-section ".concat(section)
        }, Object.keys(fields[sections].attr[section].attr).map(field => {
          const _fieldData = fields[sections].attr[section].attr[field];
          const _fieldLabel = _fieldData.label;
          switch (_fieldData.type) {
            case 'number':
            case 'text':
              return inputData(_fieldData, field, _fieldLabel, 'wsx-input');
            case 'select':
              return selectData(_fieldData, field, _fieldLabel);
            case 'multiselect':
              return multiselectData(_fieldData, field, _fieldLabel);
            case 'tiers':
              return tiersData(_fieldData, field, _fieldLabel);
            default:
              return [];
          }
        }));
    }
  }))))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("input", {
    type: "hidden",
    value: JSON.stringify(tier),
    name: "wholesalex_profile_tiers"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement("input", {
    type: "hidden",
    value: JSON.stringify(value),
    name: "wholesalex_profile_settings"
  }), popUpStatus && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_1___default().createElement(_components_UpgradeProPopUp__WEBPACK_IMPORTED_MODULE_9__["default"], {
    title: wholesalex_profile.i18n.unlock_heading,
    onClose: () => setPopUpStatus(false)
  }));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Profile);

/***/ }),

/***/ "./reactjs/src/utils/Icons.js":
/*!************************************!*\
  !*** ./reactjs/src/utils/Icons.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
icons.minus = /*#__PURE__*/React.createElement("svg", {
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
  d: "M6 12h12"
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
icons.preMadeDesign = /*#__PURE__*/React.createElement("svg", {
  xmlns: "http://www.w3.org/2000/svg",
  width: "20",
  height: "20",
  fill: "none",
  viewBox: "0 0 20 20"
}, /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M18.419 5.769 7.394 1.3a.682.682 0 0 0-.27-.05.697.697 0 0 0-.643.431l-.65 1.613.7.15.6-1.494 8.063 3.263 1.28.518 1.676.681-3.325 8.213-.563 2.744v.362c0 .038 0 .069-.006.106.025-.043.05-.08.069-.13L18.794 6.68a.694.694 0 0 0-.382-.912h.007Z"
}), /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M16.02 5.381 4.6 3.044a.694.694 0 0 0-.806.531l-.368 1.819h.719l.337-1.663 8.175 1.663 1.5.3 1.706.35-1.6 7.881v3.438L16.544 6.18a.684.684 0 0 0-.53-.806l.006.006Z"
}), /*#__PURE__*/React.createElement("path", {
  fill: "currentColor",
  d: "M13.588 5.394H1.93a.683.683 0 0 0-.681.681v11.656c0 .375.306.681.681.681h11.656a.683.683 0 0 0 .682-.68V6.074a.683.683 0 0 0-.681-.681ZM9.456 7.68c.738 0 1.338.6 1.338 1.338a1.339 1.339 0 0 1-2.675 0c0-.738.6-1.338 1.337-1.338Zm3.013 9.406H2.85a.354.354 0 0 1-.3-.537l3.5-5.588a.35.35 0 0 1 .594 0l1.981 3.163a.35.35 0 0 0 .594 0l.712-1.137a.35.35 0 0 1 .594 0l2.231 3.562a.35.35 0 0 1-.3.537h.013Z"
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Tier.scss":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Tier.scss ***!
  \************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

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
___CSS_LOADER_EXPORT___.push([module.id, `.wsx-tiers-fields {
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
  color: var(--color-text-medium); }

.wsx-condition-container .wsx-tier-wrapper, .wsx-condition-container .wsx-tier-header {
  grid-template-columns: repeat(3, 1fr) 40px; }

.wsx-single-product-settings-wrapper .wsx-tier-wrapper {
  display: grid;
  grid-template-columns: repeat(3, 1fr) 40px;
  gap: 12px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--color-border-primary); }

.wsx-single-product-settings-wrapper > .wsx-tier-wrapper {
  grid-template-columns: 1fr; }

.wsx-bundle-product-wrapper > .wsx-tier-wrapper {
  grid-template-columns: 1fr; }

.wsx-tier-price-table-wrapper select.wsx-tier-table-select {
  width: 100%;
  border-radius: 8px;
  height: 40px; }

.wsx-form-setting .wsx-tier-price-table-wrapper select.wsx-tier-table-select {
  width: fit-content !important;
  border-radius: 8px; }

.wsx-form-setting .wsx-tier-wrapper, .wsx-tier-header {
  grid-template-columns: repeat(3, 1fr) 40px; }

@media (max-width: 768px) {
  .wsx-tier-wrapper, .wsx-tier-header {
    grid-template-columns: repeat(1, 1fr);
    gap: 12px; } }

@media (max-width: 1400px) {
  .wsx-tier-wrapper, .wsx-tier-header {
    grid-template-columns: repeat(1, 1fr);
    gap: 12px; } }

@media (max-width: 576px) {
  .wsx-condition-container .wsx-tier-wrapper {
    grid-template-columns: 1fr; } }
`, "",{"version":3,"sources":["webpack://./reactjs/src/assets/scss/Tier.scss"],"names":[],"mappings":"AAAA;EACI,kBAAkB,EAAA;;AAGlB;EACI,aAAa;EACb,0CAA0C;EAC1C,SAAS;EACT,gBAAgB,EAAA;EAChB;IACI,0CAA0C,EAAA;;AAGlD;EACI,mBAAmB,EAAA;;AAGnB;EACI,iBAAiB;EACjB,+BAA+B,EAAA;;AAI3C;EAEQ,0CAA0C,EAAA;;AAGlD;EACI,aAAa;EACb,0CAA0C;EAC1C,SAAS;EACT,mBAAmB;EACnB,oDAAoD,EAAA;;AAGxD;EACI,0BAA0B,EAAA;;AAE9B;EACI,0BAA0B,EAAA;;AAG9B;EAEW,WAAW;EACX,kBAAkB;EAClB,YAAY,EAAA;;AAGvB;EAEW,6BAA6B;EAC7B,kBAAkB,EAAA;;AAG7B;EACK,0CAA0C,EAAA;;AAE/C;EAEQ;IACI,qCAAqC;IACrC,SAAS,EAAA,EACZ;;AAGT;EAEQ;IACI,qCAAqC;IACrC,SAAS,EAAA,EACZ;;AAGT;EACI;IAEQ,0BAA0B,EAAA,EAC7B","sourcesContent":[".wsx-tiers-fields {\r\n    margin-bottom: 8px;\r\n}\r\n.wsx-tier {\r\n    &-wrapper, &-header {\r\n        display: grid;\r\n        grid-template-columns: repeat(4, 1fr) 40px;\r\n        gap: 24px;\r\n        align-items: end;\r\n        &2 {\r\n            grid-template-columns: repeat(3, 1fr) 40px;\r\n        }\r\n    }\r\n    &-wrapper {\r\n        margin-bottom: 16px;\r\n    }\r\n    &-header {\r\n        &-item {\r\n            padding-left: 4px;\r\n            color: var(--color-text-medium);\r\n        }\r\n    }\r\n}\r\n.wsx-condition-container .wsx-tier {\r\n    &-wrapper, &-header {\r\n        grid-template-columns: repeat(3, 1fr) 40px;\r\n    }\r\n}\r\n.wsx-single-product-settings-wrapper .wsx-tier-wrapper {\r\n    display: grid;\r\n    grid-template-columns: repeat(3, 1fr) 40px;\r\n    gap: 12px;\r\n    margin-bottom: 16px;\r\n    border-bottom: 1px solid var(--color-border-primary);\r\n}\r\n\r\n.wsx-single-product-settings-wrapper >.wsx-tier-wrapper {\r\n    grid-template-columns: 1fr;\r\n}\r\n.wsx-bundle-product-wrapper >.wsx-tier-wrapper {\r\n    grid-template-columns: 1fr;\r\n}\r\n\r\n.wsx-tier-price-table-wrapper{\r\n       select.wsx-tier-table-select{\r\n           width: 100%;\r\n           border-radius: 8px;\r\n           height: 40px;\r\n       }\r\n   }\r\n.wsx-form-setting .wsx-tier-price-table-wrapper{\r\n       select.wsx-tier-table-select{\r\n           width: fit-content !important;\r\n           border-radius: 8px;\r\n       }\r\n   }\r\n.wsx-form-setting .wsx-tier-wrapper, .wsx-tier-header {\r\n     grid-template-columns: repeat(3, 1fr) 40px;\r\n}\r\n@media (max-width: 768px) {\r\n    .wsx-tier {\r\n        &-wrapper, &-header {\r\n            grid-template-columns: repeat(1, 1fr);\r\n            gap: 12px;\r\n        }\r\n    }\r\n}\r\n@media (max-width: 1400px) {\r\n    .wsx-tier {\r\n        &-wrapper, &-header {\r\n            grid-template-columns: repeat(1, 1fr);\r\n            gap: 12px;\r\n        }\r\n    }\r\n}\r\n@media (max-width: 576px) {\r\n    .wsx-condition-container .wsx-tier {\r\n        &-wrapper {\r\n            grid-template-columns: 1fr;\r\n        }\r\n    }\r\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/runtime/api.js":
/*!*****************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/api.js ***!
  \*****************************************************/
/***/ ((module) => {



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

/***/ "./reactjs/src/assets/scss/Tier.scss":
/*!*******************************************!*\
  !*** ./reactjs/src/assets/scss/Tier.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Tier_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./Tier.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./reactjs/src/assets/scss/Tier.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Tier_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Tier_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Tier_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_Tier_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js":
/*!****************************************************************************!*\
  !*** ./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js ***!
  \****************************************************************************/
/***/ ((module) => {



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

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = React;

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
/***/ ((module) => {

module.exports = ReactDOM;

/***/ }),

/***/ "@wordpress/i18n":
/*!**************************!*\
  !*** external "wp.i18n" ***!
  \**************************/
/***/ ((module) => {

module.exports = wp.i18n;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(e, r, t) {
  return (r = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPrimitive)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");

function toPrimitive(t, r) {
  if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPropertyKey)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js");


function toPropertyKey(t) {
  var i = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__["default"])(t, "string");
  return "symbol" == (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i) ? i : i + "";
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
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
/*!*************************************************!*\
  !*** ./reactjs/src/pages/user_profile/index.js ***!
  \*************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Profile__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Profile */ "./reactjs/src/pages/user_profile/Profile.js");



document.addEventListener('DOMContentLoaded', function () {
  if (document.body.contains(document.getElementById('_wholesalex_edit_profile'))) {
    react_dom__WEBPACK_IMPORTED_MODULE_1___default().render(/*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().StrictMode), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement(_Profile__WEBPACK_IMPORTED_MODULE_2__["default"], null)), document.getElementById('_wholesalex_edit_profile'));
  }
});
/******/ })()
;
//# sourceMappingURL=whx_profile.js.map