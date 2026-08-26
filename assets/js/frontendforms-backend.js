/*
JavaScript file for the FrontendForms backend (module configuration and
FrontendFormsManager pages)

Note: unlike frontendforms-frontend.js, this file DOES depend on jQuery,
since ProcessWire's admin area loads it by default.

Created by Jürgen K.
https://github.com/juergenweb
File name: frontendforms-backend.js
Created: 17.07.2022
*/

/*jslint browser:true */

(function () {
    "use strict";

    /**
     * Briefly flash a colored background on the given element, then fade
     * it back to its original background color. Used to make it
     * unmistakably clear which field is meant after a scroll, even inside
     * a large fieldset where the scroll position alone might not make
     * that obvious.
     * @param {Element} element
     * @returns {void}
     */
    function highlightElement(element) {
        element.style.transition = "background-color 0.3s ease";
        const originalBackground = element.style.backgroundColor;
        element.style.backgroundColor = "#fff3b8";
        setTimeout(function () {
            element.style.backgroundColor = originalBackground;
        }, 1500);
    }

    /**
     * Click handler for the "enable logging" link inside the module
     * configuration. Expands the "spam" and "failed-attempts" fieldsets
     * so the visitor immediately sees the related, now-relevant settings
     * instead of having to manually expand a collapsed fieldset, then
     * scrolls up to the "spam" fieldset.
     *
     * Also conditionally highlights the two settings that actually need
     * attention for logging to take effect: wrap_Inputfield_input_maxAttempts
     * only if input_maxAttempts is currently 0 (a value of 0 means no
     * attempts are ever logged), and
     * wrap_Inputfield_input_logFailedAttempts only if the
     * input_logFailedAttempts checkbox is not checked. A field already
     * configured correctly is left alone.
     *
     * Expands by clicking each fieldset's own header, rather than
     * directly removing ProcessWire's InputfieldStateCollapsed class:
     * PW tracks the collapsed/expanded state internally (not just via
     * that CSS class), so bypassing its own toggle logic left that
     * internal state out of sync with the visible markup, making the
     * fieldset impossible to collapse again manually afterwards.
     *
     * event.preventDefault() stops the link's native anchor-jump
     * behaviour (if it points to e.g. "#spam"), so a scroll has to be
     * triggered explicitly here instead - it is not automatic.
     * @param {Event} event - the click event on the "enable-logging" link
     * @returns {void}
     */
    function openFieldset(event) {

        event.preventDefault();

        const fieldset_spam = document.getElementById("spam");
        const fieldset_attempts = document.getElementById("failed-attempts");

        [fieldset_spam, fieldset_attempts].forEach(function (fieldset) {
            if (fieldset && fieldset.classList.contains("InputfieldStateCollapsed")) {
                const header = fieldset.querySelector(".InputfieldHeader");
                if (header) {
                    header.click();
                } else {
                    fieldset.classList.remove("InputfieldStateCollapsed");
                }
            }
        });

        if (fieldset_spam) {
            // force the browser to apply the expand above immediately
            // before scrolling, otherwise the target position is
            // measured while the layout is still changing
            void fieldset_spam.offsetHeight;
            setTimeout(function () {
                fieldset_spam.scrollIntoView({behavior: "smooth", block: "start"});
            }, 300);
            setTimeout(function () {
                fieldset_spam.scrollIntoView({behavior: "smooth", block: "start"});

                const maxAttemptsWrap = document.getElementById("wrap_Inputfield_input_maxAttempts");
                const maxAttemptsInput = document.querySelector('[name="input_maxAttempts"]');
                if (maxAttemptsWrap && maxAttemptsInput && Number(maxAttemptsInput.value) === 0) {
                    highlightElement(maxAttemptsWrap);
                }

                const logFailedWrap = document.getElementById("wrap_Inputfield_input_logFailedAttempts");
                const logFailedInput = document.querySelector('input[type="checkbox"][name="input_logFailedAttempts"]');
                if (logFailedWrap && logFailedInput && !logFailedInput.checked) {
                    highlightElement(logFailedWrap);
                }
            }, 900);
        }
    }

    /**
     * Reveal and scroll to a specific Inputfield, identified via a
     * data attribute on the <body> tag (data-ff-scroll-to, holding the
     * target element's id), if present. Used by findFontFiles() in
     * FrontendForms.module: after a manual font search, the page reload
     * carries this attribute for one request so the admin lands right on
     * the (possibly newly revealed) font family field instead of having
     * to scroll/search for it again.
     *
     * Expands the target and any collapsed ancestor Inputfield fieldset
     * first (scrolling to a hidden element either does nothing or lands
     * at the wrong position in most browsers), then scrolls smoothly
     * into view after a short delay for the expand animation to finish.
     * @returns {void}
     */
    function scrollToFlaggedField() {

        const targetId = document.body.getAttribute("data-ff-scroll-to");
        if (!targetId) {
            return;
        }

        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        // walk up a fixed, bounded number of ancestors (not a while loop
        // re-querying closest() each pass, which could loop forever if a
        // click handler does not remove the collapsed class synchronously)
        let el = target;
        for (let i = 0; i < 20 && el; i++) {
            if (el.classList && el.classList.contains("InputfieldStateCollapsed")) {
                const header = el.querySelector(".InputfieldHeader");
                if (header) {
                    header.click();
                }
                el.classList.remove("InputfieldStateCollapsed");
            }
            el = el.parentElement;
        }

        // force the browser to apply the layout changes from the expand
        // logic above immediately, rather than potentially deferring them
        // until some later point - reading a layout property (like
        // offsetHeight) forces a synchronous reflow.
        void target.offsetHeight;

        function doScroll() {
            target.scrollIntoView({behavior: "smooth", block: "center"});
        }

        // Scroll twice, with a second, later pass repeating the same
        // scroll: expanding a parent fieldset (the header.click() calls
        // above) can itself trigger its own delayed scroll/jump behavior
        // in some admin themes (e.g. a CSS transition-driven scroll into
        // view of the fieldset header), which can run after - and
        // override - a single scrollIntoView() call made here. Repeating
        // the scroll after a longer delay re-asserts the intended
        // position once any such competing behavior has settled.
        setTimeout(doScroll, 300);
        setTimeout(function () {
            doScroll();
            highlightElement(target);
        }, 900);
    }

    /**
     * Names of the buttons that trigger a side effect other than "save
     * the whole form" (searching for fonts, re-downloading the stopword
     * list, rebuilding the password blacklist) - clicking one of these
     * only saves the field(s) that specific action is about, not any
     * other fields the admin may have changed in the same form. See
     * guardActionButtons() below.
     * @type {string[]}
     */
    const ACTION_BUTTON_NAMES = [
        "input_submit_refreshFonts_frontendforms",
        "submit_save_passwordblacklist",
        "submit_save_stopwordlist"
    ];

    /**
     * Show a confirmation modal warning that unsaved changes elsewhere in
     * the form will be lost if the admin proceeds with the given action
     * button, with "Continue" and "Cancel" choices.
     * @param {string} message - the warning text to display
     * @param {function} onContinue - called if the admin chooses to proceed anyway
     * @returns {void}
     */
    function confirmDiscardChanges(message, onContinue) {

        const overlay = document.createElement("div");
        overlay.setAttribute("style",
            "position: fixed; inset: 0; z-index: 10000; " +
            "background: rgba(0, 0, 0, 0.5); " +
            "display: flex; align-items: center; justify-content: center;"
        );

        const box = document.createElement("div");
        box.setAttribute("style",
            "background: #fff; color: #333; border-radius: 4px; " +
            "max-width: 90%; width: 420px; padding: 24px; " +
            "box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3); " +
            "font-size: 14px; line-height: 1.5; text-align: center;"
        );

        const text = document.createElement("p");
        text.textContent = message;
        text.style.margin = "0 0 20px 0";

        const buttonRow = document.createElement("div");
        buttonRow.setAttribute("style", "display: flex; gap: 10px; justify-content: center;");

        const cancelButton = document.createElement("button");
        cancelButton.type = "button";
        cancelButton.textContent = document.body.getAttribute("data-ff-btn-cancel") || "Cancel";
        cancelButton.setAttribute("style",
            "background: #e6e6e6; color: #333; border: none; " +
            "border-radius: 3px; padding: 8px 24px; cursor: pointer; " +
            "font-size: 14px;"
        );

        const continueButton = document.createElement("button");
        continueButton.type = "button";
        continueButton.textContent = document.body.getAttribute("data-ff-btn-continue") || "Continue";
        continueButton.setAttribute("style",
            "background: #1c94c4; color: #fff; border: none; " +
            "border-radius: 3px; padding: 8px 24px; cursor: pointer; " +
            "font-size: 14px;"
        );

        function close() {
            overlay.remove();
            document.removeEventListener("keydown", onKeydown);
        }

        function onKeydown(event) {
            if (event.key === "Escape") {
                close();
            }
        }

        cancelButton.addEventListener("click", close);
        continueButton.addEventListener("click", function () {
            close();
            onContinue();
        });
        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) {
                close();
            }
        });
        document.addEventListener("keydown", onKeydown);

        buttonRow.appendChild(cancelButton);
        buttonRow.appendChild(continueButton);
        box.appendChild(text);
        box.appendChild(buttonRow);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        cancelButton.focus();
    }

    /**
     * Warn before running one of the manual action buttons
     * (ACTION_BUTTON_NAMES) if the form has unsaved changes elsewhere:
     * clicking e.g. "search for new fonts" only saves the field(s) that
     * action is about, so any other field the admin changed in the same
     * form would silently appear unsaved afterwards. Tracks "dirty" state
     * via input/change events on the form, and - if dirty - intercepts
     * the click to show a confirmation dialog first instead.
     *
     * On "Continue", resubmits the form via HTMLFormElement.requestSubmit
     * (submitter), so the same button's name/value still reaches the
     * server correctly. On "Cancel", nothing is submitted, letting the
     * admin save their other changes via the form's regular save button
     * first.
     * @returns {void}
     */
    function guardActionButtons() {

        const actionButtons = ACTION_BUTTON_NAMES
            .map(name => document.querySelector('[name="' + name + '"]'))
            .filter(button => button !== null);

        if (actionButtons.length === 0) {
            return;
        }

        let formIsDirty = false;
        const form = actionButtons[0].form;

        if (!form) {
            return;
        }

        form.addEventListener("input", function () {
            formIsDirty = true;
        });
        form.addEventListener("change", function () {
            formIsDirty = true;
        });

        actionButtons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                if (!formIsDirty) {
                    return;
                }
                event.preventDefault();
                const warningText = document.body.getAttribute("data-ff-discard-warning")
                    || "There are unsaved changes in this form. If you continue, these changes will be lost, since this action only saves the related settings. Please save your changes first, or choose \u201cContinue\u201d to discard them.";
                confirmDiscardChanges(
                    warningText,
                    function () {
                        formIsDirty = false;
                        form.requestSubmit(button);
                    }
                );
            });
        });
    }

    /**
     * Show a simple, self-contained popup modal with a message flagged
     * via data-ff-popup-message on the <body> tag (if present). Used by
     * blacklistButtonSubmit() in FrontendForms.module: after a manual
     * blacklist update, the page reload carries this attribute for one
     * request so the same message shown as a regular PW notice is also
     * shown as a popup, making it harder to miss.
     *
     * Built as a minimal, dependency-free modal (not using Vex) since
     * Vex is not loaded on every admin page this can run on (e.g. the
     * module configuration page).
     * @returns {void}
     */
    function showFlaggedPopupMessage() {

        const message = document.body.getAttribute("data-ff-popup-message");
        if (!message) {
            return;
        }

        const overlay = document.createElement("div");
        overlay.setAttribute("style",
            "position: fixed; inset: 0; z-index: 10000; " +
            "background: rgba(0, 0, 0, 0.5); " +
            "display: flex; align-items: center; justify-content: center;"
        );

        const box = document.createElement("div");
        box.setAttribute("style",
            "background: #fff; color: #333; border-radius: 4px; " +
            "max-width: 90%; width: 400px; padding: 24px; " +
            "box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3); " +
            "font-size: 14px; line-height: 1.5; text-align: center;"
        );

        const text = document.createElement("p");
        text.textContent = message;
        text.style.margin = "0 0 20px 0";

        const button = document.createElement("button");
        button.type = "button";
        button.textContent = document.body.getAttribute("data-ff-btn-ok") || "OK";
        button.setAttribute("style",
            "background: #1c94c4; color: #fff; border: none; " +
            "border-radius: 3px; padding: 8px 24px; cursor: pointer; " +
            "font-size: 14px;"
        );

        function close() {
            overlay.remove();
            document.removeEventListener("keydown", onKeydown);
        }

        function onKeydown(event) {
            if (event.key === "Escape") {
                close();
            }
        }

        button.addEventListener("click", close);
        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) {
                close();
            }
        });
        document.addEventListener("keydown", onKeydown);

        box.appendChild(text);
        box.appendChild(button);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        button.focus();
    }

    /**
     * On page load:
     * - make all external links inside Inputfields open in a new tab,
     *   with the recommended rel attributes for security
     * - wire up the "enable logging" link (if present on this page) to
     *   openFieldset()
     * - reveal and scroll to a field flagged via data-ff-scroll-to (if any)
     * - show a popup with a message flagged via data-ff-popup-message (if any)
     * - warn before running a manual action button if there are unsaved
     *   changes elsewhere in the form
     */
    window.addEventListener('load', function () {

        document.querySelectorAll('.Inputfield a.external').forEach(a => {
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
        });

        const login_enable_link = document.getElementById("enable-logging");

        if (login_enable_link) {

            // add event listener
            login_enable_link.addEventListener("click", openFieldset);

        }

        scrollToFlaggedField();
        showFlaggedPopupMessage();
        guardActionButtons();

    });

})();

/**
 * Ajax calls for FrontendForms Manager.
 *
 * Wires up the statistics chart toggle buttons (e.g. "Status" / "Visibility")
 * on the questions overview page: clicking one requests the corresponding
 * chart markup from the current page via a POST request and swaps it into
 * the #questionsstatistic container, replacing the initial #pageload
 * placeholder on first use.
 *
 * Depends on jQuery, which ProcessWire's admin area loads by default - this
 * check makes that dependency explicit rather than failing with a bare
 * "$ is not defined" error if it's ever missing (e.g. a custom admin theme).
 */
if (typeof jQuery !== 'undefined') {
    (function ($) {
        "use strict";

        $(document).ready(function () {
            if ($("button.statistics").length > 0) {

                $("button.statistics").click(function (event) {
                    event.preventDefault();


                    $.ajax({
                        url: $(location).attr("href"),
                        type: 'post', // performing a POST request
                        data: {
                            type: $(this).data('statistic')// will be accessible in $_POST
                        },
                        success: function (result) {
                            // remove pageload container first
                            $('#pageload').remove();
                            $("#questionsstatistic").html(result);
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error('FrontendForms statistics request failed:', textStatus, errorThrown);
                        }
                    });

                });
            }

        });

    })(jQuery);
}
