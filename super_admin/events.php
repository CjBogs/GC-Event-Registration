<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once '../config.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../landing-page.php");
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'];

// Get profile image
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($imagePath);
$stmt->fetch();
$stmt->close();
$imagePath = $imagePath ?: 'default.png';

// Count pending registrations (note: your original code queried for 'approved', maybe you want 'pending' here)
$pendingQuery = $conn->query("SELECT COUNT(*) AS count FROM event_registrations WHERE status = 'approved'");
$pendingCount = $pendingQuery->fetch_assoc()['count'] ?? 0;

// Get all approved events
$stmt = $conn->prepare("SELECT id, title, description, event_date, status FROM events WHERE status = 'approved' ORDER BY event_date ASC");
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();
?>

<div class="max-w-4xl mx-auto">
    <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-[#1D503A]">List Events</h2>
        <p class="text-lg text-[#4A5D4C] border-b-2 pb-2 border-[#1D503A]">
            View and manage all of the existing approved events.
        </p>
    </div>
</div>

<!-- Alpine.js State Wrapper -->
<div x-data="eventManager()" x-init="initAlpine()" class="min-h-screen">

    <main class="flex-1 overflow-y-auto p-6 max-w-6xl mx-auto">
        <section>

            <?php if ($events->num_rows > 0): ?>
                <div class="overflow-x-auto bg-white rounded-xl shadow border border-[#1D503A]">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-[#E5E1DB] border-b text-xs text-[#1D503A] uppercase">
                            <tr>
                                <th class="px-6 py-4">Title</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Description</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = date('Y-m-d');
                            while ($event = $events->fetch_assoc()):
                                $statusColor = match ($event['status']) {
                                    'approved' => 'green',
                                    'rejected' => 'red',
                                    default => 'yellow',
                                };
                                // Check if event date is past
                                $isPastDate = ($event['event_date'] < $today);
                            ?>
                                <tr class="border-b hover:bg-[#FAF5EE]">
                                    <td class="px-6 py-4 font-medium text-[#1D503A]"><?= htmlspecialchars($event['title']) ?></td>
                                    <td class="px-6 py-4 text-[#1D503A] flex items-center space-x-2">
                                        <span><?= htmlspecialchars($event['event_date']) ?></span>
                                        <?php if ($isPastDate): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Past event date">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 max-w-sm truncate text-[#1D503A]"><?= htmlspecialchars($event['description']) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                                <?= $statusColor === 'green' ? 'bg-green-100 text-green-700' : ($statusColor === 'red' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                            <?= htmlspecialchars($event['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <!-- View Details Button -->
                                        <button
                                            class="btn-view px-4 py-1 rounded bg-[#E5E1DB] text-[#1D503A] hover:bg-[#D6D2CC]"
                                            @click="openViewModal(<?= htmlspecialchars(json_encode($event)) ?>)"
                                            title="View Event Details">
                                            View
                                        </button>

                                        <!-- Edit always allowed -->
                                        <button
                                            class="btn-primary px-4 py-1 rounded hover:bg-[#15412B]"
                                            @click="openEditModal(<?= htmlspecialchars(json_encode($event)) ?>)"
                                            title="Edit Event">
                                            Edit
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            @click="openDeleteModal(<?= (int)$event['id'] ?>)"
                                            class="bg-red-600 text-white px-4 py-1 rounded hover:bg-red-700"
                                            title="Delete Event">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
                    <p class="text-lg font-semibold">There are no approved events at the moment.</p>
                    <p class="text-sm text-[#4F766E]">Please check back later.</p>
                </div>
            <?php endif; ?>

        </section>

        <!-- Edit Modal -->
        <div
            x-show="showEditModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            style="backdrop-filter: blur(4px);">
            <div
                @click.outside="closeEditModal()"
                class="bg-[#E5E1DB] rounded-3xl shadow-xl max-w-lg w-full p-10 border border-[#CBD5E1] overflow-auto max-h-[90vh]">
                <h2 class="text-3xl font-extrabold text-[#1D503A] mb-8 tracking-wide">Edit Event</h2>
                <form action="update-event.php" method="POST" class="space-y-6" @submit.prevent="submitEditForm">
                    <input type="hidden" name="id" :value="selectedEvent.id">
                    <div>
                        <label class="block text-sm font-semibold text-[#1D503A] mb-1">Title</label>
                        <input type="text" name="title" x-model="selectedEvent.title"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:border-[#1D503A]" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#1D503A] mb-1">Description</label>
                        <textarea name="description" x-model="selectedEvent.description"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:border-[#1D503A]"
                            rows="4" required></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#1D503A] mb-1">Event Date</label>
                        <input type="text" name="event_date" x-ref="eventDateInput" x-model="selectedEvent.event_date"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:border-[#1D503A]" required readonly>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" @click="closeEditModal()"
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-400 transition-shadow shadow-md hover:shadow-lg">Cancel</button>
                        <button type="submit"
                            class="bg-[#1D503A] hover:bg-[#174030] text-white px-6 py-2 rounded-lg font-semibold transition-shadow shadow-md hover:shadow-lg">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div
            x-show="showDeleteModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            style="backdrop-filter: blur(4px);">
            <div
                @click.outside="showDeleteModal = false"
                class="bg-[#E5E1DB] rounded-3xl shadow-xl max-w-md w-full p-10 border border-[#CBD5E1]">
                <h2 class="text-3xl font-extrabold text-[#1D503A] mb-6 tracking-wide">Confirm Delete</h2>
                <p class="text-gray-800 text-base mb-8 leading-relaxed">
                    Are you sure you want to delete this event? This action cannot be undone.
                </p>
                <form action="delete-event.php" method="POST" class="flex justify-end space-x-3">
                    <input type="hidden" name="id" :value="deleteEventId">
                    <button type="button" @click="showDeleteModal = false"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-400 transition-shadow shadow-md hover:shadow-lg">Cancel</button>
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-shadow shadow-md hover:shadow-lg">Delete</button>
                </form>
            </div>
        </div>

        <!-- View Modal -->
        <div
            x-show="showViewModal"
            x-cloak
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="viewModalTitle"
            class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
            style="backdrop-filter: blur(4px);">
            <div
                @click.outside="showViewModal = false"
                @keydown.escape.window="showViewModal = false"
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
                    <p class="text-gray-800" x-text="selectedEvent.event_date"></p>
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
                        @click="showViewModal = false"
                        class="bg-[#1D503A] hover:bg-[#174030] text-white px-8 py-3 rounded-lg font-semibold text-sm transition-shadow shadow-md hover:shadow-lg">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </main>
</div> <!-- Alpine wrapper end -->

<!-- Include Flatpickr CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Alpine.js Component -->
<script>
    function eventManager() {
        return {
            showEditModal: false,
            showDeleteModal: false,
            showViewModal: false,
            selectedEvent: {},
            deleteEventId: null,
            fpInstance: null,

            initAlpine() {},

            openEditModal(event) {
                this.selectedEvent = event;
                this.showEditModal = true;

                this.$nextTick(() => {
                    if (this.fpInstance) {
                        this.fpInstance.destroy();
                    }

                    const today = new Date();
                    const todayMidnight = new Date(today);
                    todayMidnight.setHours(0, 0, 0, 0);

                    this.fpInstance = flatpickr(this.$refs.eventDateInput, {
                        dateFormat: "Y-m-d",
                        defaultDate: this.selectedEvent.event_date,
                        minDate: today,
                        disableMobile: true,
                        onChange: (selectedDates, dateStr) => {
                            this.selectedEvent.event_date = dateStr;
                        },
                        onDayCreate: function(dObj, dStr, fp, dayElem) {
                            const date = dayElem.dateObj;

                            if (date < todayMidnight) {
                                dayElem.classList.add("flatpickr-disabled");
                                dayElem.setAttribute("aria-disabled", "true");

                                const xMark = document.createElement("span");
                                xMark.textContent = "×";
                                xMark.className = "text-red-600 text-xs font-bold absolute top-1 right-1";

                                dayElem.style.position = "relative";
                                dayElem.appendChild(xMark);
                            }
                        }
                    });
                });
            },

            closeEditModal() {
                this.showEditModal = false;
                if (this.fpInstance) {
                    this.fpInstance.destroy();
                }
            },

            openDeleteModal(id) {
                this.deleteEventId = id;
                this.showDeleteModal = true;
            },

            openViewModal(event) {
                this.selectedEvent = event;
                this.showViewModal = true;
            },

            submitEditForm() {
                document.querySelector("form[action='update-event.php']").submit();
            }
        };
    }
</script>