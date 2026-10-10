/**
 * Main JavaScript for Bootstrap 5 Hostel Complaint Tracking System
 * Features: alerts auto-dismiss, image preview, form validation, star rating,
 * search/filter, tooltips, character counter, and status change confirmation
 */

document.addEventListener('DOMContentLoaded', function() {

  // ============================================================================
  // 1. AUTO-DISMISS BOOTSTRAP ALERTS AFTER 4 SECONDS
  // ============================================================================
  const dismissibleAlerts = document.querySelectorAll('.alert');
  dismissibleAlerts.forEach(function(alert) {
    const closeButton = alert.querySelector('.btn-close');

    // Auto-dismiss after 4 seconds
    const alertTimeout = setTimeout(function() {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 4000);

    // Clear timeout if user manually closes the alert
    if (closeButton) {
      closeButton.addEventListener('click', function() {
        clearTimeout(alertTimeout);
      });
    }
  });

  // ============================================================================
  // 2. IMAGE PREVIEW BEFORE UPLOAD
  // ============================================================================
  const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
  imageInputs.forEach(function(input) {
    input.addEventListener('change', function(event) {
      const file = event.target.files[0];

      if (!file) return;

      // Validate file is an image
      if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file.');
        input.value = '';
        return;
      }

      // Create preview container if it doesn't exist
      let previewContainer = input.parentElement.querySelector('.image-preview-container');
      if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.className = 'image-preview-container mt-3';
        input.parentElement.appendChild(previewContainer);
      }

      // Read and display image
      const reader = new FileReader();
      reader.onload = function(e) {
        previewContainer.innerHTML = `
          <div class="card">
            <img src="${e.target.result}" class="card-img-top" alt="Preview" style="max-height: 250px; object-fit: cover;">
            <div class="card-body p-2">
              <small class="text-muted">File: ${file.name}</small><br>
              <small class="text-muted">Size: ${(file.size / 1024).toFixed(2)} KB</small>
            </div>
          </div>
        `;
      };
      reader.readAsDataURL(file);
    });
  });

  // ============================================================================
  // 3. CONFIRM DIALOG BEFORE STATUS CHANGE FORM SUBMIT
  // ============================================================================
  const statusChangeForms = document.querySelectorAll('form[data-confirm-status-change]');
  statusChangeForms.forEach(function(form) {
    form.addEventListener('submit', function(event) {
      const statusSelect = form.querySelector('select[name*="status"]');
      const confirmMsg = form.getAttribute('data-confirm-status-change') ||
                        'Are you sure you want to change the complaint status?';

      if (statusSelect && !confirm(confirmMsg)) {
        event.preventDefault();
        return false;
      }
    });
  });

  // ============================================================================
  // 4. STAR RATING WIDGET
  // ============================================================================
  const starContainers = document.querySelectorAll('.star-rating');
  starContainers.forEach(function(container) {
    const stars = container.querySelectorAll('.star-label');
    const hiddenInput = container.querySelector('input[type="hidden"]');

    stars.forEach(function(star, index) {
      star.addEventListener('click', function(event) {
        event.preventDefault();

        const ratingValue = index + 1;

        // Update hidden input value
        if (hiddenInput) {
          hiddenInput.value = ratingValue;
        }

        // Highlight clicked star and all preceding stars
        stars.forEach(function(s, i) {
          if (i < ratingValue) {
            s.classList.add('active');
          } else {
            s.classList.remove('active');
          }
        });
      });

      // Hover effect
      star.addEventListener('mouseover', function() {
        stars.forEach(function(s, i) {
          if (i <= index) {
            s.classList.add('hover');
          } else {
            s.classList.remove('hover');
          }
        });
      });
    });

    // Remove hover effect when leaving container
    container.addEventListener('mouseleave', function() {
      stars.forEach(function(s) {
        s.classList.remove('hover');
      });
    });
  });

  // ============================================================================
  // 5. SEARCH/FILTER ON COMPLAINT TABLES
  // ============================================================================
  const searchInputs = document.querySelectorAll('input[data-table-search]');
  searchInputs.forEach(function(searchInput) {
    const tableId = searchInput.getAttribute('data-table-search');
    const table = document.getElementById(tableId);

    if (!table) return;

    searchInput.addEventListener('keyup', function(event) {
      const searchTerm = event.target.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');
      let visibleCount = 0;

      rows.forEach(function(row) {
        const text = row.innerText.toLowerCase();
        if (text.includes(searchTerm)) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      // Show/hide "no results" message
      let noResultsMsg = table.parentElement.querySelector('.no-search-results');
      if (visibleCount === 0 && searchTerm) {
        if (!noResultsMsg) {
          noResultsMsg = document.createElement('div');
          noResultsMsg.className = 'alert alert-info no-search-results mt-2';
          noResultsMsg.textContent = 'No complaints found matching your search.';
          table.parentElement.appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = '';
      } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
      }
    });
  });

  // ============================================================================
  // 6. BOOTSTRAP 5 TOOLTIP INITIALIZATION
  // ============================================================================
  const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipElements.forEach(function(element) {
    new bootstrap.Tooltip(element);
  });

  // ============================================================================
  // 7. CHARACTER COUNTER FOR TEXTAREA FIELDS
  // ============================================================================
  const textareas = document.querySelectorAll('textarea[data-char-limit]');
  textareas.forEach(function(textarea) {
    const charLimit = parseInt(textarea.getAttribute('data-char-limit'), 10);

    // Create counter element if it doesn't exist
    let counterElement = textarea.parentElement.querySelector('.char-counter');
    if (!counterElement) {
      counterElement = document.createElement('small');
      counterElement.className = 'text-muted char-counter d-block mt-1';
      textarea.parentElement.appendChild(counterElement);
    }

    const updateCounter = function() {
      const currentLength = textarea.value.length;
      const remaining = charLimit - currentLength;
      counterElement.textContent = `${currentLength} / ${charLimit} characters`;

      // Change color if limit is exceeded
      if (remaining < 0) {
        counterElement.classList.remove('text-muted');
        counterElement.classList.add('text-danger');
        textarea.classList.add('is-invalid');
      } else if (remaining < 50) {
        counterElement.classList.remove('text-muted', 'text-danger');
        counterElement.classList.add('text-warning');
        textarea.classList.remove('is-invalid');
      } else {
        counterElement.classList.remove('text-danger', 'text-warning');
        counterElement.classList.add('text-muted');
        textarea.classList.remove('is-invalid');
      }
    };

    // Initialize counter
    updateCounter();

    // Update on input
    textarea.addEventListener('input', updateCounter);
    textarea.addEventListener('change', updateCounter);
  });

  // ============================================================================
  // 8. FORM VALIDATION ENHANCEMENT
  // ============================================================================
  const forms = document.querySelectorAll('form[novalidate]');
  forms.forEach(function(form) {
    form.addEventListener('submit', function(event) {
      // Remove previous validation states
      form.querySelectorAll('.form-control, .form-select').forEach(function(field) {
        field.classList.remove('is-invalid', 'is-valid');
      });

      // Check HTML5 validity
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();

        // Mark invalid fields
        form.querySelectorAll('.form-control, .form-select').forEach(function(field) {
          if (!field.checkValidity()) {
            field.classList.add('is-invalid');

            // Add feedback message if it doesn't exist
            if (!field.parentElement.querySelector('.invalid-feedback')) {
              const feedback = document.createElement('div');
              feedback.className = 'invalid-feedback d-block';

              if (field.type === 'email') {
                feedback.textContent = 'Please enter a valid email address.';
              } else if (field.required) {
                feedback.textContent = 'This field is required.';
              } else if (field.type === 'number') {
                feedback.textContent = 'Please enter a valid number.';
              } else {
                feedback.textContent = 'Please correct this field.';
              }

              field.parentElement.appendChild(feedback);
            }
          } else {
            field.classList.add('is-valid');
          }
        });
      } else {
        // Mark valid fields
        form.querySelectorAll('.form-control, .form-select').forEach(function(field) {
          field.classList.add('is-valid');
        });
      }
    });
  });

  // ============================================================================
  // ADDITIONAL: Real-time validation on field blur
  // ============================================================================
  const formFields = document.querySelectorAll('form .form-control, form .form-select');
  formFields.forEach(function(field) {
    field.addEventListener('blur', function() {
      // Remove previous states
      field.classList.remove('is-invalid', 'is-valid');

      // Remove old feedback
      const oldFeedback = field.parentElement.querySelector('.invalid-feedback');
      if (oldFeedback) {
        oldFeedback.remove();
      }

      // Check validity
      if (!field.checkValidity() && field.value) {
        field.classList.add('is-invalid');

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block';
        feedback.textContent = 'Please correct this field.';
        field.parentElement.appendChild(feedback);
      } else if (field.value) {
        field.classList.add('is-valid');
      }
    });
  });

});
