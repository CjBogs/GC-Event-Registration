<?php
require_once '../config.php';

$query = "
  SELECT 
    er.id,
    u.first_name,
    u.last_name,
    u.email,
    e.title AS event_title,
    e.event_date,
    er.status
  FROM event_registrations er
  JOIN users u ON er.user_email = u.email
  JOIN events e ON er.event_id = e.id
  WHERE er.status = 'approved'
  ORDER BY e.event_date ASC, u.last_name ASC
";

$result = $conn->query($query);
?>

<div class="pt-4 px-6 pb-8">
  <?php if ($result && $result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow border border-[#1D503A]">
      <table class="min-w-full text-sm text-left text-gray-700">
        <thead class="bg-gray-100 border-b text-xs text-gray-500 uppercase sticky top-0 z-10">
          <tr>
            <th class="px-6 py-4">Student Name</th>
            <th class="px-6 py-4">Email</th>
            <th class="px-6 py-4">Event Title</th>
            <th class="px-6 py-4">Event Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-6 py-4 font-medium text-gray-900">
                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
              </td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['email']) ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['event_title']) ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['event_date']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
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
      <p class="text-lg font-semibold">No registered students found.</p>
      <p class="text-sm text-[#4F766E]">Awaiting registrations.</p>
    </div>
  <?php endif; ?>
</div>