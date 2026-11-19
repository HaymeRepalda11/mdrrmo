/* =========================================================
   Backend connection – talks to the PHP + MySQL API in /api (XAMPP)
   ========================================================= */
(function () {
  "use strict";
  var BASE = "api/";
  var csrf = "";
  var mePromise = null;
  var NAMES = "home|about|services|news|contact|safety-tips";
  var REGIONS = [".topbar", "main", ".site-footer"];

  function request(method, path, data) {
    var opts = { method: method, credentials: "same-origin", headers: { "Accept": "application/json" } };
    if (method !== "GET") {
      opts.headers["Content-Type"] = "application/json";
      opts.headers["X-CSRF-Token"] = csrf;
      opts.body = JSON.stringify(data || {});
    }
    return fetch(BASE + path, opts).then(function (res) {
      return res.text().then(function (txt) {
        var j = null;
        try { j = JSON.parse(txt); } catch (e) {}
        if (!j) {
          var e1 = new Error("The server did not answer correctly. Open the site through XAMPP (http://localhost/mdrrmo/) and make sure Apache and MySQL are running.");
          e1.status = res.status; throw e1;
        }
        if (!j.ok) { var e2 = new Error(j.error || "Something went wrong."); e2.status = res.status; e2.data = j; throw e2; }
        return j;
      });
    }, function () {
      throw new Error("Can't reach the server. Start Apache and MySQL in XAMPP, then open the site at http://localhost/mdrrmo/");
    });
  }

  function me() {
    if (!mePromise) mePromise = request("GET", "me.php").then(function (j) { csrf = j.csrf; return j; });
    return mePromise;
  }

  /* page name used for saved edits: home.html and homeedit.html -> "home" */
  function pageKey() {
    var f = (location.pathname.split("/").pop() || "").replace(/\.html$/, "").replace(/edit$/, "");
    return new RegExp("^(" + NAMES + ")$").test(f) ? f : "";
  }
  function toPublicLinks(h) {
    return h.replace(new RegExp("(href=[\"'])(" + NAMES + "|index)edit\\.html", "g"), "$1$2.html");
  }
  function toEditLinks(h) {
    return h.replace(new RegExp("(href=[\"'])(" + NAMES + ")\\.html", "g"), "$1$2edit.html");
  }

  /* defence in depth: never let saved HTML run scripts */
  function sanitize(html) {
    var doc = new DOMParser().parseFromString("<body>" + html, "text/html");
    Array.prototype.slice.call(doc.querySelectorAll("script,iframe,object,embed,applet,base,meta,link")).forEach(function (n) { n.remove(); });
    Array.prototype.slice.call(doc.querySelectorAll("*")).forEach(function (el) {
      Array.prototype.slice.call(el.attributes).forEach(function (a) {
        var n = a.name.toLowerCase(), v = a.value.replace(/[\s\u0000-\u001f]/g, "").toLowerCase();
        var urlAttr = n === "href" || n === "src" || n === "action" || n === "formaction" || n === "xlink:href";
        if (n.indexOf("on") === 0 || (urlAttr && /^(javascript|vbscript|data:text\/html)/.test(v))) el.removeAttribute(a.name);
      });
    });
    return doc.body.innerHTML;
  }

  /* Saved page edits are fetched synchronously so they are in place BEFORE
     the rest of the script wires up the page (same as the old localStorage restore). */
  function applyContent(isEdit) {
    var page = pageKey();
    if (!page) return false;
    var regions = null;
    try {
      var x = new XMLHttpRequest();
      x.open("GET", BASE + "content.php?page=" + encodeURIComponent(page), false);
      x.send();
      if (x.status === 200) {
        var j = JSON.parse(x.responseText);
        if (j && j.ok && j.regions) regions = j.regions;
      }
    } catch (e) {}
    if (!regions) return false;
    var any = false;
    REGIONS.forEach(function (sel) {
      var el = document.querySelector(sel);
      if (el && typeof regions[sel] === "string") {
        var h = sanitize(regions[sel]);
        el.innerHTML = isEdit ? toEditLinks(h) : toPublicLinks(h);
        any = true;
      }
    });
    return any;
  }

  /* remember the untouched nav button / hero text / typing text so edits saved by an
     admin never contain "👤 Name • Log out", "Welcome back…" or half-typed words */
  var pristine = {};
  function capturePristine() {
    var ab = document.getElementById("authBtn"), ha = document.getElementById("heroAuth"), ty = document.getElementById("typed");
    pristine.authBtn = ab ? { text: ab.textContent, href: ab.getAttribute("href") } : null;
    pristine.heroAuth = ha ? ha.innerHTML : null;
    pristine.typed = ty ? ty.textContent : null;
  }

  window.MDRRMO = {
    me: me,
    get: function (p) { return request("GET", p); },
    post: function (p, d) {
      return me().then(function () {
        return request("POST", p, d).then(function (j) { if (j.csrf) csrf = j.csrf; return j; });
      });
    },
    pageKey: pageKey, toPublicLinks: toPublicLinks, toEditLinks: toEditLinks,
    applyContent: applyContent, capturePristine: capturePristine, pristine: pristine
  };
})();

/* Apply the saved page edits (made with the admin editor) before anything else runs */
MDRRMO.applyContent(false);
MDRRMO.capturePristine();

/* =========================================================
   MDRRMO Los Baños – Animations & Interactions
   ========================================================= */
