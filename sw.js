var CACHE = "medicine-reminder-v2";

self.addEventListener("install", function (event) {
  event.waitUntil(
    caches.open(CACHE).then(function (cache) {
      return cache.addAll([
        new URL("offline.html", self.location).href,
        new URL("assets/favicon.svg", self.location).href,
        new URL("assets/icons/icon-192.png", self.location).href,
        new URL("assets/icons/icon-512.png", self.location).href
      ]);
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener("activate", function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (key) {
        return key !== CACHE;
      }).map(function (key) {
        return caches.delete(key);
      }));
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener("fetch", function (event) {
  var request = event.request;
  if (request.method !== "GET") return;

  var url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(function () {
        return caches.match(new URL("offline.html", self.location).href);
      })
    );
    return;
  }

  if (url.pathname.indexOf("/assets/") === -1) return;

  event.respondWith(
    caches.match(request).then(function (cached) {
      var network = fetch(request).then(function (response) {
        if (response && response.ok) {
          var copy = response.clone();
          caches.open(CACHE).then(function (cache) {
            cache.put(request, copy);
          });
        }
        return response;
      }).catch(function () {
        return cached;
      });
      return cached || network;
    })
  );
});

self.addEventListener("message", function (event) {
  var data = event.data || {};
  if (data.type !== "clear-alarm") return;
  event.waitUntil(
    self.registration.getNotifications({ tag: "medicine-alarm" }).then(function (notes) {
      notes.forEach(function (note) { note.close(); });
    })
  );
});

self.addEventListener("notificationclick", function (event) {
  var action = event.action || "";
  var target = (event.notification.data && event.notification.data.url) || "reminders.php";
  event.notification.close();
  event.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true }).then(function (list) {
      if (action === "stop") {
        list.forEach(function (client) {
          client.postMessage({ type: "stop-alarm" });
        });
        return;
      }
      var index;
      for (index = 0; index < list.length; index++) {
        list[index].postMessage({ type: "show-alarm" });
        if ("focus" in list[index]) return list[index].focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow(target);
    })
  );
});
