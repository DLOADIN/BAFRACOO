// =====================================================
// BAFRACOO - Modern Dashboard JavaScript
// Version: 2.0
// =====================================================

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
  window.history.replaceState(null, null, window.location.href);
}

// Dashboard functionality
class ModernDashboard {
  constructor() {
    this.sidebar = document.querySelector('.sidebar');
    this.sidebarToggle = document.querySelector('.sidebar-toggle');
    this.mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    this.sidebarOverlay = document.querySelector('.sidebar-overlay');
    this.mainContent = document.querySelector('.main-content');
    
    this.init();
  }

  init() {
    this.setupEventListeners();
    this.setActiveNavItem();
    this.handleResponsive();
    this.loadUserPreferences();
  }

  setupEventListeners() {
    // Sidebar toggle for desktop
    if (this.sidebarToggle) {
      this.sidebarToggle.addEventListener('click', () => {
        this.toggleSidebar();
      });
    }

    // Mobile menu toggle
    if (this.mobileMenuBtn) {
      this.mobileMenuBtn.addEventListener('click', () => {
        this.toggleMobileMenu();
      });
    }

    // Overlay click to close mobile menu
    if (this.sidebarOverlay) {
      this.sidebarOverlay.addEventListener('click', () => {
        this.closeMobileMenu();
      });
    }

    // Handle window resize
    window.addEventListener('resize', () => {
      this.handleResponsive();
    });

    // Handle escape key for mobile menu
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.sidebar && this.sidebar.classList.contains('mobile-open')) {
        this.closeMobileMenu();
      }
    });

    // Smooth scrolling for navigation links
    this.setupSmoothScrolling();
  }

  toggleSidebar() {
    if (this.sidebar) {
      this.sidebar.classList.toggle('collapsed');
      this.saveUserPreference('sidebar-collapsed', this.sidebar.classList.contains('collapsed'));
      
      // Trigger a custom event for other components to listen to
      window.dispatchEvent(new CustomEvent('sidebarToggle', {
        detail: { collapsed: this.sidebar.classList.contains('collapsed') }
      }));
    }
  }

  toggleMobileMenu() {
    if (this.sidebar && this.sidebarOverlay) {
      const isOpen = this.sidebar.classList.contains('mobile-open');
      
      if (isOpen) {
        this.closeMobileMenu();
      } else {
        this.openMobileMenu();
      }
    }
  }

  openMobileMenu() {
    if (this.sidebar && this.sidebarOverlay) {
      this.sidebar.classList.add('mobile-open');
      this.sidebarOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  closeMobileMenu() {
    if (this.sidebar && this.sidebarOverlay) {
      this.sidebar.classList.remove('mobile-open');
      this.sidebarOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  handleResponsive() {
    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
      // On mobile, always close sidebar by default
      if (this.sidebar && this.sidebar.classList.contains('mobile-open')) {
        this.closeMobileMenu();
      }
    } else {
      // On desktop, restore sidebar state and close mobile menu
      if (this.sidebar) {
        this.sidebar.classList.remove('mobile-open');
        if (this.sidebarOverlay) {
          this.sidebarOverlay.classList.remove('active');
        }
        document.body.style.overflow = '';
      }
    }
  }

  setActiveNavItem() {
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (href && href.includes(currentPath)) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  setupSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
          target.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });
  }

  saveUserPreference(key, value) {
    try {
      localStorage.setItem(`dashboard_${key}`, JSON.stringify(value));
    } catch (e) {
      console.warn('Could not save user preference:', e);
    }
  }

  loadUserPreferences() {
    try {
      const sidebarCollapsed = JSON.parse(localStorage.getItem('dashboard_sidebar-collapsed'));
      if (sidebarCollapsed && this.sidebar && window.innerWidth >= 768) {
        this.sidebar.classList.add('collapsed');
      }
    } catch (e) {
      console.warn('Could not load user preferences:', e);
    }
  }
}

