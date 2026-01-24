'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';

const RESOURCES = {"flutter_bootstrap.js": "d98e53920f85fb1d422499a1ee4018d0",
"version.json": "00d0158026b230c999417c26422679dc",
"index.html": "4c28997b223763125839c4f654a8d414",
"/": "4c28997b223763125839c4f654a8d414",
"webfront.html": "cde77772fbc81053fc6c79fc2da5cfcf",
"main.dart.js": "024adb1a6b9127da9b0b0b7f0e3f4406",
"flutter.js": "83d881c1dbb6d6bcd6b42e274605b69c",
"favicon.png": "1a6fa949923ad28135a9cc5fb9ef3590",
"icons/Icon-192.png": "b413209fbdaea61a849db356aa122aef",
"icons/spinner.svg": "a3d42232c10a0e3446f94d081d5f213c",
"icons/Icon-512.png": "40caace896cb8cbdad146a96d1843fa6",
"manifest.json": "39e9cb782ef53068fe82d817ddd75c4c",
"assets/AssetManifest.json": "5d9d92193fba467bd077ef10470bb0c6",
"assets/NOTICES": "8bf64a587b36cab6a4d92ca51f1a2bbc",
"assets/FontManifest.json": "95cc1cff4d2ed30e1f14d64123604582",
"assets/AssetManifest.bin.json": "a87a6e4b6002901d64d34561e27f84fc",
"assets/packages/ipsview/fonts/IndieFlower-Regular.ttf": "0841af952c807bdf56455b1addb4c7df",
"assets/packages/ipsview/fonts/Roboto-Regular.ttf": "11eabca2251325cfc5589c9c6fb57b46",
"assets/packages/ipsview/fonts/RobotoMono-Regular.ttf": "b4618f1f7f4cee0ac09873fcc5a966f9",
"assets/packages/ipsview/fonts/DancingScript-Bold.ttf": "e4daab0ef72e9ec839c2962a5b6c10d3",
"assets/packages/ipsview/fonts/PTSans-BoldItalic.ttf": "22f2e7f9ae109154c0467619164247ea",
"assets/packages/ipsview/fonts/BebasNeue-Regular.ttf": "21bb70b62317f276f2e97a919ff5bd8c",
"assets/packages/ipsview/fonts/RobotoMono-BoldItalic.ttf": "4a0b78a48050f97c16ef6fc518afd362",
"assets/packages/ipsview/fonts/PTSans-RegularItalic.ttf": "a97ccf1e30117c053dd28f265c270a22",
"assets/packages/ipsview/fonts/RobotoMono-RegularItalic.ttf": "c37c35a80051edc42d141ec301066052",
"assets/packages/ipsview/fonts/PTSans-Regular.ttf": "4ea26cd5e7f64894d6c2451446f7dda5",
"assets/packages/ipsview/fonts/OpenSans-Bold.ttf": "1025a6e0fb0fa86f17f57cc82a6b9756",
"assets/packages/ipsview/fonts/Roboto-BoldItalic.ttf": "5b44818d2b9eda3e23cd5edd7b49b7d5",
"assets/packages/ipsview/fonts/Segment7-Regular.ttf": "e3b01fa007b90ac7f5c3f565f5fa0404",
"assets/packages/ipsview/fonts/OpenSans-RegularItalic.ttf": "f6238deb7f40a7a03134c11fb63ad387",
"assets/packages/ipsview/fonts/DancingScript-Regular.ttf": "b464889840d2a96a5dc30059d9f4ad9a",
"assets/packages/ipsview/fonts/RobotoMono-Bold.ttf": "7c13b04382bb3c4a6a50211300a1b072",
"assets/packages/ipsview/fonts/PTSans-Bold.ttf": "333ee0ee5989e593812c23ca2dd7bc24",
"assets/packages/ipsview/fonts/OpenSans-Regular.ttf": "3ed9575dcc488c3e3a5bd66620bdf5a4",
"assets/packages/ipsview/fonts/OpenSans-BoldItalic.ttf": "3a8113737b373d5bccd6f71d91408d16",
"assets/packages/ipsview/fonts/Roboto-Bold.ttf": "e07df86cef2e721115583d61d1fb68a6",
"assets/packages/ipsview/fonts/Roboto-RegularItalic.ttf": "a720f17aa773e493a7ebf8b08459e66c",
"assets/packages/timezone/data/latest_all.tzf": "df0e82dd729bbaca78b2aa3fd4efd50d",
"assets/packages/material_symbols_icons/lib/fonts/MaterialSymbolsRounded.ttf": "af39579772d86f79d056e4cacdca9ebd",
"assets/packages/material_symbols_icons/lib/fonts/MaterialSymbolsOutlined.ttf": "bb969b1e6123ab9d599183c855efa98a",
"assets/packages/material_symbols_icons/lib/fonts/MaterialSymbolsSharp.ttf": "3fcc569133fef05385aac13b534a1e9c",
"assets/packages/syncfusion_flutter_datepicker/assets/fonts/Roboto-Medium.ttf": "7d752fb726f5ece291e2e522fcecf86d",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle20.png": "52cdbc89f084428e9df1fdaf75bcf918",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle23.png": "05985cfc1de3cc32355ae56fe6652b1f",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle26.png": "084a7485156f49757e53c3910e532cc9",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle27.png": "8df8e8574e1e33751beced5cae1035b3",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle25.png": "a92b2550be278d8dcfa183d680b61ecb",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle18.png": "9cc9674b42891b1757db7b73e593c1de",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle24.png": "0a33516701ea509f461e87a1771f9c8c",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle5.png": "3cb632d1b2e967a456122f28d4770a01",
"assets/packages/syncfusion_flutter_calendar/assets/images/May.png": "e9a14fe43782d90f7c8e244316b4db49",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle8.png": "2b837eca4b31646cfdcea21211cf66d9",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle15.png": "e8d60d3a29c062b0213e0c433353c37b",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle13.png": "73da9fb063f6307b928808b8b49a7c71",
"assets/packages/syncfusion_flutter_calendar/assets/images/People_Circle11.png": "1b3d36d168f8f27a027b3d5fe54354b2",
"assets/packages/syncfusion_flutter_calendar/assets/fonts/Roboto-Medium.ttf": "7d752fb726f5ece291e2e522fcecf86d",
"assets/shaders/ink_sparkle.frag": "ecc85a2e95f5e9f53123dcaf8cb9b6ce",
"assets/AssetManifest.bin": "70d69b7b84b878bfac90efa9b23496e1",
"assets/graphics/IPSView_512_r10.png": "8b08668a235d8d8e30cbb6a45ea62323",
"assets/fonts/Roboto-Regular.ttf": "11eabca2251325cfc5589c9c6fb57b46",
"assets/fonts/Roboto-BoldItalic.ttf": "5b44818d2b9eda3e23cd5edd7b49b7d5",
"assets/fonts/MaterialIcons-Regular.otf": "cc57a73e2c14785bba8b7fdb1b31c4ab",
"assets/fonts/Roboto-Bold.ttf": "e07df86cef2e721115583d61d1fb68a6",
"assets/fonts/Roboto-RegularItalic.ttf": "a720f17aa773e493a7ebf8b08459e66c",
"assets/assets/ringtones/ringtone.mp3": "7b2394c41498cbb93519e47f533e6979",
"assets/assets/shaders/custom.frag": "2a9f643fb2bd5dcce337ddd67daebd5c",
"clientWin.html": "f832de0a103da86bb189767d182e208f",
"canvaskit/skwasm.js": "ea559890a088fe28b4ddf70e17e60052",
"canvaskit/skwasm.js.symbols": "e72c79950c8a8483d826a7f0560573a1",
"canvaskit/canvaskit.js.symbols": "bdcd3835edf8586b6d6edfce8749fb77",
"canvaskit/skwasm.wasm": "39dd80367a4e71582d234948adc521c0",
"canvaskit/chromium/canvaskit.js.symbols": "b61b5f4673c9698029fa0a746a9ad581",
"canvaskit/chromium/canvaskit.js": "8191e843020c832c9cf8852a4b909d4c",
"canvaskit/chromium/canvaskit.wasm": "f504de372e31c8031018a9ec0a9ef5f0",
"canvaskit/canvaskit.js": "728b2d477d9b8c14593d4f9b82b484f3",
"canvaskit/canvaskit.wasm": "7a3f4ae7d65fc1de6a6e7ddd3224bc93"};
// The application shell files that are downloaded before a service worker can
// start.
const CORE = ["main.dart.js",
"index.html",
"flutter_bootstrap.js",
"assets/AssetManifest.bin.json",
"assets/FontManifest.json"];

