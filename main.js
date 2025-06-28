// Wait for the page to fully load
document.addEventListener('DOMContentLoaded', function () {
  // Smooth scroll for internal links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Animate cards on scroll into view
  const cards = document.querySelectorAll('.card');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate__fadeInUp', 'animate__animated');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.2
  });
  cards.forEach(card => observer.observe(card));

  // Add hover pop to buttons
  const buttons = document.querySelectorAll('button, .btn');
  buttons.forEach(btn => {
    btn.addEventListener('mouseenter', () => {
      btn.style.transform = 'scale(1.05)';
    });
    btn.addEventListener('mouseleave', () => {
      btn.style.transform = 'scale(1)';
    });
  });

  // Basic form input validation (example)
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function (e) {
      const requiredInputs = this.querySelectorAll('[required]');
      let allFilled = true;
      requiredInputs.forEach(input => {
        if (!input.value.trim()) {
          input.classList.add('is-invalid');
          allFilled = false;
        } else {
          input.classList.remove('is-invalid');
        }
      });
      if (!allFilled) {
        e.preventDefault();
      }
    });
  });

  // ===== NEW FEATURES BELOW =====

  // Scroll-to-top button functionality
  const scrollBtn = document.getElementById("scrollToTop");
  if (scrollBtn) {
    window.addEventListener("scroll", () => {
      scrollBtn.style.display = window.scrollY > 400 ? "block" : "none";
    });

    scrollBtn.addEventListener("click", () => {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  // Optional: Dark Mode Toggle
  const darkToggle = document.getElementById("darkModeToggle");
  if (darkToggle) {
    darkToggle.addEventListener("click", () => {
      document.body.classList.toggle("bg-dark");
      document.body.classList.toggle("text-white");
    });
  }

  // Optional: Bootstrap-style validation for .needs-validation class
  const bootstrapForms = document.querySelectorAll('.needs-validation');
  Array.from(bootstrapForms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        alert("Please fill out all required fields correctly.");
      }
      form.classList.add('was-validated');
    }, false);
  });
});
