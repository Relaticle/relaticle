// Simple smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href^="#"]');
        if (link) {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                // Scroll with offset for fixed header
                window.scrollTo({
                    top: targetElement.offsetTop + 80, // Adjust for fixed header height
                    behavior: 'smooth'
                });

                // Update the URL hash without scrolling
                history.pushState(null, null, `#${targetId}`);
            }
        }
    });
});

// Ensure sidebars can properly scroll
function adjustSidebarHeights() {
    // Calculate available height for sidebars
    const viewportHeight = window.innerHeight;
    const topOffset = 96; // 24 (top-24 in TailwindCSS) = 6rem
    const sidebarMaxHeight = viewportHeight - topOffset;

    // Apply height to sidebar containers if needed
    document.querySelectorAll('.docs-sidebar > div, .toc').forEach(sidebar => {
        if (sidebar) {
            sidebar.style.maxHeight = `${sidebarMaxHeight}px`;
        }
    });
}

// Run on load and resize
window.addEventListener('load', adjustSidebarHeights);
window.addEventListener('resize', adjustSidebarHeights);
