var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var Draxx = (function () {
    function Draxx(selector, handle, callbackUp, callbackDrag) {
        var _this = this;
        this.startDraxx = function (event) {
            var elContent = _this.kdt.getParents(event.target, _this.selector)[0];
            var offset = _this.getElementOffset(elContent);
            _this.offSetY = offset.top + elContent.offsetHeight - event.pageY - elContent.offsetHeight;
            _this.offSetX = offset.left + _this.outerWidth(elContent) - event.pageX - _this.outerWidth(elContent);
            _this.elContentStyle = elContent.style;
            var bodyStyle = getComputedStyle(document.querySelector('body'));
            if (bodyStyle.position === 'relative') {
                var relOffsetY = void 0;
                var relOffsetX = void 0;
                relOffsetY = parseInt(bodyStyle.marginTop, 10);
                relOffsetX = parseInt(bodyStyle.marginLeft, 10);
                if (relOffsetY > 0) {
                }
                else {
                    var prev = elContent.previousElementSibling;
                    do {
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
        this.storage = [];
        this.handle = function (event) {
            event.stopPropagation();
            event.stop = false;
            var element = event.target;
            var selector;
            var i;
            var callbackArray = [];
            do {
                for (selector in _this.storage) {
                    if (element.matches(selector)) {
                        callbackArray = _this.storage[selector];
                        for (i = 0; i < callbackArray.length; i++) {
                            callbackArray[i](event, element);
                            if (event.stop) {
                                return;
                            }
                        }
                    }
                }
                element = element.parentNode;
                if (element === event.currentTarget) {
                    element = null;
                }
            } while (element !== null && typeof element.matches === 'function');
        };
        this.kdt = new Kdt();
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('click', this.handle);
        }
    }
    Eventhandler.prototype.addEvent = function (selector, eventName, callBack) {
        if (eventName === 'click') {
            this.addToStorage(selector, callBack);
        }
        else {
            var elements = document.querySelectorAll(selector);
            for (var i = 0; i < elements.length; i++) {
                elements[i].addEventListener(eventName, callBack);
            }
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
    Eventhandler.prototype.triggerEvent = function (el, eventName) {
        var event = document.createEvent('HTMLEvents');
        event.initEvent(eventName, true, false);
        el.dispatchEvent(event);
    };
    return Eventhandler;
}());
var Kdt = (function () {
    function Kdt() {
        var _this = this;
        this.setJumpTo = function (jumpTo) {
            _this.jumpTo = jumpTo;
        };
        this.setSetting = function (event) {
            event.preventDefault();
            event.stopPropagation();
            var settings = _this.readSettings('KrexxDebugSettings');
            var newValue = event.target.value.replace('"', '').replace("'", '');
            var valueName = event.target.name.replace('"', '').replace("'", '');
            settings[valueName] = newValue;
            var date = new Date();
            date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
            var expires = 'expires=' + date.toUTCString();
            document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
            alert(valueName + ' --> ' + newValue + '\n\nPlease reload the page to use the new local settings.');
        };
        this.collapse = function (event, element) {
            event.stop = true;
            var wrapper = _this.getParents(element, '.kwrapper')[0];
            _this.removeClass(wrapper.querySelectorAll('.kfilterroot'), 'kfilterroot');
            _this.removeClass(wrapper.querySelectorAll('.krootline'), 'krootline');
            _this.removeClass(wrapper.querySelectorAll('.ktopline'), 'ktopline');
            if (!_this.hasClass(element, 'kcollapsed')) {
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
        this.copyFrom = function (event, element) {
            var i;
            var domid = _this.getDataset(element, 'domid');
            if (domid === '') {
                return;
            }
            var orgNest = document.querySelector('#' + domid);
            if (orgNest) {
                var orgEl = orgNest.previousElementSibling;
                element.parentNode.insertBefore(orgNest.cloneNode(true), element.nextSibling);
                var newEl = orgEl.cloneNode(true);
                element.parentNode.insertBefore(newEl, element.nextSibling);
                _this.findInDomlistByClass(newEl.children, 'kname').innerHTML = _this.findInDomlistByClass(element.children, 'kname').innerHTML;
                var allChildren = newEl.nextElementSibling.getElementsByTagName("*");
                for (i = 0; i < allChildren.length; i++) {
                    allChildren[i].removeAttribute('id');
                }
                newEl.nextElementSibling.removeAttribute('id');
                _this.setDataset(newEl.parentNode, 'domid', domid);
                var newInfobox = newEl.querySelector('.khelp');
                var newButton = newEl.querySelector('.kinfobutton');
                var realInfobox = element.querySelector('.khelp');
                var realButton = element.querySelector('.kinfobutton');
                if (newInfobox !== null) {
                    newInfobox.parentNode.removeChild(newInfobox);
                }
                if (newButton !== null) {
                    newButton.parentNode.removeChild(newButton);
                }
                if (realInfobox !== null) {
                    newEl.appendChild(realButton);
                    newEl.appendChild(realInfobox);
                }
                element.parentNode.removeChild(element);
            }
        };
    }
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
    Kdt.prototype.hasClass = function (el, className) {
        if (el.classList) {
            return el.classList.contains(className);
        }
        else {
            return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
        }
    };
    Kdt.prototype.findInDomlistByClass = function (elements, className) {
        className = " " + className + " ";
        for (var i = 0; i < elements.length; i++) {
            if ((" " + elements[i].className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1) {
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
            elements[i].className += ' ' + className;
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
            elements[i].className = elements[i].className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
        }
    };
    Kdt.prototype.toggleClass = function (el, className) {
        if (el.classList) {
            el.classList.toggle(className);
        }
        else {
            var classes = el.className.split(' ');
            var existingIndex = classes.indexOf(className);
            if (existingIndex >= 0) {
                classes.splice(existingIndex, 1);
            }
            else {
                classes.push(className);
            }
            el.className = classes.join(' ');
        }
    };
    Kdt.prototype.getDataset = function (el, what, mustEscape) {
        if (mustEscape === void 0) { mustEscape = false; }
        var result;
        if (typeof el === 'undefined' ||
            typeof el.getAttribute !== 'function') {
            return '';
        }
        result = el.getAttribute('data-' + what);
        if (result === null) {
            return '';
        }
        if (mustEscape === true) {
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
        range.selectNodeContents(el);
        selection.removeAllRanges();
        selection.addRange(range);
    };
    Kdt.prototype.readSettings = function (cookieName) {
        cookieName = cookieName + "=";
        var cookieArray = document.cookie.split(';');
        var result = {};
        var cookieString;
        for (var i = 0; i < cookieArray.length; i++) {
            cookieString = cookieArray[i];
            while (cookieString.charAt(0) === ' ') {
                cookieString = cookieString.substring(1, cookieString.length);
            }
            if (cookieString.indexOf(cookieName) === 0) {
                try {
                    result = JSON.parse(cookieString.substring(cookieName.length, cookieString.length));
                }
                catch (error) {
                }
            }
        }
        return result;
    };
    Kdt.prototype.resetSetting = function (event, element) {
        var date = new Date();
        date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
        var expires = 'expires=' + date.toUTCString();
        document.cookie = 'KrexxDebugSettings={}; ' + expires + '; path=/';
        alert('All local configuration have been reset.\n\nPlease reload the page to use the these settings.');
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
        var elements = document.querySelectorAll(selector);
        for (var i = 0; i < elements.length; i++) {
            if (elements[i].parentNode.nodeName.toUpperCase() !== 'BODY') {
                document.querySelector('body').appendChild(elements[i]);
            }
        }
    };
    ;
    return Kdt;
}());
var Search = (function () {
    function Search(eventHandler, jumpTo) {
        var _this = this;
        this.results = [];
        this.clearSearch = function (event) {
            _this.results[_this.kdt.getDataset(event.target, 'instance')] = [];
        };
        this.displaySearchOptions = function (event, element) {
            _this.kdt.toggleClass(element.parentNode.nextElementSibling, 'khidden');
        };
        this.performSearch = function (event, element) {
            _this.kdt.addClass([element.parentNode.nextElementSibling], 'khidden');
            var config = new SearchConfig();
            config.searchtext = element.parentNode.querySelector('.ksearchfield').value;
            config.caseSensitive = element.parentNode.parentNode.querySelector('.ksearchcase').checked;
            config.searchKeys = element.parentNode.parentNode.querySelector('.ksearchkeys').checked;
            config.searchShort = element.parentNode.parentNode.querySelector('.ksearchshort').checked;
            config.searchLong = element.parentNode.parentNode.querySelector('.ksearchlong').checked;
            config.searchWhole = element.parentNode.parentNode.querySelector('.ksearchwhole').checked;
            if (config.caseSensitive === false) {
                config.searchtext = config.searchtext.toLowerCase();
            }
            if (config.searchtext.length === 0) {
                element.parentNode.querySelector('.ksearch-state').textContent = '<- Please enter a search text.';
                return;
            }
            if (config.searchtext.length > 2 || config.searchWhole) {
                config.instance = _this.kdt.getDataset(element, 'instance');
                _this.retrievePayload(config);
                var collapsed = config.payload.querySelectorAll('.kcollapsed');
                for (var i = 0; i < collapsed.length; i++) {
                    _this.eventHandler.triggerEvent(collapsed[i], 'click');
                }
                if (typeof _this.results[config.instance] !== "undefined") {
                    if (typeof _this.results[config.instance][config.searchtext] === "undefined") {
                        _this.refreshResultlist(config);
                    }
                }
                else {
                    _this.refreshResultlist(config);
                }
                var pointer = _this.results[config.instance][config.searchtext]['pointer'];
                var direction = _this.kdt.getDataset(element, 'direction');
                if (direction === 'forward') {
                    pointer++;
                }
                else {
                    pointer--;
                }
                if (typeof _this.results[config.instance][config.searchtext]['data'][pointer] === "undefined") {
                    if (direction === 'forward') {
                        pointer = 0;
                    }
                    else {
                        pointer = _this.results[config.instance][config.searchtext]['data'].length - 1;
                    }
                }
                if (_this.results[config.instance][config.searchtext]['data'][pointer]) {
                    _this.jumpTo(_this.results[config.instance][config.searchtext]['data'][pointer]);
                }
                element.parentNode.querySelector('.ksearch-state').textContent =
                    (pointer + 1) + ' / ' + (_this.results[config.instance][config.searchtext]['data'].length);
                _this.results[config.instance][config.searchtext]['pointer'] = pointer;
            }
            else {
                element.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
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
            _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            var selector = [];
            if (config.searchKeys === true) {
                selector.push('li.kchild span.kname');
            }
            if (config.searchShort === true) {
                selector.push('li.kchild span.kshort');
            }
            if (config.searchLong === true) {
                selector.push('li div.kpreview');
            }
            _this.results[config.instance] = [];
            _this.results[config.instance][config.searchtext] = [];
            _this.results[config.instance][config.searchtext]['data'] = [];
            _this.results[config.instance][config.searchtext]['pointer'] = [];
            if (selector.length > 0) {
                var list = void 0;
                list = config.payload.querySelectorAll(selector.join(', '));
                var textContent = '';
                for (var i = 0; i < list.length; ++i) {
                    textContent = list[i].textContent;
                    if (config.caseSensitive === false) {
                        textContent = textContent.toLowerCase();
                    }
                    if ((config.searchWhole === true && textContent === config.searchtext) ||
                        (config.searchWhole === false && textContent.indexOf(config.searchtext) > -1)) {
                        _this.kdt.toggleClass(list[i], 'ksearch-found-highlight');
                        _this.results[config.instance][config.searchtext]['data'].push(list[i]);
                    }
                }
            }
            _this.results[config.instance][config.searchtext]['pointer'] = -1;
        };
        this.searchfieldReturn = function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.which !== 13) {
                return;
            }
            _this.eventHandler.triggerEvent(event.target.parentNode.querySelectorAll('.ksearchnow')[1], 'click');
        };
        this.kdt = new Kdt();
        this.eventHandler = eventHandler;
        this.jumpTo = jumpTo;
        this.eventHandler.addEvent('.kwrapper .ksearchcase', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchkeys', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchshort', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchlong', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ksearchwhole', 'change', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .ktab', 'click', this.clearSearch);
        this.eventHandler.addEvent('.kwrapper .koptions', 'click', this.displaySearchOptions);
        this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.searchfieldReturn);
    }
    return Search;
}());
var SearchConfig = (function () {
    function SearchConfig() {
    }
    return SearchConfig;
}());
var Hans = (function () {
    function Hans() {
        var _this = this;
        this.toggle = function (event, element) {
            _this.kdt.toggleClass(element, 'kopened');
            var sibling = element.nextElementSibling;
            do {
                _this.kdt.toggleClass(sibling, 'khidden');
                sibling = sibling.nextElementSibling;
            } while (sibling);
        };
        this.jumpTo = function (el, noHighlight) {
            _this.setHighlighting(el, noHighlight);
            var destination;
            var container = document.querySelector('.kfatalwrapper-outer');
            if (container === null) {
                container = document.querySelector('html');
                ++container.scrollTop;
                if (container.scrollTop === 0 || container.scrollHeight <= container.clientHeight) {
                    container = document.querySelector('body');
                }
                --container.scrollTop;
                destination = el.getBoundingClientRect().top + container.scrollTop - 50;
            }
            else {
                destination = el.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop - 50;
            }
            var diff = Math.abs(container.scrollTop - destination);
            if (diff < 250) {
                return;
            }
            var step;
            if (container.scrollTop < destination) {
                step = Math.round(diff / 12);
            }
            else {
                step = Math.round(diff / 12) * -1;
            }
            var lastValue = container.scrollTop;
            var interval = setInterval(function () {
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
                    elInstance.parentNode.removeChild(elInstance);
                    return;
                }
                opacity -= 0.1;
                elInstance.style.opacity = opacity.toString();
            }, 20);
        };
        this.generateCode = function (event, element) {
            event.stop = true;
            var codedisplay = element.nextElementSibling;
            var resultArray = [];
            var resultString = '';
            var sourcedata;
            var domid;
            var wrapperLeft = '';
            var wrapperRight = '';
            var el = _this.kdt.getParents(element, 'li.kchild')[0];
            while (el) {
                domid = _this.kdt.getDataset(el, 'domid');
                sourcedata = _this.kdt.getDataset(el, 'source');
                wrapperLeft = _this.kdt.getDataset(el, 'codewrapperLeft');
                wrapperRight = _this.kdt.getDataset(el, 'codewrapperRight');
                if (sourcedata === '. . .') {
                    if (domid !== '') {
                        el = document.querySelector('#' + domid).parentNode;
                        resultArray.push(_this.kdt.getDataset(el, 'source'));
                    }
                }
                if (sourcedata !== '') {
                    resultArray.push(sourcedata);
                }
                el = _this.kdt.getParents(el, 'li.kchild')[0];
            }
            resultArray.reverse();
            for (var i = 0; i < resultArray.length; i++) {
                if (resultArray[i] === '. . .') {
                    resultString = '// Value is either protected or private.<br /> // Sorry . . ';
                    break;
                }
                if (resultArray[i] === ';stop;') {
                    resultString = '';
                    resultArray[i] = '';
                }
                if (resultArray[i].indexOf(';firstMarker;') !== -1) {
                    resultString = resultArray[i].replace(';firstMarker;', resultString);
                }
                else {
                    resultString = resultString + resultArray[i];
                }
            }
            resultString = wrapperLeft + resultString + wrapperRight;
            codedisplay.innerHTML = '<div class="kcode-inner">' + resultString + '</div>';
            if (codedisplay.style.display === 'none') {
                codedisplay.style.display = '';
                _this.kdt.selectText(codedisplay);
            }
            else {
                codedisplay.style.display = 'none';
            }
        };
        this.checkSearchInViewport = function () {
            var search = document.querySelector('.kfatalwrapper-outer .search-wrapper');
            search.style.position = '';
            search.style.top = '';
            var rect = search.getBoundingClientRect();
            if (rect.top < 0) {
                search.style.position = 'fixed';
                search.style.top = '0px';
            }
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
            var viewportOffset;
            if (_this.kdt.hasClass(search, 'khidden')) {
                _this.kdt.toggleClass(search, 'khidden');
                search.querySelector('.ksearchfield').focus();
                search.style.position = 'absolute';
                search.style.top = '';
                viewportOffset = search.getBoundingClientRect();
                search.style.position = 'fixed';
                search.style.top = viewportOffset.top + 'px';
            }
            else {
                _this.kdt.toggleClass(search, 'khidden');
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
    }
    Hans.prototype.run = function () {
        this.kdt = new Kdt();
        this.kdt.setJumpTo(this.jumpTo);
        this.eventHandler = new Eventhandler(this.selectors.eventHandler);
        this.search = new Search(this.eventHandler, this.jumpTo);
        this.kdt.moveToBottom(this.selectors.moveToBottom);
        this.initDraxx();
        this.eventHandler.addEvent(this.selectors.close, 'click', this.close);
        this.eventHandler.addEvent(this.selectors.toggle, 'click', this.toggle);
        this.eventHandler.addEvent(this.selectors.setSetting, 'change', this.kdt.setSetting);
        this.eventHandler.addEvent(this.selectors.resetSetting, 'click', this.kdt.resetSetting);
        this.eventHandler.addEvent(this.selectors.copyFrom, 'click', this.kdt.copyFrom);
        this.eventHandler.addEvent(this.selectors.displaySearch, 'click', this.displaySearch);
        this.eventHandler.addEvent(this.selectors.performSearch, 'click', this.search.performSearch);
        this.eventHandler.addEvent(this.selectors.collapse, 'click', this.kdt.collapse);
        this.eventHandler.addEvent(this.selectors.generateCode, 'click', this.generateCode);
        this.eventHandler.addEvent(this.selectors.preventBubble, 'click', this.eventHandler.preventBubble);
        this.eventHandler.addEvent(this.selectors.displayInfoBox, 'click', this.displayInfoBox);
        if (window.location.protocol === 'file:') {
            this.disableForms();
        }
        this.draxx.moveToViewport(this.selectors.moveToViewport);
    };
    Hans.prototype.initDraxx = function () {
        this.draxx = new Draxx('.kwrapper', '.kheadnote', function () {
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
    Hans.prototype.setHighlighting = function (el, noHighlight) {
        var nests = this.kdt.getParents(el, '.knest');
        this.kdt.removeClass(nests, 'khidden');
        for (var i = 0; i < nests.length; i++) {
            this.kdt.addClass([nests[i].previousElementSibling], 'kopened');
        }
        if (noHighlight !== true) {
            this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
            this.kdt.addClass([el], 'highlight-jumpto');
        }
    };
    Hans.prototype.disableForms = function () {
        var elements = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
        for (var i = 0; i < elements.length; i++) {
            elements[i].disabled = true;
        }
    };
    return Hans;
}());
var Selectors = (function () {
    function Selectors() {
    }
    return Selectors;
}());
var SmokyGrey = (function (_super) {
    __extends(SmokyGrey, _super);
    function SmokyGrey() {
        var _this = _super.call(this) || this;
        _this.initDraxx = function () {
            _this.draxx = new Draxx('.kwrapper', '.khandle', function () { }, function () { });
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
                var wrapper = kdt.getParents(element, '.kwrapper')[0];
                if (typeof wrapper === 'undefined') {
                    return;
                }
                var body = wrapper.querySelector('.kdatabody');
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
                            json[prop] = decodeURI(json[prop]);
                            html += '<tr><td class="kinfo">' + prop + '</td><td class="kdesc">' + json[prop] + '</td></tr>';
                            counter++;
                        }
                    }
                }
                if (counter === 0) {
                    html = '<tr><td class="kinfo">No data available for this item.</td><td class="kdesc">Sorry.</td></tr>';
                }
                html = '<table><caption class="kheadline">Additional data</caption><tbody class="kdatabody">' + html + '</tbody></table>';
                body.parentNode.parentNode.innerHTML = html;
                setPayloadMaxHeight();
            }, 100);
        };
        _this.displaySearch = function (event, element) {
            var instance = _this.kdt.getDataset(element.parentNode, 'instance');
            var search = document.querySelector('#search-' + instance);
            var searchtab = document.querySelector('#' + instance + ' .ksearchbutton');
            if (_this.kdt.hasClass(search, 'khidden')) {
                _this.kdt.toggleClass(search, 'khidden');
                _this.kdt.toggleClass(searchtab, 'kactive');
                search.querySelector('.ksearchfield').focus();
            }
            else {
                _this.kdt.toggleClass(search, 'khidden');
                _this.kdt.toggleClass(searchtab, 'kactive');
                _this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            }
        };
        _this.jumpTo = function (el, noHighlight) {
            _this.setHighlighting(el, noHighlight);
            var container = _this.kdt.getParents(el, '.kpayload');
            container.push(document.querySelector('.kfatalwrapper-outer'));
            if (container.length > 0) {
                var destination_1 = el.getBoundingClientRect().top - container[0].getBoundingClientRect().top + container[0].scrollTop - 50;
                var diff = Math.abs(container[0].scrollTop - destination_1);
                var step_1;
                if (container[0].scrollTop < destination_1) {
                    step_1 = Math.round(diff / 12);
                }
                else {
                    step_1 = Math.round(diff / 12) * -1;
                }
                var lastValue_1 = container[0].scrollTop;
                var interval_1 = setInterval(function () {
                    container[0].scrollTop += step_1;
                    if (Math.abs(container[0].scrollTop - destination_1) <= Math.abs(step_1) || container[0].scrollTop === lastValue_1) {
                        container[0].scrollTop = destination_1;
                        clearInterval(interval_1);
                    }
                    lastValue_1 = container[0].scrollTop;
                }, 1);
            }
        };
        _this.selectors.close = '.kwrapper .ktool-tabs .kclose, .kwrapper .kheadnote-wrapper .kclose';
        return _this;
    }
    SmokyGrey.prototype.run = function () {
        _super.prototype.run.call(this);
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
