<?php
/**
 * Renders the site's tracking pixels (Meta Pixel / GTM / gtag) directly into
 * the page, exactly like render_landing.php does.
 *
 * Previously this injected the pixel via JavaScript after requestIdleCallback
 * (up to 4s after load). That made Meta's Pixel Helper / Events Manager / domain
 * checks miss the pixel, lost PageViews for visitors who left early, and meant
 * fbq() did not exist yet when inline events (Purchase, AddToCart...) ran.
 *
 * The Meta base code is already asynchronous (fbevents.js is loaded with
 * t.async = true), so printing it inline costs nothing in render performance.
 *
 * Usage (includes/header.php):
 *   in <head>:        echo renderDeferredPixelLoader($siteSettings);
 *   after <body>:     echo renderPixelBody($siteSettings);
 */
function renderDeferredPixelLoader($siteSettings) {
    $head = $siteSettings['pixel_head'] ?? '';
    if (trim($head) === '') {
        return '';
    }

    // data-cfasync="false" keeps Cloudflare Rocket Loader from deferring/rewriting the pixel.
    $head = preg_replace('/<script(?![^>]*data-cfasync)(\s|>)/i', '<script data-cfasync="false"$1', $head);

    // GTM detection: when the head code is a Google Tag Manager container, the Meta
    // pixel is expected to be fired FROM GTM, so the site must not call fbq() itself
    // (that would double-count). Events are pushed to window.dataLayer instead.
    $viaGtm = stripos($head, 'googletagmanager.com/gtm.js') !== false;

    // trackPixel(): one call site for all storefront events.
    //  - always pushes {event: <MetaEventName>, ...params} to dataLayer (GTM triggers on it)
    //  - calls fbq directly only when the pixel is installed directly (no GTM)
    //  - never throws if the pixel / GTM is blocked by an ad-blocker
    $helper = '<script data-cfasync="false">' . "\n"
        . 'window.dataLayer = window.dataLayer || [];' . "\n"
        . 'window.PIXEL_VIA_GTM = ' . ($viaGtm ? 'true' : 'false') . ';' . "\n"
        . <<<'JS'
window.trackPixel = function (event, params, opts) {
    try {
        var payload = { event: event };
        for (var k in (params || {})) { payload[k] = params[k]; }
        window.dataLayer.push(payload);
    } catch (e) {}
    try {
        if (!window.PIXEL_VIA_GTM && typeof window.fbq === 'function') {
            if (opts) { fbq('track', event, params || {}, opts); }
            else { fbq('track', event, params || {}); }
        }
    } catch (e) {}
};
</script>
JS;

    return "<!-- @head pixel -->\n" . $head . "\n" . $helper . "\n";
}

/** True when @head contains a Google Tag Manager container. */
function pixelViaGtm($siteSettings) {
    return stripos($siteSettings['pixel_head'] ?? '', 'googletagmanager.com/gtm.js') !== false;
}

function renderPixelBody($siteSettings) {
    $body = $siteSettings['pixel_body'] ?? '';
    if (trim($body) === '') {
        return '';
    }
    return "<!-- @body pixel -->\n" . $body . "\n";
}
