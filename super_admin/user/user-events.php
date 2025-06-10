<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once('../config.php');

// Get user info from session
$name  = $_SESSION['name'] ?? '';
$email = $_SESSION['email'] ?? '';

// Get profile image
$imagePath = 'default.png';
if ($email) {
  $query = "SELECT profile_image FROM users WHERE email = ?";
  if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($imagePath);
    $stmt->fetch();
    $stmt->close();
  }
}

// Fetch approved events and check if user already registered
$events = [];
if ($email) {
  $query = "
  SELECT e.id, e.title, e.description, e.event_date
  FROM events e
  WHERE e.status = 'approved'
    AND NOT EXISTS (
      SELECT 1 FROM event_registrations r 
      WHERE r.event_id = e.id AND r.user_email = ?
    )
  ORDER BY e.event_date ASC
";

  if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
}

// Success message flag
$registrationMessage = $_SESSION['flash_registration'] ?? '';
unset($_SESSION['flash_registration']); // Clear flash after use
?>

<section
  x-data="{
    showSuccessModal: <?= $registrationMessage ? 'true' : 'false' ?>,
    showRegisterForm: false,
    showModal: false,
    selectedEvent: {},
    course: '',
    year: '',
    block: ''
  }"
  x-init="
    if(showSuccessModal) {
      setTimeout(() => {
        showSuccessModal = false;
        const url = new URL(window.location);
        url.hash = '';
        window.history.replaceState({}, document.title, url);
      }, 4000)
    }
  "
  class="pt-2 md:pt-2 px-6 md:px-10 mt-0 max-w-6xl mx-auto">

  <!-- Success Message Modal -->
  <div
    x-show="showSuccessModal"
    x-transition
    @click.away="showSuccessModal = false"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    style="display: none;">
    <div
      @click.stop
      class="relative bg-green-100 text-green-800 px-6 py-4 rounded shadow-lg max-w-md text-center font-semibold">
      <?= htmlspecialchars($registrationMessage) ?>
      <button
        @click="showSuccessModal = false"
        class="absolute top-2 right-2 text-green-800 hover:text-green-900 font-bold text-xl leading-none focus:outline-none"
        aria-label="Close modal"
        title="Close">&times;</button>
    </div>
  </div>
  <div class="max-w-4xl mx-auto px-4" x-data="{ showModal: false, actionType: '', eventId: null }" x-cloak>
    <div class="text-center mb-6">
      <h2 class="text-2xl font-semibold text-[#1D503A]">Available Events</h2>
      <p class="text-lg text-[#4A5D4C] border-b-2 pb-2 border-[#1D503A]">Manage your registration</p>
    </div>

    <?php if (!empty($events)): ?>
      <div class="overflow-x-auto bg-white rounded-xl shadow border" style="border-color: #1D503A;">
        <table class="min-w-full text-sm text-left text-gray-700">
          <thead class="bg-gray-100 border-b text-xs text-gray-500 uppercase">
            <tr>
              <th class="px-6 py-4">Title</th>
              <th class="px-6 py-4">Date</th>
              <th class="px-6 py-4">Description</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-center">Actions</th>
            </tr>
          </thead>
        </table>
        <div style="max-height: 360px; overflow-y: auto;">
          <table class="min-w-full text-sm text-left text-gray-700">
            <tbody>
              <?php foreach ($events as $event): ?>
                <?php
                // Check registration status
                $statusMessage = '';
                $stmt = $conn->prepare("SELECT status FROM event_registrations WHERE user_email = ? AND event_id = ?");
                $stmt->bind_param('si', $email, $event['id']);
                $stmt->execute();
                $stmt->bind_result($status);
                if ($stmt->fetch()) {
                  $statusMessage = match ($status) {
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    default     => 'Pending Approval',
                  };
                }
                $stmt->close();
                ?>
                <tr class="border-b border-gray-200 hover:bg-gray-50 transition duration-200 text-sm">
                  <!-- Title -->
                  <td class="px-4 py-3 sm:px-6 sm:py-4 font-medium text-gray-900 align-middle whitespace-nowrap">
                    <?= htmlspecialchars($event['title']) ?>
                  </td>

                  <!-- Event Date -->
                  <td class="px-4 py-3 sm:px-6 sm:py-4 text-gray-700 align-middle whitespace-nowrap">
                    <?= htmlspecialchars($event['event_date']) ?>
                  </td>

                  <!-- Description -->
                  <td class="px-4 py-3 sm:px-6 sm:py-4 text-gray-600 align-middle max-w-[12rem] sm:max-w-sm truncate">
                    <?= htmlspecialchars($event['description']) ?>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3 sm:px-6 sm:py-4 align-middle">
                    <?php if ($statusMessage): ?>
                      <?php
                      $statusColor = match ($statusMessage) {
                        'Approved' => 'bg-green-100 text-green-800',
                        'Rejected' => 'bg-red-100 text-red-800',
                        default    => 'bg-yellow-100 text-yellow-800',
                      };
                      ?>
                      <span class="inline-block px-3 py-1 text-xs font-medium rounded-full <?= $statusColor ?>">
                        <?= $statusMessage ?>
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400 italic text-xs">Not Registered</span>
                    <?php endif; ?>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-3 sm:px-6 sm:py-4 text-center align-middle">
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                      <?php if (!$statusMessage): ?>
                        <button
                          @click="selectedEvent = { id: <?= $event['id'] ?>, title: '<?= addslashes($event['title']) ?>', date: '<?= addslashes($event['event_date']) ?>', description: `<?= addslashes($event['description']) ?>` }; showRegisterForm = true"
                          class="w-full sm:w-auto px-3 py-1.5 text-xs sm:text-sm font-semibold rounded-lg shadow hover:shadow-md transition duration-200"
                          style="background-color: #1D503A; color: #E5E1DB;"
                          @mouseover="$el.style.backgroundColor='#17412F'; $el.style.color='#ffffff'"
                          @mouseout="$el.style.backgroundColor='#1D503A'; $el.style.color='#E5E1DB'">
                          Register
                        </button>
                      <?php endif; ?>
                      <button
                        @click="selectedEvent = { title: '<?= addslashes($event['title']) ?>', date: '<?= addslashes($event['event_date']) ?>', description: `<?= addslashes($event['description']) ?>` }; showModal = true"
                        class="w-full sm:w-auto px-3 py-1.5 text-xs sm:text-sm font-semibold rounded-lg shadow hover:shadow-md transition duration-200"
                        style="background-color: #E5E1DB; color: #1D503A;"
                        @mouseover="$el.style.backgroundColor='#D6D2CC'; $el.style.color='#1D503A'"
                        @mouseout="$el.style.backgroundColor='#E5E1DB'; $el.style.color='#1D503A'">
                        View
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div
        class="text-center text-[#1D503A] select-none"
        role="alert"
        aria-live="polite"
        style="margin: 0.5rem auto; border: 2px dashed #1D503A; border-radius: 1rem; background-color: #FAF5EE; max-width: 900px; width: 100%; padding: 2rem 1rem;">
        <svg class="mx-auto mb-4 w-20 h-20 text-[#1D503A]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 2a2 2 0 0 1 2 2v3H6V4a2 2 0 0 1 2-2h8zm4 7v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h16zm-5 3H9v1h6v-1z" />
        </svg>
        <p class="text-lg font-semibold">No events available.</p>
        <p class="text-sm text-[#4F766E]">Please check back later.</p>
      </div>
    <?php endif; ?>

    <!-- Registration Modal -->
    <div
      x-show="showRegisterForm"
      x-transition
      @click.away="showRegisterForm = false"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      style="display: none;">
      <div class="bg-[#E5E1DB] rounded-2xl shadow-2xl w-full max-w-lg p-8 relative">
        <h3 class="text-2xl font-bold text-[#1D503A] mb-6 text-center">Event Registration</h3>
        <form action="register-event.php" method="POST" class="space-y-5">
          <input type="hidden" name="event_id" :value="selectedEvent.id">

          <div>
            <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
            <input
              type="text"
              name="course"
              x-model="course"
              placeholder="e.g., BIST"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1D503A] focus:border-transparent transition shadow-sm" />
          </div>

          <div>
            <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
            <input
              type="text"
              name="year"
              x-model="year"
              placeholder="e.g., 1st Year"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1D503A] focus:border-transparent transition shadow-sm" />
          </div>

          <div>
            <label for="block" class="block text-sm font-medium text-gray-700 mb-1">Block</label>
            <input
              type="text"
              name="block"
              x-model="block"
              placeholder="e.g., D"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1D503A] focus:border-transparent transition shadow-sm" />
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showRegisterForm = false"
              class="px-5 py-2 rounded-lg text-sm font-medium bg-gray-300 text-gray-800 hover:bg-gray-400 transition">
              Cancel
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-lg text-sm font-medium bg-[#1D503A] text-white hover:bg-[#174030] transition">
              Submit Registration
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Modal -->
    <div
      x-show="showModal"
      x-cloak
      tabindex="-1"
      role="dialog"
      aria-modal="true"
      aria-labelledby="viewModalTitle"
      class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-60 z-50"
      style="backdrop-filter: blur(4px);">
      <div
        @click.outside="showModal = false"
        @keydown.escape.window="showModal = false"
        class="bg-[#E5E1DB] rounded-3xl shadow-xl max-w-lg w-full p-10 border border-[#CBD5E1] overflow-auto max-h-[90vh]">
        <h3
          id="viewModalTitle"
          class="text-3xl font-extrabold text-[#1D503A] mb-8 tracking-wide">
          Event Details
        </h3>

        <div class="mb-6">
          <label class="block text-sm font-semibold text-[#1D503A] mb-1">Title:</label>
          <p class="text-gray-800 text-lg" x-text="selectedEvent.title"></p>
        </div>

        <div class="mb-6 flex items-center space-x-3 text-[#1D503A] text-sm font-semibold">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#3B6A49]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-gray-800" x-text="selectedEvent.date"></p>
        </div>

        <div class="mb-10">
          <label class="block text-sm font-semibold text-[#1D503A] mb-2">Description:</label>
          <p
            class="text-gray-800 text-base leading-relaxed border-l-4 border-[#1D503A] pl-6 italic whitespace-pre-wrap"
            x-text="selectedEvent.description"
            style="line-height: 1.8;"></p>
        </div>

        <div class="flex justify-end">
          <button
            @click="showModal = false"
            class="bg-[#1D503A] hover:bg-[#174030] text-white px-8 py-3 rounded-lg font-semibold text-sm transition-shadow shadow-md hover:shadow-lg">
            Close
          </button>
        </div>
      </div>
    </div>

</section>