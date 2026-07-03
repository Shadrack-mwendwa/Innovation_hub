// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// File upload preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}

// Load more content (infinite scroll)
let loading = false;
window.addEventListener('scroll', function() {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        if (!loading) {
            loading = true;
            // Trigger load more event
            const event = new CustomEvent('loadMore');
            window.dispatchEvent(event);
            setTimeout(() => { loading = false; }, 1000);
        }
    }
});

// Dark mode toggle (optional)
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Check for saved dark mode preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}

// Handle notification clicks
function markNotificationAsRead(notificationId) {
    fetch(`mark-notification-read.php?id=${notificationId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          }
      });
}

// Certificate download tracking
function trackDownload(certificateId) {
    fetch(`track-download.php?id=${certificateId}`, {
        method: 'POST',
    });
}

// Idea like functionality (AJAX)
function likeIdea(ideaId) {
    fetch(`like-idea.php?id=${ideaId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              const likeButton = document.querySelector(`#like-btn-${ideaId}`);
              const likeCount = document.querySelector(`#like-count-${ideaId}`);
              if (likeButton && likeCount) {
                  if (data.liked) {
                      likeButton.classList.add('liked');
                  } else {
                      likeButton.classList.remove('liked');
                  }
                  likeCount.textContent = data.likes;
              }
          }
      });
}

// Export functions for global use
window.validateForm = validateForm;
window.previewImage = previewImage;
window.confirmDelete = confirmDelete;
window.searchTable = searchTable;
window.toggleDarkMode = toggleDarkMode;
window.markNotificationAsRead = markNotificationAsRead;
window.trackDownload = trackDownload;
window.likefeedback = likefeedback; 