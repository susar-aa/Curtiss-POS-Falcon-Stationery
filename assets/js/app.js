/**
 * Curtiss POS - UI Interactivity & Branch Switcher
 */

document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Mobile Toggle
    const mobileToggleBtn = document.getElementById('mobileToggleBtn');
    const sidebar = document.getElementById('appSidebar');

    if (mobileToggleBtn && sidebar) {
        mobileToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !mobileToggleBtn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // Branch Switcher Change Event
    const branchSelector = document.getElementById('headerBranchSelect');
    if (branchSelector) {
        branchSelector.addEventListener('change', (e) => {
            const branchId = e.target.value;
            if (!branchId) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'select_branch.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'branch_id';
            input.value = branchId;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
    }
});
