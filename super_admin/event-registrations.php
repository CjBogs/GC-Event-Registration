<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once '../config.php';

// Summary counts
$summaryQueries = [
  'events' => "SELECT COUNT(*) AS count FROM events WHERE status = 'approved'",
  'registrations' => "SELECT COUNT(*) AS count FROM event_registrations",
  'pending' => "SELECT COUNT(*) AS count FROM events WHERE status = 'pending'",
];

$summary = [];
foreach ($summaryQueries as $key => $query) {
  $result = $conn->query($query);
  $summary[$key] = ($result && $row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
}

// Fetch registered students per event
$registrationsSql = "
  SELECT e.title AS event_name, u.first_name, u.last_name, u.email, r.registration_date
  FROM event_registrations r
  JOIN users u ON r.user_email = u.email
  JOIN events e ON r.event_id = e.id
  ORDER BY e.title ASC, r.registration_date DESC
";
$registrationsResult = $conn->query($registrationsSql);
$events = [];

if ($registrationsResult) {
  while ($row = $registrationsResult->fetch_assoc()) {
    $eventName = $row['event_name'];
    $formattedDate = 'N/A';
    if (!empty($row['registration_date'])) {
      try {
        $dt = new DateTime($row['registration_date']);
        $formattedDate = $dt->format('F j, Y - g:i A');
      } catch (Exception $e) {
        $formattedDate = 'Invalid date';
      }
    }
    $events[$eventName][] = [
      'student_name' => $row['first_name'] . ' ' . $row['last_name'],
      'email' => $row['email'],
      'registered_at' => $formattedDate,
    ];
  }
}
?>

<!-- Main Content -->
<div style="max-height: 80vh; overflow-y: auto;" class="px-4 py-6" x-data="chartLoader" x-init="init">
  <div class="p-4 max-w-7xl mx-auto space-y-10">

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php
      $icons = [
        'events' => '<svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M8 7v10M16 7v10M3 7h18"></path></svg>',
        'registrations' => '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M15.75 7.5v.75M8.25 7.5v.75M12 7.5v.75M4.5 18.75h15M4.5 6.75h15M7.5 3v1.5M16.5 3v1.5M12 3v1.5"></path></svg>',
        'pending' => '<svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg>',
      ];
      foreach (['events' => 'Total Events', 'registrations' => 'Total Registrations', 'pending' => 'Pending Approvals'] as $key => $label): ?>
        <div class="bg-white p-6 rounded-xl shadow flex items-center space-x-4">
          <?= $icons[$key] ?>
          <div>
            <h4 class="text-gray-500 text-sm"><?= $label ?></h4>
            <p class="text-3xl font-bold text-[#1D503A]"><?= $summary[$key] ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Chart Section -->
    <div class="bg-white p-6 rounded-xl shadow">
      <h3 class="text-xl font-bold text-[#1D503A] mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-[#1D503A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 3v18h18"></path>
          <path d="M6 16h2v2H6zM10 10h2v8h-2zM14 6h2v12h-2z"></path>
        </svg>
        Participants Per Event
      </h3>
      <canvas id="participantsChart" class="w-full h-64 mb-4"></canvas>
      <div class="flex gap-4">
        <button id="downloadChart" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"></path>
            <path d="M12 12v6m0 0l-4-4m4 4l4-4"></path>
          </svg>
          Download PNG
        </button>
        <button id="downloadPDF" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
            <path d="M17 21v-8H7v8"></path>
            <path d="M17 13l-2-2"></path>
          </svg>
          Download PDF
        </button>
      </div>
    </div>

    <!-- Registered Students Table -->
    <div class="space-y-8">
      <?php foreach ($events as $eventTitle => $students): ?>
        <?php $eventId = 'eventTable_' . md5($eventTitle); ?>
        <div class="bg-white p-6 rounded-2xl shadow-lg overflow-x-auto border border-gray-200">
          <h3 class="text-2xl font-semibold text-[#1D503A] mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-[#1D503A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 4h18M3 12h18M3 20h18"></path>
            </svg>
            <?= htmlspecialchars($eventTitle) ?>
          </h3>

          <!-- Search -->
          <div class="mb-4">
            <input
              type="text"
              placeholder="Search students by name or email..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1D503A]"
              oninput="filterTableRows('<?= $eventId ?>', this.value)" />
          </div>

          <!-- Table -->
          <div class="overflow-y-auto max-h-[24rem] border border-gray-200 rounded-lg">
            <table id="<?= $eventId ?>" class="min-w-full text-sm text-left bg-white">
              <thead class="bg-[#1D503A] text-white sticky top-0 z-10">
                <tr>
                  <th class="px-6 py-3">Student Name</th>
                  <th class="px-6 py-3">Email</th>
                  <th class="px-6 py-3">Registered At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $student): ?>
                  <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-3 whitespace-nowrap"><?= htmlspecialchars($student['student_name']) ?></td>
                    <td class="px-6 py-3 whitespace-nowrap"><?= htmlspecialchars($student['email']) ?></td>
                    <td class="px-6 py-3 whitespace-nowrap"><?= htmlspecialchars($student['registered_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
      let participantsChart;

      document.addEventListener('alpine:init', () => {
        Alpine.data('chartLoader', () => ({
          init() {
            fetch('chart-data.php')
              .then(res => res.json())
              .then(data => {
                const ctx = document.getElementById('participantsChart').getContext('2d');
                participantsChart = new Chart(ctx, {
                  type: 'bar',
                  data: {
                    labels: data.map(d => d.event),
                    datasets: [{
                      label: 'Participants',
                      data: data.map(d => d.participants),
                      backgroundColor: '#16a34a',
                      borderRadius: 5,
                    }],
                  },
                  options: {
                    responsive: true,
                    plugins: {
                      legend: {
                        display: false
                      },
                      title: {
                        display: true,
                        text: 'Participants Per Event',
                      },
                    },
                  },
                });
              });
          }
        }));
      });

      document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('downloadChart').addEventListener('click', () => {
          if (participantsChart) {
            const link = document.createElement('a');
            link.href = participantsChart.toBase64Image();
            link.download = 'participants_chart.png';
            link.click();
          }
        });

        document.getElementById('downloadPDF').addEventListener('click', async () => {
          const chartContainer = document.getElementById('chartContainer');
          const canvas = await html2canvas(chartContainer, {
            useCORS: true,
            scale: 2
          });
          const imgData = canvas.toDataURL('image/png');
          const {
            jsPDF
          } = window.jspdf;
          const pdf = new jsPDF();
          const width = pdf.internal.pageSize.getWidth();
          const height = (canvas.height * width) / canvas.width;
          pdf.addImage(imgData, 'PNG', 0, 10, width, height);
          pdf.save('participants_chart.pdf');
        });
      });

      function filterTableRows(tableId, searchValue) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tbody tr');
        const filter = searchValue.toLowerCase();
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(filter) ? '' : 'none';
        });
      }
    </script>