'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';
const RESOURCES = {
  "assets/AssetManifest.json": "8b91ecc4ca8a5e2f263df805516524c6",
"assets/FontManifest.json": "1f14fc381e9565c7a2462433254f69db",
"assets/fonts/DancingScript-Bold.ttf": "e4daab0ef72e9ec839c2962a5b6c10d3",
"assets/fonts/DancingScript-Regular.ttf": "b464889840d2a96a5dc30059d9f4ad9a",
"assets/fonts/IndieFlower-Regular.ttf": "0841af952c807bdf56455b1addb4c7df",
"assets/fonts/MaterialIcons-Regular.ttf": "56d3ffdef7a25659eab6a68a3fbfaf16",
"assets/fonts/OpenSans-Bold.ttf": "1025a6e0fb0fa86f17f57cc82a6b9756",
"assets/fonts/OpenSans-BoldItalic.ttf": "3a8113737b373d5bccd6f71d91408d16",
"assets/fonts/OpenSans-Regular.ttf": "3ed9575dcc488c3e3a5bd66620bdf5a4",
"assets/fonts/OpenSans-RegularItalic.ttf": "f6238deb7f40a7a03134c11fb63ad387",
"assets/fonts/PTSans-Bold.ttf": "333ee0ee5989e593812c23ca2dd7bc24",
"assets/fonts/PTSans-BoldItalic.ttf": "22f2e7f9ae109154c0467619164247ea",
"assets/fonts/PTSans-Regular.ttf": "4ea26cd5e7f64894d6c2451446f7dda5",
"assets/fonts/PTSans-RegularItalic.ttf": "a97ccf1e30117c053dd28f265c270a22",
"assets/fonts/RobotoMono-Bold.ttf": "7c13b04382bb3c4a6a50211300a1b072",
"assets/fonts/RobotoMono-BoldItalic.ttf": "4a0b78a48050f97c16ef6fc518afd362",
"assets/fonts/RobotoMono-Regular.ttf": "b4618f1f7f4cee0ac09873fcc5a966f9",
"assets/fonts/RobotoMono-RegularItalic.ttf": "c37c35a80051edc42d141ec301066052",
"assets/graphics/ImageNotFound_128.png": "9195566839639503a6d131ad4bb5abb9",
"assets/graphics/IPSView_512_r10.png": "8b08668a235d8d8e30cbb6a45ea62323",
"assets/NOTICES": "3369eb1e910881c9f62adf861e89e50d",
"assets/packages/cupertino_icons/assets/CupertinoIcons.ttf": "9a62a954b81a1ad45a58b9bcea89b50b",
"assets/packages/timezone/data/2020a_2015-2025.tzf": "23059081abac12ca2a8dbef1001d08f5",
"favicon.png": "1a6fa949923ad28135a9cc5fb9ef3590",
"icons/Icon-192.png": "b413209fbdaea61a849db356aa122aef",
"icons/Icon-512.png": "40caace896cb8cbdad146a96d1843fa6",
"index.html": "b84cf65f2cd29c5ff5e65a028d87e3e2",
"/": "b84cf65f2cd29c5ff5e65a028d87e3e2",
"main.dart.js": "5855c4819511a7fc7455ced0e0264e84",
"manifest.json": "338f6f11d77d2125e21a6aeafe1e7822"
};

// The application shell files that are downloaded before a service worker can
// start.
const CORE = [
  "/",
"main.dart.js",
"index.html",
"assets/NOTICES",
"assets/AssetManifest.json",
"assets/FontManifest.json"];

// During install, the TEMP cache is populated with the application shell files.
self.addEventListener("install", (event) => {
  return event.waitUntil(
    caches.open(TEMP).then((cache) => {
      // Provide a no-cache param to ensure the latest version is downloaded.
      return cache.addAll(CORE.map((value) => new Request(value, {'cache': 'no-cache'})));
    })
  );
});

