@if(app()->isProduction() && !empty(config('services.fathom.site_id')))
    <script src="https://cdn.usefathom.com/script.js"
        data-site="{{ config('services.fathom.site_id') }}"
        data-spa="auto"
        data-auto="false"
        defer></script>
<script>
window.addEventListener('load', function() {
    function normalizeUrl(pathname) {
        // Remove tenant ID: /1/people → /people
        pathname = pathname.replace(/^\/\d+/, '');

        // Normalize patterns:
        // /people/5 → /people/view
        // /people/5/edit → /people/edit
        pathname = pathname
            .replace(/^(\/\w+)\/\d+$/,'$1/view')           // detail view
            .replace(/^(\/\w+)\/\d+\/edit$/,'$1/edit');    // edit form

        return pathname || '/dashboard';
    }

    function track() {
        if (typeof fathom !== 'undefined') {
            const normalized = window.location.origin + normalizeUrl(window.location.pathname);
            fathom.trackPageview({ url: normalized });
        }
    }

    // Initial track
    setTimeout(track, 100);

    // SPA navigation
    document.addEventListener('livewire:navigated', track);
});
</script>
@endif
