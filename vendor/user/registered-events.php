<?php
require_once '../config.php';

if (!isset($_SESSION['email'])) {
  header('Location: ../landing-page.php');
  exit();
}

$email = $_SESSION['email'] ?? '';

// Fetch user's first and last name
$stmtName = $conn->prepare("SELECT first_name, last_name FROM users WHERE email = ?");
$stmtName->bind_param("s", $email);
$stmtName->execute();
$resultName = $stmtName->get_result();
$name = '';
if ($resultName && $resultName->num_rows > 0) {
  $row = $resultName->fetch_assoc();
  $name = $row['first_name'] . ' ' . $row['last_name'];
}

// Fetch only APPROVED events the user is registered in
$stmt = $conn->prepare("
    SELECT e.id, e.title, e.description, e.event_date
    FROM event_registrations er
    JOIN events e ON er.event_id = e.id
    WHERE er.user_email = ? AND er.status = 'approved'
    ORDER BY e.event_date ASC
");
$stmt->bind_param("s", $email);
$stmt->execute();
$events = $stmt->get_result();
?>

<div x-data="{ showModal: false, selectedEvent: {} }" class="pt-2 px-6 pb-6">
  <div class="max-w-4xl mx-auto">
    <div class="text-center mb-6">
      <h2 class="text-2xl text-center border-b-2 font-semibold mb-6 text-[#1D503A]" style="border-color: #1D503A;">Welcome, <?= htmlspecialchars($name) ?>!</h2>
    </div>

    <?php if ($events && $events->num_rows > 0): ?>
      <div class="overflow-x-auto bg-white rounded-xl shadow border" style="border-color: #1D503A;">
        <table class="min-w-full text-sm text-left text-gray-700">
          <thead class="bg-gray-100 border-b text-xs text-gray-500 uppercase sticky top-0 z-10">
            <tr>
              <th class="px-6 py-4">Title</th>
              <th class="px-6 py-4">Date</th>
              <th class="px-6 py-4">Description</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-center">View</th>
            </tr>
          </thead>
        </table>
        <div style="max-height: 360px; overflow-y: auto;">
          <table class="min-w-full text-sm text-left text-gray-700">
            <tbody>
              <?php while ($event = $events->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($event['title']) ?></td>
                  <td class="px-6 py-4"><?= htmlspecialchars($event['event_date']) ?></td>
                  <td class="px-6 py-4 max-w-xs overflow-hidden text-ellipsis whitespace-nowrap" title="<?= htmlspecialchars($event['description']) ?>">
                    <?= htmlspecialchars($event['description']) ?>
                  </td>
                  <td class="px-6 py-4">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full text-green-800 bg-green-100">Registered</span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <button
                      @click="selectedEvent = { title: '<?= addslashes($event['title']) ?>', date: '<?= addslashes($event['event_date']) ?>', description: `<?= addslashes($event['description']) ?>` }; showModal = true"
                      class="w-full sm:w-auto px-3 py-1.5 text-xs sm:text-sm font-semibold rounded-lg shadow hover:shadow-md transition duration-200"
                      style="background-color: #E5E1DB; color: #1D503A;"
                      @mouseover="$el.style.backgroundColor='#D6D2CC'; $el.style.color='#1D503A'"
                      @mouseout="$el.style.backgroundColor='#E5E1DB'; $el.style.color='#1D503A'">
                      View
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div
        class="text-center text-[#1D503A] select-none mt-12"
        role="alert"
        aria-live="polite"
        style="margin: 2rem auto; border: 2px dashed #1D503A; border-radius: 1rem; background-color: #FAF5EE; max-width: 1000px; width: 900px; height: 200px;">
        <svg class="mx-auto mb-4 w-20 h-20 text-[#1D503A]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 2a2 2 0 0 1 2 2v3H6V4a2 2 0 0 1 2-2h8zm4 7v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h16zm-5 3H9v1h6v-1z" />
        </svg>
        <p class="text-lg font-semibold">No registered events found.</p>
        <p class="text-sm text-[#4F766E]">Register now!</p>
      </div>
    <?php endif; ?>

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
  </div>
</div>