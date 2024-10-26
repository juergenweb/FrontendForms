/**
 * slider-captcha-js
 *
 * The original script can be found at https://codepen.io/piyushpd139/pen/NWbdgwB
 * Some changes were done to fit my requirements better
 */


(function () {
    "use strict";

    function u(n) {
        var i = document.getElementById(n.id), r = typeof n == "object" && n;
        return new t(i, r)
    }

    var r = function () {
        var u = arguments.length, n = arguments[0] || {}, t, i, r;
        for (typeof n != "object" && typeof n != "function" && (n = {}), u == 1 && (n = this, t--), t = 1; t < u; t++) {
            i = arguments[t];
            for (r in i) Object.prototype.hasOwnProperty.call(i, r) && (n[r] = i[r])
        }
        return n
    }, i = function (n) {
        return typeof n == "function" && typeof n.nodeType != "number"
    }, t = function (n, i) {
        this.$element = n;
        this.options = r({}, t.DEFAULTS, i);
        this.$element.style.position = "relative";
        this.$element.style.width = this.options.width + "px";
        this.$element.style.margin = "10px 0";
        this.init()
    }, n;
    t.VERSION = "1.0";
    t.Author = "argo@163.com";
    t.DEFAULTS = {
        width: 280,
        height: 155,
        PI: Math.PI,
        sliderL: 42,
        sliderR: 9,
        offset: 5,
        loadingText: "æ­£åœ¨åŠ è½½ä¸­...",
        failedText: "å†è¯•ä¸€æ¬¡",
        barText: "å‘å³æ»‘åŠ¨å¡«å……æ‹¼å›¾",
        repeatIcon: "",
        maxLoadCount: 3,
        localImages: function () {
            return "images/Pic" + Math.round(Math.random() * 4) + ".jpg"
        },
        verify: function (n, t) {
            var i = !1;
            return $.ajax({
                url: t,
                data: {datas: JSON.stringify(n)},
                dataType: "json",
                type: "post",
                async: !1,
                success: function (n) {
                    i = JSON.stringify(n);
                    console.log("è¿”å›žç»“æžœï¼š" + i)
                }
            }), i
        },
        remoteUrl: null
    };
    window.sliderCaptcha = u;
    window.sliderCaptcha.Constructor = t;
    n = t.prototype;
    n.init = function () {
        this.initDOM();
        this.initImg();
        this.bindEvents()
    };
    n.initDOM = function () {
        var n = function (n, t) {
                var i = document.createElement(n);
                return i.className = t, i
            }, v = function (n, t) {
                var i = document.createElement("canvas");
                return i.width = n, i.height = t, i
            }, f = v(this.options.width - 2, this.options.height), e = f.cloneNode(!0), t = n("div", "ffm-sliderContainer"),
            l = n("i", "ffm-refreshIcon " + this.options.repeatIcon), o = n("div", "ffm-sliderMask"), y = n("div", "ffm-sliderbg"),
            s = n("div", "ffm-slider"), a = n("i", "ffm-sliderIcon"), h = n("span", "ffm-sliderText"), u, c;
        e.className = "ffm-block";
        h.innerHTML = this.options.barText;
        u = this.$element;
        u.appendChild(f);
        u.appendChild(l);
        u.appendChild(e);
        s.appendChild(a);
        o.appendChild(s);
        t.appendChild(y);
        t.appendChild(o);
        t.appendChild(h);
        u.appendChild(t);
        c = {
            canvas: f,
            block: e,
            sliderContainer: t,
            refreshIcon: l,
            slider: s,
            sliderMask: o,
            sliderIcon: a,
            text: h,
            canvasCtx: f.getContext("2d"),
            blockCtx: e.getContext("2d")
        };
        i(Object.assign) ? Object.assign(this, c) : r(this, c)
    };
    n.initImg = function () {
        var n = this, f = window.navigator.userAgent.indexOf("Trident") > -1,
            r = this.options.sliderL + this.options.sliderR * 2 + 3, e = function (t, i) {
                var r = n.options.sliderL, o = n.options.sliderR, s = n.options.PI, u = n.x, e = n.y;
                t.beginPath();
                t.moveTo(u, e);
                t.arc(u + r / 2, e - o + 2, o, .72 * s, 2.26 * s);
                t.lineTo(u + r, e);
                t.arc(u + r + o - 2, e + r / 2, o, 1.21 * s, 2.78 * s);
                t.lineTo(u + r, e + r);
                t.lineTo(u, e + r);
                t.arc(u + o - 2, e + r / 2, o + .4, 2.76 * s, 1.24 * s, !0);
                t.lineTo(u, e);
                t.lineWidth = 2;
                t.fillStyle = "rgba(255, 255, 255, 0.7)";
                t.strokeStyle = "rgba(255, 255, 255, 0.7)";
                t.stroke();
                t[i]();
                t.globalCompositeOperation = f ? "xor" : "destination-over"
            }, o = function (n, t) {
                return Math.round(Math.random() * (t - n) + n)
            }, t = new Image, u;
        t.crossOrigin = "Anonymous";
        u = 0;
        t.onload = function () {
            n.x = o(r + 10, n.options.width - (r + 10));
            n.y = o(10 + n.options.sliderR * 2, n.options.height - (r + 10));
            e(n.canvasCtx, "fill");
            e(n.blockCtx, "clip");
            n.canvasCtx.drawImage(t, 0, 0, n.options.width - 2, n.options.height);
            n.blockCtx.drawImage(t, 0, 0, n.options.width - 2, n.options.height);
            var i = n.y - n.options.sliderR * 2 - 1, u = n.blockCtx.getImageData(n.x - 3, i, r, r);
            n.block.width = r;
            n.blockCtx.putImageData(u, 0, i + 1);
            n.text.textContent = n.text.getAttribute("data-text")
        };
        t.onerror = function () {
            if (u++, window.location.protocol === "file:" && (u = n.options.maxLoadCount, console.error("can't load pic resource file from File protocal. Please try http or https")), u >= n.options.maxLoadCount) {
                n.text.textContent = "åŠ è½½å¤±è´¥";
                n.classList.add("text-danger");
                return
            }
            t.src = n.options.localImages()
        };
        t.setSrc = function () {
            var r = "", e;
            u = 0;
            n.text.classList.remove("text-danger");
            i(n.options.setSrc) && (r = n.options.setSrc());
            r && r !== "" || (r = "https://picsum.photos/" + n.options.width + "/" + n.options.height + "/?image=" + Math.round(Math.random() * 20));
            f ? (e = new XMLHttpRequest, e.onloadend = function (n) {
                var i = new FileReader;
                i.readAsDataURL(n.target.response);
                i.onloadend = function (n) {
                    t.src = n.target.result
                }
            }, e.open("GET", r), e.responseType = "blob", e.send()) : t.src = r
        };
        t.setSrc();
        this.text.setAttribute("data-text", this.options.barText);
        this.text.textContent = this.options.loadingText;
        this.img = t
    };
    n.clean = function () {
        this.canvasCtx.clearRect(0, 0, this.options.width, this.options.height);
        this.blockCtx.clearRect(0, 0, this.options.width, this.options.height);
        this.block.width = this.options.width
    };
    n.bindEvents = function () {
        var n = this;
        this.$element.addEventListener("selectstart", function () {
            return !1
        });
        this.refreshIcon.addEventListener("click", function () {
            n.text.textContent = n.options.barText;
            n.reset();
            i(n.options.onRefresh) && n.options.onRefresh.call(n.$element)
        });
        var r, u, f = [], t = !1, e = function (i) {
            n.text.classList.contains("text-danger") || (r = i.clientX || i.touches[0].clientX, u = i.clientY || i.touches[0].clientY, t = !0)
        }, o = function (i) {
            var o;
            if (!t) return !1;
            var s = i.clientX || i.touches[0].clientX, h = i.clientY || i.touches[0].clientY, e = s - r, c = h - u;
            if (e < 0 || e + 40 > n.options.width) return !1;
            n.slider.style.left = e - 1 + "px";
            o = (n.options.width - 60) / (n.options.width - 40) * e;
            n.block.style.left = o + "px";
            n.sliderContainer.classList.add("ffm-sliderContainer_active");
            n.sliderMask.style.width = e + 4 + "px";
            f.push(Math.round(c))
        }, s = function (u) {
            var o, e;
            if (!t || (t = !1, o = u.clientX || u.changedTouches[0].clientX, o === r)) return !1;
            n.sliderContainer.classList.remove("ffm-sliderContainer_active");
            n.trail = f;
            e = n.verify();
            e.spliced && e.verified ? (n.sliderContainer.classList.add("ffm-sliderContainer_success"), i(n.options.onSuccess) && n.options.onSuccess.call(n.$element)) : (n.sliderContainer.classList.add("ffm-sliderContainer_fail"), i(n.options.onFail) && n.options.onFail.call(n.$element), setTimeout(function () {
                n.text.innerHTML = n.options.failedText;
                n.reset()
            }, 1e3))
        };
        this.slider.addEventListener("mousedown", e);
        this.slider.addEventListener("touchstart", e);
        document.addEventListener("mousemove", o);
        document.addEventListener("touchmove", o);
        document.addEventListener("mouseup", s);
        document.addEventListener("touchend", s);
        document.addEventListener("mousedown", function () {
            return !1
        });
        document.addEventListener("touchstart", function () {
            return !1
        });
        document.addEventListener("swipe", function () {
            return !1
        })
    };
    n.verify = function () {
        var n = this.trail, r = parseInt(this.block.style.left), t = !1;
        if (this.options.remoteUrl !== null) t = this.options.verify(n, this.options.remoteUrl); else {
            var i = function (n, t) {
                return n + t
            }, u = function (n) {
                return n * n
            }, f = n.reduce(i) / n.length, e = n.map(function (n) {
                return n - f
            }), o = Math.sqrt(e.map(u).reduce(i) / n.length);
            t = o !== 0
        }
        
        return {spliced: Math.abs(r - this.x) < this.options.offset, verified: t}
    };
    n.reset = function () {
        this.sliderContainer.classList.remove("ffm-sliderContainer_fail");
        this.sliderContainer.classList.remove("ffm-sliderContainer_success");
        this.slider.style.left = 0;
        this.block.style.left = 0;
        this.sliderMask.style.width = 0;
        this.clean();
        this.text.setAttribute("data-text", this.text.textContent);
        this.text.textContent = this.options.loadingText;
        this.img.setSrc()
    }
})();


