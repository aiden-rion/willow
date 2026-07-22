(function () {
  var doc = document.documentElement;
  var nav = window.navigator;
  var isStandalone = window.matchMedia && window.matchMedia("(display-mode: standalone)").matches;
  var isIosStandalone = "standalone" in nav && nav.standalone;
  var isCapacitor = !!(window.Capacitor || window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.bridge);

  doc.classList.toggle("willow-standalone", !!(isStandalone || isIosStandalone));
  doc.classList.toggle("willow-native-shell", isCapacitor);

  if ("scrollRestoration" in window.history) {
    window.history.scrollRestoration = "manual";
  }

  if ("serviceWorker" in nav && window.location.protocol === "https:") {
    window.addEventListener("load", function () {
      nav.serviceWorker.register("/sw.js").catch(function () {});
    });
  }

  document.addEventListener("click", function (event) {
    var link = event.target.closest && event.target.closest("a[href]");
    if (!link || link.target || link.hasAttribute("download")) {
      return;
    }

    var url;
    try {
      url = new URL(link.href);
    } catch (error) {
      return;
    }

    if (url.origin !== window.location.origin) {
      link.target = "_blank";
      link.rel = "noopener noreferrer";
    }
  });

  window.willowAppBridge = {
    isStandalone: !!(isStandalone || isIosStandalone),
    isNativeShell: isCapacitor,
    canUsePush: "Notification" in window && "serviceWorker" in nav,
    requestPushPermission: function () {
      if (!("Notification" in window)) {
        return Promise.resolve("unsupported");
      }

      if (Notification.permission !== "default") {
        return Promise.resolve(Notification.permission);
      }

      return Notification.requestPermission();
    }
  };
})();
