<?php
require_once '../config.php';
require_once '../helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = 'Admin';

if (!empty($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];

    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($first_name, $last_name);

        if ($stmt->fetch()) {
            $name = trim($first_name . ' ' . $last_name);
        }

        $stmt->close();
    }
}

$query = "SELECT * FROM events WHERE status = 'pending' ORDER BY event_date DESC";
$allEvents = mysqli_query($conn, $query);

if (!$allEvents) {
    die("Error fetching events: " . mysqli_error($conn));
}

function statusIcon($status)
{
    return match ($status) {
        'approved' => '<svg class="inline w-4 h-4 mr-1 text-green-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        'rejected' => '<svg class="inline w-4 h-4 mr-1 text-red-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
        default => '<svg class="inline w-4 h-4 mr-1 text-yellow-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/></svg>',
    };
}
?>

<div class="max-w-4xl mx-auto px-4" x-data="{ showModal: false, actionType: '', eventId: null }" x-cloak>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-[#1D503A]">Welcome, <?= htmlspecialchars($name) ?>!</h2>
        <p class="text-lg text-[#4A5D4C] border-b-2 pb-2 border-[#1D503A]">Manage your events.</p>
    </div>

    <?php if (mysqli_num_rows($allEvents) > 0): ?>
        <div class="overflow-x-auto bg-white rounded-xl shadow border border-[#1D503A]">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="bg-[#E5E1DB] text-xs text-[#1D503A] uppercase">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Created By</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
            </table>
            <!-- Scrollable table body -->
            <div class="max-h-[360px] overflow-y-auto">
                <table class="w-full text-sm text-left text-gray-700">
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($allEvents)): ?>
                            <?php
                            $status = $row['status'];
                            $statusColor = match ($status) {
                                'approved' => 'green',
                                'rejected' => 'red',
                                default => 'yellow',
                            };
                            ?>
                            <tr class="border-b hover:bg-[#FAF5EE]">
                                <td class="px-4 py-3 font-medium text-[#1D503A]"><?= htmlspecialchars($row['title']) ?></td>
                                <td class="px-4 py-3 text-[#1D503A]"><?= date('F j, Y', strtotime($row['event_date'])) ?></td>
                                <td class="px-4 py-3 max-w-xs truncate text-[#1D503A]" title="<?= htmlspecialchars($row['description']) ?>"><?= truncate($row['description']) ?></td>
                                <td class="px-4 py-3 text-[#1D503A]"><?= htmlspecialchars($row['user_email']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                    <?= $statusColor === 'green' ? 'bg-green-100 text-green-700' : ($statusColor === 'red' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                        <?= statusIcon($status) ?>
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center space-y-1">
                                    <?php if ($status === 'pending'): ?>
                                        <div class="inline-flex space-x-2">
                                            <!-- Approve -->
                                            <button
                                                type="button"
                                                class="px-4 py-1 rounded bg-[#1D503A] text-white hover:bg-[#15412B]"
                                                @click="showModal = true; actionType = 'approve'; eventId = <?= $row['id'] ?>;">
                                                Approve
                                            </button>
                                            <!-- Reject -->
                                            <button
                                                type="button"
                                                class="px-4 py-1 rounded bg-red-600 text-white hover:bg-red-700"
                                                @click="showModal = true; actionType = 'reject'; eventId = <?= $row['id'] ?>;">
                                                Reject
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>

                                    <!-- View Button -->
                                    <button
                                        type="button"
                                        class="view-details-btn px-4 py-1 rounded bg-[#E5E1DB] text-[#1D503A] hover:bg-[#D6D2CC]"
                                        data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
                                        data-date="<?= htmlspecialchars(date('F j, Y', strtotime($row['event_date'])), ENT_QUOTES) ?>"
                                        data-description="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>"
                                        data-user="<?= htmlspecialchars($row['user_email'], ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"
                                        data-attachment="<?= htmlspecialchars(basename($row['request_form_path']), ENT_QUOTES) ?>"
                                        title="View Event Details">
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
        <div class="text-center text-[#1D503A] select-none" role="alert" aria-live="polite"
            style="margin: 0.5rem auto; border: 2px dashed #1D503A; border-radius: 1rem; background-color: #FAF5EE; max-width: 900px; width: 100%; padding: 2rem 1rem;">
            <svg class="mx-auto mb-4 w-20 h-20 text-[#1D503A]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 2a2 2 0 0 1 2 2v3H6V4a2 2 0 0 1 2-2h8zm4 7v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h16zm-5 3H9v1h6v-1z" />
            </svg>
            <p class="text-lg font-semibold">No events requested.</p>
            <p class="text-sm text-[#4F766E]">Please check back later for updates.</p>
        </div>
    <?php endif; ?>

    <!-- Styled Approve/Reject Reason Modal -->
    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        style="backdrop-filter: blur(4px);"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="showModal = false">

        <div
            @click.away="showModal = false"
            class="bg-[#E5E1DB] rounded-3xl max-w-md w-full p-10 border border-[#CBD5E1] shadow-xl relative overflow-auto"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90">

            <button
                @click="showModal = false"
                class="absolute top-4 right-4 text-gray-600 hover:text-gray-800 text-2xl font-bold"
                aria-label="Close modal">&times;</button>

            <h2 class="text-2xl font-extrabold text-[#1D503A] mb-6 tracking-wide"
                x-text="actionType === 'approve' ? 'Approval Reason' : 'Rejection Reason'">
            </h2>

            <form action="update-event-status.php" method="POST" class="space-y-6">
                <input type="hidden" name="event_id" :value="eventId">
                <input type="hidden" name="action" :value="actionType">

                <div>
                    <label for="reason" class="block text-sm font-semibold text-[#1D503A] mb-2">Reason:</label>
                    <textarea
                        id="reason"
                        name="reason"
                        rows="4"
                        required
                        class="block w-full rounded-md border border-[#CBD5E1] p-3 shadow-sm focus:ring-[#1D503A] focus:border-[#1D503A] text-gray-800 leading-relaxed"
                        placeholder="Enter your reason here..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-shadow shadow-md">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="bg-[#1D503A] hover:bg-[#163b2c] text-white px-6 py-2 rounded-lg font-semibold transition-shadow shadow-md">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Styled Event View Details Modal -->
    <div id="event-modal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
        style="backdrop-filter: blur(4px);">
        <div class="bg-[#E5E1DB] rounded-3xl shadow-xl max-w-lg w-full mx-4 p-10 border border-[#CBD5E1] relative overflow-auto max-h-[90vh]">
            <button id="modal-close-btn"
                class="absolute top-4 right-4 text-gray-600 hover:text-gray-800 text-2xl font-bold"
                aria-label="Close modal">
                &times;
            </button>
            <h3 id="modal-title" class="text-3xl font-extrabold text-[#1D503A] mb-6 tracking-wide"></h3>

            <div class="mb-4 flex items-center space-x-2 text-[#1D503A] text-sm font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#3B6A49]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p id="modal-date" class="text-gray-800"></p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-[#1D503A] mb-2">Description:</label>
                <p id="modal-description"
                    class="text-gray-800 text-base leading-relaxed border-l-4 border-[#1D503A] pl-6 italic whitespace-pre-wrap"
                    style="line-height: 1.8;"></p>
            </div>

            <div class="mb-2">
                <label class="block text-sm font-semibold text-[#1D503A] mb-1">Created By:</label>
                <p id="modal-user" class="text-gray-700 text-sm"></p>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-semibold text-[#1D503A] mb-1">Status:</label>
                <p id="modal-status" class="text-sm font-semibold text-gray-800"></p>
            </div>
            <div id="modal-attachment-section" class="mt-6 hidden">
                <label class="block text-sm font-semibold text-[#1D503A] mb-2">Attachment:</label>
                <a id="modal-attachment-link"
                    href="#"
                    target="_blank"
                    download
                    class="inline-flex items-center px-4 py-2 bg-[#3B6A49] text-white text-sm font-semibold rounded-lg hover:bg-[#2F5A3F] transition duration-200 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                    </svg>
                    Download Attachment
                </a>
            </div>
        </div>
    </div>


    <script>
        // Truncate helper (if you use it anywhere)
        function truncate(text, length = 30) {
            return text.length <= length ? text : text.substring(0, length) + '...';
        }

        // Get modal elements once
        const modal = document.getElementById('event-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalDate = document.getElementById('modal-date');
        const modalDescription = document.getElementById('modal-description');
        const modalUser = document.getElementById('modal-user');
        const modalStatus = document.getElementById('modal-status');
        const attachmentSection = document.getElementById('modal-attachment-section');
        const attachmentLink = document.getElementById('modal-attachment-link');

        // Unified click handler
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Set modal values
                modalTitle.textContent = this.dataset.title;
                modalDate.textContent = this.dataset.date;
                modalDescription.textContent = this.dataset.description;
                modalUser.textContent = 'Requested by: ' + this.dataset.user;
                modalStatus.textContent = 'Status: ' + this.dataset.status.charAt(0).toUpperCase() + this.dataset.status.slice(1);

                // Handle attachment
                const attachmentPath = this.dataset.attachment;
                if (attachmentPath && attachmentPath.trim() !== '') {
                    attachmentLink.href = 'download.php?file=' + encodeURIComponent(attachmentPath);
                    attachmentSection.classList.remove('hidden');
                } else {
                    attachmentSection.classList.add('hidden');
                }

                // Show modal
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        // Close modal button
        document.getElementById('modal-close-btn').addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    </script>