(function () {
  "use strict";

  /* ---------- Theme (light / dark) ---------- */
  var root = document.documentElement;
  var saved = null;
  try { saved = localStorage.getItem("mdrrmo-theme"); } catch (e) {}
  var prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
  setTheme(saved || (prefersDark ? "dark" : "light"));

  function setTheme(mode) {
    root.setAttribute("data-theme", mode);
    var btn = document.getElementById("themeToggle");
    if (btn) {
      btn.textContent = mode === "dark" ? "☀️" : "🌙";
      btn.setAttribute("aria-label", mode === "dark" ? "Switch to light mode" : "Switch to dark mode");
      btn.title = mode === "dark" ? "Light mode" : "Dark mode";
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    setTheme(root.getAttribute("data-theme"));

    var toggle = document.getElementById("themeToggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
        setTheme(next);
        try { localStorage.setItem("mdrrmo-theme", next); } catch (e) {}
      });
    }

    /* ---------- Mobile menu ---------- */
    var menuBtn = document.getElementById("menuToggle");
    var links = document.getElementById("navLinks");
    if (menuBtn && links) {
      menuBtn.addEventListener("click", function () {
        var open = links.classList.toggle("open");
        menuBtn.textContent = open ? "✕" : "☰";
        menuBtn.setAttribute("aria-expanded", open);
      });
    }

    /* ---------- Active nav link ---------- */
    var page = location.pathname.split("/").pop() || "home.html";
    document.querySelectorAll(".nav-links a").forEach(function (a) {
      if (a.getAttribute("href") === page) a.classList.add("active");
    });

    /* ---------- Scroll reveal ---------- */
    var reveals = document.querySelectorAll(".reveal");
    if ("IntersectionObserver" in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });
      reveals.forEach(function (el, i) {
        el.style.transitionDelay = (i % 4) * 90 + "ms";
        io.observe(el);
      });
    } else {
      reveals.forEach(function (el) { el.classList.add("visible"); });
    }

    /* ---------- Animated counters ---------- */
    var counters = document.querySelectorAll("[data-count]");
    if (counters.length && "IntersectionObserver" in window) {
      var co = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          var el = entry.target;
          var target = parseInt(el.getAttribute("data-count"), 10);
          var suffix = el.getAttribute("data-suffix") || "";
          var start = null, duration = 1800;
          function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.floor(eased * target).toLocaleString() + suffix;
            if (p < 1) requestAnimationFrame(step);
          }
          requestAnimationFrame(step);
          co.unobserve(el);
        });
      }, { threshold: 0.5 });
      counters.forEach(function (c) { co.observe(c); });
    }

    /* ---------- Header shadow, progress bar, back-to-top ---------- */
    var header = document.querySelector(".site-header");
    var progress = document.getElementById("progress");
    var toTop = document.getElementById("toTop");
    function onScroll() {
      var y = window.scrollY || document.documentElement.scrollTop;
      var h = document.documentElement.scrollHeight - window.innerHeight;
      if (header) header.classList.toggle("scrolled", y > 20);
      if (progress) progress.style.width = (h > 0 ? (y / h) * 100 : 0) + "%";
      if (toTop) toTop.classList.toggle("show", y > 400);
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
    if (toTop) toTop.addEventListener("click", function () { window.scrollTo({ top: 0, behavior: "smooth" }); });

    /* ---------- Typing effect (homepage) ---------- */
    var typed = document.getElementById("typed");
    if (typed) {
      var words = ["Prepared.", "Resilient.", "Responsive.", "Together."];
      var w = 0, c = 0, deleting = false;
      (function tick() {
        var word = words[w];
        typed.textContent = deleting ? word.slice(0, c--) : word.slice(0, c++);
        var delay = deleting ? 60 : 110;
        if (!deleting && c > word.length) { deleting = true; delay = 1400; }
        else if (deleting && c < 0) { deleting = false; w = (w + 1) % words.length; c = 0; delay = 300; }
        setTimeout(tick, delay);
      })();
    }

    /* ---------- News filter ---------- */
    var filterBtns = document.querySelectorAll(".filter-btn");
    var newsCards = document.querySelectorAll(".news-card[data-cat]");
    filterBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        filterBtns.forEach(function (b) { b.classList.remove("active"); });
        btn.classList.add("active");
        var cat = btn.getAttribute("data-filter");
        newsCards.forEach(function (card) {
          var show = cat === "all" || card.getAttribute("data-cat") === cat;
          card.classList.toggle("hide", !show);
          if (show) { card.classList.remove("visible"); void card.offsetWidth; card.classList.add("visible"); }
        });
      });
    });

    /* ---------- Contact form → saved in the database ---------- */
    var form = document.getElementById("contactForm");
    if (form) {
      var cfBusy = false;
      var cfErr = document.createElement("div");
      cfErr.className = "form-alert";
      cfErr.setAttribute("role", "alert");
      form.insertBefore(cfErr, form.firstChild);

      form.addEventListener("submit", function (e) {
        e.preventDefault();
        if (cfBusy) return;
        var ok = true;
        form.querySelectorAll(".field[data-required]").forEach(function (field) {
          var input = field.querySelector("input, textarea, select");
          var val = input.value.trim();
          var valid = val !== "";
          if (valid && input.type === "email") valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
          field.classList.toggle("invalid", !valid);
          if (!valid) ok = false;
        });
        var msg = document.getElementById("formSuccess");
        cfErr.className = "form-alert";
        if (!ok) { msg.classList.remove("show"); return; }

        function v(id) { var el = document.getElementById(id); return el ? el.value.trim() : ""; }
        var btn = form.querySelector('button[type="submit"]');
        cfBusy = true; if (btn) btn.disabled = true;
        MDRRMO.post("contact.php", {
          name: v("name"), email: v("email"), phone: v("phone"), topic: v("topic"), message: v("message")
        }).then(function () {
          msg.classList.add("show");
          form.reset();
          setTimeout(function () { msg.classList.remove("show"); }, 6000);
        }).catch(function (err) {
          msg.classList.remove("show");
          cfErr.textContent = err.message;
          cfErr.className = "form-alert show err";
        }).then(function () { cfBusy = false; if (btn) btn.disabled = false; });
      });
    }

    /* =====================================================
       Accounts – saved in the MySQL database through the PHP API (api/ folder).
       Passwords are hashed on the server; the browser only keeps a session cookie.
       ===================================================== */
    var HOME = "home.html", PROFILE = "profile.html", INDEX = "index.html";
    function homeFor(user) { return user && user.role === "admin" ? "homeedit.html" : HOME; }

    var authBtn = document.getElementById("authBtn");
    var heroAuth = document.getElementById("heroAuth");

    /* Form helpers (used by the forms below) */
    function setErr(id, bad) { var f = document.getElementById(id); if (f) f.classList.toggle("invalid", bad); return bad; }
    function showAlert(el, msg, type) { el.textContent = msg; el.className = "form-alert show " + type; }
    function validEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
    function busyBtn(btn, on) { if (!btn) return; btn.classList.toggle("loading", on); btn.disabled = on; }

    document.querySelectorAll(".pw-toggle").forEach(function (b) {
      b.addEventListener("click", function () {
        var input = document.getElementById(b.getAttribute("data-target"));
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        b.textContent = show ? "🙈" : "👁️";
        b.setAttribute("aria-label", show ? "Hide password" : "Show password");
      });
    });

    function logout() {
      return MDRRMO.post("logout.php").catch(function () {}).then(function () { location.href = HOME; });
    }

    MDRRMO.me().then(function (s) { return s; }, function () { return { user: null, showResetCode: false }; }).then(function (state) {
      var session = state.user || null;

      /* Navigation button + admin link */
      if (authBtn && session) {
        if (authBtn.hasAttribute("data-profile")) {
          authBtn.textContent = "👤 Profile";
          authBtn.setAttribute("href", PROFILE);
        } else {
          authBtn.textContent = "👤 " + session.name.split(" ")[0] + " • Log out";
          authBtn.setAttribute("href", "#");
          authBtn.addEventListener("click", function (e) { e.preventDefault(); logout(); });
        }
      }
      var navLinks = document.getElementById("navLinks");
      if (navLinks && authBtn && session && session.role === "admin") {
        var adm = document.createElement("a");
        adm.className = "js-admin-link";
        adm.href = "admin.html";
        adm.textContent = "⚙️ Admin";
        navLinks.insertBefore(adm, authBtn);
      }

      /* Homepage hero buttons */
      if (heroAuth && session) {
        heroAuth.textContent = "";
        var wel = document.createElement("p");
        wel.className = "welcome";
        wel.appendChild(document.createTextNode("👋 Welcome back, "));
        var strong = document.createElement("strong");
        strong.textContent = session.name.split(" ")[0];
        wel.appendChild(strong);
        wel.appendChild(document.createTextNode("!"));
        heroAuth.appendChild(wel);
      }

      function strengthMeter(input, bar, txt) {
        input.addEventListener("input", function () {
          var v = input.value, s = 0;
          if (v.length >= 8) s++;
          if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
          if (/\d/.test(v)) s++;
          if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) s++;
          var levels = [["0%", "transparent", "Use 8+ characters with letters and numbers."], ["25%", "#d62828", "Weak"], ["50%", "#f59e0b", "Fair"], ["75%", "#1d4ed8", "Good"], ["100%", "#1faa59", "Strong"]];
          if (!v) s = 0;
          bar.style.width = levels[s][0]; bar.style.background = levels[s][1]; txt.textContent = levels[s][2];
        });
      }

      /* ---------- Register ---------- */
      var regForm = document.getElementById("registerForm");
      if (regForm) {
        if (session) location.replace(homeFor(session));
        var pw = document.getElementById("regPassword");
        strengthMeter(pw, document.getElementById("strengthBar"), document.getElementById("strengthText"));

        regForm.addEventListener("submit", function (e) {
          e.preventDefault();
          var alertBox = document.getElementById("registerAlert");
          alertBox.className = "form-alert";
          var name = document.getElementById("regName").value.trim();
          var email = document.getElementById("regEmail").value.trim().toLowerCase();
          var phone = document.getElementById("regPhone").value.trim();
          var brgy = document.getElementById("regBarangay").value;
          var p1 = pw.value, p2 = document.getElementById("regConfirm").value;
          var terms = document.getElementById("regTerms").checked;

          var bad = false;
          bad = setErr("f-name", name.length < 2) || bad;
          bad = setErr("f-email", !validEmail(email)) || bad;
          bad = setErr("f-phone", phone !== "" && !/^[0-9+\-\s()]{7,15}$/.test(phone)) || bad;
          bad = setErr("f-barangay", brgy === "") || bad;
          bad = setErr("f-password", !(p1.length >= 8 && /[A-Za-z]/.test(p1) && /\d/.test(p1))) || bad;
          bad = setErr("f-confirm", p1 !== p2 || p2 === "") || bad;
          bad = setErr("f-terms", !terms) || bad;
          if (bad) { regForm.classList.remove("shake"); void regForm.offsetWidth; regForm.classList.add("shake"); return; }

          var btn = document.getElementById("registerBtn");
          busyBtn(btn, true);
          MDRRMO.post("register.php", { name: name, email: email, phone: phone, barangay: brgy, password: p1, terms: true })
            .then(function (j) {
              showAlert(alertBox, "✅ Account created! Redirecting to the homepage…", "ok");
              setTimeout(function () { location.href = homeFor(j.user); }, 1300);
            })
            .catch(function (err) { busyBtn(btn, false); showAlert(alertBox, err.message, "err"); });
        });
      }

      /* ---------- Social sign-in (Google / Facebook) ----------
         Not connected: it needs a Google OAuth Client ID / Facebook App ID and
         server-side token checks (see README). The server answers with a clear message. */
      var socialModal = document.getElementById("socialModal");
      if (socialModal) {
        var smForm = document.getElementById("socialForm");
        var smBox = document.getElementById("smBox");
        var smAlert = document.getElementById("smAlert");
        var smName = document.getElementById("smName");
        var smEmail = document.getElementById("smEmail");
        var smTitle = document.getElementById("smTitle");
        var smLogo = document.getElementById("smLogo");
        var PROVIDERS = { google: "Google", facebook: "Facebook" };
        var provider = "", lastFocus = null;

        function openSocial(p, btn) {
          provider = p; lastFocus = document.activeElement;
          socialModal.setAttribute("data-provider", p);
          smTitle.textContent = "Continue with " + PROVIDERS[p];
          smLogo.textContent = "";
          var icon = btn.querySelector("svg");
          if (icon) smLogo.appendChild(icon.cloneNode(true));
          smForm.reset(); smAlert.className = "form-alert";
          setErr("f-sname", false); setErr("f-semail", false);
          smEmail.placeholder = p === "google" ? "yourname@gmail.com" : "you@example.com";
          socialModal.classList.add("show");
          socialModal.setAttribute("aria-hidden", "false");
          document.body.style.overflow = "hidden";
          setTimeout(function () { smName.focus(); }, 150);
        }
        function closeSocial() {
          socialModal.classList.remove("show");
          socialModal.setAttribute("aria-hidden", "true");
          document.body.style.overflow = "";
          if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        document.querySelectorAll("[data-social]").forEach(function (b) {
          b.addEventListener("click", function () { openSocial(b.getAttribute("data-social"), b); });
        });
        document.getElementById("smCancel").addEventListener("click", closeSocial);
        socialModal.addEventListener("click", function (e) { if (e.target === socialModal) closeSocial(); });
        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape" && socialModal.classList.contains("show")) closeSocial();
        });

        smForm.addEventListener("submit", function (e) {
          e.preventDefault();
          smAlert.className = "form-alert";
          var name = smName.value.trim();
          var email = smEmail.value.trim().toLowerCase();
          var bad = false;
          bad = setErr("f-sname", name.length < 2) || bad;
          bad = setErr("f-semail", !validEmail(email)) || bad;
          if (bad) { smBox.classList.remove("shake"); void smBox.offsetWidth; smBox.classList.add("shake"); return; }
          MDRRMO.post("social_login.php", { provider: provider, name: name, email: email })
            .then(function (j) { location.href = homeFor(j.user); })
            .catch(function (err) { showAlert(smAlert, err.message, "err"); });
        });
      }

      /* ---------- Forgot password ---------- */
      var forgotForm = document.getElementById("forgotForm");
      if (forgotForm) {
        if (session) location.replace(homeFor(session));

        var resetForm = document.getElementById("resetForm");
        var fAlert = document.getElementById("forgotAlert");
        var rAlert = document.getElementById("resetAlert");
        var step1 = document.getElementById("forgotStep1");
        var step2 = document.getElementById("forgotStep2");
        var fEmailInput = document.getElementById("forgotEmail");
        var codeInput = document.getElementById("resetCode");
        var newPw = document.getElementById("newPassword");
        var newConfirm = document.getElementById("newConfirm");
        var resendBtn = document.getElementById("resendBtn");
        var fBtn = document.getElementById("forgotBtn");
        var rBtn = document.getElementById("resetBtn");
        var resetOverlay = document.getElementById("resetSuccess");
        var demoBox = document.querySelector(".demo-code");
        var resetEmail = "", cooldown = null;

        function shakeEl(el) { el.classList.remove("shake"); void el.offsetWidth; el.classList.add("shake"); }
        function showStep(n) { step1.classList.toggle("active", n === 1); step2.classList.toggle("active", n === 2); }
        function startCooldown(sec) {
          clearInterval(cooldown);
          var left = sec;
          resendBtn.disabled = true;
          function paint() {
            resendBtn.textContent = left > 0 ? "Resend code in " + left + "s" : "Resend code";
            if (left <= 0) { resendBtn.disabled = false; clearInterval(cooldown); }
            left--;
          }
          paint();
          cooldown = setInterval(paint, 1000);
        }
        /* asks the server to create + send a code; resolves with the response */
        function requestCode(email) {
          return MDRRMO.post("forgot_password.php", { email: email }).then(function (j) {
            if (demoBox) {
              /* The code is only shown on the page in dev mode (api/config.php); in prod it is emailed only */
              demoBox.style.display = (j.devCode || j.devNote) ? "" : "none";
              document.getElementById("demoCodeValue").textContent = j.devCode || "------";
              var noteEl = document.getElementById("demoNote");
              if (noteEl) noteEl.textContent = j.devNote || "";
            }
            startCooldown(30);
            return j;
          });
        }

        strengthMeter(newPw, document.getElementById("newStrengthBar"), document.getElementById("newStrengthText"));
        codeInput.addEventListener("input", function () { codeInput.value = codeInput.value.replace(/\D/g, "").slice(0, 6); });

        /* Step 1: request a code */
        forgotForm.addEventListener("submit", function (e) {
          e.preventDefault();
          fAlert.className = "form-alert";
          var email = fEmailInput.value.trim().toLowerCase();
          if (setErr("f-femail", !validEmail(email))) { shakeEl(forgotForm); return; }
          busyBtn(fBtn, true);
          requestCode(email).then(function () {
            busyBtn(fBtn, false);
            resetEmail = email;
            document.getElementById("sentTo").textContent = email;
            showStep(2);
            setTimeout(function () { codeInput.focus({ preventScroll: true }); }, 200);
          }).catch(function (err) {
            busyBtn(fBtn, false);
            showAlert(fAlert, err.message, "err");
            shakeEl(forgotForm);
          });
        });

        /* Step 2: verify code and set the new password */
        resetForm.addEventListener("submit", function (e) {
          e.preventDefault();
          rAlert.className = "form-alert";
          var code = codeInput.value.trim(), p1 = newPw.value, p2 = newConfirm.value, bad = false;
          bad = setErr("f-code", !/^\d{6}$/.test(code)) || bad;
          bad = setErr("f-newpw", !(p1.length >= 8 && /[A-Za-z]/.test(p1) && /\d/.test(p1))) || bad;
          bad = setErr("f-newconfirm", p1 !== p2 || p2 === "") || bad;
          if (bad) { shakeEl(resetForm); return; }
          busyBtn(rBtn, true);
          MDRRMO.post("reset_password.php", { email: resetEmail, code: code, password: p1 }).then(function () {
            resetOverlay.setAttribute("aria-hidden", "false");
            resetOverlay.classList.add("show");
            setTimeout(function () { location.href = INDEX; }, 2200);
          }).catch(function (err) {
            busyBtn(rBtn, false);
            showAlert(rAlert, err.message, "err");
            setErr("f-code", true); shakeEl(resetForm);
          });
        });

        resendBtn.addEventListener("click", function () {
          codeInput.value = ""; setErr("f-code", false);
          requestCode(resetEmail).then(function () {
            showAlert(rAlert, "A new code has been sent to your email.", "ok");
          }).catch(function (err) { showAlert(rAlert, err.message, "err"); });
        });
        document.getElementById("changeEmailBtn").addEventListener("click", function () {
          clearInterval(cooldown); resetForm.reset(); rAlert.className = "form-alert"; showStep(1);
        });
        fEmailInput.addEventListener("input", function () { setErr("f-femail", false); fAlert.className = "form-alert"; });
      }

      /* ---------- Login (with animations) ---------- */
      var loginForm = document.getElementById("loginForm");
      if (loginForm) {
        if (session) location.replace(homeFor(session));

        var loginBtn = document.getElementById("loginBtn");
        var loginAlertBox = document.getElementById("loginAlert");
        var overlay = document.getElementById("loginSuccess");
        var emailInput = document.getElementById("loginEmail");
        var passInput = document.getElementById("loginPassword");
        var capsHint = document.getElementById("capsHint");

        function setLoading(on) {
          loginBtn.classList.toggle("loading", on);
          loginBtn.disabled = on;
          loginBtn.setAttribute("aria-busy", on ? "true" : "false");
        }
        function shakeForm() {
          loginForm.classList.remove("shake"); void loginForm.offsetWidth; loginForm.classList.add("shake");
        }
        function celebrate(name, done) {
          overlay.querySelector(".who").textContent = name;
          overlay.setAttribute("aria-hidden", "false");
          overlay.classList.add("show");
          setTimeout(done, 1800);
        }

        /* clear errors while typing */
        [["loginEmail", "f-email"], ["loginPassword", "f-password"]].forEach(function (p) {
          document.getElementById(p[0]).addEventListener("input", function () {
            var f = document.getElementById(p[1]);
            if (f) f.classList.remove("invalid");
            loginAlertBox.className = "form-alert";
          });
        });

        /* Caps Lock warning */
        function capsCheck(e) {
          if (e.getModifierState) capsHint.classList.toggle("show", e.getModifierState("CapsLock"));
        }
        passInput.addEventListener("keydown", capsCheck);
        passInput.addEventListener("keyup", capsCheck);
        passInput.addEventListener("blur", function () { capsHint.classList.remove("show"); });

        /* Logo beacon follows the mouse slightly (desktop only) */
        var side = document.querySelector(".auth-side");
        var beacon = document.querySelector(".beacon");
        var canHover = window.matchMedia && window.matchMedia("(hover: hover)").matches;
        var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (side && beacon && canHover && !reduce) {
          side.addEventListener("mousemove", function (e) {
            var r = side.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - 0.5;
            var y = (e.clientY - r.top) / r.height - 0.5;
            beacon.style.setProperty("--px", (x * 22).toFixed(1) + "px");
            beacon.style.setProperty("--py", (y * 22).toFixed(1) + "px");
          });
          side.addEventListener("mouseleave", function () {
            beacon.style.setProperty("--px", "0px");
            beacon.style.setProperty("--py", "0px");
          });
          setTimeout(function () { emailInput.focus({ preventScroll: true }); }, 900);
        }

        /* Submit */
        loginForm.addEventListener("submit", function (e) {
          e.preventDefault();
          loginAlertBox.className = "form-alert";
          var email = emailInput.value.trim().toLowerCase();
          var pass = passInput.value;
          var bad = false;
          bad = setErr("f-email", !validEmail(email)) || bad;
          bad = setErr("f-password", pass === "") || bad;
          if (bad) { shakeForm(); return; }

          setLoading(true);
          MDRRMO.post("login.php", { email: email, password: pass, remember: document.getElementById("remember").checked })
            .then(function (j) {
              celebrate(j.user.name.split(" ")[0], function () { location.href = homeFor(j.user); });
            })
            .catch(function (err) {
              setLoading(false);
              if (err.data && err.data.code === "not_registered") {
                /* email detector: the address has no account yet */
                setErr("f-email", true);
                var emsg = document.querySelector("#f-email .error");
                if (emsg) emsg.textContent = "This email is not yet registered.";
                loginAlertBox.innerHTML = "⚠️ This email is not yet registered. <a href=\"register.html\">Create an account</a> to continue.";
                loginAlertBox.className = "form-alert show err";
                shakeForm();
                emailInput.focus();
                return;
              }
              var emsg2 = document.querySelector("#f-email .error");
              if (emsg2) emsg2.textContent = "Please enter a valid email address.";
              showAlert(loginAlertBox, err.message || "Incorrect email or password.", "err");
              shakeForm();
              passInput.select();
            });
        });
      }
    });

    /* ---------- Current year ---------- */
    var yr = document.getElementById("year");
    if (yr) yr.textContent = new Date().getFullYear();
  });
})();

