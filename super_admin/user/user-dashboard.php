<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: ../landing-page.php");
  exit();
}

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'];

include('../config.php');

// Fetch full user data including profile_image
$query = "SELECT first_name, last_name, email, profile_image FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
  $user = $result->fetch_assoc();

  // Check if profile_image is set and not empty, else fallback to default image
  if (!empty($user['profile_image']) && file_exists("../uploads/" . $user['profile_image'])) {
    $imagePath = "../uploads/" . $user['profile_image'];
  } else {
    $imagePath = "../uploads/default.png"; // Or wherever your default image is stored
  }
} else {
  $imagePath = "../uploads/default.png";
}

$stmt->close();

// Fetch user event registrations (approved and pending)
$events = [];
$query = "
    SELECT e.id, e.title, e.description, e.event_date, er.status
    FROM events e
    JOIN event_registrations er ON e.id = er.event_id
    WHERE er.user_email = ?
    ORDER BY e.event_date ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $events[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" x-data="{
  sidebarOpen: localStorage.getItem('sidebarOpen') === 'true',
  tab: window.location.hash ? window.location.hash.substring(1) : 'registeredEvents'
}" x-init="
  $watch('tab', value => {
    window.location.hash = value;
  });
  $watch('sidebarOpen', value => {
    localStorage.setItem('sidebarOpen', value);
  });
" class="h-full">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Dashboard</title>
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .btn-primary {
      background-color: #1D503A;
      color: #FAF5EE;
      transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #15412B;
    }

    .btn-upload {
      color: #1D503A;
      border: 1.5px solid #1D503A;
      background-color: #FAF5EE;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .btn-upload:hover {
      background-color: #1D503A;
      color: #FAF5EE;
    }

    input:focus,
    textarea:focus {
      outline: none;
      box-shadow: 0 0 0 3px #1D503A;
      border-color: #1D503A;
    }

    .sidebar-link:hover {
      background-color: #E5E1DB;
      color: #1D503A;
    }

    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-thumb {
      background-color: #1D503A;
      border-radius: 4px;
    }
  </style>
</head>

<body class="flex h-screen text-gray-800 font-sans" style="background-color: #FAF5EE;">
  <!-- Sidebar -->
  <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed z-30 inset-y-0 left-0 w-64 transition duration-300 transform border-r md:translate-x-0 md:static md:inset-0 flex flex-col justify-between py-6 sidebar-shadow-right"
    style="background-color: #FAF5EE; border-color: #1D503A;">
    <div class="absolute top-2 right-2 md:hidden p-2">
      <button @click="sidebarOpen = false" aria-label="Close sidebar" class="text-2xl font-bold text-[#1D503A] hover:text-[#15412B] focus:outline-none">
        &times;
      </button>
    </div>

    <!-- Logo Section -->
    <div class="flex flex-col items-center border-b pb-4 px-6" style="border-color: #1D503A;">
      <a href="https://gordoncollege.edu.ph/w3/">
        <img src="../Images/gclogo.png" alt="Gordon Logo" class="w-32 h-auto mb-2" />
      </a>
      <p class="text-center font-semibold text-[#1D503A] text-sm">Gordon College</p>
    </div>

    <!-- Navigation with Alpine tabs -->
    <nav class="px-6 flex flex-col space-y-3 mt-6">

      <!--Create event with  Request form-->
      <button
        @click="tab = 'requestEvent'"
        :class="tab === 'requestEvent' ? 'bg-[#1D503A] text-[#FAF5EE]' : 'bg-[#FAF5EE] text-[#1D503A] hover:bg-[#E5E1DB]'"
        class="block w-full text-center px-4 py-2 rounded font-semibold transition">Create Event</button>

      <!--Registration-->
      <button
        @click="tab = 'userEvents'"
        :class="tab === 'userEvents' ? 'bg-[#1D503A] text-[#FAF5EE]' : 'bg-[#FAF5EE] text-[#1D503A] hover:bg-[#E5E1DB]'"
        class="block w-full text-center px-4 py-2 rounded font-semibold transition">Registration</button>

      <!--View Event Details-->
      <button
        @click="tab = 'registeredEvents'"
        :class="tab === 'registeredEvents' ? 'bg-[#1D503A] text-[#FAF5EE]' : 'bg-[#FAF5EE] text-[#1D503A] hover:bg-[#E5E1DB]'"
        class="block w-full text-center px-4 py-2 rounded font-semibold transition">View Events</button>

    </nav>

    <div class="px-6 mt-auto">
      <!-- Alpine.js Sidebar Drawer -->
      <div x-data="{ showAbout: false }" class="relative z-50">

        <!-- Trigger Button -->
        <button
          @click="showAbout = true"
          class="w-full bg-[#1D503A] text-[#FAF5EE] px-4 py-2 rounded font-semibold hover:bg-[#15412B] transition">
          About This App
        </button>


        <!-- Overlay -->
        <div
          x-show="showAbout"
          x-transition.opacity
          class="fixed inset-0 bg-black bg-opacity-50 z-40"
          @click="showAbout = false">
        </div>

        <!-- Drawer Panel -->
        <div
          x-show="showAbout"
          x-transition:enter="transition transform duration-300"
          x-transition:enter-start="translate-x-full"
          x-transition:enter-end="translate-x-0"
          x-transition:leave="transition transform duration-300"
          x-transition:leave-start="translate-x-0"
          x-transition:leave-end="translate-x-full"
          class="fixed top-0 right-0 w-full max-w-sm h-full bg-white shadow-xl z-50 rounded-l-2xl flex flex-col"
          @keydown.escape.window="showAbout = false">
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-green-700">About This App</h2>
            <button @click="showAbout = false" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="p-4 overflow-y-auto space-y-4 text-sm text-gray-700">

            <!-- System Description -->
            <p class="text-gray-600">
              <strong>Gordon College Online Event Registration (GCOER) v1</strong> is a web-based platform designed for all Gordon College students (GCIANS) to easily register and participate in official college events organized by various departments. This system streamlines event management and ensures a more efficient and accessible registration experience for the entire student community.
            </p>

            <!-- Proposed By -->
            <div>
              <h3 class="font-semibold text-[#1D503A] mb-2">Proposed by:</h3>
              <ul class="space-y-1 ml-4 list-disc">
                <li>Jomar Cazeñas — BSIT 2D</li>
                <li>Carl James Bugero — BSIT 2D</li>
              </ul>
            </div>

            <!-- System Advisors -->
            <div>
              <h3 class="font-semibold text-[#1D503A] mb-2">Supervised by:</h3>
              <ul class="space-y-1 ml-4 list-disc">
                <li>Reynaldo G. Bautista — Program Coordinator, College of Computer Studies</li>
                <li>Ronnie D. Luy — Assistant Dean, College of Computer Studies</li>
                <li>Joseph Pusing — AppDev Instructor </li>
                <li>Mr. Melner Balce — Web Administrator, Management Information Systems Unit</li>
                <li>Mr. Randy Estroque — Head, Management Information Systems Unit</li>
              </ul>
            </div>

            <!-- Administration Acknowledgment -->
            <div>
              <h3 class="font-semibold text-[#1D503A] mb-2">Acknowledged by:</h3>
              <ul class="space-y-1 ml-4 list-disc">
                <li>Dr. Erlinda C. Abarintos — Vice-President for Administration and Finance</li>
                <li>Prof. Arlida M. Pame — College President</li>
              </ul>
            </div>

          </div>

        </div>
      </div>
      <!--Users email account-->
      <div class="border-t border-gray-200 my-4" style="border-color: #1D503A;"></div>
      <div class="flex flex-col items-center space-y-4">
        <p class="text-sm text-[#1D503A] text-center break-words"><?= htmlspecialchars($email) ?></p>
      </div>
    </div>
  </div>

  <!-- Main Content Area -->
  <div class="flex-1 flex flex-col overflow-hidden">

    <!-- Top Navbar -->
    <header class="px-6 py-2 flex justify-between items-center shadow md:pl-6" style="background-color: #1D503A; color: #FAF5EE;">
      <button @click="sidebarOpen = !sidebarOpen" class="md:hidden focus:outline-none" aria-label="Toggle sidebar menu">
        <svg class="w-6 h-6 text-[#FAF5EE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <h1 class="text-lg font-semibold">Student Dashboard</h1>

      <div x-data="{ open: false }" class="relative flex items-center space-x-3">
        <!-- Profile Image -->
        <img src="../uploads/<?php echo htmlspecialchars($imagePath); ?>" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow object-cover hover:scale-110 transition duration-300 ease-in-out" />

        <!-- Name & Dropdown -->
        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none" aria-haspopup="true" :aria-expanded="open.toString()">
          <span class="text-sm font-medium"><?php echo htmlspecialchars($name); ?></span>
        </button>

        <!-- Alpine.js Wrapper -->
        <div x-data="{ open: false, showProfileModal: false }" class="relative">

          <!-- Dropdown Trigger (icon or simple avatar) -->
          <button @click="open = !open" class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center hover:bg-green-700 focus:outline-none">
            <!-- Replace with an avatar image or icon if desired -->
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A4 4 0 0112 16h0a4 4 0 016.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <div
            x-show="open"
            @click.away="open = false"
            x-transition
            class="absolute right-0 mt-2 w-32 bg-white border rounded shadow text-gray-800 z-20">
            <button
              @click="showProfileModal = true; open = false"
              class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100">
              Profile
            </button>
            <a
              href="../logout.php"
              class="block px-4 py-2 text-sm hover:bg-gray-100">
              Logout
            </a>
          </div>

          <!-- Profile Modal -->
          <div
            x-show="showProfileModal"
            x-transition
            class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center px-4 overflow-auto">
            <div
              @click.away="showProfileModal = false"
              class="rounded-2xl shadow-2xl w-full max-w-md relative p-8 sm:p-10"
              style="background-color: #E5E1DB; color: #1D503A;">
              <button
                @click="showProfileModal = false"
                class="absolute top-4 right-5 text-[#1D503A] hover:text-[#144124] text-2xl">&times;</button>

              <h2 class="text-2xl font-bold mb-6 text-center">Edit Profile</h2>

              <!-- Profile Image Upload -->
              <form action="../upload-image.php" method="POST" enctype="multipart/form-data" class="w-full text-center mb-6">
                <input
                  type="file"
                  name="profile_image"
                  accept="image/*"
                  class="block w-full text-sm text-[#1D503A] mb-2 file:mr-2 file:py-1 file:px-3 file:rounded file:border file:text-sm file:bg-[#C9C3B9] file:text-[#1D503A] hover:file:bg-[#B0A998]" />
                <button
                  type="submit"
                  class="w-full px-4 py-2 text-sm font-semibold rounded-full bg-white text-red-500 border border-red-300 hover:bg-gray-200 transition">
                  Upload
                </button>
              </form>

              <!-- Profile Info Update -->
              <form action="../user/update-profile.php" method="POST" class="mb-6">
                <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1 text-[#1D503A]">First Name</label>
                    <input
                      type="text"
                      name="first_name"
                      value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-[#1D503A] focus:outline-none focus:ring-2 focus:ring-[#1D503A]"
                      placeholder="First Name"
                      required>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1 text-[#1D503A]">Last Name</label>
                    <input
                      type="text"
                      name="last_name"
                      value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-[#1D503A] focus:outline-none focus:ring-2 focus:ring-[#1D503A]"
                      placeholder="Last Name"
                      required>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="block text-sm font-medium mb-1 text-[#1D503A]">Password (optional)</label>
                  <input
                    type="password"
                    name="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-[#1D503A] focus:outline-none focus:ring-2 focus:ring-[#1D503A]"
                    placeholder="New Password">
                  <p class="text-xs text-gray-600 mt-1">Leave blank if you don't want to change it.</p>
                </div>

                <div class="mb-4">
                  <label class="block text-sm font-medium mb-1 text-[#1D503A]">Email</label>
                  <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-[#1D503A] focus:outline-none focus:ring-2 focus:ring-[#1D503A]"
                    placeholder="you@example.com"
                    required>
                </div>

                <div class="flex justify-end gap-3">
                  <button
                    type="button"
                    @click="showProfileModal = false"
                    class="px-6 py-2 text-sm font-semibold rounded-full bg-[#C9C3B9] text-[#1D503A] hover:bg-[#B0A998] transition">
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-6 py-2 text-sm font-semibold rounded-full bg-[#1D503A] text-white hover:bg-[#144124] transition shadow-md">
                    Save
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
    </header>



    <!-- Page Content -->
    <main class="p-6 pt-6 overflow-y-auto flex-1 min-h-[400px]">

      <section x-show="tab === 'requestEvent'" x-transition x-cloak>
        <h2 class="text-2xl font-bold mb-6 text-green-700 text-center"></h2>
        <?php include 'request-event.php'; ?>
      </section>

      <section x-show="tab === 'registeredEvents'" x-transition x-cloak>
        <h2 class="text-2xl font-bold mb-6 text-green-700 text-center"></h2>
        <?php include 'registered-events.php'; ?>
      </section>

      <section x-show="tab === 'userEvents'" x-transition x-cloak>
        <?php include 'user-events.php'; ?>
      </section>

    </main>

  </div>
  </div>
</body>

</html>