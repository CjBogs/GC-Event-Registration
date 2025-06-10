<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config.php');

$allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$allowedExtensions = ['pdf', 'doc', 'docx'];

if (isset($_FILES['document'])) {
    $fileType = $_FILES['document']['type'];
    $fileName = $_FILES['document']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
        die("Error: Only PDF and DOC/DOCX files are allowed.");
    }

    // OPTIONAL: Handle file upload
    $uploadDir = '../uploads/';
    $targetFile = $uploadDir . basename($fileName);

    if (move_uploaded_file($_FILES['document']['tmp_name'], $targetFile)) {
        $_SESSION['flash_success'] = 'File uploaded successfully.';
    } else {
        die("Error: File upload failed.");
    }
}

// Grab and clear flash message from session
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>


<div class="max-w-4xl mx-auto px-4" x-data="{ showModal: false, actionType: '', eventId: null }" x-cloak>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-[#1D503A]">Create an Event.</h2>
        <p class="text-lg text-[#4A5D4C] border-b-2 pb-2 border-[#1D503A]"></p>
    </div>

    <div
        x-data="{ showSuccessModal: <?= $flashSuccess ? 'true' : 'false' ?> }"
        x-init="if (showSuccessModal) { setTimeout(() => { showSuccessModal = false; }, 4000); }"
        class="max-w-3xl mx-auto px-6">

        <!-- Success Modal -->
        <div x-show="showSuccessModal" x-transition x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            style="display: none;"
            role="alert"
            aria-live="assertive"
            aria-label="Success message">
            <div
                class="relative bg-green-50 border border-green-400 text-green-800 px-6 py-4 rounded-lg shadow-lg max-w-md text-center font-semibold flex items-center gap-3 select-none"
                role="alert"
                aria-describedby="success-message-text">

                <!-- Success Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="#1D503A" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                    <circle cx="12" cy="12" r="9" stroke="#1D503A" stroke-width="2" fill="none" />
                </svg>

                <div class="flex-1 text-left" id="success-message-text">
                    <span class="block font-bold text-[#1D503A] text-lg mb-1">Success</span>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>

                <button @click="showSuccessModal = false"
                    class="text-[#1D503A] hover:text-[#144124] font-bold text-xl leading-none focus:outline-none"
                    title="Close success message"
                    aria-label="Close success message">
                    &times;
                </button>
            </div>
        </div>

        <form
            action="submit-request.php"
            method="POST"
            enctype="multipart/form-data"
            class="bg-white w-full max-w-[750px] p-5 rounded-xl shadow-md border overflow-auto max-h-[580px] overflow-y-auto"
            style="border-color: #1D503A;">

            <div class="mb-0">
                <label for="title" class="block text-gray-700 font-medium flex items-center gap-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i> Event Title
                </label>
                <input type="text" name="title" id="title" required
                    class="w-full mt-1 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1D503A]" />
            </div>

            <div class="mb-0">
                <label for="description" class="block text-gray-700 font-medium flex items-center gap-1">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Description
                </label>
                <textarea name="description" id="description" rows="4" required
                    class="w-full mt-1 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1D503A]"></textarea>
            </div>

            <div class="mb-0">
                <label for="event_date" class="block text-gray-700 font-medium flex items-center gap-1">
                    <i data-lucide="calendar-days" class="w-4 h-4"></i> Event Date
                </label>
                <input type="text" name="event_date" id="event_date" required
                    placeholder="Select Event Date"
                    class="w-full mt-1 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[#1D503A]" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="course" class="block text-gray-700 flex items-center gap-1">
                        <i data-lucide="book-open" class="w-4 h-4"></i> Course
                    </label>
                    <input type="text" name="course" id="course" required
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-[#1D503A]" />
                </div>
                <div>
                    <label for="year" class="block text-gray-700 flex items-center gap-1">
                        <i data-lucide="layers" class="w-4 h-4"></i> Year
                    </label>
                    <input type="text" name="year" id="year" required
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-[#1D503A]" />
                </div>
                <div>
                    <label for="block" class="block text-gray-700 flex items-center gap-1">
                        <i data-lucide="layout" class="w-4 h-4"></i> Block
                    </label>
                    <input type="text" name="block" id="block" required
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-[#1D503A]" />
                </div>
            </div>

            <div class="mb-0">
                <label for="request_file" class="block text-gray-700 font-medium mb-0 flex items-center gap-1">
                    <i data-lucide="paperclip" class="w-4 h-4"></i> Attach Request Form (PDF/DOC/DOCX)
                </label>
                <div class="flex items-center gap-4">
                    <div class="flex-grow">
                        <input
                            type="file"
                            id="request_file"
                            name="request_file"
                            accept=".pdf,.doc,.docx"
                            class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4
                file:rounded-md file:border-0 file:text-sm file:font-semibold
                file:bg-[#1D503A] file:text-white hover:file:bg-[#144124]"
                            required />
                    </div>

                    <button type="submit"
                        class="ml-auto bg-[#1D503A] text-white px-6 py-2 rounded-md hover:bg-[#144124] transition">
                        Submit Request
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Lucide Script Init -->
    <script>
        lucide.createIcons();
    </script>

    <!-- Flatpickr Datepicker Init -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const today = new Date();
            flatpickr("#event_date", {
                minDate: today,
                dateFormat: "Y-m-d",
                disableMobile: true,

                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    const date = dayElem.dateObj;
                    const todayMidnight = new Date(today);
                    todayMidnight.setHours(0, 0, 0, 0);

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
    </script>