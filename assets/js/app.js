(function () {
  var deferredInstall = window.__deferredInstall || null;
  var waitingForInstall = false;

  window.addEventListener("beforeinstallprompt", function (event) {
    event.preventDefault();
    var help = document.querySelector("#install-help");
    if (help) help.hidden = true;
    if (waitingForInstall) {
      waitingForInstall = false;
      runPrompt(event);
      return;
    }
    deferredInstall = event;
    window.__deferredInstall = event;
    revealInstall();
  });

  window.addEventListener("appinstalled", function () {
    deferredInstall = null;
    var offer = document.querySelector("#install-offer");
    if (offer) offer.hidden = true;
    document.querySelectorAll("[data-install-app]").forEach(markInstalled);
  });

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  ready(function () {
    setupPasswordToggles();
    setupFilters();
    setupConfirms();
    setupDoseRows();
    setupValidation();
    setupInstall();
    registerWorker();
    if (document.body.dataset.notifyUrl) {
      setupNotifications(document.body.dataset.notifyUrl);
    }
  });

  function setupPasswordToggles() {
    document.querySelectorAll("[data-show-password]").forEach(function (button) {
      button.addEventListener("click", function () {
        var input = document.getElementById(button.getAttribute("data-show-password"));
        if (!input) return;
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        button.textContent = show ? "Hide" : "Show";
      });
    });
  }

  function setupFilters() {
    document.querySelectorAll("[data-filter]").forEach(function (input) {
      var list = document.querySelector(input.getAttribute("data-filter"));
      if (!list) return;
      input.addEventListener("input", function () {
        var query = input.value.trim().toLowerCase();
        list.querySelectorAll("[data-search]").forEach(function (item) {
          var hay = (item.getAttribute("data-search") || "").toLowerCase();
          item.hidden = query !== "" && hay.indexOf(query) === -1;
        });
      });
    });
  }

  function setupConfirms() {
    document.addEventListener("submit", function (event) {
      var form = event.target;
      if (!form || !form.getAttribute) return;
      var message = form.getAttribute("data-confirm");
      if (message && !window.confirm(message)) {
        event.preventDefault();
      }
    });
  }

  function setupDoseRows() {
    var list = document.querySelector("#dose-list");
    var template = document.querySelector("#dose-template");
    var add = document.querySelector("#add-dose");
    if (!list || !template || !add) return;

    add.addEventListener("click", function () {
      var count = list.querySelectorAll(".dose-row").length;
      if (count >= 8) return;
      var html = template.innerHTML.replace(/__INDEX__/g, String(count));
      var holder = document.createElement("div");
      holder.innerHTML = html.trim();
      if (holder.firstElementChild) {
        list.appendChild(holder.firstElementChild);
      }
      refreshRemoveButtons(list);
    });

    list.addEventListener("click", function (event) {
      var button = event.target.closest("[data-remove-dose]");
      if (!button) return;
      if (list.querySelectorAll(".dose-row").length <= 1) return;
      var row = button.closest(".dose-row");
      if (row) row.remove();
      reindex(list);
      refreshRemoveButtons(list);
    });

    refreshRemoveButtons(list);
  }

  function reindex(list) {
    list.querySelectorAll(".dose-row").forEach(function (row, index) {
      row.querySelectorAll("input, select, textarea").forEach(function (input) {
        if (input.name) {
          input.name = input.name.replace(/doses\[[^\]]+\]/, "doses[" + index + "]");
        }
      });
    });
  }

  function refreshRemoveButtons(list) {
    var rows = list.querySelectorAll(".dose-row");
    rows.forEach(function (row) {
      var button = row.querySelector("[data-remove-dose]");
      if (button) button.disabled = rows.length <= 1;
    });
  }

  function setupValidation() {
    document.querySelectorAll("form[data-validate]").forEach(function (form) {
      form.addEventListener("submit", function (event) {
        var errors = validate(form);
        clearErrors(form);
        var names = Object.keys(errors);
        if (!names.length) return;
        event.preventDefault();
        names.forEach(function (name) {
          var slot = form.querySelector('[data-error-for="' + name + '"]');
          var field = form.querySelector('[name="' + name + '"]');
          if (slot) slot.textContent = errors[name];
          if (field) field.classList.add("is-invalid");
        });
        var first = form.querySelector(".is-invalid");
        if (first) first.focus();
      });
    });
  }

  function clearErrors(form) {
    form.querySelectorAll(".field-error").forEach(function (slot) {
      slot.textContent = "";
    });
    form.querySelectorAll(".is-invalid").forEach(function (field) {
      field.classList.remove("is-invalid");
    });
  }

  function validate(form) {
    var kind = form.getAttribute("data-validate");
    if (kind === "register" || kind === "profile") return validateIdentity(form, kind === "profile");
    if (kind === "password") return validatePassword(form, true);
    if (kind === "medicine") return validateMedicine(form);
    return {};
  }

  function validateIdentity(form, profile) {
    var errors = {};
    var name = form.full_name.value.trim().replace(/\s+/g, " ");
    if (!/^[A-Za-z]+(?: [A-Za-z]+)*$/.test(name)) {
      errors.full_name = "Name cannot contain numbers or special characters.";
    }
    if (!profile) {
      var username = form.username.value.trim();
      if (/\s/.test(username) || !/^[A-Za-z][A-Za-z0-9._-]*$/.test(username)) {
        errors.username = "Username cannot contain spaces, and it cannot start with a number or a special character.";
      }
    }
    var email = form.email.value.trim();
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
      errors.email = "Enter a valid email address.";
    }
    var digits = form.phone.value.replace(/\D/g, "");
    if (!/^[0-9+\s().-]+$/.test(form.phone.value.trim()) || digits.length < 10 || digits.length > 15) {
      errors.phone = "Enter a valid phone number (10 to 15 digits).";
    }
    if (!profile) {
      Object.assign(errors, validatePassword(form, true));
    }
    return errors;
  }

  function validatePassword(form, withCurrent) {
    var errors = {};
    if (withCurrent && form.current_password && form.current_password.value === "") {
      errors.current_password = "Enter your current password.";
    }
    var password = form.password.value;
    if (password.trim() === "" || password.length < 6 || password.length > 72) {
      errors.password = "Password must be at least 6 characters.";
    }
    if (form.password_confirm && form.password.value !== form.password_confirm.value) {
      errors.password_confirm = "Passwords do not match.";
    }
    return errors;
  }

  function validateMedicine(form) {
    var errors = {};
    var rows = form.querySelectorAll(".dose-row");
    if (!rows.length) {
      errors.doses = "Add at least one dose time.";
      return errors;
    }
    rows.forEach(function (row) {
      if (!row.querySelector('input[type="checkbox"]:checked')) {
        errors.doses = "Choose at least one day for every dose time.";
      }
    });
    return errors;
  }

  var audioCtx = null;
  var sirenTimer = null;
  var vibrateTimer = null;
  var notifyPulse = null;
  var wakeLock = null;
  var originalTitle = null;
  var ringingKeys = "";
  var currentDue = [];
  var stopRequested = false;
  var scheduledAlarms = {};
  var alarmUrl = "";

  function setupNotifications(url) {
    alarmUrl = url;
    document.addEventListener("pointerdown", unlockAudio);
    document.addEventListener("keydown", unlockAudio);
    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "visible") arm(url);
    });
    window.addEventListener("focus", function () { arm(url); });
    if ("serviceWorker" in window.navigator) {
      window.navigator.serviceWorker.addEventListener("message", function (event) {
        if (!event.data) return;
        if (event.data.type === "stop-alarm") {
          stopRequested = true;
          stopCurrent();
        }
      });
    }
    var status = document.querySelector("#notify-status");
    var button = document.querySelector("#notify-toggle");
    if (status && button) {
      if (!("Notification" in window)) {
        status.textContent = "This browser will sound an alarm on the page while Medicine Reminder is open. It works whether the app is installed or open in the browser.";
        button.hidden = true;
      } else {
        renderPermission(status, button);
        button.addEventListener("click", function () {
          unlockAudio();
          Notification.requestPermission().then(function () {
            renderPermission(status, button);
            arm(url);
          });
        });
      }
    }
    arm(url);
    window.setInterval(function () { arm(url); }, 15000);
  }

  function renderPermission(status, button) {
    if (Notification.permission === "granted") {
      status.textContent = "Alarms are on. A sound and a notification play when a dose is due, in the browser and in the installed app. Keep a window open so it can ring.";
      button.hidden = true;
    } else if (Notification.permission === "denied") {
      status.textContent = "Notifications are blocked, so the phone will not buzz in the background. An alarm still sounds on this page while the app is open, installed or not.";
      button.hidden = true;
    } else {
      status.textContent = "Turn on an alarm so a sound and a notification play when it is time to take a medicine. This works in the browser and in the installed app.";
      button.hidden = false;
    }
  }

  function installedAlready() {
    return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
  }

  function isIos() {
    var ua = window.navigator.userAgent || "";
    return /iphone|ipad|ipod/i.test(ua) || (window.navigator.platform === "MacIntel" && window.navigator.maxTouchPoints > 1);
  }

  function isPhone() {
    var ua = window.navigator.userAgent || "";
    if (isIos() || /android|mobile/i.test(ua)) return true;
    return window.matchMedia("(max-width: 760px)").matches;
  }

  function setupInstall() {
    document.querySelectorAll("[data-install-app]").forEach(function (button) {
      if (installedAlready()) {
        markInstalled(button);
        return;
      }
      button.addEventListener("click", onInstallClick);
    });

    var help = document.querySelector("#install-help");
    if (help) {
      help.addEventListener("click", function (event) {
        if (event.target === help || event.target.closest("[data-close-install]")) {
          help.hidden = true;
        }
      });
    }
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && help && !help.hidden) help.hidden = true;
    });

    if (window.__deferredInstall) deferredInstall = window.__deferredInstall;
    revealInstall();
  }

  function runPrompt(promptEvent) {
    deferredInstall = null;
    window.__deferredInstall = null;
    var pending = promptEvent.prompt();
    if (pending && pending.catch) pending.catch(function () {});
    promptEvent.userChoice.then(function (choice) {
      if (!choice || choice.outcome !== "accepted") return;
      var offer = document.querySelector("#install-offer");
      if (offer) offer.hidden = true;
    });
  }

  function onInstallClick() {
    var promptEvent = deferredInstall || window.__deferredInstall;
    if (promptEvent) {
      runPrompt(promptEvent);
      return;
    }
    if (isIos()) {
      openInstallHelp();
      return;
    }
    waitingForInstall = true;
  }

  function revealInstall() {
    if (installedAlready()) return;
    if (window.sessionStorage.getItem("install-dismissed")) return;
    if (document.querySelector("#install-offer")) return;
    var phone = isPhone();
    if (!phone && !deferredInstall) return;
    if (!phone && document.querySelector("[data-install-app]")) return;
    var host = document.querySelector("#content") || document.querySelector("main");
    if (!host) return;
    var bar = document.createElement("div");
    bar.className = "install-offer";
    bar.id = "install-offer";
    bar.innerHTML = '<div><strong>Install the app</strong><p>Add it to this phone so it opens on its own.</p></div><div class="install-offer-actions"><button class="btn-quiet" type="button" data-dismiss-install>Not now</button><button class="btn btn-primary btn-small" type="button" data-install-app>Install</button></div>';
    bar.querySelector("[data-install-app]").addEventListener("click", onInstallClick);
    bar.querySelector("[data-dismiss-install]").addEventListener("click", function () {
      window.sessionStorage.setItem("install-dismissed", "1");
      bar.hidden = true;
    });
    host.insertBefore(bar, host.firstChild);
  }

  function markInstalled(button) {
    var title = button.querySelector("strong");
    var hint = button.querySelector(".hint");
    var side = button.querySelector("[data-install-label]");
    if (title) title.textContent = "App installed";
    if (hint) hint.textContent = "Already on this phone or computer.";
    if (side) side.textContent = "Done";
    if (!title) button.textContent = "App installed";
    button.disabled = true;
  }

  function openInstallHelp() {
    var help = document.querySelector("#install-help");
    var list = document.querySelector("#install-help-steps");
    if (!help || !list) return;
    var ios = isIos();
    var android = /android/i.test(window.navigator.userAgent);
    var steps;
    if (ios) {
      steps = [
        "Open this page in Safari.",
        "Tap the Share button.",
        "Tap Add to Home Screen, then Add."
      ];
    } else if (android) {
      steps = [
        "Open the browser menu (the three dots).",
        "Tap Install app or Add to Home screen.",
        "Confirm to place Medicine Reminder on your phone."
      ];
    } else {
      steps = [
        "In Chrome or Edge, open the browser menu.",
        "Choose Install Medicine Reminder. You can also use the install icon in the address bar.",
        "Confirm. The app opens in its own window with the bottom menu."
      ];
    }
    list.innerHTML = "";
    steps.forEach(function (step) {
      var item = document.createElement("li");
      item.textContent = step;
      list.appendChild(item);
    });
    help.hidden = false;
    var close = help.querySelector("[data-close-install]");
    if (close) close.focus();
  }

  function registerWorker() {
    if (!("serviceWorker" in window.navigator)) return;
    var base = document.body.dataset.base || "";
    var script = base + "/sw.js";
    var scope = base === "" ? "/" : base + "/";
    window.navigator.serviceWorker.register(script, { scope: scope }).catch(function () {});
  }

  function unlockAudio() {
    var ctx = audioContext();
    if (!ctx) return;
    var pending = ctx.state === "suspended" ? ctx.resume() : Promise.resolve();
    pending.then(function () {
      if (!ringingKeys || ctx.state !== "running") return;
      var hear = document.querySelector("#dose-alarm-hear");
      if (hear) hear.hidden = true;
    }).catch(function () {});
  }

  function audioContext() {
    var Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    if (!audioCtx) audioCtx = new Ctx();
    return audioCtx;
  }

  function beep(ctx, freq, when, duration) {
    var osc = ctx.createOscillator();
    var gain = ctx.createGain();
    osc.type = "square";
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, when);
    gain.gain.exponentialRampToValueAtTime(0.08, when + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, when + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(when);
    osc.stop(when + duration + 0.02);
  }

  function startSiren() {
    if (sirenTimer) return;
    var ctx = audioContext();
    if (!ctx) return;
    if (ctx.state === "suspended") ctx.resume();
    function pulse() {
      try {
        if (!ctx || ctx.state === "suspended") ctx.resume();
        if (!ctx || ctx.state !== "running") return;
        var t = ctx.currentTime;
        beep(ctx, 880, t, 0.16);
        beep(ctx, 1175, t + 0.22, 0.2);
      } catch (e) {}
    }
    pulse();
    sirenTimer = window.setInterval(pulse, 1100);
  }

  function stopSiren() {
    if (!sirenTimer) return;
    window.clearInterval(sirenTimer);
    sirenTimer = null;
  }

  function startVibrate() {
    if (!window.navigator.vibrate || vibrateTimer) return;
    function buzz() { window.navigator.vibrate([400, 150, 400, 150, 700]); }
    buzz();
    vibrateTimer = window.setInterval(buzz, 2200);
  }

  function stopVibrate() {
    if (vibrateTimer) {
      window.clearInterval(vibrateTimer);
      vibrateTimer = null;
    }
    if (window.navigator.vibrate) window.navigator.vibrate(0);
  }

  function startTitle() {
    if (originalTitle === null) originalTitle = document.title;
    document.title = "Time for medicine · Medicine Reminder";
  }

  function stopTitle() {
    if (originalTitle === null) return;
    document.title = originalTitle;
    originalTitle = null;
  }

  function holdWake() {
    if (wakeLock || !window.navigator.wakeLock) return;
    window.navigator.wakeLock.request("screen").then(function (lock) {
      wakeLock = lock;
      lock.addEventListener("release", function () {
        if (wakeLock === lock) wakeLock = null;
      });
    }).catch(function () {});
  }

  function releaseWake() {
    if (!wakeLock) return;
    var lock = wakeLock;
    wakeLock = null;
    lock.release().catch(function () {});
  }

  function doseKey(item) {
    return item.id + "-" + item.date;
  }

  function stoppedKey(item) {
    return "alarm-off-" + doseKey(item);
  }

  function rememberStop(item) {
    try { window.localStorage.setItem(stoppedKey(item), "1"); } catch (e) {}
  }

  function isStopped(item) {
    try { return window.localStorage.getItem(stoppedKey(item)) === "1"; } catch (e) { return false; }
  }

  function pruneStops(today) {
    try {
      var index;
      for (index = window.localStorage.length - 1; index >= 0; index--) {
        var key = window.localStorage.key(index);
        if (key && key.indexOf("alarm-off-") === 0 && key.indexOf(today) === -1) {
          window.localStorage.removeItem(key);
        }
      }
    } catch (e) {}
  }

  function remindersUrl(data) {
    if (data && data.reminders) return data.reminders;
    var base = document.body.dataset.base || "";
    return base + "/reminders.php";
  }

  function raiseNotification(title, body, targetUrl) {
    if (!("Notification" in window) || Notification.permission !== "granted") return;
    var icon = (document.body.dataset.base || "") + "/assets/icons/icon-192.png";
    var options = {
      body: body,
      tag: "medicine-alarm",
      renotify: true,
      requireInteraction: true,
      vibrate: [500, 200, 500, 200, 800],
      silent: false,
      icon: icon,
      badge: icon,
      data: { url: targetUrl },
      actions: [
        { action: "stop", title: "Stop alarm" },
        { action: "open", title: "Open" }
      ]
    };
    function show(reg) {
      return reg.showNotification(title, options).catch(function () {
        var plain = {
          body: body,
          tag: "medicine-alarm",
          icon: icon,
          data: { url: targetUrl }
        };
        return reg.showNotification(title, plain);
      });
    }
    if ("serviceWorker" in window.navigator) {
      window.navigator.serviceWorker.ready.then(show).catch(function () {});
      return;
    }
    try { new Notification(title, { body: body, tag: "medicine-alarm", icon: icon }); } catch (e) {}
  }

  function closeAlarmNotification() {
    if (!("serviceWorker" in window.navigator)) return;
    var reg = window.navigator.serviceWorker.controller;
    if (reg) reg.postMessage({ type: "clear-alarm" });
    window.navigator.serviceWorker.ready.then(function (ready) {
      if (!ready.getNotifications) return null;
      return ready.getNotifications({ tag: "medicine-alarm" });
    }).then(function (notes) {
      if (!notes) return;
      notes.forEach(function (note) { note.close(); });
    }).catch(function () {});
  }

  function ensureOverlay(due, targetUrl) {
    var overlay = document.querySelector("#dose-alarm");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "dose-alarm";
      overlay.className = "dose-alarm";
      overlay.setAttribute("role", "alertdialog");
      overlay.setAttribute("aria-modal", "true");
      overlay.setAttribute("aria-labelledby", "dose-alarm-title");
      overlay.innerHTML = '<div class="dose-alarm-card"><p class="dose-alarm-kicker">Alarm</p><h2 id="dose-alarm-title">Time to take your medicine</h2><ul id="dose-alarm-list"></ul><p class="hint" id="dose-alarm-hear">Tap anywhere if you do not hear the alarm yet.</p><button class="btn btn-primary" type="button" id="dose-alarm-stop">Stop alarm</button><a class="btn btn-secondary" id="dose-alarm-open" href="">Open reminders</a></div>';
      document.body.appendChild(overlay);
      overlay.querySelector("#dose-alarm-stop").addEventListener("click", function () {
        unlockAudio();
        stopCurrent();
      });
    }
    var list = overlay.querySelector("#dose-alarm-list");
    list.innerHTML = "";
    due.forEach(function (item) {
      var li = document.createElement("li");
      li.textContent = item.time + " · " + item.name + " · " + item.dosage + " · " + item.quantity;
      list.appendChild(li);
    });
    var open = overlay.querySelector("#dose-alarm-open");
    open.href = targetUrl;
    open.hidden = window.location.pathname.indexOf("reminders.php") !== -1;
    var hear = overlay.querySelector("#dose-alarm-hear");
    if (hear) hear.hidden = !!(audioCtx && audioCtx.state === "running");
    overlay.hidden = false;
  }

  function hideOverlay() {
    var overlay = document.querySelector("#dose-alarm");
    if (overlay) overlay.hidden = true;
  }

  function quiet() {
    ringingKeys = "";
    currentDue = [];
    stopSiren();
    stopVibrate();
    stopTitle();
    releaseWake();
    hideOverlay();
    if (notifyPulse) {
      window.clearInterval(notifyPulse);
      notifyPulse = null;
    }
    closeAlarmNotification();
  }

  function stopCurrent() {
    currentDue.forEach(rememberStop);
    quiet();
  }

  function ring(due, targetUrl) {
    if (stopRequested) {
      due.forEach(rememberStop);
      stopRequested = false;
      quiet();
      return;
    }
    var signature = due.map(doseKey).join("|");
    var fresh = signature !== ringingKeys;
    ringingKeys = signature;
    currentDue = due;
    ensureOverlay(due, targetUrl);
    startSiren();
    startVibrate();
    startTitle();
    holdWake();
    var title = due.length === 1 ? due[0].name : "Time for your medicine";
    var body = due.map(function (item) {
      return item.time + " " + item.name + " (" + item.dosage + ")";
    }).join(", ");
    if (fresh) {
      raiseNotification(title, body, targetUrl);
      if (notifyPulse) window.clearInterval(notifyPulse);
      notifyPulse = window.setInterval(function () {
        if (!ringingKeys) return;
        raiseNotification(title, body, targetUrl);
      }, 30000);
    }
  }

  function scheduleUpcoming(items, url, now) {
    items.forEach(function (item) {
      if (item.status !== "scheduled" || !item.at) return;
      var key = doseKey(item);
      if (scheduledAlarms[key]) return;
      var delay = (item.at - now) * 1000;
      if (delay < 0 || delay > 18 * 60 * 60 * 1000) return;
      scheduledAlarms[key] = window.setTimeout(function () {
        delete scheduledAlarms[key];
        arm(url);
      }, delay);
    });
  }

  function arm(url) {
    window.fetch(url, { credentials: "same-origin", headers: { Accept: "application/json" } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.items) return;
        if (data.today) pruneStops(data.today);
        var target = remindersUrl(data);
        scheduleUpcoming(data.items, url, data.now || 0);
        var due = data.items.filter(function (item) {
          return item.status === "due" && !isStopped(item);
        });
        if (!due.length) {
          if (stopRequested) stopRequested = false;
          quiet();
          return;
        }
        ring(due, target);
      })
      .catch(function () {});
  }
})();
