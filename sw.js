var CACHE = "medicine-reminder-v1";

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