function showSliderCaptcha(formid, x, y) {
// ----set-captcha with script
    let texts = slidertexts["barText"];
    var captcha = sliderCaptcha({
        id: formid + '-captcha',
        loadingText: slidertexts["loadingText"],
        failedText: slidertexts["failedText"],
        barText: slidertexts["barText"],
        offset: slidertexts["offset"],
        onSuccess: function () {
            setTimeout(function () {
                //alert('Your captcha is successfully verified.');
                captcha.reset();
                document.getElementById(formid + "-xPos").value = x;
                document.getElementById(formid + "-yPos").value = y;
                document.getElementById(formid + "-slider-captcha").checked = true;
                document.getElementById(formid + "-captcha").setAttribute('data-validated', true);
                let wrapper = document.getElementById(formid + "-captcha");
                wrapper.innerHTML = "";
            }, 500);
        },
        setSrc: function () {
            //return 'https://picsum.photos/' + Math.round(Math.random() * 136) + '.jpg';
        },
    });
}


function listenToSliderCaptchaCheckboxes(){

    let checkedBoxes = document.getElementsByClassName("ff-slidercaptcha-checkbox");

    // add eventlistener for click action
    for (let i = 0; i < checkedBoxes.length; i++) {

        let x = checkedBoxes[i].dataset.x;
        let y = checkedBoxes[i].dataset.y;
        let id = checkedBoxes[i].dataset.formid;

        // uncheck all checkboxes first
        checkedBoxes[i].checked = false;

        checkedBoxes[i].addEventListener("click", function (e) {
            e.preventDefault();

            // load canvas into the captcha div if it does not exist
            let wrapper = document.getElementById(id + "-captcha");
            if ((!wrapper.innerHTML) && (wrapper.getAttribute("data-validated") == "false")) {
                showSliderCaptcha(id, x, y);
            }

        }, false);
    }
}

/** Add eventlistener to all slider captcha checkboxes **/
window.onload = function () {
    listenToSliderCaptchaCheckboxes();
}
