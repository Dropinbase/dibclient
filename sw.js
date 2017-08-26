/* global self, caches, fetch, URL, Response */
'use strict';

var config = {
    version: 'achilles',
    staticCacheItems: [],
    cachePathPattern: /^\/(?:(20[0-9]{2}|css|img|js|node_modules)\/(.+)?)?$/,
    offlineImage: '<svg role="img" aria-labelledby="offline-title"' + ' viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">' + '<title id="offline-title">Offline</title>' + '<g fill="none" fill-rule="evenodd"><path fill="#D8D8D8" d="M0 0h400v300H0z"/>' + '<text fill="#9B9B9B" font-family="Times New Roman,Times,serif" font-size="72" font-weight="bold">' + '<tspan x="93" y="172">offline</tspan></text></g></svg>',
    offlinePage: '/offline/'
};

function addToCache(cacheKey, request, response) {
    if (response.ok) {
        var copy = response.clone();
        caches.open(cacheKey).then(function (cache) {
            cache.put(request, copy);
        });
    }
    return response;
}

function fetchFromCache(event) {
    return caches.match(event.request).then(function (response) {
        if (!response) {
            throw Error(event.request.url + ' not found in cache');
        }
        return response;
    });
}

function offlineResponse(resourceType, opts) {
    if (resourceType === 'image') {
        return new Response(opts.offlineImage, { headers: { 'Content-Type': 'image/svg+xml' } });
    } else if (resourceType === 'content') {
        return caches.match(opts.offlinePage);
    }
    return undefined;
}

self.addEventListener('install', function (event) {
    function onInstall(event, opts) {
        return caches.open('static').then(function (cache) {
            return cache.addAll(opts.staticCacheItems);
        });
    }

    event.waitUntil(onInstall(event, config));
});

self.addEventListener('activate', function (event) { });

self.addEventListener('fetch', function (event) {

    function shouldHandleFetch(event, opts) {
        var request = event.request;
        var url = new URL(request.url);
        var criteria = {
            matchesPathPattern: !!opts.cachePathPattern.exec(url.pathname),
            isGETRequest: request.method === 'GET',
            isFromMyOrigin: url.origin === self.location.origin
        };
        var failingCriteria = Object.keys(criteria).filter(function (criteriaKey) {
            return !criteria[criteriaKey];
        });
        return !failingCriteria.length;
    }

    function onFetch(event, opts) {
        var request = event.request;
        var acceptHeader = request.headers.get('Accept');
        var resourceType = 'static';
        var cacheKey;

        if (acceptHeader.indexOf('text/html') !== -1) {
            resourceType = 'content';
        } else if (acceptHeader.indexOf('image') !== -1) {
            resourceType = 'image';
        }

        cacheKey = resourceType;

        if (resourceType === 'content') {
            event.respondWith(fetch(request).then(function (response) {
                return addToCache(cacheKey, request, response);
            }).catch(function () {
                return fetchFromCache(event);
            }).catch(function () {
                return offlineResponse(resourceType, opts);
            }));
        } else {
            event.respondWith(fetchFromCache(event).catch(function () {
                return fetch(request);
            }).then(function (response) {
                return addToCache(cacheKey, request, response);
            }).catch(function () {
                return offlineResponse(resourceType, opts);
            }));
        }
    }

    if (shouldHandleFetch(event, config)) {
        onFetch(event, config);
    }
});