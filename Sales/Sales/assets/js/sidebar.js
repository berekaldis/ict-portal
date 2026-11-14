// assets/js/sidebar.js
$(document).ready(function() {
    // Sidebar toggle
    $('#sidebarToggle').on('click', function() {
        const sidebar = $('#sidebar');
        const overlay = $('#sidebarOverlay');
        
        if (window.innerWidth <= 992) {
            // Mobile: toggle sidebar visibility
            sidebar.toggleClass('active');
            overlay.toggleClass('active');
        } else {
            // Desktop: toggle collapsed state
            sidebar.toggleClass('collapsed');
            // Save state to localStorage
            const isCollapsed = sidebar.hasClass('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
    });
    
    // Sidebar close (mobile)
    $('#sidebarClose').on('click', function() {
        $('#sidebar').removeClass('active');
        $('#sidebarOverlay').removeClass('active');
    });
    
    // Overlay click (mobile)
    $('#sidebarOverlay').on('click', function() {
        $('#sidebar').removeClass('active');
        $(this).removeClass('active');
    });
    
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true' && window.innerWidth > 992) {
        $('#sidebar').addClass('collapsed');
    }
    
    // Handle window resize
    let resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const sidebar = $('#sidebar');
            const overlay = $('#sidebarOverlay');
            
            if (window.innerWidth > 992) {
                // Desktop: show sidebar
                sidebar.removeClass('active');
                overlay.removeClass('active');
                
                // Restore collapsed state if saved
                const savedState = localStorage.getItem('sidebarCollapsed');
                if (savedState === 'true') {
                    sidebar.addClass('collapsed');
                } else {
                    sidebar.removeClass('collapsed');
                }
            } else {
                // Mobile: hide sidebar
                sidebar.removeClass('active');
                overlay.removeClass('active');
                sidebar.removeClass('collapsed');
            }
        }, 250);
    });
});

// Keyboard shortcut: Ctrl+B to toggle sidebar
$(document).on('keydown', function(e) {
    if (e.ctrlKey && e.key === 'b') {
        e.preventDefault();
        $('#sidebarToggle').click();
    }
});