// Utility functions
const Utils = {
  // Format numbers with commas
  formatNumber(num) {
    return new Intl.NumberFormat().format(num);
  },

  // Format currency
  formatCurrency(amount, currency = 'RWF') {
    return new Intl.NumberFormat('en-RW', {
      style: 'currency',
      currency: currency
    }).format(amount);
  },

  // Show notification
  showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
      <div class="notification-content">
        <span>${message}</span>
        <button class="notification-close">&times;</button>
      </div>
    `;

    // Add styles if not already present
    if (!document.querySelector('.notification-styles')) {
      const styles = document.createElement('style');
      styles.className = 'notification-styles';
      styles.textContent = `
        .notification {
          position: fixed;
          top: 20px;
          right: 20px;
          min-width: 300px;
          padding: 16px;
          border-radius: 8px;
          color: white;
          font-weight: 500;
          box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
          z-index: 9999;
          transform: translateX(100%);
          transition: transform 0.3s ease-in-out;
        }
        .notification.show { transform: translateX(0); }
        .notification-info { background: #3b82f6; }
        .notification-success { background: #10b981; }
        .notification-warning { background: #f59e0b; }
        .notification-error { background: #ef4444; }
        .notification-content { display: flex; justify-content: space-between; align-items: center; }
        .notification-close {
          background: none;
          border: none;
          color: white;
          font-size: 18px;
          cursor: pointer;
          margin-left: 12px;
        }
      `;
      document.head.appendChild(styles);
    }

    document.body.appendChild(notification);

    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);

    // Auto remove
    const removeNotification = () => {
      notification.classList.remove('show');
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    };

    // Close button
    notification.querySelector('.notification-close').addEventListener('click', removeNotification);

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(removeNotification, duration);
    }

    return notification;
  },

  // Confirm dialog
  confirm(message, callback) {
    const modal = document.createElement('div');
    modal.innerHTML = `
      <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
        <div style="background: white; padding: 24px; border-radius: 12px; min-width: 300px; text-align: center;">
          <p style="margin-bottom: 20px; color: #374151;">${message}</p>
          <div>
            <button class="confirm-cancel" style="margin-right: 12px; padding: 8px 16px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer;">Cancel</button>
            <button class="confirm-ok" style="padding: 8px 16px; border: none; background: #ef4444; color: white; border-radius: 6px; cursor: pointer;">Confirm</button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector('.confirm-cancel').addEventListener('click', () => {
      document.body.removeChild(modal);
    });

    modal.querySelector('.confirm-ok').addEventListener('click', () => {
      document.body.removeChild(modal);
      callback();
    });
  }
};

// Animation utilities
const Animations = {
  fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.transition = `opacity ${duration}ms ease-in-out`;
    
    requestAnimationFrame(() => {
      element.style.opacity = '1';
    });
  },

  slideIn(element, direction = 'left', duration = 300) {
    const transforms = {
      left: 'translateX(-20px)',
      right: 'translateX(20px)',
      up: 'translateY(-20px)',
      down: 'translateY(20px)'
    };

    element.style.transform = transforms[direction];
    element.style.opacity = '0';
    element.style.transition = `all ${duration}ms ease-in-out`;

    requestAnimationFrame(() => {
      element.style.transform = 'translate(0)';
      element.style.opacity = '1';
    });
  },

  pulse(element, duration = 1000) {
    element.style.animation = `pulse ${duration}ms ease-in-out`;
  }
};

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new ModernDashboard();
  
  // Animate dashboard cards on load
  const cards = document.querySelectorAll('.dashboard-card');
  cards.forEach((card, index) => {
    setTimeout(() => {
      Animations.fadeIn(card);
    }, index * 100);
  });

  // Add loading state management
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        // Re-enable after 3 seconds as fallback
        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
        }, 3000);
      }
    });
  });
});

// Export for global use
window.Dashboard = {
  Utils,
  Animations
};
