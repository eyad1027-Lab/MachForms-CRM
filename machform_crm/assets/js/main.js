/**
 * Machform CRM - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close alerts automatically
    const closeAlerts = document.querySelectorAll('.close-alert');
    closeAlerts.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const alert = btn.closest('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
    
    // Global search functionality
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = APP_URL + '/modules/entries/entries.php?search=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Bulk action checkboxes
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.bulk-select');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // Date picker initialization (if available)
    const datePickers = document.querySelectorAll('input[type="date"]');
    datePickers.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.showPicker();
        });
    });
    
    // Tab switching
    const tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to selected tab
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Modal handling
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
            }
        });
    });
    
    const modalCloses = document.querySelectorAll('.modal-close');
    modalCloses.forEach(function(close) {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
    
    // Print functionality
    const printButtons = document.querySelectorAll('[data-print]');
    printButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Export functionality
    const exportButtons = document.querySelectorAll('[data-export]');
    exportButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const format = this.getAttribute('data-export');
            const url = this.getAttribute('data-url');
            if (url) {
                window.location.href = url + '&format=' + format;
            }
        });
    });
    
    // Refresh stats periodically
    const refreshStats = document.querySelectorAll('[data-auto-refresh]');
    if (refreshStats.length > 0) {
        setInterval(function() {
            location.reload();
        }, 60000); // Refresh every minute
    }
    
    // Tooltip initialization
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(function(el) {
        el.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
    
    console.log('Machform CRM initialized successfully');
});

// Utility functions
const MachformCRM = {
    // Show notification
    notify: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(function() {
            notification.classList.remove('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    },
    
    // AJAX request helper
    ajax: function(url, method = 'GET', data = null) {
        return new Promise(function(resolve, reject) {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    reject(new Error(xhr.statusText));
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Network error'));
            };
            
            if (data) {
                xhr.send(JSON.stringify(data));
            } else {
                xhr.send();
            }
        });
    },
    
    // Format date
    formatDate: function(dateString, format = 'short') {
        const date = new Date(dateString);
        const options = format === 'short' 
            ? { year: 'numeric', month: 'short', day: 'numeric' }
            : { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return date.toLocaleDateString('en-US', options);
    },
    
    // Format number with commas
    formatNumber: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};
