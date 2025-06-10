<?php
session_start();
require_once 'config.php'; // Ensure this initializes $conn for DB connection

// Load session flash messages and defaults for UI feedback
$activeForm = $_SESSION['active_form'] ?? 'login';
$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'register' => $_SESSION['register_error'] ?? ''
];
$registerSuccess = $_SESSION['register_success'] ?? '';
$notApprovedMessage = $_SESSION['not_approved'] ?? null;

// Determine if modal should be opened based on errors or messages
$modal_open = !empty($errors['login']) || !empty($errors['register']) || !empty($registerSuccess) || !empty($notApprovedMessage);

// Flag to show pending approval modal for admin accounts
$showPendingModal = isset($_SESSION['role'], $_SESSION['status']) &&
  $_SESSION['role'] === 'admin' &&
  $_SESSION['status'] === 'pending';

// Clear flash messages so they don’t persist on page reload
unset(
  $_SESSION['login_error'],
  $_SESSION['register_error'],
  $_SESSION['register_success'],
  $_SESSION['active_form'],
  $_SESSION['not_approved']
);

// Helper function to display error messages
function showError(string $msg): string
{
  return $msg ? "<div class='text-red-600 text-sm font-medium'>{$msg}</div>" : '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GC Event Registration</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="//unpkg.com/alpinejs" defer></script>
  <style>
    html,
    body {
      height: 100%;
      overflow-x: hidden;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #E5E1DB;
      color: #1D503A;
    }

    .fade-in {
      animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.95);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }
  </style>
</head>

