(function () {
    var DURATION_SCALE = 0.48;
    var DELAY_SCALE = 0.18;
    var MIN_DURATION = 0.24;
    var INTRO_DELAY_MS = 45;
    var processedFlag = 'data-siec-anim-speedup';
    var sbsAttrs = [
        'data-animate-sbs-opts',
        'data-animate-sbs-opts-res-320',
        'data-animate-sbs-opts-res-480',
        'data-animate-sbs-opts-res-640',
        'data-animate-sbs-opts-res-960',
        'data-field-sbsopts-value'
    ];

    function roundValue(value) {
        return Math.round(value * 1000) / 1000;
    }

    function scaleNumber(rawValue, minValue, scale) {
        var number = parseFloat(rawValue);

        if (isNaN(number)) {
            return rawValue;
        }

        var scaled = roundValue(number * scale);

        if (typeof minValue === 'number' && scaled < minValue) {
            scaled = minValue;
        }

        return String(scaled);
    }

    function scaleSbsOptions(rawValue) {
        if (!rawValue) {
            return rawValue;
        }

        return rawValue.replace(/(['"]?)(ti|dt)(['"]?\s*:\s*['"]?)(-?\d+(?:\.\d+)?)(['"]?)/g, function (
            _match,
            keyQuote,
            keyName,
            separator,
            numericValue,
            valueQuote
        ) {
            var scale = keyName === 'ti' ? DURATION_SCALE : DELAY_SCALE;
            var minValue = keyName === 'ti' ? MIN_DURATION * 1000 : 0;
            var scaledValue = roundValue(parseFloat(numericValue) * scale);

            if (scaledValue < minValue) {
                scaledValue = minValue;
            }

            return keyQuote + keyName + separator + scaledValue + valueQuote;
        });
    }

    function processRoot(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var animatedElements = scope.querySelectorAll ? scope.querySelectorAll('.t-animate, [data-animate-sbs-opts], [data-field-sbsopts-value]') : [];

        Array.prototype.forEach.call(animatedElements, function (element) {
            if (element.getAttribute(processedFlag) === 'y') {
                return;
            }

            if (element.hasAttribute('data-animate-duration')) {
                element.setAttribute(
                    'data-animate-duration',
                    scaleNumber(element.getAttribute('data-animate-duration'), MIN_DURATION, DURATION_SCALE)
                );
            }

            if (element.hasAttribute('data-animate-delay')) {
                element.setAttribute(
                    'data-animate-delay',
                    scaleNumber(element.getAttribute('data-animate-delay'), 0, DELAY_SCALE)
                );
            }

            Array.prototype.forEach.call(sbsAttrs, function (attrName) {
                if (!element.hasAttribute(attrName)) {
                    return;
                }

                element.setAttribute(attrName, scaleSbsOptions(element.getAttribute(attrName)));
            });

            element.setAttribute(processedFlag, 'y');
        });
    }

    function speedUpIntroFade() {
        if (!document.querySelectorAll) {
            return;
        }

        setTimeout(function () {
            var records = document.querySelectorAll('.t-records');

            Array.prototype.forEach.call(records, function (record) {
                record.classList.add('t-records_animated');
                record.classList.add('t-records_visible');
            });

            if (typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('visited', 'y');
            }
        }, INTRO_DELAY_MS);
    }

    function injectStyleOverrides() {
        if (!document.head || document.getElementById('siec-animation-speedup-style')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'siec-animation-speedup-style';
        style.type = 'text/css';
        style.textContent = [
            '.t-records_animated{transition-property:opacity!important;transition-duration:.18s!important;transition-timing-function:cubic-bezier(.22,.61,.36,1)!important;}',
            '.r_anim{transition-duration:.22s!important;transition-timing-function:cubic-bezier(.22,.61,.36,1)!important;}',
            '.t-slds__arrow_wrapper,.t-btn,.tn-atom{transition-duration:.22s!important;transition-timing-function:cubic-bezier(.22,.61,.36,1)!important;}'
        ].join('');
        document.head.appendChild(style);
    }

    function init() {
        injectStyleOverrides();
        processRoot(document);
        speedUpIntroFade();

        if (!window.MutationObserver || !document.documentElement) {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            Array.prototype.forEach.call(mutations, function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (node && node.nodeType === 1) {
                        processRoot(node);
                    }
                });
            });
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