// During activate, the cache is populated with the temp files downloaded in
// install. If this service worker is upgrading from one with a saved
// MANIFEST, then use this to retain unchanged resource files.
self.addEventListener("activate", function(event) {
  return event.waitUntil(async function() {
    try {
      var contentCache = await caches.open(CACHE_NAME);
      var tempCache = await caches.open(TEMP);
      var manifestCache = await caches.open(MANIFEST);
      var manifest = await manifestCache.match('manifest');

      // When there is no prior manifest, clear the entire cache.
      if (!manifest) {
        await caches.delete(CACHE_NAME);
        contentCache = await caches.open(CACHE_NAME);
        for (var request of await tempCache.keys()) {
          var response = await tempCache.match(request);
          await contentCache.put(request, response);
        }
        await caches.delete(TEMP);
        // Save the manifest to make future upgrades efficient.
        await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
        return;
      }

      var oldManifest = await manifest.json();
      var origin = self.location.origin;
      for (var request of await contentCache.keys()) {
        var key = request.url.substring(origin.length + 1);
        if (key == "") {
          key = "/";
        }
        // If a resource from the old manifest is not in the new cache, or if
        // the MD5 sum has changed, delete it. Otherwise the resource is left
        // in the cache and can be reused by the new service worker.
        if (!RESOURCES[key] || RESOURCES[key] != oldManifest[key]) {
          await contentCache.delete(request);
        }
      }
      // Populate the cache with the app shell TEMP files, potentially overwriting
      // cache files preserved above.
      for (var request of await tempCache.keys()) {
        var response = await tempCache.match(request);
        await contentCache.put(request, response);
      }
      await caches.delete(TEMP);
      // Save the manifest to make future upgrades efficient.
      await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
      return;
    } catch (err) {
      // On an unhandled exception the state of the cache cannot be guaranteed.
      console.error('Failed to upgrade service worker: ' + err);
      await caches.delete(CACHE_NAME);
      await caches.delete(TEMP);
      await caches.delete(MANIFEST);
    }
  }());
});

// The fetch handler redirects requests for RESOURCE files to the service
// worker cache.
self.addEventListener("fetch", (event) => {
  var origin = self.location.origin;
  var key = event.request.url.substring(origin.length + 1);
  // Redirect URLs to the index.html
  if (event.request.url == origin || event.request.url.startsWith(origin + '/#')) {
    key = '/';
  }
  // If the URL is not the the RESOURCE list, skip the cache.
  if (!RESOURCES[key]) {
    return event.respondWith(fetch(event.request));
  }
  event.respondWith(caches.open(CACHE_NAME)
    .then((cache) =>  {
      return cache.match(event.request).then((response) => {
        // Either respond with the cached resource, or perform a fetch and
        // lazily populate the cache. Ensure the resources are not cached
        // by the browser for longer than the service worker expects.
        var modifiedRequest = new Request(event.request, {'cache': 'no-cache'});
        return response || fetch(modifiedRequest).then((response) => {
          cache.put(event.request, response.clone());
          return response;
        });
      })
    })
  );
});

self.addEventListener('message', (event) => {
  // SkipWaiting can be used to immediately activate a waiting service worker.
  // This will also require a page refresh triggered by the main worker.
  if (event.data == 'skipWaiting') {
    return self.skipWaiting();
  }

  if (event.message = 'downloadOffline') {
    downloadOffline();
  }
});

// Download offline will check the RESOURCES for all files not in the cache
// and populate them.
async function downloadOffline() {
  var resources = [];
  var contentCache = await caches.open(CACHE_NAME);
  var currentContent = {};
  for (var request of await contentCache.keys()) {
    var key = request.url.substring(origin.length + 1);
    if (key == "") {
      key = "/";
    }
    currentContent[key] = true;
  }
  for (var resourceKey in Object.keys(RESOURCES)) {
    if (!currentContent[resourceKey]) {
      resources.push(resourceKey);
    }
  }
  return contentCache.addAll(resources);
}