<body class="min-h-screen">

  <!-- Navbar -->
  <header class="w-full shadow-md px-4 sm:px-6 lg:px-12 py-4 bg-[#E5E1DB]">
    <div class="max-w-[1440px] mx-auto flex items-center justify-between">
      <div class="flex items-center gap-4">
        <img src="https://c.animaapp.com/ma2lbp83eVuDt3/img/image-18.png" alt="GC Logo" class="w-14 h-14 object-cover rounded-full shadow-sm" />
        <h1 class="text-xl sm:text-2xl font-semibold" style="color: #1D503A;">GC EVENT REGISTRATION</h1>
      </div>
      <button onclick="openModal('login')" class="bg-[#1D503A] hover:bg-[#144124] text-white px-6 py-2 rounded-full font-['PT Sans'] text-base transition shadow-md">
        LOGIN
      </button>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="w-full px-4 sm:px-6 lg:px-12 py-20 sm:py-24">
    <div class="max-w-[1440px] mx-auto flex flex-col-reverse md:flex-row items-center justify-between text-center md:text-left gap-8">
      <div class="max-w-xl">
        <h2 class="text-3xl md:text-5xl font-normal leading-tight drop-shadow-sm" style="color: #1D503A;">
          Welcome <span class="font-semibold" style="color: #1D503A;">GCians!</span>
        </h2>
        <p class="mt-4 text-lg" style="color: #1D503A;">Register and participate in upcoming events easily. Stay updated and connected with campus activities.</p>
        <div class="mt-6">
          <button onclick="openModal('login')" class="bg-[#1D503A] hover:bg-[#144124] text-white px-8 py-3 rounded-full text-lg font-['Zilla Slab'] transition shadow-md">
            Get Started
          </button>
        </div>
      </div>
      <div class="max-w-md w-full">
        <img src="https://c.animaapp.com/ma2lbp83eVuDt3/img/logo-landing-page.png" alt="Landing Logo" class="w-full h-auto" />
      </div>
    </div>
  </section>

  <!-- Main Modal -->
  <div id="modal-overlay" class="fixed inset-0 z-50 <?= $modal_open ? '' : 'hidden' ?> bg-black bg-opacity-50 flex items-center justify-center px-4 overflow-auto">
    <div class="rounded-2xl shadow-2xl w-full max-w-lg relative p-8 sm:p-10 fade-in" style="background-color: #E5E1DB; color: #1D503A;">
      <button onclick="closeModal()" class="absolute top-4 right-5 text-[#1D503A] hover:text-[#144124] text-2xl">&times;</button>

      <!-- Tabs -->
      <div class="flex justify-center mb-6">
        <button id="tab-login" onclick="switchForm('login')"
          class="px-6 py-2 text-sm sm:text-base font-semibold rounded-full transition
      <?= $activeForm === 'login' ? 'bg-[#1D503A] text-white shadow-md' : 'bg-[#C9C3B9] text-[#1D503A] hover:bg-[#B0A998]' ?>">
          Login
        </button>
      </div>

      <!-- Login Form -->
      <div id="login-form" class="space-y-6 <?= $activeForm === 'login' ? '' : 'hidden' ?>">
        <h2 class="text-2xl font-bold text-center" style="color: #1D503A;">Welcome Back</h2>
        <?= showError($errors['login']); ?>
        <form id="loginForm" action="login-register.php" method="post" class="space-y-5">
          <div>
            <label for="email" class="block text-sm font-medium mb-1" style="color: #1D503A;">Email Address</label>
            <input type="email" name="email" id="email" placeholder="your@gordoncollege.edu.ph" required
              class="w-full px-4 py-3 border border-[#B0A998] rounded-lg shadow-sm focus:ring-2 focus:ring-[#1D503A] focus:border-[#1D503A] transition" />
          </div>
          <div x-data="{ show: false }" class="relative">
            <label for="password" class="block text-sm font-medium mb-1" style="color: #1D503A;">Password</label>
            <input :type="show ? 'text' : 'password'" name="password" id="password" placeholder="••••••••••" required
              class="w-full px-4 py-3 border border-[#B0A998] rounded-lg shadow-sm focus:ring-2 focus:ring-[#1D503A] focus:border-[#1D503A] pr-12 transition" />

            <!-- Toggle button -->
            <button type="button" @click="show = !show"
              class="absolute top-9 right-3 text-[#1D503A] focus:outline-none">
              <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-.274.927-.69 1.794-1.23 2.57M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18" />
              </svg>
              <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7C20.268 16.057 16.478 19 12 19c-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>

          <!-- Terms and Forgot Password aligned horizontally -->
          <div class="flex justify-between items-center mb-4">
            <div class="flex items-center space-x-2">
              <input type="checkbox" id="terms" name="terms" class="mt-0.5" />
              <label for="terms" class="text-sm text-[#1D503A] cursor-pointer select-none">
                I agree to the
                <button type="button" id="terms-link" class="text-[#1D503A] underline hover:text-[#144124] focus:outline-none">
                  Terms and Conditions
                </button>
              </label>
            </div>
            <div>
              <a href="forgot-password.php" class="text-sm text-[#1D503A] hover:underline hover:text-[#144124]">Forgot Password?</a>
            </div>
          </div>

          <button type="submit" name="login"
            class="w-full py-3 bg-[#1D503A] hover:bg-[#144124] text-white font-semibold rounded-lg shadow-md transition">
            Login
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Custom Scrollbar Styles (Scoped to Modal) -->
  <style>
    /* WebKit Scrollbar (Chrome, Safari, Edge) */
    #terms-modal .scrollbar::-webkit-scrollbar {
      width: 8px;
    }

    #terms-modal .scrollbar::-webkit-scrollbar-track {
      background: transparent;
      border-radius: 8px;
    }

    #terms-modal .scrollbar::-webkit-scrollbar-thumb {
      background-color: #1D503A;
      border-radius: 8px;
      border: 2px solid #E5E1DB;
    }

    /* Firefox Scrollbar */
    #terms-modal .scrollbar {
      scrollbar-width: thin;
      scrollbar-color: #1D503A transparent;
    }
  </style>

  <!-- Terms and Conditions Modal -->
  <div id="terms-modal" class="fixed inset-0 z-[60] hidden bg-black bg-opacity-60 flex items-center justify-center px-4">
    <div
      class="max-w-2xl w-full rounded-xl shadow-lg p-6 relative overflow-y-auto max-h-[90vh] scrollbar fade-in"
      role="dialog" aria-modal="true" aria-labelledby="terms-title" tabindex="-1"
      style="background-color: #E5E1DB; color: #1D503A; box-shadow: 0 10px 15px -3px rgba(29,80,58,0.3), 0 4px 6px -4px rgba(29,80,58,0.2);">

      <!-- Close Button -->
      <button id="terms-close"
        class="absolute top-4 right-5 text-3xl font-bold hover:text-[#144124] focus:outline-none"
        style="color: #1D503A;">
        &times;
      </button>

      <!-- Modal Title -->
      <h3 id="terms-title" class="text-xl font-semibold mb-4" style="color: #1D503A;">Terms and Conditions</h3>

      <!-- Terms Content -->
      <div class="space-y-4 text-sm leading-relaxed">
        <p><strong>Terms and Conditions</strong></p>
        <p>Welcome to GC Event Registration! Please review the following terms carefully before using our platform.</p>
        <p><strong>Eligibility:</strong> Only currently enrolled students with a valid Gordon College email address are permitted to register and participate.</p>
        <p><strong>Account Security:</strong> You are responsible for keeping your login credentials confidential and secure. Any activity under your account is your responsibility.</p>
        <p><strong>Event Participation:</strong> By registering for any event, you agree to follow all event-specific rules, guidelines, and codes of conduct.</p>
        <p><strong>Privacy and Data Protection:</strong> Your personal information is collected and processed in accordance with our Privacy Policy. We are committed to protecting your data.</p>
        <p><strong>Account Termination:</strong> We reserve the right to suspend or terminate your access if you violate any of our terms, misuse the platform, or engage in misconduct.</p>
        <p><strong>Updates to Terms:</strong> We may modify these terms periodically. Continued use of the platform after updates implies your acceptance of the revised terms.</p>
        <p><strong>Support and Contact:</strong> For questions, issues, or feedback, please contact us at <a href="mailto:support@gcevent.com" class="text-blue-600 underline">support@gcevent.com</a>.</p>
        <p>Thank you for being an active part of the GCians community!</p>
      </div>

      <!-- Accept Button -->
      <button id="terms-accept"
        class="mt-6 w-full py-3 rounded-lg font-semibold transition"
        style="background-color: #1D503A; color: white;"
        onmouseover="this.style.backgroundColor='#144124'"
        onmouseout="this.style.backgroundColor='#1D503A'">
        I Accept
      </button>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    function openModal(tab = 'login') {
      document.getElementById('modal-overlay').classList.remove('hidden');
      switchForm(tab);
    }

    function closeModal() {
      document.getElementById('modal-overlay').classList.add('hidden');
      closeNotApprovedModal();
    }

    function switchForm(tab) {
      const loginForm = document.getElementById('login-form');
      // No register form anymore

      const tabLogin = document.getElementById('tab-login');
      // No register tab anymore

      loginForm.classList.toggle('hidden', tab !== 'login');

      // For login tab active state
      tabLogin.classList.toggle('bg-[#1D503A]', tab === 'login'); // Dark green bg
      tabLogin.classList.toggle('text-white', tab === 'login'); // White text
      tabLogin.classList.toggle('bg-[#E5E1DB]', tab !== 'login'); // Cream bg when inactive
      tabLogin.classList.toggle('text-[#1D503A]', tab !== 'login'); // Green text when inactive
    }

    function closeNotApprovedModal() {
      const modal = document.getElementById('not-approved-modal');
      if (modal) {
        modal.remove();
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const termsCheckbox = document.getElementById('terms');
      const termsModal = document.getElementById('terms-modal');
      const termsCloseBtn = document.getElementById('terms-close');
      const termsAcceptBtn = document.getElementById('terms-accept');
      const termsLink = document.getElementById('terms-link');
      const loginForm = document.getElementById('loginForm');

      let termsJustClicked = false;

      // Show modal if checkbox is checked manually
      termsCheckbox.addEventListener('change', () => {
        if (termsCheckbox.checked && !termsJustClicked) {
          termsCheckbox.checked = false; // uncheck until user accepts
          termsModal.classList.remove('hidden');
          termsModal.focus();
        }
        termsJustClicked = false;
      });

      // When clicking the link text, open modal without changing checkbox
      termsLink.addEventListener('click', (e) => {
        e.preventDefault();
        termsJustClicked = true;
        termsModal.classList.remove('hidden');
        termsModal.focus();
      });

      // Accept button inside modal
      termsAcceptBtn.addEventListener('click', () => {
        termsModal.classList.add('hidden');
        termsCheckbox.checked = true;
      });

      // Close button inside modal
      termsCloseBtn.addEventListener('click', () => {
        termsModal.classList.add('hidden');
        termsCheckbox.checked = false;
      });

      // Click outside modal closes it
      termsModal.addEventListener('click', (e) => {
        if (e.target === termsModal) {
          termsModal.classList.add('hidden');
          termsCheckbox.checked = false;
        }
      });

      // Form submission validation
      loginForm.addEventListener('submit', function(e) {
        if (!termsCheckbox.checked) {
          e.preventDefault();
          alert('You must agree to the Terms and Conditions before logging in.');
          termsCheckbox.focus();
        }
      });
    });

    // On page load, open modal if error or register success or not approved message exists
    window.onload = function() {
      <?php if ($modal_open): ?>
        openModal('<?= $activeForm ?>');
      <?php endif; ?>
    }
  </script>

</body>

</html>
