!function(e){function webpackJsonpCallback(t){for(var r,o,i=t[0],c=t[1],l=0,_=[];l<i.length;l++)o=i[l],Object.prototype.hasOwnProperty.call(n,o)&&n[o]&&_.push(n[o][0]),n[o]=0;for(r in c)Object.prototype.hasOwnProperty.call(c,r)&&(e[r]=c[r]);for(a&&a(t);_.length;)_.shift()()}var t={},n={0:0};function __webpack_require__(n){if(t[n])return t[n].exports;var r=t[n]={i:n,l:!1,exports:{}};return e[n].call(r.exports,r,r.exports,__webpack_require__),r.l=!0,r.exports}__webpack_require__.e=function requireEnsure(){return Promise.resolve()},__webpack_require__.m=e,__webpack_require__.c=t,__webpack_require__.d=function(e,t,n){__webpack_require__.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},__webpack_require__.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},__webpack_require__.t=function(e,t){if(1&t&&(e=__webpack_require__(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(__webpack_require__.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)__webpack_require__.d(n,r,function(t){return e[t]}.bind(null,r));return n},__webpack_require__.n=function(e){var t=e&&e.__esModule?function getDefault(){return e.default}:function getModuleExports(){return e};return __webpack_require__.d(t,"a",t),t},__webpack_require__.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},__webpack_require__.p="",__webpack_require__.oe=function(e){throw console.error(e),e};var r=window.webpackJsonp=window.webpackJsonp||[],o=r.push.bind(r);r.push=webpackJsonpCallback,r=r.slice();for(var i=0;i<r.length;i++)webpackJsonpCallback(r[i]);var a=o;__webpack_require__(__webpack_require__.s=40)}({128:function(e,t,n){"use strict";var r=n(2);Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var o=r(n(3)),i=r(n(129));t.default=function App(){var e=this;(0,o.default)(this,App),this.modules={},window.elementorNewTemplate&&window.elementorNewTemplate.layout.modal.on("show",(function(){e.modules.category||(e.modules.category=new i.default({categories:EWBConfig.categories}).render())}))}},129:function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=Marionette.ItemView.extend({template:_.template('<label class="elementor-form-field__label">Select a category for your custom widget:</label>\n\t<div class="elementor-form-field__select__wrapper">\t\n\t\t<select class="elementor-form-field__select" name="elementor-new-template__form__ewb-widget-category">\n\t\t\t<% _.each(categories, function(name, category) { %>\n\t\t\t\t<option value="<%= category %>"><%= name.title %></option>\n\t\t\t<% }); %>\n\t\t</select>\n\t</div>\n\t'),el:"#elementor-new-template__form__ewb-widget-category__wrapper",initialize:function initialize(e){var t=this;this.getSelect().addEventListener("change",(function(){t.setFieldVisibility()})),e.categories&&(this.model=new Backbone.Model({categories:e.categories}))},onRender:function onRender(){this.setFieldVisibility()},setFieldVisibility:function setFieldVisibility(){this.$el.css({display:"ewb-widget"==this.getSelect().value?"block":"none"})},getSelect:function getSelect(){return document.querySelector("#elementor-new-template__form__template-type")}});t.default=r},2:function(e,t){e.exports=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}},3:function(e,t){e.exports=function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}},40:function(e,t,n){"use strict";document.addEventListener("DOMContentLoaded",(function(){var e=setInterval((function(){window.jQuery&&window.Marionette&&(clearInterval(e),Promise.resolve().then(n.t.bind(null,128,7)).then((function(e){window.ewbAdmin=new e.default})))}),100)})),jQuery((function(){jQuery('.button[data-action="hide-welcome-message"]').on("click",(function(){jQuery(".ewb-welcome-message").slideUp(250),jQuery.ajax({type:"post",url:EWBConfig.ajaxurl,dataType:"json",data:{action:"hide_welcome_message",nonce:EWBConfig.hide_welcome_notice_nonce},success:function success(){}})}))}))}});