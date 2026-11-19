const OFFLINE_VERSION = 2;
const CACHE_NAME = `offline-v${OFFLINE_VERSION}`;
const OFFLINE_URL = "/offline.html";

const BYPASS_PREFIXES = [
    "/oauth",
    "/login",
    "/logout",
    "/register",
    "/password",
    "/auth",
    "/authorize_interaction",
    "/i/auth",
    "/i/confirm-email",
    "/settings/security",
    "/api",
    "/storage",
];

const shouldBypass = (url) =>
    BYPASS_PREFIXES.some(
        (prefix) =>
            url.pathname === prefix || url.pathname.startsWith(prefix + "/"),
    );

self.addEventListener("install", (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_NAME);
            await cache.add(new Request(OFFLINE_URL, { cache: "reload" }));
            await self.skipWaiting();
        })(),
    );
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
