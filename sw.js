const WILLOW_CACHE = "willow-app-shell-v1";
const WILLOW_APP_SHELL = [
  "/",
  "/offline.php",
  "/manifest.webmanifest",
  "/js/willow_app.js",
  "/img/m_logo.png",
  "/img/ico_alert.png",
  "/img/ico_menu.png",
  "/img/willow_app_icon.svg",
  "/img/willow_app_icon.png",
  "/img/willow_splash2.png"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(WILLOW_CACHE).then((cache) => cache.addAll(WILLOW_APP_SHELL)).catch(() => null)
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((key) => {
      if (key !== WILLOW_CACHE) {
        return caches.delete(key);
      }
      return null;
    })))
  );
  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  const request = event.request;

  if (request.method !== "GET") {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(() => caches.match("/offline.php"))
    );
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => cached || fetch(request).then((response) => {
      if (!response || response.status !== 200 || response.type !== "basic") {
        return response;
      }

      const responseClone = response.clone();
      caches.open(WILLOW_CACHE).then((cache) => cache.put(request, responseClone));
      return response;
    }).catch(() => cached))
  );
});