/* =========================================================
   Safety Tips page: hazard tabs + go-bag checklist
   (only runs when those elements exist on the page)
   ========================================================= */
(function () {
  "use strict";
  function init() {
    if (!document.querySelector(".tab-btn") && !document.querySelector(".bag-item")) return;
  (function () {
    /* ---------- Hazard tabs ---------- */
    var tabs = document.querySelectorAll(".tab-btn");
    var panels = document.querySelectorAll(".tab-panel");
    function openTab(name) {
      tabs.forEach(function (b) {
        var on = b.getAttribute("data-tab") === name;
        b.classList.toggle("active", on);
        b.setAttribute("aria-selected", on);
      });
      panels.forEach(function (p) { p.classList.toggle("active", p.id === "tab-" + name); });
    }
    tabs.forEach(function (b) {
      b.addEventListener("click", function () {
        var name = b.getAttribute("data-tab");
        openTab(name);
        if (history.replaceState) history.replaceState(null, "", "#" + name);
      });
    });
    var hash = location.hash.replace("#", "");
    if (document.getElementById("tab-" + hash)) openTab(hash);

    /* ---------- Go-bag checklist (saved in localStorage) ---------- */
    var KEY = "mdrrmo-gobag";
    var boxes = document.querySelectorAll(".bag-item input");
    var bar = document.getElementById("bagBar");
    var count = document.getElementById("bagCount");
    var saved = [];
    try { saved = JSON.parse(localStorage.getItem(KEY) || "[]"); } catch (e) {}

    boxes.forEach(function (box, i) { box.checked = saved.indexOf(i) !== -1; });

    function update(fromServer) {
      var done = [], n = 0;
      boxes.forEach(function (box, i) { if (box.checked) { n++; done.push(i); } });
      count.textContent = n + " / " + boxes.length;
      bar.style.width = (boxes.length ? (n / boxes.length) * 100 : 0) + "%";
      try { localStorage.setItem(KEY, JSON.stringify(done)); } catch (e) {}
      if (signedIn && fromServer !== true) MDRRMO.post("gobag.php", { items: done }).catch(function () {});
    }
    var signedIn = false;
    boxes.forEach(function (box) { box.addEventListener("change", function () { update(); }); });
    update();
    /* signed-in visitors: the checklist is also kept in the database */
    MDRRMO.me().then(function (s) {
      if (!s.user) return;
      signedIn = true;
      return MDRRMO.get("gobag.php").then(function (j) {
        if (j.items.length) { boxes.forEach(function (box, i) { box.checked = j.items.indexOf(i) !== -1; }); update(true); }
        else update();
      });
    }).catch(function () {});

    document.getElementById("resetBag").addEventListener("click", function () {
      boxes.forEach(function (box) { box.checked = false; });
      update();
    });
    document.getElementById("printBtn").addEventListener("click", function () { window.print(); });
  })();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();

/* =========================================================
   News pop-up: shows the full story when "Read more" is clicked
   (only runs on pages that have .news-card elements)
   Edit the DETAILS below – the key must match the card's <h3> title.
   ========================================================= */
(function () {
  "use strict";

  var DETAILS = {
    "Typhoon Preparedness Reminder": {
      paragraphs: [
        "MDRRMO Los Baños reminds all residents to prepare early before a typhoon makes landfall. Being ready ahead of time reduces risk to your family and helps responders focus on those who need help most.",
        "Monitor official bulletins from PAGASA and the MDRRMO, and follow instructions from your barangay officials once warnings are raised."
      ],
      listTitle: "Before the typhoon",
      list: [
        "Know the location of your nearest evacuation center",
        "Prepare a go-bag with water, food, medicines and documents",
        "Charge phones and power banks; keep a flashlight and radio ready",
        "Secure loose objects and check your roof and windows",
        "Agree on a family meeting place and emergency contacts"
      ]
    },
    "Community First Aid & CPR Training": {
      paragraphs: [
        "MDRRMO Los Baños is offering a community training on basic first aid and CPR. The session is open to volunteers, teachers and residents who want to learn skills that can save lives.",
        "Participants will practice hands-on techniques guided by trained responders. Watch this page and our official channels for the schedule and registration details."
      ],
      listTitle: "What you will learn",
      list: [
        "Checking a person's condition and calling for help",
        "Hands-only CPR and use of basic first aid supplies",
        "Controlling bleeding and caring for wounds and burns",
        "Handling choking and common household emergencies"
      ]
    },
    "Fire Prevention at Home": {
      paragraphs: [
        "Most house fires are preventable. A few simple habits can greatly lower the risk in your home, especially in kitchens and around electrical connections.",
        "If a fire starts and cannot be controlled right away, get everyone out, stay out, and call the emergency hotline."
      ],
      listTitle: "Fire safety checklist",
      list: [
        "Check wiring and replace damaged cords and plugs",
        "Never leave cooking unattended",
        "Keep exits and hallways clear of clutter",
        "Do not overload outlets or extension cords",
        "Keep matches, lighters and candles away from children"
      ]
    },
    "Rescue Team Completes Flood Drill": {
      paragraphs: [
        "MDRRMO responders completed a flood drill to sharpen water rescue and evacuation procedures together with partner agencies.",
        "Exercises like this test coordination, communication and equipment so the team is ready to respond quickly when real flooding occurs."
      ],
      listTitle: "Drill focus areas",
      list: [
        "Water rescue techniques and equipment handling",
        "Evacuation procedures for affected families",
        "Coordination with partner agencies and barangays"
      ]
    },
    "Earthquake Drill Schedule": {
      paragraphs: [
        "Residents, schools and offices are encouraged to take part in the quarterly nationwide simultaneous earthquake drill (NSED).",
        "Practicing what to do before the ground shakes helps everyone react calmly and correctly when a real earthquake happens."
      ],
      listTitle: "During an earthquake",
      list: [
        "DROP to your hands and knees",
        "COVER your head and neck under a sturdy table",
        "HOLD ON until the shaking stops",
        "Once it is safe, evacuate to an open area away from buildings"
      ]
    },
    "Barangay DRRM Volunteer Orientation": {
      paragraphs: [
        "MDRRMO invites residents to join the accredited community disaster volunteer program. Volunteers help their barangays prepare for, respond to and recover from emergencies.",
        "An orientation will explain the roles, training opportunities and responsibilities of volunteers."
      ],
      listTitle: "Why volunteer",
      list: [
        "Receive training in disaster preparedness and response",
        "Help protect your family and community",
        "Work alongside MDRRMO and barangay responders"
      ]
    },
    "What to Pack in Your Go-Bag": {
      paragraphs: [
        "A go-bag keeps your most important supplies in one place so you can leave quickly during an evacuation. Keep it where everyone can reach it and check its contents every few months.",
        "Include extra items for infants, seniors, persons with disabilities and pets if needed."
      ],
      listTitle: "Go-bag essentials",
      list: [
        "Drinking water and ready-to-eat food",
        "Flashlight, batteries and a battery-powered radio",
        "Medicines and a first aid kit",
        "Copies of IDs and important documents in a waterproof bag",
        "Whistle, face masks, hygiene items and spare clothes"
      ]
    },
    "Evacuation Centers Ready": {
      paragraphs: [
        "Evacuation sites in Los Baños have been inspected and stocked ahead of the rainy season so families have a safe place to go when needed.",
        "Know which evacuation center serves your barangay and how to get there before an emergency happens."
      ],
      listTitle: "Remember",
      list: [
        "Follow instructions from barangay and MDRRMO personnel",
        "Bring your go-bag, medicines and important documents",
        "Evacuate early when warnings are issued"
      ]
    },
    "Flood Safety: Do's and Don'ts": {
      paragraphs: [
        "Floodwater can be deeper, faster and more contaminated than it looks. Taking the right steps early can protect your family.",
        "Move to higher ground as soon as flooding is expected, and heed evacuation advisories."
      ],
      listTitle: "Do's and Don'ts",
      list: [
        "DO move to higher ground early and bring your go-bag",
        "DO turn off the main electricity if water is rising",
        "DON'T walk or drive through floodwaters",
        "DON'T touch electrical wires or appliances while wet"
      ]
    }
  };

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function norm(s) { return (s || "").replace(/\s+/g, " ").trim(); }

  function init() {
    var cards = document.querySelectorAll(".news-card");
    if (!cards.length) return;

    /* ----- build the modal once ----- */
    var modal = el("div", "news-modal");
    modal.setAttribute("aria-hidden", "true");
    var box = el("div", "nm-box");
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.setAttribute("aria-labelledby", "nmTitle");
    var closeBtn = el("button", "nm-close", "✕");
    closeBtn.type = "button";
    closeBtn.setAttribute("aria-label", "Close news");
    var thumbBox = el("div");
    var content = el("div", "nm-content");
    box.appendChild(closeBtn); box.appendChild(thumbBox); box.appendChild(content);
    modal.appendChild(box);
    document.body.appendChild(modal);

    var lastFocus = null;

    function fill(card) {
      var title = norm(card.querySelector("h3").textContent);
      var info = DETAILS[title] || {};
      var thumb = card.querySelector(".news-thumb");
      var tag = card.querySelector(".tag");
      var date = card.querySelector(".date");
      var summary = card.querySelector(".news-body p");

      thumbBox.textContent = "";
      var t = el("div", "news-thumb nm-thumb" + (thumb && thumb.classList.contains("red") ? " red" : thumb && thumb.classList.contains("yellow") ? " yellow" : ""), thumb ? thumb.textContent : "");
      thumbBox.appendChild(t);

      content.textContent = "";
      var meta = el("div", "nm-meta");
      meta.appendChild(el("span", "tag", tag ? tag.textContent : ""));
      meta.appendChild(el("span", "date", date ? date.textContent : ""));
      content.appendChild(meta);

      var h = el("h2", "", title); h.id = "nmTitle";
      content.appendChild(h);

      var paras = info.paragraphs && info.paragraphs.length ? info.paragraphs : [summary ? summary.textContent : ""];
      paras.forEach(function (p) { content.appendChild(el("p", "", p)); });

      if (info.list && info.list.length) {
        var wrap = el("div", "nm-list");
        wrap.appendChild(el("h4", "", info.listTitle || "Key points"));
        var ul = el("ul");
        info.list.forEach(function (item) { ul.appendChild(el("li", "", item)); });
        wrap.appendChild(ul);
        content.appendChild(wrap);
      }

      var foot = el("div", "nm-foot");
      foot.appendChild(el("small", "", "🚨 For emergencies, call the MDRRMO hotline."));
      var done = el("button", "btn btn-red", "Close");
      done.type = "button";
      done.addEventListener("click", close);
      foot.appendChild(done);
      content.appendChild(foot);
    }

    function open(card, trigger) {
      lastFocus = trigger || document.activeElement;
      fill(card);
      box.scrollTop = 0;
      modal.classList.add("show");
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
      setTimeout(function () { closeBtn.focus(); }, 50);
    }
    function close() {
      modal.classList.remove("show");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    /* ----- wire up the "Read more" links ----- */
    cards.forEach(function (card) {
      var link = card.querySelector(".news-body a");
      if (!link) return;
      /* cards whose "Read more" has a real URL just go to that URL (no pop-up) */
      var href = (link.getAttribute("href") || "").trim();
      if (href && href !== "#") return;
      link.addEventListener("click", function (e) { e.preventDefault(); open(card, link); });
    });

    closeBtn.addEventListener("click", close);
    modal.addEventListener("click", function (e) { if (e.target === modal) close(); });
    document.addEventListener("keydown", function (e) {
      if (!modal.classList.contains("show")) return;
      if (e.key === "Escape") { close(); return; }
      if (e.key === "Tab") {            /* keep keyboard focus inside the pop-up */
        var f = box.querySelectorAll("button, a[href]");
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();

/* =========================================================
   Profile page: view details, edit profile, change password
   (saved in the MySQL database through the PHP API)
   Only runs on pages that have #pf-view.
   ========================================================= */
(function () {
  "use strict";

  function init() {
    if (!document.getElementById("pf-view")) return;

    /* ----- small helpers ----- */
    function $(id) { return document.getElementById(id); }
    function setErr(id, bad) { var f = $(id); if (f) f.classList.toggle("invalid", bad); return bad; }
    function shake(el) { el.classList.remove("shake"); void el.offsetWidth; el.classList.add("shake"); }
    function busy(btn, on) { btn.classList.toggle("loading", on); btn.disabled = on; }
    var alertBox = $("pfAlert");
    function showAlert(msg, type) {
      alertBox.textContent = msg; alertBox.className = "form-alert show " + type;
      alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
    function hideAlert() { alertBox.className = "form-alert"; }
    var PHONE_RE = /^[0-9+\-\s()]{7,15}$/;

    /* ----- must be logged in ----- */
    var user = null;
    MDRRMO.me().then(function (state) {
      if (!state.user) { location.replace("index.html"); return; }
      user = state.user;
      start();
    }, function (err) {
      showAlert(err.message, "err");
    });

    function todayISO() {
      var d = new Date();
      return d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
    }
    function parseISO(iso) {
      var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || "");
      if (!m) return null;
      var d = new Date(+m[1], +m[2] - 1, +m[3]);
      return d.getFullYear() === +m[1] && d.getMonth() === +m[2] - 1 && d.getDate() === +m[3] ? d : null;
    }
    function calcAge(iso) {
      var b = parseISO(iso); if (!b) return null;
      var t = new Date(), age = t.getFullYear() - b.getFullYear();
      if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) age--;
      return age;
    }
    function fmtDate(d) { return d.toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" }); }

    function start() {
      /* ----- render the "My Details" view ----- */
      function setView(key, text) {
        var dd = document.querySelector('[data-view="' + key + '"]');
        if (!dd) return;
        dd.textContent = text || "Not set";
        dd.classList.toggle("empty", !text);
      }
      function render() {
        var parts = (user.name || "").trim().split(/\s+/);
        var initials = (parts[0] ? parts[0][0] : "?") + (parts.length > 1 ? parts[parts.length - 1][0] : "");
        $("pfAvatar").textContent = initials.toUpperCase();
        $("pfSideName").textContent = user.name || "";
        $("pfSideEmail").textContent = user.email || "";
        $("pfBadge").textContent = user.barangay ? "📍 Brgy. " + user.barangay : "Community Member";

        var b = parseISO(user.birthday), age = calcAge(user.birthday);
        var created = user.created ? new Date(user.created) : null;
        setView("name", user.name);
        setView("email", user.email);
        setView("birthday", b ? fmtDate(b) : "");
        setView("age", age !== null && age >= 0 ? age + " years old" : "");
        setView("sex", user.sex);
        setView("blood", user.blood);
        setView("phone", user.phone);
        setView("barangay", user.barangay);
        setView("address", user.address);
        setView("ecName", user.ecName);
        setView("ecPhone", user.ecPhone);
        setView("created", created && !isNaN(created) ? fmtDate(created) : "");
      }

      /* ----- panels ----- */
      var panels = document.querySelectorAll(".pf-panel");
      var menuBtns = document.querySelectorAll(".pf-menu button");
      var NAMES = ["view", "edit", "password"];
      function showPanel(name, keepAlert) {
        if (NAMES.indexOf(name) === -1) name = "view";
        panels.forEach(function (p) { p.classList.toggle("active", p.id === "pf-" + name); });
        menuBtns.forEach(function (b) {
          var on = b.getAttribute("data-panel") === name;
          b.classList.toggle("active", on);
          if (on) b.setAttribute("aria-current", "page"); else b.removeAttribute("aria-current");
        });
        if (!keepAlert) hideAlert();
        if (history.replaceState) history.replaceState(null, "", name === "view" ? location.pathname : "#" + name);
      }
      document.querySelectorAll("[data-panel]").forEach(function (b) {
        b.addEventListener("click", function () {
          var name = b.getAttribute("data-panel");
          if (name === "edit") fillForm();
          showPanel(name);
        });
      });

      /* ----- edit form ----- */
      var editForm = $("pf-edit");
      var inBirthday = $("pf-in-birthday");
      inBirthday.max = todayISO();

      function fillForm() {
        $("pf-in-name").value = user.name || "";
        $("pf-in-email").value = user.email || "";
        inBirthday.value = user.birthday || "";
        $("pf-in-sex").value = user.sex || "";
        $("pf-in-blood").value = user.blood || "";
        $("pf-in-phone").value = user.phone || "";
        $("pf-in-barangay").value = user.barangay || "";
        $("pf-in-address").value = user.address || "";
        $("pf-in-ecName").value = user.ecName || "";
        $("pf-in-ecPhone").value = user.ecPhone || "";
        ["f-pf-name", "f-pf-birthday", "f-pf-phone", "f-pf-barangay", "f-pf-ecPhone"].forEach(function (id) { setErr(id, false); });
        updateAge();
      }
      function updateAge() {
        var a = calcAge(inBirthday.value);
        $("pf-in-age").value = a !== null && a >= 0 ? a + " years old" : "";
      }
      inBirthday.addEventListener("input", updateAge);

      $("pfCancelEdit").addEventListener("click", function () { fillForm(); showPanel("view"); });

      editForm.addEventListener("submit", function (e) {
        e.preventDefault(); hideAlert();
        var name = $("pf-in-name").value.trim().replace(/\s+/g, " ");
        var birthday = inBirthday.value;
        var phone = $("pf-in-phone").value.trim();
        var brgy = $("pf-in-barangay").value;
        var ecPhone = $("pf-in-ecPhone").value.trim();

        var bad = false;
        bad = setErr("f-pf-name", name.length < 2) || bad;
        var bd = parseISO(birthday), age = calcAge(birthday);
        bad = setErr("f-pf-birthday", birthday !== "" && (!bd || age < 0 || age > 120)) || bad;
        bad = setErr("f-pf-phone", phone !== "" && !PHONE_RE.test(phone)) || bad;
        bad = setErr("f-pf-barangay", brgy === "") || bad;
        bad = setErr("f-pf-ecPhone", ecPhone !== "" && !PHONE_RE.test(ecPhone)) || bad;
        if (bad) { shake(editForm); return; }

        var btn = $("pfSaveBtn");
        busy(btn, true);
        MDRRMO.post("profile.php", {
          name: name, birthday: birthday, sex: $("pf-in-sex").value, blood: $("pf-in-blood").value,
          phone: phone, barangay: brgy, address: $("pf-in-address").value.trim(),
          ecName: $("pf-in-ecName").value.trim(), ecPhone: ecPhone
        }).then(function (j) {
          user = j.user;
          var authBtn = $("authBtn");
          if (authBtn && !authBtn.hasAttribute("data-profile")) authBtn.textContent = "👤 " + user.name.split(" ")[0] + " • Log out";
          render();
          showPanel("view", true);
          showAlert("✅ Profile updated successfully.", "ok");
        }).catch(function (err) {
          showAlert(err.message || "Sorry, your changes could not be saved. Please try again.", "err");
        }).then(function () { busy(btn, false); });
      });

      /* ----- change password ----- */
      var pwForm = $("pf-password");
      var cur = $("pw-current"), np = $("pw-new"), cf = $("pw-confirm");
      $("pwFields").hidden = false;
      $("pfNoPw").classList.remove("show");

      np.addEventListener("input", function () {
        var v = np.value, s = 0;
        if (v.length >= 8) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) s++;
        var L = [["0%", "transparent", "Use 8+ characters with letters and numbers."], ["25%", "#d62828", "Weak"], ["50%", "#f59e0b", "Fair"], ["75%", "#1d4ed8", "Good"], ["100%", "#1faa59", "Strong"]];
        if (!v) s = 0;
        $("pwBar").style.width = L[s][0]; $("pwBar").style.background = L[s][1]; $("pwText").textContent = L[s][2];
      });
      function resetPwForm() {
        pwForm.reset();
        $("pwBar").style.width = "0%"; $("pwText").textContent = "Use 8+ characters with letters and numbers.";
        ["f-pw-cur", "f-pw-new", "f-pw-confirm"].forEach(function (id) { setErr(id, false); });
        document.querySelectorAll("#pf-password .pw-toggle").forEach(function (b) {
          $(b.getAttribute("data-target")).type = "password"; b.textContent = "👁️";
        });
      }
      $("pfCancelPw").addEventListener("click", function () { resetPwForm(); showPanel("view"); });

      pwForm.addEventListener("submit", function (e) {
        e.preventDefault(); hideAlert();
        var c = cur.value, p1 = np.value, p2 = cf.value, bad = false;
        bad = setErr("f-pw-cur", c === "") || bad;
        bad = setErr("f-pw-new", !(p1.length >= 8 && /[A-Za-z]/.test(p1) && /\d/.test(p1))) || bad;
        bad = setErr("f-pw-confirm", p1 !== p2 || p2 === "") || bad;
        if (bad) { shake(pwForm); return; }
        if (p1 === c) { setErr("f-pw-new", true); showAlert("Your new password must be different from your current password.", "err"); shake(pwForm); return; }

        var btn = $("pwSaveBtn");
        busy(btn, true);
        MDRRMO.post("change_password.php", { current: c, password: p1 }).then(function () {
          resetPwForm(); showPanel("view", true);
          showAlert("✅ Password changed successfully. Use your new password the next time you log in.", "ok");
        }).catch(function (err) {
          if (err.status === 403) { setErr("f-pw-cur", true); cur.select(); }
          showAlert(err.message || "Sorry, your password could not be changed. Please try again.", "err");
          shake(pwForm);
        }).then(function () { busy(btn, false); });
      });

      [["pf-in-name", "f-pf-name"], ["pf-in-phone", "f-pf-phone"], ["pf-in-barangay", "f-pf-barangay"], ["pf-in-ecPhone", "f-pf-ecPhone"],
       ["pw-current", "f-pw-cur"], ["pw-new", "f-pw-new"], ["pw-confirm", "f-pw-confirm"]].forEach(function (p) {
        $(p[0]).addEventListener("input", function () { setErr(p[1], false); });
      });

      /* ----- log out ----- */
      $("pfLogout").addEventListener("click", function () {
        MDRRMO.post("logout.php").catch(function () {}).then(function () { location.href = "home.html"; });
      });

      /* ----- start ----- */
      render(); fillForm();
      var startPanel = location.hash.replace("#", "");
      showPanel(NAMES.indexOf(startPanel) !== -1 ? startPanel : "view");
    }
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
