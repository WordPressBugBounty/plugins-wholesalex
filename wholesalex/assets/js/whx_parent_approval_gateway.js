(()=>{"use strict";var e={816:(e,t,n)=>{let r;function a(e){if("string"!=typeof e||-1===e.indexOf("&"))return e;void 0===r&&(r=document.implementation&&document.implementation.createHTMLDocument?document.implementation.createHTMLDocument("").createElement("textarea"):document.createElement("textarea")),r.innerHTML=e;const t=r.textContent;return r.innerHTML="",t}n.d(t,{S:()=>a})},834:e=>{e.exports=wc.wcBlocksRegistry},542:e=>{e.exports=wc.wcSettings},470:e=>{e.exports=wp.i18n}},t={};function n(r){var a=t[r];if(void 0!==a)return a.exports;var o=t[r]={exports:{}};return e[r](o,o.exports,n),o.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var r=n(470),a=n(834),o=n(816);const c=(0,n(542).getSetting)("wholesalex_subaccount_order_approval_data",{}),l=(0,r.__)("Sent to Parent Account For Approval","wholesalex"),s=(0,o.S)(c.title)||l,u=()=>(0,o.S)(c.description||""),i=e=>{const{PaymentMethodLabel:t}=e.components;return React.createElement(React.Fragment,null,React.createElement(t,{text:s}))},p={name:"wholesalex_subaccount_order_approval",label:React.createElement(i,null),content:React.createElement(u,null),edit:React.createElement(u,null),ariaLabel:s,canMakePayment:()=>c.canMakePayment,supports:{features:c.supports}};(0,a.registerPaymentMethod)(p)})();
//# sourceMappingURL=whx_parent_approval_gateway.js.map