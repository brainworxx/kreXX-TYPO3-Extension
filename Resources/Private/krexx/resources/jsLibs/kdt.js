"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var CodeGen = (function () {
    function CodeGen() {
        var _this = this;
        this.resultArray = [];
        this.resultString = '';
        this.sourcedata = '';
        this.domid = '';
        this.wrapperLeft = '';
        this.wrapperRight = '';
        this.generateCode = function (event, element) {
            event.stop = true;
            _this.reset();
            var el = _this.kdt.getParents(element, 'li.kchild')[0];
            while (el) {
                if (!_this.processElement(el)) {
                    break;
                }
                el = _this.kdt.getParents(el, 'li.kchild')[0];
            }
            _this.processResultArray();
            _this.resultString = _this.wrapperLeft + _this.resultString + _this.wrapperRight;
            _this.displayCode(element);
        };
        this.kdt = new Kdt();
    }
    CodeGen.prototype.processResultArray = function () {
        this.resultArray.reverse();
        for (var i = 0; i < this.resultArray.length; i++) {
            if (this.resultArray[i] === '. . .') {
                this.resultString = this.kdt.translations.translate('tsNoCodeGen');
                break;
            }
            if (this.resultArray[i] === ';stop;') {
                this.resultString = '';
                this.resultArray[i] = '';
            }
            if (this.resultArray[i].indexOf(';firstMarker;') !== -1) {
                this.resultString = this.resultArray[i].replace(';firstMarker;', this.resultString);
            }
            else {
                this.resultString = this.resultString + this.resultArray[i];
            }
        }
    };
    CodeGen.prototype.processElement = function (el) {
        var _a;
        this.domid = this.kdt.getDataset(el, 'domid');
        this.sourcedata = this.kdt.getDataset(el, 'source');
        this.wrapperLeft = this.kdt.getDataset(el, 'codewrapperLeft');
        this.wrapperRight = this.kdt.getDataset(el, 'codewrapperRight');
        if (this.sourcedata === '. . .') {
            if (this.domid !== '') {
                var parentEl = (_a = document.querySelector('#' + this.domid)) === null || _a === void 0 ? void 0 : _a.parentNode;
                if (!parentEl) {
                    return false;
                }
                this.resultArray.push(this.kdt.getDataset(parentEl, 'source'));
            }
        }
        if (this.sourcedata !== '') {
            this.resultArray.push(this.sourcedata);
        }
        return true;
    };
    CodeGen.prototype.displayCode = function (element) {
        var codedisplay = element.nextElementSibling;
        codedisplay.innerHTML = '<div class="kcode-inner">' + this.resultString + '</div>';
        if (codedisplay.style.display === 'none') {
            codedisplay.style.display = '';
            this.kdt.selectText(codedisplay);
        }
        else {
            codedisplay.style.display = 'none';
        }
    };
    CodeGen.prototype.reset = function () {
        this.resultArray = [];
        this.resultString = '';
        this.sourcedata = '';
        this.domid = '';
        this.wrapperLeft = '';
        this.wrapperRight = '';
    };
    return CodeGen;
}());
var CopyFrom = (function () {
    function CopyFrom() {
        var _this = this;
        this.retrieveMainElements = function (element) {
            var domid = _this.kdt.getDataset(element, 'domid');
            if (domid === '' || element.parentNode === null) {
                return false;
            }
            var orgNest = document.querySelector('#' + domid);
            var orgEl = orgNest === null || orgNest === void 0 ? void 0 : orgNest.previousElementSibling;
            var parentElement = element.parentNode;
            if (orgNest && orgEl && parentElement) {
                return { orgNest: orgNest, orgEl: orgEl, parentElement: parentElement, domid: domid };
            }
            return false;
        };
        this.prepareCopy = function (element, newEl) {
            var _a, _b;
            _this.kdt.findInDomlistByClass(newEl.children, 'kname').innerHTML =
                _this.kdt.findInDomlistByClass(element.children, 'kname').innerHTML;
            var allChildren = (_a = newEl.nextElementSibling) === null || _a === void 0 ? void 0 : _a.getElementsByTagName("*");
            if (allChildren) {
                for (var i = 0; i < allChildren.length; i++) {
                    allChildren[i].removeAttribute('id');
                }
            }
            (_b = newEl.nextElementSibling) === null || _b === void 0 ? void 0 : _b.removeAttribute('id');
        };
        this.prepareButtons = function (element, newEl) {
            var _a, _b;
            var newInfobox = newEl.querySelector('.khelp');
            var newButton = newEl.querySelector('.kinfobutton');
            var realInfobox = element.querySelector('.khelp');
            var realButton = element.querySelector('.kinfobutton');
            if (newInfobox !== null) {
                (_a = newInfobox.parentNode) === null || _a === void 0 ? void 0 : _a.removeChild(newInfobox);
            }
            if (newButton !== null) {
                (_b = newButton.parentNode) === null || _b === void 0 ? void 0 : _b.removeChild(newButton);
            }
            if (realInfobox === null) {
                return;
            }
            if (realButton !== null) {
                newEl.appendChild(realButton);
            }
            newEl.appendChild(realInfobox);
        };
        this.copyFrom = function (event, element) {
            var mainElements = _this.retrieveMainElements(element);
            if (!mainElements) {
                return;
            }
            var orgNest = mainElements.orgNest, orgEl = mainElements.orgEl, parentElement = mainElements.parentElement, domid = mainElements.domid;
            parentElement.insertBefore(orgNest.cloneNode(true), element.nextSibling);
            var newEl = orgEl.cloneNode(true);
            parentElement.insertBefore(newEl, element.nextSibling);
            _this.kdt.setDataset(newEl.parentNode, 'domid', domid);
            _this.prepareCopy(element, newEl);
            _this.prepareButtons(element, newEl);
            parentElement.removeChild(element);
        };
        this.kdt = new Kdt();
    }
    return CopyFrom;
}());
var Draxx = (function () {
    function Draxx(selector, handle, callbackUp, callbackDrag) {
        var _this = this;
        this.offSetX = 0;
        this.offSetY = 0;
        this.startDraxx = function (event) {
            var _a;
            var elContent = _this.kdt.getParents(event.target, _this.selector)[0];
            var offset = _this.getElementOffset(elContent);
            _this.offSetY = offset.top + elContent.offsetHeight - event.pageY - elContent.offsetHeight;
            _this.offSetX = offset.left + _this.outerWidth(elContent) - event.pageX - _this.outerWidth(elContent);
            _this.elContentStyle = elContent.style;
            var body = document.querySelector('body');
            if (body === null) {
                return;
            }
            var bodyStyle = getComputedStyle(body);
            if (bodyStyle.position === 'relative') {
                var relOffsetY = void 0;
                var relOffsetX = void 0;
                relOffsetY = parseInt(bodyStyle.marginTop, 10);
                relOffsetX = parseInt(bodyStyle.marginLeft, 10);
                if (relOffsetY > 0) {
                }
                else {
                    var prev = (_a = elContent.previousElementSibling) !== null && _a !== void 0 ? _a : elContent.parentElement;
                    do {
                        if (prev === null) {
                            break;
                        }
                        relOffsetY = parseInt(getComputedStyle(prev).marginTop, 10);
                        prev = prev.previousElementSibling;
                    } while (prev && relOffsetY === 0);
                }
                _this.offSetY -= relOffsetY;
                _this.offSetX -= relOffsetX;
            }
            document.addEventListener("mousemove", _this.drag);
            document.addEventListener("mouseup", _this.mouseUp);
            event.preventDefault();
            event.stopPropagation();
        };
        this.mouseUp = function (event) {
            event.preventDefault();
            event.stopPropagation();
            document.removeEventListener("mousemove", _this.drag);
            document.removeEventListener("mouseup", _this.mouseUp);
            _this.callbackUp();
        };
        this.drag = function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!_this.elContentStyle) {
                return;
            }
            _this.elContentStyle.left = (event.pageX + _this.offSetX) + "px";
            _this.elContentStyle.top = (event.pageY + _this.offSetY) + "px";
            _this.callbackDrag();
        };
        this.selector = selector;
        this.callbackUp = callbackUp;
        this.callbackDrag = callbackDrag;
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(handle);
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('mousedown', this.startDraxx);
        }
    }
    Draxx.prototype.moveToViewport = function (selector) {
        setTimeout(function () {
            var viewportTop = document.documentElement.scrollTop;
            if (viewportTop === 0) {
                viewportTop = document.body.scrollTop;
            }
            var elements = document.querySelectorAll(selector);
            var oldOffset = 0;
            for (var i = 0; i < elements.length; i++) {
                oldOffset = parseInt(elements[i].style.top.slice(0, -2), 10);
                elements[i].style.top = (oldOffset + viewportTop) + 'px';
            }
        }, 500);
    };
    Draxx.prototype.getElementOffset = function (element) {
        var de = document.documentElement;
        var box = element.getBoundingClientRect();
        var top = box.top + window.pageYOffset - de.clientTop;
        var left = box.left + window.pageXOffset - de.clientLeft;
        return { top: top, left: left };
    };
    Draxx.prototype.outerWidth = function (element) {
        var width = element.offsetWidth;
        var style = getComputedStyle(element);
        width += parseInt(style.marginLeft, 10) + parseInt(style.marginRight, 10);
        return width;
    };
    return Draxx;
}());
var Eventhandler = (function () {
    function Eventhandler(selector) {
        var _this = this;
        this.storage = {};
        this.handle = function (event) {
            event.stopPropagation();
            event.stop = false;
            var element = event.target;
            do {
                _this.callEvents(event, element);
                element = element.parentNode;
                if (element === event.currentTarget) {
                    element = null;
                }
            } while (element !== null && typeof element.matches === 'function');
        };
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(selector);
        this.registerEventsOnElements(elements, 'click', this.handle);
    }
    Eventhandler.prototype.registerEventsOnElements = function (elements, eventName, callback) {
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener(eventName, callback);
        }
    };
    Eventhandler.prototype.addEvent = function (selector, eventName, callBack) {
        if (eventName === 'click') {
            this.addToStorage(selector, callBack);
        }
        else {
            var elements = document.querySelectorAll(selector);
            this.registerEventsOnElements(elements, eventName, callBack);
        }
    };
    Eventhandler.prototype.preventBubble = function (event) {
        event.stop = true;
    };
    Eventhandler.prototype.addToStorage = function (selector, callback) {
        if (!(selector in this.storage)) {
            this.storage[selector] = [];
        }
        this.storage[selector].push(callback);
    };
    Eventhandler.prototype.callEvents = function (event, element) {
        for (var selector in this.storage) {
            if (!element.matches(selector)) {
                continue;
            }
            var callbackArray = this.storage[selector];
            for (var i = 0; i < callbackArray.length; i++) {
                callbackArray[i](event, element);
                if (event.stop) {
                    return false;
                }
            }
        }
        return true;
    };
    Eventhandler.prototype.triggerEvent = function (el, eventName) {
        var event = new Event(eventName, { bubbles: true, cancelable: false });
        el.dispatchEvent(event);
    };
    return Eventhandler;
}());
var Kdt = (function () {
    function Kdt() {
        var _this = this;
        this.jumpTo = function () {
        };
        this.setJumpTo = function (jumpTo) {
            _this.jumpTo = jumpTo;
        };
        this.collapse = function (event, element) {
            event.stop = true;
            var wrapper = _this.getParents(element, '.kwrapper')[0];
            _this.removeClass(wrapper.querySelectorAll('.kfilterroot'), 'kfilterroot');
            _this.removeClass(wrapper.querySelectorAll('.krootline'), 'krootline');
            _this.removeClass(wrapper.querySelectorAll('.ktopline'), 'ktopline');
            if (!element.classList.contains('kcollapsed')) {
                _this.addClass(_this.getParents(element, 'div.kbg-wrapper > ul'), 'kfilterroot');
                _this.addClass(_this.getParents(element, 'ul.knode, li.kchild'), 'krootline');
                _this.addClass([_this.getParents(element, '.krootline')[0]], 'ktopline');
                _this.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');
                _this.addClass([element], 'kcollapsed');
            }
            else {
                _this.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');
            }
            var jumpTo = _this.jumpTo;
            setTimeout(function () {
                jumpTo(element, true);
            }, 100);
        };
        this.translations = new Translations('.krdata-structure.krtrans', this);
        this.addClass('.kwrapper .knoscript', 'khidden');
    }
    Kdt.prototype.beenHere = function () {
        if (typeof window['krexxDone'] === 'undefined') {
            window['krexxDone'] = true;
            return false;
        }
        return true;
    };
    Kdt.prototype.getParents = function (el, selector) {
        var result = [];
        var parent = el.parentNode;
        var body = document.querySelector('body');
        while (parent !== null) {
            if (parent.matches(selector)) {
                result.push(parent);
            }
            parent = parent.parentNode;
            if (parent === body) {
                parent = null;
            }
        }
        return result;
    };
    Kdt.prototype.findInDomlistByClass = function (elements, className) {
        for (var i = 0; i < elements.length; i++) {
            if (elements[i].classList.contains(className.trim())) {
                return elements[i];
            }
        }
        return null;
    };
    Kdt.prototype.addClass = function (selector, className) {
        var elements;
        if (typeof selector === 'string') {
            elements = document.querySelectorAll(selector);
        }
        else {
            elements = selector;
        }
        for (var i = 0; i < elements.length; i++) {
            elements[i].classList.add(className);
        }
    };
    Kdt.prototype.removeClass = function (selector, className) {
        var elements;
        if (typeof selector === 'string') {
            elements = document.querySelectorAll(selector);
        }
        else {
            elements = selector;
        }
        for (var i = 0; i < elements.length; i++) {
            elements[i].classList.remove(className);
        }
    };
    Kdt.prototype.getDataset = function (el, what, mustEscape) {
        if (mustEscape === void 0) { mustEscape = false; }
        var result;
        if (el === null
            || typeof el === 'undefined'
            || typeof el.getAttribute !== 'function') {
            return '';
        }
        result = el.getAttribute('data-' + what);
        if (result === null) {
            return '';
        }
        if (mustEscape) {
            return result.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;")
                .replace('&lt;small&gt;', '<small>')
                .replace('&lt;/small&gt;', '</small>');
        }
        return result;
    };
    Kdt.prototype.setDataset = function (el, what, value) {
        if (typeof el !== 'undefined') {
            el.setAttribute('data-' + what, value);
        }
    };
    Kdt.prototype.selectText = function (el) {
        var range = document.createRange();
        var selection = window.getSelection();
        if (selection === null) {
            return;
        }
        range.selectNodeContents(el);
        selection.removeAllRanges();
        selection.addRange(range);
    };
    Kdt.prototype.parseJson = function (string) {
        try {
            return JSON.parse(string);
        }
        catch (error) {
            return false;
        }
    };
    Kdt.prototype.moveToBottom = function (selector) {
        var _a, _b, _c;
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            if (((_b = (_a = elements[i]) === null || _a === void 0 ? void 0 : _a.parentNode) === null || _b === void 0 ? void 0 : _b.nodeName.toUpperCase()) !== 'BODY') {
                (_c = document.querySelector('body')) === null || _c === void 0 ? void 0 : _c.appendChild(elements[i]);
            }
        }
    };
    return Kdt;
}());
var Settings = (function () {
    function Settings() {
        var _this = this;
        this.set = function (event) {
            event.preventDefault();
            event.stopPropagation();
            var settings = _this.read('KrexxDebugSettings');
            var newValue = event.target.value.replace('"', '').replace("'", '');
            var valueName = event.target.name.replace('"', '').replace("'", '');
            settings[valueName] = newValue;
            var date = new Date();
            date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
            var expires = 'expires=' + date.toUTCString();
            document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
            alert(valueName + ' --> ' + newValue + '\n\n' + _this.kdt.translations.translate('tsPleaseReload'));
        };
        this.reset = function () {
            var date = new Date();
            date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
            var expires = 'expires=' + date.toUTCString();
            document.cookie = 'KrexxDebugSettings={}; ' + expires + '; path=/';
            alert(_this.kdt.translations.translate('tsConfigReset') + '\n\n' + _this.kdt.translations.translate('tsPleaseReload'));
        };
        this.kdt = new Kdt();
    }
    Settings.prototype.read = function (cookieName) {
        var match = document.cookie.match(new RegExp('(^| )' + cookieName + '=([^;]+)'));
        var result = {};
        if (match === null) {
            return result;
        }
        try {
            result = JSON.parse(match[2]);
        }
        catch (error) {
        }
        return result;
    };
    return Settings;
}());
var Translations = (function () {
    function Translations(selector, kdt) {
        this.translations = {};
        var dataElements = document.querySelectorAll(selector);
        var data;
        var json;
        for (var i = 0; i < dataElements.length; i++) {
            data = kdt.getDataset(dataElements[i], 'translations');
            json = kdt.parseJson(data);
            if (json !== false) {
                this.translations = __assign(__assign({}, this.translations), json);
            }
        }
    }
    Translations.prototype.translate = function (key) {
        if (typeof this.translations[key] === 'undefined') {
            return key;
        }
        return this.translations[key];
    };
    return Translations;
}());
var Search = (function () {
    function Search(eventHandler, jumpTo) {
        var _this = this;
        this.prepareConfig = function (element, instanceElement) {
            var config = new SearchConfig();
            config.node = element;
            config.searchtext = element.querySelector('.ksearchfield').value;
            config.caseSensitive = element.querySelector('.ksearchcase').checked;
            config.searchKeys = element.querySelector('.ksearchkeys').checked;
            config.searchShort = element.querySelector('.ksearchshort').checked;
            config.searchLong = element.querySelector('.ksearchlong').checked;
            config.searchWhole = element.querySelector('.ksearchwhole').checked;
            config.instance = _this.kdt.getDataset(instanceElement, 'instance');
            if (!config.caseSensitive) {
                config.searchtext = config.searchtext.toLowerCase();
            }
            return config;
        };
        this.prepareNodes = function (element) {
            var parentNode = element.parentNode;
            if (parentNode === null) {
                return null;
            }
            var grandParentNode = parentNode.parentNode;
            if (grandParentNode === null) {
                return null;
            }
            var parentSibling = parentNode.nextElementSibling;
            if (parentSibling !== null) {
                _this.kdt.addClass([parentSibling], 'khidden');
            }
            return grandParentNode;
        };
        this.performSearch = function (event, element) {
            var root = _this.prepareNodes(element);
            if (root === null) {
                return;
            }
            var config = _this.prepareConfig(root, element);
            if (!_this.navigation.isSearchable(config)) {
                return;
            }
            _this.retrievePayload(config);
            _this.expandCollapsedNodes(config);
            _this.refreshResultlist(config);
            _this.navigation.movePointer(config, element);
            var result = _this.resultBucket.getResultBucket(config);
            var current = result.data[result.pointer];
            if (current) {
                _this.jumpTo(current);
            }
            _this.navigation.updateSearchState(config);
        };
        this.expandCollapsedNodes = function (config) {
            var _a;
            var collapsed = (_a = config.payload) === null || _a === void 0 ? void 0 : _a.querySelectorAll('.kcollapsed');
            if (collapsed !== undefined) {
                for (var i = 0; i < collapsed.length; i++) {
                    _this.eventHandler.triggerEvent(collapsed[i], 'click');
                }
            }
        };
        this.retrievePayload = function (config) {
            var tab = document.querySelector('#' + config.instance + ' .ktab.kactive');
            var additionalClasses = '';
            if (tab !== null) {
                additionalClasses = ' .' + _this.kdt.getDataset(tab, 'what');
            }
            config.payload = document.querySelector('#' + config.instance + ' .kbg-wrapper' + additionalClasses);
        };
        this.refreshResultlist = function (config) {
            var _a;
            if (_this.resultBucket.hasSearchResults(config)) {
                return;
            }
            _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            var list = (_a = config.payload) === null || _a === void 0 ? void 0 : _a.querySelectorAll(_this.buildSelector(config));
            if (list === undefined) {
                return;
            }
            var result = _this.resultBucket.getResultBucket(config);
            for (var i = 0; i < list.length; ++i) {
                _this.updateResult(list[i], config, result);
            }
            result.pointer = -1;
        };
        this.kdt = new Kdt();
        this.eventHandler = eventHandler;
        this.jumpTo = jumpTo;
        this.resultBucket = new SearchResultBucket();
        this.navigation = new SearchNavigator(this.resultBucket);
        new SearchEventBinder(eventHandler, this.resultBucket);
    }
    Search.prototype.updateResult = function (node, config, result) {
        var _a;
        var textContent = (_a = node.textContent) !== null && _a !== void 0 ? _a : '';
        if (!config.caseSensitive) {
            textContent = textContent.toLowerCase();
        }
        if ((config.searchWhole && textContent === config.searchtext)
            || (!config.searchWhole && textContent.indexOf(config.searchtext) > -1)) {
            node.classList.toggle('ksearch-found-highlight');
            result.data.push(node);
        }
    };
    Search.prototype.buildSelector = function (config) {
        var selector = [];
        if (config.searchKeys) {
            selector.push('li.kchild span.kname');
        }
        if (config.searchShort) {
            selector.push('li.kchild span.kshort');
        }
        if (config.searchLong) {
            selector.push('li div.kpreview');
        }
        return selector.join(', ');
    };
    return Search;
}());
var SearchConfig = (function () {
    function SearchConfig() {
        this.searchKeys = false;
        this.searchShort = false;
        this.searchLong = false;
        this.caseSensitive = false;
        this.searchWhole = false;
        this.instance = '';
        this.searchtext = '';
        this.payload = null;
        this.node = null;
    }
    return SearchConfig;
}());
var Hans = (function () {
    function Hans() {
        var _this = this;
        this.jumpToInterval = 0;
        this.initDraxx = function () {
            _this.draxx = new Draxx('.kwrapper', '.kheadnote', function () {
                var searchWrapper = document.querySelectorAll('.search-wrapper');
                var viewportOffset;
                for (var i = 0; i < searchWrapper.length; i++) {
                    viewportOffset = searchWrapper[i].getBoundingClientRect();
                    searchWrapper[i].style.position = 'fixed';
                    searchWrapper[i].style.top = viewportOffset.top + 'px';
                }
            }, function () {
                var searchWrapper = document.querySelectorAll('.search-wrapper');
                for (var i = 0; i < searchWrapper.length; i++) {
                    searchWrapper[i].style.position = 'absolute';
                    searchWrapper[i].style.top = '';
                }
            });
        };
        this.toggle = function (event, element) {
            element.classList.toggle('kopened');
            var sibling = element.nextElementSibling;
            if (sibling === null) {
                return;
            }
            do {
                sibling.classList.toggle('khidden');
                sibling = sibling.nextElementSibling;
            } while (sibling);
        };
        this.jumpTo = function (el, noHighlight) {
            _this.setHighlighting(el, noHighlight);
            var destination;
            var container = document.querySelector('html');
            if (container === null) {
                return;
            }
            ++container.scrollTop;
            if (container.scrollTop === 0 || container.scrollHeight <= container.clientHeight) {
                container = document.querySelector('body');
            }
            if (container === null) {
                return;
            }
            --container.scrollTop;
            destination = el.getBoundingClientRect().top + container.scrollTop - 200;
            var diff = Math.abs(container.scrollTop - destination);
            if (diff < 150) {
                return;
            }
            _this.scrollContainer(container, destination, diff);
        };
        this.scrollContainer = function (container, destination, diff) {
            var step;
            if (container.scrollTop < destination) {
                step = Math.round(diff / 12);
            }
            else {
                step = Math.round(diff / 12) * -1;
            }
            var lastValue = container.scrollTop;
            clearInterval(_this.jumpToInterval);
            var interval = _this.jumpToInterval = setInterval(function () {
                container.scrollTop += step;
                if (Math.abs(container.scrollTop - destination) <= Math.abs(step) || container.scrollTop === lastValue) {
                    container.scrollTop = destination;
                    clearInterval(interval);
                }
                lastValue = container.scrollTop;
            }, 10);
        };
        this.close = function (event, element) {
            var instance = _this.kdt.getDataset(element, 'instance');
            var elInstance = document.querySelector('#' + instance);
            var opacity = 1;
            var interval = setInterval(function () {
                if (opacity < 0) {
                    clearInterval(interval);
                    if (elInstance === null || elInstance.parentNode === null) {
                        return;
                    }
                    elInstance.parentNode.removeChild(elInstance);
                    return;
                }
                opacity -= 0.1;
                if (elInstance === null) {
                    return;
                }
                elInstance.style.opacity = opacity.toString();
            }, 20);
        };
        this.displayInfoBox = function (event, element) {
            event.stop = true;
            var box = element.nextElementSibling;
            if (box.style.display === 'none') {
                box.style.display = '';
            }
            else {
                box.style.display = 'none';
            }
        };
        this.displaySearch = function (event, element) {
            var instance = _this.kdt.getDataset(element, 'instance');
            var search = document.querySelector('#search-' + instance);
            if (search === null) {
                return;
            }
            var viewportOffset;
            if (search.classList.contains('khidden')) {
                search.classList.remove('khidden');
                search.querySelector('.ksearchfield').focus();
                search.style.position = 'absolute';
                search.style.top = '';
                viewportOffset = search.getBoundingClientRect();
                search.style.position = 'fixed';
                search.style.top = viewportOffset.top + 'px';
            }
            else {
                search.classList.add('khidden');
                _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
                search.style.position = 'absolute';
                search.style.top = '';
            }
        };
        this.selectors = new Selectors();
        this.selectors.eventHandler = '.kwrapper.kouterwrapper, .kfatalwrapper-outer';
        this.selectors.moveToBottom = '.kouterwrapper';
        this.selectors.close = '.kwrapper .kheadnote-wrapper .kclose, .kwrapper .kfatal-headnote .kclose';
        this.selectors.toggle = '.kwrapper .kexpand';
        this.selectors.setSetting = '.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)';
        this.selectors.resetSetting = '.kwrapper .kresetbutton';
        this.selectors.copyFrom = '.kwrapper .kcopyFrom';
        this.selectors.displaySearch = '.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose';
        this.selectors.performSearch = '.kwrapper .ksearchnow';
        this.selectors.collapse = '.kwrapper .kolps';
        this.selectors.generateCode = '.kwrapper .kgencode';
        this.selectors.preventBubble = '.kodsp';
        this.selectors.displayInfoBox = '.kwrapper .kchild .kinfobutton';
        this.selectors.moveToViewport = '.kouterwrapper';
        this.kdt = new Kdt();
        this.CodeGen = new CodeGen();
        this.settings = new Settings();
        this.copyFrom = new CopyFrom();
    }
    Hans.prototype.run = function () {
        if (this.kdt.beenHere()) {
            return;
        }
        this.initDraxx();
        this.kdt.setJumpTo(this.jumpTo);
        this.eventHandler = new Eventhandler(this.selectors.eventHandler);
        this.search = new Search(this.eventHandler, this.jumpTo);
        this.kdt.moveToBottom(this.selectors.moveToBottom);
        this.eventHandler.addEvent(this.selectors.close, 'click', this.close);
        this.eventHandler.addEvent(this.selectors.toggle, 'click', this.toggle);
        this.eventHandler.addEvent(this.selectors.setSetting, 'change', this.settings.set);
        this.eventHandler.addEvent(this.selectors.resetSetting, 'click', this.settings.reset);
        this.eventHandler.addEvent(this.selectors.copyFrom, 'click', this.copyFrom.copyFrom);
        this.eventHandler.addEvent(this.selectors.displaySearch, 'click', this.displaySearch);
        this.eventHandler.addEvent(this.selectors.performSearch, 'click', this.search.performSearch);
        this.eventHandler.addEvent(this.selectors.collapse, 'click', this.kdt.collapse);
        this.eventHandler.addEvent(this.selectors.generateCode, 'click', this.CodeGen.generateCode);
        this.eventHandler.addEvent(this.selectors.preventBubble, 'click', this.eventHandler.preventBubble);
        this.eventHandler.addEvent(this.selectors.displayInfoBox, 'click', this.displayInfoBox);
        if (window.location.protocol === 'file:') {
            this.disableForms();
        }
        this.draxx.moveToViewport(this.selectors.moveToViewport);
    };
    Hans.prototype.setHighlighting = function (el, noHighlight) {
        var nests = this.kdt.getParents(el, '.knest');
        this.kdt.removeClass(nests, 'khidden');
        for (var i = 0; i < nests.length; i++) {
            var prevSibling = nests[i].previousElementSibling;
            if (prevSibling === null) {
                continue;
            }
            this.kdt.addClass([prevSibling], 'kopened');
        }
        if (!noHighlight) {
            this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
            this.kdt.addClass([el], 'highlight-jumpto');
        }
    };
    Hans.prototype.disableForms = function () {
        var elements = document.querySelectorAll('.kwrapper .kconfiguration .keditable input, .kwrapper .kconfiguration .keditable select');
        for (var i = 0; i < elements.length; i++) {
            elements[i].disabled = true;
        }
    };
    return Hans;
}());
var Selectors = (function () {
    function Selectors() {
        this.eventHandler = '';
        this.moveToBottom = '';
        this.close = '';
        this.toggle = '';
        this.setSetting = '';
        this.resetSetting = '';
        this.copyFrom = '';
        this.displaySearch = '';
        this.performSearch = '';
        this.collapse = '';
        this.generateCode = '';
        this.preventBubble = '';
        this.displayInfoBox = '';
        this.moveToViewport = '';
    }
    return Selectors;
}());
var SmokyGrey = (function (_super) {
    __extends(SmokyGrey, _super);
    function SmokyGrey() {
        var _this = _super.call(this) || this;
        _this.initDraxx = function () {
            _this.draxx = new Draxx('.kwrapper', '.khandle', function () {
            }, function () {
            });
        };
        _this.switchTab = function (event, element) {
            var instance = _this.kdt.getDataset(element.parentNode, 'instance');
            var what = _this.kdt.getDataset(element, 'what');
            _this.kdt.removeClass('#' + instance + ' .kactive:not(.ksearchbutton)', 'kactive');
            if (element.classList) {
                element.classList.add('kactive');
            }
            else {
                element.className += ' kactive';
            }
            _this.kdt.addClass('#' + instance + ' .kpayload', 'khidden');
            _this.kdt.removeClass('#' + instance + ' .' + what, 'khidden');
        };
        _this.setAdditionalData = function (event, element) {
            var kdt = _this.kdt;
            var setPayloadMaxHeight = _this.setPayloadMaxHeight.bind(_this);
            setTimeout(function () {
                var _a;
                var wrapper = kdt.getParents(element, '.kwrapper')[0];
                if (typeof wrapper === 'undefined') {
                    return;
                }
                var body = wrapper.querySelector('.kdatabody');
                if (body === null) {
                    return;
                }
                var html = '';
                var counter = 0;
                var regex = /\\u([\d\w]{4})/gi;
                kdt.removeClass(wrapper.querySelectorAll('.kcurrent-additional'), 'kcurrent-additional');
                kdt.addClass([element], 'kcurrent-additional');
                var json = kdt.parseJson(kdt.getDataset(element, 'addjson', false));
                if (typeof json === 'object') {
                    for (var prop in json) {
                        if (json[prop].length > 0) {
                            json[prop] = json[prop].replace(regex, function (match, grp) {
                                return String.fromCharCode(parseInt(grp, 16));
                            });
                            html += '<tr><td class="kinfo">' + prop + '</td><td class="kdesc">' + json[prop] + '</td></tr>';
                            counter++;
                        }
                    }
                }
                if (counter === 0) {
                    html = '<tr><td class="kinfo">' + kdt.translations.translate('tsNoDataAvailable') + '</td><td class="kdesc"></td></tr>';
                }
                html = '<table><caption class="kheadline">' + kdt.translations.translate('tsAdditionalData') +
                    '</caption><tbody class="kdatabody">' + html + '</tbody></table>';
                ((_a = body.parentNode) === null || _a === void 0 ? void 0 : _a.parentNode).innerHTML = html;
                setPayloadMaxHeight();
            }, 100);
        };
        _this.displaySearch = function (event, element) {
            if (element.parentNode === null) {
                return;
            }
            var instance = _this.kdt.getDataset(element.parentNode, 'instance');
            var search = document.querySelector('#search-' + instance);
            var searchtab = document.querySelector('#' + instance + ' .ksearchbutton');
            if (search === null || searchtab === null) {
                return;
            }
            searchtab.classList.toggle('kactive');
            if (search.classList.contains('khidden')) {
                search.classList.toggle('khidden');
                search.querySelector('.ksearchfield').focus();
            }
            else {
                search.classList.toggle('khidden');
                _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            }
        };
        _this.jumpTo = function (el, noHighlight) {
            _this.setHighlighting(el, noHighlight);
            var container = _this.kdt.getParents(el, '.kpayload')[0];
            var destination = el.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop - 50;
            var diff = Math.abs(container.scrollTop - destination);
            _this.scrollContainer(container, destination, diff);
        };
        _this.selectors.close = '.kwrapper .ktool-tabs .kclose, .kwrapper .kheadnote-wrapper .kclose';
        return _this;
    }
    SmokyGrey.prototype.run = function () {
        _super.prototype.run.call(this);
        if (typeof this.eventHandler === 'undefined') {
            return;
        }
        this.setPayloadMaxHeight();
        this.eventHandler.addEvent('.ktool-tabs .ktab:not(.ksearchbutton)', 'click', this.switchTab);
        this.eventHandler.addEvent('.kwrapper .kel', 'click', this.setAdditionalData);
    };
    SmokyGrey.prototype.setPayloadMaxHeight = function () {
        var elements = document.querySelectorAll('.krela-wrapper .kpayload');
        this.handlePayloadMinHeight(Math.round(Math.min(document.documentElement.clientHeight, window.innerHeight || 0) * 0.70), elements);
        elements = document.querySelectorAll('.kfatalwrapper-outer .kpayload');
        if (elements.length > 0) {
            var header = document.querySelector('.kfatalwrapper-outer ul.knode.kfirst').offsetHeight;
            var footer = document.querySelector('.kfatalwrapper-outer .kinfo-wrapper').offsetHeight;
            var handler = document.querySelector('.kfatalwrapper-outer').offsetHeight;
            this.handlePayloadMinHeight(handler - header - footer - 17, elements);
        }
    };
    SmokyGrey.prototype.handlePayloadMinHeight = function (height, elements) {
        var i;
        if (height > 350) {
            for (i = 0; i < elements.length; i++) {
                elements[i].style.maxHeight = height + 'px';
            }
        }
    };
    return SmokyGrey;
}(Hans));
var SearchResultBucket = (function () {
    function SearchResultBucket() {
        this.results = {};
    }
    SearchResultBucket.prototype.getResultBucket = function (config) {
        var _a, _b;
        var _c, _d, _e, _f;
        (_a = (_c = this.results)[_d = config.instance]) !== null && _a !== void 0 ? _a : (_c[_d] = {});
        (_b = (_e = this.results[config.instance])[_f = config.searchtext]) !== null && _b !== void 0 ? _b : (_e[_f] = {
            data: [],
            pointer: -1,
        });
        return this.results[config.instance][config.searchtext];
    };
    SearchResultBucket.prototype.clearSearchResults = function (config) {
        this.results[config.instance] = {};
    };
    SearchResultBucket.prototype.hasSearchResults = function (config) {
        return (this.results[config.instance] !== undefined &&
            this.results[config.instance][config.searchtext] !== undefined &&
            this.results[config.instance][config.searchtext].data.length > 0);
    };
    return SearchResultBucket;
}());
var SearchEventBinder = (function () {
    function SearchEventBinder(eventHandler, resultBucket) {
        var _this = this;
        this.clearSearch = function (event) {
            var config = new SearchConfig();
            config.instance = _this.kdt.getDataset(event.target, 'instance');
            _this.resultBucket.clearSearchResults(config);
        };
        this.displaySearchOptions = function (event, element) {
            if (element.parentNode === null) {
                return;
            }
            var nextElementSibling = element.parentNode.nextElementSibling;
            if (nextElementSibling === null) {
                return;
            }
            nextElementSibling.classList.toggle('khidden');
        };
        this.searchfieldReturn = function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.key !== 'Enter') {
                return;
            }
            if (event.target === null) {
                return;
            }
            var parentNode = event.target.parentNode;
            if (parentNode === null) {
                return;
            }
            _this.eventHandler.triggerEvent(parentNode.querySelectorAll('.ksearchnow')[1], 'click');
        };
        this.resultBucket = resultBucket;
        this.eventHandler = eventHandler;
        this.kdt = new Kdt();
        this.eventHandler.addEvent('.kwrapper .ksearchcase', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchkeys', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchshort', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchlong', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchwhole', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ktab', 'click', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .koptions', 'click', this.displaySearchOptions);
        this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.searchfieldReturn);
    }
    return SearchEventBinder;
}());
var SearchNavigator = (function () {
    function SearchNavigator(resultBucket) {
        var _this = this;
        this.movePointer = function (config, element) {
            var result = _this.resultBucket.getResultBucket(config);
            var direction = _this.kdt.getDataset(element, 'direction');
            if (direction === 'forward') {
                result.pointer++;
            }
            else {
                result.pointer--;
            }
            if (typeof result.data[result.pointer] === "undefined") {
                if (direction === 'forward') {
                    result.pointer = 0;
                }
                else {
                    result.pointer = result.data.length - 1;
                }
            }
        };
        this.updateSearchState = function (config) {
            var result = _this.resultBucket.getResultBucket(config);
            var searchStateElement = _this.getSearchStateElement(config);
            if (!searchStateElement) {
                return;
            }
            searchStateElement.textContent = (result.pointer + 1) + ' / ' + (result.data.length);
        };
        this.isSearchable = function (config) {
            var searchStateElement = _this.getSearchStateElement(config);
            if (!searchStateElement) {
                return false;
            }
            if (config.searchtext.length === 0) {
                searchStateElement.textContent = _this.kdt.translations.translate('tsEnterText');
                return false;
            }
            if (config.searchtext.length < 3 && !config.searchWhole) {
                searchStateElement.textContent = _this.kdt.translations.translate('tsTooSmall');
                return false;
            }
            return true;
        };
        this.resultBucket = resultBucket;
        this.kdt = new Kdt();
    }
    SearchNavigator.prototype.getSearchStateElement = function (config) {
        var _a, _b;
        return (_b = (_a = config.node) === null || _a === void 0 ? void 0 : _a.querySelector('.ksearch-state')) !== null && _b !== void 0 ? _b : null;
    };
    return SearchNavigator;
}());
