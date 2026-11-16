(function () {
  var deferredInstall = null;

  window.addEventListener("beforeinstallprompt", function (event) {
    event.preventDefault();
    deferredInstall = event;
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

  function setupNotifications(url) {
    var box = document.querySelector("#notify-box");
    var status = document.querySelector("#notify-status");
    var button = document.querySelector("#notify-toggle");
    if (!box || !status || !button) {
      if ("Notification" in window && Notification.permission === "granted") {
        poll(url);
        window.setInterval(function () { poll(url); }, 30000);
      }
      return;
    }
    if (!("Notification" in window)) {
      status.textContent = "This browser does not support notifications. Your due doses still appear in the list.";
      button.hidden = true;
      return;
    }
    renderPermission(status, button);
    button.addEventListener("click", function () {
      Notification.requestPermission().then(function () {
        renderPermission(status, button);
        if (Notification.permission === "granted") poll(url);
      });
    });
    if (Notification.permission === "granted") {
      poll(url);
      window.setInterval(function () { poll(url); }, 30000);
    }
  }

  function renderPermission(status, button) {
    if (Notification.permission === "granted") {
      status.textContent = "Browser reminders are on while this app is open.";
      button.hidden = true;
    } else if (Notification.permission === "denied") {
      status.textContent = "Notifications are blocked in this browser. You can still see due doses in the list.";
      button.hidden = true;
    } else {
      status.textContent = "Turn on a browser reminder for doses that are due now.";
      button.hidden = false;
    }
  }

  function installedAlready() {
    return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
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

    if (deferredInstall) revealInstall();
  }

  function onInstallClick() {
    if (deferredInstall) {
      var promptEvent = deferredInstall;
      deferredInstall = null;
      promptEvent.prompt();
      promptEvent.userChoice.then(function () {
        var offer = document.querySelector("#install-offer");
        if (offer) offer.hidden = true;
      });
      return;
    }
    openInstallHelp();
  }

  function revealInstall() {
    if (installedAlready() || !deferredInstall) return;
    if (window.sessionStorage.getItem("install-dismissed")) return;
    if (document.querySelector("[data-install-app]")) return;
    if (document.querySelector("#install-offer")) return;
    var host = document.querySelector("#content") || document.querySelector("main");
    if (!host) return;
    var bar = document.createElement("div");
    bar.className = "install-offer";
    bar.id = "install-offer";
    bar.innerHTML = '<div><strong>Install the app</strong><p>Add it to this phone or computer.</p></div><button class="btn btn-primary btn-small" type="button" data-install-app>Install</button>';
    var button = bar.querySelector("[data-install-app]");
    button.addEventListener("click", onInstallClick);
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
    var ios = /iphone|ipad|ipod/i.test(window.navigator.userAgent) || (window.navigator.platform === "MacIntel" && window.navigator.maxTouchPoints > 1);
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

  function poll(url) {
    window.fetch(url, { credentials: "same-origin", headers: { Accept: "application/json" } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.due) return;
        var shown = 0;
        data.due.forEach(function (item) {
          if (shown >= 3) return;
          var key = "noted-" + item.id + "-" + item.date;
          if (window.sessionStorage.getItem(key)) return;
          window.sessionStorage.setItem(key, "1");
          shown += 1;
          var note = new Notification(item.name, {
            body: item.time + " · " + item.dosage + " · " + item.quantity,
            tag: key
          });
          note.onclick = function () { window.focus(); };
        });
      })
      .catch(function () {});
  }
})();
