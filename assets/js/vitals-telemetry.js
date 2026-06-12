(function() {
    if (typeof baloaStructureAuditorSeoTelemetry === 'undefined') return;

    function sendMetric(name, value) {
        const data = {
            name: name,
            value: value,
            url: baloaStructureAuditorSeoTelemetry.url
        };
        
        const payload = JSON.stringify(data);
        if (navigator.sendBeacon) {
            navigator.sendBeacon(baloaStructureAuditorSeoTelemetry.endpoint, payload);
        } else {
            fetch(baloaStructureAuditorSeoTelemetry.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': baloaStructureAuditorSeoTelemetry.nonce
                },
                body: payload,
                keepalive: true
            });
        }
    }

    try {
        // Collect TTFB from navigation performance entries
        performance.getEntriesByType('navigation').forEach(entry => {
            sendMetric('TTFB', entry.responseStart);
        });

        // Collect FCP
        performance.getEntriesByType('paint').forEach(entry => {
            if (entry.name === 'first-contentful-paint') {
                sendMetric('FCP', entry.startTime);
            }
        });

        // Collect LCP
        let lcpValue = 0;
        const lcpObserver = new PerformanceObserver((entryList) => {
            const entries = entryList.getEntries();
            const lastEntry = entries[entries.length - 1];
            lcpValue = lastEntry.startTime;
        });
        lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });

        // Collect CLS
        let clsValue = 0;
        const clsObserver = new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
        });
        clsObserver.observe({ type: 'layout-shift', buffered: true });

        // Collect FID
        const fidObserver = new PerformanceObserver((entryList) => {
            const entries = entryList.getEntries();
            if (entries.length > 0) {
                sendMetric('FID', entries[0].processingStart - entries[0].startTime);
            }
        });
        fidObserver.observe({ type: 'first-input', buffered: true });

        // Collect INP (Interaction to Next Paint)
        let inpValue = 0;
        const inpObserver = new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                if (entry.duration > inpValue) {
                    inpValue = entry.duration;
                }
            }
        });
        inpObserver.observe({ type: 'event', buffered: true, durationThreshold: 16 });

        // Dispatch on visibilitychange/unload to capture final values for cumulative metrics
        window.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                if (lcpValue > 0) sendMetric('LCP', lcpValue);
                if (clsValue > 0) sendMetric('CLS', clsValue);
                if (inpValue > 0) sendMetric('INP', inpValue);
            }
        });
    } catch (e) {
        console.warn('BALOA RUM Telemetry not fully supported in this browser:', e);
    }
})();
