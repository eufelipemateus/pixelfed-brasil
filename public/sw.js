const OFFLINE_VERSION = 1;
const CACHE_NAME = "offline";
const OFFLINE_URL = "/offline.html";

self.addEventListener("install", (event) => {
	event.waitUntil(
		(async () => {
			const cache = await caches.open(CACHE_NAME);
			await cache.add(new Request(OFFLINE_URL, { cache: "reload" }));
		})()
	);
	self.skipWaiting();
});

self.addEventListener("activate", (event) => {
	event.waitUntil(
		(async () => {
			if ("navigationPreload" in self.registration) {
				// A preload follows redirects through fetch(), which cannot hand
				// custom schemes such as pixelfed:// back to the operating system.
				await self.registration.navigationPreload.disable();
			}
			await self.clients.claim();
		})()
	);
});

self.addEventListener("message", (event) => {
	if (event.data && event.data.type === "pixelfed:oauth-navigation-bypass" && event.ports[0]) {
		event.ports[0].postMessage({ supported: true });
	}
});

self.addEventListener("fetch", (event) => {
	if (event.request.mode !== "navigate") {
		return;
	}

	const requestUrl = new URL(event.request.url);

	// Let the browser own OAuth and form navigations. In particular, the
	// authorization response may redirect to pixelfed://login; following that
	// redirect inside fetch() fails before the OS can open the client app.
	if (event.request.method !== "GET" || requestUrl.pathname === "/oauth/authorize") {
		return;
	}

	event.respondWith(
		(async () => {
			try {
				return await fetch(event.request);
			} catch (error) {
				const cache = await caches.open(CACHE_NAME);
				return await cache.match(OFFLINE_URL);
			}
		})()
	);
});