// During install, the TEMP cache is populated with the application shell files.
self.addEventListener("install", (event) => {
  self.skipWaiting();
  return event.waitUntil(
    caches.open(TEMP).then((cache) => {
      return cache.addAll(
        CORE.map((value) => new Request(value, {'cache': 'reload'})));
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
        // Claim client to enable caching on first launch
        self.clients.claim();
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
      // Claim client to enable caching on first launch
      self.clients.claim();
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
  if (event.request.method !== 'GET') {
    return;
  }
  var origin = self.location.origin;
  var key = event.request.url.substring(origin.length + 1);
  // Redirect URLs to the index.html
  if (key.indexOf('?v=') != -1) {
    key = key.split('?v=')[0];
  }
  if (event.request.url == origin || event.request.url.startsWith(origin + '/#') || key == '') {
    key = '/';
  }
  // If the URL is not the RESOURCE list then return to signal that the
  // browser should take over.
  if (!RESOURCES[key]) {
    return;
  }
  // If the URL is the index.html, perform an online-first request.
  if (key == '/') {
    return onlineFirst(event);
  }
  event.respondWith(caches.open(CACHE_NAME)
    .then((cache) =>  {
      return cache.match(event.request).then((response) => {
        // Either respond with the cached resource, or perform a fetch and
        // lazily populate the cache only if the resource was successfully fetched.
        return response || fetch(event.request).then((response) => {
          if (response && Boolean(response.ok)) {
            cache.put(event.request, response.clone());
          }
          return response;
        });
      })
    })
  );
});
self.addEventListener('message', (event) => {
  // SkipWaiting can be used to immediately activate a waiting service worker.
  // This will also require a page refresh triggered by the main worker.
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
    return;
  }
  if (event.data === 'downloadOffline') {
    downloadOffline();
    return;
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
  for (var resourceKey of Object.keys(RESOURCES)) {
    if (!currentContent[resourceKey]) {
      resources.push(resourceKey);
    }
  }
  return contentCache.addAll(resources);
}
// Attempt to download the resource online before falling back to
// the offline cache.
function onlineFirst(event) {
  return event.respondWith(
    fetch(event.request).then((response) => {
      return caches.open(CACHE_NAME).then((cache) => {
        cache.put(event.request, response.clone());
        return response;
      });
    }).catch((error) => {
      return caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request).then((response) => {
          if (response != null) {
            return response;
          }
          throw error;
        });
      });
    })
  );
}
