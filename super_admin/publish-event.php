<!-- Main Page Content -->
<main class="flex-1 overflow-y-auto p-6">

    <!-- Event Creation -->
    <section
        class="p-6 rounded-xl shadow border max-w-2xl mx-auto border-[#1D503A] bg-white"
        aria-labelledby="event-create-title">

        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-4">
                <h2 class="text-xl font-semibold text-[#1D503A]">Create New Event</h2>
                <p class="text-lg text-[#4A5D4C] inline-block border-b-2 border-[#1D503A] pb-1">
                    Fill out the form below to add a new event
                </p>
            </div>
        </div>

        <!-- Message -->
        <p
            class="text-sm text-center mb-4 min-h-[1.25rem]"
            style="color: <?php
                            if (isset($_SESSION['event_message'])) {
                                echo ($_SESSION['event_message_type'] ?? 'error') === 'success' ? '#16a34a' : '#dc2626';
                            } else {
                                echo 'transparent';
                            }
                            ?>;"
            role="alert"
            aria-live="polite">
            <?php
            if (isset($_SESSION['event_message'])) {
                echo htmlspecialchars($_SESSION['event_message']);
                unset($_SESSION['event_message'], $_SESSION['event_message_type']);
            } else {
                echo "&nbsp;";
            }
            ?>
        </p>

        <form action="create-event.php" method="POST" class="space-y-3" novalidate>
            <!-- Title -->
            <div class="relative">
                <label for="title" class="block mb-1 font-semibold text-[#1D503A] flex items-center gap-2 select-none cursor-pointer">
                    <i data-lucide="edit-2" class="w-5 h-5 text-[#1D503A]"></i>
                    <span>Event Title</span>
                </label>
                <input
                    id="title"
                    name="title"
                    type="text"
                    placeholder="Enter event title"
                    required
                    class="w-full pl-4 pr-3 py-2 border rounded-lg border-[#1D503A] text-[#1D503A] placeholder-[#6B7C6B] focus:outline-none focus:ring-2 focus:ring-[#1D503A] focus:border-[#14532D]"
                    aria-describedby="titleHelp" />
                <p id="titleHelp" class="text-xs text-[#4F766E] mt-0.5">
                    Give a clear and concise event title.
                </p>
            </div>

            <!-- Description -->
            <div class="relative">
                <label for="description" class="block mb-1 font-semibold text-[#1D503A] flex items-center gap-2 select-none cursor-pointer">
                    <i data-lucide="file-text" class="w-5 h-5 text-[#1D503A]"></i>
                    <span>Description</span>
                </label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe the event details"
                    required
                    rows="2"
                    class="w-full px-3 py-2 border rounded-lg border-[#1D503A] text-[#1D503A] placeholder-[#6B7C6B] resize-y focus:outline-none focus:ring-2 focus:ring-[#1D503A] focus:border-[#14532D]"
                    aria-describedby="descHelp"></textarea>
                <p id="descHelp" class="text-xs text-[#4F766E] mt-0.5">
                    Include important info attendees should know.
                </p>
            </div>

            <!-- Date -->
            <div class="relative">
                <label for="event_date" class="block mb-1 font-semibold text-[#1D503A] flex items-center gap-2 select-none cursor-pointer">
                    <i data-lucide="calendar" class="w-5 h-5 text-[#1D503A]"></i>
                    <span>Event Date</span>
                </label>
                <input
                    id="event_date"
                    name="event_date"
                    type="text"
                    placeholder="Select Event Date"
                    required
                    class="w-full px-3 py-2 border rounded-lg border-[#1D503A] text-[#1D503A] placeholder-[#6B7C6B] focus:outline-none focus:ring-2 focus:ring-[#1D503A] focus:border-[#14532D]"
                    aria-describedby="dateHelp" />
                <p id="dateHelp" class="text-xs text-[#4F766E] mt-0.5">
                    Select the date when the event will take place.
                </p>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full py-2.5 rounded-lg bg-[#1D503A] hover:bg-[#14532D] text-[#FAF5EE] font-semibold transition-colors">
                Publish Event
            </button>
        </form>
    </section>
</main>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (window.lucide && typeof lucide.replace === "function") {
            lucide.replace();
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        flatpickr("#event_date", {
            minDate: today,
            dateFormat: "Y-m-d",
            disableMobile: true,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj;
                if (date < today) {
                    dayElem.classList.add("flatpickr-disabled");
                    dayElem.setAttribute("aria-disabled", "true");

                    const xContainer = document.createElement("span");
                    xContainer.className = "absolute top-1 right-1 text-red-600";

                    xContainer.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>`;

                    dayElem.style.position = "relative";
                    dayElem.appendChild(xContainer);
                }
            }
        });
    });
</script>